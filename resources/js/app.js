import './bootstrap';

// Self-hosted variable fonts: no third-party font requests, works under a strict CSP.
import '@fontsource-variable/inter';
import '@fontsource-variable/jetbrains-mono';

import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();
