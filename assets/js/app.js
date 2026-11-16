/**
 * Sistem Keuangan Kas dengan AI - Client-side UI & Interaction Scripts
 * Responsive Sidebar Drawer, Flash Auto-dismiss, Tooltips & Popovers
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Mobile & Tablet Sidebar Drawer Interactions
    const sidebarToggleBtn = document.getElementById('sidebarToggle');
    const appSidebar = document.getElementById('appSidebar');
    const sidebarBackdrop = document.getElementById('sidebarBackdrop');

    function openSidebar() {
        if (appSidebar) {
            appSidebar.classList.add('show');
            if (sidebarToggleBtn) {
                sidebarToggleBtn.setAttribute('aria-expanded', 'true');
            }
        }
        if (sidebarBackdrop) {
            sidebarBackdrop.classList.add('show');
        }
        document.body.style.overflow = window.innerWidth < 992 ? 'hidden' : '';
    }

    function closeSidebar() {
        if (appSidebar) {
            appSidebar.classList.remove('show');
            if (sidebarToggleBtn) {
                sidebarToggleBtn.setAttribute('aria-expanded', 'false');
            }
        }
        if (sidebarBackdrop) {
            sidebarBackdrop.classList.remove('show');
        }
        document.body.style.overflow = '';
    }

    if (sidebarToggleBtn && appSidebar) {
        sidebarToggleBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            if (appSidebar.classList.contains('show')) {
                closeSidebar();
            } else {
                openSidebar();
            }
        });
    }

    if (sidebarBackdrop) {
        sidebarBackdrop.addEventListener('click', () => {
            closeSidebar();
        });
    }

    // Close on ESC key press
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && appSidebar && appSidebar.classList.contains('show')) {
            closeSidebar();
        }
    });

    // Close sidebar when clicking any navigation link on mobile/tablet screens
    if (appSidebar) {
        const navLinks = appSidebar.querySelectorAll('.sidebar-nav-link');
        navLinks.forEach((link) => {
            link.addEventListener('click', () => {
                if (window.innerWidth < 992) {
                    closeSidebar();
                }
            });
        });
    }

    // Reset mobile state on window resize above breakpoint
    window.addEventListener('resize', () => {
        if (window.innerWidth >= 992) {
            closeSidebar();
        }
    });

    // 2. Auto-dismiss Flash Alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach((alert) => {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            if (bsAlert) {
                bsAlert.close();
            }
        }, 5000);
    });

    // 3. Tooltips & Popovers initialization
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // 4. Form Submit Loading Feedback
    document.querySelectorAll('form').forEach((form) => {
        form.addEventListener('submit', function () {
            if (this.checkValidity && !this.checkValidity()) return;
            const submitBtn = this.querySelector('button[type="submit"]:not(.no-loading)');
            if (submitBtn && !submitBtn.disabled) {
                submitBtn.classList.add('btn-loading');
            }
        });
    });
});
