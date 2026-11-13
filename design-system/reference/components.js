/* Reference only: React implementations of the ITK components (behaviour, ARIA, keyboard handling). Port to Blade + Alpine. */
/* @ds-bundle: {"format":4,"namespace":"ITK","components":[{"name":"Button"},{"name":"ButtonGroup"},{"name":"Toggle"},{"name":"ToggleGroup"},{"name":"FAB"},{"name":"Text"},{"name":"Label"},{"name":"Field"},{"name":"Input"},{"name":"TextField"},{"name":"Textarea"},{"name":"InputGroup"},{"name":"InputOTP"},{"name":"AmountInput"},{"name":"NativeSelect"},{"name":"Select"},{"name":"Combobox"},{"name":"Checkbox"},{"name":"RadioGroup"},{"name":"Switch"},{"name":"Slider"},{"name":"Chip"},{"name":"Badge"},{"name":"Alert"},{"name":"Toast"},{"name":"Toaster"},{"name":"Spinner"},{"name":"Progress"},{"name":"Skeleton"},{"name":"Empty"},{"name":"Card"},{"name":"Item"},{"name":"ItemGroup"},{"name":"Avatar"},{"name":"Kbd"},{"name":"Marker"},{"name":"Separator"},{"name":"AspectRatio"},{"name":"CodeBlock"},{"name":"Table"},{"name":"Chart"},{"name":"Widget"},{"name":"Trace"},{"name":"Calendar"},{"name":"WheelPicker"},{"name":"Collapsible"},{"name":"Accordion"},{"name":"Tabs"},{"name":"Breadcrumb"},{"name":"Pagination"},{"name":"PageIndicator"},{"name":"Carousel"},{"name":"SiteHeader"},{"name":"Header"},{"name":"NavigationMenu"},{"name":"Menubar"},{"name":"Sidebar"},{"name":"TabBar"},{"name":"BottomBar"},{"name":"FullscreenMenu"},{"name":"Dialog"},{"name":"AlertDialog"},{"name":"AlertSheet"},{"name":"Sheet"},{"name":"Drawer"},{"name":"Popover"},{"name":"Tooltip"},{"name":"HoverCard"},{"name":"DropdownMenu"},{"name":"ContextMenu"},{"name":"Command"},{"name":"ScrollArea"},{"name":"Resizable"},{"name":"Screen"},{"name":"KeyboardScrollView"},{"name":"InputAccessoryBar"},{"name":"Direction"},{"name":"Bubble"},{"name":"Message"},{"name":"MessageScroller"},{"name":"Attachment"},{"name":"Keypad"},{"name":"PinPad"},{"name":"QRScanner"},{"name":"SignaturePad"},{"name":"Questionnaire"},{"name":"Ornament"},{"name":"Icon"}]} */
(function () {
  var React = window.React;
  var h = React.createElement;
  var useState = React.useState, useRef = React.useRef, useId = React.useId || function () { var r = useRef('itk-' + Math.random().toString(36).slice(2, 8)); return r.current; };
  function cx() { return Array.prototype.filter.call(arguments, Boolean).join(' '); }
  function omit(o, keys) { var r = {}; for (var k in o) if (keys.indexOf(k) < 0) r[k] = o[k]; return r; }

  var PATHS = {
    check: 'M5 12.5l4.5 4.5L19 7.5',
    info: 'M12 8h.01M11 12h1v5h1 M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18z',
    success: 'M8 12.5l2.8 2.8L16.5 9.5 M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18z',
    warning: 'M12 9v4.5M12 17h.01 M10.3 3.9L2.4 17.6A2 2 0 0 0 4.1 20.6h15.8a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z',
    danger: 'M12 7.5v5.5M12 16.5h.01 M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18z',
    x: 'M6 6l12 12M18 6L6 18',
    chevronDown: 'M6 9l6 6 6-6',
    chevronRight: 'M9 6l6 6-6 6',
    menu: 'M4 7h16M4 12h16M4 17h16',
    search: 'M11 18a7 7 0 1 0 0-14 7 7 0 0 0 0 14zM20 20l-4-4'
  };
  function Icon(props) {
    return h('svg', { className: cx('itk-icon', props.className), viewBox: '0 0 24 24', 'aria-hidden': 'true', focusable: 'false' },
      h('path', { d: Icon.paths[props.name] || '' }));
  }
  Icon.paths = PATHS;

  function Button(p) {
    var variant = p.variant || 'primary', size = p.size || 'md';
    var iconOnly = !!p.icon && !p.children;
    var rest = omit(p, ['variant', 'size', 'loading', 'icon', 'iconRight', 'className', 'children', 'label', 'as']);
    var Tag = p.as || (p.href ? 'a' : 'button');
    if (Tag === 'button' && !rest.type) rest.type = 'button';
    var busy = !!p.loading;
    return h(Tag, Object.assign(rest, {
      className: cx('itk-btn', 'itk-btn-' + variant, size !== 'md' && 'itk-btn-' + size, iconOnly && 'itk-btn-icon', p.className),
      'aria-busy': busy || undefined,
      'aria-label': iconOnly ? p.label : rest['aria-label'],
      disabled: Tag === 'button' ? (p.disabled || busy) : undefined
    }),
      busy ? h('span', { className: 'itk-spinner', 'aria-hidden': 'true' }) : (p.icon ? h(Icon, { name: p.icon }) : null),
      p.children,
      p.iconRight ? h(Icon, { name: p.iconRight }) : null);
  }

  function Field(p, control) {
    var id = p.id, hintId = id + '-hint', errId = id + '-err';
    return h('div', { className: cx('itk-field', p.className) },
      h('label', { className: 'itk-field-label', htmlFor: id }, p.label, p.required ? h('span', { className: 'itk-req', 'aria-hidden': 'true' }, '*') : null),
      p.hint ? h('span', { className: 'itk-field-hint', id: hintId }, p.hint) : null,
      control,
      p.error ? h('span', { className: 'itk-field-error', id: errId }, h(Icon, { name: 'danger' }), p.error) : null);
  }
  function describedBy(p, id) { return [p.hint && id + '-hint', p.error && id + '-err'].filter(Boolean).join(' ') || undefined; }

  function TextField(p) {
    var auto = useId(); var id = p.id || auto;
    var rest = omit(p, ['label', 'hint', 'error', 'className', 'id']);
    return Field(Object.assign({}, p, { id: id }), h('input', Object.assign({ type: 'text' }, rest, {
      id: id, className: 'itk-input', 'aria-invalid': p.error ? 'true' : undefined, 'aria-describedby': describedBy(p, id)
    })));
  }

  function Select(p) {
    var auto = useId(); var id = p.id || auto;
    var rest = omit(p, ['label', 'hint', 'error', 'className', 'id', 'options', 'placeholder']);
    var opts = (p.options || []).map(function (o) { o = typeof o === 'string' ? { value: o, label: o } : o; return h('option', { key: o.value, value: o.value, disabled: o.disabled }, o.label); });
    if (p.placeholder) opts.unshift(h('option', { key: '__ph', value: '', disabled: true }, p.placeholder));
    return Field(Object.assign({}, p, { id: id }), h('div', { className: 'itk-select-wrap' },
      h('select', Object.assign({ defaultValue: p.value === undefined && p.placeholder ? '' : undefined }, rest, {
        id: id, className: 'itk-select', 'aria-invalid': p.error ? 'true' : undefined, 'aria-describedby': describedBy(p, id)
      }), opts), h(Icon, { name: 'chevronDown' })));
  }

  function Checkbox(p) {
    var rest = omit(p, ['label', 'description', 'className']);
    return h('label', { className: cx('itk-check', p.className) },
      h('input', Object.assign({ type: 'checkbox' }, rest)),
      h('span', { className: 'itk-check-box', 'aria-hidden': 'true' }, h(Icon, { name: 'check' })),
      h('span', { className: 'itk-check-text' }, p.label, p.description ? h('small', null, p.description) : null));
  }

  function Switch(p) {
    var rest = omit(p, ['label', 'className']);
    return h('label', { className: cx('itk-switch', p.className) },
      h('input', Object.assign({ type: 'checkbox', role: 'switch' }, rest)),
      h('span', { className: 'itk-switch-track', 'aria-hidden': 'true' }),
      h('span', null, p.label));
  }

  var TONE_ICON = { info: 'info', success: 'success', warning: 'warning', danger: 'danger' };
  function Badge(p) {
    var tone = p.tone || 'neutral';
    return h('span', { className: cx('itk-badge', 'itk-badge-' + tone, p.className) },
      p.icon && TONE_ICON[tone] ? h(Icon, { name: TONE_ICON[tone] }) : null, p.children);
  }

  var TONE_WORD = { info: 'Info', success: 'Berhasil', warning: 'Perhatian', danger: 'Galat' };
  function Alert(p) {
    var tone = p.tone || 'info';
    var urgent = tone === 'danger' || tone === 'warning';
    return h('div', { className: cx('itk-alert', 'itk-alert-' + tone, p.className), role: urgent ? 'alert' : 'status' },
      h(Icon, { name: TONE_ICON[tone] }),
      h('div', null,
        h('span', { className: 'itk-sr' }, (p.toneLabel || TONE_WORD[tone]) + ': '),
        p.title ? h('strong', { className: 'itk-alert-title' }, p.title) : null,
        p.children),
      p.onDismiss ? h('button', { type: 'button', className: 'itk-alert-close', onClick: p.onDismiss, 'aria-label': p.dismissLabel || 'Tutup' }, h(Icon, { name: 'x' })) : h('span'));
  }

  function Card(p) {
    var Tag = p.href ? 'a' : (p.as || 'article');
    return h(Tag, { className: cx('itk-card', p.href && 'itk-card-interactive', p.className), href: p.href, style: p.href ? { textDecoration: 'none' } : undefined },
      p.media || null,
      p.eyebrow ? h('span', { className: 'itk-card-eyebrow' }, p.eyebrow) : null,
      p.title ? h(p.titleAs || 'h3', { className: 'itk-card-title' }, p.title) : null,
      p.children ? h('div', { className: 'itk-card-body' }, p.children) : null,
      p.footer ? h('div', { className: 'itk-card-footer' }, p.footer) : null);
  }

  function Tabs(p) {
    var items = p.items || [];
    var st = useState(p.defaultValue || (items[0] && items[0].value)); var cur = p.value !== undefined ? p.value : st[0];
    var base = useId(); var refs = useRef({});
    function set(v) { if (p.value === undefined) st[1](v); if (p.onChange) p.onChange(v); }
    function onKey(e, i) {
      var n = items.length, j = null;
      if (e.key === 'ArrowRight') j = (i + 1) % n; else if (e.key === 'ArrowLeft') j = (i - 1 + n) % n; else if (e.key === 'Home') j = 0; else if (e.key === 'End') j = n - 1;
      if (j !== null) { e.preventDefault(); set(items[j].value); var el = refs.current[items[j].value]; if (el) el.focus(); }
    }
    var active = items.filter(function (t) { return t.value === cur; })[0];
    return h('div', { className: cx('itk-tabs', p.className) },
      h('div', { className: 'itk-tablist', role: 'tablist', 'aria-label': p.label },
        items.map(function (t, i) {
          var sel = t.value === cur;
          return h('button', { key: t.value, type: 'button', role: 'tab', className: 'itk-tab', id: base + '-t-' + t.value, 'aria-selected': sel, 'aria-controls': base + '-p-' + t.value,
            tabIndex: sel ? 0 : -1, onClick: function () { set(t.value); }, onKeyDown: function (e) { onKey(e, i); }, ref: function (el) { refs.current[t.value] = el; } }, t.label);
        })),
      active ? h('div', { className: 'itk-tabpanel', role: 'tabpanel', id: base + '-p-' + active.value, 'aria-labelledby': base + '-t-' + active.value, tabIndex: 0 }, active.content) : null);
  }

  function Table(p) {
    var cols = p.columns || [];
    return h('div', { className: cx('itk-table-wrap', p.className), role: 'region', 'aria-label': p.caption, tabIndex: 0 },
      h('table', { className: 'itk-table' },
        p.caption ? h('caption', null, p.caption) : null,
        h('thead', null, h('tr', null, cols.map(function (c) { return h('th', { key: c.key, scope: 'col', className: c.numeric ? 'itk-num' : undefined }, c.header); }))),
        h('tbody', null, (p.rows || []).map(function (r, i) {
          return h('tr', { key: r.id || i }, cols.map(function (c, j) {
            var v = c.render ? c.render(r) : r[c.key];
            return h(j === 0 && p.rowHeader ? 'th' : 'td', { key: c.key, scope: j === 0 && p.rowHeader ? 'row' : undefined, className: c.numeric ? 'itk-num' : undefined }, v);
          }));
        }))));
  }

  function Breadcrumb(p) {
    var items = p.items || [];
    return h('nav', { className: cx('itk-crumb', p.className), 'aria-label': p.label || 'Breadcrumb' },
      h('ol', null, items.map(function (it, i) {
        var last = i === items.length - 1;
        return h('li', { key: i },
          last ? h('span', { 'aria-current': 'page' }, it.label) : h('a', { href: it.href }, it.label),
          last ? null : h(Icon, { name: 'chevronRight' }));
      })));
  }

  function SiteHeader(p) {
    var st = useState(false), open = st[0];
    var links = p.links || [];
    var sheetId = useId();
    function list(cls) {
      return h('ul', { className: cls }, links.map(function (l) { return h('li', { key: l.href }, h('a', { href: l.href, 'aria-current': l.current ? 'page' : undefined }, l.label)); }));
    }
    return h('header', { className: cx('itk-header', p.className) },
      h('div', { className: 'itk-header-bar' },
        h('a', { className: 'itk-header-logo', href: p.homeHref || '/' }, h('img', { className: p.logoSrcDark ? 'itk-logo-light' : undefined, src: p.logoSrc, alt: p.logoAlt || 'Institut Teknologi Kalimantan — Beranda' }), p.logoSrcDark ? h('img', { className: 'itk-logo-dark', src: p.logoSrcDark, alt: p.logoAlt || 'Institut Teknologi Kalimantan — Beranda' }) : null),
        h('nav', { className: 'itk-nav', 'aria-label': 'Utama' }, list()),
        h('div', { className: 'itk-header-actions' },
          p.actions || null,
          h('span', { className: 'itk-header-menu' }, h(Button, { variant: 'ghost', icon: open ? 'x' : 'menu', label: open ? 'Tutup menu' : 'Buka menu', 'aria-expanded': open, 'aria-controls': sheetId, onClick: function () { st[1](!open); } })))),
      open ? h('nav', { className: 'itk-header-sheet', id: sheetId, 'aria-label': 'Utama' }, list()) : null);
  }

  function Ornament(p) {
    return h('div', { role: 'presentation', className: cx('itk-ornament', p.align === 'center' && 'itk-ornament-center', p.className),
      style: Object.assign({ backgroundImage: 'url("' + p.src + '")', '--itk-ornament-h': (p.height || 20) + 'px' }, p.style || {}) });
  }

  var ns = { Icon: Icon, Button: Button, TextField: TextField, NativeSelect: Select, _Field: Field, _describedBy: describedBy, cx: cx, omit: omit, Checkbox: Checkbox, Switch: Switch, Badge: Badge, Alert: Alert, Card: Card, Tabs: Tabs, Table: Table, Breadcrumb: Breadcrumb, SiteHeader: SiteHeader, Ornament: Ornament };
  window.ITK = Object.assign(window.ITK || {}, ns);
})();

