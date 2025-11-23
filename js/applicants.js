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
    function switchTab(targetTabId) { /* ... */ }
    const currentHash = window.location.hash.substring(1);


    // 
    /**
     * Switches the visible tab and updates the URL hash.
     * @param {string} targetTabId - The ID of the tab content to show.
     */
    function switchTab(targetTabId) {
        // Update URL hash without causing the page to jump
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
        switchTab('summary-content'); // Default to the summary tab
    }

    // Add click listeners to all tab links
    tabs.forEach(tab => {
        tab.addEventListener('click', (e) => {
            e.preventDefault();
            switchTab(tab.dataset.tab);
        });
    });


    // --- Main Data Loading and Table Rendering Function ---
    function loadApplications(filterParams = '') {
        mainTableBody.innerHTML = '<tr><td colspan="6">Loading...</td></tr>';
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

    // --- Helper Functions ---

    /**
     * Populates the filter dropdowns with data from the API.
     * @param {object} options - Contains arrays of statuses and programs.
     */
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

    /**
     * REVISION 2: Gets a specific CSS class based on the application status string.
     * @param {string} status - The application status text.
     * @returns {string} The corresponding CSS class name.
     */
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
    
    /**
     * Renders the data into the main application table, applying all necessary logic.
     * @param {Array} data - An array of application objects from the API.
     */
    function renderMainTable(data) {
        mainTableBody.innerHTML = '';

        if (data.length === 0) {
            mainTableBody.innerHTML = `<tr><td colspan="6">No applications match the current filters.</td></tr>`;
            return;
        }

        data.forEach(app => {
            const row = document.createElement('tr');
            row.dataset.appId = app.id;

            const isAssigned = !!app.evaluator_id;
            const isAssignedToMe = (parseInt(app.evaluator_id) === currentUserId);
            
            // A row is viewable only if it's assigned to the current user.
            const isViewable = isAssignedToMe;

            if (isViewable) {
                row.className = 'clickable-row';
            } else {
                row.className = ''; 
            }
                
            // --- REVISION 2 & 3: Determine the content for the "Evaluator" cell ---
            let evaluatorCell = 'Unassigned'; // Default to Unassigned
            if (isAssigned) {
                if (isAssignedToMe) {
                    // If assigned to me, wrap in a span with the special class
                    evaluatorCell = `<span class="evaluator-me">${app.evaluator_name}</span>`;
                } else {
                    // If assigned to someone else, just show their name
                    evaluatorCell = app.evaluator_name;
                }
            }
            
            const canEvaluate = currentUserRole && !currentUserRole.includes('Admin');
            const activeStatuses = ['For Evaluation', 'For Interview', 'For Approving'];

            // Show the button ONLY if unassigned, user is not an admin, and status is active.
            if (!isAssigned && canEvaluate && activeStatuses.includes(app.application_status)) {
                evaluatorCell = `<button class="btn-assign" data-app-id="${app.id}">Assign to Me</button>`;
            }
            
            const statusCell = `<span class="status-badge ${getStatusClass(app.application_status)}">${app.application_status}</span>`;

            row.innerHTML = `
                <td data-label="Applicant No." class="${isViewable ? 'clickable-cell' : ''}">${app.application_no}</td>
                <td data-label="Name" class="applicant-name ${isViewable ? 'clickable-cell' : ''}">${app.student_name}</td>
                <td data-label="Program" class="${isViewable ? 'clickable-cell' : ''}">${app.choice1_program}</td>
                <td data-label="Date" class="${isViewable ? 'clickable-cell' : ''}">${new Date(app.submission_timestamp).toLocaleDateString()}</td>
                <td data-label="Status">${statusCell}</td>
                <td data-label="Evaluator">${evaluatorCell}</td>
            `;

            mainTableBody.appendChild(row);
        });
    }
    /**
     * Populates and displays the "Filters Applied" modal.
     * @param {object} appliedFilters - A key-value object of the filters used.
     */
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

    // Handle form submission for applying filters
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

    // Handle the "Clear Filters" button
    filterForm.addEventListener('reset', () => {
        // Just reload all applications. Our flag prevents filter duplication.
        loadApplications();
    });
    hideFiltersBtn.addEventListener('click', (e) => {
        e.preventDefault();
        filterForm.classList.toggle('hidden');
        const isHidden = filterForm.classList.contains('hidden');
        hideFiltersBtn.textContent = isHidden ? 'Show Filters' : 'Hide Filters';
    });
    
    // Close the filter modal
    closeModalBtn.addEventListener('click', () => filterModal.classList.add('hidden'));

    // Live search functionality
    searchInput.addEventListener('keyup', () => {
        const searchTerm = searchInput.value.toLowerCase();
        document.querySelectorAll('#applicant-list-body tr').forEach(row => {
            const applicantName = row.cells[1]?.textContent.toLowerCase();
            const applicantNo = row.cells[0]?.textContent.toLowerCase();
            
            if (applicantName?.includes(searchTerm) || applicantNo?.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });

    // Export to Excel functionality
    exportBtn.addEventListener('click', () => {
        const headers = ["Applicant No.", "Name", "Program Applied", "Submission Date", "Status", "Evaluator"];
        const dataToExport = [headers]; 

        // Iterate through each visible row in the table
        mainTableBody.querySelectorAll('tr').forEach(row => {
            // Skip rows that are hidden by the search filter
            if (row.style.display === 'none') return;
            
            const rowData = [];
            const cells = row.cells;
            
            // Cell 0: Applicant No. (Explicitly set as a string)
            rowData.push({ v: cells[0].textContent, t: 's' }); 
            
            // Other cells can be added normally
            rowData.push(cells[1].textContent);
            rowData.push(cells[2].textContent);
            rowData.push(cells[3].textContent);
            rowData.push(cells[4].textContent);
            rowData.push(cells[5].textContent);

            dataToExport.push(rowData);
        });

        // Create the worksheet from our manually built array of arrays
        const ws = XLSX.utils.aoa_to_sheet(dataToExport);

        ws['!cols'] = [
            { wch: 20 }, // Applicant No.
            { wch: 30 }, // Name
            { wch: 35 }, // Program
            { wch: 15 }, // Date
            { wch: 20 }, // Status
            { wch: 30 }  // Evaluator
        ];

        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, "All Applications");
        
        // Trigger the download
        XLSX.writeFile(wb, "TAO_Application_List.xlsx");
    });

    // Event delegation for clicks on the table (Assign button and row clicks)
    mainTableBody.addEventListener('click', (e) => {
        const target = e.target; // The specific element that was clicked
        
        // Case 1: Click was on an "Assign to Me" button
        if (target.classList.contains('btn-assign')) {
            appIdToAssign = target.dataset.appId;
            const applicantName = target.closest('tr').cells[1].textContent;
            assignModalText.innerHTML = `Assign <strong>${applicantName}</strong> to yourself? This cannot be undone.`;
            
            assignModalLoader.classList.add('hidden');
            assignModalButtons.classList.remove('hidden');
            assignModal.classList.remove('hidden');
            return; // Stop further execution
        }

        // Case 2: Click was on a row to view details
        const row = target.closest('tr');
        if (row && row.classList.contains('clickable-row')) {
            const appId = row.dataset.appId;
            if (appId) {
                window.location.href = `Applicant-Details.php?id=${appId}`;
            }
        }
    });

    // Handle clicking the final "Yes, Assign to Me" confirmation
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
                // Reload the list to reflect the change correctly
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
    
    // --- Initial Load ---
    loadApplications();
});