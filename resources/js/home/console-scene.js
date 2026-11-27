/**
 * Hero 3D scene: a live mixing console on a dark stage.
 *
 * - A large-format digital desk (see dlive-surface.js): fader banks, keys, encoders, touchscreens.
 * - The meters and screens "play" a simulated worship mix (72 BPM); levels follow the faders.
 * - Stage beams in the brand blue/red, haze particles, and an LED wall showing the cover photo.
 * - Boot: faders run their self-test sweep, LEDs light up. Scroll pushes the faders up and flies the camera in.
 *
 * Colours come from the theme tokens (the dark palette, since the hero is always dark), read from CSS variables.
 * The caller owns the loop (GSAP ticker), visibility and scroll; this module only builds and draws.
 */
import {
    ACESFilmicToneMapping,
    AdditiveBlending,
    AmbientLight,
    BoxGeometry,
    BufferGeometry,
    CanvasTexture,
    Color,
    CylinderGeometry,
    DirectionalLight,
    DoubleSide,
    Float32BufferAttribute,
    Fog,
    Group,
    HalfFloatType,
    InstancedMesh,
    MathUtils,
    Mesh,
    MeshBasicMaterial,
    MeshStandardMaterial,
    Object3D,
    PerspectiveCamera,
    PlaneGeometry,
    PMREMGenerator,
    PointLight,
    Points,
    PointsMaterial,
    Scene,
    ShaderMaterial,
    SpotLight,
    SRGBColorSpace,
    TextureLoader,
    Vector2,
    Vector3,
    WebGLRenderer,
    WebGLRenderTarget,
} from 'three';
import { RoundedBoxGeometry } from 'three/addons/geometries/RoundedBoxGeometry.js';
import { RoomEnvironment } from 'three/addons/environments/RoomEnvironment.js';
import { EffectComposer } from 'three/addons/postprocessing/EffectComposer.js';
import { RenderPass } from 'three/addons/postprocessing/RenderPass.js';
import { UnrealBloomPass } from 'three/addons/postprocessing/UnrealBloomPass.js';
import { OutputPass } from 'three/addons/postprocessing/OutputPass.js';
import { SMAAPass } from 'three/addons/postprocessing/SMAAPass.js';
import { buildSurface } from './dlive-surface';


/** Reads an "R G B" token from the dark theme scope, so no colour is hardcoded here. */
function readTokens() {
    const probe = document.createElement('div');
    probe.className = 'dark';
    probe.hidden = true;
    document.body.append(probe);
    const style = getComputedStyle(probe);
    const read = (name, fallback) => {
        const parts = style.getPropertyValue(name).trim().split(/\s+/).map(Number);
        return parts.length === 3 && parts.every((n) => !Number.isNaN(n))
            ? new Color(parts[0] / 255, parts[1] / 255, parts[2] / 255).convertSRGBToLinear()
            : fallback;
    };
    const tokens = {
        primary: read('--color-primary', new Color(0.04, 0.5, 0.94)),
        accent: read('--color-accent', new Color(0.9, 0.05, 0.12)),
        background: read('--color-background', new Color(0.002, 0.002, 0.004)),
        surface: read('--color-surface', new Color(0.006, 0.007, 0.012)),
        text: read('--color-text', new Color(0.88, 0.91, 0.94)),
    };
    probe.remove();
    return tokens;
}

/** Small seeded PRNG so the console looks the same on every visit. */
function seeded(seed) {
    return () => {
        seed |= 0;
        seed = (seed + 0x6d2b79f5) | 0;
        let t = Math.imul(seed ^ (seed >>> 15), 1 | seed);
        t = (t + Math.imul(t ^ (t >>> 7), 61 | t)) ^ t;
        return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
    };
}

const smooth = (edge0, edge1, x) => {
    const t = MathUtils.clamp((x - edge0) / (edge1 - edge0), 0, 1);
    return t * t * (3 - 2 * t);
};

function softDotTexture() {
    const size = 64;
    const canvas = document.createElement('canvas');
    canvas.width = canvas.height = size;
    const ctx = canvas.getContext('2d');
    const g = ctx.createRadialGradient(size / 2, size / 2, 0, size / 2, size / 2, size / 2);
    g.addColorStop(0, 'rgba(255,255,255,1)');
    g.addColorStop(0.4, 'rgba(255,255,255,0.35)');
    g.addColorStop(1, 'rgba(255,255,255,0)');
    ctx.fillStyle = g;
    ctx.fillRect(0, 0, size, size);
    return new CanvasTexture(canvas);
}