(function () {
  var I = window.ITK, React = window.React, h = React.createElement;
  var useState = React.useState, useRef = React.useRef, useEffect = React.useEffect, useLayoutEffect = React.useLayoutEffect, useMemo = React.useMemo, useId = React.useId;
  var cx = I.cx, omit = I.omit, Icon = I.Icon, Button = I.Button, Field = I._Field, describedBy = I._describedBy;

  Object.assign(Icon.paths, {
    plus: 'M12 5v14M5 12h14', minus: 'M5 12h14', chevronLeft: 'M15 6l-6 6 6 6', chevronUp: 'M6 15l6-6 6 6',
    more: 'M6 12h.01M12 12h.01M18 12h.01', moreV: 'M12 6h.01M12 12h.01M12 18h.01',
    calendar: 'M7 3v3M17 3v3M4 8h16M5 5h14a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1z',
    copy: 'M9 9h10v10H9zM5 15V5h10', home: 'M4 11l8-7 8 7M6 9.5V20h12V9.5', user: 'M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM4 21a8 8 0 0 1 16 0',
    book: 'M4 5a2 2 0 0 1 2-2h13v16H6a2 2 0 0 0-2 2V5zM4 19a2 2 0 0 1 2-2h13', bell: 'M6 16V11a6 6 0 1 1 12 0v5l2 2H4l2-2zM10 20a2 2 0 0 0 4 0',
    paperclip: 'M20 11.5l-8.2 8.2a5 5 0 0 1-7.1-7.1l8.6-8.6a3.4 3.4 0 0 1 4.8 4.8l-8.4 8.4a1.7 1.7 0 0 1-2.4-2.4L15 7.1',
    file: 'M14 3H6a1 1 0 0 0-1 1v16a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V8zM14 3v5h5', send: 'M4 12l16-8-6 16-3-7-7-1z',
    upload: 'M12 16V4M7 9l5-5 5 5M5 20h14', camera: 'M4 8h3l2-3h6l2 3h3v12H4zM12 17a4 4 0 1 0 0-8 4 4 0 0 0 0 8z',
    qr: 'M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h2v2h-2zM18 14h2M14 18h2M18 18h2v2',
    grip: 'M9 6h.01M15 6h.01M9 12h.01M15 12h.01M9 18h.01M15 18h.01', eye: 'M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12zM12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6z',
    settings: 'M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6zM19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z',
    backspace: 'M9 5h11a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H9l-6-7zM12 9l6 6M18 9l-6 6', sidebar: 'M4 5h16v14H4zM9 5v14',
    trash: 'M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3', edit: 'M4 20h4L19 9l-4-4L4 16zM13.5 6.5l4 4',
    share: 'M12 3v12M7 8l5-5 5 5M5 13v7h14v-7', download: 'M12 4v12M7 11l5 5 5-5M5 20h14', clock: 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18zM12 7v5l3 2',
    inbox: 'M3 13l3-8h12l3 8v6H3zM3 13h5l1 3h6l1-3h5', grid: 'M4 4h7v7H4zM13 4h7v7h-7zM4 13h7v7H4zM13 13h7v7h-7z', bold: 'M7 4h6a4 4 0 0 1 0 8H7zM7 12h7a4 4 0 0 1 0 8H7z',
    italic: 'M10 4h8M6 20h8M14 4l-4 16', underline: 'M7 4v7a5 5 0 0 0 10 0V4M5 20h14', map: 'M9 4l-6 3v13l6-3 6 3 6-3V4l-6 3zM9 4v13M15 7v13'
  });

  function useCtrl(value, def, onChange) {
    var s = useState(def); var ctl = value !== undefined;
    return [ctl ? value : s[0], function (v) { if (!ctl) s[1](v); if (onChange) onChange(v); }];
  }
  function useDismiss(open, refs, close) {
    useEffect(function () {
      if (!open) return;
      function down(e) { if (refs.every(function (r) { return !r.current || !r.current.contains(e.target); })) close(); }
      function key(e) { if (e.key === 'Escape') { e.stopPropagation(); close(); } }
      document.addEventListener('pointerdown', down); document.addEventListener('keydown', key);
      return function () { document.removeEventListener('pointerdown', down); document.removeEventListener('keydown', key); };
    }, [open]);
  }
  function rovingKeys(e, container, sel) {
    var els = Array.prototype.slice.call(container.querySelectorAll(sel)).filter(function (el) { return el.getAttribute('aria-disabled') !== 'true' && !el.disabled; });
    var i = els.indexOf(document.activeElement), j = null;
    if (e.key === 'ArrowDown' || e.key === 'ArrowRight') j = (i + 1) % els.length;
    else if (e.key === 'ArrowUp' || e.key === 'ArrowLeft') j = (i - 1 + els.length) % els.length;
    else if (e.key === 'Home') j = 0; else if (e.key === 'End') j = els.length - 1;
    if (j !== null && els[j]) { e.preventDefault(); els[j].focus(); return true; }
    return false;
  }

  /* ---------- Typography & primitives ---------- */
  function Text(p) {
    var v = p.variant || 'body';
    var tag = p.as || ({ 'display-xl': 'h1', 'display-l': 'h1', 'title-1': 'h1', 'title-2': 'h2', 'title-3': 'h3', headline: 'h4', code: 'code' }[v] || 'p');
    return h(tag, Object.assign(omit(p, ['variant', 'as', 'tone', 'className']), { className: cx('itk-text', v, p.tone && 'itk-tone-' + p.tone, p.className) }), p.children);
  }
  function Label(p) {
    return h('label', Object.assign(omit(p, ['required', 'className', 'children']), { className: cx('itk-field-label', p.className) }), p.children,
      p.required ? h('span', { className: 'itk-req', 'aria-hidden': 'true' }, '*') : null);
  }
  function Input(p) {
    return h('input', Object.assign({ type: 'text' }, omit(p, ['invalid', 'className']), { className: cx('itk-input', p.className), 'aria-invalid': p.invalid ? 'true' : p['aria-invalid'] }));
  }
  function FieldC(p) {
    var auto = useId(); var child = React.Children.only(p.children); var id = child.props.id || p.id || auto;
    var c = React.cloneElement(child, { id: id, 'aria-invalid': p.error ? 'true' : undefined, 'aria-describedby': describedBy(p, id), required: p.required });
    return Field(Object.assign({}, p, { id: id }), c);
  }
  function Textarea(p) {
    var auto = useId(); var id = p.id || auto; var ref = useRef();
    var st = useState(String(p.value != null ? p.value : (p.defaultValue || '')).length);
    function grow() { var el = ref.current; if (!el || p.autoGrow === false) return; el.style.height = 'auto'; el.style.height = el.scrollHeight + 2 + 'px'; }
    useLayoutEffect(grow, [p.value]);
    var rest = omit(p, ['label', 'hint', 'error', 'className', 'id', 'autoGrow', 'showCount']);
    var ta = h('textarea', Object.assign({ rows: 3 }, rest, { id: id, ref: ref, className: 'itk-input itk-textarea', 'aria-invalid': p.error ? 'true' : undefined, 'aria-describedby': describedBy(p, id),
      onChange: function (e) { st[1](e.target.value.length); grow(); if (p.onChange) p.onChange(e); } }));
    var count = p.showCount && p.maxLength ? h('span', { className: 'itk-count', 'aria-live': 'polite' }, st[0] + '/' + p.maxLength) : null;
    return Field(Object.assign({}, p, { id: id }), h(React.Fragment, null, ta, count));
  }
  function InputGroup(p) {
    var auto = useId(); var id = p.id || auto;
    var rest = omit(p, ['label', 'hint', 'error', 'className', 'id', 'prefix', 'suffix']);
    function add(x, side) { return x == null ? null : h('span', { className: 'itk-ig-add itk-ig-' + side }, typeof x === 'string' && Icon.paths[x] ? h(Icon, { name: x }) : x); }
    return Field(Object.assign({}, p, { id: id }), h('div', { className: cx('itk-ig', p.error && 'is-invalid') },
      add(p.prefix, 'pre'), h('input', Object.assign({ type: 'text' }, rest, { id: id, className: 'itk-ig-input', 'aria-invalid': p.error ? 'true' : undefined, 'aria-describedby': describedBy(p, id) })), add(p.suffix, 'suf')));
  }
  function InputOTP(p) {
    var n = p.length || 6; var c = useCtrl(p.value, '', p.onChange); var val = c[0]; var refs = useRef([]); var gid = useId();
    function setAt(i, ch) {
      var arr = val.split(''); while (arr.length < n) arr.push('');
      arr[i] = ch; var next = arr.join('').slice(0, n); c[1](next.replace(/\s/g, ''));
      if (next.replace(/\s/g, '').length === n && p.onComplete) p.onComplete(next);
    }
    function onInput(i, e) {
      var d = e.target.value.replace(/\D/g, '');
      if (d.length > 1) { var v = (val.slice(0, i) + d).slice(0, n); c[1](v); var k = Math.min(v.length, n - 1); refs.current[k] && refs.current[k].focus(); if (v.length === n && p.onComplete) p.onComplete(v); return; }
      setAt(i, d); if (d && i < n - 1) refs.current[i + 1].focus();
    }
    function onKey(i, e) {
      if (e.key === 'Backspace' && !val[i] && i > 0) { e.preventDefault(); setAt(i - 1, ''); refs.current[i - 1].focus(); }
      else if (e.key === 'ArrowLeft' && i > 0) refs.current[i - 1].focus(); else if (e.key === 'ArrowRight' && i < n - 1) refs.current[i + 1].focus();
    }
    var boxes = []; for (var i = 0; i < n; i++) (function (i) {
      boxes.push(h('input', { key: i, ref: function (el) { refs.current[i] = el; }, className: 'itk-otp-box', inputMode: 'numeric', autoComplete: i === 0 ? 'one-time-code' : 'off',
        maxLength: i === 0 ? n : 1, value: val[i] || '', 'aria-label': 'Digit ' + (i + 1) + ' dari ' + n, 'aria-invalid': p.error ? 'true' : undefined,
        onChange: function (e) { onInput(i, e); }, onKeyDown: function (e) { onKey(i, e); }, onFocus: function (e) { e.target.select(); } }));
    })(i);
    return h('fieldset', { className: cx('itk-field itk-fieldset', p.className), 'aria-describedby': p.error ? gid + '-err' : (p.hint ? gid + '-hint' : undefined) },
      h('legend', { className: 'itk-field-label' }, p.label || 'Kode verifikasi'),
      p.hint ? h('span', { className: 'itk-field-hint', id: gid + '-hint' }, p.hint) : null,
      h('div', { className: 'itk-otp' }, boxes),
      p.error ? h('span', { className: 'itk-field-error', id: gid + '-err' }, h(Icon, { name: 'danger' }), p.error) : null);
  }
  var nf = new Intl.NumberFormat('id-ID');
  function AmountInput(p) {
    var c = useCtrl(p.value, p.defaultValue == null ? null : p.defaultValue, p.onChange); var auto = useId(); var id = p.id || auto;
    var shown = c[0] == null || c[0] === '' ? '' : nf.format(c[0]);
    return Field(Object.assign({}, p, { id: id }), h('div', { className: cx('itk-ig', p.error && 'is-invalid') },
      h('span', { className: 'itk-ig-add itk-ig-pre itk-amount-cur' }, p.currencyLabel || 'Rp'),
      h('input', { id: id, className: 'itk-ig-input itk-amount', inputMode: 'numeric', autoComplete: 'off', placeholder: p.placeholder || '0', value: shown, name: p.name,
        'aria-invalid': p.error ? 'true' : undefined, 'aria-describedby': describedBy(p, id),
        onChange: function (e) { var d = e.target.value.replace(/\D/g, ''); c[1](d === '' ? null : Math.min(Number(d), p.max || 1e15)); } })));
  }
  function Kbd(p) { var keys = p.keys || [p.children]; return h('span', { className: 'itk-kbd-group' }, keys.map(function (k, i) { return h('kbd', { key: i, className: 'itk-kbd' }, k); })); }
  function Separator(p) {
    if (p.orientation === 'vertical') return h('span', { className: 'itk-sep-v', role: 'separator', 'aria-orientation': 'vertical' });
    if (p.label) return h('div', { className: 'itk-sep-label', role: 'separator' }, h('span', null, p.label));
    return h('hr', { className: cx('itk-sep', p.className) });
  }
  function AspectRatio(p) { return h('div', { className: cx('itk-ar', p.className), style: { aspectRatio: String(p.ratio || 16 / 9) } }, p.children); }
  function initials(n) { return (n || '?').split(/\s+/).filter(Boolean).slice(0, 2).map(function (w) { return w[0]; }).join('').toUpperCase(); }
  function Avatar(p) {
    var s = useState(false); var size = p.size || 'md';
    return h('span', { className: cx('itk-avatar', 'itk-avatar-' + size, p.className), role: p.src && !s[0] ? undefined : 'img', 'aria-label': p.src && !s[0] ? undefined : p.name },
      p.src && !s[0] ? h('img', { src: p.src, alt: p.name || '', onError: function () { s[1](true); } }) : h('span', { 'aria-hidden': 'true' }, initials(p.name)),
      p.status ? h('span', { className: 'itk-avatar-dot itk-dot-' + p.status, title: p.status === 'online' ? 'Daring' : 'Luring' }) : null);
  }
  function Marker(p) { return h('mark', { className: cx('itk-marker', 'itk-marker-' + (p.tone || 'gold'), p.className) }, p.children); }

  /* ---------- Buttons & selection ---------- */
  function ButtonGroup(p) { return h('div', { role: 'group', 'aria-label': p.label, className: cx('itk-btn-group', p.attached !== false && 'is-attached', p.className) }, p.children); }
  function Toggle(p) {
    var c = useCtrl(p.pressed, !!p.defaultPressed, p.onPressedChange); var iconOnly = p.icon && !p.children;
    return h('button', { type: 'button', className: cx('itk-toggle', iconOnly && 'is-icon', p.className), 'aria-pressed': c[0], 'aria-label': iconOnly ? p.label : undefined, disabled: p.disabled,
      onClick: function () { c[1](!c[0]); } }, p.icon ? h(Icon, { name: p.icon }) : null, p.children);
  }
  function ToggleGroup(p) {
    var opts = p.options || []; var c = useCtrl(p.value, p.defaultValue || (opts[0] && opts[0].value), p.onChange); var ref = useRef();
    return h('div', { ref: ref, role: 'radiogroup', 'aria-label': p.label, className: cx('itk-seg', p.block && 'is-block', p.className),
      onKeyDown: function (e) { if (rovingKeys(e, ref.current, '[role=radio]')) { var el = document.activeElement; c[1](el.dataset.value); } } },
      opts.map(function (o) {
        var on = o.value === c[0];
        return h('button', { key: o.value, type: 'button', role: 'radio', 'aria-checked': on, tabIndex: on ? 0 : -1, 'data-value': o.value, className: 'itk-seg-item',
          'aria-label': o.icon && !o.label ? o.ariaLabel : undefined, onClick: function () { c[1](o.value); } }, o.icon ? h(Icon, { name: o.icon }) : null, o.label);
      }));
  }
  function RadioGroup(p) {
    var name = useId(); var c = useCtrl(p.value, p.defaultValue, p.onChange); var gid = useId();
    return h('fieldset', { className: cx('itk-field itk-fieldset', p.className), 'aria-describedby': p.error ? gid + '-err' : undefined },
      h('legend', { className: 'itk-field-label' }, p.label, p.required ? h('span', { className: 'itk-req', 'aria-hidden': 'true' }, '*') : null),
      p.hint ? h('span', { className: 'itk-field-hint' }, p.hint) : null,
      h('div', { className: cx('itk-radios', p.orientation === 'horizontal' && 'is-row') }, (p.options || []).map(function (o) {
        return h('label', { key: o.value, className: 'itk-check itk-radio' },
          h('input', { type: 'radio', name: p.name || name, value: o.value, checked: c[0] === o.value, disabled: o.disabled, required: p.required, onChange: function () { c[1](o.value); } }),
          h('span', { className: 'itk-radio-dot', 'aria-hidden': 'true' }),
          h('span', { className: 'itk-check-text' }, o.label, o.description ? h('small', null, o.description) : null));
      })),
      p.error ? h('span', { className: 'itk-field-error', id: gid + '-err' }, h(Icon, { name: 'danger' }), p.error) : null);
  }
  function Slider(p) {
    var min = p.min || 0, max = p.max == null ? 100 : p.max; var c = useCtrl(p.value, p.defaultValue == null ? min : p.defaultValue, p.onChange); var id = useId();
    var fmt = p.format || function (v) { return String(v); }; var pct = (c[0] - min) / (max - min) * 100; var list = p.ticks ? id + '-ticks' : undefined;
    return h('div', { className: cx('itk-field itk-slider', p.className) },
      h('div', { className: 'itk-slider-head' }, h('label', { className: 'itk-field-label', htmlFor: id }, p.label), p.showValue !== false ? h('output', { htmlFor: id, className: 'itk-slider-val' }, fmt(c[0])) : null),
      h('input', { id: id, type: 'range', min: min, max: max, step: p.step || 1, value: c[0], list: list, disabled: p.disabled, 'aria-valuetext': fmt(c[0]),
        style: { '--itk-pct': pct + '%' }, className: 'itk-range', onChange: function (e) { c[1](Number(e.target.value)); } }),
      p.ticks ? h('div', { className: 'itk-ticks', 'aria-hidden': 'true' }, p.ticks.map(function (t) { return h('span', { key: t, style: { left: (t - min) / (max - min) * 100 + '%' } }, fmt(t)); })) : null);
  }
  function Chip(p) {
    var c = useCtrl(p.selected, !!p.defaultSelected, p.onSelectedChange);
    if (p.onRemove) return h('span', { className: cx('itk-chip is-input', p.className) }, p.children,
      h('button', { type: 'button', className: 'itk-chip-x', 'aria-label': 'Hapus ' + (typeof p.children === 'string' ? p.children : ''), onClick: p.onRemove }, h(Icon, { name: 'x' })));
    return h('button', { type: 'button', className: cx('itk-chip', p.className), 'aria-pressed': c[0], onClick: function () { c[1](!c[0]); } },
      c[0] ? h(Icon, { name: 'check' }) : (p.icon ? h(Icon, { name: p.icon }) : null), p.children);
  }

  /* ---------- Disclosure ---------- */
  function Collapsible(p) {
    var c = useCtrl(p.open, !!p.defaultOpen, p.onOpenChange); var id = useId();
    return h('div', { className: cx('itk-collapsible', c[0] && 'is-open', p.className) },
      h('button', { type: 'button', className: 'itk-disclosure', 'aria-expanded': c[0], 'aria-controls': id, onClick: function () { c[1](!c[0]); } },
        h('span', null, p.title), h(Icon, { name: 'chevronDown' })),
      h('div', { id: id, className: 'itk-collapsible-body', hidden: !c[0] }, p.children));
  }
  function Accordion(p) {
    var multi = p.type === 'multiple'; var c = useCtrl(p.value, p.defaultValue || (multi ? [] : null), p.onChange); var base = useId();
    function isOpen(v) { return multi ? c[0].indexOf(v) >= 0 : c[0] === v; }
    function toggle(v) { if (multi) c[1](isOpen(v) ? c[0].filter(function (x) { return x !== v; }) : c[0].concat([v])); else c[1](isOpen(v) ? null : v); }
    return h('div', { className: cx('itk-accordion', p.className) }, (p.items || []).map(function (it) {
      var o = isOpen(it.value), hid = base + '-h-' + it.value, pid = base + '-p-' + it.value;
      return h('div', { key: it.value, className: cx('itk-acc-item', o && 'is-open') },
        h(p.headingAs || 'h3', { className: 'itk-acc-h' }, h('button', { type: 'button', id: hid, className: 'itk-disclosure', 'aria-expanded': o, 'aria-controls': pid, onClick: function () { toggle(it.value); } },
          h('span', null, it.title), h(Icon, { name: 'chevronDown' }))),
        h('div', { id: pid, role: 'region', 'aria-labelledby': hid, className: 'itk-collapsible-body', hidden: !o }, it.content));
    }));
  }

  /* ---------- Lists & content ---------- */
  function ItemGroup(p) {
    return h('section', { className: cx('itk-item-group', p.className) },
      p.title ? h('h3', { className: 'itk-item-group-title' }, p.title) : null,
      h('ul', { className: 'itk-list', role: 'list' }, p.children),
      p.footer ? h('p', { className: 'itk-item-group-foot' }, p.footer) : null);
  }
  function Item(p) {
    var Tag = p.href ? 'a' : (p.onClick ? 'button' : 'div');
    var inner = h(Tag, { className: cx('itk-item', (p.href || p.onClick) && 'is-action', p.className), href: p.href, onClick: p.onClick, type: Tag === 'button' ? 'button' : undefined, 'aria-current': p.current ? 'page' : undefined },
      p.leading ? h('span', { className: 'itk-item-lead' }, typeof p.leading === 'string' && Icon.paths[p.leading] ? h(Icon, { name: p.leading }) : p.leading) : null,
      h('span', { className: 'itk-item-main' }, h('span', { className: 'itk-item-title' }, p.title), p.subtitle ? h('span', { className: 'itk-item-sub' }, p.subtitle) : null),
      p.trailing != null ? h('span', { className: 'itk-item-trail' }, p.trailing) : null,
      p.href || p.chevron ? h(Icon, { name: 'chevronRight', className: 'itk-item-chev' }) : null);
    return p.as === 'div' ? inner : h('li', null, inner);
  }
  function Empty(p) {
    return h('div', { className: cx('itk-empty', p.className) },
      h('span', { className: 'itk-empty-icon', 'aria-hidden': 'true' }, h(Icon, { name: p.icon || 'inbox' })),
      h(p.titleAs || 'h3', { className: 'itk-empty-title' }, p.title),
      p.children ? h('p', { className: 'itk-empty-body' }, p.children) : null,
      p.action ? h('div', { className: 'itk-empty-action' }, p.action) : null);
  }
  function CodeBlock(p) {
    var s = useState(false);
    function copy() { try { navigator.clipboard.writeText(p.code).then(function () { s[1](true); setTimeout(function () { s[1](false); }, 1800); }); } catch (e) {} }
    var lines = String(p.code || '').replace(/\n$/, '').split('\n');
    return h('figure', { className: cx('itk-code', p.className) },
      h('figcaption', { className: 'itk-code-head' }, h('span', null, p.title || p.language || 'Kode'),
        h('button', { type: 'button', className: 'itk-code-copy', onClick: copy, 'aria-live': 'polite' }, h(Icon, { name: s[0] ? 'check' : 'copy' }), s[0] ? 'Tersalin' : 'Salin')),
      h('pre', { tabIndex: 0, 'aria-label': p.title || 'Contoh kode' }, h('code', null, p.lineNumbers ? lines.map(function (l, i) { return h('span', { key: i, className: 'itk-code-line' }, h('span', { className: 'itk-code-ln', 'aria-hidden': 'true' }, i + 1), l + '\n'); }) : p.code)));
  }
  function Spinner(p) {
    var size = p.size || 20;
    return h('span', { className: cx('itk-activity', p.className), role: 'status', style: { width: size, height: size } },
      h('svg', { viewBox: '0 0 24 24', 'aria-hidden': 'true' }, Array.from({ length: 8 }, function (_, i) { return h('line', { key: i, x1: 12, y1: 3, x2: 12, y2: 7, transform: 'rotate(' + i * 45 + ' 12 12)', style: { opacity: 0.25 + i * 0.1 } }); })),
      h('span', { className: 'itk-sr' }, p.label || 'Memuat'));
  }
  function Progress(p) {
    var ind = p.value == null; var max = p.max || 100; var pct = ind ? 0 : Math.round(p.value / max * 100); var id = useId();
    return h('div', { className: cx('itk-progress', p.className) },
      p.label || p.showValue ? h('div', { className: 'itk-progress-head' }, h('span', { id: id }, p.label), !ind && p.showValue !== false ? h('span', { className: 'itk-progress-val' }, pct + '%') : null) : null,
      h('div', { className: cx('itk-progress-track', ind && 'is-ind'), role: 'progressbar', 'aria-labelledby': p.label ? id : undefined, 'aria-label': p.label ? undefined : 'Progres', 'aria-valuemin': ind ? undefined : 0, 'aria-valuemax': ind ? undefined : max, 'aria-valuenow': ind ? undefined : p.value, 'aria-valuetext': p.valueText },
        h('div', { className: 'itk-progress-bar', style: ind ? undefined : { width: pct + '%' } })),
      p.description ? h('span', { className: 'itk-field-hint' }, p.description) : null);
  }
  function Skeleton(p) {
    var st = { width: p.width, height: p.height }; if (p.circle) { st.width = st.height = p.size || 40; }
    return h('span', { className: cx('itk-skel', p.circle && 'is-circle', p.text && 'is-text', p.className), style: st, 'aria-hidden': 'true' });
  }

  /* ---------- Toast ---------- */
  var toasts = [], subs = [];
  function emit() { subs.forEach(function (f) { f(toasts.slice()); }); }
  function toast(o) { if (typeof o === 'string') o = { message: o }; var id = 't' + Date.now() + Math.random(); toasts = toasts.concat([Object.assign({ id: id, tone: 'neutral', duration: 5000 }, o)]).slice(-3); emit(); return id; }
  toast.dismiss = function (id) { toasts = toasts.filter(function (t) { return t.id !== id; }); emit(); };
  var TONE_ICON = { success: 'success', danger: 'danger', warning: 'warning', info: 'info' };
  function Toast(p) {
    return h('div', { className: cx('itk-toast', 'itk-toast-' + (p.tone || 'neutral'), p.className), onMouseEnter: p.onPause, onMouseLeave: p.onResume, onFocus: p.onPause, onBlur: p.onResume },
      TONE_ICON[p.tone] ? h(Icon, { name: TONE_ICON[p.tone] }) : null,
      h('span', { className: 'itk-toast-msg' }, p.message || p.children),
      p.action ? h('button', { type: 'button', className: 'itk-toast-action', onClick: function () { p.action.onClick(); if (p.onDismiss) p.onDismiss(); } }, p.action.label) : null,
      p.onDismiss ? h('button', { type: 'button', className: 'itk-toast-x', 'aria-label': 'Tutup', onClick: p.onDismiss }, h(Icon, { name: 'x' })) : null);
  }
  function ToastTimer(p) {
    var t = p.t; var timer = useRef(); var left = useRef(t.duration); var start = useRef(0);
    function run() { if (!t.duration) return; start.current = Date.now(); timer.current = setTimeout(function () { toast.dismiss(t.id); }, left.current); }
    function pause() { clearTimeout(timer.current); left.current -= Date.now() - start.current; }
    useEffect(function () { run(); return function () { clearTimeout(timer.current); }; }, []);
    return h(Toast, Object.assign({}, t, { onPause: pause, onResume: run, onDismiss: function () { toast.dismiss(t.id); } }));
  }
  function Toaster(p) {
    var s = useState(toasts);
    useEffect(function () { subs.push(s[1]); return function () { subs = subs.filter(function (f) { return f !== s[1]; }); }; }, []);
    return h('section', { className: cx('itk-toaster', p && p.position === 'top' && 'is-top'), 'aria-label': 'Notifikasi', 'aria-live': 'polite', role: 'status' }, s[0].map(function (t) { return h(ToastTimer, { key: t.id, t: t }); }));
  }

  Object.assign(I, { Text: Text, Label: Label, Input: Input, Field: FieldC, Textarea: Textarea, InputGroup: InputGroup, InputOTP: InputOTP, AmountInput: AmountInput, Kbd: Kbd,
    Separator: Separator, AspectRatio: AspectRatio, Avatar: Avatar, Marker: Marker, ButtonGroup: ButtonGroup, Toggle: Toggle, ToggleGroup: ToggleGroup, RadioGroup: RadioGroup,
    Slider: Slider, Chip: Chip, Collapsible: Collapsible, Accordion: Accordion, Item: Item, ItemGroup: ItemGroup, Empty: Empty, CodeBlock: CodeBlock, Spinner: Spinner,
    Progress: Progress, Skeleton: Skeleton, Toast: Toast, Toaster: Toaster, toast: toast,
    _h: { useCtrl: useCtrl, useDismiss: useDismiss, rovingKeys: rovingKeys, initials: initials, nf: nf } });
})();

