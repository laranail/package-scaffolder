# Release

Releases are **tag-driven**. Cutting `vX.Y.Z` triggers `.github/workflows/release.yml`, which extracts
that version's `CHANGELOG.md` section as the release body and publishes the GitHub release.

## Steps

1. Update `CHANGELOG.md`: move unreleased entries under a new `## [X.Y.Z] - YYYY-MM-DD` heading.
2. Commit on the default branch (`git config user.email imanimanyara@users.noreply.github.com`).
3. Tag + push:
   ```bash
   git tag vX.Y.Z
   git push origin --tags
   ```
4. CI (`release.yml`) publishes the GitHub release from the CHANGELOG section.

## Versioning

Semver. Breaking changes to the public API (the `make:artifact` command surface, the generated-artifact
layout, and the **manifest schemas**) are a major bump and must be documented in
[../UPGRADING.md](../UPGRADING.md). Keep the manifest schemas in lockstep with
[`laranail/package-management`](https://github.com/laranail/package-management) — they are the shared
contract between the generator and the loader.

[← Docs index](../README.md#documentation)
