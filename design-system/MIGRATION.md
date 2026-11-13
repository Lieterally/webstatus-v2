# Migrating webstatus-v2 to the ITK Design System

Instructions for Claude Code. The goal is a full front-end change (colors, type, spacing, components, icons) to the ITK Design System. **The layout stays as it is:** top bar, sidebar drawer, content area and footer. It is restyled, not rebuilt.

The work happens in two steps:

- **Step 1: retheme in place.** Map DaisyUI and Tailwind onto the ITK tokens, so every page takes the new colors, type, radii, control sizes and focus ring with almost no markup changes. Low risk, one sitting.
- **Step 2: replace components page by page.** Build ITK Blade components, convert each view to them, switch icons to Lucide, then remove DaisyUI and Font Awesome.

Read `design-system/README.md` (the brand book and the project overrides) and skim `design-system/COMPONENTS.md` before starting.

---

## Rules for every change

1. **Front end only.** Do not change controllers, models, enums, routes, Livewire component classes, validation or queries. Exception: a small presentational helper (for example a `label()` or `icon()` method on `App\Enums\SiteStatus`) is fine if it removes duplicated markup. Say so in the commit message.
2. **Keep behaviour attributes intact:** every `wire:*`, `x-*`, `@click`, `:class`, `@entangle`, `id` used by scripts (for example `downtimeTotalBadge`, `siteDowntimeTotalBadge`), form `name`s, `@csrf` and `@method`.
3. **Use tokens, never raw values.** No hex codes, no `gray-*` / `blue-*` palette classes and no arbitrary values like `bg-[#...]` in views. If a value is missing from the tokens, stop and ask.
4. **Git:** work on a branch per step (`design-system/step-1`, `design-system/step-2`). Commit after each sub-step or page, with a message naming the page.
5. **After every page**, run `npm run build` and `php artisan view:clear && php artisan view:cache`, and fix any error before moving on.
6. **Ask before deleting files** (Font Awesome assets, old images, unused views).
7. **UI language is English.** Use sentence case everywhere ("Website manager", "Add website", "Save changes"), plain verbs on buttons, and error messages that say what is wrong and how to fix it.

---

## Step 1: retheme in place

Branch: `git switch -c design-system/step-1`

### 1.1 Fonts: local Figtree

Figtree is currently loaded from the Bunny CDN in `vite.config.js`. The design system ships the font files in `resources/fonts/` (Regular, Italic, Medium, SemiBold, Bold, ExtraBold).

- In `vite.config.js`, remove the `fonts: [bunny('Figtree', …)]` option and the `bunny` import.
- Check whether any view uses `@fonts` (the default `welcome.blade.php` does). If `welcome.blade.php` is not routed, leave it alone. Otherwise remove its `@fonts` line.

### 1.2 Replace `resources/css/app.css`

Replace the whole file with the version below. It removes the old Material-style `@theme` color scales (no view uses them; the earlier audit found zero `primary-500`-style classes) and replaces them with the ITK mapping.

