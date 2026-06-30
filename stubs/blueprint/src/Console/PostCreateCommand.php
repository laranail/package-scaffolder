<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Console;

use Simtabi\Laranail\Console\Facades\Console;
use Simtabi\Laranail\Console\Tools\Commands\Command;
use Some\NamespacePath\Blog\Blog;
use Some\NamespacePath\Blog\DataTransferObjects\PostData;
use Some\NamespacePath\Blog\Enums\PostStatus;

class PostCreateCommand extends Command
{
    protected $signature = 'blog:post:create {--title=} {--status=draft}';

    protected $description = 'Create a blog post interactively';

    public function handle(Blog $blog): int
    {
        $title = $this->option('title') ?: prompter()->text('Post title', required: true)->getResult();
        $body = prompter()->textarea('Post body', required: true)->getResult();

        $statusValue = $this->option('title')
            ? (string) $this->option('status')
            : (string) prompter()->select('Status', PostStatus::options(), PostStatus::Draft->value)->getResult();

        // tryFrom (not from) so a bad --status flag is a friendly error, not a ValueError stack trace.
        $status = PostStatus::tryFrom($statusValue);

        if ($status === null) {
            $valid = implode(', ', array_map(static fn (PostStatus $s): string => $s->value, PostStatus::cases()));
            $this->output->writeln(Console::status()->error("Invalid status [{$statusValue}]. Use one of: {$valid}."));

            return self::FAILURE;
        }

        $post = $blog->create(PostData::fromArray([
            'title' => $title,
            'body' => $body,
            'status' => $status,
        ]));

        $this->output->writeln(Console::status()->success("Created post #{$post->id} ({$post->slug})."));

        return self::SUCCESS;
    }
}
