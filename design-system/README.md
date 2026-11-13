# ITK Design System

The brand book for Institut Teknologi Kalimantan interfaces, copied from the published ITK Design System. **Read this before changing any front-end code**, then `MIGRATION.md` for how it applies to webstatus-v2.

## Files in this folder

| File | What it is |
|---|---|
| `tokens.json` | Source of truth for every color, type style, spacing, radius, shadow, duration and z-index, in light and dark |
| `tokens.css` | The same tokens as CSS variables (`--itk-*`), plus the Figtree `@font-face` rules. Imported by `resources/css/app.css` |
| `COMPONENTS.md` | Guidelines for all 88 components: when to use each, what it needs, and the rules it must follow |
| `MIGRATION.md` | Step-by-step instructions for moving webstatus-v2 onto this system |
| `reference/components.css` | Reference styles of the React implementation (tokens renamed to `--itk-*`). Port rules from it; don't import it |
| `reference/components.js` | Reference React implementation: markup, ARIA roles and keyboard behaviour to reproduce in Blade + Alpine |
| `reference/components.d.ts` | Props of each reference component |

## Project overrides for webstatus-v2

These win over anything below that says otherwise:

- **Language: English.** Apply the voice rules in English: sentence case, plain words, verbs on buttons ("Add website", "Save changes"), errors that say what went wrong and how to fix it. Ignore the Bahasa Indonesia examples.
- **Icons: Lucide**, stroke width 1.75, 20px beside body text, 16px in badges, 24px for standalone icons. Font Awesome is being removed.
- **Ornaments are not used.** Webstatus is a task tool (tables, forms, monitoring), and the brand book keeps ornaments out of task screens.
- **Token names in CSS carry an `itk-` prefix:** `surface` below means `--itk-surface`.
- **Navigation:** keep the existing drawer layout (sidebar on desktop, drawer on mobile). Style the sidebar per the `Sidebar` component. The `TabBar` guidance does not apply.

---

Institut Teknologi Kalimantan is a young technology institute in Balikpapan. The interface should feel like the institution: modern, precise, reassuring, quietly confident. Students, lecturers, staff and visitors all use the same system, so clarity beats decoration everywhere except a few brand moments.

## Voice and content

- Write in Bahasa Indonesia first; English pages mirror the same structure. Use the reader's word, not the system's: "Jadwal kuliah", not "Modul penjadwalan".
- Sentence case for everything: buttons, headings, tabs, menu items. Uppercase only in `label` badges and eyebrows.
- Address the reader directly and plainly ("Unggah dokumen persyaratan sebelum 30 November."). No exclamation marks, no emoji, no "Oops".
- Buttons are verbs that say what happens: "Daftar sekarang", "Unggah KRS", "Simpan perubahan". The result keeps the verb: Unggah → Terunggah.
- Errors state the problem and the fix in one line: "Ukuran maksimal 2 MB. Kompres PDF lalu coba lagi."
- Dates as "12 Nov 2026", times as "15.50 WITA" (Indonesian dot separator, time zone named for anything with a deadline).
- Numbers in tables use tabular figures and right alignment; IDs (NIM, NIP, course codes) use `code`.

## Color

The brand is two colors, both sampled exactly from the lambang: **ITK blue** `itk-blue` #2982DC and **ITK gold** `itk-gold` #FBB03B. Everything else is a cool slate neutral tinted toward that blue.

