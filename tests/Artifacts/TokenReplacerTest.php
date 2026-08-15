<?php

namespace Simtabi\Laranail\Package\Scaffolder\Tests\Artifacts;

use PHPUnit\Framework\TestCase;
use Simtabi\Laranail\Package\Scaffolder\Support\Artifacts\TokenReplacer;

class TokenReplacerTest extends TestCase
{
    /** @return array{namespaceBase:string,studly:string,lower:string,vendor:string} */
    private function target(): array
    {
        return ['namespaceBase' => 'Acme\\Shop', 'studly' => 'Invoicing', 'lower' => 'invoicing', 'vendor' => 'acme'];
    }

    public function test_rewrites_php_namespace_and_manager_class(): void
    {
        $this->assertSame(
            'namespace Acme\\Shop\\Invoicing\\Models;',
            TokenReplacer::replace('namespace Some\\NamespacePath\\Blog\\Models;', $this->target())
        );

        // root + the manager class named after the artifact
        $this->assertSame(
            'use Acme\\Shop\\Invoicing\\Invoicing;',
            TokenReplacer::replace('use Some\\NamespacePath\\Blog\\Blog;', $this->target())
        );
    }

    public function test_rewrites_class_prefixes_and_facade(): void
    {
        $this->assertSame('InvoicingServiceProvider', TokenReplacer::replace('BlogServiceProvider', $this->target()));
        $this->assertSame('Invoicing::morphMap()', TokenReplacer::replace('Blog::morphMap()', $this->target()));
    }

    public function test_rewrites_composite_name_forms_distinctly(): void
    {
        $this->assertSame('acme/invoicing', TokenReplacer::replace('modules/blog', $this->target()));
        $this->assertSame('acme-invoicing', TokenReplacer::replace('modules-blog', $this->target()));
        $this->assertSame("config('acme.invoicing.cache.enabled')", TokenReplacer::replace("config('modules.blog.cache.enabled')", $this->target()));
        // view/translation namespace rides on the composer-name token
        $this->assertSame("view('acme/invoicing::layouts.master')", TokenReplacer::replace("view('modules/blog::layouts.master')", $this->target()));
    }

    public function test_rewrites_json_escaped_psr4_root(): void
    {
        $this->assertSame(
            '"Acme\\\\Shop\\\\Invoicing\\\\": "src/"',
            TokenReplacer::replace('"Some\\\\NamespacePath\\\\Blog\\\\": "src/"', $this->target())
        );
    }

    public function test_rewrites_lower_identifiers(): void
    {
        $this->assertSame("'invoicing'", TokenReplacer::replace("'blog'", $this->target()));
        $this->assertSame('invoicing:publish-scheduled', TokenReplacer::replace('blog:publish-scheduled', $this->target()));
    }

    public function test_identity_rename_under_a_new_base_only_moves_namespace(): void
    {
        // Generating a "Blog" artifact under base "Modules" + vendor "modules"
        // only rewrites the namespace base; names/slugs/keys are unchanged.
        $t = ['namespaceBase' => 'Modules', 'studly' => 'Blog', 'lower' => 'blog', 'vendor' => 'modules'];
        $this->assertSame('namespace Modules\\Blog\\Providers;', TokenReplacer::replace('namespace Some\\NamespacePath\\Blog\\Providers;', $t));
        $this->assertSame('modules/blog', TokenReplacer::replace('modules/blog', $t));
        $this->assertSame('BlogServiceProvider', TokenReplacer::replace('BlogServiceProvider', $t));
    }

    /** @return array{namespaceBase:string,studly:string,lower:string,vendor:string,entityStudly:string,entityStudlyPlural:string,entityLower:string,entityPlural:string} */
    private function entityTarget(): array
    {
        return [
            'namespaceBase' => 'Acme', 'studly' => 'Customer', 'lower' => 'customer', 'vendor' => 'acme',
            'entityStudly' => 'Order', 'entityStudlyPlural' => 'Orders', 'entityLower' => 'order', 'entityPlural' => 'orders',
        ];
    }

    public function test_entity_tokenization_rewrites_identifiers(): void
    {
        $t = $this->entityTarget();
        $this->assertSame('OrderController', TokenReplacer::replace('PostController', $t));
        $this->assertSame('OrderStatus', TokenReplacer::replace('PostStatus', $t));
        $this->assertSame('recentOrders()', TokenReplacer::replace('recentPosts()', $t));
        $this->assertSame('$order = $x;', TokenReplacer::replace('$post = $x;', $t));
        $this->assertSame('{order}', TokenReplacer::replace('{post}', $t));
        $this->assertSame('customer_orders', TokenReplacer::replace('blog_posts', $t));
        $this->assertSame('customer_order', TokenReplacer::replace('blog_post', $t));
        $this->assertSame('$this->orderService', TokenReplacer::replace('$this->postService', $t));
    }

    public function test_entity_tokenization_protects_framework_and_english(): void
    {
        $t = $this->entityTarget();
        $this->assertSame("Route::post('/', [C::class]);", TokenReplacer::replace("Route::post('/', [C::class]);", $t));
        $this->assertSame('$this->postJson($url);', TokenReplacer::replace('$this->postJson($url);', $t));
        $this->assertSame('->post(route(...))', TokenReplacer::replace('->post(route(...))', $t));
        $this->assertSame('MySQL/Postgres use', TokenReplacer::replace('MySQL/Postgres use', $t));
        $this->assertSame('a compost heap, posted today', TokenReplacer::replace('a compost heap, posted today', $t));
    }

    public function test_entity_defaults_to_post_identity_when_no_entity_keys(): void
    {
        $t = ['namespaceBase' => 'Modules', 'studly' => 'Blog', 'lower' => 'blog', 'vendor' => 'modules'];
        $this->assertSame('PostController $post posts', TokenReplacer::replace('PostController $post posts', $t));
    }

    /**
     * Regression: the entity replacement must be inserted LITERALLY. An entity form
     * containing regex-replacement syntax (`$1`, `\1`, `$`) must not be interpreted
     * as a backreference (the pre-hardening `preg_replace` would have expanded it).
     */
    public function test_entity_replacement_is_literal_not_a_backreference(): void
    {
        $t = [
            'namespaceBase' => 'Acme', 'studly' => 'Shop', 'lower' => 'shop', 'vendor' => 'acme',
            'entityStudly' => 'A$1B', 'entityStudlyPlural' => 'A$1Bs',
            'entityLower' => 'a$1b', 'entityPlural' => 'a$1bs',
        ];

        $this->assertSame('A$1B', TokenReplacer::replace('Post', $t));
        $this->assertSame('a$1b', TokenReplacer::replace('post', $t));
        // a literal backslash form must survive too
        $t['entityStudly'] = 'X\\1Y';
        $this->assertSame('X\\1Y', TokenReplacer::replace('Post', $t));
    }
}