function beamMaterial(color) {
    return new ShaderMaterial({
        uniforms: {
            uColor: { value: color.clone() },
            uIntensity: { value: 0 },
            uHeight: { value: 12 },
        },
        vertexShader: /* glsl */ `
            uniform float uHeight;
            varying float vAlong;
            varying vec3 vNormal;
            varying vec3 vView;
            void main() {
                vAlong = clamp(-position.y / uHeight, 0.0, 1.0);
                vec4 mv = modelViewMatrix * vec4(position, 1.0);
                vNormal = normalize(normalMatrix * normal);
                vView = normalize(-mv.xyz);
                gl_Position = projectionMatrix * mv;
            }
        `,
        fragmentShader: /* glsl */ `
            uniform vec3 uColor;
            uniform float uIntensity;
            varying float vAlong;
            varying vec3 vNormal;
            varying vec3 vView;
            void main() {
                float facing = pow(abs(dot(normalize(vNormal), normalize(vView))), 1.8);
                float fall = pow(1.0 - vAlong, 1.6) * smoothstep(0.0, 0.08, vAlong);
                float a = facing * fall * uIntensity;
                gl_FragColor = vec4(uColor * a, a);
            }
        `,
        transparent: true,
        depthWrite: false,
        blending: AdditiveBlending,
        side: DoubleSide,
    });
}

function ledWallMaterial() {
    return new ShaderMaterial({
        uniforms: {
            map: { value: null },
            uBright: { value: 0 },
            uTime: { value: 0 },
            uGrid: { value: new Vector2(180, 68) },
        },
        vertexShader: /* glsl */ `
            varying vec2 vUv;
            void main() {
                vUv = uv;
                gl_Position = projectionMatrix * modelViewMatrix * vec4(position, 1.0);
            }
        `,
        fragmentShader: /* glsl */ `
            uniform sampler2D map;
            uniform float uBright;
            uniform float uTime;
            uniform vec2 uGrid;
            varying vec2 vUv;
            void main() {
                vec2 cellUv = vUv * uGrid;
                vec2 cell = fract(cellUv) - 0.5;
                float dotMask = smoothstep(0.5, 0.22, length(cell));
                // Far away the dots get smaller than a pixel: blend to a flat panel instead of shimmering.
                float aa = clamp(1.6 - fwidth(cellUv.x) * 2.2, 0.0, 1.0);
                float mask = mix(0.55, dotMask, aa);
                vec3 color = texture2D(map, (floor(cellUv) + 0.5) / uGrid).rgb;
                float scan = 0.9 + 0.1 * sin(vUv.y * 420.0 - uTime * 3.0);
                float edge = smoothstep(0.0, 0.06, vUv.x) * smoothstep(1.0, 0.94, vUv.x) * smoothstep(0.0, 0.1, vUv.y) * smoothstep(1.0, 0.9, vUv.y);
                gl_FragColor = vec4(color * mask * scan * edge * uBright, 1.0);
                #include <tonemapping_fragment>
                #include <colorspace_fragment>
            }
        `,
    });
}

