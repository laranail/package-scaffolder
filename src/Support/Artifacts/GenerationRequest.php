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
     * @param  string  $entity  the primary entity name (Post → {entity}); defaults to the
     *                          blueprint's `Post`, so an un-set entity is a no-op
     */
    public function __construct(
        public string $kind,        // package | module | plugin
        public string $plugin,      // nova | filament | none
        public array $features,
        public string $name,
        public string $namespaceBase,
        public string $vendor,
        public bool $force = false,
        public string $entity = 'Post',
        public string $flavor = 'laravel',   // vanilla | laravel | lumen (framework)
    ) {}

    public function studly(): string
    {
        return Str::studly($this->name);
    }

    public function lower(): string
    {
        return Str::kebab($this->studly());
    }

    public function entityStudly(): string
    {
        return Str::studly($this->entity);
    }

    /**
     * @return array{namespaceBase:string, studly:string, lower:string, upper:string, vendor:string, entityStudly:string, entityStudlyPlural:string, entityLower:string, entityPlural:string}
     */
    public function tokens(): array
    {
        $entity = $this->entityStudly();

        return [
            'namespaceBase' => trim($this->namespaceBase, '\\'),
            'studly' => $this->studly(),
            'lower' => $this->lower(),
            'upper' => Str::upper(Str::snake($this->studly())),
            'vendor' => $this->vendor,
            // Entity forms (singular/plural × studly/lower) via a real inflector.
            'entityStudly' => $entity,
            'entityStudlyPlural' => Str::studly(Str::plural($entity)),
            'entityLower' => Str::camel($entity),
            'entityPlural' => Str::camel(Str::plural($entity)),
        ];
    }
}
