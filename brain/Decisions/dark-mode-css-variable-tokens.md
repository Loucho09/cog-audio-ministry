# Dark mode: CSS variable tokens
Date: 2026-11-24
Status: accepted (owner approved adding dark mode)

Related: [[dark-mode]], [[primary-dark-contrast-token]], [[public-layout]], [[00-Index]]

Context: the owner asked for dark mode. This softens the brief's "clean minimalist light" style; the owner approved it.

Decision:
- Theme colors are CSS variables (RGB channels) in `resources/css/app.css`. Light values in `:root`, dark values in `.dark`.
- `tailwind.config.js`: `darkMode: 'class'`. Tailwind tokens still map to the variables, so views use the same class names.
- An inline script in the document head sets the `dark` class before paint, which prevents a flash of the wrong theme.
- Dark values:
  - background `#0F172A`
  - surface `#1E293B`
  - text `#F1F5F9`
  - primary `#38BDF8`
  - primary-dark `#38BDF8` (the AA-contrast blue for the current theme, so `bg-primary-dark text-surface` still works; see [[primary-dark-contrast-token]])
  - accent `#0EA5E9`
- Focus ring offset follows the background.
- Elements on photos (hero) and the gallery lightbox stay dark in both themes.
- No new packages.