```css
@import 'tailwindcss';
@import '../../design-system/tokens.css';

@source '../../vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php';
@source '../../storage/framework/views/*.php';
@source '../views/**/*.blade.php';

/* ---------- Fonts (files in resources/fonts) ---------- */
@font-face { font-family: 'Figtree'; src: url('../fonts/Figtree-Regular.ttf') format('truetype'); font-weight: 400; font-style: normal; font-display: swap; }
@font-face { font-family: 'Figtree'; src: url('../fonts/Figtree-Italic.ttf') format('truetype'); font-weight: 400; font-style: italic; font-display: swap; }
@font-face { font-family: 'Figtree'; src: url('../fonts/Figtree-Medium.ttf') format('truetype'); font-weight: 500; font-style: normal; font-display: swap; }
@font-face { font-family: 'Figtree'; src: url('../fonts/Figtree-SemiBold.ttf') format('truetype'); font-weight: 600; font-style: normal; font-display: swap; }
@font-face { font-family: 'Figtree'; src: url('../fonts/Figtree-Bold.ttf') format('truetype'); font-weight: 700; font-style: normal; font-display: swap; }
@font-face { font-family: 'Figtree'; src: url('../fonts/Figtree-ExtraBold.ttf') format('truetype'); font-weight: 800; font-style: normal; font-display: swap; }

/* ---------- DaisyUI mapped onto ITK tokens (removed in Step 2.9) ---------- */
@plugin 'daisyui' {
    themes: false;
}

@plugin 'daisyui/theme' {
    name: 'light';
    default: true;
    prefersdark: false;
    color-scheme: light;
    --color-base-100: var(--itk-surface);
    --color-base-200: var(--itk-canvas);
    --color-base-300: var(--itk-border);
    --color-base-content: var(--itk-ink);
    --color-primary: var(--itk-accent);
    --color-primary-content: var(--itk-on-accent);
    --color-secondary: var(--itk-gold);
    --color-secondary-content: var(--itk-on-gold);
    --color-accent: var(--itk-accent);
    --color-accent-content: var(--itk-on-accent);
    --color-neutral: var(--itk-surface-inverse);
    --color-neutral-content: var(--itk-ink-inverse);
    --color-info: var(--itk-accent);
    --color-info-content: var(--itk-on-accent);
    --color-success: var(--itk-success);
    --color-success-content: var(--itk-on-accent);
    --color-warning: var(--itk-warning);
    --color-warning-content: var(--itk-on-accent);
    --color-error: var(--itk-danger-fill);
    --color-error-content: var(--itk-on-danger);
    --radius-selector: 0.25rem;
    --radius-field: 0.375rem;
    --radius-box: 0.625rem;
    --size-selector: 0.25rem;
    --size-field: 0.275rem;
    --border: 1px;
    --depth: 0;
    --noise: 0;
}

@plugin 'daisyui/theme' {
    name: 'dark';
    default: false;
    prefersdark: true;
    color-scheme: dark;
    /* Same mapping: the --itk-* tokens already switch values under [data-theme="dark"] */
    --color-base-100: var(--itk-surface);
    --color-base-200: var(--itk-canvas);
    --color-base-300: var(--itk-border);
    --color-base-content: var(--itk-ink);
    --color-primary: var(--itk-accent);
    --color-primary-content: var(--itk-on-accent);
    --color-secondary: var(--itk-gold);
    --color-secondary-content: var(--itk-on-gold);
    --color-accent: var(--itk-accent);
    --color-accent-content: var(--itk-on-accent);
    --color-neutral: var(--itk-surface-inverse);
    --color-neutral-content: var(--itk-ink-inverse);
    --color-info: var(--itk-accent);
    --color-info-content: var(--itk-on-accent);
    --color-success: var(--itk-success);
    --color-success-content: var(--itk-on-accent);
    --color-warning: var(--itk-warning);
    --color-warning-content: var(--itk-on-accent);
    --color-error: var(--itk-danger-fill);
    --color-error-content: var(--itk-on-danger);
    --radius-selector: 0.25rem;
    --radius-field: 0.375rem;
    --radius-box: 0.625rem;
    --size-selector: 0.25rem;
    --size-field: 0.275rem;
    --border: 1px;
    --depth: 0;
    --noise: 0;
}

/* ---------- Tailwind utilities named after ITK tokens ----------
   primary / success / warning / error share names with DaisyUI on purpose:
   both point at the same ITK token, so the names stay valid after DaisyUI is removed. */
@theme inline {
    --font-sans: var(--itk-font-sans);
    --font-mono: var(--itk-font-mono);

    --color-canvas: var(--itk-canvas);
    --color-surface: var(--itk-surface);
    --color-surface-raised: var(--itk-surface-raised);
    --color-surface-sunken: var(--itk-surface-sunken);
    --color-surface-inverse: var(--itk-surface-inverse);
    --color-line: var(--itk-border);
    --color-line-strong: var(--itk-border-strong);
    --color-ink: var(--itk-ink);
    --color-ink-muted: var(--itk-ink-muted);
    --color-ink-disabled: var(--itk-ink-disabled);
    --color-ink-inverse: var(--itk-ink-inverse);
    --color-primary: var(--itk-accent);
    --color-primary-hover: var(--itk-accent-hover);
    --color-primary-pressed: var(--itk-accent-pressed);
    --color-primary-content: var(--itk-on-accent);
    --color-primary-soft: var(--itk-accent-soft);
    --color-primary-soft-content: var(--itk-accent-soft-ink);
    --color-link: var(--itk-accent-text);
    --color-gold: var(--itk-gold);
    --color-gold-content: var(--itk-on-gold);
    --color-focus: var(--itk-focus-ring);
    --color-success: var(--itk-success);
    --color-success-soft: var(--itk-success-soft);
    --color-success-content: var(--itk-on-accent);
    --color-warning: var(--itk-warning);
    --color-warning-soft: var(--itk-warning-soft);
    --color-warning-content: var(--itk-on-accent);
    --color-error: var(--itk-danger);
    --color-error-soft: var(--itk-danger-soft);
    --color-error-fill: var(--itk-danger-fill);
    --color-error-content: var(--itk-on-danger);
    --color-chart-1: var(--itk-chart-1);
    --color-chart-2: var(--itk-chart-2);
    --color-chart-3: var(--itk-chart-3);
    --color-chart-4: var(--itk-chart-4);
    --color-itk-blue: var(--itk-itk-blue);
    --color-itk-gold: var(--itk-itk-gold);

    --radius-xs: var(--itk-radius-xs);
    --radius-sm: var(--itk-radius-sm);
    --radius-md: var(--itk-radius-md);
    --radius-lg: var(--itk-radius-lg);

    --shadow-1: var(--itk-shadow-1);
    --shadow-2: var(--itk-shadow-2);
    --shadow-3: var(--itk-shadow-3);

    /* Type scale: text-title-1, text-body, text-footnote … (weights and line heights included) */
    --text-title-1: 30px;  --text-title-1--line-height: 36px; --text-title-1--font-weight: 700; --text-title-1--letter-spacing: -0.01em;
    --text-title-2: 24px;  --text-title-2--line-height: 30px; --text-title-2--font-weight: 600;
    --text-title-3: 20px;  --text-title-3--line-height: 26px; --text-title-3--font-weight: 600;
    --text-headline: 17px; --text-headline--line-height: 24px; --text-headline--font-weight: 600;
    --text-body-l: 18px;   --text-body-l--line-height: 28px;
    --text-body: 16px;     --text-body--line-height: 24px;
    --text-callout: 15px;  --text-callout--line-height: 22px; --text-callout--font-weight: 500;
    --text-footnote: 13px; --text-footnote--line-height: 18px;
    --text-label: 12px;    --text-label--line-height: 16px; --text-label--font-weight: 600; --text-label--letter-spacing: 0.04em;
}

/* Tailwind's default 4px spacing scale already matches ITK spacing (p-4 = space-4 = 16px), so it stays. */

@custom-variant dark (&:where([data-theme=dark], [data-theme=dark] *));

/* ---------- Global rules (unlayered on purpose, so they beat DaisyUI) ---------- */
html {
    background: var(--itk-canvas);
    color: var(--itk-ink);
}

:focus-visible {
    outline: 2px solid var(--itk-focus-ring);
    outline-offset: 2px;
}

[x-cloak] {
    display: none !important;
}

/* Fix DaisyUI drawer scroll - allow main content to scroll (remove with DaisyUI) */
.drawer-content {
    overflow-y: auto !important;
}

/* ---------- DaisyUI adjustments to meet the design system (remove in Step 2.9) ---------- */
.input, .select, .textarea {
    background-color: var(--itk-surface-sunken);
    border-color: var(--itk-border-strong);   /* ≥3:1 control borders */
    font-size: 1rem;                          /* 16px: no iOS zoom on focus */
}
.checkbox, .radio, .toggle {
    border-color: var(--itk-border-strong);
}
.table :where(th) {
    color: var(--itk-ink-muted);
    font-weight: 600;
}

@media (prefers-reduced-motion: reduce) {
    *, *::before, *::after {
        transition-duration: 0ms !important;
        animation-duration: 1ms !important;
        animation-iteration-count: 1 !important;
    }
}
```

