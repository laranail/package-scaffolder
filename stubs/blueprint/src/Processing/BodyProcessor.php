<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Processing;

use Closure;
use Illuminate\Contracts\Container\Container;
use Illuminate\Pipeline\Pipeline;
use Some\NamespacePath\Blog\Processing\Stages\SanitizeHtmlStage;

/**
 * Runs a post body through an ordered, composable set of save-time stages
 * (an {@see Pipeline}). The default stage is HTML sanitization; consumers add
 * their own — `Blog::pipe(MyStage::class)`, the `blog.body.stages` container tag,
 * or `config('modules.blog.processing.stages')`. Invoked from the model `saving` hook so
 * every writer (facade, [[plugins]]Filament, Nova, [[/plugins]]raw Eloquent) goes through it.
 *
 * A stage is `handle(string $body, Closure $next): string`; a stage that does not
 * call `$next` short-circuits the rest (lets a consumer veto/replace processing).
 */
class BodyProcessor
{
    /** @var array<int, class-string|Closure> */
    private array $runtimeStages = [];

    public function __construct(private readonly Container $container) {}

    public function pipe(string|Closure $stage): static
    {
        $this->runtimeStages[] = $stage;

        return $this;
    }

    public function process(string $body): string
    {
        return (new Pipeline($this->container))
            ->send($body)
            ->through($this->stages())
            ->thenReturn();
    }

    /**
     * config stages → container-tagged stages → runtime (`Blog::pipe`) stages,
     * then ALWAYS a final {@see SanitizeHtmlStage}. Sanitization runs last (gated
     * internally on config('modules.blog.security.sanitize_html')) so whatever a consumer
     * transform stage produced is cleaned before it is persisted and later
     * rendered as HTML — consumer stages can't reintroduce XSS.
     *
     * config values must be class-strings (so `config:cache` stays serializable);
     * closures arrive only via the runtime DSL.
     *
     * @return array<int, class-string|object|Closure>
     */
    private function stages(): array
    {
        /** @var array<int, class-string> $configured */
        $configured = (array) config('modules.blog.processing.stages', []);

        return [
            ...$configured,
            ...iterator_to_array($this->container->tagged('blog.body.stages')),
            ...$this->runtimeStages,
            SanitizeHtmlStage::class,
        ];
    }
}
