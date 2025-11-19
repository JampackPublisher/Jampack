document.addEventListener('DOMContentLoaded', () => {
    let currentPage = 1;
    let order = 'DESC';
    let orderby = 'id';
    const perPage = 10;
    const paginationSiblings = 3;

    const table = document.getElementById('analytics-table');
    const pagination = document.getElementById('analytics-pagination');
    const loadingScreen = document.getElementById('loading-screen');
    const mainContent = document.getElementById('main-content');

    function loadData() {
        const url = new URL(AnalyticsData.restUrl);
        url.searchParams.append('page', currentPage);
        url.searchParams.append('per_page', perPage);
        url.searchParams.append('orderby', orderby);
        url.searchParams.append('order', order);
        loadingScreen.style.display = 'block';
    
        fetch(url.toString(), {
            method: 'GET',
            credentials: 'include',
            headers: {
                'X-WP-Nonce': AnalyticsData.nonce
            }
        })
        .then(res => res.json())
        .then(res => {
            loadingScreen.style.display = 'none';
            mainContent.style.display = 'block';
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
                <td><a href="${row.post_url}" target="_blank">${row.post_title}</a></td>
                <td>${parseFloat(row.average)}</td>
                <td>${parseInt(row.votes)}</td>
            `;
            tbody.appendChild(tr);
        });
    }

    function createButton(pagination, i, page) {
        const btn = document.createElement('button');
        btn.textContent = i;
        btn.disabled = (i === page);
        btn.className = i === page ? 'pagination-button active-page' : 'pagination-button';
        btn.addEventListener('click', () => {
            mainContent.style.display = 'none';
            currentPage = i;
            loadData();
        });
        pagination.appendChild(btn);
    }

    function renderPagination(total, perPage, page) {
        pagination.innerHTML = '';

        const totalPages = Math.ceil(total / perPage);
        const paginationContractionCondition = (paginationSiblings * 2) + 1;

        if(totalPages > 1) {
            const prevBtn = document.createElement('button');
            prevBtn.textContent = '« Prev';
            prevBtn.className = 'pagination-button';
            prevBtn.disabled = page === 1;
            prevBtn.addEventListener('click', () => {
                if (page > 1) {
                    mainContent.style.display = 'none';
                    currentPage = page - 1;
                    loadData();
                }
            });
            pagination.appendChild(prevBtn);


            createButton(pagination, 1, page);
            if (totalPages >= paginationContractionCondition && currentPage > paginationSiblings + 1) {
                const span = document.createElement('span');
                span.textContent = '...';
                pagination.appendChild(span);
            }

            let downStart = currentPage - paginationSiblings;
            let upEnd = currentPage + paginationSiblings;

            for (let i = 2; i <= totalPages - 1; i++) {
                if(i < downStart && i != 1 || i > upEnd && i != totalPages) {
                    continue;
                }
                createButton(pagination, i, page);
            }

            if (totalPages >= paginationContractionCondition && currentPage < totalPages - paginationSiblings) {
                const span = document.createElement('span');
                span.textContent = '...';
                pagination.appendChild(span);
            }
            createButton(pagination, totalPages, page);

            const nextBtn = document.createElement('button');
            nextBtn.textContent = 'Next »';
            nextBtn.className = 'pagination-button';
            nextBtn.disabled = page === totalPages;
            nextBtn.addEventListener('click', () => {
                if (page < totalPages) {
                    mainContent.style.display = 'none';
                    currentPage = page + 1;
                    loadData();
                }
            });
            pagination.appendChild(nextBtn);
         }
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
            mainContent.style.display = 'none';
            loadData();
        });
    });

    loadData();
});
