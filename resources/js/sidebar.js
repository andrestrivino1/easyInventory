document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.querySelector('.main-sidebar');
    const toggleBtn = document.getElementById('sidebarToggleBtn');
    if (toggleBtn) {
        toggleBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            sidebar.classList.toggle('active');
        });
    }
    document.addEventListener('click', function (e) {
        if (window.innerWidth < 992 && sidebar && sidebar.classList.contains('active')) {
            if (!sidebar.contains(e.target) && e.target !== toggleBtn) {
                sidebar.classList.remove('active');
            }
        }
    });
});
