document.addEventListener('DOMContentLoaded', function () {
    if (window.innerWidth < 992) {
        document.querySelectorAll('table:not(.table-responsive)').forEach(function (tbl) {
            if (!tbl.closest('.table-responsive')) {
                const wrap = document.createElement('div');
                wrap.className = 'table-responsive';
                tbl.parentNode.insertBefore(wrap, tbl);
                wrap.appendChild(tbl);
            }
        });
    }
});
