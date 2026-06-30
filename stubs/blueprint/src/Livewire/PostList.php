<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;
use Some\NamespacePath\Blog\Models\Post;

/**
 * A searchable, paginated list of published posts. Registered only when
 * Livewire is installed, under the configurable component prefix
 * (default: <livewire:modules-blog.post-list />).
 */
class PostList extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $posts = Post::query()
            ->published()
            ->when($this->search !== '', fn (Builder $query) => $query->whereRaw('title LIKE ? ESCAPE ?', ['%'.Post::escapeLike($this->search).'%', '\\']))
            ->latest('published_at')
            ->paginate((int) config('modules.blog.pagination.per_page', 15));

        return view('modules/blog::livewire.post-list', ['posts' => $posts]);
    }
}
