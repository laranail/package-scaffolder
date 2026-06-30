# Refactor audit — scaffolder ⇄ blueprint alignment

Running audit trail for the blueprint-alignment refactor. One entry per logical change:
**what / why / how verified / behavior change / deferred-or-open**. The requirement→evidence
checklist at the bottom is the source of truth for what's done.

Plan of record: `~/.claude/plans/then-the-github-caveat-logical-pebble.md` (Phase 0 approved).
Foundation: the prior bug-fix + laranail-rebrand work (local commits) is retained. All work is
local commits only — never pushed.

---

## Entries

### 0001 — Vendor the blueprint as the parameterized template
- **What:** Added `stubs/blueprint/` — a copy of the reference module
  `/Users/imanimanyara/Downloads/Modules/Blog/` (240 files), excluding `vendor/`, `node_modules/`,
  `composer.lock`, `package-lock.json`, `.phpunit.result.cache`, `.claude/`, and built `public/build/`.
  The blueprint already uses the find-replace placeholder namespace `Some\NamespacePath\Blog` (present
  in 120 files), so it is template-ready. Excluded `stubs` from Pint (`pint.json`) so the gold-standard
  template (and the `%marker%` blocks added later) are never reformatted.
- **Why:** Requirement — "keep a local copy of its relevant files in the repo under `/stubs/` so
  generation doesn't depend on an external path." It is the gold standard the generator reproduces.
- **How verified:** `find stubs/blueprint -type f` = 240; placeholder token present in 120 src files;
  `phpstan` (`paths: src`) and `phpunit` (`testsuite: tests`) don't scan `stubs/`; `pint --test` clean.
  Scaffolder suite re-run to confirm vendoring is inert (stubs are not autoloaded).
