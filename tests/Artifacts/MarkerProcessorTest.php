<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Scaffolder\Tests\Artifacts;

use PHPUnit\Framework\TestCase;
use Simtabi\Laranail\Package\Scaffolder\Support\Artifacts\MarkerProcessor;

class MarkerProcessorTest extends TestCase
{
    private string $sample = <<<'PHP'
        core line
            // @artifact:start caching
            cache line
            // @artifact:end caching
        // @artifact:start web-ui
        web line
            // @artifact:start livewire
            livewire line
            // @artifact:end livewire
        // @artifact:end web-ui
        tail line
        PHP;

    public function test_enabled_feature_keeps_inner_code_drops_markers(): void
    {
        $out = MarkerProcessor::process($this->sample, ['caching', 'web-ui', 'livewire']);

        $this->assertStringContainsString('cache line', $out);
        $this->assertStringContainsString('web line', $out);
        $this->assertStringContainsString('livewire line', $out);
        $this->assertStringNotContainsString('@artifact:', $out);
    }

    public function test_disabled_feature_removes_the_whole_block(): void
    {
        $out = MarkerProcessor::process($this->sample, ['web-ui', 'livewire']);

        $this->assertStringNotContainsString('cache line', $out);
        $this->assertStringContainsString('core line', $out);
        $this->assertStringContainsString('web line', $out);
    }

    public function test_disabled_outer_block_removes_nested_inner_regardless(): void
    {
        $out = MarkerProcessor::process($this->sample, ['caching', 'livewire']); // web-ui OFF

        $this->assertStringNotContainsString('web line', $out);
        $this->assertStringNotContainsString('livewire line', $out, 'nested block must go with its disabled parent');
        $this->assertStringContainsString('cache line', $out);
        $this->assertStringContainsString('tail line', $out);
    }

    public function test_nested_sub_toggle_off_while_parent_on(): void
    {
        $out = MarkerProcessor::process($this->sample, ['caching', 'web-ui']); // livewire OFF

        $this->assertStringContainsString('web line', $out);
        $this->assertStringNotContainsString('livewire line', $out);
        $this->assertStringNotContainsString('@artifact:', $out);
    }

    public function test_inline_markers_strip_a_mid_sentence_clause(): void
    {
        $line = ' * every writer (facade, [[plugins]]Filament, Nova, [[/plugins]]raw Eloquent) goes through it.';

        $kept = MarkerProcessor::process($line, ['plugins']);
        $this->assertSame(' * every writer (facade, Filament, Nova, raw Eloquent) goes through it.', $kept);

        $stripped = MarkerProcessor::process($line, []);
        $this->assertSame(' * every writer (facade, raw Eloquent) goes through it.', $stripped);
        $this->assertStringNotContainsString('Filament', $stripped);
        $this->assertStringNotContainsString('[[', $stripped);
    }

    public function test_docblock_continuation_markers(): void
    {
        $php = <<<'PHP'
            /**
             * Does the thing.
             * @artifact:start plugins
             * Works for every writer (facade, Filament, Nova, raw Eloquent).
             * @artifact:end plugins
             */
            PHP;

        $kept = MarkerProcessor::process($php, ['plugins']);
        $this->assertStringContainsString('Filament, Nova', $kept);
        $this->assertStringNotContainsString('@artifact:', $kept);

        $stripped = MarkerProcessor::process($php, []);
        $this->assertStringNotContainsString('Filament', $stripped);
        $this->assertStringNotContainsString('Nova', $stripped);
        $this->assertStringContainsString('Does the thing.', $stripped);
        // remains a valid docblock skeleton
        $this->assertStringContainsString('/**', $stripped);
        $this->assertStringContainsString('*/', $stripped);
    }

    public function test_html_comment_markers_for_markdown(): void
    {
        $md = <<<'MD'
            Intro line.
            <!-- @artifact:start plugins -->
            ## Admin panels
            Filament + Nova adapters.
            <!-- @artifact:end plugins -->
            Outro line.
            MD;

        $kept = MarkerProcessor::process($md, ['plugins']);
        $this->assertStringContainsString('## Admin panels', $kept);
        $this->assertStringNotContainsString('@artifact:', $kept);

        $stripped = MarkerProcessor::process($md, []);
        $this->assertStringNotContainsString('Admin panels', $stripped);
        $this->assertStringNotContainsString('Filament', $stripped);
        $this->assertStringContainsString('Intro line.', $stripped);
        $this->assertStringContainsString('Outro line.', $stripped);
    }
}
