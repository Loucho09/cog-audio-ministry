/**
 * Hero 3D scene: a digital mixing surface on a dark stage (see dlive-surface.js).
 *
 * - PBR materials (brushed metal, matte plastic, rubber caps, clearcoat glass) lit by RoomEnvironment,
 *   ACES tone mapping and sRGB output. A soft contact shadow sits under the desk (no hard shadow maps).
 * - Post: bloom only catches values above the threshold, which the LEDs and bright screens exceed.
 *   Light depth of field (BokehPass) and SMAA run on desktop only.
 * - The screens and meters "play" a simulated mix. hero.js drives the state: load-in, standby power, and the
 *   scroll story (shot, boot, the PASTOR fader, the mix). Red and blue area lights rake the chassis.
 * - Optional GLB: when glbUrl is given, a Meshopt-compressed model replaces the procedural desk. Draco and
 *   KTX2 are not wired (their decoder files are not in public/); see resources/js/home/hero.js for the path.
 *
 * The caller owns the loop, visibility and scroll; this module only builds and draws.
 */
import {
    ACESFilmicToneMapping,
    AmbientLight,
    Box3,
    Color,
    DirectionalLight,
    Group,
    HalfFloatType,
    LoadingManager,
    MathUtils,
    PerspectiveCamera,
    PMREMGenerator,
    PointLight,
    RectAreaLight,
    Scene,
    SRGBColorSpace,
    Vector2,
    Vector3,
    WebGLRenderer,
    WebGLRenderTarget,
} from 'three';
import { RectAreaLightUniformsLib } from 'three/addons/lights/RectAreaLightUniformsLib.js';
import { RoomEnvironment } from 'three/addons/environments/RoomEnvironment.js';
import { GLTFLoader } from 'three/addons/loaders/GLTFLoader.js';
import { MeshoptDecoder } from 'three/addons/libs/meshopt_decoder.module.js';
import { EffectComposer } from 'three/addons/postprocessing/EffectComposer.js';
import { RenderPass } from 'three/addons/postprocessing/RenderPass.js';
import { UnrealBloomPass } from 'three/addons/postprocessing/UnrealBloomPass.js';
import { BokehPass } from 'three/addons/postprocessing/BokehPass.js';
import { OutputPass } from 'three/addons/postprocessing/OutputPass.js';
import { SMAAPass } from 'three/addons/postprocessing/SMAAPass.js';
import { buildSurface } from './dlive-surface';

const VERTICAL_FOV = 35;
const DESK_WIDTH = { desktop: 8.2, mobile: 5.6 };
const MAX_TILT = MathUtils.degToRad(3.5);

/** Reads "R G B" tokens from the dark theme scope, so no colour is hardcoded here. */
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
        primary: read('--color-primary', new Color(0.22, 0.74, 0.97).convertSRGBToLinear()),
        background: read('--color-console', new Color(0.06, 0.09, 0.16).convertSRGBToLinear()),
        surface: read('--color-surface', new Color(0.007, 0.008, 0.012)),
        text: read('--color-text', new Color(0.88, 0.91, 0.94)),
        red: read('--color-hero-red', new Color(0.5, 0.03, 0.05)),
    };
    probe.remove();
    return tokens;
}

/** Seeded PRNG so the desk looks the same on every visit. */
function seeded(seed) {
    return () => {
        seed |= 0;
        seed = (seed + 0x6d2b79f5) | 0;
        let t = Math.imul(seed ^ (seed >>> 15), 1 | seed);
        t = (t + Math.imul(t ^ (t >>> 7), 61 | t)) ^ t;
        return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
    };
}

const smooth = (x) => {
    const t = MathUtils.clamp(x, 0, 1);
    return t * t * (3 - 2 * t);
};

/**
 * Camera shots for the hero story, in world units (desk centred on the origin, operator at +z).
 * 0 Silence (3/4 from the front right, wide), 1 Power on (pushed in), 2 The fader (close on PASTOR),
 * 3-4 The mix (glide along the input bay, then toward the centre), 5 The room (pulled back, full desk).
 * `fit` scales with the horizontal field of view, so the desk frames the same on any canvas shape.
 */
function buildShots(aspect, deskWidth, anchors) {
    const vertical = MathUtils.degToRad(VERTICAL_FOV / 2);
    const horizontal = 2 * Math.atan(Math.tan(vertical) * aspect);
    const fit = (deskWidth / 2 / Math.tan(horizontal / 2)) * 1.55;
    const { pastor, bank } = anchors;
    return [
        { pos: [fit * 0.5, fit * 0.42, fit * 0.76], look: [0, -0.2, 0] },
        { pos: [fit * 0.4, fit * 0.36, fit * 0.62], look: [0, -0.1, -0.1] },
        { pos: [pastor.x + 1.5, 1.9, pastor.z + 2.5], look: [pastor.x + 0.55, 0, pastor.z - 0.45] },
        { pos: [bank - 0.2, 2.7, 4.5], look: [bank + 0.9, 0, 0.1] },
        { pos: [bank + 3.6, 3.1, 5.0], look: [bank + 3.6, 0, 0] },
        { pos: [fit * 0.42, fit * 0.5, fit * 0.9], look: [0, -0.3, 0] },
    ];
}

