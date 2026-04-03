
// Theme toggle logic
function initTheme() {
    const saved = localStorage.getItem('theme');
    if (saved === 'light') {
        document.documentElement.classList.remove('dark');
    } else {
        document.documentElement.classList.add('dark');
    }
}

function toggleTheme() {
    const html = document.documentElement;
    html.classList.add('theme-transitioning');
    if (html.classList.contains('dark')) {
        html.classList.remove('dark');
        localStorage.setItem('theme', 'light');
    } else {
        html.classList.add('dark');
        localStorage.setItem('theme', 'dark');
    }
    setTimeout(() => html.classList.remove('theme-transitioning'), 500);
}

function bindThemeButtons() {
    ['theme-toggle-sidebar', 'theme-toggle-sidebar-dark', 'theme-toggle-mobile'].forEach(id => {
        const el = document.getElementById(id);
        if (el && !el.dataset.themeBound) {
            el.addEventListener('click', toggleTheme);
            el.dataset.themeBound = '1';
        }
    });
}

// Init on page load
initTheme();
document.addEventListener('DOMContentLoaded', bindThemeButtons);
// Re-bind after Livewire navigation
document.addEventListener('livewire:navigated', bindThemeButtons);
