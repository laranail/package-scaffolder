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

    /*
    | Files/dirs owned solely by a feature — deleted (in addition to stripping the
    | feature's %marker% wiring) when the feature is OFF. Paths are relative to the
    | generated artifact root; a trailing "/" means the whole directory. See
    | REFACTOR_AUDIT.md (entry 0002) for the derivation. Shared files are NOT listed
    | here (they stay). Note: the `Assets` component + view are intentionally NOT in
    | asset-pipeline — they stay as a graceful no-op so web-ui views don't dangle;
    | they're removed with `web-ui` (View/Components/) when web-ui itself is off.
    */
    'feature_files' => [
        'caching' => [
            'src/Repositories/CachingPostRepository.php',
            'src/Listeners/FlushBlogCache.php',
            'tests/Feature/CachingTest.php',
        ],
        'rest-api' => [
            'src/Http/Controllers/Api/',
            'src/Http/Resources/',
            'src/Http/Middleware/EnsureApiAbility.php',
            'src/Http/Requests/IndexPostRequest.php',
            'routes/api.php',
            'tests/Feature/Api/',
            'tests/Feature/ApiAbilityTest.php',
            'docs/tools/rest-api.md',
            'docs/tools/openapi.yaml',
        ],
        'feeds' => [
            'src/Http/Controllers/FeedController.php',
            'resources/views/feed/',
            'tests/Feature/PostFeedTest.php',
        ],
        'scheduling' => [
            'src/Console/PublishScheduledPostsCommand.php',
            'src/Jobs/PublishScheduledPosts.php',
            'tests/Feature/SchedulingTest.php',
        ],
        'asset-pipeline' => [
            'vite.config.js',
            'package.json',
            'resources/assets/',
            'tests/Feature/AssetsComponentTest.php',
            'docs/tools/assets.md',
        ],
        'notifications' => [
            'src/Listeners/SendPostPublishedNotification.php',
            'src/Notifications/PostPublishedNotification.php',
        ],
        'livewire' => [
            'src/Livewire/',
            'resources/views/livewire/',
            'tests/Feature/LivewireTest.php',
            'docs/tools/livewire.md',
        ],
        'web-ui' => [
            'src/Http/Controllers/BlogController.php',
            'src/Http/Controllers/CommentController.php',
            'src/View/Components/',
            'resources/views/components/',
            'resources/views/layouts/',
            'resources/views/partials/',
            'resources/views/posts/',
            'tests/Feature/ComponentTest.php',
            'tests/Feature/UiComponentsTest.php',
            'tests/Feature/WebRoutesTest.php',
            'tests/Feature/CustomComponentPrefixTest.php',
            'docs/tools/components.md',
            'docs/tools/partials.md',
        ],
    ],

    /*
    | Plugin-dimension files. The matching plugin's files are KEPT; the others are
    | removed. The shared panels test/docs are removed only when plugin = none.
    */
    'plugin_files' => [
        'filament' => [
            'src/Filament/',
            'src/Providers/Integrations/FilamentBlogServiceProvider.php',
        ],
        'nova' => [
            'src/Nova/',
            'src/Providers/Integrations/NovaBlogServiceProvider.php',
        ],
        'shared' => [
            'tests/Feature/PanelsTest.php',
            'docs/tools/panels.md',
        ],
    ],

    /*
    | Composer dependencies owned by a feature — dropped from require/require-dev/
    | suggest when the feature is OFF (matched as substrings of the package name).
    | Core-search backends (scout) and markdown (commonmark) stay: search and the
    | body pipeline are core, only their optional drivers are opt-in at runtime.
    */
    'feature_deps' => [
        'rest-api' => ['laravel/sanctum'],
        'livewire' => ['livewire/livewire'],
    ],
];