If the build rejects the `@plugin 'daisyui/theme'` syntax, check the installed DaisyUI version's docs in `node_modules/daisyui` and adapt the syntax, keeping the same values.

### 1.3 Theme: follow the system, allow an override

In `resources/views/layouts/app.blade.php`, `<html>` currently hard-codes `data-theme="light"`. Remove that attribute and add this as the **first** element inside `<head>`, before `@vite`:

```html
<script>
    try {
        var t = localStorage.getItem('itk-theme') ||
            (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        document.documentElement.dataset.theme = t;
    } catch (e) {
        document.documentElement.dataset.theme = 'light';
    }
</script>
```

Do the same in `auth/login.blade.php` if it has its own `<html>`. The visible theme toggle comes in Step 2.2.

### 1.4 Chart colors from tokens

Both chart libraries currently use hard-coded hex values (`#1565C0`, `#DC2626`, `rgba(21,101,192,.1)`, `#f8f8f8`). Create `resources/js/itk-theme.js`:

```js
// Reads ITK tokens from CSS so charts follow the current theme.
export function itkToken(name) {
    return getComputedStyle(document.documentElement).getPropertyValue(`--itk-${name}`).trim();
}

export function itkChartColors() {
    return {
        series: [itkToken('chart-1'), itkToken('chart-2'), itkToken('chart-3'), itkToken('chart-4')],
        primary: itkToken('chart-1'),
        danger: itkToken('danger'),
        success: itkToken('success'),
        warning: itkToken('warning'),
        grid: itkToken('border'),
        text: itkToken('ink-muted'),
        surface: itkToken('surface'),
        font: itkToken('font-sans'),
    };
}

export function applyChartDefaults(Chart, ApexCharts) {
    const c = itkChartColors();
    Chart.defaults.color = c.text;
    Chart.defaults.borderColor = c.grid;
    Chart.defaults.font.family = c.font;
    Chart.defaults.font.size = 12;
    window.Apex = {
        chart: { fontFamily: c.font, foreColor: c.text, background: 'transparent', toolbar: { show: false } },
        grid: { borderColor: c.grid },
        theme: { mode: document.documentElement.dataset.theme === 'dark' ? 'dark' : 'light' },
        tooltip: { theme: document.documentElement.dataset.theme === 'dark' ? 'dark' : 'light' },
    };
}
```

