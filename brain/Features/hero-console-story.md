# Hero console story + attention-seeking Join us button
Status: in progress
Phase: 1 (homepage rework, 2026-11-27)
Goal: the hero keeps the cover-photo background (dark, grid lines, red/blue streaks, grain) and adds the live Three.js dLive S5000-style desk on the right/lower-right (3/4 angle, partly in shadow). Scrolling plays a 5-chapter story. The Join us button becomes the one attention-seeking control on the page.
Users: visitor
Screens / routes: `/` hero (`resources/views/components/home/hero.blade.php`), `resources/js/home/hero.js`, `console-scene.js`, `dlive-surface.js`, `join-button.js`.
Data changes: none.
Permissions: public.
Out of scope: GLB model, Draco/KTX2, other sections.
Tasks: [[hero-console-story-tasks]]
Notes / decisions: [[hero-join-brand-exception]], [[homepage-redesign]], [[homepage-console-brand]]

## 3D desk
- Real-time desk behind the text, inside the current background. Right/lower-right, 3/4 angle, partly in shadow; the headline on the left stays readable.
- Idle motion (always, subtle): meters move to a fake signal, scribble-strip LEDs glow, faders drift a few mm, very slow camera drift.
- Mouse parallax: desk tilts max 3-4 degrees toward the cursor, with inertia.
- One red and one blue area light hit the chassis (the streaks become real light).
- Performance: lazy-load, DPR cap, lighter mobile version, reduced motion = static render (poster).

## Scribble strips (12)
PASTOR, WL 1, WL 2, CHOIR L, CHOIR R, KEYS, AG, BASS, KICK, SNARE, OH L, OH R.

## Chapters (desktop pinned scroll; one short line per chapter, SplitText reveal, enters and exits in sync with the camera)
0. **Silence**: dark desk, the "COG Audio Ministry" headline visible. The headline fades out as chapter 1 starts.
1. **Power on**: screens boot, LEDs light in sequence, camera slowly pushes in. Label "CH 01 — POWER ON". Text: "Every Sunday, before the first word is spoken…"
2. **The fader**: camera moves in close on the PASTOR channel; that fader rises with the scroll, its meter goes up, the screen waveform grows from flat to clear speech. Label "CH 02 — THE FADER". Text: "…someone makes sure it's heard."
3. **The mix**: the other channels come alive one by one (vocals, choir, keys, drums), faders at different levels, meters full, camera glides along the bays then pulls wide. Label "CH 03 — THE MIX". Text: "Every voice. Every instrument. In balance."
4. **The room**: pull back to the full desk. Label "CH 04 — THE ROOM". Text: "We serve behind the sound." Subline: "Serve with us. Halina at sumama sa amin!" plus Join us and See service times buttons. Then unpin smoothly into the next section.

Phones and reduced motion: no pin; the headline block shows with the buttons (no chapter story).

## Join us button (hero only; "See service times" stays a quiet outline button)
- Left of the text: 5 mini level-meter bars bouncing like a live signal (random heights, ~160ms; ~90ms on hover).
- A short light (#7dd3fc) runs round the border (SVG rect, stroke-dasharray + animated stroke-dashoffset, ~2.6s loop).
- Sonar: every 4s, two rings ripple out (350ms apart) and fade.
- Pill tooltip above, fades in 1.5s after load: "● Volunteers needed — sound, stream, stage" (sky-blue #0EA5E9 dot with a soft pulse; no green).
- Hover: text scramble settling on "JOIN US" (GSAP ScrambleText), arrow slides 5px right, scale 1.04, background lightens.
- Magnetic: follows the cursor within ~160px (GSAP quickTo, max ~15px, elastic return).
- Click: press 0.95 → 1.04, 3 sound-wave circles burst, text scrambles to "LET'S GO", navigate to the form after ~400ms.
- Sonar and border light pause off-screen (IntersectionObserver). Reduced motion: only the meter bars, static.
