import './orders.js';

const sidebar = document.querySelector('#app-sidebar');
const sidebarOverlay = document.querySelector('#sidebar-overlay');
const sidebarOpenButton = document.querySelector('[data-sidebar-open]');
const sidebarCloseButton = document.querySelector('[data-sidebar-close]');

function setSidebarOpen(isOpen) {
    if (!sidebar || !sidebarOverlay || !sidebarOpenButton) {
        return;
    }

    sidebar.classList.toggle('-translate-x-full', !isOpen);
    sidebarOverlay.classList.toggle('hidden', !isOpen);
    sidebarOpenButton.setAttribute('aria-expanded', String(isOpen));
    document.body.classList.toggle('overflow-hidden', isOpen);
}

sidebarOpenButton?.addEventListener('click', () => setSidebarOpen(true));
sidebarCloseButton?.addEventListener('click', () => setSidebarOpen(false));
sidebarOverlay?.addEventListener('click', () => setSidebarOpen(false));

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        setSidebarOpen(false);
    }
});

window.addEventListener('resize', () => {
    if (window.innerWidth >= 1024) {
        setSidebarOpen(false);
    }
});
