# ITK components — guidelines

One section per component in the ITK Design System. They were written for the React reference implementation (`reference/components.js`); in this project each one becomes a Blade component under `resources/views/components/itk/`, with Alpine for behaviour. Props listed under **Consumer provides** become Blade props/slots. Rules that quote Apple HIG pages (`tab-bars.md`, `alerts.md`…) refer to Apple's Human Interface Guidelines.

Token names in these guides (`surface`, `ink`, `accent`…) are `--itk-surface`, `--itk-ink`, `--itk-accent` in CSS, and `bg-surface`, `text-ink`, `bg-primary` etc. in Tailwind (see MIGRATION.md).


---

## Accordion

A stack of disclosures, for FAQs and long reference pages.

**Consumer provides:** `items` (`{value, title, content}`), `type` (`single` default, or `multiple`), `value` + `onChange` or `defaultValue`, `headingAs` (default `h3`).

### Rules
- Each header is a real heading with a button inside, so screen-reader users can jump between questions.
- HIG `disclosure-controls.md`: use it to reduce clutter, not to hide the main content of a page. If most readers open every item, show the content instead.
- Questions are phrased the way people ask them.

---

## Alert

An inline message about the page or a form, with tone icon and hidden tone word.

**Consumer provides:** `tone` (`info` · `success` · `warning` · `danger`), `children`, optional `title`, `onDismiss` (shows a close button), `toneLabel` (overrides the screen-reader prefix, default Info / Berhasil / Perhatian / Galat; pass English words on English pages).

### Rules
- Put it where the problem is: top of the form for submit errors, top of the page for system notices.
- `danger` and `warning` announce immediately (`role="alert"`); `info` and `success` are polite (`role="status"`).
- Say what happened and what to do next. No apologies, no "Oops".
- Not for confirmations of routine undoable actions; use inline state instead.

---

## AlertDialog

An interrupting question that must be answered before continuing, usually to confirm a destructive action.

**Consumer provides:** `open`, `title` (the question), optional `message`, `confirmLabel` (the verb), `cancelLabel` (default "Batal"; `null` hides it), `destructive`, `onConfirm`, `onCancel`.

### Rules
- HIG `alerts.md`: use alerts sparingly; never for information alone, never for common undoable actions, never on launch; title describes the situation; succinct button titles; avoid "OK" unless purely informational; place buttons where people expect.
- Cancel left, action right. For destructive actions the action is red and focus starts on Cancel.
- It cannot be dismissed by clicking outside; Esc equals Cancel.

---

## AlertSheet

An action sheet: a short list of choices for an action the person just started, rising from the bottom on phones.

**Consumer provides:** `open`, `onClose`, `actions` (`{label, onSelect, destructive?}`), optional `title`, `message`, `cancelLabel`.

### Rules
- HIG `action-sheets.md`: "Use an action sheet — not an alert — to offer choices related to an intentional action"; keep titles to one line; provide a message only if necessary; include Cancel; make destructive choices prominent; avoid letting it scroll.
- Two to five actions. Centered on tablet and desktop.

---

## AmountInput

Currency entry in Rupiah with live grouping (4.500.000) and a numeric keyboard.

**Consumer provides:** `label`, `value` (a number or `null`) + `onChange(number)` or `defaultValue`, optional `max`, `hint`, `error`, `currencyLabel` (default "Rp").

### Rules
- HIG `text-fields.md`: "Use a number formatter to help with numeric data." The value you receive is a plain number; the field shows Indonesian grouping.
- Whole Rupiah only; no decimals.
- Show the bill amount nearby so people can compare, and validate against it on blur.

---

## AspectRatio

Holds media at a fixed ratio so the layout does not jump while it loads.

**Consumer provides:** `ratio` (default 16/9), one child (`img`, `video`, embed).

### Rules
- Use 16:9 for video and campus photography, 4:3 for news thumbnails, 1:1 for avatars and rosettes.
- Images inside are cropped with `object-fit: cover`; give them real `alt` text.

---

## Attachment

A file tile: type, name, size, upload progress or error, and remove.

**Consumer provides:** `name`, optional `size` (bytes), `href` (downloadable), `progress` (0–100 while uploading), `error`, `onRemove`.

### Rules
- HIG `progress-indicators.md`: determinate progress while uploading, and let people halt it (the remove button reads "Batalkan" during upload).
- Errors say what's wrong in words and keep the file visible so people know which one to fix.
- Accept drag-and-drop and paste where people add files (HIG `entering-data.md`).

---

## Avatar

A person's photo or initials.

**Consumer provides:** `name` (always; used for initials and the accessible name), optional `src`, `size` (`sm` 32, `md` 40, `lg` 56), `status` (`online` / `offline`).

### Rules
- Falls back to initials on `accent-soft` when the photo is missing or fails.
- Decorative next to a visible name: the row's text carries the name.
- Status dots also need words somewhere nearby ("Daring") for color-independent meaning.

---

## Badge

A short uppercase status label attached to a row, card or title.

