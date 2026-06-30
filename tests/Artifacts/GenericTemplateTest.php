<?php

namespace Simtabi\Laranail\Package\Scaffolder\Tests\Artifacts;

use Composer\Autoload\ClassLoader;
use Illuminate\Filesystem\Filesystem;
use Simtabi\Laranail\Package\Scaffolder\Support\Artifacts\ArtifactGenerator;
use Simtabi\Laranail\Package\Scaffolder\Support\Artifacts\GenerationRequest;
use Simtabi\Laranail\Package\Scaffolder\Tests\BaseTestCase;
use Simtabi\Laranail\Package\Tools\Providers\PackageServiceProvider;

/**
 * Proves the stub is a domain-agnostic template: generating non-blog artifacts
 * (Customer/Account, Admin/Role) leaves zero `blog` and zero ENTITY `post`/`tag-as-post`
 * leftovers. The entity-leftover regexes are exactly the ones the tokenizer targets,
 * so framework API (Route::post, ->postJson, ->post()) and words like `Postgres` are
 * intentionally excluded — per the agreed bar (entity-scope, framework excluded).
 * Comment/Category/Tag stay by design (the generic supporting layer).
 */
class GenericTemplateTest extends BaseTestCase
{
    private Filesystem $fs;

    private array $targets = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->fs = new Filesystem;
    }

    protected function tearDown(): void
    {
        foreach ($this->targets as $t) {
            $this->fs->deleteDirectory($t);
        }
        parent::tearDown();
    }

    private function generate(string $base, string $name, string $vendor, string $entity): string
    {
        $config = require dirname(__DIR__, 2).'/config/artifacts.php';
        $target = sys_get_temp_dir().'/laranail-generic-'.uniqid();
        $this->targets[] = $target;

        $all = ['web-ui', 'livewire', 'rest-api', 'caching', 'feeds', 'scheduling', 'asset-pipeline', 'notifications'];
        (new ArtifactGenerator($this->fs, $config, dirname(__DIR__, 2).'/vendor/bin/pint'))
            ->generate(new GenerationRequest('package', 'none', $all, $name, $base, $vendor, false, $entity), dirname(__DIR__, 2).'/src/Commands/stubs/blueprint', $target);

        return $target;
    }

    /** @return array{blog: list<string>, entity: list<string>} */
    private function leftovers(string $target): array
    {
        $blog = [];
        $entity = [];
        foreach ($this->fs->allFiles($target) as $f) {
            $c = $this->fs->get($f->getPathname());
            $rel = $f->getRelativePathname();
            if (preg_match('/blog/i', $c) === 1) {
                $blog[] = $rel;
            }
            // the exact patterns the entity tokenizer targets (frameworks/Postgres excluded)
            if (preg_match('/Post(?![a-z])/', $c) === 1
                || preg_match('/(?<![a-zA-Z])posts(?![a-z])/', $c) === 1
                || preg_match('/(?<![a-zA-Z])post(?![a-z(]|Json)/', $c) === 1) {
                $entity[] = $rel;
            }
        }

        return ['blog' => $blog, 'entity' => $entity];
    }

    public function test_customer_artifact_is_blog_and_post_free()
    {
        $t = $this->generate('Generic1', 'Customer', 'acme', 'Account');

        // entity files renamed so basename matches the tokenized class (PSR-4)
        $this->assertFileExists($t.'/src/Models/Account.php');
        $this->assertFileExists($t.'/src/Http/Controllers/Api/AccountController.php');
        $this->assertFileDoesNotExist($t.'/src/Models/Post.php');
        $this->assertStringContainsString('namespace Generic1\\Customer\\Models;', $this->fs->get($t.'/src/Models/Account.php'));
        $this->assertStringContainsString('class Account', $this->fs->get($t.'/src/Models/Account.php'));
        // supporting layer kept by design
        $this->assertFileExists($t.'/src/Models/Comment.php');
        $this->assertFileExists($t.'/src/Models/Tag.php');

        // entity VIEW dirs/files renamed too, so code refs (view('…::accounts.show'),
        // livewire 'account-list') resolve — regression guard for the renamePaths fix
        $this->assertDirectoryExists($t.'/resources/views/accounts');
        $this->assertDirectoryDoesNotExist($t.'/resources/views/posts');
        $this->assertFileExists($t.'/resources/views/livewire/account-list.blade.php');
        $this->assertFileDoesNotExist($t.'/resources/views/livewire/post-list.blade.php');

        $l = $this->leftovers($t);
        $this->assertSame([], $l['blog'], 'residual blog tokens in: '.implode(', ', $l['blog']));
        $this->assertSame([], $l['entity'], 'residual entity post tokens in: '.implode(', ', $l['entity']));

        // every generated PHP file is valid
        $bad = [];
        foreach ($this->fs->allFiles($t) as $f) {
            if ($f->getExtension() !== 'php') {
                continue;
            }
            $out = [];
            exec('php -l '.escapeshellarg($f->getPathname()).' 2>&1', $out, $code);
            if ($code !== 0) {
                $bad[] = $f->getRelativePathname();
            }
        }
        $this->assertSame([], $bad, 'invalid PHP: '.implode(', ', $bad));
    }

    public function test_admin_artifact_is_blog_and_post_free()
    {
        $t = $this->generate('Generic2', 'Admin', 'acme', 'Role');

        $this->assertFileExists($t.'/src/Models/Role.php');
        $l = $this->leftovers($t);
        $this->assertSame([], $l['blog'], 'residual blog tokens in: '.implode(', ', $l['blog']));
        $this->assertSame([], $l['entity'], 'residual entity post tokens in: '.implode(', ', $l['entity']));
    }

    public function test_generated_non_blog_provider_boots()
    {
        if (! class_exists(PackageServiceProvider::class)) {
            $this->markTestSkipped('laranail/package-tools not installed.');
        }

        $t = $this->generate('GenericBoot', 'Customer', 'acme', 'Account');

        $loader = new ClassLoader;
        $loader->addPsr4('GenericBoot\\Customer\\', $t.'/src');
        $loader->register();

        $this->app->register('GenericBoot\\Customer\\Providers\\CustomerServiceProvider');

        $this->assertSame('Customer', config('acme.customer.name'));
        $this->assertInstanceOf('GenericBoot\\Customer\\Customer', $this->app->make('GenericBoot\\Customer\\Customer'));
        // the renamed entity model resolves + its repository binding is wired
        $this->assertInstanceOf(
            'GenericBoot\\Customer\\Repositories\\EloquentAccountRepository',
            $this->app->make('GenericBoot\\Customer\\Contracts\\AccountRepositoryInterface'),
        );
    }
}
