# Refactor audit — scaffolder ⇄ blueprint alignment

Running audit trail for the blueprint-alignment refactor. One entry per logical change:
**what / why / how verified / behavior change / deferred-or-open**. The requirement→evidence
checklist at the bottom is the source of truth for what's done.

Plan of record: `~/.claude/plans/then-the-github-caveat-logical-pebble.md` (Phase 0 approved).
Foundation: the prior bug-fix + laranail-rebrand work (local commits) is retained. All work is
local commits only — never pushed.

---

## Blueprint RE-SYNC (Phase 0–3, second pass)

Naming model (decided, D1/D2 locked + secondary-entity question answered):
- **Artifact** = the package/module/plugin (`Blog`→{Artifact}); **primary Entity** = `Post`→{Entity}
  (prompted via `--entity`, default = singular of the artifact name, since the blueprint decouples
  them: Blog≠Post). **Comment/Category/Tag stay verbatim** as the standard supporting layer (generic
  nouns; documented). Zero-leftover bar therefore targets `blog`/`post` (not comment/category/tag).
- **D1 (panel comments) = option (a):** keep the blueprint's wording verbatim, wrap each panel-named
  comment in `plugins` markers (block for whole comments/markdown bullets; inline `[[plugins]]…[[/plugins]]`
  for mid-sentence parentheticals) so nova/filament keep gold-standard text and `plugin=none` strips it.
- **D2 (cross-feature tests) = option (b):** `ReviewHardeningTest` kept as a full-feature fixture; the
  matrix runs an artifact's own suite in full only for all-features-on, and otherwise verifies the
  pruned artifact boots + passes applicable checks (documented matrix policy).

### RS-1 — Re-sync the refactored blueprint into `stubs/` + re-apply markers (issues #1/#2/#4)
- **What:** Diffed the current blueprint (`~/Downloads/Modules/Blog`) against the pristine baseline
  (commit `5a781d6` via a detached worktree, so author changes are isolated from my markers). The
  refactor is a **hardening pass**, not a restructure: 4 migrations → 1 `create_blog_tables.php`; new
  `src/Policies/TagPolicy.php`; 16 content edits (LIKE-wildcard escaping in `Post`/`PostList`; Unicode
  `countWords`; `PostData` clear exceptions; case-insensitive tag dedup + `tag_count_max`; approved-only
  API comments; `SendPostPublishedNotification` `deleteWhenMissingModels`; observer demotes dateless
  scheduled posts; provider registers `TagPolicy`, drops the `blog.publish` gate; ~20 new
  `ReviewHardeningTest` methods; docs/CHANGELOG). Re-synced every change into `stubs/blueprint`
  (direct-copy for no-marker files; patched author changes into the marker-bearing provider/config to
  preserve markers; consolidated migrations).
- **D1 implemented:** added inline-marker (`[[feat]]…[[/feat]]`) and docblock-continuation (` * `) +
  `#` (NEON) support to `MarkerProcessor`; restored verbatim panel wording and wrapped all 17 panel
  mentions (docblocks, `//` comments, markdown, the new `TagPolicy` docblock + `PostObserver` demote
  comment + `ReviewHardeningTest` comments). Generator now also runs the marker pass on inline-token
  files (not just `@artifact:` ones).
- **Why:** issues #1 (don't lose markers), #2 (D1), #4 (migration rename).
- **How verified:** **marker before/after diff = IDENTICAL** (68 block markers across the same 7 files;
  nothing lost) + 34 new inline tokens (17 pairs). `php -l` clean on all edited stubs.
  `MatrixVerificationTest` `plugin=none` scans the **whole generated tree** → zero `Filament`/`Nova`
  and zero leftover `@artifact`/`[[ ]]` markers; nova/filament builds keep verbatim wording;
  `GeneratedArtifactBootTest` boots the re-synced provider (TagPolicy/Gate changes included). Issue #4:
  no generation path references the 4 old migration filenames. Full suite green (**446**).
- **Behavior change:** generated artifacts now carry the blueprint's hardening fixes + `TagPolicy` +
  consolidated migration; `blog.publish` convenience gate removed (publish via policy ability) — matches
  the gold standard.
