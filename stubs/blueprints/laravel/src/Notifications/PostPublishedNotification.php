<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Route;
use Some\NamespacePath\Blog\Models\Post;

class PostPublishedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Post $post,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return (array) config('modules.blog.notifications.channels', ['mail']);
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your post is now live')
            ->greeting('Nice work!')
            ->line("\"{$this->post->title}\" has been published.")
            ->action('View post', $this->postUrl())
            ->line('Thanks for writing.');
    }

    /**
     * Resolve the post URL from the named route so it honours the configurable
     * web prefix (route map), falling back to the conventional path when the web
     * routes are disabled.
     */
    private function postUrl(): string
    {
        $route = (string) config('modules.blog.ui.routes.show', 'blog.show');

        return Route::has($route)
            ? route($route, $this->post->slug)
            : url('/'.trim((string) config('modules.blog.routes.web.prefix', 'blog'), '/').'/'.$this->post->slug);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'post_id' => $this->post->id,
            'title' => $this->post->title,
            'slug' => $this->post->slug,
        ];
    }
}
