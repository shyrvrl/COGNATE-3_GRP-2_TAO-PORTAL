// js/db-notices.js

document.addEventListener('DOMContentLoaded', () => {
    // --- Elements ---
    const listContainer = document.getElementById('archive-list-container');
    const searchForm = document.getElementById('search-form');
    const searchInput = document.getElementById('search-input');
    const filterSelect = document.getElementById('filter-select');
    
    const paginationNav = document.getElementById('pagination-controls');
    const prevBtn = document.getElementById('prev-btn');
    const nextBtn = document.getElementById('next-btn');
    const pageInfo = document.getElementById('page-info-text');

    // --- State ---
    let currentState = {
        page: 1,
        search: '',
        filter: 'all'
    };

    // --- Helper: Map Color to Label ---
    const getTypeLabel = (type) => {
        switch(type) {
            case 'blue': return 'Policy / General';
            case 'green': return 'Maintenance / Success';
            case 'red': return 'Urgent Alert';
            default: return 'Notice';
        }
    };

    // --- Main Fetch Function ---
    const fetchNotices = () => {
        // Show simple loading state
        listContainer.style.opacity = '0.6';

        const params = new URLSearchParams({
            page: currentState.page,
            search: currentState.search,
            filter: currentState.filter
        });

        fetch(`api/get_notices_archive.php?${params.toString()}`)
            .then(res => res.json())
            .then(data => {
                if (data.error) throw new Error(data.message);
                renderList(data.notices);
                updatePagination(data.pagination);
            })
            .catch(err => {
                listContainer.innerHTML = `<p style="color:red; padding:20px;">Error: ${err.message}</p>`;
            })
            .finally(() => {
                listContainer.style.opacity = '1';
            });
    };

    // --- Render List HTML ---
    const renderList = (notices) => {
        listContainer.innerHTML = '';
        
        if (notices.length === 0) {
            listContainer.innerHTML = `<div class="no-results"><p>No notices found matching your criteria.</p></div>`;
            return;
        }

        notices.forEach(notice => {
            const item = document.createElement('div');
            // notice_type corresponds to CSS classes .notice-blue, .notice-red, etc.
            item.className = `notice-item notice-${notice.notice_type}`;
            
            // Format date
            const dateObj = new Date(notice.created_at);
            const dateStr = dateObj.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });

            item.innerHTML = `
                <div class="notice-content">
                    <h3>${notice.notice_title}</h3>
                    <p>${notice.notice_content}</p>
                </div>
                <div class="notice-meta">
                    <span class="notice-type">${getTypeLabel(notice.notice_type)}</span>
                    <span class="notice-date">${dateStr}</span>
                </div>
            `;
            listContainer.appendChild(item);
        });
    };

    // --- Update Pagination Controls ---
    const updatePagination = (pageData) => {
        const { current_page, total_pages } = pageData;
        
        // Update Text
        pageInfo.textContent = `Page ${current_page} of ${total_pages || 1}`; // fallback to 1 if 0 pages
        
        // Show/Hide Controls
        paginationNav.style.display = total_pages > 0 ? 'flex' : 'none';

        // Disable Buttons logic
        prevBtn.disabled = current_page <= 1;
        nextBtn.disabled = current_page >= total_pages;

        // Visual styling for disabled buttons
        prevBtn.classList.toggle('disabled', current_page <= 1);
        nextBtn.classList.toggle('disabled', current_page >= total_pages);
    };

    // --- Event Listeners ---

    // 1. Search Form Submit
    searchForm.addEventListener('submit', (e) => {
        e.preventDefault();
        currentState.search = searchInput.value;
        currentState.page = 1; // Reset to page 1 on new search
        fetchNotices();
    });

    // 2. Filter Dropdown Change
    filterSelect.addEventListener('change', (e) => {
        currentState.filter = e.target.value;
        currentState.page = 1; // Reset to page 1 on filter change
        fetchNotices();
    });

    // 3. Pagination Buttons
    prevBtn.addEventListener('click', (e) => {
        e.preventDefault(); // Prevent link default
        if (currentState.page > 1) {
            currentState.page--;
            fetchNotices();
        }
    });

    nextBtn.addEventListener('click', (e) => {
        e.preventDefault(); // Prevent link default
        currentState.page++;
        fetchNotices();
    });

    // Initial Load
    fetchNotices();
});