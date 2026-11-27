/**
 * Hero 3D scene: a digital mixing surface on a dark stage (see dlive-surface.js).
 *
 * - PBR materials (brushed metal, matte plastic, rubber caps, clearcoat glass) lit by RoomEnvironment,
 *   ACES tone mapping and sRGB output. A soft contact shadow sits under the desk (no hard shadow maps).
 * - Post: bloom only catches values above the threshold, which the LEDs and bright screens exceed.
 *   Light depth of field (BokehPass) and SMAA run on desktop only.
 * - The screens and meters "play" a simulated mix; the boot sweep and the load-in are driven from outside.
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
    Scene,
    SpotLight,
    SRGBColorSpace,
    Vector2,
    Vector3,
    WebGLRenderer,
    WebGLRenderTarget,
} from 'three';
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
 * Camera keyframes for the scroll story, fitted to the canvas: 3/4 view, then top-down over the faders,
 * then a close-up on the first touchscreen. Distances scale with the horizontal field of view, so the desk
 * fits on a phone and on a wide screen alike.
 */
function keyframes(aspect, deckWidth, screenX, screenY, screenZ) {
    const vertical = MathUtils.degToRad(VERTICAL_FOV / 2);
    const horizontal = 2 * Math.atan(Math.tan(vertical) * aspect);
    // 1.4 leaves room for the near edge of the desk, which is closer to the camera than its centre.
    const fit = ((deckWidth / 2) * 1.4) / Math.tan(horizontal / 2);
    return {
        start: { pos: [0, fit * 0.36 + 1.6, fit * 0.8], look: [0, 0.2, -0.3] },
        top: { pos: [0, fit * 0.98, fit * 0.12], look: [0, 0, 0.1] },
        end: { pos: [screenX * 0.6, 1.9, screenZ + 3.0], look: [screenX, screenY, screenZ] },
    };
}