- **Behavior change:** none (additive; stubs aren't autoloaded/analysed/tested).
- **Open:** tokenization map + feature `%markers%` come next (entry 0002+).

### 0002 — Feature taxonomy decided + encoded as `config/artifacts.php`
- **What:** Mapped every blueprint file/provider-line/config-key/dep to a feature (full analysis
  recorded below). Decided (with sign-off) the **always-on core** vs **toggleable** split, and
  encoded the taxonomy in `config/artifacts.php` (kinds→containers, plugin types, toggleable features
  + defaults + sub-toggles, core list, namespace suggestions). Added `tests/Artifacts/ArtifactsConfigTest.php`.
- **Why:** Requirements 4/8/9 (toggleable features; off=not-generated; unknown=error) need a single
  source of truth for what's optional. The analysis revealed several "extensibility" features are
  **core substrate** (not cleanly removable) — flagged and resolved per the brief's "stop & ask".
- **Decisions (signed off):**
  - **Always-on core (NOT toggleable):** lifecycle-events, search-manager, body-pipeline,
    macroable-manager+DSL, spy-seam — they're the blueprint's identity and removing them needs
    invasive `PostService`/`PostObserver`/model surgery (drops sanitization/search).
  - **Toggleable `--features`:** `web-ui` (sub: `livewire`), `rest-api`, `caching`, `feeds`,
    `scheduling`, `asset-pipeline`, plus `notifications` (sub-toggle of core events). Default = **all
    on** (match the gold-standard blueprint); `--features=` selects an explicit subset; interactive
    multiselect pre-checks all; unknown feature ⇒ error.
  - **Filament/Nova are the plugin dimension**, not features: included only for `--type=plugin
    --plugin=filament|nova`; `--plugin=none` (and `type=module|package`) emit zero Nova/Filament footprint.
- **Prune map (evidence; per feature: owned files · provider lines to wrap in `%START_X%…%END_X%` ·
  config keys · composer):**
  - `caching`: `src/Repositories/CachingPostRepository.php`,`src/Listeners/FlushBlogCache.php`,`tests/Feature/CachingTest.php` · provider 185-198 (the `if(config(cache.enabled)){app->extend + Event::listen}`) + imports 52,67 · cfg `cache.*` · —
  - `rest-api`: `src/Http/Controllers/Api/*`,`src/Http/Resources/*`,`Middleware/EnsureApiAbility`,`Requests/IndexPostRequest`,`routes/api.php`,`tests/Feature/Api/*`,`ApiAbilityTest` · provider 99 (`hasRoute('api')`),101 (`registerMiddlewareAlias('blog.ability')`)+import 50 · cfg `routes.api` · suggest sanctum
  - `feeds`: `src/Http/Controllers/FeedController.php`,`resources/views/feed/*`,`tests/Feature/PostFeedTest` · `routes/web.php` 44-45+import 8 · cfg `features.rss|sitemap|feed_limit`,`ui.routes.feed|sitemap`,`reserved_slugs` feed entries · —
  - `scheduling`: `src/Console/PublishScheduledPostsCommand`,`src/Jobs/PublishScheduledPosts`,`tests/Feature/SchedulingTest` · provider 103 (cmd in hasCommands),209+method 305-325 (`registerSchedule`),import 8(Schedule),32 · cfg `scheduling.*` · —
  - `asset-pipeline`: `vite.config.js`,`package.json`,`resources/assets/*`,`public/.gitkeep`,`tests/Feature/AssetsComponentTest` (+`Assets` component co-owned with web-ui) · provider 95 (`hasAssets`),119 (`publishAssets`) · cfg `ui.framework`,`ui.assets` · —
  - `web-ui`: web controllers,`View/Components/*`,`resources/views/*`(except feed),`lang`,web tests · provider 93-94,98,100,200-201 + methods `registerComponents`(223-266),`registerComposerPartials`(274-289) · cfg `components.prefix`,`ui.*` · — ; **sub `livewire`:** `src/Livewire/*`,`resources/views/livewire/*`,provider 254-265+imports 18,54-55 · req-dev/suggest livewire
  - `notifications` (sub of core events): `src/Listeners/SendPostPublishedNotification`,`src/Notifications/PostPublishedNotification` · provider 180 (`Event::listen(PostPublished…)`) · cfg `notifications.channels` · —
  - **Shared, do-not-blind-delete:** `blog.published` alias (web+api), `Store*/Update*Request`+`Provides*Rules`+`Rules/*` (web+api), `comments.*`+`blog-comments` RateLimiter+`CreateCommentAction` (web+api), `ui.sortable` (web+api). Prune only when BOTH dependents are off.
- **How verified:** `vendor/bin/phpunit --no-coverage tests/Artifacts/ArtifactsConfigTest.php` green;
  pint clean. (Template markers + engine are entries 0003+.)
- **Behavior change:** none (additive config + test).
- **Open:** insert `%markers%` into the template (0003), build the generation engine (0004) + command (0005).

### 0003 — Feature markers in the template (provider + routes) + MarkerProcessor
- **What:** Added `src/Support/Artifacts/MarkerProcessor.php` — strips comment-delimited
  `// @artifact:start <feature>` / `// @artifact:end <feature>` blocks (drops markers for enabled
  features, removes the whole block for disabled ones; nesting-aware). Inserted markers into
  `stubs/blueprint/src/Providers/BlogServiceProvider.php` (asset-pipeline, rest-api, scheduling,
  caching, notifications, web-ui, livewire, plugin-filament, plugin-nova) and `routes/web.php`
  (web-ui vs feeds). Truly-shared registrations (`blog.published`+`EnsurePostIsPublished`,
  `hasViews`/`hasTranslations`/`hasRoute('web')`, rate limiter) kept as core to avoid OR-logic.
- **Why:** Requirements 8/9 — feature "off = not generated"; the engine (0004) strips disabled
  blocks. Comment markers keep the template valid PHP. Imports orphaned by stripped wiring are
  cleaned by the planned post-generation Pint `no_unused_imports` pass (verified: only `use` lines
  remain after stripping; php -l stays valid).
- **How verified:** `MarkerProcessorTest` (4 tests, incl. nested sub-toggle). Processed the marked
  provider/routes across feature sets (all-on, all-off, nova-only, feeds-only) → `php -l` valid in
  every case, zero markers left, feature code present/absent as expected, core wiring survives.
  Full scaffolder suite green (**418** tests).
- **Behavior change:** none to the scaffolder (template-only + additive support class/tests).
- **Open:** config/blog.php feature-key markers (next), then the generation engine (0004).

### 0003b — Config feature-key markers
- **What:** Marked the cleanly feature-owned blocks in `stubs/blueprint/config/blog.php`:
  `web-ui` (components), `notifications`, `scheduling`, `rest-api` (routes.api), `caching` (cache),
  `feeds` (features), `asset-pipeline` (ui.framework + ui.assets). `search`/`security`/`processing`/
  `routes.web`/`validation`/`morph_map`/`pagination`/`comments`/`ui.*` core keys stay.
- **How verified:** processed all-on / all-off → `php -l` valid both; markers gone; feature keys
  present when on and absent when off; core keys retained. (Runtime array-eval N/A on the template —
  it references placeholder classes resolved only after token replacement.)
- **Behavior change:** none (template-only).

---

## Requirement → status → evidence checklist

| # | Requirement (from the brief) | Status | Evidence |
|---|------------------------------|--------|----------|
| 1 | Blueprint copied under `/stubs/` | done | entry 0001 |
| 2 | Scaffolder + every generated artifact use `laranail/console` + `laranail/package-tools` | open | — |
| 3 | Generated output matches the blueprint's standard | open | — |
| 4 | CLI/TUI prompts: artifact type, plugin type (true "none"), toggleable features | open | — |
| 5 | Interactive + non-interactive, shared validation/generation path | open | — |
| 6 | Missing required flag fails loudly; non-TTY ⇒ non-interactive | open | — |
| 7 | Plugin "none" ⇒ zero Nova/Filament footprint (asserted) | open | — |
| 8 | Each feature toggleable; artifact still builds + tests green with it off | open | — |
| 9 | "Off" = not generated (not generated-but-disabled); unknown feature errors | open | — |
| 10 | Same artifact works in `platform/{modules,packages,plugins}/`; folder ≠ namespace | open | — |
| 11 | composer.json wiring minimal, idempotent, preserves unrelated keys | open | — |
| 12 | Full tests: unit + functional + regression per bug fixed | open | — |
| 13 | `REFACTOR_AUDIT.md` complete, maps every requirement to evidence | in progress | this file |
