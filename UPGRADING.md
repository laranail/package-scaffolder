# Upgrading

Breaking changes are documented here per release, with a clear before/after.

## Within the laranail line

The laranail `1.x` line targets PHP `^8.4.1 || ^8.5` and Laravel `^13.0`. Breaking changes — to the
`make:artifact` command surface, the generated-artifact layout, or the **manifest schemas**
(`composer.json` / `module.json` / `plugin.json`) — are a major bump. Keep the manifest schemas in
lockstep with [`laranail/package-management`](https://github.com/laranail/package-management); they are
the shared contract between the generator and the loader.

## From the upstream module engine

Earlier major lines (`5.x` – `12.x`) tracked the upstream Laravel-modules engine per Laravel version (see
the [CHANGELOG](CHANGELOG.md)). The laranail line renamespaces to
`Simtabi\Laranail\Package\Scaffolder\` and standardises on the laranail conventions; migrating from an
upstream install means updating the vendor/package name, namespace imports, and the published config keys.
