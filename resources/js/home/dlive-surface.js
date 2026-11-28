/**
 * A large-format digital mixing surface, modelled on the layout of a dLive-class desk:
 * - two banks of 12 motorised faders (24 strips), each strip with Mix / PAFL / Mute keys, a scribble-strip
 *   LCD, a 10-segment meter and a rotary encoder with an LED ring;
 * - a 12" touchscreen above each bank, leaning back into a low rear hood, with its encoder row in front;
 * - a centre master section with soft keys and a small rotary cluster;
 * - dark anodised chassis with brushed-metal side cheeks and a matte armrest.
 * No maker's name, logo or wordmark is drawn (trademark). Two banks and two screens at every quality; the
 * quality ('desktop' | 'medium' | 'light', see console-scene.js) only changes the screen redraw rate here.
 *
 * Built from primitives (instanced where repeated) so it stays light. The touchscreens are canvas textures
 * redrawn 20 times a second on desktop, 15 on tablets, 12 on phones: the PASTOR channel (waveform) and a mixer overview, driven by a fake signal.
 *
 * Units: 1 = 10 cm. Top of the work surface is y = 0; the operator sits at +z.
 */
import {
    BoxGeometry,
    CanvasTexture,
    Color,
    CylinderGeometry,
    Group,
    InstancedMesh,
    MathUtils,
    Mesh,
    MeshBasicMaterial,
    MeshPhysicalMaterial,
    Object3D,
    PlaneGeometry,
    SRGBColorSpace,
} from 'three';
import { RoundedBoxGeometry } from 'three/addons/geometries/RoundedBoxGeometry.js';

const PITCH = 0.32; // fader strip width
const BANK = 12; // faders per bank
const BANK_W = BANK * PITCH; // 3.84
const CENTER = 1.5; // centre master section between the banks
const FADER_NEAR = 1.85; // fader at the bottom of its travel
const TRAVEL = 1.0;
const RING = 15; // LED segments per encoder ring
const METER = 10; // segments per strip meter
const BPM = 72;
const SANS = '"Schibsted Grotesk", system-ui, sans-serif';
const MONO = '"JetBrains Mono", ui-monospace, monospace';

// Scribble strips (owner's input list, see brain/Features/hero-console-story.md). Bank 1 holds the inputs; on
// desktop bank 2 holds the outputs. Groups pick the scribble-strip colour.
const CHANNELS = [
    ['PASTOR', 'speech'], ['WL 1', 'vocal'], ['WL 2', 'vocal'], ['CHOIR L', 'choir'], ['CHOIR R', 'choir'], ['KEYS', 'band'],
    ['AG', 'band'], ['BASS', 'band'], ['KICK', 'drums'], ['SNARE', 'drums'], ['OH L', 'drums'], ['OH R', 'drums'],
    ['MAIN L', 'play'], ['MAIN R', 'play'], ['SUB', 'play'], ['FILL', 'play'], ['MON 1', 'play'], ['MON 2', 'play'],
    ['MON 3', 'play'], ['MON 4', 'play'], ['IEM 1', 'play'], ['IEM 2', 'play'], ['LIVE', 'play'], ['REC', 'play'],
];

// Speech-like envelope for the PASTOR channel: syllables inside words inside phrases, with short pauses.
const speech = (t) => {
    const syllable = Math.pow(Math.max(0, Math.sin(t * 9.1) * 0.6 + Math.sin(t * 5.3 + 1.2) * 0.4), 1.4);
    const word = Math.sin(t * 2.1) > -0.45 ? 1 : 0.12;
    const phrase = Math.sin(t * 0.55 + 0.8) > -0.7 ? 1 : 0.08;
    return MathUtils.clamp(syllable * word * phrase * 1.25, 0, 1);
};

const css = (color, alpha = 1) => {
    const c = color.clone().convertLinearToSRGB();
    return `rgba(${Math.round(c.r * 255)},${Math.round(c.g * 255)},${Math.round(c.b * 255)},${alpha})`;
};