In `resources/js/app.js`, import it, call `applyChartDefaults(Chart, ApexCharts)` once, and expose `window.itkChartColors = itkChartColors`.

Then, in `livewire/dashboard.blade.php` and `downtime/index.blade.php`, replace every hard-coded chart color with values from `window.itkChartColors()`:

- Response-time lines use `primary`, with a fill made from it at 10% alpha (`color-mix(in srgb, <primary> 10%, transparent)`). If the chart library won't accept `color-mix`, convert the hex to rgba in JS.
- Downtime bars use `danger`.
- Apex grid row colors use `surface-sunken` and `transparent`.

Charts must re-render when the theme changes. Listen for a `itk-theme-change` window event (dispatched by the toggle in Step 2.2), then destroy and re-create the charts, or call their update method with new colors.

### 1.5 Check Step 1

- `npm run build` passes; the browser shows no 404s for fonts; Figtree renders at weights 400–800.
- Visit every page in light **and** dark (set `localStorage.setItem('itk-theme','dark')` in the console and reload). Text is readable everywhere and no white panels are left in dark mode. Look out for any `bg-white` / `text-gray-*` classes; the audit found about 20, list them for Step 2.
- Inputs and buttons are 44px tall; keyboard focus shows the blue 2px ring on every control.
- **Known and accepted in Step 1:** the top bar is still a solid blue band and the footer still shows the color logo on a dark ground. Both are fixed in Step 2.2.

Commit, then merge or keep the branch, as the user prefers.

---

## Step 2: replace components page by page

Branch: `git switch -c design-system/step-2`

### 2.1 Foundations: icons and Blade components

