# Code review + hardening — findings

Running, per-chunk findings from the end-to-end review/hardening pass. Format:
`file:line · severity · what/why · fix`. Fixed items link to their regression test.
Severity: critical / high / medium / low.

## Chunk A1 — custom generation subsystem (`Support/Artifacts/*`, `MakeArtifactCommand`, `config/artifacts.php`)

| # | Location | Sev | Issue | Fix | Test |
|---|----------|-----|-------|-----|------|
| A1-1 | `Support/Artifacts/HostComposerWriter.php:26` | **HIGH** | `json_decode(...) ?: []` turned a *corrupt* host `composer.json` into `[]` and rewrote it from scratch — silently discarding all the developer's keys, directly contradicting its "never clobbers" docstring. | Distinguish absent/empty (→ `[]`) from a non-empty file that fails to parse (→ **throw**, leave the file untouched). | `HostComposerWriterTest::test_refuses_to_overwrite_corrupt_json_and_leaves_it_untouched` (+ preserves-keys, fresh, idempotent) |
| A1-2 | `HostComposerWriter.php:67`, `ArtifactGenerator::repairComposer` | MEDIUM | Non-atomic `put` + unchecked `json_encode` — an interrupted write leaves a half-written composer.json; an encode failure writes `false`/empty. | Check `json_encode !== false`; write via temp file + `rename` (`atomicPut`). | covered by generation + HostComposerWriter tests |
| A1-3 | `Support/Artifacts/TokenReplacer.php:88-98` | MEDIUM (defensive) | Entity replacement text was inserted into `preg_replace` unescaped — an entity form containing `$1`/`\1`/`$` would be interpreted as a backreference. Real call path is safe (`GenerationRequest` `Str::studly`-sanitises), but `replace()` is public API. | Use `preg_replace_callback` so replacements are always literal. | `TokenReplacerTest::test_entity_replacement_is_literal_not_a_backreference` |
| A1-4 | `ArtifactGenerator::renamePaths` | LOW | `Filesystem::move()` result ignored — a failed rename would silently leave a placeholder-named (broken) file. | Throw `RuntimeException` on move failure. | exercised by generation tests |
| A1-5 | `ArtifactGenerator::runPint` | LOW (by design) | Pint runs best-effort; a non-zero exit is ignored. | **Accepted / won't-fix**: Pint is purely cosmetic (formatting + orphaned-import cleanup); a failure never breaks the generated artifact, and there is no clean surfacing channel from a `Support` class without a larger refactor. Documented in the method docblock. | n/a |

No behavior changes to flag in A1 (all fixes are hardening; the corrupt-file throw only triggers on
input that previously caused silent data loss). Suite green; phpstan + pint clean.

## Chunk A2 — `Process/*` (module install/update; inherited)

| # | Location | Sev | Issue | Fix | Test |
|---|----------|-----|-------|-----|------|
| A2-1 | `Process/Installer.php:219-255` | **CRITICAL** | `Process::fromShellCommandline(sprintf(...))` interpolated the module name / branch(version) / URL / dest path straight into a shell string (`git clone/checkout`, `composer require`) — a crafted branch/name injects shell (`master; rm -rf …`). | `escapeshellarg` every user-derived value in all three install methods. | `Process/InstallerTest::test_git_install_escapes_a_malicious_branch`, `…_composer_install_escapes_a_malicious_package_name` |
| A2-2 | `Process/Updater.php:31-57` | **HIGH** | `installRequires/installDevRequires` built `composer require "{name}:{version}"` from a module's composer metadata with only double-quotes (no escaping) → shell injection via untrusted metadata. | `escapeshellarg` each `name:version` (`concatPackages`). | (escaping shared with Installer; covered) |
| A2-3 | `Process/Updater.php:63-74` | **HIGH** | `copyScriptsToMainComposerJson` did `json_decode(file_get_contents(...))` with no validation, indexed `$composer['scripts']` (may be absent), and wrote non-atomically → corrupts the host root composer.json on any decode failure / missing key. | Validate read + `is_array`; `??= []` the scripts key; check `json_encode`; atomic temp+rename write. | — (guarded; behavior otherwise unchanged) |
| A2-4 | `Process/Runner.php:23-26` | MEDIUM | `run()` `passthru($command)` discarded the exit code (callers couldn't detect a failed install/update) and had no return. | `passthru($command, $exitCode); return $exitCode;`. | `Process/RunnerTest::test_run_returns_the_command_exit_code` |
| A2-5 | `Process/Installer.php` (`$version`, `$path` props) | MEDIUM (real bug) | `$version` was typed `string` but the constructor assigns `?string $version = null` → **`TypeError` on any version-less install**; `$path` was an uninitialized typed property (`Error` if `getDestinationPath()` ran before `setPath()`). | `?string $version = null`; `string $path = ''`. Removed the now-obsolete phpstan-baseline `is_null()` suppression (the fix makes the null-check meaningful). | exercised by the composer-install test (version-less path) |

**Behavior flagged:** these change inherited nwidart behavior — install/update now (a) refuse to run
with unescaped shell input, (b) fail loudly on an unresolvable git type, (c) surface exit codes, and
(d) no longer `TypeError` on a version-less git/subtree install. All strictly safer; no valid
invocation regresses. Suite 471; phpstan + pint clean.
