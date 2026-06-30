<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Console;

use Simtabi\Laranail\Console\Facades\Console;
use Simtabi\Laranail\Console\Tools\Commands\Command;
use Some\NamespacePath\Blog\Blog;

class StatsCommand extends Command
{
    protected $signature = 'blog:stats';

    protected $description = 'Show blog statistics';

    public function handle(Blog $blog): int
    {
        $stats = $blog->stats();

        $this->output->writeln(
            (string) Console::keyValue([
                'Posts' => $stats['posts'],
                'Published' => $stats['published'],
                'Scheduled' => $stats['scheduled'],
                'Comments' => $stats['comments'],
                'Pending comments' => $stats['pending_comments'],
            ])->separator(' : ')
        );

        return self::SUCCESS;
    }
}
