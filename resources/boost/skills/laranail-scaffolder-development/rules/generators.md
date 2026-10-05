# Module Generator Commands

All generators follow the pattern: `php artisan laranail::package-scaffolder.make-{type} {Name} {ModuleName}`

## Controllers

```bash
php artisan laranail::package-scaffolder.make-controller PostController Blog
php artisan laranail::package-scaffolder.make-controller PostController Blog --api        # Resourceful API controller
php artisan laranail::package-scaffolder.make-controller PostController Blog --invokable  # Single-action controller
php artisan laranail::package-scaffolder.make-controller PostController Blog --plain      # Empty controller
```

## Models

```bash
php artisan laranail::package-scaffolder.make-model Post Blog
php artisan laranail::package-scaffolder.make-model Post Blog --migration  # Create model + migration together
php artisan laranail::package-scaffolder.make-model Post Blog --factory    # Create model + factory
php artisan laranail::package-scaffolder.make-model Post Blog --fillable=title,body  # Set $fillable
```

## Migrations & Database

```bash
php artisan laranail::package-scaffolder.make-migration create_posts_table Blog
php artisan laranail::package-scaffolder.make-migration add_slug_to_posts_table Blog
php artisan laranail::package-scaffolder.make-factory PostFactory Blog
php artisan laranail::package-scaffolder.make-seed PostDatabaseSeeder Blog
```

## Requests & Resources

```bash
php artisan laranail::package-scaffolder.make-request StorePostRequest Blog
php artisan laranail::package-scaffolder.make-request UpdatePostRequest Blog
php artisan laranail::package-scaffolder.make-resource PostResource Blog
php artisan laranail::package-scaffolder.make-resource PostCollection Blog --collection
```

## Policies & Rules

```bash
php artisan laranail::package-scaffolder.make-policy PostPolicy Blog
php artisan laranail::package-scaffolder.make-rule UniqueSlug Blog
```

## Events, Listeners & Observers

```bash
php artisan laranail::package-scaffolder.make-event PostCreated Blog
php artisan laranail::package-scaffolder.make-event PostPublished Blog
php artisan laranail::package-scaffolder.make-listener SendPostNotification Blog
php artisan laranail::package-scaffolder.make-listener SendPostNotification Blog --event=PostCreated
php artisan laranail::package-scaffolder.make-observer PostObserver Blog
```

## Jobs, Mail & Notifications

```bash
php artisan laranail::package-scaffolder.make-job ProcessPost Blog
php artisan laranail::package-scaffolder.make-job ProcessPost Blog --sync   # Synchronous job
php artisan laranail::package-scaffolder.make-mail WelcomeMail Blog
php artisan laranail::package-scaffolder.make-notification PostPublished Blog
```

## Commands, Providers & Middleware

```bash
php artisan laranail::package-scaffolder.make-command SyncPosts Blog
php artisan laranail::package-scaffolder.make-provider BlogAuthServiceProvider Blog
php artisan laranail::package-scaffolder.make-middleware EnsureUserIsAdmin Blog
```

## Service & Repository Classes

```bash
php artisan laranail::package-scaffolder.make-service PostService Blog
php artisan laranail::package-scaffolder.make-repository PostRepository Blog
php artisan laranail::package-scaffolder.make-action CreatePost Blog
php artisan laranail::package-scaffolder.make-class PostFormatter Blog
php artisan laranail::package-scaffolder.make-interface PostRepositoryInterface Blog
php artisan laranail::package-scaffolder.make-trait HasSlug Blog
```

## Enums & Casts

```bash
php artisan laranail::package-scaffolder.make-enum PostStatus Blog
php artisan laranail::package-scaffolder.make-cast MoneyValue Blog
```

## Tests

```bash
php artisan laranail::package-scaffolder.make-test PostFeatureTest Blog         # Feature test
php artisan laranail::package-scaffolder.make-test PostUnitTest Blog --unit     # Unit test
```

## Inertia Pages & Components

```bash
php artisan laranail::package-scaffolder.make Blog --inertia                         # Full Inertia module scaffold
php artisan laranail::package-scaffolder.make-inertia-page Index Blog                # Page (uses default frontend)
php artisan laranail::package-scaffolder.make-inertia-page Index Blog --vue
php artisan laranail::package-scaffolder.make-inertia-page Index Blog --react
php artisan laranail::package-scaffolder.make-inertia-page Index Blog --svelte
php artisan laranail::package-scaffolder.make-inertia-component PostCard Blog        # Reusable component
```

## Generated File Locations

| Generator | Output Path |
|---|---|
| Controller | `Modules/Blog/app/Http/Controllers/` |
| Model | `Modules/Blog/app/Models/` |
| Migration | `Modules/Blog/database/migrations/` |
| Factory | `Modules/Blog/database/factories/` |
| Seeder | `Modules/Blog/database/seeders/` |
| Request | `Modules/Blog/app/Http/Requests/` |
| Resource | `Modules/Blog/app/Http/Resources/` |
| Policy | `Modules/Blog/app/Policies/` |
| Event | `Modules/Blog/app/Events/` |
| Listener | `Modules/Blog/app/Listeners/` |
| Observer | `Modules/Blog/app/Observers/` |
| Job | `Modules/Blog/app/Jobs/` |
| Mail | `Modules/Blog/app/Mail/` |
| Notification | `Modules/Blog/app/Notifications/` |
| Command | `Modules/Blog/app/Console/Commands/` |
| Provider | `Modules/Blog/app/Providers/` |
| Middleware | `Modules/Blog/app/Http/Middleware/` |
| Service | `Modules/Blog/app/Services/` |
| Repository | `Modules/Blog/app/Repositories/` |
| Action | `Modules/Blog/app/Actions/` |
| Test | `Modules/Blog/tests/Feature/` or `tests/Unit/` |
| Rule | `Modules/Blog/app/Rules/` |
| Enum | `Modules/Blog/app/Enums/` |
| Cast | `Modules/Blog/app/Casts/` |

All paths are configurable via `config/laranail/package-scaffolder/modules.php` under `paths.generator`.
