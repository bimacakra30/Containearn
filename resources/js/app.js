import './bootstrap';

import Alpine from 'alpinejs';
import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';

const PAGE_LOADING_CLASS = 'page-loading';

const startPageLoading = () => {
    document.body.classList.add(PAGE_LOADING_CLASS);
};

const stopPageLoading = () => {
    document.body.classList.remove(PAGE_LOADING_CLASS);
};

const showSwalAlert = () => {
    const alertElement = document.querySelector('[data-swal-alert]');

    if (!alertElement) {
        return;
    }

    const config = JSON.parse(alertElement.textContent);

    Swal.fire({
        position: 'center',
        showConfirmButton: false,
        timer: 1500,
        ...config,
    });
};

const shouldHandleNavigationLink = (link, event) => {
    if (
        event.defaultPrevented ||
        event.button !== 0 ||
        event.metaKey ||
        event.ctrlKey ||
        event.shiftKey ||
        event.altKey
    ) {
        return false;
    }

    if (link.hasAttribute('download')) {
        return false;
    }

    const target = link.getAttribute('target');
    if (target && target !== '_self') {
        return false;
    }

    const href = link.getAttribute('href');
    if (!href || href.startsWith('#') || href.startsWith('javascript:')) {
        return false;
    }

    let url;

    try {
        url = new URL(link.href, window.location.href);
    } catch {
        return false;
    }

    if (url.origin !== window.location.origin) {
        return false;
    }

    if (
        url.pathname === window.location.pathname &&
        url.search === window.location.search &&
        url.hash
    ) {
        return false;
    }

    return true;
};

Alpine.data('shellLayout', () => ({
    desktopSidebarHidden: true,
    desktopSidebarPeek: false,
    mobileSidebarOpen: false,
    isDesktop() {
        return window.innerWidth >= 1280;
    },
    init() {
        stopPageLoading();

        window.addEventListener('resize', () => {
            if (this.isDesktop()) {
                this.mobileSidebarOpen = false;
                return;
            }

            this.desktopSidebarPeek = false;
        });

        window.addEventListener('pageshow', () => {
            stopPageLoading();
        });

        document.addEventListener('click', (event) => {
            const link = event.target.closest('a[href]');

            if (!link || !shouldHandleNavigationLink(link, event)) {
                return;
            }

            startPageLoading();
        });

        document.addEventListener('submit', (event) => {
            const form = event.target;

            if (!(form instanceof HTMLFormElement) || event.defaultPrevented) {
                return;
            }

            if (form.hasAttribute('data-no-loading')) {
                return;
            }

            startPageLoading();
        });
    },
    toggleSidebar() {
        if (this.isDesktop()) {
            this.desktopSidebarHidden = !this.desktopSidebarHidden;
            this.desktopSidebarPeek = false;
            return;
        }

        this.mobileSidebarOpen = !this.mobileSidebarOpen;
    },
    closeSidebar() {
        if (this.isDesktop()) {
            this.desktopSidebarHidden = true;
            this.desktopSidebarPeek = false;
            return;
        }

        this.closeMobileSidebar();
    },
    closeMobileSidebar() {
        this.mobileSidebarOpen = false;
    },
    openSidebarPeek() {
        if (this.isDesktop() && this.desktopSidebarHidden) {
            this.desktopSidebarPeek = true;
        }
    },
    closeSidebarPeek() {
        if (this.isDesktop()) {
            this.desktopSidebarPeek = false;
        }
    },
}));

window.loginForm = () => ({
    submitting: false,
});

window.Alpine = Alpine;

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', showSwalAlert);
} else {
    showSwalAlert();
}

Alpine.start();