export function createConsoleScene(canvas, { mobile = false, glbUrl = null, onProgress = () => {} } = {}) {
    const tokens = readTokens();
    const random = seeded(1977);

    // Renderer -----------------------------------------------------------------------------------------
    const renderer = new WebGLRenderer({ canvas, antialias: false, powerPreference: 'high-performance' });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, mobile ? 1.5 : 1.75));
    renderer.toneMapping = ACESFilmicToneMapping;
    renderer.toneMappingExposure = 1.0;
    renderer.outputColorSpace = SRGBColorSpace;

    const scene = new Scene();
    // Black, not the console colour: the stage is blended with mix-blend-mode: screen over the hero's bg-console,
    // so black shows as the page colour exactly (no tone-mapped seam against the copy column).
    scene.background = new Color(0, 0, 0);

    const pmrem = new PMREMGenerator(renderer);
    scene.environment = pmrem.fromScene(new RoomEnvironment(), 0.04).texture;
    scene.environmentIntensity = 0.45;
    pmrem.dispose();

    const camera = new PerspectiveCamera(VERTICAL_FOV, 1, 0.1, 80);

    // Desk --------------------------------------------------------------------------------------------------
    const rig = new Group();
    const deck = new Group(); // the desk itself, sloped slightly toward the operator
    deck.rotation.x = 0.08;
    rig.add(deck);
    scene.add(rig);

    const surface = buildSurface({ tokens, mobile, random });
    const deskWidth = mobile ? DESK_WIDTH.mobile : DESK_WIDTH.desktop;
    const scale = deskWidth / surface.width;
    deck.scale.setScalar(scale);
    deck.add(surface.group);

    // Screen 0 in world space (for the close-up keyframe): the desk group is scaled by `scale`.
    const screen0 = surface.screens[0];
    const screenPos = new Vector3();
    screen0.glass.getWorldPosition(screenPos);

    // Lights: a soft key from above the front, a cool rim from behind, and a little glow from the meters.
    const ambient = new AmbientLight(0xffffff, 0.12);
    const key = new DirectionalLight(0xffffff, 0.9);
    key.position.set(2, 8, 6);
    const rim = new SpotLight(tokens.primary, 0, 40, 0.6, 0.7, 1.4);
    rim.position.set(-4, 9, -6);
    rim.target = rig;
    const meterGlow = new PointLight(tokens.primary, 0, 6, 1.6);
    meterGlow.position.set(0, 1.4, -1.6);
    rig.add(meterGlow);
    scene.add(ambient, key, rim);

    // Post ---------------------------------------------------------------------------------------------------
    // No MSAA on the target: multisampled half-float targets render blank on some GPUs/drivers.
    const target = new WebGLRenderTarget(1, 1, { type: HalfFloatType });
    const composer = new EffectComposer(renderer, target);
    composer.addPass(new RenderPass(scene, camera));
    const bokeh = mobile ? null : new BokehPass(scene, camera, { focus: 10, aperture: 0.00012, maxblur: 0.004 });
    if (bokeh) composer.addPass(bokeh);
    // Threshold: only LED and screen highlights above 1.0 (in HDR) bloom; the rest of the desk stays crisp.
    const bloom = new UnrealBloomPass(new Vector2(1, 1), mobile ? 0.35 : 0.55, 0.45, 1.0);
    composer.addPass(bloom);
    composer.addPass(new OutputPass());
    if (!mobile) composer.addPass(new SMAAPass());

    // State (driven from outside by GSAP) ----------------------------------------------------------------------
    const state = {
        power: 0, // 0..1 lights and meters coming up
        boot: 0, // 0..1 motorised fader self-test sweep
        intro: 0, // 0..1 camera dolly in from darkness
        progress: 0, // 0..1 scroll through the hero
        pointerX: 0,
        pointerY: 0,
    };

    const cam = { x: 0, y: 0 };
    const look = new Vector3();
    const tmpPos = new Vector3();
    const tmpLook = new Vector3();
    let frames = null;
    let aspectNow = 1;

    function setKeyframes(a, b, c) {
        frames = { start: a, top: b, end: c };
    }

    function resize() {
        const w = canvas.clientWidth || 1;
        const h = canvas.clientHeight || 1;
        aspectNow = w / h;
        renderer.setSize(w, h, false);
        composer.setSize(w, h);
        bloom.resolution.set(w / 2, h / 2);
        camera.aspect = aspectNow;
        camera.updateProjectionMatrix();
        const k = keyframes(aspectNow, deskWidth, screenPos.x, screenPos.y, screenPos.z);
        setKeyframes(k.start, k.top, k.end);
    }

    function updateCamera() {
        const p = state.progress;
        const seg = p < 0.5 ? smooth(p / 0.5) : smooth((p - 0.5) / 0.5);
        const from = p < 0.5 ? frames.start : frames.top;
        const to = p < 0.5 ? frames.top : frames.end;

        tmpPos.set(...from.pos).lerp(new Vector3(...to.pos), seg);
        tmpLook.set(...from.look).lerp(new Vector3(...to.look), seg);

        cam.x += (state.pointerX - cam.x) * 0.045;
        cam.y += (state.pointerY - cam.y) * 0.045;
        tmpPos.x += cam.x * 0.5;
        tmpPos.y += cam.y * 0.3;

        // Intro: the camera starts further out and dollies in while the desk powers up.
        const dolly = 1 + (1 - smooth(state.intro)) * 0.45;
        camera.position.copy(tmpLook).addScaledVector(tmpPos.sub(tmpLook), dolly);
        look.copy(tmpLook);
        camera.lookAt(look);
    }

    function updateLights() {
        const power = state.power;
        ambient.intensity = 0.12 * power;
        key.intensity = 0.9 * power;
        rim.intensity = 26 * power;
        meterGlow.intensity = 1.2 * power;
    }

    let last = 0;
    function render(timeSeconds) {
        const dt = last ? Math.min(0.05, timeSeconds - last) : 0.016;
        last = timeSeconds;
        updateCamera();
        surface.update(timeSeconds, dt, state);
        updateLights();
        if (bokeh) bokeh.uniforms.focus.value = camera.position.distanceTo(look);
        composer.render();
    }

    resize();

    // Optional GLB: replaces the procedural desk when present. Meshopt only (no external decoder files).
    const ready = glbUrl ? loadGlb(glbUrl) : Promise.resolve();

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
            // A GLB has no screen node to aim at, so the close-up frames the upper middle of the model.
            screenPos.set(0, size.y * fitScale * 0.6, 0);
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
