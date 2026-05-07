<?php

declare(strict_types=1);

namespace Simtabi\Laranail\PackageScaffolder\Services;

use Illuminate\Support\Str;

class PlaceholderService
{
    protected array $placeholders = [];

    public function set(string $key, mixed $value): self
    {
        $this->placeholders[$key] = $value;

        return $this;
    }

    public function setMany(array $placeholders): self
    {
        $this->placeholders = array_merge($this->placeholders, $placeholders);

        return $this;
    }

    public function replace(string $content): string
    {
        foreach ($this->placeholders as $key => $value) {
            $content = Str::replace('{{'.$key.'}}', (string) $value, $content);
        }

        return $content;
    }

    public function all(): array
    {
        return $this->placeholders;
    }
}