export function createConsoleScene(canvas, { mobile = false, glbUrl = null, onProgress = () => {} } = {}) {
    const tokens = readTokens();
    const random = seeded(1977);

    // Renderer -----------------------------------------------------------------------------------------
    const renderer = new WebGLRenderer({ canvas, antialias: false, powerPreference: 'high-performance' });
    // DPR cap: 1.5 on desktop, 1.25 on phones (the canvas is large; more pixels cost more than they show).
    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, mobile ? 1.25 : 1.5));
    renderer.toneMapping = ACESFilmicToneMapping;
    renderer.toneMappingExposure = 1.0;
    renderer.outputColorSpace = SRGBColorSpace;

    const scene = new Scene();
    // Black, not the console colour: the stage is blended with mix-blend-mode: screen over the hero background,
    // so black shows the CSS streaks and grain exactly (no tone-mapped seam).
    scene.background = new Color(0, 0, 0);

    const pmrem = new PMREMGenerator(renderer);
    scene.environment = pmrem.fromScene(new RoomEnvironment(), 0.04).texture;
    scene.environmentIntensity = 0.18;
    pmrem.dispose();

    const camera = new PerspectiveCamera(VERTICAL_FOV, 1, 0.1, 80);

    // Desk --------------------------------------------------------------------------------------------------
    const rig = new Group(); // pointer tilt lives here
    const deck = new Group(); // the desk itself, sloped slightly toward the operator
    deck.rotation.x = 0.08;
    rig.add(deck);
    scene.add(rig);

    const surface = buildSurface({ tokens, mobile, random });
    const deskWidth = mobile ? DESK_WIDTH.mobile : DESK_WIDTH.desktop;
    const scale = deskWidth / surface.width;
    deck.scale.setScalar(scale);
    deck.add(surface.group);
    const anchors = {
        pastor: { x: surface.anchors.pastorX * scale, z: surface.anchors.pastorZ * scale },
        bank: surface.anchors.inputBankX * scale,
    };

    // Lights. The hero's red and blue streaks become real light: two area lights rake across the chassis from
    // either side (brand exception, hero only: brain/Decisions/hero-join-brand-exception.md). A low key light from
    // the front right leaves the far side of the desk in shadow.
    RectAreaLightUniformsLib.init();
    const ambient = new AmbientLight(0xffffff, 0.05);
    const key = new DirectionalLight(0xffffff, 0.5);
    key.position.set(5, 6, 7);
    const redLight = new RectAreaLight(tokens.red, 0, 7, 2.2);
    redLight.position.set(6.5, 3.2, -3.5);
    redLight.lookAt(0, 0, 0);
    const blueLight = new RectAreaLight(tokens.primary, 0, 6, 2);
    blueLight.position.set(-6.5, 2.6, 2.5);
    blueLight.lookAt(0, 0, 0.5);
    const meterGlow = new PointLight(tokens.primary, 0, 6, 1.6);
    meterGlow.position.set(0, 1.4, -1.6);
    rig.add(meterGlow);
    scene.add(ambient, key, redLight, blueLight);

    // Post ---------------------------------------------------------------------------------------------------
    // No MSAA on the target: multisampled half-float targets render blank on some GPUs/drivers.
    const target = new WebGLRenderTarget(1, 1, { type: HalfFloatType });
    const composer = new EffectComposer(renderer, target);
    composer.addPass(new RenderPass(scene, camera));
    const bokeh = mobile ? null : new BokehPass(scene, camera, { focus: 10, aperture: 0.00005, maxblur: 0.003 });
    if (bokeh) composer.addPass(bokeh);
    // Threshold: only LED and screen highlights above 1.0 (in HDR) bloom; the rest of the desk stays crisp.
    const bloom = new UnrealBloomPass(new Vector2(1, 1), mobile ? 0.25 : 0.38, 0.4, 1.0);
    composer.addPass(bloom);
    composer.addPass(new OutputPass());
    if (!mobile) composer.addPass(new SMAAPass());

    // State (driven from outside by GSAP) ----------------------------------------------------------------------
    const state = {
        power: 0, // 0..1 lights, screens and meters (the hero idles at a low standby level before chapter 1)
        boot: 0, // 0..1 motorised fader self-test sweep (chapter 1)
        intro: 0, // 0..1 camera dolly in from darkness on load
        shot: 0, // 0..5 camera position along the story (see buildShots)
        pastor: 0, // 0..1 the PASTOR fader move (chapter 2)
        mix: 0, // 0..1 the other channels joining (chapter 3)
        shift: mobile ? 0 : 0.17, // share of the canvas width the desk sits right of centre (text is on the left)
        pointerX: 0,
        pointerY: 0,
    };

    const look = new Vector3();
    const tmpPos = new Vector3();
    const tmpLook = new Vector3();
    const toPos = new Vector3();
    const toLook = new Vector3();
    let shots = null;
    let width = 1;
    let height = 1;

    function resize() {
        width = canvas.clientWidth || 1;
        height = canvas.clientHeight || 1;
        renderer.setSize(width, height, false);
        composer.setSize(width, height);
        bloom.resolution.set(width / 2, height / 2);
        camera.aspect = width / height;
        // The view offset is re-applied every frame (it follows state.shift); this keeps the projection current.
        camera.updateProjectionMatrix();
        shots = buildShots(width / height, deskWidth, anchors);
    }

    function updateCamera(time) {
        const s = MathUtils.clamp(state.shot, 0, shots.length - 1);
        const i = Math.min(Math.floor(s), shots.length - 2);
        const t = smooth(s - i);
        tmpPos.set(...shots[i].pos).lerp(toPos.set(...shots[i + 1].pos), t);
        tmpLook.set(...shots[i].look).lerp(toLook.set(...shots[i + 1].look), t);

        // Very slow drift, always on, so the idle desk never looks frozen.
        tmpPos.x += Math.sin(time * 0.07) * 0.18;
        tmpPos.y += Math.sin(time * 0.05 + 1.3) * 0.08;

        // Intro: the camera starts further out and dollies in while the desk comes up to standby.
        const dolly = 1 + (1 - smooth(state.intro)) * 0.4;
        camera.position.copy(tmpLook).addScaledVector(tmpPos.sub(tmpLook), dolly);
        look.copy(tmpLook);
        camera.lookAt(look);

        // Desk sits right of centre (and a little low), leaving the left of the hero for the copy.
        camera.setViewOffset(width, height, -width * state.shift, -height * 0.05 * (state.shift > 0 ? 1 : 0), width, height);

        // Pointer parallax: the desk tilts at most 3.5 degrees toward the cursor, with inertia.
        rig.rotation.y += (state.pointerX * MAX_TILT - rig.rotation.y) * 0.04;
        rig.rotation.x += (-state.pointerY * MAX_TILT * 0.6 - rig.rotation.x) * 0.04;
    }

    function updateLights() {
        const power = state.power;
        ambient.intensity = 0.02 + 0.04 * power;
        key.intensity = 0.08 + 0.3 * power;
        // The coloured lights are there even in "Silence" (the desk reads as a shape in the streaks).
        redLight.intensity = 3 + 2 * power;
        blueLight.intensity = 1 + 0.8 * power;
        meterGlow.intensity = 0.8 * power;
    }

    let last = 0;
    function render(timeSeconds) {
        const dt = last ? Math.min(0.05, timeSeconds - last) : 0.016;
        last = timeSeconds;
        updateCamera(timeSeconds);
        surface.update(timeSeconds, dt, state);
        updateLights();
        if (bokeh) bokeh.uniforms.focus.value = camera.position.distanceTo(look);
        composer.render();
    }

    resize();

    // Optional GLB: replaces the procedural desk when present. Meshopt only (no external decoder files).
    // Shaders compile off the first frame (compileAsync yields between programs), so the first visible frame is smooth.
    const ready = (glbUrl ? loadGlb(glbUrl) : Promise.resolve()).then(() => renderer.compileAsync?.(scene, camera));

    function loadGlb(url) {
        const manager = new LoadingManager();
        manager.onProgress = (_item, loaded, total) => onProgress(0.2 + 0.7 * (loaded / Math.max(1, total)));
        const loader = new GLTFLoader(manager);
        loader.setMeshoptDecoder(MeshoptDecoder);

        return loader.loadAsync(url).then((gltf) => {
            const model = gltf.scene;
            const box = new Box3().setFromObject(model);
            const size = box.getSize(new Vector3());
            const fitScale = deskWidth / Math.max(size.x, 0.001);
            model.scale.setScalar(fitScale);
            box.setFromObject(model);
            const center = box.getCenter(new Vector3());
            model.position.set(-center.x * fitScale, -box.min.y * fitScale, -center.z * fitScale);
            surface.group.visible = false;
            deck.add(model);
            // A GLB has no PASTOR fader node: the close-up aims at the front left of the model instead.
            anchors.pastor = { x: -deskWidth * 0.4, z: size.z * fitScale * 0.25 };
            anchors.bank = -deskWidth * 0.25;
            resize();
        });
    }

    return {
        state,
        ready,
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
