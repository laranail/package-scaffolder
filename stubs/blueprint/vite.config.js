import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

// The Blog package ships THREE independent CSS bundles — pick one per app via
// config('modules.blog.ui.framework'): 'tailwind' | 'bootstrap' | 'vanilla' | 'none'.
// Each builds into the package-local public/build (with a manifest); publish it to
// the host with: php artisan vendor:publish --tag="blog::assets" (→ public/vendor/blog).
export default defineConfig({
    build: {
        outDir: 'public/build',
        emptyOutDir: true,
        manifest: true,
    },
    css: {
        preprocessorOptions: {
            // Bootstrap's own SCSS still uses legacy @import — silence its
            // (third-party) deprecation noise without hiding ours.
            scss: { quietDeps: true },
        },
    },
    plugins: [
        laravel({
            publicDirectory: 'public',
            buildDirectory: 'build',
            input: [
                // Tailwind v4 bundle (CSS-first; theme in tailwind.css).
                'resources/assets/css/tailwind.css',
                'resources/assets/scripts/tailwind.js',
                // Bootstrap 5 bundle (theme in sass/_variables.scss).
                'resources/assets/sass/bootstrap.scss',
                'resources/assets/scripts/bootstrap.js',
                // Vanilla bundle (framework-agnostic base styles only).
                'resources/assets/scripts/app.js',
            ],
            refresh: ['resources/views/**', 'resources/assets/**'],
        }),
        // Processes the Tailwind entry (the only file that @imports "tailwindcss");
        // the Bootstrap/vanilla entries don't, so it leaves them untouched.
        tailwindcss(),
    ],
});
