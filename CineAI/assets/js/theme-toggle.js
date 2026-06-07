document.addEventListener('DOMContentLoaded', () => {
    const themeToggle = document.getElementById('theme-toggle');
    const root = document.documentElement;
    const currentTheme = localStorage.getItem('theme');

    // Apply saved theme on load
    if (currentTheme === 'light') {
        root.classList.add('light-theme');
        if (themeToggle) themeToggle.textContent = '🌙';
    } else {
        if (themeToggle) themeToggle.textContent = '☀️';
    }

    if (themeToggle) {
        themeToggle.addEventListener('click', () => {
            root.classList.toggle('light-theme');
            let theme = 'dark';
            if (root.classList.contains('light-theme')) {
                theme = 'light';
                themeToggle.textContent = '🌙';
            } else {
                themeToggle.textContent = '☀️';
            }
            localStorage.setItem('theme', theme);
        });
    }
});
