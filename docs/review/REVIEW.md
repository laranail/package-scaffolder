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
