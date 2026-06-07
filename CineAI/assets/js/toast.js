/**
 * Toast Notification Utility
 */

const showToast = (message, type = 'info') => {
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    
    let icon = '🔔';
    if (type === 'success') icon = '✅';
    if (type === 'error') icon = '❌';

    toast.innerHTML = `
        <span style="font-size: 1.2rem;">${icon}</span>
        <div style="flex-grow: 1;">${message}</div>
    `;

    container.appendChild(toast);

    setTimeout(() => {
        toast.style.animation = 'toast-in 0.3s reverse forwards';
        setTimeout(() => toast.remove(), 300);
    }, 4000);
};

// Global listener for session messages if any
document.addEventListener('DOMContentLoaded', () => {
    // Check for PHP session flashes via global variable if needed
    // Example: if (window.flashMessage) showToast(window.flashMessage.text, window.flashMessage.type);
});
