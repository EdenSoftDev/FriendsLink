document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.fl-delete-link').forEach(function (el) {
        el.addEventListener('click', function (e) {
            if (!confirm('确定要删除这条友链吗？此操作不可撤销。')) {
                e.preventDefault();
            }
        });
    });
});
