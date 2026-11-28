# 3D desk on tablets and phones
Date: 2026-11-28
Status: accepted (owner decision)

Related: [[homepage-redesign]], [[mobile-pass]], [[mobile-pass-tasks]], [[motion-and-3d-packages]], [[hero-join-brand-exception]], [[00-Index]]

Supersedes:
- "3D only on capable desktops" (CLAUDE.md homepage rule, and the desktop-only lines in [[motion-and-3d-packages]]).
- The mobile-pass performance bullet "3D/three.js never downloads or runs below 1024px" (written earlier on 2026-11-28, in [[mobile-pass]]).

Context: the owner wants tablets to get the live desk, not only desktops.

Decision:
- At 768px and up (tablets and desktops): live 3D at medium quality.
  - No depth of field.
  - Low bloom, on the LEDs and screens only.
  - Device pixel ratio capped at 1.5.
  - 1K–2K textures.
  - Scroll chapters and fader interaction stay.
- Capability check (all must pass):
  - WebGL2 available.
  - `navigator.deviceMemory` >= 4 when the browser supports it.
  - FPS in the first second >= 45.
  - Fail: the re-rendered still image (poster) stays.
- Below 768px (phones): poster first. A light 3D version loads only if the phone passes the same check.
- Reduced motion: poster always, no 3D.
- The poster shows instantly while 3D loads, then crossfades to the live scene.
- Only after the check passes does three.js load (see [[mobile-pass]] performance rule).

Why: the owner wants tablets to get the live desk.

Open (decide at build time): the FPS check needs a running frame loop before three.js is loaded. The build must pick a measuring method that does not add a heavy download to phones that fail.
