/**
 * Main Application JavaScript
 */

document.addEventListener('DOMContentLoaded', () => {
    // --- 1. Dark Mode Toggle ---
    const themeToggleBtn = document.getElementById('themeToggle');
    const darkIcon = themeToggleBtn?.querySelector('.dark-icon');
    const lightIcon = themeToggleBtn?.querySelector('.light-icon');

    function updateIcon(theme) {
        if (!darkIcon || !lightIcon) return;
        if (theme === 'dark') {
            darkIcon.classList.remove('d-none');
            lightIcon.classList.add('d-none');
        } else {
            lightIcon.classList.remove('d-none');
            darkIcon.classList.add('d-none');
        }
    }

    const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
    updateIcon(currentTheme);

    themeToggleBtn?.addEventListener('click', () => {
        let theme = document.documentElement.getAttribute('data-theme');
        if (theme === 'dark') {
            document.documentElement.removeAttribute('data-theme');
            localStorage.setItem('theme', 'light');
            updateIcon('light');
        } else {
            document.documentElement.setAttribute('data-theme', 'dark');
            localStorage.setItem('theme', 'dark');
            updateIcon('dark');
        }
    });

    // --- 2. SweetAlert2 for Delete Confirmations ---
    const deleteButtons = document.querySelectorAll('.btn-danger[onclick*="confirm"]');
    deleteButtons.forEach(button => {
        const href = button.getAttribute('href');
        button.removeAttribute('onclick'); // Remove native confirm
        
        button.addEventListener('click', (e) => {
            e.preventDefault();
            Swal.fire({
                title: 'Are you sure?',
                text: "This action cannot be undone!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Yes, delete it!',
                background: document.documentElement.getAttribute('data-theme') === 'dark' ? '#1e293b' : '#ffffff',
                color: document.documentElement.getAttribute('data-theme') === 'dark' ? '#f8fafc' : '#0f172a'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = href;
                }
            });
        });
    });

    // --- 3. Chart.js Initialization ---
    if (window.appStats) {
        const primaryColor = '#4f46e5';
        const successColor = '#10b981';
        const warningColor = '#f59e0b';
        
        const getChartTextColor = () => document.documentElement.getAttribute('data-theme') === 'dark' ? '#94a3b8' : '#64748b';
        const getChartGridColor = () => document.documentElement.getAttribute('data-theme') === 'dark' ? '#334155' : '#e2e8f0';

        let overviewChartInstance = null;
        let distChartInstance = null;

        const renderCharts = () => {
            const textColor = getChartTextColor();
            const gridColor = getChartGridColor();

            // Overview Bar Chart
            const overviewCtx = document.getElementById('overviewChart');
            if (overviewCtx) {
                if (overviewChartInstance) overviewChartInstance.destroy();
                overviewChartInstance = new Chart(overviewCtx, {
                    type: 'bar',
                    data: {
                        labels: ['Students', 'Teachers', 'Classrooms'],
                        datasets: [{
                            label: 'Total Count',
                            data: [window.appStats.students, window.appStats.teachers, window.appStats.classrooms],
                            backgroundColor: [primaryColor, successColor, warningColor],
                            borderRadius: 8,
                            barThickness: 40
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { 
                            legend: { display: false } 
                        },
                        scales: {
                            y: { 
                                beginAtZero: true, 
                                grid: { color: gridColor },
                                ticks: { color: textColor }
                            },
                            x: { 
                                grid: { display: false },
                                ticks: { color: textColor }
                            }
                        }
                    }
                });
            }

            // Distribution Doughnut Chart
            const distCtx = document.getElementById('distributionChart');
            if (distCtx) {
                if (distChartInstance) distChartInstance.destroy();
                distChartInstance = new Chart(distCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Students', 'Teachers'],
                        datasets: [{
                            data: [window.appStats.students, window.appStats.teachers],
                            backgroundColor: [primaryColor, successColor],
                            borderWidth: 0,
                            hoverOffset: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '75%',
                        plugins: {
                            legend: { 
                                position: 'bottom',
                                labels: { color: textColor, padding: 20 }
                            }
                        }
                    }
                });
            }
        };

        renderCharts();

        // Re-render charts on theme toggle
        themeToggleBtn?.addEventListener('click', () => {
            setTimeout(renderCharts, 50);
        });
    }

    // --- 4. Sidebar Toggle System ---
    const sidebar          = document.getElementById('appSidebar');
    const sidebarToggleBtn = document.getElementById('sidebarToggle');
    const overlay          = document.getElementById('sidebarOverlay');
    const html             = document.documentElement;

    const isMobile = () => window.innerWidth < 992;

    function setSidebarToggleAria(isOpen) {
        sidebarToggleBtn?.setAttribute('aria-expanded', String(isOpen));
    }

    function openMobileSidebar() {
        sidebar?.classList.add('mobile-open');
        overlay?.classList.add('active');
        document.body.style.overflow = 'hidden';
        setSidebarToggleAria(true);
    }

    function closeMobileSidebar() {
        sidebar?.classList.remove('mobile-open');
        overlay?.classList.remove('active');
        document.body.style.overflow = '';
        setSidebarToggleAria(false);
    }

    function toggleDesktopSidebar() {
        const isCollapsed = html.getAttribute('data-sidebar') === 'collapsed';
        if (isCollapsed) {
            html.removeAttribute('data-sidebar');
            localStorage.setItem('sidebarCollapsed', 'false');
            setSidebarToggleAria(true);
        } else {
            html.setAttribute('data-sidebar', 'collapsed');
            localStorage.setItem('sidebarCollapsed', 'true');
            setSidebarToggleAria(false);
        }
    }

    // Set initial aria state
    setSidebarToggleAria(html.getAttribute('data-sidebar') !== 'collapsed');

    sidebarToggleBtn?.addEventListener('click', () => {
        if (isMobile()) {
            const isOpen = sidebar?.classList.contains('mobile-open');
            isOpen ? closeMobileSidebar() : openMobileSidebar();
        } else {
            toggleDesktopSidebar();
        }
    });

    overlay?.addEventListener('click', closeMobileSidebar);

    // Ensure clean state on resize
    window.addEventListener('resize', () => {
        if (!isMobile()) {
            // On desktop: always clear mobile state
            sidebar?.classList.remove('mobile-open');
            overlay?.classList.remove('active');
            document.body.style.overflow = '';
        }
    });

    // --- 5. Topbar Breadcrumb Page Title ---
    const topbarPageTitle = document.getElementById('topbarPageTitle');
    if (topbarPageTitle) {
        // Try to get the text from the active sidebar link
        const activeLink = document.querySelector('.sidebar-link.active .sidebar-link-text');
        if (activeLink) {
            topbarPageTitle.textContent = activeLink.textContent.trim();
        }
        // Fallback: leave the "Dashboard" default from the HTML
    }
});

