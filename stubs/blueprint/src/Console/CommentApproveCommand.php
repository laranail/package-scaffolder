<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Console;

use Simtabi\Laranail\Console\Facades\Console;
use Simtabi\Laranail\Console\Tools\Commands\Command;
use Some\NamespacePath\Blog\Models\Comment;

class CommentApproveCommand extends Command
{
    // `comment` is optional so `--all` is invocable on its own (a required arg
    // made the --all path impossible to reach).
    protected $signature = 'blog:comment:approve {comment? : Comment id} {--all : Approve every pending comment}';

    protected $description = 'Approve a comment (or all pending comments)';

    public function handle(): int
    {
        if ($this->option('all')) {
            // Update through the model so the CommentObserver fires CommentApproved
            // per comment — a mass query-builder update() would skip events, leaving
            // the read cache stale and host listeners unrun. lazyById() (cursor by id,
            // not offset) stays correct while the `approved` filter column mutates.
            $count = 0;

            Comment::query()->where('approved', false)->lazyById()->each(function (Comment $comment) use (&$count): void {
                $comment->update(['approved' => true]);
                $count++;
            });

            $this->output->writeln(Console::status()->success("Approved {$count} comment(s)."));

            return self::SUCCESS;
        }

        $comment = Comment::query()->find($this->argument('comment'));

        if (! $comment instanceof Comment) {
            $this->output->writeln(Console::status()->error('Comment not found.'));

            return self::FAILURE;
        }

        $comment->update(['approved' => true]);
        $this->output->writeln(Console::status()->success("Approved comment #{$comment->id}."));

        return self::SUCCESS;
    }
}
