<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Console;

use Simtabi\Laranail\Console\Facades\Console;
use Simtabi\Laranail\Console\Tools\Commands\Command;
use Some\NamespacePath\Blog\Blog;
use Some\NamespacePath\Blog\Models\Post;

class PostDeleteCommand extends Command
{
    protected $signature = 'blog:post:delete {post : Post id or slug} {--force : Permanently delete}';

    protected $description = 'Delete a blog post';

    public function handle(Blog $blog): int
    {
        $post = $blog->findByKey((string) $this->argument('post'), withTrashed: true);

        if (! $post instanceof Post) {
            $this->output->writeln(Console::status()->error('Post not found.'));

            return self::FAILURE;
        }

        if (! $this->confirm("Delete \"{$post->title}\"?", false)) {
            $this->output->writeln(Console::status()->warning('Aborted.'));

            return self::SUCCESS;
        }

        $this->option('force') ? $blog->forceDelete($post) : $blog->delete($post);
        $this->output->writeln(Console::status()->success('Post deleted.'));

        return self::SUCCESS;
    }
}
