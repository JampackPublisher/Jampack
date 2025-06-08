document.addEventListener('DOMContentLoaded', () => {
    let currentPage = 1;
    let order = 'DESC';
    let orderby = 'id';
    const perPage = 10;

    const table = document.getElementById('analytics-table');
    const pagination = document.getElementById('analytics-pagination');

    function loadData() {
        const url = new URL(AnalyticsData.restUrl);
        url.searchParams.append('page', currentPage);
        url.searchParams.append('per_page', perPage);
        url.searchParams.append('orderby', orderby);
        url.searchParams.append('order', order);

        fetch(url.toString(), {
            method: 'GET',
            credentials: 'include',
            headers: {
                'X-WP-Nonce': AnalyticsData.nonce
            }
        })
        .then(res => res.json())
        .then(res => {
            renderTable(res.data);
            renderPagination(res.total, res.per_page, res.page);
        });
    }

    function renderTable(data) {
        const tbody = table.querySelector('tbody');
        tbody.innerHTML = '';

        data.forEach(row => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${row.time}</td>
                <td><a href="${row.post_url}" target="_blank">${row.post_title}</a></td>
                <td>${row.duration_display}</td>
                <td>${parseFloat(row.average)}</td>
                <td>${parseInt(row.votes)}</td>
                <td>${parseInt(row.value)}</td>
            `;
            tbody.appendChild(tr);
        });
    }

    function renderPagination(total, perPage, page) {
        pagination.innerHTML = '';

        const totalPages = Math.ceil(total / perPage);

        const prevBtn = document.createElement('button');
        prevBtn.textContent = '« Prev';
        prevBtn.disabled = page === 1;
        prevBtn.addEventListener('click', () => {
            if (page > 1) {
                currentPage = page - 1;
                loadData();
            }
        });
        pagination.appendChild(prevBtn);

        for (let i = 1; i <= totalPages; i++) {
            const btn = document.createElement('button');
            btn.textContent = i;
            btn.disabled = (i === page);
            btn.className = i === page ? 'active-page' : '';
            btn.addEventListener('click', () => {
                currentPage = i;
                loadData();
            });
            pagination.appendChild(btn);
        }

        const nextBtn = document.createElement('button');
        nextBtn.textContent = 'Next »';
        nextBtn.disabled = page === totalPages;
        nextBtn.addEventListener('click', () => {
            if (page < totalPages) {
                currentPage = page + 1;
                loadData();
            }
        });
        pagination.appendChild(nextBtn);
    }

    document.querySelectorAll('#analytics-table thead th.sortable').forEach(th => {
        th.addEventListener('click', () => {
            const col = th.dataset.column;
            if (orderby === col) {
                order = (order === 'ASC') ? 'DESC' : 'ASC';
            } else {
                orderby = col;
                order = 'ASC';
            }
            loadData();
        });
    });

    loadData();
});
