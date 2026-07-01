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

**Behavior flagged:** these change inherited upstream behavior — install/update now (a) refuse to run
with unescaped shell input, (b) fail loudly on an unresolvable git type, (c) surface exit codes, and
(d) no longer `TypeError` on a version-less git/subtree install. All strictly safer; no valid
invocation regresses. Suite 471; phpstan + pint clean.

## Chunk A3–A7 — Support / Generators / Commands / rest

| # | Location | Sev | Issue | Fix | Test |
|---|----------|-----|-------|-----|------|
| A3-1 | `Support/Stub.php:104` | MEDIUM | `removeContentsBetweenTagMarkers` interpolated the removal tag into a regex without escaping — a tag with a metachar (esp. `/`, the delimiter) broke the pattern. | `preg_quote($tag, '/')`. | `StubTest::test_removal_tag_with_regex_metacharacters_is_literal` |
| A3-2 | `Support/Stub.php:86` | MEDIUM | `file_get_contents` result unchecked → a missing/unreadable stub fed `false` into `str_replace`. | `@file_get_contents` + throw `RuntimeException` on `false`. | `StubTest::test_get_contents_throws_when_the_stub_is_missing` |
| A3-3 | `Support/Module.php:70` | LOW | `getAssets` read `build/manifest.json` after a `file_exists` (TOCTOU), read unchecked. | `is_file` + `@file_get_contents` guard. | existing suite |
| A4-1 | `Generators/ModuleGenerator.php:427` | MEDIUM | With the provider generator disabled, `module.json` was edited by `preg_replace` regex surgery on JSON. | Decode → `providers = []` → re-encode. Snapshot-uncovered path, no churn. | existing generation suite |
| A5-1 | `Commands/ComposerUpdateCommand.php:33,53` | MEDIUM | Unvalidated `json_decode`; unchecked `json_encode`; non-atomic write. | `is_array` guard; encode check; atomic temp+rename. | existing suite |
| A5-2 | `Commands/Actions/ListCommands.php:180` | LOW | `catch (\Throwable)` returns fallback without logging. | **Accepted**: graceful degradation for a display/listing command. | n/a |
| A5-3 | `Commands/Actions/DumpCommand.php:32` | LOW | `passthru('composer dump …')` exit code not captured. | **Accepted**: static command (no injection); composer prints its own errors. | n/a |
| A6-1 | `Activators/FileActivator.php:125` | LOW | `modules_statuses.json` written non-atomically. | **Noted**: simple bool map; low risk; left to avoid snapshot churn. | n/a |

No CRITICAL/HIGH remained after the sweep (no raw SQL; the only other shell-out is a static composer
command). Suite 473; phpstan + pint clean.