### RS-2 — Generic template: entity parameterization (Post → {Entity})
- **What:** Decided + documented the naming model (Artifact vs primary Entity, Comment/Category/Tag
  as the generic supporting layer; framework `post` excluded — see the answered question). Added an
  entity pass to `TokenReplacer` (studly `Post`/`Posts`, lowercase `post`/`posts`, SCREAMING `BLOG_`
  env prefix → `{UPPER}_`) that PROTECTS framework API (`Route::post`/`->postJson`/`->post(`) and
  English words (`Postgres`/`compost`/`posted`). `GenerationRequest` computes the forms with a real
  inflector (`Str::plural`/`singular`/`snake`/`camel`); the generator renames entity class files
  (`PostController.php` → `{Entity}Controller.php`) for PSR-4; `make:artifact` gained `--entity`
  (default = singular of the artifact name).
- **How verified:** `TokenReplacerTest` (entity rewrite + framework/English protection + Post-identity
  no-op); `GenericTemplateTest` — `Customer`/`Account` + `Admin`/`Role` generate with **zero `blog`
  and zero entity-`post` leftovers**, entity files renamed, all PHP valid, supporting layer kept, and
  a **non-blog provider boots** (manager + `EloquentAccountRepository` binding resolve). Existing
  tests unaffected (entity defaults to `Post`). Suite 452.
- **Behavior change:** `make:artifact` now produces domain-generic artifacts; entity defaults to the
  singular of the name.

### RS-3 — Sweep + D2 policy + docs + audit (incl. a self-caught regression)
- **What / fixes:**
  - **Regression caught + fixed:** `src/helpers.php` (referenced by `composer.json` autoload.files)
    was unintentionally dropped during the re-sync, fataling the autoloader (php -l passed, but
    `vendor/autoload.php`/phpstan broke). Restored from `a3652d9`; confirmed no other non-stub file
    was lost. Regression guard: phpstan now runs clean in the suite checks.
  - **phpstan:** fixed a latent larastan false-positive on `make:artifact` `getOptions()` (Symfony's
    null option-shortcut) by matching the codebase's loose `@return array` convention. `phpstan
    analyse` → **No errors**.
  - **D2 matrix policy documented** (docs/make-artifact.md "Build & test matrix policy"): static
    checks every combo; runtime boot for all-features; pruned artifacts build but their feature-specific
    suites aren't run; `ReviewHardeningTest` is a full-feature fixture.
  - **Docs:** `docs/make-artifact.md` gains `--entity`, the naming model, the framework-`post` rule,
    and the matrix policy.
- **How verified:** `phpstan` clean; full suite green (**452**); `MatrixVerificationTest` (all combos)
  + `GeneratedArtifactBootTest` + `GenericTemplateTest` green.

### RE-SYNC requirement → evidence checklist
| Prompt item | Status | Evidence |
|---|---|---|
| Hard rule 1 — artifact = package/module/plugin, folder ≠ namespace | ✅ | `PortabilityTest`, `MatrixVerificationTest` |
| Hard rule 2 — panel = Nova XOR Filament XOR none (mutually exclusive) | ✅ | `--plugin` single choice; `MakeArtifactCommandTest`, `MatrixVerificationTest` |
| Hard rule 3 — none ⇒ zero Nova/Filament (asserted) | ✅ | `MatrixVerificationTest` whole-tree scan |
| #1 — re-sync without losing markers | ✅ | RS-1; block-marker diff IDENTICAL (68) + 34 inline |
| #2 — D1 panel comments verbatim, plugin-markered | ✅ | RS-1; inline/docblock/`#` markers; none-scan zero |
| #3 — D2 cross-feature tests policy | ✅ | RS-3 documented; matrix static + boot |
| #4 — migration rename (4→1) | ✅ | RS-1; no path references old names |
| Generic template — zero blog/post; Customer + Admin proven | ✅ | RS-2; `GenericTemplateTest` |
| Naming model documented (artifact vs entity, inflection, case) | ✅ | RS-2; `docs/make-artifact.md` |
| Sweep — drift / dead refs / docs / regressions | ✅ | RS-3 (helpers.php restore, phpstan, docs) |
| Idempotency (stub copy / marker / composer merge) | ✅ | marker re-apply is data-driven + verified; `PortabilityTest` composer idempotency |
| Audit trail | ✅ | this section |