**Consumer provides:** `children` (one or two words), `tone` (`neutral` · `info` · `success` · `warning` · `danger` · `brand`), optional `icon` (adds the tone's glyph).

### Rules
- Status is carried by the word first, color second. Turn `icon` on wherever badges of different tones sit together, so success and danger never differ by hue alone.
- `brand` (gold) marks editorial highlights ("Baru", "Unggulan"), never a status.

---

## BottomBar

A toolbar fixed to the bottom of a mobile screen, for the screen's primary action and its context.

**Consumer provides:** `children` (a summary and one or two buttons), `label`.

### Rules
- HIG `toolbars.md`: actions on the current view live in a toolbar; "Reduce the use of toolbar backgrounds and tinted controls". One `primary` button, right-aligned.
- Reachable by thumb (HIG `layout.md`: important controls mid-screen or lower); pads for the home-indicator safe area.
- Not combined with `TabBar` on the same screen: a task screen replaces the tab bar.

---

## Breadcrumb

Shows where a page sits in the site hierarchy and links back up it.

**Consumer provides:** `items` (`{label, href}`; the last item has no `href` and is marked `aria-current="page"`), optional `label` (default "Breadcrumb").

### Rules
- Use on content pages three or more levels deep; not on the home page or inside app flows.
- Wraps on narrow screens; never truncates the current page name.

---

## Bubble

The text balloon of a chat message.

**Consumer provides:** `children`, `from` (`me` | `them`).

### Rules
- Mine on `accent` with `on-accent` text, aligned to the end; theirs on `surface-sunken` with a border, aligned to the start. Alignment and shape carry who sent it, not color alone.
- Max 520px; long words wrap. Use `Message` for avatar, name and time.

---

## Button

Triggers an action or submits a form; the one control every screen leans on.

**Consumer provides:** `children` (the label, a verb phrase), optional `variant` (`primary` · `secondary` · `ghost` · `danger`), `size` (`sm` 36px · `md` 44px · `lg` 52px), `icon` / `iconRight` (a name from `ITK.Icon`), `loading`, `disabled`, and either `onClick` or `href` (renders an `<a>`). An icon-only button needs `label`, which becomes its `aria-label`.

### When to use
- `primary`: the single most likely action in a view (Daftar, Simpan, Kirim). One per view, two at most.
- `secondary`: alternatives that sit beside the primary (Batal, Lihat jadwal).
- `ghost`: low-emphasis and inline actions, "Selengkapnya" links that behave like buttons.
- `danger`: destroys data (Hapus berkas). Always behind a confirmation that names what is lost.

### Rules
- Label says what happens: "Unggah KRS", not "Submit". Keep the verb through the flow (Unggah → Terunggah).
- Sentence case, Bahasa Indonesia first.
- `md` (44px) is the default on touch; `sm` only in dense desktop tables and toolbars.
- `loading` keeps the width and disables the button; never swap the label for a spinner alone.
- Fill is `accent` (blue-700), never `itk-blue`: white on `itk-blue` is 3.95:1 and fails at 16px.

---

## ButtonGroup

Groups related buttons so they read as one set of choices or one cluster of actions.

**Consumer provides:** `children` (`Button`s), `label` (accessible name for the group), optional `attached` (default `true`: buttons share edges; `false`: spaced by `space-2`).

### Rules
- Attached groups hold peers of equal weight (Hari / Minggu / Bulan). If exactly one is selected at a time, use `ToggleGroup` instead so the state is announced.
- Spaced groups put the primary action first in reading order and keep at most one `primary` (HIG `buttons.md`: keep prominent buttons to one or two per view).
- Every button stays 44px tall; never shrink a group to fit a toolbar.

---

## Calendar

A month grid for picking a date and showing which days have events.

**Consumer provides:** `value` + `onChange(date)` or `defaultValue`, optional `min`, `max`, `isDisabled(date)`, `marked(date)` (shows a gold dot), `weekStartsOn` (default 1, Senin), `locale` (default `id-ID`), `today`.

### Rules
- HIG `pickers.md`: avoid switching views to show a picker; use a compact date picker when space is constrained. For a single date in a form, prefer `<input type="date">` in a `Field`; use `Calendar` where the month view itself is useful (jadwal, booking ruang).
- Keyboard: arrows move by day and week, PageUp/PageDown by month, Home/End to week edges.
- Days are 44px targets; today is bold `accent-text`; the selection is an `accent` circle; events are described in the label ("ada agenda").

---

## Card

A bounded group of related content: a program, an announcement, a course.

**Consumer provides:** `title`, optional `eyebrow` (category or date), `children` (body text), `footer` (badges, buttons), `media` (an image element placed first), `href` (makes the whole card one link with hover lift), `titleAs` (heading level, default `h3`).

### Rules
- A linked card is one link: no buttons inside it.
- Edge is a `border` hairline plus `shadow-1`; no colored side stripes.
- Padding steps from `space-4` to `space-6` at 768px.
- Grid cards with `repeat(auto-fit, minmax(240px, 1fr))` so 360px gets one column and 1440px gets four.

---

## Carousel

A horizontally paged set of cards, with Previous / Next buttons and a page indicator.

**Consumer provides:** `children` (slides), `label`, optional `peek` (shows the edge of the next slide on mobile).

### Rules
- No autoplay. People swipe, scroll or press the buttons (WCAG 2.2.2; HIG `motion.md`: motion is optional).
- HIG `scroll-views.md`: "Consider showing a page control when a scroll view is in page-by-page mode", and make scrollability apparent.
- Three to seven slides. Anything people must not miss does not go in a carousel.

---

## Chart

Bar or line charts drawn in SVG from the chart tokens, with a summary and a hidden data table.

**Consumer provides:** `title`, `summary` (one sentence with the takeaway), `data` (rows), `x` (category key), `series` (`{key, label}`, up to four), optional `type` (`bar` | `line`), `height`, `max`, `format(value)`, `xLabel`.

### Rules
- HIG `charts.md` and `charting-data.md`: choose the mark for the message, start bar axes at zero, write a description and "summarize the main message", keep visual hierarchy consistent, and never require interaction to reveal critical information. The summary is required.
- Series use `chart-1`…`chart-4`, each ≥3:1 on `surface`; series 2 lines are dashed so no series relies on hue.
- Every chart carries a screen-reader table of its values. For more than four series or exact lookups, use `Table`.

---

## Checkbox

An independent yes/no choice, or one of several non-exclusive options.

**Consumer provides:** `label`, optional `description`, and native checkbox props (`checked`/`onChange`, `name`, `disabled`).

### Rules
- The whole row (box + label) is the hit area, ≥44px tall.
- Use `Switch` instead when the change applies immediately (settings); checkboxes are for forms that are submitted.

---

## Chip

A compact pill: a filter people turn on and off, or a removable token representing a chosen value.

**Consumer provides:** `children`; for filters `selected` + `onSelectedChange` or `defaultSelected`, optional `icon`; for tokens `onRemove`.

### Rules
- Filter chips sit in a horizontal row above results and announce their state (`aria-pressed`) with a checkmark, not color alone.
- Token chips show what is selected in a multi-select or search (HIG `search-fields.md`: "Use tokens to filter by common search terms or items"); each has a 28px remove button with a full label ("Hapus Informatika").
- Hit area is padded to 44px.

---

## CodeBlock

Preformatted code with a title and a copy button.

**Consumer provides:** `code` (string), optional `title` or `language`, `lineNumbers`.

### Rules
- The copy button confirms in place ("Tersalin") instead of a toast (HIG `feedback.md`: integrate status into the interface).
- The block scrolls sideways and is keyboard-focusable; never wrap code.
- Inline code in prose uses the `code` text style, not this component.

---

## Collapsible

One disclosure: a labelled button that shows and hides a region.

**Consumer provides:** `title`, `children`, `open` + `onOpenChange` or `defaultOpen`.

### Rules
- HIG `disclosure-controls.md`: "hide details until they're relevant", give a descriptive label, and place it next to the content it controls.
- Never hide something people need to finish the task.
- For several in a row, use `Accordion`.

---

## Combobox

A text field that filters a list as people type, for choosing from many known options.

**Consumer provides:** `label`, `options` (`{value, label, description?, keywords?}`), `value` + `onChange` or `defaultValue`, optional `placeholder`, `hint`, `emptyText`.

### Rules
- HIG `combo-boxes.md`: use an introductory label so people know what to expect, provide relevant choices, and keep list items no wider than the field. HIG `search-fields.md`: start filtering immediately as people type.
- The result count is announced; an empty result says so in words.
- Choosing an option fills the field with its label; the `value` is the code.

---

## Command

A search-driven command palette (Ctrl/⌘ + K) for jumping to pages and running actions.

**Consumer provides:** `groups` (`{title, items: [{label, icon?, keywords?, shortcut?, onSelect}]}`), `open` + `onOpenChange`, optional `hotkey` (registers Ctrl/⌘ + K), `placeholder`, `label`.

### Rules
- HIG `search-fields.md`: placeholder says what can be searched, results update immediately, results are simple and grouped. HIG `keyboards.md`: respect standard shortcuts.
- An accelerator, not the only path: everything here is reachable from regular navigation.
- Empty results repeat the query and suggest what to try.

---

## ContextMenu

Commands for an item, opened by right-click, long-press, Shift+F10 or the Menu key.

**Consumer provides:** `children` (the target area), `items` (as in `DropdownMenu`), `label`.

### Rules
- HIG `context-menus.md`: prioritize relevant items, keep it short, "Always make context menu items available in the main interface, too", hide unavailable items rather than dimming, one submenu level at most, no keyboard shortcuts in context menus, warn before destructive items.
- Long-press (500ms) opens it on touch.

---

## Dialog

A modal window for a short, focused task that needs the person's full attention.

**Consumer provides:** `open`, `onClose`, `title`, optional `description`, `children`, `footer` (buttons), `size` (`sm`, `md`, `lg`). `inline` only for documentation.

### Rules
- HIG `modality.md`: present modally only with a clear benefit; keep the task simple, short and streamlined; always give an obvious way to dismiss; confirm before closing if data would be lost.
- Built on native `<dialog>`: focus is trapped, Esc closes, focus returns to the trigger.
- Footer: Cancel then the primary action (one `primary`). On phones the buttons share the width.
- Long or multi-step flows get their own page instead.

---

## Direction

Sets the reading direction (`ltr` / `rtl`) for a subtree, for Arabic content such as Islamic studies material or international pages.

**Consumer provides:** `dir`, optional `lang`, `children`.

### Rules
- HIG `right-to-left.md`: align text with the interface direction; flip controls that show reading order (chevrons, back, breadcrumbs); don't flip icons that point at a real direction; never reverse the digits of a number; don't flip photos or artwork.
- Components use logical CSS (`inline-start`), so most layouts mirror automatically; set `dir` on `<html>` for whole pages.

---

## Drawer

A bottom sheet with a grabber and two heights (medium and large) that people can drag or tap to resize, or swipe down to dismiss.

**Consumer provides:** `open`, `onClose`, `title`, `children`, optional `detent` + `onDetentChange` or `defaultDetent` (`medium` | `large`), `doneLabel`.

### Rules
- HIG `sheets.md`: "Include a grabber in a resizable sheet", "Support swiping to dismiss a sheet", and "consider supporting the medium detent to allow progressive disclosure".
- The grabber is also a button (tap toggles height) so it works without dragging.
- A visible "Selesai" button always closes it. Centered dialog-style from 768px.

---

## DropdownMenu

A button that opens a list of commands.

**Consumer provides:** `trigger` (a `Button`), `items` (`{label, icon?, shortcut?, onSelect, destructive?, disabled?}`, `{separator: true}`, `{heading}`), `label`, optional `align="end"`.

### Rules
- HIG `menus.md`: clear, succinct labels; show unavailable items as dimmed; append "…" when more input is needed; represent common actions consistently; use icons sparingly; list frequent items first; group related items.
- Keyboard: ArrowDown opens, arrows move, typing jumps, Enter selects, Esc closes and returns focus.
- Destructive items go last, in red, after a separator.

---

## Empty

What a list or page shows when it has nothing yet, with the next step.

**Consumer provides:** `title`, `children` (one sentence of context), optional `icon`, `action` (usually one `Button`), `titleAs`.

### Rules
- Invite the next action rather than state the obvious ("Belum ada pengajuan" + "Buat pengajuan").
- For a search with no results, say what was searched and offer to clear filters.
- One illustration-weight icon in `accent-soft`; no mascots or stock art.

---

## FAB

A floating button for the single most important creation action on a mobile screen.

**Consumer provides:** `icon` (default `plus`), `label` (required when icon-only), or `children` for the extended version; `onClick`; `aboveTabBar` when the screen has a `TabBar`; `static` only in documentation.

### Rules
- Apple's guidelines define no floating action button. On ITK screens it is allowed for one creation action on mobile (Tulis pesan, Buat pengajuan) and must also exist in the page's normal toolbar or header for keyboard and desktop users (HIG `toolbars.md`: actions belong with the view they affect).
- One per screen. Never for navigation, never for destructive actions.
- Hide it at ≥1024px and show the same action as a `primary` button in the `Header`.
- 56px circle on `accent`, `shadow-3`, sits above safe-area inset and the tab bar.

---

## Field

Wraps any single control with a label, hint and error, and wires the ids and `aria-describedby` for you.

**Consumer provides:** `label`, one child control (`Input`, a native `<select>`, a custom control that accepts `id`), optional `hint`, `error`, `required`.

### Rules
- Errors appear under the control with an icon and say how to fix it (HIG `entering-data.md`: "Dynamically validate field values").
- Required fields are marked; optional ones may say "(opsional)" in the label instead when most fields are required.
- Stack fields at `space-5`; keep tab order the same as visual order (HIG `text-fields.md`).

---

## FullscreenMenu

The mobile site menu: a full-screen sheet of large links.

**Consumer provides:** `open`, `onClose`, `links` (`{label, href, description?, current?}`), optional `logo`, `footer` (login, language), `label`.

### Rules
- HIG `modality.md`: "Always give people an obvious way to dismiss a modal view." The close button sits where the menu button was; Esc also closes.
- Built on the native dialog: focus is trapped inside and returns to the menu button.
- Four to seven top-level links; deeper levels open on their own pages, not nested here.

---

## Haptics

`ITK.haptics`: short vibration patterns that complement on-screen feedback on supported phones.

**Consumer provides:** calls to `ITK.haptics.selection()`, `.impact()`, `.success()`, `.warning()`, `.error()`; `ITK.haptics.enabled = false` to turn them off (wire this to a user setting); `.supported()` to check.

### Rules
- HIG `playing-haptics.md`: use patterns according to their meaning, consistently, "to complement other feedback", avoid overuse, prefer short haptics for discrete events, and "Make haptics optional".
- Built on the Vibration API: Android browsers support it; iOS Safari and desktops do not, so haptics are never the only feedback.
- Used by `WheelPicker`, `Keypad`, `PinPad` and `QRScanner`.

---

## Header

The in-app navigation bar: back, title and up to two actions. For the public website, use `SiteHeader`.

**Consumer provides:** `title`, optional `backHref` or `onBack` + `backLabel` (the previous screen's name), `actions` (ghost icon buttons), `largeTitle` (title below the bar on top-level screens), `children` (search field under a large title).

### Rules
- HIG `toolbars.md`: "Provide a useful title for each window", "Use the standard Back and Close buttons", "Choose items deliberately to avoid overcrowding". Two trailing actions at most; the rest go in a `DropdownMenu`.
- The back label names where you are going ("Jadwal"), falling back to "Kembali".
- Large titles on top-level screens, inline titles on detail screens.

---

## HoverCard

A preview of a linked person or item, shown when hovering or focusing the link.

**Consumer provides:** one child (a link), `content`, optional `side`.

### Rules
- The link must work on its own: the card is a convenience for pointer users, and touch users go straight to the page.
- Opens after 500ms, stays while the pointer is over it, closes on Esc.
- Read-only content. Actions belong on the destination page.

---

## Icon

The system's stroked icon set: 24px grid, 1.75px stroke, round caps and joins, `currentColor`.

**Consumer provides:** `name` (one of the names shown), optional `className`. Icons are `aria-hidden`; the control or text beside them carries the meaning.

### Rules
- HIG `icons.md`: icons in one interface share one visual language and match the weight of adjacent text. Use 20px beside 15–17px text, 24px in tab bars, 16px inside badges and chips.
- Never the only label: icon-only buttons pass `label`.
- Need a glyph that isn't here? Use Lucide at stroke 1.75 (the closest match), then add it to `Icon.paths` so the set stays one family.
- In RTL, icons that show reading direction (chevrons, back) flip; icons that point at a real direction do not (HIG `right-to-left.md`).

---

## Input

The bare text input, styled, with no label or messages.

**Consumer provides:** any native `<input>` prop, `invalid` (sets `aria-invalid` and the error border). Must be named: wrap it in `Field`, or pass `aria-label` when a visible label already exists nearby (a search bar).

### Rules
- Use `TextField` or `Field` in forms; use `Input` alone only where layout already labels it.
- 16px text so iOS does not zoom; 44px height.
- Choose `type`, `inputMode` and `autoComplete` for the data (HIG `virtual-keyboards.md`: "Choose a keyboard that matches the type of content people are editing").

---

## InputAccessoryBar

A slim toolbar that sits on top of the on-screen keyboard with Previous / Next field, Done, and optional suggestions.

**Consumer provides:** `onPrev`, `onNext`, `onDone`, optional `children` (suggestion chips), `floating` (pins it above the keyboard using `visualViewport`), `doneLabel`.

### Rules
- HIG `virtual-keyboards.md`: "Place custom controls above the keyboard thoughtfully" and "Avoid duplicating system-provided keyboard features". iOS Safari already shows its own arrows on many forms; use this only where it adds something (suggestions, a numeric field with no Return key).
- Buttons keep focus in the field (they don't steal it on press).

---

## InputGroup

A text input with fixed text, an icon or a small button attached inside its border.

**Consumer provides:** `label`, `prefix` and/or `suffix` (a string, an icon name, or an element such as a `Button size="sm"`), optional `hint`, `error`, native input props.

### Rules
- Use add-ons for parts people must not type: a domain, a currency, a unit.
- A suffix button acts on this field only (Cek, Salin). Form submission stays a separate primary button.
- The whole group shows the focus ring and the error border.

---

## InputOTP

One-time code entry, one box per digit, with paste and SMS autofill support.

**Consumer provides:** `length` (default 6), `value` + `onChange` or `defaultValue`, `onComplete(code)`, optional `label`, `hint`, `error`.

### Rules
- The first box has `autocomplete="one-time-code"`, so iOS and Android offer the code from SMS (HIG `entering-data.md`: "Get information from the system whenever possible").
- Pasting a full code fills every box; Backspace moves back; arrow keys move between boxes.
- Submit automatically on completion, but keep a visible "Verifikasi" button and a "Kirim ulang kode" link with a countdown.

---

## Item

A row in a list: leading icon or avatar, title, subtitle, and a trailing value, control or chevron. `ItemGroup` wraps rows in an inset grouped list.

**Consumer provides:** `title`, optional `subtitle`, `leading` (icon name or element), `trailing` (text, `Badge`, `Switch`), `href` or `onClick` (row becomes the action and shows a chevron), `current`. `ItemGroup`: `title`, `footer`, `children`.

### Rules
- HIG `lists-and-tables.md`: prefer lists for text, keep item text succinct, and give feedback when a row is selected.
- HIG `toggles.md`: a `Switch` belongs in a list row like this one.
- One action per row: a linked row holds no other buttons.
- Rows are at least 52px; dividers are inset hairlines.

---

## Kbd

Shows a keyboard key or shortcut in running text or menus.

**Consumer provides:** `keys` (array, rendered in order) or `children` for one key.

### Rules
- HIG `keyboards.md`: list modifier keys in the correct order and respect standard shortcuts. On Mac show ⌘, elsewhere Ctrl.
- Use only where a keyboard is likely; never as the only way to discover a command.

---

## KeyboardScrollView

A scroll container that keeps the focused field visible above the on-screen keyboard.

**Consumer provides:** `children` (a form), optional `maxHeight`.

### Rules
- HIG `virtual-keyboards.md`: "Use the keyboard layout guide to make the keyboard feel like an integrated part of your interface." It reads `visualViewport`, pads the bottom by the keyboard height and centers the focused field.
- Wrap long mobile forms (registration, biodata). Not needed on desktop, where it does nothing.

---

## Keypad

A large on-screen number pad.

**Consumer provides:** `onKey(digit)`, `onDelete`, optional `extraKey` (`{value, label, content}` for the bottom-left key), `disabled`, `label`.

### Rules
- HIG `virtual-keyboards.md`: "Avoid duplicating system-provided keyboard features." For ordinary number fields use `inputMode="numeric"` on an `Input`. Keypad is for screens built around digits: PIN, kiosk presensi, calculators.
- 64px keys with a light haptic tick where supported; physical keyboards also work through the parent (see `PinPad`).

---

## Label

The visible name of a form control.

**Consumer provides:** `children`, `htmlFor` (the control's id), optional `required` (adds a red asterisk, hidden from screen readers since the control carries `required`).

### Rules
- Every control has a visible label above it. Placeholder text is a hint, not a label (HIG `text-fields.md`: "Show a hint in a text field to help communicate its purpose").
- Sentence case, no trailing colon.
- Prefer `Field` or `TextField`, which wire label, hint and error together.

---

## Marker

Highlights a phrase inside text: a key deadline, or the matched term in search results.

**Consumer provides:** `children`, optional `tone` (`gold` for emphasis, `blue` for search matches).

### Rules
- Renders `<mark>`, so screen readers can announce it. Do not highlight more than one phrase per paragraph.
- Not a status indicator; use `Badge` or `Alert` for that.

---

## Menubar

A horizontal bar of menus for desktop tools (document editors, admin consoles).

**Consumer provides:** `menus` (`{label, items}` with items as in `DropdownMenu`), `label`.

### Rules
- HIG `the-menu-bar.md` and `menus.md`: every command is reachable from a menu, standard shortcuts are shown, unavailable items are dimmed, items needing more input end with "…", and related items are grouped.
- Arrow keys move across menus and through items; typing jumps to an item.
- Desktop tools only. Public pages use `NavigationMenu`; mobile uses `Header` + `DropdownMenu`.

---

## Message

A chat message: avatar, author, bubble, attachments, time and delivery status.

**Consumer provides:** `children` (text), `from` (`me` | `them`), `author`, optional `avatar`, `role`, `time`, `status` (Terkirim, Dibaca), `grouped` (consecutive message from the same author), `attachments`.

### Rules
- Group consecutive messages from one author within a few minutes: hide the repeated avatar and name.
- Status and time are text, never only ticks or colors.
- Each message is an `article` labelled with author and time for screen-reader navigation.

---

## MessageScroller

The scrolling conversation log: sticks to the newest message and offers a "pesan baru" button when the reader has scrolled up.

**Consumer provides:** `children` (`Message`s, oldest first), `height`, `label`.

### Rules
- HIG `scroll-views.md`: "In some cases, scroll automatically to help people find their place", but never yank the view while someone is reading older messages.
- `role="log"` announces new messages politely.
- Load older messages at the top with a `Spinner` row, keeping the scroll position.

---

## NativeSelect

The platform's own `<select>` with the system's field chrome.

**Consumer provides:** `label`, `options` (strings or `{value, label, disabled}`), optional `placeholder`, `hint`, `error`, native props.

### Rules
- The default choice on mobile: it opens the phone's own picker, works with every screen reader, and needs no JavaScript (HIG `pickers.md`: use pickers for medium-to-long lists of predictable values).
- Use `Select` when options need descriptions or custom rendering; `Combobox` when people need to search.
- Fewer than four options: use `RadioGroup` so every choice is visible.

---

## NavigationMenu

The website's top navigation with drop-down panels of grouped links (desktop, ≥1024px).

**Consumer provides:** `items`: plain links `{label, href, current?}` or sections `{label, sections: [{title, links: [{label, href, description?}]}]}`; `label`.

### Rules
- These are disclosure buttons revealing links, not ARIA menus, so normal Tab navigation works.
- Opens on click or tap; on hover only with a precise pointer, with a 250ms grace period to cross into the panel. Esc and clicking outside close it.
- HIG `menus.md`: group logically related items, put important ones first, keep it short. Two levels at most.
- Below 1024px, the same links go into `FullscreenMenu` or the `SiteHeader` sheet.

---

## Ornament

The brand's Dayak frieze, repeated as a decorative band or a short centred divider.

**Consumer provides:** `src` (one of the `Ornaments` frieze PNGs), optional `height` in px (default 20), `align="center"` for a 240px divider.

### Rules
- Decorative only (`role="presentation"`), never conveys meaning.
- At most one ornament band per screen: under the hero, above the footer, or between two landing sections. Never inside forms, tables or app screens such as SIAKAD.
- Blue frieze on light grounds, white frieze on `itk-blue` or `surface-inverse`, gold frieze on `blue-950`.
- Do not recolour, stretch vertically, or crop a motif mid-curl.

---

## PageIndicator

Dots showing the current page in a paged sequence, usually under a `Carousel`.

**Consumer provides:** `count`, `current` (0-based), `onChange(index)`, `label`.

### Rules
- HIG `page-controls.md`: represent movement in an ordered list of pages, center it under the content, don't show too many pages, and "avoid coloring indicator images". Up to seven dots; beyond that use a "3 / 12" counter.
- Each dot is a 24px target labelled "Halaman 2 dari 5"; it never replaces Previous / Next buttons.

---

## Pagination

Moves through pages of results: previous, next and numbered pages.

**Consumer provides:** `total`, `page` + `onChange(n)` or `defaultPage`, optional `href(n)` (renders links, best with Inertia `Link` for server pagination), `siblings` (default 1).

### Rules
- Below 520px it collapses to Previous · 4 / 12 · Next so targets stay 44px.
- The current page is marked `aria-current="page"`; disabled ends stay visible.
- Prefer "Muat lebih banyak" in feeds and infinite lists; use pagination where people return to a position (search results, data tables).

---

## PinPad

PIN entry: title, filled dots and a keypad, with error feedback.

**Consumer provides:** `length` (default 6), `onComplete(pin)`, optional `title`, `error` (message; shakes the dots and plays an error haptic), `extraKey`, `clearOnComplete`.

### Rules
- HIG `entering-data.md`: "Use a secure text-entry field when appropriate". Digits are never shown, only dots; screen readers hear "3 dari 6 digit".
- Physical keyboards type digits and Backspace directly.
- After an error, say what to do and how many tries remain; never lock silently.

---

## Popover

A small non-modal panel anchored to a control, for a little more information or a quick setting.

**Consumer provides:** `trigger` (a `Button`), `children`, optional `title`, `label`, `side` (`bottom` | `top`), `align` (`start` | `end`), `open` + `onOpenChange`.

### Rules
- HIG `popovers.md`: show a small amount of information or functionality, one popover at a time, never too big, never another view over it, save work when it closes, and "Avoid using a popover to show a warning".
- Opens on click, closes on Esc, outside click or the trigger; focus moves in and returns to the trigger.

---

## Progress

A bar showing how far a task has come, or that it is under way.

**Consumer provides:** `value` and `max` (omit `value` for indeterminate), `label`, optional `description`, `valueText` (what screen readers hear), `showValue`.

### Rules
- HIG `progress-indicators.md`: be as accurate as possible, keep it moving, switch from indeterminate to determinate when you can, display it in a consistent location, and let people halt long tasks.
- Label says what is progressing ("Kelengkapan berkas"), the description says the numbers in words.

---

## QRScanner

Scans a QR code with the camera (presensi kuliah, event check-in), with manual entry as a fallback.

**Consumer provides:** `onResult(text)`, optional `prompt`, `startLabel`.

### Rules
- HIG `privacy.md`: ask for camera access in context, only when people press "Buka kamera", never on page load. Explain what the camera is for.
- Uses the browser's `BarcodeDetector` where available (Chrome on Android). Safari and Firefox fall back to typing the code, which is always offered.
- A success haptic plays on supported phones, and the result is also shown on screen.

---

## Questionnaire

A one-question-per-step survey with progress, back/next and validation (EDOM, kepuasan layanan, tracer study).

**Consumer provides:** `questions` (`{id, type: 'single'|'multiple'|'scale'|'text', title, options?, required?, description?, scaleLabels?, maxLength?}`), `onSubmit(answers)`, optional `defaultAnswers`, `submitLabel`, `doneTitle`, `doneText`.

### Rules
- HIG `entering-data.md`: offer choices instead of text entry, make required data clear before people proceed, validate dynamically.
- One question per screen on phones; the heading takes focus on each step so screen readers announce it.
- Show progress in words ("Pertanyaan 2 dari 3"); answers persist when going back.

---

## RadioGroup

A visible set of mutually exclusive options.

**Consumer provides:** `label` (the question, rendered as `<legend>`), `options` (`{value, label, description?, disabled?}`), `value` + `onChange` or `defaultValue`, optional `orientation="horizontal"`, `hint`, `error`, `required`.

### Rules
- Use for two to six options people should compare (HIG `entering-data.md`: "offer choices instead of requiring text entry").
- Order options logically and do not preselect when the choice matters (no default jalur).
- Each row is a full 44px target with its description beneath.

---

## Resizable

Two panes with a draggable divider, for list–detail layouts on wide screens.

**Consumer provides:** two children, optional `direction` (`horizontal` | `vertical`), `size` + `onSizeChange` or `defaultSize` (percent), `min`, `max`, `height`, `label`.

### Rules
- HIG `split-views.md`: prefer split views in regular (wide) environments, account for narrow widths, set reasonable minimum and maximum pane sizes, prefer the thin divider style. Below 768px the panes stack and the handle disappears.
- The divider is a focusable separator: arrow keys resize by 5%, Home/End jump to limits.

---

## Screen

The page shell for app screens: header, scrolling main content, and an optional bottom bar, honoring safe areas.

**Consumer provides:** `header` (`Header`), `children` (main content), optional `bottom` (`TabBar` or `BottomBar`), `mainId` (skip-link target, default `konten`).

### Rules
- HIG `layout.md`: respect safe areas; layout follows available space, not device names. Content caps at 1280px and pads 16 / 24 / 32px by breakpoint.
- Use it as the Inertia persistent layout so the header and tab bar don't remount between visits.
- Include a "Lewati ke konten" skip link before the header pointing at `mainId`.

---

## ScrollArea

A bounded scrolling region with thin scrollbars and edge shadows that show there is more.

**Consumer provides:** `children`, `label` (names the region for keyboard and screen-reader users), optional `maxHeight`, `orientation="horizontal"`.

### Rules
- HIG `scroll-views.md`: make it apparent when content is scrollable; "Avoid putting a scroll view inside another scroll view with the same orientation".
- Focusable so keyboard users can scroll it; uses the browser's native scrolling and gestures.
- Prefer letting the page scroll; use this for side panels, chat logs and pickers.

---

## Select

A pop-up button that opens a list of options, for choices that need a description or richer rows than a native select allows.

**Consumer provides:** `label`, `options` (`{value, label, description?, disabled?}`), `value` + `onChange` or `defaultValue`, optional `placeholder`, `hint`, `error`, `required`.

### Rules
- HIG `menus.md` and `pickers.md`: show the current value in the button; mark the selected option with a checkmark; keep options short and ordered predictably.
- Keyboard: arrows, Home/End, typeahead, Enter/Space to choose, Esc to close, focus returns to the button.
- Prefer `NativeSelect` on mobile-heavy forms; prefer `Combobox` beyond ~15 options.

---

## Separator

A hairline between groups of content, optionally with a label.

**Consumer provides:** optional `orientation="vertical"`, `label`.

### Rules
- Use whitespace first; reach for a line only when groups would otherwise merge.
- `border` color: decorative, so it has no contrast floor.
- For the brand's ornament band between landing sections, use `Ornament`.

---

## Sheet

A panel that slides in from the side (or bottom) for supplementary tasks such as filters or details, keeping the page in view.

**Consumer provides:** `open`, `onClose`, `title`, `children`, optional `footer`, `side` (`right` default, `left`, `bottom`).

### Rules
- HIG `sheets.md`: display one sheet at a time, "Provide an alternative to the Done button" (close button and Esc), present it at a reasonable default size, and consider alternatives for complex or prolonged flows.
- Right side on desktop, bottom on phones (`side="bottom"` or use `Drawer` for resizable).

---

## Sidebar

The left navigation column for the app on tablet and desktop, collapsible to an icon rail.

**Consumer provides:** `groups` (`{title?, items: [{label, href, icon, current?, badge?}]}`), optional `header` (logo), `collapsible`, `collapsed` + `onCollapsedChange`, `label`.

### Rules
- HIG `sidebars.md`: show no more than two levels, title groups succinctly, use familiar symbols, let people hide it, and "Avoid putting critical information or actions at the bottom of a sidebar". HIG also says "Consider using a tab bar first": below 1024px use `TabBar`.
- The current page gets `accent-soft`, bold text and the gold edge mark.
- Collapsed, every link keeps its accessible name and a tooltip.

---

## SignaturePad

Draws a signature with finger, stylus or mouse, with an option to type the name instead.

**Consumer provides:** `onChange(value)` (a PNG data URL, or `{typed}` when typed, or `null`), optional `label`.

### Rules
- Always offer "Ketik nama saja": drawing is not possible for every person or input method (HIG `accessibility.md`).
- Undo removes the last stroke; Clear removes all; both are disabled when empty.
- The stroke uses `ink`, so it shows in both themes; export on a white background if printing.

---

## SiteHeader

The top bar of every page: lambang + wordmark, primary navigation, and a menu sheet below 1024px.

**Consumer provides:** `logoSrc` (the colour `itk-lambang-branding.png`), `logoSrcDark` (the white version, shown when `data-theme="dark"`), `links` (`{label, href, current?}`, 4–6 items), optional `actions` (search, login), `homeHref`.

### Rules
- The logo appears here once per page and nowhere else in app chrome.
- 360–1023px: logo, actions, and a 44px menu button that opens the links as a full-width list. ≥1024px: links inline.
- Current page: `ink` at 600 weight with a 3px `gold` underline (the brand's one signature use of gold in UI chrome).
- Make it sticky (`position: sticky; top: 0; z-index: var(--z-sticky)`) on long pages and add `shadow-2` after scroll.

---

## Skeleton

Placeholder shapes in the layout of content that is still loading.

**Consumer provides:** `width`, `height`, or `text` (a line of text height), `circle` + `size`.

### Rules
- HIG `loading.md`: "Show something as soon as possible" and let people keep working. Mirror the real layout so nothing jumps when data arrives.
- Hidden from assistive tech; announce loading once with the region's `aria-busy="true"`.
- The shimmer slows under reduced motion.

---

## Slider

Picks a value from a continuous or stepped range by dragging.

**Consumer provides:** `label`, `min`, `max`, `step`, `value` + `onChange` or `defaultValue`, optional `ticks` (values to label), `format(value)`, `showValue` (default on).

### Rules
- HIG `sliders.md`: introduce it with a label, give live feedback as the value changes, use tick marks (with labels) for accuracy, and consider pairing with a text field when exact values matter.
- `aria-valuetext` uses `format`, so screen readers hear "20 SKS", not "20".
- Thumb is 26px inside a 44px tall hit area.

---

## Spinner

An activity indicator for short, indeterminate waits.

**Consumer provides:** optional `size` (px, default 20), `label` (screen-reader text, default "Memuat").

### Rules
- HIG `progress-indicators.md`: "When possible, use a determinate progress indicator"; prefer a spinner "when space is constrained" or for background operations. Uploads and multi-step jobs use `Progress`.
- HIG `loading.md`: "Show something as soon as possible." For whole sections, show `Skeleton` layouts instead of a lone spinner.
- Pair with a short text label when it stands alone.

---

## Switch

Turns a setting on or off with immediate effect.

**Consumer provides:** `label`, native checkbox props (`checked`, `onChange`). Rendered as `role="switch"`.

### Rules
- Only for settings that apply at once; never inside a form that needs "Simpan".
- Label names the setting, not the state ("Notifikasi jadwal", not "Aktifkan").

---

## TabBar

Bottom navigation between the top-level sections of the mobile app.

**Consumer provides:** `items` (`{label, icon, href, current?, badge?}`, 3–5), optional `label`. `static` only for documentation.

### Rules
- HIG `tab-bars.md`: "Use a tab bar to support navigation, not to provide actions"; keep it visible across sections; avoid overflow tabs; "Don't disable or hide tab bar buttons"; include labels; use a badge only for critical information.
- Below 1024px only. At ≥1024px the same sections live in `Sidebar` or `SiteHeader`.
- Selected tab: `accent-text`, heavier stroke and weight; labels are always shown.

---

## Table

Tabular data with a caption, sticky-looking header, tabular numerals and horizontal scroll on narrow screens.

**Consumer provides:** `columns` (`{key, header, numeric?, render?}`), `rows` (objects, optional `id`), `caption` (required; it also names the scroll region), optional `rowHeader` (first column becomes `<th scope="row">`).

### Rules
- Below 640px the table scrolls inside its frame (the frame is focusable for keyboard scrolling). If people mostly read one row at a time on phones, render a list of `Card`s instead.
- Right-align numbers (`numeric`) and keep them in tabular figures.
- Status columns use `Badge` with `icon`.
- Row hover is `accent-soft`; selection, if added, uses the same ground plus a checkbox.

---

## Tabs

Switches between peer views of the same subject without leaving the page.

**Consumer provides:** `items` (`{value, label, content}`), `label` (the tablist's accessible name), optional `value` + `onChange` (controlled) or `defaultValue`.

### Rules
- Tabs navigate, they never act: no "Simpan" tab.
- 2–6 tabs, one or two words each. The list scrolls sideways at 360px rather than wrapping.
- Arrow keys move between tabs, Home/End jump; Tab moves into the panel.
- Selected tab: `ink` text at 600 weight plus a 3px `accent` bar. Weight and the bar carry state, not color alone.
- For site-level sections use `SiteHeader` navigation, not tabs.

---

## Text

Sets text in one of the system's type styles with the right semantic element.

**Consumer provides:** `children`, `variant` (any style in Typography: `display-xl` … `label`, `code`; default `body`), optional `as` (override the element), `tone` (`muted`, `accent`, `brand`).

### Rules
- Pick the variant for hierarchy and `as` for document outline: a card title can look like `title-3` and still be an `h2`.
- `tone="brand"` (`itk-blue`) only on `title-2` and larger; it is 3.95:1 on white.
- HIG `typography.md`: keep to the scale; don't invent in-between sizes, and avoid light weights for small text.

---

## TextField

A labelled single-line input with optional hint and error message.

**Consumer provides:** `label` (always visible, never placeholder-only), optional `hint`, `error` (string; sets `aria-invalid` and links the message via `aria-describedby`), `required`, plus any native `<input>` prop (`type`, `inputMode`, `autoComplete`, `name`, `value`/`onChange` for Inertia `useForm`).

### Rules
- Text is 16px so iOS Safari never zooms on focus.
- Pick the right keyboard: `inputMode="numeric"` for NIM/NIP, `type="email"` with `autoComplete="email"`.
- Errors say what is wrong and how to fix it ("NIM harus 8 digit. Kurang 1 digit."), shown under the field with an icon, never by red border alone.
- Validate on blur and on submit, not on every keystroke.
- Placeholder is an example, not a label; it is `ink-muted` and disappears on input.

---

## Textarea

Multi-line text entry that grows with its content.

**Consumer provides:** `label`, optional `hint`, `error`, `maxLength` + `showCount` (live character count), `autoGrow` (default on), `rows` (starting height, default 3), native props.

### Rules
- HIG `text-fields.md`: "match the size of a text field to the quantity of anticipated text". Start at 3 rows for a paragraph, more for an essay.
- Show the count only when there is a real limit, and keep it polite to screen readers.
- Never use it for a single line (names, NIM).

---

## Toast

A brief, non-blocking confirmation that appears at the screen edge and leaves on its own.

**Consumer provides:** mount one `<Toaster />` near the app root, then call `ITK.toast({ message, tone?, action?: {label, onClick}, duration? })` or `ITK.toast('Pesan')`. `Toast` renders a single toast for static layouts.

### Rules
- HIG `feedback.md`: "Consider integrating status feedback into your interface"; HIG `alerts.md`: avoid alerts for common, undoable actions. A toast with "Urungkan" replaces a confirmation dialog.
- Never for errors that block the task: those use `Alert` beside the problem or `AlertDialog`.
- Auto-dismiss after 5 s, paused while hovered or focused (WCAG 2.2.1); at most three on screen; announced politely.
- One short sentence, no title, at most one action.

---

## Toaster

The live region that hosts toasts. Mount it once per app.

**Consumer provides:** optional `position="top"` (default bottom; bottom-right from 768px).

### Rules
- Place it once in the Inertia layout, after the page content, so every page can call `ITK.toast()`.
- It sits above the safe area and above `TabBar`; on desktop it hugs the bottom-right corner.
- See `Toast` for content rules.

---

## Toggle

A button that stays pressed or unpressed (`aria-pressed`), for a two-state option outside a settings list.

**Consumer provides:** `children` (label) or `icon` + `label` for icon-only, `pressed` + `onPressedChange` (controlled) or `defaultPressed`, `disabled`.

### Rules
- HIG `toggles.md`: "Outside of a list, use a button that behaves like a toggle, not a switch." Use `Switch` only inside an `Item` row or a settings list.
- The pressed state changes fill (`accent-soft`), border and weight, never color alone.
- The label names the thing, not the action: "Ikuti pengumuman", not "Klik untuk mengikuti".

---

## ToggleGroup

A segmented control: two to five closely related, mutually exclusive choices that change one view.

**Consumer provides:** `options` (`{value, label}` or `{value, icon, ariaLabel}`), `label` (group name), `value` + `onChange` or `defaultValue`, optional `block` (fills the width).

### Rules
- HIG `segmented-controls.md`: use it for "closely related choices that affect an object, state, or view"; limit the number of segments; keep segments the same size; use nouns, and text or icons but not a mix.
- For switching whole pages, use `Tabs`; for filtering, a `Chip` row.
- Keyboard: arrow keys move and select, matching a radio group.

---

## Tooltip

A short help label shown on hover or keyboard focus, for icon-only controls on desktop.

**Consumer provides:** one child (the control), `content` (a few words), optional `side`, `delay` (ms, default 400).

### Rules
- Supplementary only: touch screens have no hover, so the control's own `label` must already carry the meaning (HIG `accessibility.md`: nothing relies on a single input method).
- Shows on focus as well as hover, stays while hovered, closes on Esc (WCAG 1.4.13).
- Never put links, actions or essential instructions in a tooltip; use `Popover`.

---

## Trace

A vertical timeline of the steps in a process, each with status, time and optional detail.

**Consumer provides:** `steps` (`{title, status: 'done'|'current'|'pending'|'error', time?, detail?, collapsible?, detailTitle?}`), `label`.

### Rules
- Use it for anything people wait on: admission, surat aktif kuliah, legalisir, payments. HIG `progress-indicators.md`: "If it's helpful, display a description that provides additional context for the task."
- State is carried by the node shape (check, ring, cross), a hidden status word and the line, not by color alone.
- Show expected duration on the current step; show the date on future steps when known.

---

## WheelPicker

Scrollable wheels for picking values such as time, in the style of a mobile picker.

**Consumer provides:** `columns` (`{label, options, value, onChange}`), `label` (group name).

### Rules
- HIG `pickers.md`: for medium-to-long lists with predictable, ordered values; and "consider providing less granularity when specifying minutes" (steps of 5 or 10).
- Each column is a listbox: arrows and PageUp/PageDown move, scrolling snaps to the band, a light haptic tick plays on supported phones.
- On desktop forms prefer `<input type="time">` or `Select`.

---

## Widget

A glanceable tile on a dashboard: one number or fact, a caption, and a link into the detail.

**Consumer provides:** `title`, optional `icon`, `value`, `unit`, `caption`, `children`, `updated`, `size` (`small` 170×170, `medium`), `brand` (navy tile for one featured widget), `href`.

### Rules
- HIG `widgets.md`: choose simple ideas tied to the main purpose, prefer information that changes during the day, balance density, and "use brand elements thoughtfully". One `brand` widget per dashboard at most.
- The whole widget is one link; it never contains buttons.
- Keep it current; show when it was updated.
