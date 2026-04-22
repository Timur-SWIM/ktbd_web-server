document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.data-table').forEach((table) => {
        if (window.DataTable) {
            new DataTable(table, {
                pageLength: 10,
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/2.0.8/i18n/ru.json'
                }
            });
        }
    });
});
