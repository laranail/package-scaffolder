<?php

declare(strict_types=1);
use Illuminate\Filesystem\Filesystem;

require __DIR__.'/../vendor/autoload.php';

/*
|--------------------------------------------------------------------------
| Test bootstrap — clear stale generated modules
|--------------------------------------------------------------------------
|
| The suite generates modules into the shared Testbench skeleton
| (vendor/orchestra/testbench-core/laravel/modules) and caches a manifest at
| bootstrap/cache/modules.php. If a run is interrupted (SIGKILL, timeout) the
| leftover module + cache register a provider whose class isn't autoloadable and
| fail EVERY subsequent boot. Clear both once, before any test app boots.
|
*/
$skeleton = __DIR__.'/../vendor/orchestra/testbench-core/laravel';

if (is_dir($skeleton.'/modules')) {
    (new Filesystem)->deleteDirectory($skeleton.'/modules');
}

if (is_file($skeleton.'/bootstrap/cache/modules.php')) {
    @unlink($skeleton.'/bootstrap/cache/modules.php');
}
