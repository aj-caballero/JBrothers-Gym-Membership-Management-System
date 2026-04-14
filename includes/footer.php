<?php
// C:/Users/Kyle/GYM MEMBERSHIP/includes/footer.php
?>
            </div> <!-- End content-wrapper -->
        </main>
    </div> <!-- End app-container -->

    <script>
    // Sidebar toggle — desktop collapses, mobile slides in
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const sidebar       = document.getElementById('sidebar');
    const appContainer  = document.querySelector('.app-container');

    if (sidebarToggle && sidebar && appContainer) {
        sidebarToggle.addEventListener('click', () => {
            if (window.innerWidth > 1024) {
                // Desktop: collapse/expand by toggling class on the container
                appContainer.classList.toggle('sidebar-collapsed');
            } else {
                // Mobile: slide in/out by toggling class on the sidebar itself
                sidebar.classList.toggle('open');
            }
        });

        // Close sidebar on outside click (mobile only)
        document.addEventListener('click', (e) => {
            if (window.innerWidth <= 1024 && sidebar.classList.contains('open')) {
                if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
                    sidebar.classList.remove('open');
                }
            }
        });

        // When resizing from mobile → desktop, clean up mobile open state
        window.addEventListener('resize', () => {
            if (window.innerWidth > 1024) {
                sidebar.classList.remove('open');
            }
        });
    }

    // Auto-dismiss alerts after 5 seconds
    document.querySelectorAll('.alert').forEach(el => {
        setTimeout(() => {
            el.style.transition = 'opacity 0.5s';
            el.style.opacity = '0';
            setTimeout(() => el.remove(), 500);
        }, 5000);
    });

    // Modal helpers: close on overlay click
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) overlay.classList.remove('open');
        });
    });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.searchable-select').forEach(function(el) {
            new TomSelect(el, {
                create: false,
                sortField: {
                    field: "text",
                    direction: "asc"
                }
            });
        });
    });
    </script>
</body>
</html>
