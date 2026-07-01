<div {{ $attributes->merge(['class' => 'blog__comment-form']) }}>
    @if ($canComment)
        <form method="POST" action="{{ $blogRoute('comment_store', $post) }}">
            @csrf

            {{-- Honeypot + render timestamp (anti-spam; mirror of StoreCommentRequest). --}}
            <input type="text" name="{{ config('modules.blog.comments.honeypot', 'website') }}" value="" tabindex="-1" autocomplete="off" style="display:none">
            <input type="hidden" name="rendered_at" value="{{ now()->timestamp }}">

            @guest
                <label class="blog__field">
                    {{ __('modules/blog::blog.field.author_name') }}
                    <input type="text" name="author_name" value="{{ old('author_name') }}" required>
                </label>
                @error('author_name') <p class="blog__error">{{ $message }}</p> @enderror
            @endguest

            <label class="blog__field">
                {{ __('modules/blog::blog.field.comment') }}
                <textarea name="body" required>{{ old('body') }}</textarea>
            </label>
            @error('body') <p class="blog__error">{{ $message }}</p> @enderror

            <button type="submit">{{ __('modules/blog::blog.submit_comment') }}</button>
        </form>
    @else
        <x-dynamic-component :component="$blogComponentPrefix.'::login-prompt'" :message="__('modules/blog::blog.login_to_comment')" />
    @endif
</div>
