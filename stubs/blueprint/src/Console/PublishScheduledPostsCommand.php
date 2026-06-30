<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Console;

use Simtabi\Laranail\Console\Facades\Console;
use Simtabi\Laranail\Console\Tools\Commands\Command;
use Some\NamespacePath\Blog\Blog;
use Some\NamespacePath\Blog\Jobs\PublishScheduledPosts;
use Some\NamespacePath\Blog\Models\Post;

class PublishScheduledPostsCommand extends Command
{
    protected $signature = 'blog:publish-scheduled {--queue : Dispatch to the queue instead of publishing inline}';

    protected $description = 'Publish blog posts whose scheduled time has arrived';

    public function handle(Blog $blog): int
    {
        $due = Post::query()->due()->get();

        if ($due->isEmpty()) {
            $this->output->writeln(Console::status()->info('No scheduled posts are due.'));

            return self::SUCCESS;
        }

        if ($this->option('queue')) {
            PublishScheduledPosts::dispatch();
            $this->output->writeln(Console::status()->success("Queued publication of {$due->count()} post(s)."));

            return self::SUCCESS;
        }

        $bar = Console::progress($this->output, $due->count());
        $bar->start();

        foreach ($due as $post) {
            $blog->publish($post);
            $bar->advance();
        }

        $bar->finish();

        $this->output->writeln(Console::status()->success("Published {$due->count()} scheduled post(s)."));

        return self::SUCCESS;
    }
}
