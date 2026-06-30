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
