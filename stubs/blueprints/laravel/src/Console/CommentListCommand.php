<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Console;

use Illuminate\Database\Eloquent\Builder;
use Simtabi\Laranail\Console\Facades\Console;
use Simtabi\Laranail\Console\Tools\Commands\Command;
use Some\NamespacePath\Blog\Models\Comment;

class CommentListCommand extends Command
{
    protected $signature = 'blog:comment:list {--pending : Only unapproved comments}';

    protected $description = 'List blog comments';

    public function handle(): int
    {
        $comments = Comment::query()
            ->when($this->option('pending'), fn (Builder $q) => $q->where('approved', false))
            ->latest()
            ->limit(50)
            ->get();

        if ($comments->isEmpty()) {
            $this->output->writeln(Console::status()->info('No comments found.'));

            return self::SUCCESS;
        }

        $rows = $comments->map(fn (Comment $c): array => [
            (string) $c->id,
            $c->commentable_type.'#'.$c->commentable_id,
            $c->author_name,
            $c->approved ? 'yes' : 'no',
        ])->all();

        $this->output->writeln(
            (string) Console::table()
                ->title('Comments')
                ->headers(['ID', 'On', 'Author', 'Approved'])
                ->rows($rows)
        );

        return self::SUCCESS;
    }
}
