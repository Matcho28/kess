(() => {
    const sidebar = document.getElementById('appSidebar');
    const toggleButton = document.getElementById('appSidebarToggle');
    const STORAGE_KEY = 'kess_app_sidebar_collapsed';
    const MOBILE_BREAKPOINT = 991.98;

    if (!sidebar || !toggleButton) {
        return;
    }

    function isMobileViewport() {
        return window.innerWidth <= MOBILE_BREAKPOINT;
    }

    function readStoredCollapsed() {
        try {
            return window.localStorage.getItem(STORAGE_KEY) === '1';
        } catch (error) {
            console.error(error);
            return false;
        }
    }

    function writeStoredCollapsed(collapsed) {
        try {
            window.localStorage.setItem(STORAGE_KEY, collapsed ? '1' : '0');
        } catch (error) {
            console.error(error);
        }
    }

    function applySidebarState(collapsed, mobileExpanded) {
        const mobile = isMobileViewport();
        const isDesktopCollapsed = !mobile && collapsed;
        const isMobileExpanded = mobile && mobileExpanded;
        const isExpanded = mobile ? isMobileExpanded : !isDesktopCollapsed;

        sidebar.classList.toggle('is-collapsed', isDesktopCollapsed);
        sidebar.classList.toggle('is-expanded-mobile', isMobileExpanded);
        sidebar.setAttribute('aria-expanded', isExpanded ? 'true' : 'false');
        toggleButton.setAttribute('aria-expanded', isExpanded ? 'true' : 'false');
    }

    const state = {
        collapsed: readStoredCollapsed(),
        mobileExpanded: false,
    };

    function syncForViewport() {
        if (isMobileViewport()) {
            state.mobileExpanded = false;
        }

        applySidebarState(state.collapsed, state.mobileExpanded);
    }

    toggleButton.addEventListener('click', () => {
        if (isMobileViewport()) {
            state.mobileExpanded = !state.mobileExpanded;
            applySidebarState(state.collapsed, state.mobileExpanded);
            return;
        }

        state.collapsed = !state.collapsed;
        writeStoredCollapsed(state.collapsed);
        applySidebarState(state.collapsed, state.mobileExpanded);
    });

    window.addEventListener('resize', syncForViewport);

    syncForViewport();
})();
