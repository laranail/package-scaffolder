<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Scaffolder\Support\Artifacts;

use Illuminate\Support\Str;

/**
 * Immutable description of one artifact to generate. Built identically from the
 * interactive prompts and the CLI flags so the two paths can never drift.
 */
final readonly class GenerationRequest
{
    /**
     * @param  list<string>  $features  enabled toggleable features (incl. 'livewire' if on)
     */
    public function __construct(
        public string $kind,        // package | module | plugin
        public string $plugin,      // nova | filament | none
        public array $features,
        public string $name,
        public string $namespaceBase,
        public string $vendor,
        public bool $force = false,
    ) {}

    public function studly(): string
    {
        return Str::studly($this->name);
    }

    public function lower(): string
    {
        return Str::kebab($this->studly());
    }

    /**
     * @return array{namespaceBase:string, studly:string, lower:string, vendor:string}
     */
    public function tokens(): array
    {
        return [
            'namespaceBase' => trim($this->namespaceBase, '\\'),
            'studly' => $this->studly(),
            'lower' => $this->lower(),
            'vendor' => $this->vendor,
        ];
    }
}