## Blueprint RE-SYNC v2 (blueprint refactored again + relocate)

Re-review change list (current blueprint vs the prior sync, author changes isolated from my markers):
an **asset-pipeline overhaul** — `Assets.php` rewritten (compiled vs `live`/HMR), `config ui.assets`
`base`/`manifest`/`bundles` → `build_directory`/`live`, new `tailwind.config.js`, base/vanilla entry
`app.{js,scss}` → `blog.{js,scss}`, `vite.config.js`/scss/js + `AssetsComponentTest`/`assets.md` —
plus refinements: `PostStatus` (dependency-light `array_reduce`/`array_column`), `SanitizeHtmlStage`,
`NotReservedSlug`, `ValidTagList`, `PostData`, `Post` (Str helpers), `PostCreateCommand`, Filament
`BlogPlugin`, CHANGELOG/UPGRADING/docs. No new toggleable feature (asset-pipeline internals changed).

### RS-4 — Re-sync the asset overhaul + refinements
- **What:** Direct-copied the no-marker changed files; patched the `ui.assets` config block in place
  (markers preserved); re-synced `Post.php` + re-applied its inline marker; restored the phpstan
  comment verbatim with a `plugins` inline marker (D1); re-synced `CHANGELOG.md` + re-applied its 4
  markers; `app.*`→`blog.*` asset rename.
- **How verified:** block-marker set IDENTICAL before/after (68); inline 34→36 (+1 new phpstan D1
  marker, nothing lost); `php -l` clean; Artifacts suite green incl. whole-tree none-scan = zero
  Filament/Nova over the new asset files; boot + generic green. Full suite 457; phpstan clean.

### RS-5 — Relocate blueprint to the primary stub repo (decision Q2 = Both)
- **What:** `git mv stubs/blueprint → src/Commands/stubs/blueprint` (the named primary location);
  repointed the command (`__DIR__.'/stubs/blueprint'`) + 5 test source paths; excluded the template
  from phpstan (`excludePaths: src/Commands/stubs/blueprint`) and composer
  (`autoload.exclude-from-classmap`) since it uses the placeholder namespace and isn't autoloaded;
  pint's `stubs` exclude already covers it. Updated `docs/make-artifact.md` path.
- **Persisted user changes (read-before-assume):** the working tree exactly matched the prior commit
  (RS-3) — no stash/branch/worktree/uncommitted changes. The user's `helpers/helpers.php` layout
  (composer `autoload.files`) was already in HEAD and is preserved; a stale `src/helpers.php` orphan
  was cleaned up. No user change was reverted or relocated back.
- **How verified:** `composer dump-autoload` OK; `vendor/autoload.php` loads; `phpstan` clean
  (template excluded); `pint --test` clean (template excluded); full suite **458** green from the new
  location (matrix/boot/generic/portability repointed).
- **Open (next increments):** feature catalog ✅ (`FEATURE_CATALOG.md` + config `requires` + CLI
  dependency resolution); per-file stub upgrade (`src/Commands/stubs/*.stub` → blueprint quality,
  improve-first); `resources/boost/*` refactor; full matrix build-and-test per D2; final checklist.

### RS-6 — Per-file stub upgrade + boost refactor + real artifact build-and-test
- **Item 1 (per-file stubs → blueprint quality, improve-first):** added `declare(strict_types=1)` to
  all 63 PHP per-file stubs (`src/Commands/stubs/*.stub`) — the blueprint's universal convention,
  previously absent from every one; added the modern `casts(): array` method to `model.stub`.
  `request.stub` already typed. Skeleton `handle()`/`__invoke()` bodies left generic (the blueprint's
  typed signatures are domain-specific, not appropriate to impose on empty stubs). Regenerated 142+1
  snapshots; verified the ONLY diffs are strict_types/casts (no unintended changes).