**Icons: Lucide.** Install a server-side Blade icon set so icons survive Livewire re-renders. `composer require mallardduck/blade-lucide-icons` gives `<x-lucide-name />` (verify the package supports Laravel 13 first). If it doesn't, create `resources/views/components/itk/icon.blade.php`, which renders inline Lucide SVGs from a small map of the icons this app uses. Do **not** use the client-side `lucide.createIcons()`, because Livewire morphing would undo it.

All icons: `stroke-width="1.75"`, `aria-hidden="true"`, size 20px beside body text, 16px in badges and buttons-sm, 24px for standalone or empty-state icons. Icon-only buttons need `aria-label`.

Font Awesome → Lucide map for the current views (extend as you find more):

| Font Awesome | Lucide |
|---|---|
| fa-home | house |
| fa-triangle-exclamation | triangle-alert |
| fa-globe | globe |
| fa-users | users |
| fa-id-card | id-card |
| fa-telegram (brand) | send (Lucide has no brand logos; use `send` and keep the word "Telegram" in the label) |
| fa-gear | settings |
| fa-key | key-round |
| inline hamburger SVG | menu |
| inline check-circle SVG | circle-check |
| inline x-circle SVG | circle-x |

**Blade components.** Create anonymous components in `resources/views/components/itk/`. Port each one's markup, states and ARIA from `design-system/reference/components.js`, its styles from `reference/components.css`, and its rules from `COMPONENTS.md`. Style them with the Tailwind token utilities from Step 1.2 (`bg-surface`, `text-ink`, `border-line-strong`, `bg-primary text-primary-content`, `rounded-sm`, `shadow-1` …). Use Alpine for behaviour.

These are the components this app needs. Build each one when the first page that uses it is converted, not all up front.

| Blade component | ITK component(s) | Replaces |
|---|---|---|
| `x-itk.button` (variant: primary, secondary, ghost, danger; size: sm, md, lg; `icon`; `href`; loading via `wire:loading`) | Button | `btn`, `btn-*` |
| `x-itk.button-group` | ButtonGroup | `join` with buttons |
| `x-itk.toggle-group` (segmented) | ToggleGroup | `join` used as a filter (1h / 24h / 7d) or the table/card view switch |
| `x-itk.field` + `x-itk.input` / `x-itk.select` / `x-itk.textarea` (label, hint, `error` defaulting to `$errors->first($name)`, required) | Field, TextField, NativeSelect, Textarea | `label` + `input input-bordered` / `select` / `textarea` blocks |
| `x-itk.checkbox`, `x-itk.switch` | Checkbox, Switch | `checkbox`, `toggle` |
| Tom Select styling (CSS file `resources/css/tom-select-itk.css`, imported in app.css) | Combobox look | Tom Select's default theme: field = 44px, `surface-sunken`, `border-line-strong`; dropdown = `surface-raised`, `shadow-3`, `rounded-md`; active option = `primary-soft` |
| `x-itk.badge` (tone: neutral, info, success, warning, danger, brand; `icon`) | Badge | `badge`, `badge-*` |
| `x-itk.status-badge :status` | Badge with icon | the three duplicated `match` blocks in the dashboard: Up = success + `circle-check` + "Up"; PartiallyDown = warning + `triangle-alert` + "Partially down"; TotallyDown = danger + `circle-x` + "Down"; anything else = neutral + "Unknown" |
| `x-itk.alert` (tone, title, dismissible) | Alert | `alert`, flash messages |
| `x-itk.toast` + an Alpine store `$store.toasts` | Toast, Toaster | success flashes after routine, undoable actions (save, update) |
| `x-itk.card` | Card | `card`, `card-body`, `card-title` |
| `x-itk.stat` | Widget (dashboard tile) | `stats`, `stat`, `stat-title`, `stat-value`, `stat-desc`, `stat-figure` |
| `x-itk.table` (wrapper with caption, scroll region) | Table | `table`, `table-zebra`, `table-sm` (no zebra: use hairlines + `primary-soft` row hover) |
| `x-itk.page-header` (title, description, actions slot) | Header | the `<h1>` + button rows at the top of each page |
| `x-itk.alert-dialog` (native `<dialog>` + Alpine; destructive) | AlertDialog | the `modal modal-open` delete confirmations in `users/index` and `sites/index` |
| `x-itk.dialog`, `x-itk.sheet` (side = right) | Dialog, Sheet | other modals; the dashboard live-log drawer (`toggleLogDrawer`) becomes a right-side Sheet |
| `x-itk.tooltip` | Tooltip | `tooltip` + `data-tip` |
| `x-itk.empty` | Empty | "No data" / empty table rows |
| `x-itk.progress`, `x-itk.spinner` | Progress, Spinner | `progress`, `loading loading-*` |
| `x-itk.trace` | Trace | any `steps` / timeline of downtime events |
| `x-itk.pagination` | Pagination | `vendor/pagination/daisy.blade.php` and `vendor/livewire/daisy-pagination.blade.php` (rewrite both to use it) |

