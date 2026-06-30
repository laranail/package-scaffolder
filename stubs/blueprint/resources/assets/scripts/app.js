// Blog package — "vanilla" bundle entry.
//
// Ships ONLY the package's framework-agnostic base styles (no Tailwind, no
// Bootstrap) — the bring-your-own-CSS option. Selected per app via
// config('modules.blog.ui.framework') = 'vanilla'.
import '../sass/app.scss';