- **Item 2 (`resources/boost/*`):** `core.blade.php` + `SKILL.md` now feature `make:artifact` (the
  blueprint generator) + laranail command naming + the config-driven feature catalog; new
  `rules/artifacts.md` (usage, naming model, panel, catalog, D2 policy).
- **Item 3 (real build-and-test) + bug found+fixed:** ran genuine `composer install` + the generated
  artifact's own PHPUnit. **Blog passed 149/149 but a non-blog Customer/Account failed 10** — entity
  tokenization renamed view *references* (`accounts.show`, livewire `account-list`) but `renamePaths`
  only renamed PHP class files by studly token, leaving view **dirs/files** at `posts/`,
  `post-list.blade.php` → "View not found" 500s. **Fix:** `renamePaths` now walks dirs+files
  (CHILD_FIRST) and maps lowercase entity forms (`posts`→{entityPlural}, `post`→{entityLower}). After
  the fix **Customer/Account builds + passes 149/149** too. Added `scripts/verify-artifacts.sh` (the
  D2 standing check: full-test all-features Blog + Customer, build+static a pruned combo) and a
  `GenericTemplateTest` regression guard (view dirs/files renamed). This is the bug `php -l`/boot
  missed — proof that build-and-test was needed.
- **Verified:** scaffolder suite 458; phpstan clean; `scripts/verify-artifacts.sh` → "ALL ARTIFACT
  BUILDS + TESTS PASSED".

### RS-7 — Re-verification pass (blueprint unchanged) — found+fixed 2 real bugs
- **Re-review:** diffed the current blueprint vs the stub — **unchanged** since RS-4 (every "diff" is
  my inline markers; only `public/build` artifact differs). No re-sync due. Removed a stray empty
  top-level `stubs/` dir (untracked leftover of the relocate).
- **Bug 1 (lowercase Filament/Nova leak, plugin=none):** `UPGRADING.md` named the panel deps in
  **lowercase** (`filament/filament`, `laravel/nova`); `plugin=none` didn't strip them and the matrix
  none-scan MISSED it (checked only capitalized `Filament`/`Nova`). Fixed: markered the clause;
  hardened the matrix scan to case-insensitive word-bounded `/\bfilament\b|\bnova\b/i` (catches
  lowercase product refs, not "innovation"). composer suggests were already repairComposer-stripped;
  `panels.md` deleted — so UPGRADING was the sole leak.
- **Bug 2 (default-entity collision — important):** `resolveEntity` defaulted entity to
  `singular(name)`, so a single-word `make:artifact Admin` set entity==artifact → manager `{Artifact}`
  and model `{Entity}` collide on import (`Cannot use …\Models\Admin as Admin`); the generated suite
  fatals. The file-only command tests never built the artifact, so it went unnoticed — and it's the
  exact "don't assume entity == artifact" case the brief flagged. Fixed: default entity = distinct
  generic (`config artifacts.default_entity='Item'`); reject `--entity == artifact`. `make:artifact
  Admin` now builds (Admin/Item) and passes 149/149.
- **Verified (reproducible):** `scripts/verify-artifacts.sh` builds + full-tests **Blog/Post,
  Customer/Account, AND Admin/Item (default entity)** — 149 tests each — plus a pruned build. Fresh
  `plugin=none` artifact = zero filament/nova (any case). Scaffolder suite **460**; phpstan + pint
  clean. Both bugs have regression tests (MatrixVerificationTest hardened scan;
  MakeArtifactCommandTest distinct-default + rejection).
- **Lesson reinforced:** build-and-test catches what static/boot/file-only checks miss; the matrix
  scan and command tests now cover both classes of bug.

### RS-8 — Complete the 3×3 build-and-test; fix PanelsTest single-panel coupling
- **What:** Extended real build-and-test to the panel dimension (build a filament + nova artifact,
  not just none). This exposed a 3rd cross-fixture coupling: `PanelsTest` asserts BOTH the Filament
  AND Nova integration providers are registered, but panels are mutually exclusive — a filament
  artifact has no Nova provider (deleted), so its generated suite failed
  (`assertArrayHasKey Nova…ServiceProvider`). Static/`php -l` missed it.