export function createConsoleScene(canvas, { mobile = false, wallImage = null } = {}) {
    const tokens = readTokens();
    const random = seeded(1977);

    // Renderer -----------------------------------------------------------------------------------------
    const renderer = new WebGLRenderer({ canvas, antialias: false, powerPreference: 'high-performance' });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, mobile ? 1.5 : 1.75));
    renderer.toneMapping = ACESFilmicToneMapping;
    renderer.toneMappingExposure = 1.05;
    renderer.outputColorSpace = SRGBColorSpace;

    const scene = new Scene();
    scene.background = tokens.background.clone();
    scene.fog = new Fog(tokens.background.clone(), 9, 26);

    const pmrem = new PMREMGenerator(renderer);
    scene.environment = pmrem.fromScene(new RoomEnvironment(), 0.04).texture;
    scene.environmentIntensity = 0.35;
    pmrem.dispose();

    const camera = new PerspectiveCamera(35, 1, 0.1, 60);

    // Console --------------------------------------------------------------------------------------------
    const rig = new Group(); // moves for layout (right of the text on wide screens)
    const deck = new Group(); // the console itself, sloped slightly toward the operator
    deck.rotation.x = 0.08;
    rig.add(deck);
    scene.add(rig);

    const surface = buildSurface({ tokens, mobile, random });
    // Scale the desk so the framing stays the same whether it has one bank (phones) or two.
    deck.scale.setScalar((mobile ? 5.6 : 8.2) / surface.width);
    deck.position.z = -0.25;
    deck.add(surface.group);

    // Stage: LED wall, beams, haze, lights --------------------------------------------------------------
    const wallMat = ledWallMaterial();
    const wall = new Mesh(new PlaneGeometry(16, 16 * (609 / 1600)), wallMat);
    wall.position.set(0, 2.6, -10);
    scene.add(wall);
    if (wallImage) {
        new TextureLoader().load(wallImage, (texture) => {
            texture.colorSpace = SRGBColorSpace;
            wallMat.uniforms.map.value = texture;
            wallMat.needsUpdate = true;
        });
    } else {
        wall.visible = false;
    }

    const beams = [];
    const beamGeo = new CylinderGeometry(0.06, 2.4, 12, 40, 1, true);
    beamGeo.translate(0, -6, 0);
    [
        { x: -6, color: tokens.primary, tilt: -0.5, sway: 0.18, speed: 0.31 },
        { x: -2.2, color: tokens.accent, tilt: -0.2, sway: 0.22, speed: 0.23 },
        { x: 2.2, color: tokens.primary, tilt: 0.2, sway: 0.2, speed: 0.27 },
        { x: 6, color: tokens.accent, tilt: 0.5, sway: 0.16, speed: 0.35 },
    ].forEach((b, i) => {
        const mesh = new Mesh(beamGeo, beamMaterial(b.color));
        mesh.position.set(b.x, 7.5, -4.5);
        mesh.userData = { ...b, phase: i * 1.7 };
        scene.add(mesh);
        beams.push(mesh);
    });

    const hazeCount = mobile ? 220 : 520;
    const hazePositions = new Float32Array(hazeCount * 3);
    for (let i = 0; i < hazeCount; i++) {
        hazePositions[i * 3] = (random() - 0.5) * 18;
        hazePositions[i * 3 + 1] = random() * 7 - 0.5;
        hazePositions[i * 3 + 2] = -8 + random() * 11;
    }
    const hazeGeo = new BufferGeometry();
    hazeGeo.setAttribute('position', new Float32BufferAttribute(hazePositions, 3));
    const hazeMat = new PointsMaterial({
        size: 0.07,
        map: softDotTexture(),
        transparent: true,
        opacity: 0,
        depthWrite: false,
        blending: AdditiveBlending,
        color: tokens.text.clone().lerp(tokens.primary, 0.35),
    });
    const haze = new Points(hazeGeo, hazeMat);
    scene.add(haze);

    const ambient = new AmbientLight(0xffffff, 0);
    const key = new DirectionalLight(0xffffff, 0);
    key.position.set(2, 6, 5);
    const blueSpot = new SpotLight(tokens.primary, 0, 22, 0.55, 0.6, 1.2);
    blueSpot.position.set(-6, 7, -1);
    const redSpot = new SpotLight(tokens.accent, 0, 22, 0.55, 0.6, 1.2);
    redSpot.position.set(6, 7, -1);
    blueSpot.target = redSpot.target = rig;
    const meterGlow = new PointLight(tokens.primary, 0, 4, 1.6);
    meterGlow.position.set(0, 1.0, -1.2);
    rig.add(meterGlow);
    scene.add(ambient, key, blueSpot, redSpot);

    // Post: bloom makes the LEDs and beams glow ---------------------------------------------------------
    // No MSAA on the target: multisampled half-float targets render blank on some GPUs/drivers.
    // Edges are smoothed by SMAA at the end instead (desktop only; phones have dense pixels anyway).
    const target = new WebGLRenderTarget(1, 1, { type: HalfFloatType });
    const composer = new EffectComposer(renderer, target);
    composer.addPass(new RenderPass(scene, camera));
    const bloom = new UnrealBloomPass(new Vector2(1, 1), mobile ? 0.7 : 0.95, 0.55, 0.82);
    composer.addPass(bloom);
    composer.addPass(new OutputPass());
    if (!mobile) composer.addPass(new SMAAPass());

    // State (driven from outside by GSAP) ----------------------------------------------------------------
    const state = {
        power: 0, // 0..1 lights and meters coming up
        boot: 0, // 0..1 motorized fader self-test sweep
        progress: 0, // 0..1 scroll through the hero
        pointerX: 0,
        pointerY: 0,
    };

    const cam = { x: 0, y: 0 };
    const look = new Vector3();
    const camStart = new Vector3();
    const camEnd = new Vector3();
    const lookStart = new Vector3();
    const lookEnd = new Vector3();
    let layout = null;

    function resize() {
        const w = canvas.clientWidth || 1;
        const h = canvas.clientHeight || 1;
        const aspect = w / h;
        renderer.setSize(w, h, false);
        composer.setSize(w, h);
        bloom.resolution.set(w / 2, h / 2);
        camera.aspect = aspect;
        camera.fov = aspect < 0.9 ? 48 : 35;
        camera.updateProjectionMatrix();
        // Wide screens: the console sits right of the text and the camera swoops in low from the left.
        // Tall screens (phones, portrait tablets): centred behind the text, overflowing the sides a little,
        // and the camera pushes straight in over the faders.
        const wide = aspect >= 1.25;
        const x = wide ? Math.min(2.6, (aspect - 1.25) * 3 + 1.2) : 0;
        const d = wide ? 1 : MathUtils.clamp(0.75 / aspect, 1, 1.7);
        layout = wide
            ? {
                  start: [x * 0.15, 3.7, 8.4],
                  lookStart: [x * 0.55, 0.15, -0.6],
                  end: [x - 3.4, 1.15, 2.6],
                  lookEnd: [x + 0.8, 0.25, -0.7],
              }
            : {
                  start: [0, 5.2 * d, 7.2 * d],
                  lookStart: [0, -0.35, -0.4],
                  // The hero scrolls away here (no pin), so by the end only its lower half is on screen:
                  // aim above the console so it sits low in the frame, where the viewer still sees it.
                  end: [0.4, 2.1 * d, 3.6 * d],
                  lookEnd: [0, 1.7, -1.6],
              };
        rig.position.set(x, 0, 0);
        camStart.fromArray(layout.start);
        lookStart.fromArray(layout.lookStart);
        camEnd.fromArray(layout.end);
        lookEnd.fromArray(layout.lookEnd);
    }

    function updateCamera() {

        const p = smooth(0, 1, state.progress);
        cam.x += (state.pointerX - cam.x) * 0.045;
        cam.y += (state.pointerY - cam.y) * 0.045;

        camera.position.lerpVectors(camStart, camEnd, p);
        camera.position.x += cam.x * 0.6;
        camera.position.y += cam.y * 0.35;
        look.lerpVectors(lookStart, lookEnd, p);
        camera.lookAt(look);
    }

    function updateStage(time) {
        const power = state.power;
        ambient.intensity = 0.25 * power;
        key.intensity = 0.9 * power;
        blueSpot.intensity = 48 * power;
        redSpot.intensity = 36 * power;
        meterGlow.intensity = 2.2 * power;
        hazeMat.opacity = 0.45 * power;
        wallMat.uniforms.uBright.value = 0.55 * power;
        wallMat.uniforms.uTime.value = time;

        beams.forEach((beam) => {
            const { tilt, sway, speed, phase } = beam.userData;
            beam.rotation.z = tilt + Math.sin(time * speed + phase) * sway;
            beam.rotation.x = 0.35 + Math.sin(time * speed * 0.7 + phase) * 0.12;
            beam.material.uniforms.uIntensity.value = (0.22 + 0.06 * Math.sin(time * 1.3 + phase)) * power;
        });

        const positions = hazeGeo.attributes.position.array;
        for (let i = 0; i < hazeCount; i++) {
            positions[i * 3 + 1] += 0.0016 + Math.sin(time * 0.3 + i) * 0.0008;
            positions[i * 3] += Math.sin(time * 0.2 + i * 0.37) * 0.0012;
            if (positions[i * 3 + 1] > 6.5) positions[i * 3 + 1] = -0.5;
        }
        hazeGeo.attributes.position.needsUpdate = true;
    }

    let last = 0;
    function render(timeSeconds) {
        const dt = last ? Math.min(0.05, timeSeconds - last) : 0.016;
        last = timeSeconds;
        updateCamera();
        surface.update(timeSeconds, dt, state);
        updateStage(timeSeconds);
        composer.render();
    }

    resize();

    return {
        state,
        resize,
        render,
        dispose() {
            surface.dispose();
            composer.dispose();
            renderer.dispose();
            scene.traverse((obj) => {
                obj.geometry?.dispose();
                if (obj.material) [].concat(obj.material).forEach((m) => m.dispose());
            });
        },
    };
}