- Build UI from semantic tokens, never from the scales directly: `canvas` behind content, `surface` for cards and the header, `surface-raised` for anything that floats, `surface-sunken` for fields and table headers.
- Text is `ink` (17.3:1 on white) or `ink-muted` (6.3:1). Both hold ≥4.5:1 on every surface in both themes.
- Charts use `chart-1`…`chart-4` (each ≥3:1 on `surface`); series 2 is the brand gold, darkened in light theme.
- Interactive blue is `accent` (blue-700 #1A67B8, white text 5.7:1). `itk-blue` itself is 3.95:1 against white, so it is reserved for the logo, ornaments, large brand fields and headings at 24px or larger. Never set body text or a small button label in or on `itk-blue`.
- Gold is the signature highlight, used sparingly: the current-page underline in `SiteHeader`, `brand` badges, ornaments. Text on gold is always `on-gold` (navy, 7.8:1), never white (1.85:1). Gold never means "warning"; warnings use `warning` (a darker amber) plus an icon and a word.
- One color means one thing. Blue = interactive or informational; gold = brand emphasis; green/amber/red = status only.
- Status never relies on hue alone: every `Alert` carries an icon and a hidden tone word, and `Badge` gets `icon` wherever tones sit side by side.
- Dark theme is a deep navy-black (`slate-950` canvas), not pure black. Accent lightens to blue-400 with dark text (`on-accent` = blue-975, 6.7:1); body text is a softened white (`slate-ink-dark`).
- No gradients. In particular no purple-to-blue. Brand color lives in solid fields and in the ornament artwork.

## Typography

One family: **Figtree** (Regular, Italic, Medium, SemiBold, Bold, ExtraBold), with the system UI stack as fallback. Its open, geometric shapes sit naturally beside the squared "itk" wordmark.

- Body text is `body` 16/24. Never below 16px in inputs (prevents iOS zoom) and never below `footnote` 13/18 for running text. `label` 12/16 is for uppercase badges and eyebrows only.
- Headings: `title-1` (h1 in app screens), `title-2` (h2), `title-3` (card headings), `headline` (list titles, dialogs).
- `display-xl` 56px appears once per landing page at ≥1024px and steps down to `display-l` 40px on smaller screens. ExtraBold (800) is used only here.
- Hierarchy comes from size and weight, not color. Headings stay `ink`; `itk-blue` may color a display heading at 24px+ on landing pages, nowhere else.
- Avoid Light weights for text under 24px.

## Spacing and layout

- 4px base unit. Gutters: `space-4` (16px) at 360px, `space-6` at 768px, `space-8` at 1024px+. Content width caps at 1280px.
- Cards pad `space-4` on mobile and `space-6` from 768px. Form fields stack at `space-5`.
- Grids use `repeat(auto-fit, minmax(240px, 1fr))` so layouts follow available width, not device names.
- Breakpoints: `bp-sm` 640, `bp-md` 768, `bp-lg` 1024 (full navigation appears), `bp-xl` 1280, `bp-2xl` 1440.

## Shape, depth and motion

- Radii are modest and consistent: `radius-sm` 6px for controls, `radius-md` 10px for cards and alerts, `radius-lg` 16px for dialogs and hero media, `radius-full` only for switches, avatars and pills.
- Depth is a `border` hairline plus `shadow-1` at rest, `shadow-2` on hover, `shadow-3` for floating layers. No coloured side-stripes on cards.
- Motion is brief and functional: `duration-fast` 120ms for color changes, `duration-base` 200ms for menus and tabs, `duration-slow` 320ms for dialogs. Under `prefers-reduced-motion` transitions are removed.

## Interaction and accessibility (WCAG 2.2 AA)

- Every interactive target is at least 44×44px on touch (`Button` md, fields, tabs, nav links, checkbox rows). `sm` buttons (36px) are for dense desktop toolbars only and keep 8px of space around them.
- Focus is one ring everywhere: 2px solid `focus-ring` with 2px offset, ≥3:1 on every surface in both themes. Never remove it.
- Control borders use `border-strong` (≥3:1). Decorative hairlines use `border`.
- Labels are always visible; placeholder is an example, not a label. Errors are linked with `aria-describedby` and shown with an icon.
- `Tabs` follow the ARIA tabs pattern with arrow keys; `SiteHeader` collapses links into a menu sheet below 1024px with `aria-expanded`.
- Respect `prefers-color-scheme` for the default theme and let `data-theme` override it. `forced-colors` mode keeps selection bars visible.

## Brand assets and ornament

- **Logos**: use the full lambang + wordmark lockup (`itk-lambang-branding.png`) in `SiteHeader`, its white version (`itk-lambang-branding-white.png`) on dark or `itk-blue` grounds. The standalone lambang (`lambang-itk.png`) is for square contexts: favicons, avatars, the footer seal. `itk-branding.png` (wordmark only) is for compact co-branding. Show the logo once per page.
- **Ornaments**: the frieze, rosette and keygraphic patterns are Dayak-inspired motifs and the system's signature. Use one ornament moment per screen: a frieze band (`Ornament`) under a hero or above the footer, a rosette as a large cropped watermark (`rosette-light.png`, already ~10% opacity) in a hero corner, a keygraphic tile as a texture on a solid `itk-blue` or `blue-950` band.
- Keep ornaments out of forms, tables and task screens (SIAKAD, KRS, payments). They belong on public and landing pages.
- Never recolor, stretch, skew or crop a motif through its middle. Pair blue artwork with light grounds, white artwork with `itk-blue` / `surface-inverse`, gold artwork with `blue-950`.

## Components and the HIG

The library has 88 components. Each one's guidelines quote the Apple Human Interface Guidelines page it follows (for example `tab-bars.md`, `alerts.md`, `sheets.md`), translated to the web. Where Apple has no equivalent (FAB, Command), the guidelines say so and limit its use.

- **Platform behavior on the web:** modal views (`Dialog`, `AlertDialog`, `AlertSheet`, `Sheet`, `Drawer`, `FullscreenMenu`, `Command`) use the native `<dialog>`, so focus is trapped, Esc dismisses and focus returns to the trigger. Menus, listboxes, tabs, segmented controls and grids follow WAI-ARIA keyboard patterns.
- **Navigation by width:** below 1024px, use `TabBar` (app) or `SiteHeader` / `FullscreenMenu` (website); at 1024px and wider, use `Sidebar` (app) or `NavigationMenu` (website). In-app screens use `Header` inside `Screen`.
- **Feedback ladder, lightest first:** inline state, then `Toast` (with Urungkan), then `Alert` beside the problem, then `AlertDialog` only for destructive or blocking decisions. Never an alert for a routine, undoable action.
- **Status color is never alone:** every status pairs color with an icon or a word.
- **Haptics** (`ITK.haptics`) only complement on-screen feedback and are off where unsupported (iOS Safari, desktop).

## Iconography

- The brand sources contain no icon set. The system ships a stroked set inside `ITK.Icon` (24px grid, 1.75px stroke, round caps; 40 glyphs, see the Icon component).
- For anything beyond that set, use **Lucide** at the same 1.75 stroke so new icons match. This is a substitution, not a brand-provided set.
- Icons are 20px beside 15–17px text and take the text color. Icon-only buttons always carry a `label`.