### 2.2 Layout: `layouts/app.blade.php`

Keep the structure (drawer, top bar, sidebar, content, footer); restyle it:

- **Top bar:** `bg-surface border-b border-line text-ink`, 64px tall (72px from `lg`), sticky. Show the color lockup `images/brand/itk-lambang-branding.png` in light mode and `itk-lambang-branding-white.png` in dark mode (two `<img>`s toggled by the `dark:` variant), 40px tall, with "Webstatus" in `text-headline` beside it. On the right: user name (`text-callout`) and role (`text-footnote text-ink-muted`); a **theme toggle** (ghost icon button, `sun` / `moon`, `aria-label="Switch to dark theme"` / "Switch to light theme"; on click it sets `data-theme`, saves `localStorage.itk-theme` and dispatches `itk-theme-change`); and "Log out" as a secondary `sm` button. The mobile menu button is a 44px ghost icon button (`menu`) with `aria-label="Open navigation"`.
- **Sidebar** (per the `Sidebar` component): `bg-surface border-r border-line`, width 272px. Links are 44px tall, `rounded-sm`, `text-callout`, with a 20px Lucide icon in `text-ink-muted`. The current page gets `bg-primary-soft text-primary-soft-content font-semibold`, a 3px gold bar on its left edge and `aria-current="page"`. Section titles ("Administration", "Settings") use `text-label uppercase text-ink-muted`. Nav labels in sentence case: "Dashboard", "Downtime details", "Website manager", "User manager", "IT staff manager", "Telegram manager", "System config", "Change password".
- **Content:** `bg-canvas`, padding 16 / 24 / 32px by breakpoint (already `p-4 md:p-6 lg:p-8`), max width 1280px, centered.
- **Flash messages:** `session('error')` → `x-itk.alert tone="danger"`; `session('success')` → a toast (or a success alert if it must persist).
- **Footer:** `bg-surface border-t border-line`, `text-footnote text-ink-muted`, the square `images/brand/lambang-itk.png` at 32px (white variant in dark). No dark band.
- Add a "Skip to content" link as the first focusable element, targeting `<main id="content">`.

### 2.3 Login: `auth/login.blade.php`

Centered card on `bg-canvas`, logo above, `x-itk.field` + inputs with `autocomplete="username"` / `"current-password"`, one primary full-width "Log in" button, and errors under their fields.

### 2.4 Dashboard: `livewire/dashboard.blade.php`

- Page header with the countdown / refresh cycle. The countdown uses `tabular-nums`.
- Summary cards → `x-itk.stat` tiles in `grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4`. Down counts use the danger tone with an icon, never color alone.
- Filters (`join` + `btn-xs`) → `x-itk.toggle-group`, with `wire:click` kept on each option.
- Table/card view switch → `x-itk.toggle-group` with icon options (`table`, `layout-grid`) and aria-labels.
- Site list table → `x-itk.table`. Status column → `x-itk.status-badge`. Response time right-aligned with `tabular-nums`.
- Card view → `x-itk.card` per site.
- Live log drawer → `x-itk.sheet`.
- Charts already follow tokens from Step 1.4.

### 2.5 Website manager: `sites/*`, `categories/*`

