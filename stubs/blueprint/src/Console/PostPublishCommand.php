<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Console;

use Simtabi\Laranail\Console\Facades\Console;
use Simtabi\Laranail\Console\Tools\Commands\Command;
use Some\NamespacePath\Blog\Blog;
use Some\NamespacePath\Blog\Models\Post;

class PostPublishCommand extends Command
{
    protected $signature = 'blog:post:publish {post : Post id or slug}';

    protected $description = 'Publish a blog post immediately';

    public function handle(Blog $blog): int
    {
        $post = $blog->findByKey((string) $this->argument('post'));

        if (! $post instanceof Post) {
            $this->output->writeln(Console::status()->error('Post not found.'));

            return self::FAILURE;
        }

        $blog->publish($post);
        $this->output->writeln(Console::status()->success("Published \"{$post->title}\"."));

        return self::SUCCESS;
    }
}
