<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Console;

use Simtabi\Laranail\Console\Facades\Console;
use Simtabi\Laranail\Console\Tools\Commands\Command;
use Some\NamespacePath\Blog\Blog;

class CategoryCreateCommand extends Command
{
    protected $signature = 'blog:category:create {name? : Category name}';

    protected $description = 'Create a blog category';

    public function handle(Blog $blog): int
    {
        $name = $this->argument('name') ?: prompter()->text('Category name', required: true)->getResult();

        $category = $blog->createCategory(['name' => $name]);

        $this->output->writeln(Console::status()->success("Category \"{$category->name}\" ({$category->slug}) created."));

        return self::SUCCESS;
    }
}