- **Fix:** markered `PanelsTest`'s per-panel imports + provider assertions (`plugin-filament` /
  `plugin-nova`) so a single-panel artifact keeps only its own; `none` deletes the file (shared).
- **How verified:** `scripts/verify-artifacts.sh` now runs the full 3×3 via real `composer install` +
  suites — **Blog/Post, Customer/Account, Admin/Item (none, 149 each), Shop/Product (filament, 151),
  Store/Listing (nova, 151)** + a pruned build — → "ALL ARTIFACT BUILDS + TESTS PASSED".
  `MatrixVerificationTest` now asserts PanelsTest is single-panel (present with only the selected
  provider for filament/nova; absent for none). Scaffolder suite **460** (1225 assertions); phpstan +
  pint clean.
- **Running tally of bugs the build-and-test loop caught that static/boot/file-only checks missed:**
  (1) renamePaths view dirs/files [RS-6], (2) lowercase Filament/Nova leak in UPGRADING [RS-7],
  (3) default-entity collision [RS-7], (4) PanelsTest both-panels coupling [RS-8].

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
| 1 | Blueprint copied under `/stubs/` | ✅ done | entry 0001 |
| 2 | Scaffolder + every generated artifact use `laranail/console` + `laranail/package-tools` | ✅ done | scaffolder command on the console base (0005); every generated `composer.json` requires both libs — asserted across the matrix (0007) — `MatrixVerificationTest` |
| 3 | Generated output matches the blueprint's standard | ✅ done | full blueprint vendored + reproduced via token/marker/prune/pint (0001–0007); `ArtifactGeneratorTest`, `MatrixVerificationTest` |
| 4 | CLI/TUI prompts: artifact type, plugin type (true "none"), toggleable features | ✅ done | 0005 — `MakeArtifactCommandTest` |
| 5 | Interactive + non-interactive, shared validation/generation path | ✅ done | 0005 — `MakeArtifactCommandTest` |
| 6 | Missing required flag fails loudly; non-TTY ⇒ non-interactive | ✅ done | 0005 — `MakeArtifactCommandTest` |
| 7 | Plugin "none" ⇒ zero Nova/Filament footprint (asserted) | ✅ done | 0004 (functional) + 0007 (consumer prose) + **0009 (literal zero across the whole tree, enforced)** — `MatrixVerificationTest` |
| 8 | Each feature toggleable; artifact still builds with it off | ✅ done | off ⇒ files/wiring/config removed, provider stays `php -l`-valid across all-on/minimal/partial combos (0007) — `MatrixVerificationTest`. Running each generated artifact's OWN PHPUnit needs its composer install — documented manual gate (see #12). |
| 9 | "Off" = not generated (not generated-but-disabled); unknown feature errors | ✅ done | 0003/0004/0005 — `MarkerProcessorTest`, `ArtifactGeneratorTest`, `MakeArtifactCommandTest` |
| 10 | Same artifact works in `platform/{modules,packages,plugins}/`; folder ≠ namespace | ✅ done | 0006 — `PortabilityTest`; unique-name-across-containers guard — `MakeArtifactCommandTest` |
| 11 | composer.json wiring minimal, idempotent, preserves unrelated keys | ✅ done | 0006 — `PortabilityTest` |
| 12 | Full tests: unit + functional + regression per bug fixed | ✅ done | scaffolder suite 444 green incl. the artifact engine; generated artifacts are now **booted in a real Laravel container** (`GeneratedArtifactBootTest`, 0009) — config merges, the manager + repository binding resolve — plus the whole-tree static sweep (`MatrixVerificationTest`). No per-artifact `composer install`: the only hard boot dep (`laranail/package-tools`) is a scaffolder dev dep; optional integrations are `class_exists`-guarded. |
| 13 | `REFACTOR_AUDIT.md` complete, maps every requirement to evidence | ✅ done | this table + entries 0001–0007 |

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
- **Accepted gap (justified):** the interactive (TUI) branch's prompt calls are thin one-line
  delegations to `laranail/console`'s `CommandInteractionService` (`askSelect`/`askText`/
  `askMultiSelect`, separately tested in that library). The shared `resolve*()` logic is fully tested
  via the flag path; the command forces non-interactive whenever `! input->isInteractive()` (true
  under PHPUnit), so reaching the prompts would require brittle TTY/keystroke simulation for 3
  delegation lines — not worth the fragility. The interactive↔non-interactive *parity* is structural
  (one `resolve*()` path, branching only on `$nonInteractive`). Resolved: 0004c (Pint), 0008
  (dep-trim), #24 (composer wiring), #23 (prose scrub).

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

