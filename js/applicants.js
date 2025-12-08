document.addEventListener('DOMContentLoaded', () => {

    // --- Element Selectors ---
    const filterForm = document.getElementById('filter-form');
    const hideFiltersBtn = document.getElementById('hide-filters-btn');
    const statusSelect = document.getElementById('filter-status');
    const programSelect = document.getElementById('filter-program');
    const assignmentSelect = document.getElementById('filter-assignment');
    const mainTableBody = document.getElementById('applicant-list-body');
    const searchInput = document.getElementById('search-input');
    const exportBtn = document.getElementById('export-btn');
    
    // Modal Elements
    const filterModal = document.getElementById('filter-modal');
    const closeModalBtn = document.getElementById('close-modal-btn');
    const filterSummaryList = document.getElementById('filter-summary-list');
    const assignModal = document.getElementById('assign-confirm-modal');
    const cancelAssignBtn = document.getElementById('cancel-assign-btn');
    const confirmAssignBtn = document.getElementById('confirm-assign-btn');
    const assignModalText = document.getElementById('assign-modal-text');
    const assignModalLoader = document.getElementById('assign-modal-loader');
    const assignModalButtons = document.getElementById('assign-modal-buttons');

     // State variables
    let appIdToAssign = null;
    let filtersPopulated = false;
    let currentUserId = null;
    let currentUserRole = null; 

    // Tab Elements for State Management
    const tabs = document.querySelectorAll('.tab-link');
    const tabContents = document.querySelectorAll('.tab-content');
    const currentHash = window.location.hash.substring(1);

    function switchTab(targetTabId) {
        history.pushState(null, null, '#' + targetTabId);
        
        tabContents.forEach(content => {
            content.classList.toggle('active', content.id === targetTabId);
        });
        tabs.forEach(tab => {
            tab.classList.toggle('active', tab.dataset.tab === targetTabId);
        });
    }

    if (currentHash === 'list-content') {
        switchTab('list-content');
    } else {
        switchTab('summary-content');
    }

    tabs.forEach(tab => {
        tab.addEventListener('click', (e) => {
            e.preventDefault();
            switchTab(tab.dataset.tab);
        });
    });


    // --- Main Data Loading ---
    function loadApplications(filterParams = '') {
        mainTableBody.innerHTML = '<tr><td colspan="7">Loading...</td></tr>'; // Updated colspan to 7
        fetch(`api/get_applications.php?${filterParams}`)
            .then(response => response.json())
            .then(data => {
                currentUserId = data.current_user_id;
                currentUserRole = data.current_user_role; 
                if (!filtersPopulated) {
                    populateFilters(data.filter_options);
                    filtersPopulated = true;
                }
                renderMainTable(data.applications);
            })
            .catch(error => console.error('Error:', error));
    }

    function populateFilters(options) {
        statusSelect.innerHTML = '<option value="">All Statuses</option>';
        programSelect.innerHTML = '<option value="">All Programs</option>';
        
        options.statuses.forEach(status => {
            statusSelect.innerHTML += `<option value="${status}">${status}</option>`;
        });
        options.programs.forEach(program => {
            programSelect.innerHTML += `<option value="${program}">${program}</option>`;
        });
    }

    function getStatusClass(status) {
        const sanitizedStatus = status.toLowerCase().replace(/\s+/g, '-');
        const map = {
            'approved': 'status-approved',
            'for-approving': 'status-for-approving',
            'for-evaluation': 'status-for-evaluation',
            'for-interview': 'status-for-interview',
            'rejected': 'status-rejected'
        };
        return map[sanitizedStatus] || 'status-default';
    }
    
    // --- Helper: Format Date/Time Correctly ---
    function formatTimestamp(dbTimestamp) {
        if (!dbTimestamp) return 'N/A';
        // Create a date object. The browser will assume the string is local or parse it.
        // If DB stores UTC, append 'Z' to string. If DB stores server local time, use as is.
        // Assuming DB stores standard MySQL 'YYYY-MM-DD HH:MM:SS' in local server time.
        const date = new Date(dbTimestamp);
        
        // This converts it to the user's browser locale and timezone
        return date.toLocaleString('en-US', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            hour12: true
        });
    }

    function renderMainTable(data) {
        mainTableBody.innerHTML = '';

        if (data.length === 0) {
            mainTableBody.innerHTML = `<tr><td colspan="7">No applications match the current filters.</td></tr>`;
            return;
        }

        data.forEach(app => {
            const row = document.createElement('tr');
            row.dataset.appId = app.id;

            const isAssigned = !!app.evaluator_id;
            const isAssignedToMe = (parseInt(app.evaluator_id) === currentUserId);
            const isViewable = isAssignedToMe;

            if (isViewable) {
                row.className = 'clickable-row';
            } else {
                row.className = ''; 
            }
                
            let evaluatorCell = 'Unassigned'; 
            if (isAssigned) {
                if (isAssignedToMe) {
                    evaluatorCell = `<span class="evaluator-me">${app.evaluator_name}</span>`;
                } else {
                    evaluatorCell = app.evaluator_name;
                }
            }
            
            const canEvaluate = currentUserRole && !currentUserRole.includes('Admin');
            const activeStatuses = ['For Evaluation', 'For Interview', 'For Approval'];

            if (!isAssigned && canEvaluate && activeStatuses.includes(app.application_status)) {
                evaluatorCell = `<button class="btn-assign" data-app-id="${app.id}">Assign to Me</button>`;
            }
            
            const statusCell = `<span class="status-badge ${getStatusClass(app.application_status)}">${app.application_status}</span>`;

            // Format dates
            const subDate = new Date(app.submission_timestamp).toLocaleDateString();
            const lastUpdated = formatTimestamp(app.last_updated);

            row.innerHTML = `
                <td data-label="Applicant No." class="${isViewable ? 'clickable-cell' : ''}">${app.application_no}</td>
                <td data-label="Name" class="applicant-name ${isViewable ? 'clickable-cell' : ''}">${app.student_name}</td>
                <td data-label="Program" class="${isViewable ? 'clickable-cell' : ''}">${app.choice1_program}</td>
                <td data-label="Date" class="${isViewable ? 'clickable-cell' : ''}">${subDate}</td>
                <td data-label="Timestamp" class="${isViewable ? 'clickable-cell' : ''}" style="font-size:0.85em; color:#666;">${lastUpdated}</td>
                <td data-label="Status">${statusCell}</td>
                <td data-label="Evaluator">${evaluatorCell}</td>
            `;

            mainTableBody.appendChild(row);
        });
    }

    function showFilterModal(appliedFilters) {
        filterSummaryList.innerHTML = '';
        if (Object.keys(appliedFilters).length === 0) {
            filterSummaryList.innerHTML = '<li><strong>No filters applied. Showing all applications.</strong></li>';
        } else {
            for (const key in appliedFilters) {
                const li = document.createElement('li');
                li.innerHTML = `<strong>${key}:</strong> ${appliedFilters[key]}`;
                filterSummaryList.appendChild(li);
            }
        }
        filterModal.classList.remove('hidden');
    }

    // --- Event Listeners ---
    filterForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const params = new URLSearchParams();
        const appliedFilters = {};

        const status = statusSelect.value;
        const program = programSelect.value;
        const assignment = assignmentSelect.value;
        const dateStart = document.getElementById('date-start').value;
        const dateEnd = document.getElementById('date-end').value;

        if (status) { params.append('status', status); appliedFilters.Status = status; }
        if (program) { params.append('program', program); appliedFilters.Program = program; }
        if (assignment) { params.append('assignment_status', assignment); appliedFilters.Assignment = assignment; }
        if (dateStart) { params.append('date_start', dateStart); appliedFilters['Date Start'] = dateStart; }
        if (dateEnd) { params.append('date_end', dateEnd); appliedFilters['Date End'] = dateEnd; }

        loadApplications(params.toString());
        showFilterModal(appliedFilters);
    });

    filterForm.addEventListener('reset', () => loadApplications());
    
    hideFiltersBtn.addEventListener('click', (e) => {
        e.preventDefault();
        filterForm.classList.toggle('hidden');
        const isHidden = filterForm.classList.contains('hidden');
        hideFiltersBtn.textContent = isHidden ? 'Show Filters' : 'Hide Filters';
    });
    
    closeModalBtn.addEventListener('click', () => filterModal.classList.add('hidden'));

    searchInput.addEventListener('keyup', () => {
        const searchTerm = searchInput.value.toLowerCase();
        document.querySelectorAll('#applicant-list-body tr').forEach(row => {
            // Check cells 0 (No) and 1 (Name)
            if(row.cells.length < 2) return; 
            const applicantName = row.cells[1].textContent.toLowerCase();
            const applicantNo = row.cells[0].textContent.toLowerCase();
            
            if (applicantName.includes(searchTerm) || applicantNo.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });

    exportBtn.addEventListener('click', () => {
        // Updated Headers for Export
        const headers = ["Applicant No.", "Name", "Program Applied", "Submission Date", "Timestamp", "Status", "Evaluator"];
        const dataToExport = [headers]; 

        mainTableBody.querySelectorAll('tr').forEach(row => {
            if (row.style.display === 'none') return;
            
            const rowData = [];
            const cells = row.cells;
            if (cells.length < 7) return; // Ensure row has enough cells
            
            rowData.push({ v: cells[0].textContent, t: 's' }); 
            rowData.push(cells[1].textContent);
            rowData.push(cells[2].textContent);
            rowData.push(cells[3].textContent);
            rowData.push(cells[4].textContent); // Timestamp
            rowData.push(cells[5].textContent); // Status
            rowData.push(cells[6].textContent); // Evaluator

            dataToExport.push(rowData);
        });

        const ws = XLSX.utils.aoa_to_sheet(dataToExport);

        ws['!cols'] = [
            { wch: 20 }, 
            { wch: 30 }, 
            { wch: 35 }, 
            { wch: 15 },
            { wch: 25 }, 
            { wch: 20 }, 
            { wch: 30 } 
        ];

        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, "All Applications");
        XLSX.writeFile(wb, "TAO_Application_List.xlsx");
    });

    mainTableBody.addEventListener('click', (e) => {
        const target = e.target;
        
        if (target.classList.contains('btn-assign')) {
            appIdToAssign = target.dataset.appId;
            const applicantName = target.closest('tr').cells[1].textContent;
            assignModalText.innerHTML = `Assign <strong>${applicantName}</strong> to yourself? This cannot be undone.`;
            
            assignModalLoader.classList.add('hidden');
            assignModalButtons.classList.remove('hidden');
            assignModal.classList.remove('hidden');
            return;
        }

        const row = target.closest('tr');
        if (row && row.classList.contains('clickable-row')) {
            const appId = row.dataset.appId;
            if (appId) {
                window.location.href = `Applicant-Details.php?id=${appId}`;
            }
        }
    });

    confirmAssignBtn.addEventListener('click', () => {
        if (!appIdToAssign) return;

        assignModalLoader.classList.remove('hidden');
        assignModalButtons.classList.add('hidden');

        fetch('api/assign_evaluator.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ application_id: appIdToAssign })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                assignModalText.textContent = 'Assignment successful!';
                loadApplications(); 
            } else {
                assignModalText.textContent = 'Assignment failed: ' + data.message;
            }
            setTimeout(() => assignModal.classList.add('hidden'), 2000);
        })
        .catch(error => {
            console.error('Assignment error:', error);
            assignModalText.textContent = 'A network or server error occurred.';
            setTimeout(() => {
                assignModal.classList.add('hidden');
                appIdToAssign = null;
            }, 2000);
        });
    });

    cancelAssignBtn.addEventListener('click', () => assignModal.classList.add('hidden'));
    
    loadApplications();
});