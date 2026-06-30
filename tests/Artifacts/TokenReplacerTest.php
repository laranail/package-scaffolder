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

    public function test_rewrites_php_namespace_and_manager_class()
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

    public function test_rewrites_class_prefixes_and_facade()
    {
        $this->assertSame('InvoicingServiceProvider', TokenReplacer::replace('BlogServiceProvider', $this->target()));
        $this->assertSame('Invoicing::morphMap()', TokenReplacer::replace('Blog::morphMap()', $this->target()));
    }

    public function test_rewrites_composite_name_forms_distinctly()
    {
        $this->assertSame('acme/invoicing', TokenReplacer::replace('modules/blog', $this->target()));
        $this->assertSame('acme-invoicing', TokenReplacer::replace('modules-blog', $this->target()));
        $this->assertSame("config('acme.invoicing.cache.enabled')", TokenReplacer::replace("config('modules.blog.cache.enabled')", $this->target()));
        // view/translation namespace rides on the composer-name token
        $this->assertSame("view('acme/invoicing::layouts.master')", TokenReplacer::replace("view('modules/blog::layouts.master')", $this->target()));
    }

    public function test_rewrites_json_escaped_psr4_root()
    {
        $this->assertSame(
            '"Acme\\\\Shop\\\\Invoicing\\\\": "src/"',
            TokenReplacer::replace('"Some\\\\NamespacePath\\\\Blog\\\\": "src/"', $this->target())
        );
    }

    public function test_rewrites_lower_identifiers()
    {
        $this->assertSame("'invoicing'", TokenReplacer::replace("'blog'", $this->target()));
        $this->assertSame('invoicing:publish-scheduled', TokenReplacer::replace('blog:publish-scheduled', $this->target()));
    }

    public function test_identity_rename_under_a_new_base_only_moves_namespace()
    {
        // Generating a "Blog" artifact under base "Modules" + vendor "modules"
        // only rewrites the namespace base; names/slugs/keys are unchanged.
        $t = ['namespaceBase' => 'Modules', 'studly' => 'Blog', 'lower' => 'blog', 'vendor' => 'modules'];
        $this->assertSame('namespace Modules\\Blog\\Providers;', TokenReplacer::replace('namespace Some\\NamespacePath\\Blog\\Providers;', $t));
        $this->assertSame('modules/blog', TokenReplacer::replace('modules/blog', $t));
        $this->assertSame('BlogServiceProvider', TokenReplacer::replace('BlogServiceProvider', $t));
    }
}