export function buildSurface({ tokens, quality = 'desktop', random }) {
    // Both banks, both screens and the master section at every quality (the poster shows them all). The quality
    // only changes the screen redraw rate here; pixel ratio, bloom and post live in console-scene.js.
    const banks = 2;
    const strips = banks * BANK;
    const centre = CENTER;
    const totalW = banks * BANK_W + centre;
    const width = totalW + 0.5; // with cheeks

    // Layout left to right, like a dLive S5000: fader bank 1, fader bank 2, then the master section on the right.
    // The surface is centred on x = 0. Phones show a single bank, centred (no master section).
    const left = -totalW / 2;
    const bankCenter = (b) => left + BANK_W / 2 + b * BANK_W;
    const masterX = left + banks * BANK_W + centre / 2;
    const stripX = (s) => bankCenter(Math.floor(s / BANK)) - BANK_W / 2 + PITCH / 2 + (s % BANK) * PITCH;

    const group = new Group();
    const dummy = new Object3D();
    const unit = new Color();
    const place = (mesh, index, x, y, z, ry = 0, sx = 1) => {
        dummy.position.set(x, y, z);
        dummy.rotation.set(0, ry, 0);
        dummy.scale.set(sx, 1, 1);
        dummy.updateMatrix();
        mesh.setMatrixAt(index, dummy.matrix);
    };

    // Materials ------------------------------------------------------------------------------------------
    const black = tokens.background.clone();
    const chassisMat = new MeshPhysicalMaterial({
        color: tokens.surface.clone().multiplyScalar(1.4),
        metalness: 0.35,
        roughness: 0.5,
        clearcoat: 0.25,
        clearcoatRoughness: 0.4,
    });
    const brushed = new MeshPhysicalMaterial({
        color: tokens.text.clone().multiplyScalar(0.28),
        metalness: 0.7,
        roughness: 0.5,
        anisotropy: 0.6,
    });
    // Dark anodised side cheeks and rails: near-black with only a soft edge sheen, so bloom never catches them.
    const cheekMat = new MeshPhysicalMaterial({
        color: tokens.surface.clone().multiplyScalar(2.2),
        metalness: 0.3,
        roughness: 0.55,
        clearcoat: 0.3,
        clearcoatRoughness: 0.35,
        envMapIntensity: 0.15,
    });
    const plastic = new MeshPhysicalMaterial({ color: black.clone().multiplyScalar(2), metalness: 0, roughness: 0.8 });
    // Fader caps: matte dark rubber (not emissive). Only LEDs, scribble strips and screens emit light.
    const rubber = new MeshPhysicalMaterial({ color: tokens.surface.clone().multiplyScalar(7), metalness: 0, roughness: 0.92 });
    // Thin centre line on each cap: a matte light line, not a glowing one.
    const capLineMat = new MeshPhysicalMaterial({ color: tokens.text.clone().multiplyScalar(0.7), metalness: 0, roughness: 0.6 });
    const slotMat = new MeshPhysicalMaterial({ color: black, metalness: 0, roughness: 1 });
    const armMat = new MeshPhysicalMaterial({ color: black.clone().multiplyScalar(1.6), metalness: 0, roughness: 0.9, sheen: 0.4, sheenRoughness: 0.8 });
    const glow = () => new MeshBasicMaterial({ color: new Color(0xffffff).multiplyScalar(2.5), toneMapped: false });

    // Chassis --------------------------------------------------------------------------------------------
    const chassis = new Mesh(new RoundedBoxGeometry(totalW + 0.4, 0.32, 4.4, 4, 0.06), chassisMat);
    chassis.position.set(0, -0.16, 0.3);
    group.add(chassis);

    // Contact shadow: a soft radial falloff under the desk (no hard shadow map).
    const shadow = new Mesh(
        new PlaneGeometry(width * 1.5, 6.2),
        new MeshBasicMaterial({ map: contactShadowTexture(), transparent: true, depthWrite: false, opacity: 0.9 }),
    );
    shadow.rotation.x = -Math.PI / 2;
    shadow.position.set(0, -0.335, 0.3);
    group.add(shadow);

    // Brushed side cheeks, plus a plate on top of the work surface.
    [-1, 1].forEach((dir) => {
        const cheek = new Mesh(new RoundedBoxGeometry(0.13, 0.62, 4.5, 3, 0.05), cheekMat);
        cheek.position.set(dir * (width / 2 - 0.02), -0.02, 0.3);
        group.add(cheek);
    });
    const plate = new Mesh(new BoxGeometry(totalW + 0.2, 0.014, 3.05), plastic);
    plate.position.set(0, 0.007, 0.72);
    group.add(plate);

    // Matte armrest along the front edge.
    const armrest = new Mesh(new RoundedBoxGeometry(totalW + 0.4, 0.16, 0.55, 5, 0.075), armMat);
    armrest.position.set(0, 0.06, 2.3);
    group.add(armrest);

    // Low rear hood: the touchscreens lean back into it.
    const hood = new Mesh(new RoundedBoxGeometry(totalW + 0.4, 0.5, 1.45, 4, 0.06), chassisMat);
    hood.position.set(0, 0.2, -1.38);
    group.add(hood);

    // Bank separators (thin brushed bars between the banks and the master section).
    {
        // Between the two banks, and between bank 2 and the master section.
        [left + BANK_W, left + 2 * BANK_W].forEach((x) => {
            const bar = new Mesh(new BoxGeometry(0.05, 0.02, 2.9), cheekMat);
            bar.position.set(x, 0.016, 0.72);
            group.add(bar);
        });
    }

    // Fader strips -----------------------------------------------------------------------------------------
    const slots = new InstancedMesh(new BoxGeometry(0.026, 0.012, TRAVEL + 0.2), slotMat, strips);
    const caps = new InstancedMesh(new RoundedBoxGeometry(0.2, 0.15, 0.12, 2, 0.03), rubber, strips);
    const capLines = new InstancedMesh(new BoxGeometry(0.2, 0.004, 0.012), capLineMat, strips);
    for (let s = 0; s < strips; s++) place(slots, s, stripX(s), 0.012, FADER_NEAR - TRAVEL / 2);

    // Mix / PAFL / Mute keys (RGB lit, always faintly glowing like the real thing).
    const keyRows = [
        { z: 0.66, color: tokens.primary }, // Mix
        { z: 0.53, color: tokens.text.clone().lerp(tokens.primary, 0.4) }, // PAFL
        { z: 0.4, color: tokens.primary.clone().lerp(tokens.text, 0.5) }, // Mute
    ];
    const keys = new InstancedMesh(new RoundedBoxGeometry(0.22, 0.04, 0.095, 2, 0.014), glow(), strips * keyRows.length);
    for (let s = 0; s < strips; s++) keyRows.forEach((row, r) => place(keys, s * 3 + r, stripX(s), 0.024, row.z));

    // Strip meters: horizontal segments just above the scribble strip.
    const meters = new InstancedMesh(new BoxGeometry(0.02, 0.006, 0.028), glow(), strips * METER);
    for (let s = 0; s < strips; s++) for (let m = 0; m < METER; m++) place(meters, s * METER + m, stripX(s) - 0.12 + m * 0.0267, 0.012, 0.035);

    // Scribble-strip LCDs: one canvas per bank, 12 coloured name tiles.
    const groupColor = {
        speech: tokens.text.clone(),
        vocal: tokens.primary.clone(),
        choir: tokens.primary.clone().lerp(tokens.text, 0.45),
        band: tokens.primary.clone().lerp(tokens.text, 0.2),
        drums: tokens.primary.clone().lerp(tokens.text, 0.65),
        play: tokens.text.clone().lerp(tokens.primary, 0.2).multiplyScalar(0.6),
    };
    const lcdTextures = [];
    for (let b = 0; b < banks; b++) {
        const canvas = document.createElement('canvas');
        canvas.width = 1536;
        canvas.height = 96;
        const ctx = canvas.getContext('2d');
        ctx.fillStyle = css(black);
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        for (let i = 0; i < BANK; i++) {
            const [name, kind] = CHANNELS[(b * BANK + i) % CHANNELS.length];
            const x = i * 128;
            ctx.fillStyle = css(groupColor[kind], 0.95);
            ctx.fillRect(x + 6, 6, 116, 84);
            ctx.fillStyle = kind === 'speech' || kind === 'choir' ? css(black) : 'rgba(255,255,255,0.95)';
            ctx.textAlign = 'center';
            ctx.font = `700 26px ${SANS}`;
            ctx.fillText(name, x + 64, 46);
            ctx.font = `600 18px ${MONO}`;
            ctx.fillText(String(b * BANK + i + 1).padStart(2, '0'), x + 64, 76);
        }
        const texture = new CanvasTexture(canvas);
        texture.colorSpace = SRGBColorSpace;
        texture.anisotropy = 4;
        lcdTextures.push(texture);
        const lcd = new Mesh(new PlaneGeometry(BANK_W - 0.04, 0.19), new MeshBasicMaterial({ map: texture, toneMapped: false }));
        lcd.material.color.setScalar(1.15);
        lcd.rotation.x = -Math.PI / 2;
        lcd.position.set(bankCenter(b), 0.016, 0.19);
        group.add(lcd);
    }

    // Rotary encoders with LED rings: one per strip (row beneath each screen), three in the master cluster.
    const encoderSpots = [];
    for (let s = 0; s < strips; s++) encoderSpots.push({ x: stripX(s), z: -0.22, strip: s });
    const clusterX = [-0.3, 0, 0.3];
    clusterX.forEach((x) => encoderSpots.push({ x: masterX + x, z: -0.2, strip: -1 }));
    const knobBodies = new InstancedMesh(new CylinderGeometry(0.068, 0.074, 0.09, 32), plastic, encoderSpots.length);
    const knobCaps = new InstancedMesh(new CylinderGeometry(0.048, 0.048, 0.094, 32), brushed, encoderSpots.length);
    const knobPointers = new InstancedMesh(new BoxGeometry(0.01, 0.006, 0.04), glow(), encoderSpots.length);
    const rings = new InstancedMesh(new BoxGeometry(0.016, 0.005, 0.03), glow(), encoderSpots.length * RING);
    encoderSpots.forEach((spot, e) => {
        place(knobBodies, e, spot.x, 0.045, spot.z);
        place(knobCaps, e, spot.x, 0.047, spot.z);
        knobPointers.setColorAt(e, unit.setRGB(1.5, 1.5, 1.5));
        spot.value = 0.25 + random() * 0.35;
        spot.phase = random() * 6.28;
        for (let k = 0; k < RING; k++) {
            const a = MathUtils.degToRad(-135 + (270 * k) / (RING - 1));
            place(rings, e * RING + k, spot.x + Math.sin(a) * 0.11, 0.012, spot.z - Math.cos(a) * 0.11, -a);
        }
    });

    // Master section: a grid of soft keys (lit at random, colour per function).
    let softkeys = null;
    {
        softkeys = new InstancedMesh(new RoundedBoxGeometry(0.24, 0.04, 0.14, 2, 0.015), glow(), 8);
        let k = 0;
        for (let r = 0; r < 2; r++) for (let c = 0; c < 4; c++) place(softkeys, k++, masterX - 0.45 + c * 0.3, 0.024, 0.45 + r * 0.22);
        softkeys.userData.lit = Array.from({ length: 8 }, () => random() < 0.4);
        softkeys.userData.color = Array.from({ length: 8 }, (_, i) => (i % 3 === 0 ? tokens.primary : i % 3 === 1 ? tokens.text : tokens.primary.clone().lerp(tokens.text, 0.5)));
    }

    group.add(slots, caps, capLines, keys, meters, knobBodies, knobCaps, knobPointers, rings);
    if (softkeys) group.add(softkeys);

    // Touchscreens: side by side in the rear section, leaning back into the hood. ----------------------------
    const screens = [];
    const tilt = -0.58;
    for (let b = 0; b < banks; b++) {
        const canvas = document.createElement('canvas');
        canvas.width = 1024;
        canvas.height = 640;
        const texture = new CanvasTexture(canvas);
        texture.colorSpace = SRGBColorSpace;
        texture.anisotropy = 4;

        const holder = new Group();
        holder.position.set(bankCenter(b), 1.27, -1.22);
        holder.rotation.x = tilt;
        const bezel = new Mesh(new RoundedBoxGeometry(BANK_W - 0.25, 2.2, 0.12, 4, 0.05), chassisMat);
        bezel.position.z = -0.065;
        const glass = new Mesh(
            new PlaneGeometry(BANK_W - 0.42, (BANK_W - 0.42) * (640 / 1024)),
            new MeshPhysicalMaterial({
                color: black,
                roughness: 0.2,
                metalness: 0,
                clearcoat: 1,
                clearcoatRoughness: 0.05,
                emissive: 0xffffff,
                emissiveMap: texture,
                emissiveIntensity: 0,
            }),
        );
        glass.position.y = 0.06;
        holder.add(bezel, glass);
        group.add(holder);
        screens.push({ canvas, ctx: canvas.getContext('2d'), texture, glass, view: b === 0 ? 'pastor' : 'overview', bank: b });
    }

    // "Music" per strip: the meters, rings and screens all read from this. --------------------------------
    const voices = Array.from({ length: strips }, (_, s) => ({
        name: CHANNELS[s % CHANNELS.length][0],
        kind: CHANNELS[s % CHANNELS.length][1],
        base: 0.15 + random() * 0.2,
        amp: 0.25 + random() * 0.35,
        f1: 0.6 + random() * 2.2,
        f2: 0.15 + random() * 0.6,
        p1: random() * 6.28,
        p2: random() * 6.28,
        kick: CHANNELS[s % CHANNELS.length][1] === 'drums' ? 0.4 : random() * 0.12,
        level: 0,
        peak: 0,
        hold: 0,
        fader: 0,
        alive: 0,
        input: 0,
        wave: 0,
        rest: 0.1 + random() * 0.18,
        up: s === 0 ? 0.78 : 0.48 + random() * 0.32, // "faders at different levels" once the mix is up
        muted: s >= BANK && random() < 0.12,
        mix: s === 0,
        pafl: s >= BANK && random() < 0.08,
    }));

    // Screen drawing --------------------------------------------------------------------------------------
    const ui = {
        bg: css(black),
        panel: css(tokens.surface.clone().multiplyScalar(2.2)),
        line: css(tokens.text, 0.08),
        dim: css(tokens.text, 0.45),
        text: css(tokens.text, 0.92),
        primary: css(tokens.primary),
        primarySoft: css(tokens.primary, 0.22),
        hot: css(tokens.primary.clone().lerp(tokens.text, 0.6)),
    };
    const wave = new Float32Array(120); // PASTOR waveform history, one column per screen redraw
    let waveHead = 0;

    function header(ctx, title, tabs, active) {
        ctx.fillStyle = ui.bg;
        ctx.fillRect(0, 0, 1024, 640);
        ctx.fillStyle = ui.panel;
        ctx.fillRect(0, 0, 1024, 58);
        ctx.fillStyle = ui.primary;
        ctx.fillRect(16, 12, 10, 34);
        ctx.font = `700 24px ${SANS}`;
        ctx.textAlign = 'left';
        ctx.fillStyle = ui.text;
        ctx.fillText(title, 38, 38);
        ctx.font = `600 16px ${MONO}`;
        tabs.forEach((tab, i) => {
            const x = 470 + i * 108;
            ctx.fillStyle = i === active ? ui.primary : ui.line;
            ctx.beginPath();
            ctx.roundRect(x, 12, 98, 34, 8);
            ctx.fill();
            ctx.fillStyle = i === active ? ui.bg : ui.dim;
            ctx.textAlign = 'center';
            ctx.fillText(tab, x + 49, 35);
        });
    }

    function vMeter(ctx, x, y, w, h, value, segments = 24) {
        const gap = 2;
        const segH = (h - gap * (segments - 1)) / segments;
        const lit = Math.round(MathUtils.clamp(value, 0, 1) * segments);
        for (let i = 0; i < segments; i++) {
            const top = y + h - (i + 1) * (segH + gap) + gap;
            ctx.globalAlpha = i < lit ? 1 : 0.12;
            ctx.fillStyle = i >= segments - 2 ? ui.hot : i >= segments - 6 ? ui.text : ui.primary;
            ctx.fillRect(x, top, w, segH);
        }
        ctx.globalAlpha = 1;
    }

    // Screen 1: the PASTOR channel. A scrolling waveform that grows from flat to clear speech as the fader comes up.
    function drawPastor(screen, state) {
        const { ctx } = screen;
        const v = voices[0];
        header(ctx, '01  PASTOR', ['Input', 'Gate', 'PEQ', 'Comp'], 0);

        wave[waveHead] = v.wave;
        waveHead = (waveHead + 1) % wave.length;

        const gx = 24, gy = 80, gw = 760, gh = 380;
        const mid = gy + gh / 2;
        ctx.fillStyle = ui.panel;
        ctx.fillRect(gx, gy, gw, gh);
        ctx.strokeStyle = ui.line;
        ctx.lineWidth = 1;
        [-0.45, 0, 0.45].forEach((f) => {
            ctx.beginPath();
            ctx.moveTo(gx, mid + f * gh);
            ctx.lineTo(gx + gw, mid + f * gh);
            ctx.stroke();
        });

        // Mirrored bars, newest on the right. A hairline stays at the centre even when the channel is silent.
        const bw = gw / wave.length;
        ctx.fillStyle = ui.primary;
        for (let i = 0; i < wave.length; i++) {
            const a = wave[(waveHead + i) % wave.length];
            const h = Math.max(1.5, a * gh * 0.44);
            ctx.globalAlpha = 0.35 + 0.65 * (i / wave.length);
            ctx.fillRect(gx + i * bw + 1, mid - h, Math.max(1, bw - 2), h * 2);
        }
        ctx.globalAlpha = 1;

        ctx.font = `600 15px ${MONO}`;
        ctx.textAlign = 'left';
        ctx.fillStyle = ui.dim;
        ctx.fillText('SPEECH  HPF 100Hz  GATE -48dB', gx + 14, gy + 26);
        const db = v.fader < 0.02 ? '-inf' : (-60 + v.fader * 70).toFixed(1);
        ctx.textAlign = 'right';
        ctx.fillStyle = (state.pastor ?? 0) > 0.05 ? ui.primary : ui.dim;
        ctx.fillText(`FADER ${db} dB`, gx + gw - 14, gy + 26);

        // Right: input / output meters and gain reduction.
        ctx.fillStyle = ui.panel;
        ctx.fillRect(804, 80, 196, 380);
        ctx.font = `600 14px ${MONO}`;
        ctx.textAlign = 'center';
        ctx.fillStyle = ui.dim;
        ['IN', 'OUT', 'GR'].forEach((label, i) => ctx.fillText(label, 846 + i * 56, 448));
        vMeter(ctx, 832, 96, 28, 330, v.input);
        vMeter(ctx, 888, 96, 28, 330, v.level);
        const gr = Math.max(0, v.level - 0.55) * 1.6;
        ctx.fillStyle = ui.hot;
        ctx.fillRect(944, 96, 28, 330 * gr);

        // Bottom: the bank at a glance.
        ctx.fillStyle = ui.panel;
        ctx.fillRect(24, 500, 976, 120);
        for (let i = 0; i < BANK; i++) {
            const voice = voices[i];
            const x = 34 + i * 80.5;
            ctx.fillStyle = css(groupColor[voice.kind], 0.9);
            ctx.fillRect(x, 510, 70, 24);
            ctx.fillStyle = voice.kind === 'speech' || voice.kind === 'choir' ? ui.bg : ui.text;
            ctx.font = `700 12px ${SANS}`;
            ctx.textAlign = 'center';
            ctx.fillText(voice.name, x + 35, 527);
            vMeter(ctx, x + 6, 542, 14, 70, voice.level, 12);
            ctx.fillStyle = ui.line;
            ctx.fillRect(x + 44, 542, 4, 70);
            ctx.fillStyle = ui.text;
            ctx.fillRect(x + 34, 542 + 62 - voice.fader * 62, 24, 8);
        }
    }

    // Screen 2: the input bank as a mixer overview (the channels coming alive one by one in "The mix").
    // Each screen shows its own bay: screen 1 the first 12 channels (PASTOR highlighted), screen 2 the next 12.
    function drawOverview(screen) {
        const { ctx } = screen;
        const first = screen.bank * BANK;
        header(ctx, 'Scene 04  Worship', ['Mixer', 'Scenes', 'FX', 'Setup'], 0);
        const colW = 976 / BANK;
        for (let i = 0; i < BANK; i++) {
            const voice = voices[first + i];
            const x = 24 + i * colW;
            ctx.fillStyle = ui.panel;
            ctx.fillRect(x + 3, 72, colW - 6, 548);
            ctx.fillStyle = css(groupColor[voice.kind], 0.95);
            ctx.fillRect(x + 3, 72, colW - 6, 40);
            ctx.fillStyle = voice.kind === 'speech' || voice.kind === 'choir' ? ui.bg : ui.text;
            ctx.font = `700 14px ${SANS}`;
            ctx.textAlign = 'center';
            ctx.fillText(voice.name, x + colW / 2, 98);
            // Mini PEQ thumbnail.
            ctx.strokeStyle = ui.primary;
            ctx.lineWidth = 2;
            ctx.beginPath();
            for (let p = 0; p <= 60; p += 4) {
                const y = 160 - Math.sin((p / 60) * Math.PI * 2 + i) * 10 * (voice.amp + 0.2);
                p === 0 ? ctx.moveTo(x + 10 + p, y) : ctx.lineTo(x + 10 + p, y);
            }
            ctx.stroke();
            vMeter(ctx, x + 12, 210, 18, 360, voice.level, 22);
            ctx.fillStyle = ui.line;
            ctx.fillRect(x + colW - 30, 210, 5, 360);
            ctx.fillStyle = ui.text;
            ctx.fillRect(x + colW - 44, 210 + 348 - voice.fader * 348, 32, 12);
            ctx.fillStyle = voice.muted ? ui.hot : ui.line;
            ctx.fillRect(x + 8, 584, colW - 16, 26);
            ctx.fillStyle = voice.muted ? ui.text : ui.dim;
            ctx.font = `700 12px ${MONO}`;
            ctx.textAlign = 'center';
            ctx.fillText('MUTE', x + colW / 2, 602);
            if (first + i === 0) {
                ctx.strokeStyle = ui.primary;
                ctx.lineWidth = 3;
                ctx.strokeRect(x + 3, 72, colW - 6, 548);
            }
        }
    }

    // Per-frame update -------------------------------------------------------------------------------------
    let screenClock = Infinity; // the first update draws both screens at once (no blank first frame)
    const screenInterval = quality === 'light' ? 1 / 12 : quality === 'medium' ? 1 / 15 : 1 / 20;

    function update(time, dt, state) {
        const beat = (time * BPM) / 60;
        const kick = Math.pow(1 - (beat % 1), 6);
        const blink = Math.floor(beat * 2) % 2 === 0;

        const pastor = state.pastor ?? 0;
        const mixAmount = state.mix ?? 0;
        for (let s = 0; s < strips; s++) {
            const v = voices[s];
            const local = s % BANK;
            const bank = Math.floor(s / BANK);
            // LEDs light in sequence: each strip comes up a moment after the one before it.
            // LEDs are even across both bays (one power level). PASTOR, the selected channel, runs a touch brighter.
            const pw = MathUtils.clamp(state.power, 0, 1);
            const lift = s === 0 ? 1.15 : 1;
            const sweep = Math.sin(MathUtils.clamp((state.boot * 1.6 - (bank * 0.2 + local * 0.04)) / 0.55, 0, 1) * Math.PI);
            // How "live" this channel is: PASTOR follows its own fader move; the others join one by one in the mix.
            const alive = s === 0
                ? pastor
                : bank === 0
                    ? MathUtils.smoothstep(mixAmount, ((local - 1) / 11) * 0.75, ((local - 1) / 11) * 0.75 + 0.25)
                    : MathUtils.smoothstep(mixAmount, 0.5, 1);
            v.alive = alive;
            // Idle: every fader drifts a few millimetres (full travel is 10 cm), like a motorised desk settling.
            let fader = MathUtils.lerp(v.rest, v.up, alive) + Math.sin(time * 0.35 + v.p2) * 0.022;
            fader = MathUtils.lerp(fader, 1, sweep);
            v.fader = MathUtils.clamp(fader, 0, 1);

            const z = FADER_NEAR - v.fader * TRAVEL;
            place(caps, s, stripX(s), 0.09, z);
            place(capLines, s, stripX(s), 0.166, z);

            let raw;
            if (s === 0) {
                const env = speech(time);
                v.input = env * (0.25 + 0.6 * pw);
                v.wave = env * pastor * pw + Math.random() * 0.012;
                raw = env * (0.06 + 0.9 * pastor) * pw;
            } else {
                const music = v.base + v.amp * (0.5 + 0.5 * Math.sin(time * v.f1 + v.p1)) * (0.55 + 0.45 * Math.sin(time * v.f2 + v.p2)) + v.kick * kick;
                raw = music * (0.14 + 0.86 * alive) * (0.3 + 0.85 * v.fader) * pw * (v.muted ? 0.1 : 1) + (Math.random() - 0.5) * 0.03;
            }
            v.level += (raw - v.level) * (raw > v.level ? 0.45 : 0.07);
            if (v.level > v.peak) {
                v.peak = v.level;
                v.hold = 0.7;
            } else if ((v.hold -= dt) <= 0) {
                v.peak = Math.max(v.level, v.peak - dt * 0.5);
            }

            const dim = 0.1 * pw;
            keys.setColorAt(s * 3, unit.copy(keyRows[0].color).multiplyScalar(v.mix || alive > 0.5 ? 2.2 * pw * lift : dim));
            keys.setColorAt(s * 3 + 1, unit.copy(keyRows[1].color).multiplyScalar(v.pafl && blink ? 2 * pw * lift : dim));
            keys.setColorAt(s * 3 + 2, unit.copy(keyRows[2].color).multiplyScalar(v.muted ? 2.4 * pw * lift : dim));

            const lit = Math.round(MathUtils.clamp(v.level, 0, 1) * METER);
            const peak = Math.min(METER - 1, Math.round(MathUtils.clamp(v.peak, 0, 1) * METER) - 1);
            for (let m = 0; m < METER; m++) {
                meters.setColorAt(s * METER + m, unit.copy(meterBase[m]).multiplyScalar(m < lit || m === peak ? 2.4 * pw * lift : 0.05 + 0.03 * pw));
            }
        }

        encoderSpots.forEach((spot, e) => {
            const voice = spot.strip >= 0 ? voices[spot.strip] : null;
            const pw = MathUtils.clamp(state.power, 0, 1);
            const value = MathUtils.clamp(spot.value + (voice ? voice.alive * 0.2 : 0) + Math.sin(time * 0.2 + spot.phase) * 0.02, 0, 1);
            const angle = MathUtils.degToRad(-135 + 270 * value);
            place(knobPointers, e, spot.x + Math.sin(angle) * 0.03, 0.095, spot.z - Math.cos(angle) * 0.03, -angle);
            const litCount = Math.round(value * RING);
            const color = voice ? groupColor[voice.kind] : tokens.primary;
            for (let k = 0; k < RING; k++) {
                rings.setColorAt(e * RING + k, unit.copy(color).multiplyScalar(k < litCount ? 2.2 * pw : 0.04 * pw));
            }
        });

        if (softkeys) {
            const pw = state.power;
            softkeys.userData.lit.forEach((on, i) => {
                softkeys.setColorAt(i, unit.copy(softkeys.userData.color[i]).multiplyScalar(on ? 1.8 * pw : 0.08 * pw));
            });
            softkeys.instanceColor.needsUpdate = true;
        }

        caps.instanceMatrix.needsUpdate = true;
        capLines.instanceMatrix.needsUpdate = true;
        knobPointers.instanceMatrix.needsUpdate = true;
        keys.instanceColor.needsUpdate = true;
        meters.instanceColor.needsUpdate = true;
        rings.instanceColor.needsUpdate = true;

        screenClock += dt;
        if (screenClock >= screenInterval) {
            screenClock = 0;
            screens.forEach((screen) => {
                screen.view === 'pastor' ? drawPastor(screen, state) : drawOverview(screen);
                screen.texture.needsUpdate = true;
            });
        }
        // Above 1.0 so the brightest screen text blooms slightly (bloom threshold is 1.0 in console-scene.js).
        // The screens boot in order: PASTOR first, then the overview. Floor of 0.45: a screen is never blank.
        screens.forEach((screen, i) => (screen.glass.material.emissiveIntensity = 1.5 * MathUtils.clamp(state.power * 1.4 - i * 0.25, 0.45, 1)));
    }

    const meterBase = [];
    for (let m = 0; m < METER; m++) meterBase.push(m >= METER - 1 ? tokens.primary.clone().lerp(tokens.text, 0.5) : m >= METER - 3 ? tokens.text : tokens.primary);

    // Initial state so the first frame is complete.
    update(0, 0.016, { power: 0, boot: 0, pastor: 0, mix: 0 });

    return {
        group,
        width,
        screens,
        update,
        // Camera anchors in surface units: the PASTOR fader (strip 1) and the input bank centre.
        anchors: { pastorX: stripX(0), pastorZ: FADER_NEAR - TRAVEL * 0.55, inputBankX: bankCenter(0) },
        dispose() {
            lcdTextures.forEach((t) => t.dispose());
            screens.forEach((s) => s.texture.dispose());
        },
    };
}

/** Soft radial falloff used as the contact shadow under the desk. */
function contactShadowTexture() {
    const size = 128;
    const canvas = document.createElement('canvas');
    canvas.width = canvas.height = size;
    const ctx = canvas.getContext('2d');
    const g = ctx.createRadialGradient(size / 2, size / 2, 0, size / 2, size / 2, size / 2);
    g.addColorStop(0, 'rgba(0,0,0,0.85)');
    g.addColorStop(0.55, 'rgba(0,0,0,0.4)');
    g.addColorStop(1, 'rgba(0,0,0,0)');
    ctx.fillStyle = g;
    ctx.fillRect(0, 0, size, size);
    return new CanvasTexture(canvas);
}
