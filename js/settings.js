// js/settings.js

document.addEventListener('DOMContentLoaded', () => {

    const portalOpenToggle = document.getElementById('portal-open-toggle');
    const toggleStatusText = document.getElementById('toggle-status-text');
    const documentChecklist = document.getElementById('document-checklist');
    const newDocTitleInput = document.getElementById('new-doc-title');
    const addDocBtn = document.getElementById('add-doc-btn');
    const settingsForm = document.getElementById('settings-form');
    
    // Modal elements
    const editRoleModal = document.getElementById('edit-role-modal');
    const modalStaffNameSpan = document.getElementById('modal-staff-name');
    const saveRoleBtn = document.getElementById('save-role-btn');
    const cancelRoleBtn = document.getElementById('cancel-role-btn');
    const roleSelect = document.getElementById('modal-role-select');
    const staffTableBody = document.querySelector('.staff-table tbody');


    // --- 1. Admission Cycle Control Toggle Logic ---
    function updatePortalStatusText() {
        if (portalOpenToggle.checked) {
            toggleStatusText.textContent = 'APPLICATIONS ARE CURRENTLY OPEN';
            toggleStatusText.style.color = '#38A169';
        } else {
            toggleStatusText.textContent = 'APPLICATIONS ARE CURRENTLY CLOSED';
            toggleStatusText.style.color = '#D84046'; // BSU Red
        }
    }

    if (portalOpenToggle && toggleStatusText) {
        // Initial state update
        updatePortalStatusText();
        
        // Event listener for changes
        portalOpenToggle.addEventListener('change', updatePortalStatusText);
    }
    
    // --- 2. Document Checklist Logic ---

    // Function to handle clicking on a document checklist item toggle
    function handleDocumentToggle(e) {
        const toggle = e.target.closest('.required-toggle');
        if (!toggle) return;
        
        // Find the parent item for visual feedback
        const item = toggle.closest('.checklist-item');

        // Toggle the required state 
        const isRequired = toggle.dataset.required === 'true';
        toggle.dataset.required = (!isRequired).toString();

        if (isRequired) {
            // Document is now NOT required (Unchecked)
            // Removes the background, checkmark, and resets item background
            toggle.style.backgroundColor = 'transparent';
            toggle.textContent = '';
            if (item) item.style.backgroundColor = '#f9f9f9'; // Keep default row background
        } else {
            // Document is now required (Checked)
            // Adds the green background and checkmark
            toggle.style.backgroundColor = '#38A169';
            toggle.textContent = '✓';
            if (item) item.style.backgroundColor = '#f0f0f0'; // Slightly darker when checked for contrast
        }
        
        // In a real app, you would send an AJAX request to save this setting.
        console.log(`Document ID ${item.dataset.docId} required status toggled to: ${!isRequired}`);
    }

    // Attach event listener to the list container for delegation
    if (documentChecklist) {
        documentChecklist.addEventListener('click', handleDocumentToggle);
    }

    // Function to add a new document item
    let nextDocId = 6; // Start ID after existing items (1-5)
    function addNewDocumentItem() {
        const title = newDocTitleInput.value.trim();
        
        if (title === "") {
            console.error("Document title cannot be empty.");
            // NOTE: Using a custom modal/message box instead of alert()
            newDocTitleInput.focus();
            return;
        }

        const newItem = document.createElement('div');
        newItem.classList.add('checklist-item');
        newItem.dataset.docId = nextDocId++;
        
        // Default new item to unchecked (data-required="false")
        newItem.innerHTML = `
            <span class="checklist-item-title">${title}</span>
            <div class="required-toggle" data-required="false"></div>
        `;
        
        documentChecklist.appendChild(newItem);
        newDocTitleInput.value = ''; // Clear input
        newDocTitleInput.focus();
        
        console.log(`New document '${title}' added (ID: ${newItem.dataset.docId}) and set as NOT required.`);
    }

    if (addDocBtn) {
        addDocBtn.addEventListener('click', addNewDocumentItem);
    }
    
    // Allow adding via Enter key in the input field
    if (newDocTitleInput) {
        newDocTitleInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault(); // Prevent form submission
                addNewDocumentItem();
            }
        });
    }
    
    // --- 3. Staff Access Management Modal Logic ---
    
    /**
     * Finds the current role class based on the role name.
     * @param {string} roleName 
     * @returns {string} The CSS class for the role.
     */
    function getRoleClass(roleName) {
        if (roleName.includes('Admin')) return 'role-admin';
        if (roleName.includes('Evaluator')) return 'role-evaluator';
        if (roleName.includes('Assessor') || roleName.includes('Interviewer')) return 'role-interviewer';
        return '';
    }

    /**
     * Opens the modal when 'Edit Role' is clicked.
     */
    function handleEditRoleClick(e) {
        const editLink = e.target.closest('.action-link');
        if (!editLink || !staffTableBody || !editRoleModal) return;

        e.preventDefault();
        
        // Find the parent row (<tr>)
        const row = editLink.closest('tr');
        if (!row) return;

        const staffNameCell = row.cells[0];
        const currentRoleCell = row.cells[1].querySelector('span'); // The <span> containing the role text
        
        const staffName = staffNameCell.textContent.trim();
        const currentRole = currentRoleCell.textContent.trim();
        
        // Set modal data
        modalStaffNameSpan.textContent = staffName;
        editRoleModal.dataset.staffId = staffName; // Use name as ID for demo
        
        // Set select box to the current role (matching the option text to the current role text)
        for (let i = 0; i < roleSelect.options.length; i++) {
            if (roleSelect.options[i].textContent === currentRole) {
                roleSelect.value = roleSelect.options[i].value;
                break;
            }
        }
        
        editRoleModal.classList.add('visible');
    }
    
    /**
     * Closes the modal.
     */
    function closeModal() {
        if (editRoleModal) {
            editRoleModal.classList.remove('visible');
        }
    }
    
    /**
     * Handles saving the new role.
     */
    function handleSaveRole() {
        const staffName = editRoleModal.dataset.staffId;
        const newRoleText = roleSelect.options[roleSelect.selectedIndex].textContent;
        const newRoleValue = roleSelect.value;
        const newRoleClass = getRoleClass(newRoleText);

        console.log(`Saving new role for ${staffName}: ${newRoleText} (${newRoleValue})`);

        // --- Simulated UI Update (In a real app, this would happen after a successful API call) ---
        
        // Find the corresponding table row in the Staff Access Management table (Section 3)
        let rowToUpdate = null;
        staffTableBody.querySelectorAll('tr').forEach(row => {
            if (row.cells[0].textContent.trim() === staffName) {
                rowToUpdate = row;
            }
        });

        if (rowToUpdate) {
            const roleSpan = rowToUpdate.cells[1].querySelector('span');
            // 1. Update text
            roleSpan.textContent = newRoleText;
            // 2. Update color class
            roleSpan.className = ''; // Clear existing classes
            roleSpan.classList.add(newRoleClass);
        }
        
        // --- End of Simulated UI Update ---

        closeModal();
    }


    if (staffTableBody) {
        // Attach click listener to the table body to catch 'Edit Role' clicks
        staffTableBody.addEventListener('click', handleEditRoleClick);
    }
    
    if (cancelRoleBtn) {
        cancelRoleBtn.addEventListener('click', closeModal);
    }
    
    if (saveRoleBtn) {
        saveRoleBtn.addEventListener('click', handleSaveRole);
    }
    
    // Close modal if clicking the overlay backdrop
    if (editRoleModal) {
        editRoleModal.addEventListener('click', (e) => {
            if (e.target === editRoleModal) {
                closeModal();
            }
        });
    }

    // --- 4. Save Settings Button Logic ---
    if (settingsForm) {
        settingsForm.addEventListener('submit', (e) => {
            e.preventDefault();
            
            console.log("--- SAVING ALL PORTAL SETTINGS ---");
            // Simulated Success Feedback 
            e.target.querySelector('.btn-save-settings-green').textContent = 'SETTINGS SAVED!';
            setTimeout(() => {
                e.target.querySelector('.btn-save-settings-green').textContent = 'SAVE ALL SETTINGS';
            }, 2000);
        });
    }

    console.log("Settings page script loaded.");
});