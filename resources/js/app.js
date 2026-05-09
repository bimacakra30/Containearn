import './bootstrap';

import Alpine from 'alpinejs';

const PAGE_LOADING_CLASS = 'page-loading';

const startPageLoading = () => {
    document.body.classList.add(PAGE_LOADING_CLASS);
};

const stopPageLoading = () => {
    document.body.classList.remove(PAGE_LOADING_CLASS);
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
    mobileSidebarOpen: false,
    init() {
        stopPageLoading();

        window.addEventListener('resize', () => {
            if (window.innerWidth >= 1280) {
                this.mobileSidebarOpen = false;
            }
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
        this.mobileSidebarOpen = !this.mobileSidebarOpen;
    },
    closeMobileSidebar() {
        this.mobileSidebarOpen = false;
    },
}));

window.Alpine = Alpine;

Alpine.start();
