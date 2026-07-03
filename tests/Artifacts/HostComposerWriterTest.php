<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Scaffolder\Tests\Artifacts;

use Illuminate\Filesystem\Filesystem;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Simtabi\Laranail\Package\Scaffolder\Support\Artifacts\HostComposerWriter;

class HostComposerWriterTest extends TestCase
{
    private Filesystem $fs;

    private string $path;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fs = new Filesystem;
        $this->path = sys_get_temp_dir().'/laranail-hcw-'.getmypid().'-'.uniqid().'.json';
    }

    protected function tearDown(): void
    {
        if ($this->fs->exists($this->path)) {
            $this->fs->delete($this->path);
        }
        parent::tearDown();
    }

    private function wire(): void
    {
        (new HostComposerWriter($this->fs))->wire($this->path);
    }

    private function read(): array
    {
        return json_decode($this->fs->get($this->path), true);
    }

    public function test_wires_a_fresh_composer_when_the_file_is_absent(): void
    {
        $this->wire();

        $c = $this->read();
        $this->assertContains('./platform/modules/*/composer.json', $c['extra']['merge-plugin']['include']);
        $this->assertContains('./platform/packages/*/composer.json', $c['extra']['merge-plugin']['include']);
        $this->assertTrue(collect($c['repositories'])->contains(
            fn ($r): bool => $r['type'] === 'path' && $r['url'] === './platform/plugins/*',
        ));
    }

    public function test_preserves_the_developers_unrelated_keys(): void
    {
        $this->fs->put($this->path, json_encode([
            'name' => 'acme/app',
            'require' => ['php' => '^8.4'],
            'config' => ['preferred-install' => 'source'],
        ]));

        $this->wire();

        $c = $this->read();
        $this->assertSame('acme/app', $c['name']);
        $this->assertSame('^8.4', $c['require']['php']);
        // scalars only set when absent — developer's choice wins
        $this->assertSame('source', $c['config']['preferred-install']);
        // and our wiring was still added
        $this->assertNotEmpty($c['extra']['merge-plugin']['include']);
    }

    /**
     * Regression: a NON-empty but unparseable composer.json must NOT be silently
     * rewritten from scratch (the old `json_decode(...) ?: []` discarded all keys).
     */
    public function test_refuses_to_overwrite_corrupt_json_and_leaves_it_untouched(): void
    {
        $corrupt = '{ "name": "acme/app", oops not json ';
        $this->fs->put($this->path, $corrupt);

        try {
            $this->wire();
            $this->fail('Expected a RuntimeException for corrupt composer.json.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('not valid JSON', $e->getMessage());
        }

        // the corrupt file is left exactly as it was — never clobbered
        $this->assertSame($corrupt, $this->fs->get($this->path));
    }

    public function test_is_idempotent(): void
    {
        $this->wire();
        $first = $this->fs->get($this->path);
        $this->wire();
        $this->assertSame($first, $this->fs->get($this->path));
    }
}