### 0008 — Feature dep-trimming + full-tree lint (closing 0004c deferrals)
- **What:** Added `config/artifacts.feature_deps` (rest-api→laravel/sanctum, livewire→livewire/livewire)
  and extended `repairComposer` to drop a disabled feature's deps from require/require-dev/suggest
  (scout + commonmark stay — search and the body pipeline are core, only their drivers are opt-in).
  Strengthened `ArtifactGeneratorTest`: the full-features artifact now has **every** generated `.php`
  file `php -l`-checked (not just the provider), and the filament/livewire-off case asserts
  `livewire/livewire` is gone from composer.
- **Why:** Finishes the 0004c-deferred dep-trimming and the #12 "static check" depth (whole tree, not
  one file).
- **How verified:** `ArtifactGeneratorTest` (3 tests, 32 assertions) green incl. the full-tree lint
  and the dep-trim assertion. Full suite green (**442**).
- **Behavior change:** generated composer no longer suggests/requires a disabled feature's packages.

### 0009 — Permanent fixes for the two documented caveats
- **Caveat A — per-artifact tests were a manual (network) gate → now runtime-verified.** The only
  hard dependency to *boot* a generated provider is `laranail/package-tools` (the provider base); every
  optional integration (livewire/sanctum/scout/filament/nova) is `class_exists`-guarded. Added
  `package-tools` as a scaffolder **dev** dependency (one-time, normal) and `GeneratedArtifactBootTest`,
  which generates an artifact, autoloads its namespace (a runtime `Composer\Autoload\ClassLoader`
  PSR-4 mapping), registers its provider in the Testbench app and asserts it **boots** — config
  merges under the derived `vendor.package` key, the manager singleton resolves, the repository
  contract is bound. Covers both a full-featured `plugin=none` artifact and a fully-pruned (minimal)
  one. This replaces "php -l only + manual gate" with real container boot, with **no per-artifact
  `composer install`**.
- **Caveat B — Filament/Nova prose was kept by design → now literal zero for `plugin=none`.** The
  architectural "every writer (… Filament, Nova …)" mentions in `BodyProcessor`/`Post`/`PostObserver`/
  `CommentObserver`/`CachingPostRepository`/`FlushBlogCache` and `docs/security.md`/`extending.md`/
  `CHANGELOG`/`UPGRADING` were **reworded panel-agnostic** ("admin panels"/"admin-panel adapters") —
  accurate for every artifact, no literal names. The discrete panel mentions (CHANGELOG feature entry,
  phpstan `excludePaths`) are now markered: `MarkerProcessor` gained `#` (NEON/YAML) marker support
  alongside `//` and `<!-- -->`, the phpstan `src/Filament`/`src/Nova`/`Integrations` excludes are
  per-plugin/`plugins`-markered, and the CHANGELOG entry is `plugins`-markered.
- **How verified:** `GeneratedArtifactBootTest` (2, boots full + minimal); `MatrixVerificationTest`
  `plugin=none` now scans the **whole generated tree** and asserts **zero** "Filament"/"Nova"
  anywhere (across the package/module/minimal/partial none-cases); `MarkerProcessorTest` HTML case.
  Full suite green (**444**).
- **Behavior change:** scaffolder gains a `package-tools` dev dep; blueprint comments are
  panel-agnostic; `plugin=none` output is literally Filament/Nova-free.
