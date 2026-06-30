<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Console;

use Simtabi\Laranail\Console\Facades\Console;
use Simtabi\Laranail\Console\Tools\Commands\Command;
use Some\NamespacePath\Blog\Blog;
use Some\NamespacePath\Blog\Models\Post;

class PostUnpublishCommand extends Command
{
    protected $signature = 'blog:post:unpublish {post : Post id or slug}';

    protected $description = 'Revert a blog post back to draft';

    public function handle(Blog $blog): int
    {
        $post = $blog->findByKey((string) $this->argument('post'));

        if (! $post instanceof Post) {
            $this->output->writeln(Console::status()->error('Post not found.'));

            return self::FAILURE;
        }

        $blog->unpublish($post);
        $this->output->writeln(Console::status()->success("Unpublished \"{$post->title}\"."));

        return self::SUCCESS;
    }
}
