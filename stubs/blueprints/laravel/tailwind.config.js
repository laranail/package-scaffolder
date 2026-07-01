/** @type {import('tailwindcss').Config} */

// Tailwind v4 is CSS-first: the theme tokens live in resources/assets/css/tailwind.css
// (the `@theme` block) and source scanning is driven by `@source` there. This JS config
// is loaded via `@config "./tailwind.config.js"` in that file — it adds the familiar
// `content` / `darkMode` / `theme.extend` / `plugins` surface (and powers the Tailwind
// IntelliSense editor extension). Theme tokens stay in CSS (`@theme`); use `theme.extend`
// here only for things you'd rather express in JS.
export default {
    content: [
        './resources/views/**/*.blade.php',
        './resources/assets/scripts/**/*.js',
    ],
    darkMode: 'class',
    theme: {
        extend: {},
    },
    plugins: [],
};
