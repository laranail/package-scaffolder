<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Console;

use Illuminate\Database\Eloquent\Builder;
use Simtabi\Laranail\Console\Facades\Console;
use Simtabi\Laranail\Console\Tools\Commands\Command;
use Some\NamespacePath\Blog\Models\Post;

class PostListCommand extends Command
{
    protected $signature = 'blog:post:list {--status= : Filter by status (draft|scheduled|published|archived)} {--limit=20}';

    protected $description = 'List blog posts in a table';

    public function handle(): int
    {
        $posts = Post::query()
            ->when($this->option('status'), fn (Builder $q, string $status) => $q->where('status', $status))
            ->latest()
            ->limit((int) $this->option('limit'))
            ->get();

        if ($posts->isEmpty()) {
            $this->output->writeln(Console::status()->info('No posts found.'));

            return self::SUCCESS;
        }

        $rows = $posts->map(fn (Post $post): array => [
            (string) $post->id,
            $post->title,
            $post->status->label(),
            $post->published_at?->toDateString() ?? '—',
        ])->all();

        $this->output->writeln(
            (string) Console::table()
                ->title('Blog posts')
                ->headers(['ID', 'Title', 'Status', 'Published'])
                ->rows($rows)
        );

        return self::SUCCESS;
    }
}
