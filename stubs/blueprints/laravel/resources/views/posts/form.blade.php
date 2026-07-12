@csrf

@php($post = $post ?? null)

<label>
    {{ __('modules/blog::blog.field.title') }}
    <input type="text" name="title" value="{{ old('title', $post?->title) }}" required>
</label>

<label>
    {{ __('modules/blog::blog.field.category') }}
    <select name="category_id">
        <option value="">—</option>
        @foreach ($categories as $category)
            <option value="{{ $category->id }}" @selected(old('category_id', $post?->category_id) == $category->id)>
                {{ $category->name }}
            </option>
        @endforeach
    </select>
</label>

<label>
    {{ __('modules/blog::blog.field.status') }}
    <select name="status">
        @foreach (\Some\NamespacePath\Blog\Enums\PostStatus::options() as $value => $label)
            <option value="{{ $value }}" @selected(old('status', $post?->status?->value) === $value)>
                {{ $label }}
            </option>
        @endforeach
    </select>
</label>

<label>
    {{ __('modules/blog::blog.field.excerpt') }}
    <textarea name="excerpt">{{ old('excerpt', $post?->excerpt) }}</textarea>
</label>

<label>
    {{ __('modules/blog::blog.field.body') }}
    <textarea name="body" required>{{ old('body', $post?->body) }}</textarea>
</label>

@error('title') <p class="blog__error">{{ $message }}</p> @enderror
@error('body') <p class="blog__error">{{ $message }}</p> @enderror
