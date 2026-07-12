<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Database\Seeders;

use Illuminate\Database\Seeder;
use Some\NamespacePath\Blog\Models\Category;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Engineering', 'Product', 'Design', 'Company News'] as $name) {
            Category::query()->firstOrCreate(['name' => $name]);
        }
    }
}
