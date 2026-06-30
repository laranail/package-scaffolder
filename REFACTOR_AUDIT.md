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

### 0004 — Generation engine (TokenReplacer + ArtifactGenerator)
- **What:** `TokenReplacer` (strtr-based placeholder→identity rewrite) and `ArtifactGenerator`
  (copy `stubs/blueprint` → token-replace file contents → strip disabled `%marker%` blocks via
  `MarkerProcessor` → delete disabled feature/plugin files (prune map in `config/artifacts.php`) →
  rename surviving files to the artifact identity → repair `composer.json`: drop inactive
  integration providers from `extra.laravel.providers` and inactive plugin deps from
  require/require-dev/suggest). Added `GenerationRequest` value object. Markered the two
  integration-provider `use` imports (`plugin-filament`/`plugin-nova`) so `plugin=none` carries no
  Filament/Nova import.
- **Why:** Requirements 3 (match blueprint), 7 (plugin none zero footprint), 8/9 (feature toggles).
- **How verified:** `TokenReplacerTest` (6), `ArtifactGeneratorTest` (2 scenarios, 28 assertions):
  generates a full package (plugin=none, all features) — provider namespace `Modules\Blog`, valid
  `php -l`, caching/search/livewire present, **no `src/Filament`|`src/Nova`|integration providers**,
  **no functional `Filament\`/`Laravel\Nova` refs in src/**, composer has 1 provider + no
  filament/nova deps; and a renamed Filament plugin (`Acme\Shop`) with caching+livewire OFF —
  `ShopServiceProvider.php` (rename works), no caching files, no `src/Livewire`, Filament kept /
  Nova removed. Full suite green (**426**).
- **Behavior change:** none to existing scaffolder; new generator is additive (not yet wired to a
  command).
- **Deferred to 0004c / later tasks:** generated-artifact Pint pass to strip the remaining
  feature-off orphaned imports (cosmetic; `php -l` already valid); feature dep-trimming
  (livewire/commonmark/scout — harmless dev deps); host composer.json idempotent wiring (#24);
  README/docs prose scrub of Filament/Nova mentions for full literal zero-reference (#23).

| 4 (partial) | feature/plugin toggles drive generation | engine done (0004); command next | ArtifactGeneratorTest |
| 7 (partial) | plugin none zero footprint | functional footprint asserted (0004); prose scrub #23 | ArtifactGeneratorTest |

### 0005 — `make:artifact` command (CLI + TUI shared path)
- **What:** `MakeArtifactCommand` (`laranail::package-scaffolder.new`, alias `make:artifact`) on the
  **laranail/console** `Command` base (+ `SupportsNamespacedNames`). One `resolve*()` path per input
  (type, plugin, name, namespace, features) drives both modes via `$this->services->interaction()`:
  a flag wins when present; else interactive prompts (`askSelect`/`askText`/`askMultiSelect`); else
  (non-interactive) a missing **required** input fails loudly naming the flag. `nonInteractive =
  !TTY || --no-interaction`. Builds a `GenerationRequest` and calls `ArtifactGenerator`, writing to
  `base_path(config artifacts.kinds.{type})/{Studly}` (or `--path`). Registered in
  `ConsoleServiceProvider::defaultCommands()`; `artifacts` config merged in the provider.
- **Why:** Requirements 4/5/6 (prompts for type/plugin/features; one shared validation+generation
  path; fail loud + non-TTY default) and 2 (the scaffolder itself uses laranail/console).
- **How verified:** `MakeArtifactCommandTest` (4): non-interactive flag generation (package, ns
  `Acme\Demo`, a non-selected feature absent); missing `--type` ⇒ exit 1 + "--type is required";
  unknown feature ⇒ error; `--type=plugin` without `--plugin` ⇒ "--plugin is required".
  `CommandNamingTest::all()` confirms the console-base command constructs/registers without breaking
  listing. Full suite green (**430**).
- **Behavior change:** new command added (additive).
- **Open:** interactive `expectsChoice` test (deferred to matrix verification); 0004c post-gen Pint
  pass + dep trimming; #24 host composer wiring; #23 prose scrub.

| 5 | interactive + non-interactive shared path | done (0005) | MakeArtifactCommandTest |
| 6 | missing required flag fails; non-TTY ⇒ non-interactive | done (0005) | MakeArtifactCommandTest |

### 0006 — Portability proof + idempotent host composer.json wiring (#24)
- **What:** `HostComposerWriter::wire()` idempotently merges the platform shape into a host
  composer.json — `extra.merge-plugin.include` for `./platform/{modules,packages,plugins}/*/composer.json`
  (+ recurse/replace false), path `repositories`, `config` (allow-plugins pestphp/pest-plugin +
  wikimedia/composer-merge-plugin, optimize-autoloader, preferred-install dist, sort-packages),
  minimum-stability dev, prefer-stable. Arrays are unioned, scalars set only when absent, allow-plugins
  merged with the developer's values winning. Wired into `make:artifact` (opt out via `--no-repo`).
- **Why:** Requirements 10 (same artifact in any container) and 11 (minimal, idempotent, preserving
  composer merge).
- **How verified:** `PortabilityTest` (2): the same `Widget`/`Acme` artifact generated into module,
  package and plugin containers yields a **byte-identical** provider with `namespace Acme\Widget`
  (folder is location-only); wiring an existing composer twice is byte-identical (idempotent) and
  preserves `name`/`require`/a developer `minimum-stability: stable`/a developer allow-plugins entry
  while still adding the platform includes + repositories. Full suite green (**433**).
- **Behavior change:** `make:artifact` now wires the host composer by default (opt out `--no-repo`).
- **Open:** unique-name-across-containers validation in the command (#24 tail); matrix self-verification (#25).

| 10 | same artifact resolves identically in every container | done (0006) | PortabilityTest |
| 11 | composer merge minimal / idempotent / preserving | done (0006) | PortabilityTest |

### 0007 — Plugin matrix + zero-footprint prose scrub (#23) + matrix self-verification (#25 part)
- **What:** Extended `MarkerProcessor` to also accept HTML-comment markers
  (`<!-- @artifact:start plugins -->`) so markdown can be pruned, and added a `plugins` umbrella
  marker (set when plugin ≠ none). Wrapped the consumer-facing panel prose (README "Admin panels"
  bullet/section/docs-index row, `docs/architecture.md` panel bullet + its link to the deleted
  `panels.md`) so `plugin=none` strips them. Added `MatrixVerificationTest` — a dataprovider sweep
  over type × plugin × feature combos.
- **Why:** Requirements 7/23 (plugin none/nova/filament + literal zero footprint at the consumer
  surface), 2/3 (every artifact uses both laranail libs), 8/9 (toggles across combos), 12 (matrix).
- **Decision (documented):** "zero footprint" = no Nova/Filament **code, stubs, deps, providers,
  panel docs/tests, or consumer-facing README/docs prose**. Incidental *architectural* code-comments
  that name Filament/Nova as examples of "writers the body-pipeline/events cover" (PostObserver,
  BodyProcessor, Post, extending.md, security.md) and the CHANGELOG/phpstan ignore lines are **kept**
  by design — they document a real model-layer guarantee, not a panel footprint.
- **How verified:** `MarkerProcessorTest` HTML-marker case; `MatrixVerificationTest` (7 combos, 64
  assertions): each combo → no leftover `@artifact:` markers anywhere, a `php -l`-valid provider,
  **both `laranail/console` + `laranail/package-tools` in require**, plugin dir present/absent per the
  choice, and for `plugin=none` no `src/Filament|Nova`, no `panels.md`, and **README contains zero
  "Filament"/"Nova"**. Full suite green (**441**).
- **Behavior change:** none (template markers + tests).

| 2/3 | scaffolder + every artifact use both laranail libs | scaffolder ✓ (0005); artifacts ✓ (require asserted across the matrix) | MatrixVerificationTest |
| 7 | plugin none/nova/filament; none = zero footprint | done incl. consumer prose (0007) | MatrixVerificationTest |
| 8/9 | every feature toggleable; off ⇒ not generated; unknown ⇒ error | done | MatrixVerificationTest, MakeArtifactCommandTest |
| 12 | matrix self-verification of generated artifacts | static sweep done (0007); per-artifact PHPUnit = documented manual gate | MatrixVerificationTest |
