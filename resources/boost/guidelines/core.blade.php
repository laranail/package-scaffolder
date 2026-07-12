@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
# Package Scaffolder

- laranail/package-scaffolder organises large Laravel applications into self-contained feature bundles under a `Modules/` directory.
- Each module contains its own controllers, models, migrations, routes, views, service providers, and config — like a mini Laravel package inside your app.
- Modules are scaffolded with `{{ $assist->artisanCommand('module:make ModuleName') }}` and live under `Modules/` by default.
- Module namespaces follow `Modules\{StudlyName}` (e.g. `Modules\Blog\Http\Controllers\PostController`).
- Always place module-specific logic inside the module directory — never in the app's `app/` folder.
- Disabled modules have their service providers skipped; their routes, bindings, and migrations will not load.
- Never import classes from another module directly — use events/listeners to avoid hard coupling.

## Blueprint artifacts (`make:artifact`)

- This is the **laranail/package-scaffolder** fork. Its Artisan commands are namespaced
  `laranail::package-scaffolder.*` with the familiar `module:*` names kept as aliases.
- Prefer `{{ $assist->artisanCommand('make:artifact Blog') }}` (alias of `laranail::package-scaffolder.new`)
  to scaffold a **complete, opinionated artifact** from the gold-standard blueprint — a full
  `laranail/package-tools` package (manager/DSL, services, actions, repository+contract, search
  manager, body pipeline, lifecycle events, policies, REST API, web UI, etc.) rather than an empty
  module shell. Use `module:make-*` only to add individual classes into an existing artifact.
- One artifact, three shapes: `--type=package|module|plugin` → `platform/{packages,modules,plugins}/{Name}`.
  The folder is a location only; the PSR-4 root comes from `--namespace`, never the folder.
- Naming model: the **artifact** (`Blog` → `{Name}`) and a distinct **primary entity**
  (`Post` → `--entity`, default = distinct generic `Item`, must differ from the artifact). `Comment`/`Category`/`Tag` ship in every
  artifact as the fixed supporting entities (do not rename per-domain).
- Panel is one mutually-exclusive choice: `--plugin=nova|filament|none` (default `none` = zero
  Nova/Filament footprint). Never both.
- Features are config-driven and opt-in/opt-out via `--features=` (see `FEATURE_CATALOG.md` /
  `config('artifacts.features')`); a disabled feature's code/routes/migrations/config/tests are not
  generated, dependencies are pulled in automatically (e.g. `livewire` requires `web-ui`).