Index pages: page header with a primary "Add website" button, `x-itk.table`, `x-itk.tooltip` for the page-path list, `x-itk.alert-dialog` for delete (title "Delete {name}?", message saying what is lost, confirm "Delete website", focus starts on Cancel). Create/edit forms: `x-itk.field` stack (`space-y-5`), max width ~640px, footer with Cancel (secondary) + Save (primary), and errors under their fields.

### 2.6 Downtime: `downtime/index.blade.php`

Filters as fields or a toggle-group, the ApexCharts chart (already tokenized), the table → `x-itk.table`, and "Ongoing" → `x-itk.badge tone="danger" icon`.

### 2.7 Administration and settings

`users/*`, `it-staff/*`, `telegram-targets/*`, `system-config/index`, `profile/password`: same patterns as 2.5. System config toggles → `x-itk.switch` inside list rows (Switch belongs in rows; elsewhere use Toggle).

### 2.8 Pagination

Rewrite `vendor/pagination/daisy.blade.php` and `vendor/livewire/daisy-pagination.blade.php` with `x-itk.pagination`: "Previous" / "Next" with chevrons, the current page as `aria-current="page"` on `bg-primary text-primary-content`, 44px targets, and "Page 4 of 12" below 520px.

### 2.9 Remove DaisyUI, Font Awesome and leftovers

1. Search `resources/views` for DaisyUI classes and convert any left: `btn`, `card`, `badge`, `alert`, `table`, `input`, `select`, `textarea`, `toggle`, `checkbox`, `modal`, `stat`, `join`, `menu`, `navbar`, `drawer`, `tooltip`, `loading`, `progress`, `link`, `bg-base-*`, `text-base-content`, `*-content`. The drawer may be rebuilt with Alpine (checkbox-free: a button with `aria-expanded`, Esc to close, focus moved into the panel).
2. In `app.css`, remove both `@plugin 'daisyui…'` blocks and the "DaisyUI adjustments" block; run `npm uninstall daisyui`.
3. Lock the palette: inside the `@theme inline` block, add `--color-*: initial;` as its first line so off-brand classes like `bg-blue-500` no longer exist. Then confirm `npm run build` passes and nothing visible changed.
4. Remove the 14 Font Awesome `<link>` tags from the layout. **Ask the user** before deleting `public/assets/font-awesome/`, `resources/css/*` Font Awesome copies (`all.css`, `pro.min.css`, `fontawesome.css`, `woff2.css` …), `resources/webfonts/`, and the old `public/images/Logo_ITK*.webp`.
5. Search for raw colors: `grep -rnE "#[0-9a-fA-F]{3,6}|(bg|text|border)-(gray|slate|blue|red|green|yellow)-[0-9]" resources/views resources/js` should return nothing except inside comments.

### 2.10 Check Step 2 (each page and at the end)

- Light and dark: every text pair readable; status always has icon + word.
- Keyboard only: you can reach and operate everything; the focus ring is visible; dialogs trap focus, close on Esc and return focus to their trigger.
- 360px wide: no horizontal page scroll (tables scroll inside their frame); targets ≥44px.
- Livewire still works: polling, filters, view switch, live log, countdown, delete confirmations, pagination.
- `npm run build` and `php artisan view:cache` pass; `php artisan test` passes if tests exist.

---

## Prompts to paste into Claude Code

**Step 1:**

> Read `design-system/README.md` and `design-system/MIGRATION.md`. Then carry out **Step 1** of MIGRATION.md exactly, on a new branch `design-system/step-1`. Follow the "Rules for every change". Commit after each sub-step (1.1 … 1.4), run the checks in 1.5, and give me a list of anything that looked wrong, including the leftover raw color classes you found. Stop after Step 1.

**Step 2** (after you have reviewed Step 1):

> Read `design-system/README.md`, `design-system/COMPONENTS.md` and `design-system/MIGRATION.md`. Carry out **Step 2** on a new branch `design-system/step-2`, in the order 2.1 → 2.10. Build each `x-itk.*` component when the first page needs it, porting markup and behaviour from `design-system/reference/`. Convert one page at a time, commit after each, and run the build and view-cache check after each. After 2.2 (layout) and 2.4 (dashboard), stop and show me before continuing. Ask before deleting any files.
