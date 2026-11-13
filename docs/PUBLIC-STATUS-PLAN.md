# Public status page: plan

A public page, in the style of status.supabase.com, where students, staff and visitors can see whether ITK websites are working without logging in. It is built from data webstatus already collects and from the ITK Design System.

Status: **decided, being built** on branch `feature/public-status`. D4 is English (user's choice); every other decision follows the recommendation. Indonesian strings in this plan are replaced by their English versions in the build.

---

## 1. Decisions needed before building

| # | Question | Options | Recommendation |
|---|---|---|---|
| D1 | Where does the page live? | `/` (today it redirects to `/login`) or `/status` | `/` for the public page, with a "Staff log in" link in its header. `/status` also works if `/` must stay the login. |
| D2 | Which sites are public? | All monitored sites, or only sites marked public | Only sites marked public, **off by default**. Some sites (for example "Host to Host BNI/BRI/Mandiri") are internal or sensitive, and listing them publicly says they exist and when they fail. |
| D3 | Public names | Show the internal site name, or a separate public name | Optional public name per site, falling back to the internal name. |
| D4 | Language | English (project override), Bahasa Indonesia, or both | **Decided: English for now** (project override). Bahasa Indonesia can be added later. |
| D5 | Brand moment | No ornaments (project override), or one ornament band | The brand book allows one ornament on public pages; the override bans them for webstatus. Recommendation: keep the override (no ornament), use the full logo lockup in a `SiteHeader`. |
| D6 | Response times | Show average response time per site, or not | Not in v1. It invites "slow" complaints and isn't what readers need. |
| D7 | Partial outages | Count "partially down" as downtime in the uptime %, or not | Keep today's rule (any page down = downtime for that cycle), and label those days "Gangguan sebagian" / "Partial outage" in the tooltip. Changing the rule changes the admin charts too. |
| D8 | History length | 30 or 90 days of uptime bars | 90 days on desktop, 30 on phones (bars stay ≥ 3px wide). |

---

## 2. What the page shows

From top to bottom:

1. **Header** (`SiteHeader`): ITK logo lockup (colour in light, white in dark), "Status layanan ITK", theme toggle, "Masuk staf" / "Staff log in" link.
2. **Overall banner** (`Alert`), one of:
   - success: "Semua layanan berjalan normal" / "All services operational"
   - warning: "Sebagian layanan terganggu" / "Some services are degraded" (any public site partially down)
   - danger: "Ada layanan yang tidak dapat diakses" / "Some services are down" (any public site totally down)
   - info: "Pemantauan sedang dijeda" / "Monitoring is paused" when global monitoring is off, and "Status mungkin belum terbaru" / "Status may be out of date" when the last completed cycle is older than 3 × the cycle interval. In these two cases the per-site statuses are shown greyed as "Tidak diketahui" / "Unknown", never as "operational".
   - Under it: "Terakhir diperiksa 15.50 WITA · diperiksa setiap 10 menit".
3. **Sites grouped by category** (Akademik, Non-Akademik, Others), in `Card`s. Each row:
   - public name, and the base URL host as a link (opens in a new tab);
   - status badge with icon and word (`x-itk.status-badge`);
   - 90-day uptime bars, and the uptime % for the period.
   - Categories collapse (`Collapsible`) on phones; a category with any site down starts open.
4. **Ongoing and past incidents** (`Trace`), last 14 days, newest first:
   - "Gangguan pada {site}" / "Outage on {site}", started 12 Nov 2026 10.12 WITA, resolved 10.40 WITA, duration 28 min, number of affected pages (not the paths).
   - Ongoing incidents first, marked "Sedang berlangsung" / "Ongoing".
   - Outages closed by turning monitoring off show "Pemantauan dihentikan" / "Monitoring stopped", not "Resolved".
5. **Footer**: square lambang, "© 2026 Institut Teknologi Kalimantan", short note: "Status diperiksa otomatis dari server ITK setiap {n} menit."

### Never shown publicly

Responsible person, internal page paths, HTTP codes, error types, response times (D6), Telegram targets, unmonitored or non-public sites, anything from `system_configs` except the cycle interval and whether monitoring is on.

---

## 3. Data: what exists and what is new

| Need | Source | New work |
|---|---|---|
| Current status per site | `sites.status`, `sites.is_monitored` | none |
| Uptime bars per day | `daily_stats` (`downtime_seconds`, `checks_count`, `period_start`), updated after every cycle by `StatsAggregationService::updateAllCurrentStats()`, never pruned | none |
| Day with no checks | `daily_stats` row missing or `checks_count = 0` | shown as "Tidak ada data" / "No data", grey, not counted |
| Incidents | `downtime_histories` (`started_at`, `ended_at`, `duration_seconds`, `affected_pages`, `status`) | none |
| Last check time | `checking_cycles.completed_at` (latest) | none |
| Monitoring on/off, interval | `SystemConfig` `is_monitoring`, cycle interval | none |
| Which sites are public | — | **migration**: `sites.is_public` boolean default false, `sites.public_name` nullable string |

### Uptime % for a day

`uptime = 1 − downtime_seconds / observed_seconds`, where `observed_seconds` is 86 400 for past days and the time elapsed so far for today.

Known limit: days when monitoring was switched off part-way count the unmonitored hours as "up". A precise version would subtract stopped periods (the aggregation already tracks them in `getMonitoringStops()`); left for later unless D7 changes.

Bar tone for a day (always with a text equivalent):

| Uptime | Tone | Word |
|---|---|---|
| no checks | neutral (`border` / `surface-sunken`) | Tidak ada data |
| 100% | success | Normal |
| ≥ 99% | warning | Gangguan singkat |
| < 99% | danger | Gangguan |

---

## 4. Back end

Small and separate from the views (the "front end only" migration rule does not apply to a new feature, but each change gets its own commit):

1. **Migration** `add_public_fields_to_sites_table`: `is_public` (bool, default false, indexed), `public_name` (nullable string 100).
2. **Site form** (`sites/_form.blade.php`, `SiteController` validation): "Tampilkan di halaman status publik" / "Show on the public status page" checkbox and "Nama publik (opsional)" field.
3. **`PublicStatusService`** builds one array for the page: banner state, last check, categories → sites → 90 daily points + uptime %, incidents (14 days). Only `is_public && is_monitored` sites.
4. **Caching**: `Cache::remember('public-status', 60s, …)`, and `Cache::forget('public-status')` at the end of a monitoring cycle (after `updateAllCurrentStats()` in `MonitoringService`). The page is read during outages, when traffic spikes; it must not hit `check_results`.
5. **Route** (D1): `Route::get('/', PublicStatusController::class)->name('status')` with `throttle:120,1`, outside the `auth` group. `/login` unchanged. Response header `Cache-Control: public, max-age=60`.
6. **JSON** (optional, same cache): `GET /status.json` with the same data, for a future campus app or widget.

No Livewire on the public page: it is a plain Blade page that re-fetches itself every 60 s (Alpine `fetch` of `/status.json` swapping the data, or a simple meta refresh as a fallback).

---

## 5. Front end

- New layout `layouts/public.blade.php`: same theme script and tokens, `SiteHeader` instead of the sidebar, skip link, footer.
- Reuse: `x-itk.alert`, `x-itk.card`, `x-itk.badge`, `x-itk.status-badge`, `x-itk.tooltip`, `x-itk.empty`.
- Build from the design system (port from `design-system/reference/`): `x-itk.site-header` (SiteHeader, with the menu sheet below 1024px), `x-itk.trace` (Trace), `x-itk.collapsible` (Collapsible).
- **One component the design system does not have:** `x-itk.uptime-bars`. It is built only from tokens and existing patterns (Tooltip for each day, Badge words, Chart's rule of a hidden data table). This is flagged as a custom component; nothing else is invented.
- Accessibility:
  - each bar is a focusable button with a tooltip ("12 Nov 2026 · 99,2% · 1 gangguan, 11 menit"), and the whole row has a screen-reader table of the 90 days;
  - status is always icon + word; bar colours are never the only signal;
  - every target ≥ 44px on touch (on phones the bars are one tap target per week that opens a short list, or the bars hide and the 30-day % shows instead);
  - light and dark themes; `prefers-reduced-motion` respected.
- Dates and times per the brand book: "12 Nov 2026", "15.50 WITA" (app timezone is `Asia/Makassar`).

---

## 6. Steps (one commit each, build + view cache + tests after each)

1. Migration + `Site` fillable/casts + site form checkbox and public name (D2, D3).
2. `PublicStatusService` with feature tests: only public monitored sites, uptime maths, no-data days, banner states (all up, degraded, down, paused, stale), incident list, no private fields in the output.
3. Route, controller, cache and cache-busting at cycle end; throttle; `status.json`.
4. `layouts/public.blade.php` + `x-itk.site-header`.
5. Page: banner, categories, status badges.
6. `x-itk.uptime-bars` + tooltips + screen-reader table.
7. Incidents with `x-itk.trace`.
8. Checks: 360px / 1440px, light and dark, keyboard only, contrast, page load with 100 sites under 300 ms from cache, a stale-cycle and monitoring-off run.

---

## 7. Later (not in v1)

- Admin-written incident notes ("Kami sedang menangani gangguan SIAKAD…") attached to an outage, shown in the Trace.
- Public Telegram channel for status changes (the bot already exists), linked as "Ikuti pembaruan".
- Scheduled maintenance windows.
- Precise uptime that excludes monitoring-stopped periods.
- Monitoring from a second location, so a campus network problem is not reported as every site being down.
