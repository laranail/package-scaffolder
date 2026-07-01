#!/usr/bin/env bash
#
# Standing build-and-test check for generated artifacts (D2 matrix policy).
#
# Generates artifacts, runs `composer install` (the real build), then their own
# PHPUnit suites:
#   - all-features-on combos (Blog + a non-blog Customer): run the FULL suite.
#   - panel combos (filament / nova) + a default-entity combo.
#   - pruned combos: build + run applicable tests (feature-specific + ReviewHardening
#     fixture excluded — they'd reference deleted code).
#   - framework flavors (vanilla / lumen): build + run each flavor's own suite.
#
# NETWORK-GATED: `composer install` downloads each artifact's deps, so this runs
# manually / in CI, not in the unit-test suite. The scaffolder's PHPUnit instead
# boots a generated provider in-process (GeneratedArtifactBootTest) + statically
# sweeps the matrix (MatrixVerificationTest).
#
# Usage: scripts/verify-artifacts.sh
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
WORK="$(mktemp -d)"
trap 'rm -rf "$WORK"' EXIT
ALL='web-ui,livewire,rest-api,caching,feeds,scheduling,asset-pipeline,notifications'

gen() { # <target> <name> <entity> <namespace> <vendor> <plugin> <features-csv>
  php -r '
    $root = $argv[1];
    require $root."/vendor/autoload.php";
    $config = require $root."/config/artifacts.php";
    [$target, $name, $entity, $ns, $vendor, $plugin, $feat] = array_slice($argv, 2);
    $features = $feat === "" ? [] : explode(",", $feat);
    (new Simtabi\Laranail\Package\Scaffolder\Support\Artifacts\ArtifactGenerator(
        new Illuminate\Filesystem\Filesystem, $config, $root."/vendor/bin/pint"))
      ->generate(new Simtabi\Laranail\Package\Scaffolder\Support\Artifacts\GenerationRequest(
        "package", $plugin, $features, $name, $ns, $vendor, false, $entity),
        $root."/stubs/blueprints/laravel", $target);
  ' "$ROOT" "$1" "$2" "$3" "$4" "$5" "$6" "$7"
}

gen_flavor() { # <target> <name> <entity> <flavor> <features-csv>
  php -r '
    $root = $argv[1];
    require $root."/vendor/autoload.php";
    $config = require $root."/config/artifacts.php";
    [$target, $name, $entity, $flavor, $feat] = array_slice($argv, 2);
    $features = $feat === "" ? [] : explode(",", $feat);
    $blueprint = (string) ($config["flavors"][$flavor]["blueprint"] ?? $flavor);
    (new Simtabi\Laranail\Package\Scaffolder\Support\Artifacts\ArtifactGenerator(
        new Illuminate\Filesystem\Filesystem, $config, $root."/vendor/bin/pint"))
      ->generate(new Simtabi\Laranail\Package\Scaffolder\Support\Artifacts\GenerationRequest(
        "package", "none", $features, $name, "Acme", "acme", false, $entity, $flavor),
        $root."/stubs/blueprints/".$blueprint, $target);
  ' "$ROOT" "$1" "$2" "$3" "$4" "$5"
}

build_and_full_test() { # <dir> <label>
  echo "==> [$2] composer install + full suite"
  ( cd "$1" && composer install --no-interaction --prefer-dist --quiet && vendor/bin/phpunit --no-coverage )
}

build_and_applicable_test() { # <dir> <label>
  echo "==> [$2] composer install + applicable tests (ReviewHardening full-feature fixture excluded)"
  ( cd "$1" && composer install --no-interaction --prefer-dist --quiet )
  if grep -rq '@artifact:\|\[\[plugins\]\]' "$1/src" "$1/config" 2>/dev/null; then
    echo "FAIL: leftover markers in $2"; exit 1
  fi
  # per D2: run everything except the all-features ReviewHardeningTest fixture; the
  # remaining suite must pass with no dangling references to pruned code.
  ( cd "$1" && vendor/bin/phpunit --no-coverage --filter '/^(?!.*ReviewHardening).*/' )
}

# all-features-on (full suite) — Blog (identity) + non-blog Customer/Account
gen "$WORK/Blog"     "Blog"     "Post"    "Modules" "modules" "none" "$ALL"
build_and_full_test "$WORK/Blog" "package · none · all · Blog"

gen "$WORK/Customer" "Customer" "Account" "Acme"    "acme"    "none" "$ALL"
build_and_full_test "$WORK/Customer" "package · none · all · Customer/Account"

# default-entity path (Admin + the distinct default entity "Item") — must build
gen "$WORK/Admin"    "Admin"    "Item"    "Acme"    "acme"    "none" "$ALL"
build_and_full_test "$WORK/Admin" "package · none · all · Admin/Item (default entity)"

# panel builds (filament / nova) — the packages aren't installed (suggest-only), so
# the guarded providers self-disable; PanelsTest keeps ONLY the selected panel.
gen "$WORK/Shop"     "Shop"     "Product" "Acme"    "acme"    "filament" "$ALL"
build_and_full_test "$WORK/Shop" "package · filament · all · Shop/Product"

gen "$WORK/Store"    "Store"    "Listing" "Acme"    "acme"    "nova" "$ALL"
build_and_full_test "$WORK/Store" "package · nova · all · Store/Listing"

# pruned combo (build + static only, per D2)
gen "$WORK/Lean"     "Lean"     "Item"    "Acme"    "acme"    "none" "caching,rest-api"
build_and_applicable_test "$WORK/Lean" "package · none · caching+rest-api · Lean/Item"
gen "$WORK/Min" "Min" "Item" "Acme" "acme" "none" ""
build_and_applicable_test "$WORK/Min" "package · none · MINIMAL (all optional off) · Min/Item"

# framework flavors — vanilla (pure PHP, no Illuminate) + lumen (service provider).
# Each generates from its own blueprint and runs its own (lean) suite.
gen_flavor "$WORK/Vanilla" "Widget" "Item" "vanilla" ""
build_and_full_test "$WORK/Vanilla" "package · vanilla · Widget/Item"

gen_flavor "$WORK/Lumen" "Gadget" "Item" "lumen" ""
build_and_full_test "$WORK/Lumen" "package · lumen · Gadget/Item"

echo "ALL ARTIFACT BUILDS + TESTS PASSED (flavor + D2 matrix policy)."
