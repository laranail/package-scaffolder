<?php

declare(strict_types=1);

namespace Simtabi\Laranail\PackageScaffolder\Services;

use Illuminate\Support\Facades\File;

class PackageStructureService
{
    protected array $createdDirs = [];

    protected array $createdFiles = [];

    public function __construct(
        protected StubService $stubService,
        protected PlaceholderService $placeholderService
    ) {}

    public function create(array $blueprint, string $basePath): bool
    {
        try {
            foreach ($blueprint['directories'] ?? [] as $dir) {
                $fullPath = $basePath.'/'.$dir;
                File::makeDirectory($fullPath, 0755, true);
                $this->createdDirs[] = $fullPath;
            }

            return true;
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    protected function rollback(): void
    {
        foreach (array_reverse($this->createdFiles) as $file) {
            if (File::exists($file)) {
                File::delete($file);
            }
        }
        foreach (array_reverse($this->createdDirs) as $dir) {
            if (File::isDirectory($dir)) {
                File::deleteDirectory($dir);
            }
        }
    }
}
