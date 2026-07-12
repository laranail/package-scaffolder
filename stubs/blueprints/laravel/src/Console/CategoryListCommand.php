<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Console;

use Simtabi\Laranail\Console\Facades\Console;
use Simtabi\Laranail\Console\Tools\Commands\Command;
use Some\NamespacePath\Blog\Models\Category;

class CategoryListCommand extends Command
{
    protected $signature = 'blog:category:list';

    protected $description = 'List blog categories with post counts';

    public function handle(): int
    {
        $categories = Category::query()->withCount('posts')->orderBy('name')->get();

        if ($categories->isEmpty()) {
            $this->output->writeln(Console::status()->info('No categories found.'));

            return self::SUCCESS;
        }

        $rows = $categories->map(fn (Category $c): array => [
            (string) $c->id,
            $c->name,
            $c->slug,
            (string) $c->posts_count,
        ])->all();

        $this->output->writeln(
            (string) Console::table()
                ->title('Categories')
                ->headers(['ID', 'Name', 'Slug', 'Posts'])
                ->rows($rows)
        );

        return self::SUCCESS;
    }
}
