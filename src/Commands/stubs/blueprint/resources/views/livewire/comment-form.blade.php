<div class="blog__comment-form">
    @if ($submitted)
        <x-dynamic-component :component="$blogComponentPrefix.'::alert'" type="success">{{ __('modules/blog::blog.comment_submitted') }}</x-dynamic-component>
    @endif

    <form wire:submit="submit">
        <label>
            {{ __('modules/blog::blog.field.author_name') }}
            <input type="text" wire:model="author_name">
        </label>
        @error('author_name') <p class="blog__error">{{ $message }}</p> @enderror

        <label>
            {{ __('modules/blog::blog.field.comment') }}
            <textarea wire:model="body"></textarea>
        </label>
        @error('body') <p class="blog__error">{{ $message }}</p> @enderror

        <button type="submit">{{ __('modules/blog::blog.submit_comment') }}</button>
    </form>
</div>
