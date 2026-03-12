import './bootstrap';
import Alpine from 'alpinejs';

// Dark mode — restore from localStorage before first paint
if (localStorage.getItem('darkMode') === 'true') {
    document.documentElement.classList.add('dark');
}

window.Alpine = Alpine;

// Alpine store for dark mode toggle
Alpine.store('darkMode', {
    on: localStorage.getItem('darkMode') === 'true',
    toggle() {
        this.on = !this.on;
        localStorage.setItem('darkMode', this.on);
        document.documentElement.classList.toggle('dark', this.on);
    },
});

Alpine.start();