(function () {
  var I = window.ITK, React = window.React, h = React.createElement;
  var useState = React.useState, useRef = React.useRef, useEffect = React.useEffect, useMemo = React.useMemo, useId = React.useId;
  var cx = I.cx, omit = I.omit, Icon = I.Icon, Button = I.Button, H = I._h, useCtrl = H.useCtrl, useDismiss = H.useDismiss, rovingKeys = H.rovingKeys;

  function useKeepInView(ref, open) {
    React.useLayoutEffect(function () {
      var el = ref.current; if (!open || !el) return; el.style.translate = '';
      var r = el.getBoundingClientRect(), vw = document.documentElement.clientWidth, dx = 0;
      if (r.right > vw - 8) dx = vw - 8 - r.right; if (r.left + dx < 8) dx = 8 - r.left;
      if (dx) el.style.translate = dx + 'px 0';
    });
  }
  /* ---------- Modal base (native <dialog>: focus trap, Esc, top layer) ---------- */
  function Modal(p) {
    var ref = useRef();
    useEffect(function () {
      var d = ref.current; if (!d || p.inline) return;
      if (p.open && !d.open) { try { d.showModal(); } catch (e) { d.setAttribute('open', ''); } }
      else if (!p.open && d.open) d.close();
    }, [p.open, p.inline]);
    useEffect(function () {
      var d = ref.current; if (!d || p.inline) return;
      function cancel(e) { e.preventDefault(); if (p.dismissible !== false && p.onClose) p.onClose(); }
      d.addEventListener('cancel', cancel); return function () { d.removeEventListener('cancel', cancel); };
    }, [p.onClose, p.dismissible]);
    var props = { className: cx('itk-modal', p.className, p.inline && 'is-inline'), role: p.role, 'aria-labelledby': p.labelledBy, 'aria-describedby': p.describedBy, 'aria-modal': p.inline ? undefined : 'true' };
    if (p.inline) return h('div', Object.assign(props, { role: p.role || 'dialog' }), p.children);
    return h('dialog', Object.assign(props, { ref: ref, onClick: function (e) { if (e.target === ref.current && p.dismissible !== false && p.onClose) p.onClose(); } }), p.open ? p.children : null);
  }
  function CloseBtn(p) { return h('button', { type: 'button', className: 'itk-close', 'aria-label': p.label || 'Tutup', onClick: p.onClick }, h(Icon, { name: 'x' })); }

  function Dialog(p) {
    var id = useId();
    return h(Modal, { open: p.open, inline: p.inline, onClose: p.onClose, labelledBy: id + '-t', describedBy: p.description ? id + '-d' : undefined, className: 'itk-dialog-wrap' },
      h('div', { className: cx('itk-dialog', 'itk-dialog-' + (p.size || 'md'), p.className) },
        h('div', { className: 'itk-dialog-head' }, h('h2', { id: id + '-t', className: 'itk-dialog-title' }, p.title), p.onClose ? h(CloseBtn, { onClick: p.onClose }) : null),
        p.description ? h('p', { id: id + '-d', className: 'itk-dialog-desc' }, p.description) : null,
        p.children ? h('div', { className: 'itk-dialog-body' }, p.children) : null,
        p.footer ? h('div', { className: 'itk-dialog-foot' }, p.footer) : null));
  }
  function AlertDialog(p) {
    var id = useId();
    return h(Modal, { open: p.open, inline: p.inline, role: 'alertdialog', dismissible: false, onClose: p.onCancel, labelledBy: id + '-t', describedBy: p.message ? id + '-d' : undefined, className: 'itk-dialog-wrap' },
      h('div', { className: cx('itk-dialog itk-alertdialog', p.className) },
        h('h2', { id: id + '-t', className: 'itk-dialog-title' }, p.title),
        p.message ? h('p', { id: id + '-d', className: 'itk-dialog-desc' }, p.message) : null,
        h('div', { className: 'itk-alertdialog-actions' },
          p.cancelLabel !== null ? h(Button, { variant: 'secondary', onClick: p.onCancel, autoFocus: p.destructive }, p.cancelLabel || 'Batal') : null,
          h(Button, { variant: p.destructive ? 'danger' : 'primary', onClick: p.onConfirm, autoFocus: !p.destructive }, p.confirmLabel))));
  }
  function AlertSheet(p) {
    var id = useId();
    return h(Modal, { open: p.open, inline: p.inline, onClose: p.onClose, labelledBy: p.title ? id + '-t' : undefined, className: 'itk-asheet-wrap' },
      h('div', { className: cx('itk-asheet', p.className) },
        h('div', { className: 'itk-asheet-group' },
          p.title || p.message ? h('div', { className: 'itk-asheet-head' }, p.title ? h('h2', { id: id + '-t' }, p.title) : null, p.message ? h('p', null, p.message) : null) : null,
          (p.actions || []).map(function (a, i) { return h('button', { key: i, type: 'button', className: cx('itk-asheet-btn', a.destructive && 'is-danger'), onClick: function () { a.onSelect && a.onSelect(); p.onClose && p.onClose(); } }, a.label); })),
        h('button', { type: 'button', className: 'itk-asheet-btn itk-asheet-cancel', onClick: p.onClose }, p.cancelLabel || 'Batal')));
  }
  function Sheet(p) {
    var id = useId(); var side = p.side || 'right';
    return h(Modal, { open: p.open, inline: p.inline, onClose: p.onClose, labelledBy: id + '-t', className: 'itk-sheet-wrap is-' + side },
      h('div', { className: cx('itk-sheet', 'itk-sheet-' + side, p.className) },
        h('div', { className: 'itk-sheet-head' }, h('h2', { id: id + '-t', className: 'itk-dialog-title' }, p.title), h(CloseBtn, { onClick: p.onClose })),
        h('div', { className: 'itk-sheet-body' }, p.children),
        p.footer ? h('div', { className: 'itk-sheet-foot' }, p.footer) : null));
  }
  function Drawer(p) {
    var id = useId(); var c = useCtrl(p.detent, p.defaultDetent || 'medium', p.onDetentChange); var drag = useRef(null); var panel = useRef();
    function down(e) { drag.current = { y: e.clientY, h: panel.current.getBoundingClientRect().height }; e.currentTarget.setPointerCapture(e.pointerId); }
    function move(e) { if (!drag.current) return; var dy = e.clientY - drag.current.y; panel.current.style.height = Math.max(120, drag.current.h - dy) + 'px'; }
    function up(e) {
      if (!drag.current) return; var dy = e.clientY - drag.current.y; drag.current = null; panel.current.style.height = '';
      if (dy > 120 && c[0] === 'medium') { p.onClose && p.onClose(); } else if (dy > 80) c[1]('medium'); else if (dy < -80) c[1]('large');
    }
    return h(Modal, { open: p.open, inline: p.inline, onClose: p.onClose, labelledBy: id + '-t', className: 'itk-sheet-wrap is-bottom' },
      h('div', { ref: panel, className: cx('itk-drawer', 'is-' + c[0], p.className) },
        h('button', { type: 'button', className: 'itk-grabber', 'aria-label': c[0] === 'medium' ? 'Perbesar panel' : 'Perkecil panel', onPointerDown: down, onPointerMove: move, onPointerUp: up,
          onClick: function () { if (!drag.current) c[1](c[0] === 'medium' ? 'large' : 'medium'); } }, h('span')),
        h('div', { className: 'itk-sheet-head' }, h('h2', { id: id + '-t', className: 'itk-dialog-title' }, p.title), h(Button, { variant: 'ghost', size: 'sm', onClick: p.onClose }, p.doneLabel || 'Selesai')),
        h('div', { className: 'itk-sheet-body' }, p.children)));
  }
  function FullscreenMenu(p) {
    return h(Modal, { open: p.open, inline: p.inline, onClose: p.onClose, labelledBy: undefined, className: 'itk-fsm-wrap' },
      h('nav', { className: cx('itk-fsm', p.className), 'aria-label': p.label || 'Menu utama' },
        h('div', { className: 'itk-fsm-top' }, p.logo || h('span'), h(CloseBtn, { onClick: p.onClose, label: 'Tutup menu' })),
        h('ul', { className: 'itk-fsm-links' }, (p.links || []).map(function (l) { return h('li', { key: l.href }, h('a', { href: l.href, 'aria-current': l.current ? 'page' : undefined, onClick: p.onClose }, l.label, l.description ? h('small', null, l.description) : null)); })),
        p.footer ? h('div', { className: 'itk-fsm-foot' }, p.footer) : null));
  }

  /* ---------- Floating: popover, tooltip, hover card ---------- */
  function Popover(p) {
    var c = useCtrl(p.open, false, p.onOpenChange); var wrap = useRef(); var panel = useRef(); var id = useId(); useKeepInView(panel, c[0] || p.inline);
    useDismiss(c[0], [wrap], function () { c[1](false); var b = wrap.current && wrap.current.querySelector('[aria-controls="' + id + '"]'); b && b.focus(); });
    useEffect(function () { if (c[0] && panel.current) { var f = panel.current.querySelector('input,button,select,textarea,a[href],[tabindex]'); (f || panel.current).focus(); } }, [c[0]]);
    var trig = React.cloneElement(p.trigger, { 'aria-expanded': c[0], 'aria-controls': id, 'aria-haspopup': 'dialog', onClick: function () { c[1](!c[0]); } });
    return h('span', { ref: wrap, className: 'itk-anchor' }, trig,
      c[0] || p.inline ? h('div', { ref: panel, id: id, role: 'dialog', 'aria-label': p.label, tabIndex: -1, className: cx('itk-float itk-popover', 'is-' + (p.side || 'bottom'), p.align === 'end' && 'is-end', p.className) },
        p.title ? h('div', { className: 'itk-popover-title' }, p.title) : null, p.children) : null);
  }
  function Tooltip(p) {
    var s = useState(false); var t = useRef(); var id = useId(); var tip = useRef(); useKeepInView(tip, s[0] || p.open);
    useEffect(function () { if (!s[0]) return; function k(e) { if (e.key === 'Escape') s[1](false); } document.addEventListener('keydown', k); return function () { document.removeEventListener('keydown', k); }; }, [s[0]]);
    function show() { clearTimeout(t.current); t.current = setTimeout(function () { s[1](true); }, p.delay == null ? 400 : p.delay); }
    function hide() { clearTimeout(t.current); t.current = setTimeout(function () { s[1](false); }, 100); }
    var child = React.cloneElement(React.Children.only(p.children), { 'aria-describedby': id, onMouseEnter: show, onMouseLeave: hide, onFocus: show, onBlur: hide });
    return h('span', { className: 'itk-anchor', onMouseEnter: function () { if (s[0]) clearTimeout(t.current); }, onMouseLeave: hide }, child,
      h('span', { ref: tip, id: id, role: 'tooltip', className: cx('itk-float itk-tooltip', 'is-' + (p.side || 'top')), hidden: !(s[0] || p.open) }, p.content));
  }
  function HoverCard(p) {
    var s = useState(false); var t = useRef(); var id = useId(); var card = useRef(); useKeepInView(card, s[0] || p.open);
    function show() { clearTimeout(t.current); t.current = setTimeout(function () { s[1](true); }, 500); }
    function hide() { clearTimeout(t.current); t.current = setTimeout(function () { s[1](false); }, 200); }
    useEffect(function () { if (!s[0]) return; function k(e) { if (e.key === 'Escape') s[1](false); } document.addEventListener('keydown', k); return function () { document.removeEventListener('keydown', k); }; }, [s[0]]);
    var child = React.cloneElement(React.Children.only(p.children), { onFocus: show, onBlur: hide, 'aria-describedby': s[0] || p.open ? id : undefined });
    return h('span', { className: 'itk-anchor', onMouseEnter: show, onMouseLeave: hide }, child,
      s[0] || p.open ? h('div', { ref: card, id: id, className: cx('itk-float itk-popover itk-hovercard', 'is-' + (p.side || 'bottom')) }, p.content) : null);
  }

  /* ---------- Menus ---------- */
  function MenuList(p) {
    var ref = useRef();
    useEffect(function () { if (p.autoFocus !== false && ref.current) { var f = ref.current.querySelector('[role=menuitem]:not([aria-disabled=true])'); f && f.focus(); } }, []);
    function onKey(e) {
      if (rovingKeys(e, ref.current, '[role=menuitem]')) return;
      if (e.key === 'Tab') p.onClose && p.onClose();
      if (e.key.length === 1 && /\S/.test(e.key)) {
        var els = Array.prototype.slice.call(ref.current.querySelectorAll('[role=menuitem]'));
        var m = els.filter(function (el) { return el.textContent.trim().toLowerCase().indexOf(e.key.toLowerCase()) === 0; })[0]; m && m.focus();
      }
    }
    return h('div', { ref: ref, role: 'menu', 'aria-label': p.label, className: cx('itk-menu', p.className), style: p.style, onKeyDown: onKey },
      (p.items || []).map(function (it, i) {
        if (it.separator) return h('div', { key: i, role: 'separator', className: 'itk-menu-sep' });
        if (it.heading) return h('div', { key: i, className: 'itk-menu-heading', role: 'presentation' }, it.heading);
        return h('button', { key: i, type: 'button', role: 'menuitem', tabIndex: -1, 'aria-disabled': it.disabled ? 'true' : undefined, className: cx('itk-menu-item', it.destructive && 'is-danger'),
          onClick: function () { if (it.disabled) return; it.onSelect && it.onSelect(); p.onClose && p.onClose(true); } },
          h('span', { className: 'itk-menu-icon' }, it.icon ? h(Icon, { name: it.icon }) : null), h('span', { className: 'itk-menu-label' }, it.label),
          it.shortcut && p.showShortcuts !== false ? h('span', { className: 'itk-menu-kbd' }, it.shortcut) : null);
      }));
  }
  function DropdownMenu(p) {
    var s = useState(false); var wrap = useRef(); var btn = useRef(); var id = useId(); var fl = useRef(); useKeepInView(fl, s[0] || p.inline);
    function close(ret) { s[1](false); if (ret !== false) { var b = wrap.current && wrap.current.querySelector('[aria-haspopup=menu]'); b && b.focus(); } }
    useDismiss(s[0], [wrap], function () { close(); });
    var trig = React.cloneElement(p.trigger, { id: id + '-b', 'aria-haspopup': 'menu', 'aria-expanded': s[0], 'aria-controls': s[0] ? id : undefined, onClick: function () { s[1](!s[0]); },
      onKeyDown: function (e) { if (e.key === 'ArrowDown') { e.preventDefault(); s[1](true); } } });
    return h('span', { ref: wrap, className: 'itk-anchor' }, trig,
      s[0] || p.inline ? h('div', { ref: fl, id: id, className: cx('itk-float', p.align === 'end' && 'is-end') }, h(MenuList, { items: p.items, label: p.label, onClose: close, autoFocus: !p.inline })) : null);
  }
  function ContextMenu(p) {
    var s = useState(null); var wrap = useRef(); var lp = useRef();
    useDismiss(!!s[0], [wrap], function () { s[1](null); });
    function openAt(x, y) { var r = wrap.current.getBoundingClientRect(); s[1]({ x: x - r.left, y: y - r.top }); }
    return h('div', { ref: wrap, className: cx('itk-ctx', p.className), tabIndex: 0, 'aria-haspopup': 'menu', 'aria-label': p.label,
      onContextMenu: function (e) { e.preventDefault(); openAt(e.clientX, e.clientY); },
      onKeyDown: function (e) { if ((e.shiftKey && e.key === 'F10') || e.key === 'ContextMenu') { e.preventDefault(); var r = wrap.current.getBoundingClientRect(); openAt(r.left + 16, r.top + 16); } },
      onPointerDown: function (e) { if (e.pointerType !== 'mouse') { var x = e.clientX, y = e.clientY; lp.current = setTimeout(function () { openAt(x, y); }, 500); } },
      onPointerUp: function () { clearTimeout(lp.current); }, onPointerMove: function () { clearTimeout(lp.current); } },
      p.children,
      s[0] || p.inline ? h('div', { className: 'itk-ctx-pos', style: s[0] ? { left: s[0].x, top: s[0].y } : { left: 24, top: 24 } }, h(MenuList, { items: p.items, label: p.label, showShortcuts: false, autoFocus: !p.inline, onClose: function () { s[1](null); wrap.current.focus(); } })) : null);
  }
  function Menubar(p) {
    var s = useState(p.inlineOpen == null ? null : p.inlineOpen); var ref = useRef(); var menus = p.menus || [];
    useDismiss(s[0] !== null && p.inlineOpen == null, [ref], function () { s[1](null); });
    function focusTop(i) { var b = ref.current.querySelectorAll('.itk-menubar-top')[i]; b && b.focus(); }
    return h('div', { ref: ref, role: 'menubar', 'aria-label': p.label, className: cx('itk-menubar', p.className),
      onKeyDown: function (e) {
        var tops = Array.prototype.slice.call(ref.current.querySelectorAll('.itk-menubar-top')); var i = tops.indexOf(document.activeElement);
        var cur = s[0] !== null ? s[0] : i;
        if (e.key === 'ArrowRight' || e.key === 'ArrowLeft') { e.preventDefault(); var j = (cur + (e.key === 'ArrowRight' ? 1 : -1) + menus.length) % menus.length; if (s[0] !== null) s[1](j); focusTop(j); }
        else if (e.key === 'ArrowDown' && i >= 0) { e.preventDefault(); s[1](i); }
      } },
      menus.map(function (m, i) {
        var open = s[0] === i;
        return h('span', { key: m.label, className: 'itk-anchor' },
          h('button', { type: 'button', role: 'menuitem', 'aria-haspopup': 'menu', 'aria-expanded': open, tabIndex: i === 0 ? 0 : -1, className: 'itk-menubar-top', onClick: function () { s[1](open ? null : i); },
            onMouseEnter: function () { if (s[0] !== null && s[0] !== i) s[1](i); } }, m.label),
          open ? h('div', { className: 'itk-float' }, h(MenuList, { items: m.items, label: m.label, autoFocus: p.inlineOpen == null, onClose: function () { s[1](null); focusTop(i); } })) : null);
      }));
  }

  /* ---------- Select, Combobox, Command ---------- */
  function norm(o) { return typeof o === 'string' ? { value: o, label: o } : o; }
  function Select(p) {
    var opts = (p.options || []).map(norm); var c = useCtrl(p.value, p.defaultValue == null ? null : p.defaultValue, p.onChange);
    var s = useState(false), open = s[0]; var act = useState(0); var wrap = useRef(); var btn = useRef(); var list = useRef(); var auto = useId(); var id = p.id || auto;
    useDismiss(open, [wrap], function () { s[1](false); });
    var cur = opts.filter(function (o) { return o.value === c[0]; })[0];
    function openList() { var i = Math.max(0, opts.indexOf(cur)); act[1](i); s[1](true); }
    useEffect(function () { if (open && list.current) list.current.focus(); }, [open]);
    function choose(i) { var o = opts[i]; if (!o || o.disabled) return; c[1](o.value); s[1](false); btn.current && btn.current.focus(); }
    function onKey(e) {
      var i = act[0];
      if (e.key === 'ArrowDown') { e.preventDefault(); act[1](Math.min(opts.length - 1, i + 1)); }
      else if (e.key === 'ArrowUp') { e.preventDefault(); act[1](Math.max(0, i - 1)); }
      else if (e.key === 'Home') { e.preventDefault(); act[1](0); } else if (e.key === 'End') { e.preventDefault(); act[1](opts.length - 1); }
      else if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); choose(i); }
      else if (e.key === 'Tab') s[1](false);
      else if (e.key.length === 1) { var j = opts.findIndex(function (o) { return o.label.toLowerCase().indexOf(e.key.toLowerCase()) === 0; }); if (j >= 0) act[1](j); }
    }
    useEffect(function () { var el = list.current && list.current.children[act[0]]; el && el.scrollIntoView && el.scrollIntoView({ block: 'nearest' }); }, [act[0], open]);
    var control = h('div', { ref: wrap, className: 'itk-anchor is-block' },
      h('button', { ref: btn, id: id, type: 'button', className: cx('itk-input itk-select-btn', !cur && 'is-placeholder'), 'aria-haspopup': 'listbox', 'aria-expanded': open, 'aria-controls': id + '-l',
        'aria-invalid': p.error ? 'true' : undefined, 'aria-describedby': I._describedBy(p, id), disabled: p.disabled, onClick: function () { open ? s[1](false) : openList(); },
        onKeyDown: function (e) { if (e.key === 'ArrowDown' || e.key === 'ArrowUp') { e.preventDefault(); openList(); } } },
        h('span', null, cur ? cur.label : (p.placeholder || 'Pilih')), h(Icon, { name: 'chevronDown' })),
      open || p.inline ? h('ul', { ref: list, id: id + '-l', role: 'listbox', tabIndex: -1, 'aria-labelledby': id + '-lbl', 'aria-activedescendant': id + '-o' + act[0], className: 'itk-float itk-listbox is-block', onKeyDown: onKey },
        opts.map(function (o, i) {
          var sel = o.value === c[0];
          return h('li', { key: o.value, id: id + '-o' + i, role: 'option', 'aria-selected': sel, 'aria-disabled': o.disabled ? 'true' : undefined, className: cx('itk-option', i === act[0] && 'is-active'),
            onClick: function () { choose(i); }, onMouseMove: function () { act[1](i); } }, h('span', null, o.label, o.description ? h('small', null, o.description) : null), sel ? h(Icon, { name: 'check' }) : null);
        })) : null);
    return h('div', { className: cx('itk-field', p.className) },
      h('label', { className: 'itk-field-label', id: id + '-lbl', htmlFor: id }, p.label, p.required ? h('span', { className: 'itk-req', 'aria-hidden': 'true' }, '*') : null),
      p.hint ? h('span', { className: 'itk-field-hint', id: id + '-hint' }, p.hint) : null, control,
      p.error ? h('span', { className: 'itk-field-error', id: id + '-err' }, h(Icon, { name: 'danger' }), p.error) : null);
  }
  function Combobox(p) {
    var opts = (p.options || []).map(norm); var c = useCtrl(p.value, p.defaultValue == null ? null : p.defaultValue, p.onChange);
    var cur = opts.filter(function (o) { return o.value === c[0]; })[0];
    var q = useState(cur ? cur.label : ''); var s = useState(false); var act = useState(0); var wrap = useRef(); var auto = useId(); var id = p.id || auto;
    useDismiss(s[0], [wrap], function () { s[1](false); });
    var shown = opts.filter(function (o) { return !q[0] || (cur && q[0] === cur.label) || (o.label + ' ' + (o.keywords || '')).toLowerCase().indexOf(q[0].toLowerCase()) >= 0; });
    function choose(o) { c[1](o.value); q[1](o.label); s[1](false); }
    function onKey(e) {
      if (e.key === 'ArrowDown') { e.preventDefault(); if (!s[0]) s[1](true); act[1](Math.min(shown.length - 1, act[0] + 1)); }
      else if (e.key === 'ArrowUp') { e.preventDefault(); act[1](Math.max(0, act[0] - 1)); }
      else if (e.key === 'Enter' && s[0] && shown[act[0]]) { e.preventDefault(); choose(shown[act[0]]); }
      else if (e.key === 'Escape') { s[1](false); }
    }
    var open = (s[0] || p.inline);
    return h('div', { className: cx('itk-field', p.className) },
      h('label', { className: 'itk-field-label', htmlFor: id }, p.label),
      p.hint ? h('span', { className: 'itk-field-hint', id: id + '-hint' }, p.hint) : null,
      h('div', { ref: wrap, className: 'itk-anchor is-block' },
        h('div', { className: 'itk-ig' }, h('span', { className: 'itk-ig-add itk-ig-pre' }, h(Icon, { name: 'search' })),
          h('input', { id: id, className: 'itk-ig-input', role: 'combobox', 'aria-autocomplete': 'list', 'aria-expanded': !!open, 'aria-controls': id + '-l', autoComplete: 'off',
            'aria-activedescendant': open && shown[act[0]] ? id + '-o' + act[0] : undefined, placeholder: p.placeholder, value: q[0], 'aria-describedby': p.hint ? id + '-hint' : undefined,
            onChange: function (e) { q[1](e.target.value); s[1](true); act[1](0); }, onFocus: function () { s[1](true); }, onKeyDown: onKey })),
        open ? h('ul', { id: id + '-l', role: 'listbox', className: 'itk-float itk-listbox is-block' },
          shown.length ? shown.map(function (o, i) {
            return h('li', { key: o.value, id: id + '-o' + i, role: 'option', 'aria-selected': o.value === c[0], className: cx('itk-option', i === act[0] && 'is-active'), onMouseDown: function (e) { e.preventDefault(); choose(o); }, onMouseMove: function () { act[1](i); } },
              h('span', null, o.label, o.description ? h('small', null, o.description) : null), o.value === c[0] ? h(Icon, { name: 'check' }) : null);
          }) : h('li', { className: 'itk-option is-empty', role: 'presentation' }, p.emptyText || 'Tidak ada hasil')) : null),
      h('span', { className: 'itk-sr', 'aria-live': 'polite' }, open ? shown.length + ' hasil' : ''));
  }
  function Command(p) {
    var q = useState(''); var act = useState(0); var id = useId(); var listRef = useRef();
    useEffect(function () {
      if (!p.hotkey || !p.onOpenChange) return;
      function k(e) { if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') { e.preventDefault(); p.onOpenChange(true); } }
      document.addEventListener('keydown', k); return function () { document.removeEventListener('keydown', k); };
    }, [p.hotkey]);
    var flat = []; var groups = (p.groups || []).map(function (g) {
      var items = g.items.filter(function (it) { return !q[0] || (it.label + ' ' + (it.keywords || '')).toLowerCase().indexOf(q[0].toLowerCase()) >= 0; });
      items.forEach(function (it) { flat.push(it); }); return { title: g.title, items: items };
    }).filter(function (g) { return g.items.length; });
    function run(it) { it.onSelect && it.onSelect(); p.onOpenChange && p.onOpenChange(false); q[1](''); }
    var idx = -1;
    var body = h('div', { className: cx('itk-command', p.className) },
      h('div', { className: 'itk-command-search' }, h(Icon, { name: 'search' }),
        h('input', { autoFocus: !p.inline, role: 'combobox', 'aria-expanded': 'true', 'aria-controls': id + '-l', 'aria-activedescendant': flat[act[0]] ? id + '-o' + act[0] : undefined, 'aria-label': p.label || 'Cari perintah',
          placeholder: p.placeholder || 'Cari halaman atau perintah…', value: q[0], onChange: function (e) { q[1](e.target.value); act[1](0); },
          onKeyDown: function (e) {
            if (e.key === 'ArrowDown') { e.preventDefault(); act[1](Math.min(flat.length - 1, act[0] + 1)); } else if (e.key === 'ArrowUp') { e.preventDefault(); act[1](Math.max(0, act[0] - 1)); }
            else if (e.key === 'Enter' && flat[act[0]]) { e.preventDefault(); run(flat[act[0]]); }
          } }), h(I.Kbd, { keys: ['Esc'] })),
      h('div', { ref: listRef, id: id + '-l', role: 'listbox', 'aria-label': 'Hasil', className: 'itk-command-list' },
        groups.length ? groups.map(function (g) {
          return h('div', { key: g.title, role: 'group', 'aria-label': g.title }, h('div', { className: 'itk-menu-heading', 'aria-hidden': 'true' }, g.title),
            g.items.map(function (it) { idx++; var i = idx; return h('div', { key: it.label, id: id + '-o' + i, role: 'option', 'aria-selected': i === act[0], className: cx('itk-option', i === act[0] && 'is-active'), onClick: function () { run(it); }, onMouseMove: function () { act[1](i); } },
              h('span', { className: 'itk-command-item' }, it.icon ? h(Icon, { name: it.icon }) : null, it.label), it.shortcut ? h('span', { className: 'itk-menu-kbd' }, it.shortcut) : null); }));
        }) : h('p', { className: 'itk-command-empty' }, 'Tidak ada hasil untuk "' + q[0] + '".')));
    if (p.inline) return body;
    return h(Modal, { open: p.open, onClose: function () { p.onOpenChange && p.onOpenChange(false); }, className: 'itk-command-wrap' }, body);
  }

  /* ---------- Navigation ---------- */
  function Header(p) {
    return h('header', { className: cx('itk-appbar', p.largeTitle && 'is-large', p.className) },
      h('div', { className: 'itk-appbar-row' },
        h('div', { className: 'itk-appbar-lead' }, p.backHref || p.onBack ? h(p.backHref ? 'a' : 'button', { className: 'itk-back', href: p.backHref, type: p.backHref ? undefined : 'button', onClick: p.onBack },
          h(Icon, { name: 'chevronLeft' }), h('span', null, p.backLabel || 'Kembali')) : null),
        h(p.largeTitle ? 'span' : 'h1', { className: 'itk-appbar-title', 'aria-hidden': p.largeTitle ? 'true' : undefined }, p.title),
        h('div', { className: 'itk-appbar-trail' }, p.actions)),
      p.largeTitle ? h('h1', { className: 'itk-appbar-large' }, p.title) : null,
      p.children ? h('div', { className: 'itk-appbar-extra' }, p.children) : null);
  }
  function TabBar(p) {
    return h('nav', { className: cx('itk-tabbar', p.static && 'is-static', p.className), 'aria-label': p.label || 'Navigasi utama' },
      h('ul', null, (p.items || []).map(function (it) {
        return h('li', { key: it.href }, h('a', { href: it.href, 'aria-current': it.current ? 'page' : undefined, onClick: it.onClick },
          h('span', { className: 'itk-tabbar-icon' }, h(Icon, { name: it.icon }), it.badge ? h('span', { className: 'itk-tabbar-badge' }, h('span', { className: 'itk-sr' }, ', '), it.badge, h('span', { className: 'itk-sr' }, ' baru')) : null),
          h('span', { className: 'itk-tabbar-label' }, it.label)));
      })));
  }
  function BottomBar(p) { return h('div', { role: 'toolbar', 'aria-label': p.label, className: cx('itk-bottombar', p.static && 'is-static', p.className) }, p.children); }
  function FAB(p) {
    var ext = !!p.children;
    return h('button', { type: 'button', className: cx('itk-fab', ext && 'is-extended', p.static && 'is-static', p.aboveTabBar && 'is-above-tabbar', p.className), 'aria-label': ext ? undefined : p.label, onClick: p.onClick },
      h(Icon, { name: p.icon || 'plus' }), p.children);
  }
  function PageIndicator(p) {
    var n = p.count || 0;
    return h('div', { className: cx('itk-pageind', p.className), role: 'group', 'aria-label': p.label || 'Halaman' }, Array.from({ length: n }, function (_, i) {
      return h('button', { key: i, type: 'button', className: 'itk-pageind-dot', 'aria-current': i === p.current ? 'true' : undefined, 'aria-label': 'Halaman ' + (i + 1) + ' dari ' + n, onClick: function () { p.onChange && p.onChange(i); } }, h('span'));
    }));
  }
  function pages(cur, total, sib) {
    var out = [], s = Math.max(2, cur - sib), e = Math.min(total - 1, cur + sib);
    out.push(1); if (s > 2) out.push('…'); for (var i = s; i <= e; i++) out.push(i); if (e < total - 1) out.push('…'); if (total > 1) out.push(total); return out;
  }
  function Pagination(p) {
    var c = useCtrl(p.page, p.defaultPage || 1, p.onChange); var t = p.total || 1;
    function link(n, label, content, extra) {
      var dis = n < 1 || n > t;
      return h(p.href ? 'a' : 'button', Object.assign({ className: cx('itk-page', extra), 'aria-label': label, href: p.href && !dis ? p.href(n) : undefined, type: p.href ? undefined : 'button', disabled: !p.href && dis, 'aria-disabled': dis ? 'true' : undefined,
        onClick: function (e) { if (dis) { e.preventDefault(); return; } if (!p.href) c[1](n); } }), content);
    }
    return h('nav', { className: cx('itk-pagination', p.className), 'aria-label': p.label || 'Paginasi' },
      h('ul', null,
        h('li', null, link(c[0] - 1, 'Halaman sebelumnya', h(React.Fragment, null, h(Icon, { name: 'chevronLeft' }), h('span', { className: 'itk-page-word' }, 'Sebelumnya')), 'is-step')),
        h('li', { className: 'itk-page-compact', 'aria-hidden': 'true' }, c[0] + ' / ' + t),
        pages(c[0], t, p.siblings == null ? 1 : p.siblings).map(function (n, i) {
          return h('li', { key: i, className: 'itk-page-num' }, n === '…' ? h('span', { className: 'itk-page-gap', 'aria-hidden': 'true' }, '…') :
            React.cloneElement(link(n, 'Halaman ' + n, n), { 'aria-current': n === c[0] ? 'page' : undefined }));
        }),
        h('li', null, link(c[0] + 1, 'Halaman berikutnya', h(React.Fragment, null, h('span', { className: 'itk-page-word' }, 'Berikutnya'), h(Icon, { name: 'chevronRight' })), 'is-step'))));
  }
  function Sidebar(p) {
    var col = useCtrl(p.collapsed, false, p.onCollapsedChange);
    return h('nav', { className: cx('itk-sidebar', col[0] && 'is-collapsed', p.className), 'aria-label': p.label || 'Navigasi samping' },
      p.header ? h('div', { className: 'itk-sidebar-head' }, p.header) : null,
      h('div', { className: 'itk-sidebar-scroll' }, (p.groups || []).map(function (g, gi) {
        var gid = 'g' + gi;
        return h('section', { key: gi, className: 'itk-sidebar-group', 'aria-labelledby': g.title ? undefined : undefined },
          g.title ? h('h2', { className: 'itk-sidebar-title' }, g.title) : null,
          h('ul', null, g.items.map(function (it) {
            return h('li', { key: it.href }, h('a', { href: it.href, className: 'itk-sidebar-link', 'aria-current': it.current ? 'page' : undefined, title: col[0] ? it.label : undefined },
              it.icon ? h(Icon, { name: it.icon }) : null, h('span', { className: 'itk-sidebar-label' }, it.label), it.badge ? h('span', { className: 'itk-sidebar-badge' }, it.badge) : null));
          })));
      })),
      p.collapsible ? h('button', { type: 'button', className: 'itk-sidebar-toggle', 'aria-expanded': !col[0], onClick: function () { col[1](!col[0]); } },
        h(Icon, { name: 'sidebar' }), h('span', { className: 'itk-sidebar-label' }, col[0] ? 'Perluas' : 'Ciutkan')) : null);
  }
  function NavigationMenu(p) {
    var s = useState(p.defaultOpen == null ? null : p.defaultOpen); var ref = useRef(); var t = useRef();
    useDismiss(s[0] !== null, [ref], function () { s[1](null); });
    var items = p.items || [];
    return h('nav', { ref: ref, className: cx('itk-navmenu', p.className), 'aria-label': p.label || 'Utama', onMouseLeave: function () { clearTimeout(t.current); t.current = setTimeout(function () { if (p.defaultOpen == null) s[1](null); }, 250); }, onMouseEnter: function () { clearTimeout(t.current); } },
      h('ul', { className: 'itk-navmenu-bar' }, items.map(function (it, i) {
        if (!it.sections) return h('li', { key: it.label }, h('a', { className: 'itk-navmenu-top', href: it.href, 'aria-current': it.current ? 'page' : undefined }, it.label));
        var open = s[0] === i;
        return h('li', { key: it.label },
          h('button', { type: 'button', className: 'itk-navmenu-top', 'aria-expanded': open, onClick: function () { s[1](open ? null : i); },
            onMouseEnter: function (e) { if (window.matchMedia && matchMedia('(hover: hover)').matches) s[1](i); } }, it.label, h(Icon, { name: 'chevronDown' })),
          open ? h('div', { className: 'itk-navmenu-panel' }, it.sections.map(function (sec) {
            return h('div', { key: sec.title, className: 'itk-navmenu-sec' }, h('h3', null, sec.title),
              h('ul', null, sec.links.map(function (l) { return h('li', { key: l.href }, h('a', { href: l.href, className: 'itk-navmenu-link' }, h('span', null, l.label), l.description ? h('small', null, l.description) : null)); })));
          })) : null);
      })));
  }

  /* ---------- Layout & scroll ---------- */
  function ScrollArea(p) {
    return h('div', { className: cx('itk-scroll', p.orientation === 'horizontal' && 'is-x', p.className), style: { maxHeight: p.maxHeight }, tabIndex: 0, role: 'region', 'aria-label': p.label }, p.children);
  }
  function Resizable(p) {
    var vert = p.direction === 'vertical'; var c = useCtrl(p.size, p.defaultSize || 50, p.onSizeChange); var ref = useRef(); var drag = useRef(false);
    var min = p.min || 20, max = p.max || 80; var kids = React.Children.toArray(p.children);
    function clamp(v) { return Math.max(min, Math.min(max, v)); }
    function move(e) { if (!drag.current) return; var r = ref.current.getBoundingClientRect(); c[1](clamp(Math.round(vert ? (e.clientY - r.top) / r.height * 100 : (e.clientX - r.left) / r.width * 100))); }
    return h('div', { ref: ref, className: cx('itk-resizable', vert && 'is-vertical', p.className), style: { height: p.height } },
      h('div', { className: 'itk-resizable-pane', style: { flexBasis: c[0] + '%' } }, kids[0]),
      h('div', { role: 'separator', tabIndex: 0, 'aria-orientation': vert ? 'horizontal' : 'vertical', 'aria-valuenow': c[0], 'aria-valuemin': min, 'aria-valuemax': max, 'aria-label': p.label || 'Ubah ukuran panel', className: 'itk-resizable-handle',
        onPointerDown: function (e) { drag.current = true; e.currentTarget.setPointerCapture(e.pointerId); }, onPointerMove: move, onPointerUp: function () { drag.current = false; },
        onKeyDown: function (e) { var d = { ArrowLeft: -5, ArrowRight: 5, ArrowUp: -5, ArrowDown: 5 }[e.key]; if (d) { e.preventDefault(); c[1](clamp(c[0] + d)); } else if (e.key === 'Home') c[1](min); else if (e.key === 'End') c[1](max); } }, h('span')),
      h('div', { className: 'itk-resizable-pane is-fill' }, kids[1]));
  }
  function Screen(p) {
    return h('div', { className: cx('itk-screen', p.className) }, p.header || null, h('main', { className: 'itk-screen-main', id: p.mainId || 'konten' }, p.children), p.bottom || null);
  }
  function KeyboardScrollView(p) {
    var ref = useRef(); var s = useState(0);
    useEffect(function () {
      var vv = window.visualViewport; function onResize() { if (!vv) return; s[1](Math.max(0, Math.round(window.innerHeight - vv.height - vv.offsetTop))); }
      function onFocus(e) { var el = e.target; if (!/INPUT|TEXTAREA|SELECT/.test(el.tagName)) return; setTimeout(function () { el.scrollIntoView({ block: 'center', behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' }); }, 250); }
      vv && vv.addEventListener('resize', onResize); ref.current.addEventListener('focusin', onFocus);
      var node = ref.current;
      return function () { vv && vv.removeEventListener('resize', onResize); node.removeEventListener('focusin', onFocus); };
    }, []);
    return h('div', { ref: ref, className: cx('itk-kbscroll', p.className), style: { paddingBottom: 'calc(' + s[0] + 'px + var(--itk-space-4))', maxHeight: p.maxHeight } }, p.children);
  }
  function InputAccessoryBar(p) {
    var s = useState(0);
    useEffect(function () {
      var vv = window.visualViewport; if (!vv || !p.floating) return;
      function f() { s[1](Math.max(0, window.innerHeight - vv.height - vv.offsetTop)); } vv.addEventListener('resize', f); vv.addEventListener('scroll', f); f();
      return function () { vv.removeEventListener('resize', f); vv.removeEventListener('scroll', f); };
    }, [p.floating]);
    return h('div', { role: 'toolbar', 'aria-label': p.label || 'Alat input', className: cx('itk-accbar', p.floating && 'is-floating', p.className), style: p.floating ? { bottom: s[0] } : undefined },
      h('div', { className: 'itk-accbar-nav' },
        h(Button, { variant: 'ghost', size: 'sm', icon: 'chevronUp', label: 'Kolom sebelumnya', onClick: p.onPrev, disabled: !p.onPrev, onMouseDown: function (e) { e.preventDefault(); } }),
        h(Button, { variant: 'ghost', size: 'sm', icon: 'chevronDown', label: 'Kolom berikutnya', onClick: p.onNext, disabled: !p.onNext, onMouseDown: function (e) { e.preventDefault(); } })),
      h('div', { className: 'itk-accbar-mid' }, p.children),
      h(Button, { variant: 'ghost', size: 'sm', onClick: p.onDone, onMouseDown: function (e) { e.preventDefault(); } }, p.doneLabel || 'Selesai'));
  }
  function Direction(p) { return h('div', { dir: p.dir || 'ltr', lang: p.lang, className: cx('itk-direction', p.className) }, p.children); }

  /* ---------- Carousel ---------- */
  function Carousel(p) {
    var ref = useRef(); var s = useState(0); var n = React.Children.count(p.children); var id = useId();
    function go(i) { var el = ref.current; var slide = el && el.children[i]; if (slide) el.scrollTo({ left: slide.offsetLeft - el.offsetLeft, behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' }); }
    function onScroll() { var el = ref.current; var w = el.children[0] ? el.children[0].getBoundingClientRect().width : 1; s[1](Math.round(el.scrollLeft / w)); }
    return h('section', { className: cx('itk-carousel', p.className), 'aria-roledescription': 'carousel', 'aria-label': p.label },
      h('div', { ref: ref, className: 'itk-carousel-track', onScroll: onScroll, id: id }, React.Children.map(p.children, function (ch, i) {
        return h('div', { className: 'itk-carousel-slide', role: 'group', 'aria-roledescription': 'slide', 'aria-label': (i + 1) + ' dari ' + n, style: p.peek ? { flexBasis: 'calc(100% - 48px)' } : undefined }, ch);
      })),
      h('div', { className: 'itk-carousel-ctl' },
        h(Button, { variant: 'secondary', size: 'sm', icon: 'chevronLeft', label: 'Sebelumnya', 'aria-controls': id, disabled: s[0] <= 0, onClick: function () { go(s[0] - 1); } }),
        h(PageIndicator, { count: n, current: s[0], onChange: go, label: p.label }),
        h(Button, { variant: 'secondary', size: 'sm', icon: 'chevronRight', label: 'Berikutnya', 'aria-controls': id, disabled: s[0] >= n - 1, onClick: function () { go(s[0] + 1); } })));
  }

  Object.assign(I, { Modal: Modal, Dialog: Dialog, AlertDialog: AlertDialog, AlertSheet: AlertSheet, Sheet: Sheet, Drawer: Drawer, FullscreenMenu: FullscreenMenu,
    Popover: Popover, Tooltip: Tooltip, HoverCard: HoverCard, MenuList: MenuList, DropdownMenu: DropdownMenu, ContextMenu: ContextMenu, Menubar: Menubar,
    Select: Select, Combobox: Combobox, Command: Command, Header: Header, TabBar: TabBar, BottomBar: BottomBar, FAB: FAB, PageIndicator: PageIndicator,
    Pagination: Pagination, Sidebar: Sidebar, NavigationMenu: NavigationMenu, ScrollArea: ScrollArea, Resizable: Resizable, Screen: Screen,
    KeyboardScrollView: KeyboardScrollView, InputAccessoryBar: InputAccessoryBar, Direction: Direction, Carousel: Carousel });
})();

(function () {
  var I = window.ITK, React = window.React, h = React.createElement;
  var useState = React.useState, useRef = React.useRef, useEffect = React.useEffect, useLayoutEffect = React.useLayoutEffect, useId = React.useId;
  var cx = I.cx, Icon = I.Icon, Button = I.Button, H = I._h, useCtrl = H.useCtrl;

  /* ---------- Haptics ---------- */
  var haptics = {
    enabled: true,
    supported: function () { return typeof navigator !== 'undefined' && typeof navigator.vibrate === 'function'; },
    play: function (pattern) { if (!haptics.enabled || !haptics.supported()) return false; try { return navigator.vibrate(pattern); } catch (e) { return false; } },
    selection: function () { return haptics.play(8); },
    impact: function () { return haptics.play(14); },
    success: function () { return haptics.play([12, 60, 18]); },
    warning: function () { return haptics.play([20, 80, 20]); },
    error: function () { return haptics.play([24, 50, 24, 50, 24]); }
  };

  /* ---------- Calendar ---------- */
  function sameDay(a, b) { return a && b && a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth() && a.getDate() === b.getDate(); }
  function addDays(d, n) { var x = new Date(d); x.setDate(x.getDate() + n); return x; }
  function Calendar(p) {
    var locale = p.locale || 'id-ID'; var start = p.weekStartsOn == null ? 1 : p.weekStartsOn;
    var c = useCtrl(p.value, p.defaultValue || null, p.onChange);
    var today = p.today || new Date(); var f = useState(c[0] || today); var focus = f[0];
    var month = useState(new Date((c[0] || today).getFullYear(), (c[0] || today).getMonth(), 1)); var m = month[0];
    var grid = useRef(); var id = useId(); var moved = useRef(false);
    useEffect(function () { if (!moved.current) return; var b = grid.current && grid.current.querySelector('[data-focus=true]'); b && b.focus(); }, [focus]);
    function disabled(d) { return (p.min && d < p.min && !sameDay(d, p.min)) || (p.max && d > p.max && !sameDay(d, p.max)) || (p.isDisabled && p.isDisabled(d)); }
    function setFocus(d) { moved.current = true; f[1](d); if (d.getMonth() !== m.getMonth() || d.getFullYear() !== m.getFullYear()) month[1](new Date(d.getFullYear(), d.getMonth(), 1)); }
    var first = new Date(m); var off = (first.getDay() - start + 7) % 7; var gStart = addDays(first, -off);
    var weeks = []; for (var w = 0; w < 6; w++) { var row = []; for (var d = 0; d < 7; d++) row.push(addDays(gStart, w * 7 + d)); weeks.push(row); }
    var dayNames = []; for (var k = 0; k < 7; k++) { var dd = addDays(gStart, k); dayNames.push({ s: dd.toLocaleDateString(locale, { weekday: 'short' }), l: dd.toLocaleDateString(locale, { weekday: 'long' }) }); }
    var title = m.toLocaleDateString(locale, { month: 'long', year: 'numeric' });
    function key(e, d) {
      var map = { ArrowLeft: -1, ArrowRight: 1, ArrowUp: -7, ArrowDown: 7 }; if (map[e.key] != null) { e.preventDefault(); setFocus(addDays(d, map[e.key])); }
      else if (e.key === 'PageUp') { e.preventDefault(); var x = new Date(d); x.setMonth(x.getMonth() - 1); setFocus(x); } else if (e.key === 'PageDown') { e.preventDefault(); var y = new Date(d); y.setMonth(y.getMonth() + 1); setFocus(y); }
      else if (e.key === 'Home') { e.preventDefault(); setFocus(addDays(d, -((d.getDay() - start + 7) % 7))); } else if (e.key === 'End') { e.preventDefault(); setFocus(addDays(d, 6 - (d.getDay() - start + 7) % 7)); }
    }
    function shift(n) { var x = new Date(m); x.setMonth(x.getMonth() + n); month[1](x); f[1](new Date(x)); }
    return h('div', { className: cx('itk-cal', p.className) },
      h('div', { className: 'itk-cal-head' },
        h('h2', { id: id + '-t', className: 'itk-cal-title', 'aria-live': 'polite' }, title),
        h('div', null, h(Button, { variant: 'ghost', size: 'sm', icon: 'chevronLeft', label: 'Bulan sebelumnya', onClick: function () { shift(-1); } }),
          h(Button, { variant: 'ghost', size: 'sm', icon: 'chevronRight', label: 'Bulan berikutnya', onClick: function () { shift(1); } }))),
      h('table', { ref: grid, role: 'grid', 'aria-labelledby': id + '-t', className: 'itk-cal-grid' },
        h('thead', null, h('tr', null, dayNames.map(function (n) { return h('th', { key: n.l, scope: 'col', abbr: n.l }, n.s.replace('.', '')); }))),
        h('tbody', null, weeks.map(function (row, ri) {
          return h('tr', { key: ri }, row.map(function (d) {
            var out = d.getMonth() !== m.getMonth(), sel = sameDay(d, c[0]), isT = sameDay(d, today), dis = disabled(d), foc = sameDay(d, focus), mark = p.marked && p.marked(d);
            return h('td', { key: d.toISOString(), role: 'gridcell', 'aria-selected': sel ? 'true' : undefined },
              h('button', { type: 'button', tabIndex: foc ? 0 : -1, 'data-focus': foc, className: cx('itk-cal-day', out && 'is-out', sel && 'is-sel', isT && 'is-today'), disabled: dis,
                'aria-label': d.toLocaleDateString(locale, { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }) + (isT ? ', hari ini' : '') + (mark ? ', ada agenda' : ''),
                'aria-current': isT ? 'date' : undefined, onClick: function () { c[1](d); f[1](d); }, onKeyDown: function (e) { key(e, d); } }, d.getDate(), mark ? h('span', { className: 'itk-cal-dot', 'aria-hidden': 'true' }) : null));
          }));
        }))));
  }

  /* ---------- Wheel picker ---------- */
  var ROW = 40;
  function WheelColumn(p) {
    var ref = useRef(); var t = useRef(); var opts = p.options; var idx = Math.max(0, opts.findIndex(function (o) { return String(o.value) === String(p.value); }));
    useLayoutEffect(function () { if (ref.current && Math.abs(ref.current.scrollTop - idx * ROW) > 2) ref.current.scrollTop = idx * ROW; }, [idx]);
    function onScroll() { clearTimeout(t.current); t.current = setTimeout(function () { var i = Math.round(ref.current.scrollTop / ROW); i = Math.max(0, Math.min(opts.length - 1, i)); if (i !== idx) { p.onChange(opts[i].value); I.haptics.selection(); } }, 90); }
    function key(e) { var d = { ArrowUp: -1, ArrowDown: 1, PageUp: -5, PageDown: 5 }[e.key]; if (d) { e.preventDefault(); var i = Math.max(0, Math.min(opts.length - 1, idx + d)); p.onChange(opts[i].value); } }
    return h('div', { ref: ref, className: 'itk-wheel-col', role: 'listbox', tabIndex: 0, 'aria-label': p.label, 'aria-activedescendant': p.id + '-' + idx, onScroll: onScroll, onKeyDown: key },
      h('div', { className: 'itk-wheel-pad' }),
      opts.map(function (o, i) { return h('div', { key: o.value, id: p.id + '-' + i, role: 'option', 'aria-selected': i === idx, className: cx('itk-wheel-opt', i === idx && 'is-sel'), onClick: function () { p.onChange(o.value); } }, o.label); }),
      h('div', { className: 'itk-wheel-pad' }));
  }
  function WheelPicker(p) {
    var id = useId(); var cols = p.columns || [];
    return h('div', { className: cx('itk-wheel', p.className), role: 'group', 'aria-label': p.label },
      h('div', { className: 'itk-wheel-band', 'aria-hidden': 'true' }),
      cols.map(function (col, i) { return h(WheelColumn, { key: i, id: id + '-c' + i, label: col.label, options: col.options.map(function (o) { return typeof o === 'object' ? o : { value: o, label: String(o) }; }), value: col.value, onChange: col.onChange }); }));
  }

  /* ---------- Chart ---------- */
  var SERIES = ['var(--itk-chart-1)', 'var(--itk-chart-2)', 'var(--itk-chart-3)', 'var(--itk-chart-4)'];
  function niceMax(v) { if (v <= 0) return 1; var e = Math.pow(10, Math.floor(Math.log10(v))); var n = v / e; return (n <= 1 ? 1 : n <= 2 ? 2 : n <= 2.5 ? 2.5 : n <= 5 ? 5 : 10) * e; }
  function Chart(p) {
    var wrap = useRef(); var w = useState(600); var tip = useState(null); var id = useId();
    useEffect(function () { var ro = new ResizeObserver(function (en) { w[1](Math.max(280, Math.round(en[0].contentRect.width))); }); ro.observe(wrap.current); return function () { ro.disconnect(); }; }, []);
    var data = p.data || [], series = p.series || [], type = p.type || 'bar', H_ = p.height || 240, W = w[0];
    var pad = { l: 44, r: 12, t: 12, b: 32 }; var pw = W - pad.l - pad.r, ph = H_ - pad.t - pad.b;
    var max = niceMax(p.max || Math.max.apply(null, data.map(function (d) { return Math.max.apply(null, series.map(function (s) { return +d[s.key] || 0; })); }).concat([0])));
    var ticks = [0, .25, .5, .75, 1].map(function (f) { return max * f; }); var fmt = p.format || function (v) { return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 1 }).format(v); };
    var band = pw / Math.max(1, data.length); var y = function (v) { return pad.t + ph - v / max * ph; };
    var marks = [];
    if (type === 'bar') {
      var bw = Math.min(32, band * 0.7 / series.length);
      data.forEach(function (d, i) { series.forEach(function (s, j) {
        var x = pad.l + band * i + (band - bw * series.length) / 2 + j * bw; var v = +d[s.key] || 0;
        marks.push(h('rect', { key: i + '-' + j, x: x + 1, y: y(v), width: Math.max(2, bw - 2), height: Math.max(0, pad.t + ph - y(v)), rx: 3, style: { fill: SERIES[j % 4] },
          onMouseEnter: function () { tip[1]({ x: x + bw / 2, y: y(v), t: d[p.x] + ' · ' + s.label + ': ' + fmt(v) }); }, onMouseLeave: function () { tip[1](null); } }));
      }); });
    } else {
      series.forEach(function (s, j) {
        var pts = data.map(function (d, i) { return [pad.l + band * i + band / 2, y(+d[s.key] || 0)]; });
        marks.push(h('polyline', { key: 'l' + j, points: pts.map(function (q) { return q.join(','); }).join(' '), style: { fill: 'none', stroke: SERIES[j % 4], strokeWidth: 2.5, strokeLinejoin: 'round', strokeDasharray: j === 1 ? '6 4' : undefined } }));
        pts.forEach(function (q, i) { marks.push(h('circle', { key: j + 'c' + i, cx: q[0], cy: q[1], r: 4, style: { fill: 'var(--itk-surface)', stroke: SERIES[j % 4], strokeWidth: 2 },
          onMouseEnter: function () { tip[1]({ x: q[0], y: q[1], t: data[i][p.x] + ' · ' + s.label + ': ' + fmt(+data[i][s.key] || 0) }); }, onMouseLeave: function () { tip[1](null); } })); });
      });
    }
    var every = Math.ceil(data.length / Math.max(1, Math.floor(pw / 56)));
    return h('figure', { className: cx('itk-chart', p.className), 'aria-labelledby': id + '-t', 'aria-describedby': id + '-s' },
      h('figcaption', null, h('span', { id: id + '-t', className: 'itk-chart-title' }, p.title), p.summary ? h('span', { id: id + '-s', className: 'itk-chart-sum' }, p.summary) : null),
      series.length > 1 ? h('ul', { className: 'itk-chart-legend' }, series.map(function (s, j) { return h('li', { key: s.key }, h('span', { className: 'itk-chart-key', style: { background: SERIES[j % 4] }, 'data-type': type, 'data-i': j }), s.label); })) : null,
      h('div', { ref: wrap, className: 'itk-chart-plot' },
        h('svg', { width: W, height: H_, viewBox: '0 0 ' + W + ' ' + H_, 'aria-hidden': 'true' },
          ticks.map(function (t) { return h('g', { key: t }, h('line', { x1: pad.l, x2: W - pad.r, y1: y(t), y2: y(t), className: t === 0 ? 'itk-chart-base' : 'itk-chart-grid' }), h('text', { x: pad.l - 8, y: y(t) + 4, textAnchor: 'end', className: 'itk-chart-tick' }, fmt(t))); }),
          data.map(function (d, i) { return i % every ? null : h('text', { key: i, x: pad.l + band * i + band / 2, y: H_ - 10, textAnchor: 'middle', className: 'itk-chart-tick' }, d[p.x]); }),
          marks),
        tip[0] ? h('div', { className: 'itk-chart-tip', style: { left: tip[0].x, top: tip[0].y } }, tip[0].t) : null),
      h('table', { className: 'itk-sr' }, h('caption', null, p.title), h('thead', null, h('tr', null, h('th', null, p.xLabel || ''), series.map(function (s) { return h('th', { key: s.key }, s.label); }))),
        h('tbody', null, data.map(function (d, i) { return h('tr', { key: i }, h('th', null, d[p.x]), series.map(function (s) { return h('td', { key: s.key }, fmt(+d[s.key] || 0)); })); }))));
  }

  /* ---------- Chat ---------- */
  function Bubble(p) {
    var me = p.from === 'me';
    return h('div', { className: cx('itk-bubble', me ? 'is-me' : 'is-them', p.tone && 'is-' + p.tone, p.className) }, p.children);
  }
  function Message(p) {
    var me = p.from === 'me';
    return h('article', { className: cx('itk-msg', me && 'is-me', p.grouped && 'is-grouped', p.className), 'aria-label': (me ? 'Anda' : p.author) + (p.time ? ', ' + p.time : '') },
      !me && !p.grouped ? h(I.Avatar, { name: p.author, src: p.avatar, size: 'sm' }) : h('span', { className: 'itk-msg-gap' }),
      h('div', { className: 'itk-msg-col' },
        !me && !p.grouped ? h('div', { className: 'itk-msg-meta' }, h('span', { className: 'itk-msg-author' }, p.author), p.role ? h('span', { className: 'itk-msg-role' }, p.role) : null) : null,
        h(Bubble, { from: p.from }, p.children),
        p.attachments ? h('div', { className: 'itk-msg-att' }, p.attachments) : null,
        p.time || p.status ? h('div', { className: 'itk-msg-foot' }, p.time ? h('time', null, p.time) : null, p.status ? h('span', null, ' · ' + p.status) : null) : null));
  }
  function MessageScroller(p) {
    var ref = useRef(); var stick = useRef(true); var s = useState(0); var count = React.Children.count(p.children);
    function near() { var el = ref.current; return el.scrollHeight - el.scrollTop - el.clientHeight < 48; }
    useLayoutEffect(function () { var el = ref.current; if (stick.current) el.scrollTop = el.scrollHeight; else s[1](function (n) { return n + 1; }); }, [count]);
    return h('div', { className: cx('itk-msgs-wrap', p.className), style: { height: p.height } },
      h('div', { ref: ref, className: 'itk-msgs', role: 'log', 'aria-live': 'polite', 'aria-label': p.label || 'Percakapan', tabIndex: 0, onScroll: function () { stick.current = near(); if (stick.current) s[1](0); } },
        p.children),
      s[0] > 0 ? h('button', { type: 'button', className: 'itk-msgs-jump', onClick: function () { var el = ref.current; el.scrollTo({ top: el.scrollHeight, behavior: 'smooth' }); s[1](0); } },
        h(Icon, { name: 'chevronDown' }), s[0] + ' pesan baru') : null);
  }
  function fmtSize(b) { if (b == null) return ''; var u = ['B', 'KB', 'MB', 'GB'], i = 0; while (b >= 1024 && i < 3) { b /= 1024; i++; } return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 1 }).format(b) + ' ' + u[i]; }
  function Attachment(p) {
    var ext = (p.name || '').split('.').pop().toUpperCase().slice(0, 4);
    var busy = p.progress != null && p.progress < 100;
    var Tag = p.href && !busy ? 'a' : 'div';
    return h('div', { className: cx('itk-attach', p.error && 'is-error', p.className) },
      h(Tag, { className: 'itk-attach-main', href: Tag === 'a' ? p.href : undefined, download: Tag === 'a' ? '' : undefined },
        h('span', { className: 'itk-attach-ext', 'aria-hidden': 'true' }, ext || h(Icon, { name: 'file' })),
        h('span', { className: 'itk-attach-text' }, h('span', { className: 'itk-attach-name' }, p.name),
          h('span', { className: 'itk-attach-meta' }, p.error ? h(React.Fragment, null, h(Icon, { name: 'danger' }), p.error) : busy ? 'Mengunggah ' + p.progress + '%' : fmtSize(p.size)))),
      busy ? h('span', { className: 'itk-attach-bar', role: 'progressbar', 'aria-label': 'Unggah ' + p.name, 'aria-valuenow': p.progress, 'aria-valuemin': 0, 'aria-valuemax': 100 }, h('span', { style: { width: p.progress + '%' } })) : null,
      p.onRemove ? h('button', { type: 'button', className: 'itk-attach-x', 'aria-label': (busy ? 'Batalkan ' : 'Hapus ') + p.name, onClick: p.onRemove }, h(Icon, { name: 'x' })) : null);
  }

  /* ---------- Keypad, Pin pad ---------- */
  function Keypad(p) {
    function press(k) { I.haptics.selection(); p.onKey && p.onKey(k); }
    var keys = ['1', '2', '3', '4', '5', '6', '7', '8', '9'];
    return h('div', { className: cx('itk-keypad', p.className), role: 'group', 'aria-label': p.label || 'Papan angka' },
      keys.map(function (k) { return h('button', { key: k, type: 'button', className: 'itk-key', onClick: function () { press(k); }, disabled: p.disabled }, k); }),
      p.extraKey ? h('button', { type: 'button', className: 'itk-key is-fn', onClick: function () { press(p.extraKey.value); }, 'aria-label': p.extraKey.label }, p.extraKey.content || p.extraKey.label) : h('span'),
      h('button', { type: 'button', className: 'itk-key', onClick: function () { press('0'); }, disabled: p.disabled }, '0'),
      h('button', { type: 'button', className: 'itk-key is-fn', 'aria-label': 'Hapus', onClick: function () { I.haptics.selection(); p.onDelete && p.onDelete(); }, disabled: p.disabled }, h(Icon, { name: 'backspace' })));
  }
  function PinPad(p) {
    var n = p.length || 6; var s = useState(''); var ref = useRef(); var id = useId();
    function add(k) { if (s[0].length >= n) return; var v = s[0] + k; s[1](v); if (v.length === n) { p.onComplete && p.onComplete(v); if (p.clearOnComplete !== false) setTimeout(function () { s[1](''); }, 300); } }
    function del() { s[1](s[0].slice(0, -1)); }
    useEffect(function () { if (p.error) I.haptics.error(); }, [p.error]);
    var dots = []; for (var i = 0; i < n; i++) dots.push(h('span', { key: i, className: cx('itk-pin-dot', i < s[0].length && 'is-on') }));
    return h('div', { ref: ref, className: cx('itk-pinpad', p.error && 'is-error', p.className), tabIndex: 0, 'aria-labelledby': id + '-t', 'aria-describedby': id + '-s',
      onKeyDown: function (e) { if (/^\d$/.test(e.key)) { e.preventDefault(); add(e.key); } else if (e.key === 'Backspace') { e.preventDefault(); del(); } } },
      h('p', { id: id + '-t', className: 'itk-pin-title' }, p.title || 'Masukkan PIN'),
      h('div', { className: 'itk-pin-dots', 'aria-hidden': 'true' }, dots),
      h('p', { id: id + '-s', className: cx('itk-pin-status', p.error && 'is-error'), 'aria-live': 'polite' }, p.error || (s[0].length + ' dari ' + n + ' digit')),
      h(Keypad, { onKey: add, onDelete: del, extraKey: p.extraKey }));
  }

  /* ---------- QR scanner ---------- */
  function QRScanner(p) {
    var st = useState(p.initialState || 'idle'); var video = useRef(); var stream = useRef(); var raf = useRef(); var manual = useState('');
    var supported = typeof window !== 'undefined' && 'BarcodeDetector' in window && navigator.mediaDevices;
    function stop() { cancelAnimationFrame(raf.current); if (stream.current) stream.current.getTracks().forEach(function (t) { t.stop(); }); stream.current = null; }
    useEffect(function () { return stop; }, []);
    function start() {
      if (!supported) { st[1]('unsupported'); return; }
      st[1]('starting');
      navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } }).then(function (s) {
        stream.current = s; video.current.srcObject = s; video.current.play(); st[1]('scanning');
        var det = new window.BarcodeDetector({ formats: ['qr_code'] });
        (function tick() { det.detect(video.current).then(function (codes) { if (codes.length) { stop(); st[1]('done'); I.haptics.success(); p.onResult && p.onResult(codes[0].rawValue); } else raf.current = requestAnimationFrame(tick); }).catch(function () { raf.current = requestAnimationFrame(tick); }); })();
      }).catch(function () { st[1]('denied'); });
    }
    var state = st[0];
    var msg = { idle: p.prompt || 'Arahkan kamera ke kode QR presensi.', starting: 'Membuka kamera…', scanning: 'Mencari kode QR…', done: 'Kode terbaca.', denied: 'Akses kamera ditolak. Izinkan kamera di pengaturan browser, atau masukkan kode secara manual.', unsupported: 'Browser ini belum bisa memindai QR. Masukkan kode secara manual.' }[state];
    return h('div', { className: cx('itk-qr', p.className) },
      h('div', { className: cx('itk-qr-view', 'is-' + state) },
        h('video', { ref: video, playsInline: true, muted: true, 'aria-hidden': 'true' }),
        h('span', { className: 'itk-qr-frame', 'aria-hidden': 'true' }, h('i'), h('i'), h('i'), h('i')),
        state === 'idle' || state === 'denied' || state === 'unsupported' ? h('span', { className: 'itk-qr-icon', 'aria-hidden': 'true' }, h(Icon, { name: 'qr' })) : null),
      h('p', { className: 'itk-qr-msg', role: 'status' }, msg),
      state === 'idle' ? h(Button, { icon: 'camera', onClick: start }, p.startLabel || 'Buka kamera') : null,
      state === 'scanning' ? h(Button, { variant: 'secondary', onClick: function () { stop(); st[1]('idle'); } }, 'Batal') : null,
      h('form', { className: 'itk-qr-manual', onSubmit: function (e) { e.preventDefault(); if (manual[0]) p.onResult && p.onResult(manual[0]); } },
        h(I.InputGroup, { label: 'Atau masukkan kode', placeholder: 'contoh: ITK-PRS-4821', value: manual[0], onChange: function (e) { manual[1](e.target.value); }, autoCapitalize: 'characters',
          suffix: h(Button, { type: 'submit', size: 'sm', variant: 'ghost' }, 'Kirim') })));
  }

  /* ---------- Signature pad ---------- */
  function SignaturePad(p) {
    var cv = useRef(); var strokes = useRef([]); var cur = useRef(null); var s = useState(0); var typed = useState(''); var mode = useState('draw'); var id = useId();
    function ctx() { return cv.current.getContext('2d'); }
    function redraw() {
      var c = cv.current; var r = c.getBoundingClientRect(); var dpr = window.devicePixelRatio || 1; c.width = r.width * dpr; c.height = r.height * dpr;
      var x = ctx(); x.setTransform(dpr, 0, 0, dpr, 0, 0); x.lineWidth = 2.2; x.lineCap = 'round'; x.lineJoin = 'round'; x.strokeStyle = getComputedStyle(c).color;
      strokes.current.forEach(function (st) { x.beginPath(); st.forEach(function (pt, i) { i ? x.lineTo(pt[0], pt[1]) : x.moveTo(pt[0], pt[1]); }); x.stroke(); });
    }
    useEffect(function () { if (mode[0] !== 'draw') return; redraw(); var ro = new ResizeObserver(redraw); ro.observe(cv.current); return function () { ro.disconnect(); }; }, [mode[0]]);
    function pt(e) { var r = cv.current.getBoundingClientRect(); return [e.clientX - r.left, e.clientY - r.top]; }
    function emit() { s[1](strokes.current.length); p.onChange && p.onChange(strokes.current.length ? cv.current.toDataURL('image/png') : null); }
    return h('div', { className: cx('itk-field itk-sig', p.className) },
      h('div', { className: 'itk-slider-head' }, h('span', { className: 'itk-field-label', id: id }, p.label || 'Tanda tangan'),
        h('button', { type: 'button', className: 'itk-link-btn', onClick: function () { mode[1](mode[0] === 'draw' ? 'type' : 'draw'); } }, mode[0] === 'draw' ? 'Ketik nama saja' : 'Gambar tanda tangan')),
      mode[0] === 'draw' ? h('div', { className: 'itk-sig-pad' },
        h('canvas', { ref: cv, role: 'img', 'aria-labelledby': id, 'aria-describedby': id + '-h', className: 'itk-sig-canvas',
          onPointerDown: function (e) { e.currentTarget.setPointerCapture(e.pointerId); cur.current = [pt(e)]; strokes.current.push(cur.current); },
          onPointerMove: function (e) { if (!cur.current) return; cur.current.push(pt(e)); var x = ctx(); var a = cur.current[cur.current.length - 2], b = cur.current[cur.current.length - 1]; x.beginPath(); x.moveTo(a[0], a[1]); x.lineTo(b[0], b[1]); x.stroke(); },
          onPointerUp: function () { cur.current = null; emit(); } }),
        h('span', { className: 'itk-sig-line', 'aria-hidden': 'true' }),
        !s[0] ? h('span', { className: 'itk-sig-ph', 'aria-hidden': 'true' }, 'Tanda tangan di sini') : null)
        : h('input', { className: 'itk-input itk-sig-typed', 'aria-labelledby': id, value: typed[0], placeholder: 'Nama lengkap', onChange: function (e) { typed[1](e.target.value); p.onChange && p.onChange(e.target.value ? { typed: e.target.value } : null); } }),
      h('div', { className: 'itk-sig-actions' }, h('span', { className: 'itk-field-hint', id: id + '-h' }, mode[0] === 'draw' ? 'Gunakan jari, stylus, atau mouse.' : 'Nama yang diketik berlaku sebagai tanda tangan elektronik.'),
        mode[0] === 'draw' ? h('div', null,
          h(Button, { variant: 'ghost', size: 'sm', disabled: !s[0], onClick: function () { strokes.current.pop(); redraw(); emit(); } }, 'Urungkan'),
          h(Button, { variant: 'ghost', size: 'sm', disabled: !s[0], onClick: function () { strokes.current = []; redraw(); emit(); } }, 'Hapus')) : null));
  }

  /* ---------- Questionnaire ---------- */
  function Questionnaire(p) {
    var qs = p.questions || []; var step = useState(0); var ans = useState(p.defaultAnswers || {}); var err = useState(null); var done = useState(false); var head = useRef();
    var q = qs[step[0]]; var a = ans[0];
    useEffect(function () { if (head.current && step[0] > 0) head.current.focus(); }, [step[0]]);
    function set(v) { var n = Object.assign({}, a); n[q.id] = v; ans[1](n); err[1](null); }
    function valid() { var v = a[q.id]; return !q.required || (v != null && v !== '' && !(Array.isArray(v) && !v.length)); }
    function next() { if (!valid()) { err[1](q.errorText || 'Jawaban ini wajib diisi.'); return; } if (step[0] < qs.length - 1) step[1](step[0] + 1); else { done[1](true); p.onSubmit && p.onSubmit(a); } }
    if (done[0]) return h('div', { className: cx('itk-quest', p.className) }, h(I.Empty, { icon: 'success', title: p.doneTitle || 'Terima kasih', titleAs: 'h2' }, p.doneText || 'Jawaban Anda sudah kami terima.'));
    var body;
    if (q.type === 'single' || q.type === 'scale') body = q.type === 'scale'
      ? h(I.ToggleGroup, { label: q.title, block: true, value: a[q.id] == null ? '' : String(a[q.id]), onChange: function (v) { set(v); }, options: (q.options || [1, 2, 3, 4, 5]).map(function (o) { return { value: String(o), label: String(o) }; }) })
      : h(I.RadioGroup, { label: h('span', { className: 'itk-sr' }, q.title), value: a[q.id], onChange: set, options: q.options.map(function (o) { return typeof o === 'string' ? { value: o, label: o } : o; }) });
    else if (q.type === 'multiple') body = h('fieldset', { className: 'itk-fieldset' }, h('legend', { className: 'itk-sr' }, q.title), q.options.map(function (o) {
      var v = a[q.id] || []; return h(I.Checkbox, { key: o, label: o, checked: v.indexOf(o) >= 0, onChange: function (e) { set(e.target.checked ? v.concat([o]) : v.filter(function (x) { return x !== o; })); } }); }));
    else body = h(I.Textarea, { label: h('span', { className: 'itk-sr' }, q.title), value: a[q.id] || '', onChange: function (e) { set(e.target.value); }, maxLength: q.maxLength, showCount: !!q.maxLength });
    return h('form', { className: cx('itk-quest', p.className), onSubmit: function (e) { e.preventDefault(); next(); }, noValidate: true, 'aria-labelledby': 'q-' + q.id },
      h(I.Progress, { label: 'Pertanyaan ' + (step[0] + 1) + ' dari ' + qs.length, value: step[0] + 1, max: qs.length, showValue: false }),
      h('h2', { id: 'q-' + q.id, ref: head, tabIndex: -1, className: 'itk-quest-q' }, q.title, q.required ? h('span', { className: 'itk-req', 'aria-hidden': 'true' }, '*') : null),
      q.description ? h('p', { className: 'itk-field-hint' }, q.description) : null,
      q.type === 'scale' && q.scaleLabels ? h('div', { className: 'itk-quest-scale' }, h('span', null, q.scaleLabels[0]), h('span', null, q.scaleLabels[1])) : null,
      body,
      err[0] ? h('p', { className: 'itk-field-error', role: 'alert' }, h(Icon, { name: 'danger' }), err[0]) : null,
      h('div', { className: 'itk-quest-nav' },
        step[0] > 0 ? h(Button, { variant: 'secondary', onClick: function () { step[1](step[0] - 1); err[1](null); } }, 'Kembali') : h('span'),
        h(Button, { type: 'submit' }, step[0] === qs.length - 1 ? (p.submitLabel || 'Kirim jawaban') : 'Lanjut')));
  }

  /* ---------- Trace ---------- */
  var TRACE_ICON = { done: 'check', current: null, pending: null, error: 'x' };
  var TRACE_WORD = { done: 'Selesai', current: 'Sedang berjalan', pending: 'Menunggu', error: 'Gagal' };
  function Trace(p) {
    return h('ol', { className: cx('itk-trace', p.className), 'aria-label': p.label }, (p.steps || []).map(function (s, i) {
      return h('li', { key: i, className: cx('itk-trace-step', 'is-' + (s.status || 'pending')), 'aria-current': s.status === 'current' ? 'step' : undefined },
        h('span', { className: 'itk-trace-node', 'aria-hidden': 'true' }, TRACE_ICON[s.status] ? h(Icon, { name: TRACE_ICON[s.status] }) : null),
        h('div', { className: 'itk-trace-body' },
          h('div', { className: 'itk-trace-top' }, h('span', { className: 'itk-trace-title' }, s.title), h('span', { className: 'itk-sr' }, ', ' + TRACE_WORD[s.status || 'pending']), s.time ? h('time', { className: 'itk-trace-time' }, s.time) : null),
          s.detail ? (s.collapsible ? h(I.Collapsible, { title: s.detailTitle || 'Rincian' }, s.detail) : h('div', { className: 'itk-trace-detail' }, s.detail)) : null));
    }));
  }

  /* ---------- Widget ---------- */
  function Widget(p) {
    var Tag = p.href ? 'a' : 'section';
    return h(Tag, { href: p.href, className: cx('itk-widget', 'is-' + (p.size || 'small'), p.brand && 'is-brand', p.className), 'aria-label': p.href ? undefined : p.title },
      h('div', { className: 'itk-widget-head' }, p.icon ? h(Icon, { name: p.icon }) : null, h('span', null, p.title)),
      p.value != null ? h('div', { className: 'itk-widget-value' }, p.value, p.unit ? h('span', null, p.unit) : null) : null,
      p.caption ? h('div', { className: 'itk-widget-cap' }, p.caption) : null,
      p.children ? h('div', { className: 'itk-widget-body' }, p.children) : null,
      p.updated ? h('div', { className: 'itk-widget-upd' }, p.updated) : null);
  }

  Object.assign(I, { haptics: haptics, Calendar: Calendar, WheelPicker: WheelPicker, Chart: Chart, Bubble: Bubble, Message: Message, MessageScroller: MessageScroller,
    Attachment: Attachment, Keypad: Keypad, PinPad: PinPad, QRScanner: QRScanner, SignaturePad: SignaturePad, Questionnaire: Questionnaire, Trace: Trace, Widget: Widget });
})();
