<div class="blog__livewire-post-list">
    <input
        type="search"
        wire:model.live.debounce.300ms="search"
        placeholder="{{ __('modules/blog::blog.search') }}"
        class="blog__search"
    >

    @forelse ($posts as $post)
        <x-dynamic-component :component="$blogComponentPrefix.'::post-card'" :post="$post" />
    @empty
        <p>{{ __('modules/blog::blog.empty') }}</p>
    @endforelse

    {{ $posts->links() }}
</div>
