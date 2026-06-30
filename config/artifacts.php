<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Artifact generation (make:artifact / laranail::package-scaffolder.new)
|--------------------------------------------------------------------------
|
| Single source of truth for what `make:artifact` can generate: where each
| artifact kind is written, the plugin dimension, and the toggleable feature
| taxonomy. The generation engine reads this; the command's prompts/flags are
| derived from it so a flag and its prompt can never drift apart.
|
| See REFACTOR_AUDIT.md (entry 0002) for the per-feature prune map.
|
*/

return [

    /*
    | Root PHP namespace offered at creation. The container directory is NEVER
    | derived into the namespace; the user is prompted (default + suggestions).
    */
    'default_namespace' => env('ARTIFACT_DEFAULT_NAMESPACE', 'Modules'),

    'namespace_suggestions' => [
        'Modules',
        'Packages',
        'Plugins',
        'App\\Modules',
    ],

    /*
    | Artifact kinds → container directory (relative to base_path()). The folder
    | is a location only; all three resolve to the same prompted PSR-4 root.
    */
    'kinds' => [
        'package' => 'platform/packages',
        'module' => 'platform/modules',
        'plugin' => 'platform/plugins',
    ],

    /*
    | Plugin dimension (only when kind = plugin). "none" generates ZERO
    | Nova/Filament code, stubs or dependencies (asserted by the generator).
    */
    'plugin_types' => ['nova', 'filament', 'none'],

    /*
    | Always-on CORE — never pruned (the blueprint's identity). Listed for
    | documentation/validation; not user-toggleable.
    */
    'core' => [
        'lifecycle-events',
        'search-manager',
        'body-pipeline',
        'macroable-dsl',
        'spy-seam',
    ],

    /*
    | Toggleable features. Default = all on (match the gold-standard blueprint);
    | `--features=` selects an explicit subset; the interactive multiselect
    | pre-checks all; an unknown feature name is an error. Each maps to a prune
    | entry in the engine (files + %START_<key>% markers + config keys + deps).
    */
    'features' => [
        'web-ui' => [
            'default' => true,
            'description' => 'Blade components, views, web controllers/routes.',
            'sub' => [
                'livewire' => [
                    'default' => true,
                    'description' => 'Livewire components (requires web-ui).',
                ],
            ],
        ],
        'rest-api' => [
            'default' => true,
            'description' => 'JSON API controllers, resources, ability middleware, api routes.',
        ],
        'caching' => [
            'default' => true,
            'description' => 'Caching repository decorator + event-driven invalidation.',
        ],
        'feeds' => [
            'default' => true,
            'description' => 'RSS feed + XML sitemap.',
        ],
        'scheduling' => [
            'default' => true,
            'description' => 'Scheduled-publish command + job.',
        ],
        'asset-pipeline' => [
            'default' => true,
            'description' => 'Vite (tailwind/bootstrap/vanilla) asset build pipeline.',
        ],
        'notifications' => [
            'default' => true,
            'description' => 'Publish notification listener (sub-toggle of core events).',
        ],
    ],
];
