        </div> <!-- End .app-content -->

        <!-- Dashboard Footer -->
        <footer class="app-footer no-print">
            <div>
                <strong>SPKK Maternal CDSS</strong> &copy; <?= date('Y') ?> — Sistem Pendukung Keputusan Klinis Hipertensi Kehamilan (Forward Chaining).
            </div>
            <div>
                Standar Pedoman: <strong>POGI (Perkumpulan Obstetri dan Ginekologi Indonesia) & WHO</strong>
            </div>
        </footer>
    </div> <!-- End .app-main -->
</div> <!-- End .app-layout -->

<!-- Custom App JS -->
<script src="assets/js/app.js"></script>

<script>
// Sidebar Toggle Helper for Mobile
function toggleSidebar() {
    const sidebar = document.getElementById('appSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    if (sidebar && overlay) {
        sidebar.classList.toggle('open');
        overlay.classList.toggle('active');
    }
}

// Topbar User Dropdown Toggle
function toggleUserMenu() {
    const dropdown = document.getElementById('topbarDropdown');
    if (dropdown) dropdown.classList.toggle('open');
}

// Close dropdown when clicking outside
document.addEventListener('click', function(e) {
    const menu = document.getElementById('topbarUserMenu');
    const dropdown = document.getElementById('topbarDropdown');
    if (menu && dropdown && !menu.contains(e.target)) {
        dropdown.classList.remove('open');
    }
});
</script>
</body>
</html>
