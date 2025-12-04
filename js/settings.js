document.addEventListener('DOMContentLoaded', () => {

    // --- ELEMENTS ---
    // Cycle Control Elements
    const openDateInput = document.getElementById('application-open-date');
    const deadlineInput = document.getElementById('application-deadline');
    const portalToggle = document.getElementById('portal-open-toggle');
    const statusText = document.getElementById('toggle-status-text');
    const saveCycleBtn = document.getElementById('btn-save-cycle');

    // Staff Management Elements (Summary Table)
    const staffSummaryBody = document.getElementById('staff-summary-body');
    
    // Modal Elements
    const editRoleModal = document.getElementById('edit-role-modal');
    const saveRoleBtn = document.getElementById('save-role-btn');
    const cancelRoleBtn = document.getElementById('cancel-role-btn');
    const roleSelect = document.getElementById('modal-role-select');
    const modalStaffName = document.getElementById('modal-staff-name');

    // --- 1. INITIAL LOAD: GET SETTINGS & PERMISSIONS ---
    function loadSettings() {
        fetch('api/get_settings.php')
            .then(res => res.json())
            .then(data => {
                if(data.error) throw new Error(data.message);

                const s = data.settings;
                
                // Populate inputs
                if(openDateInput) openDateInput.value = s.application_open_date;
                if(deadlineInput) deadlineInput.value = s.application_deadline;
                if(portalToggle) portalToggle.checked = (parseInt(s.is_portal_open) === 1);
                
                updateStatusText();

                // PERMISSION CHECK
                // data.can_edit is true ONLY if the user is an Admin
                if (!data.can_edit) {
                    console.log("User is not Admin. Disabling inputs.");
                    if(openDateInput) openDateInput.disabled = true;
                    if(deadlineInput) deadlineInput.disabled = true;
                    if(portalToggle) portalToggle.disabled = true;
                    if(saveCycleBtn) {
                        saveCycleBtn.disabled = true;
                        saveCycleBtn.textContent = "Admin Access Only";
                        saveCycleBtn.style.backgroundColor = "#ccc";
                        saveCycleBtn.style.cursor = "not-allowed";
                    }
                    // Add class to body to help CSS hide specific elements if needed
                    document.body.classList.add('not-admin'); 
                } else {
                    // Is Admin - Ensure enabled
                    document.body.classList.remove('not-admin');
                    if(openDateInput) openDateInput.disabled = false;
                    if(deadlineInput) deadlineInput.disabled = false;
                    if(portalToggle) portalToggle.disabled = false;
                    if(saveCycleBtn) {
                        saveCycleBtn.disabled = false;
                        saveCycleBtn.textContent = "Save Settings";
                        saveCycleBtn.style.backgroundColor = ""; // Reset to default CSS
                        saveCycleBtn.style.cursor = "pointer";
                    }
                }
            })
            .catch(err => console.error("Error loading settings:", err));
    }

    function updateStatusText() {
        if (!statusText) return;
        if (portalToggle.checked) {
            statusText.textContent = 'APPLICATIONS ARE CURRENTLY OPEN';
            statusText.style.color = '#38A169';
        } else {
            statusText.textContent = 'APPLICATIONS ARE CURRENTLY CLOSED';
            statusText.style.color = '#D84046';
        }
    }

    // --- 2. SAVE SETTINGS (Dates & Toggle) ---
    function saveSettings() {
        const payload = {
            application_open_date: openDateInput.value,
            application_deadline: deadlineInput.value,
            is_portal_open: portalToggle.checked ? 1 : 0
        };

        saveCycleBtn.textContent = 'Saving...';
        saveCycleBtn.disabled = true;
        
        fetch('api/save_settings.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                saveCycleBtn.textContent = 'Saved Successfully!';
                setTimeout(() => { 
                    saveCycleBtn.textContent = 'Save Settings'; 
                    saveCycleBtn.disabled = false;
                }, 2000);
            } else {
                alert("Error: " + data.message);
                saveCycleBtn.textContent = 'Save Settings';
                saveCycleBtn.disabled = false;
            }
        })
        .catch(err => {
            console.error(err);
            alert("Network Error");
            saveCycleBtn.textContent = 'Save Settings';
            saveCycleBtn.disabled = false;
        });
    }

    // --- EVENT LISTENERS FOR SETTINGS ---
    if(saveCycleBtn) saveCycleBtn.addEventListener('click', saveSettings);
    if(portalToggle) portalToggle.addEventListener('change', updateStatusText);


    // --- 3. STAFF MANAGEMENT LOGIC (Summary View) ---
    
    function getRoleClass(role) {
        if (!role) return '';
        if(role.includes('Admin')) return 'role-admin';
        if(role.includes('Evaluator')) return 'role-evaluator';
        return 'role-interviewer';
    }

    function loadStaffSummary() {
        if (!staffSummaryBody) return;
        
        fetch('api/get_staff_list.php')
            .then(res => res.json())
            .then(data => {
                staffSummaryBody.innerHTML = '';
                
                // Only show the first 3 for summary
                const summary = data.slice(0, 3);
                
                summary.forEach(staff => {
                    const row = document.createElement('tr');
                    row.dataset.staffId = staff.id; 
                    row.dataset.currentRole = staff.role;
                    
                    // --- LOGIC: Prevent Admin from editing another Admin ---
                    let actionHtml = '';
                    // Check if the current user is allowed to edit (must be Admin via class check or logic)
                    // AND the target staff is NOT an Admin
                    
                    if (staff.role && staff.role.toLowerCase().includes('admin')) {
                        // If target is Admin, show text instead of link
                        actionHtml = '<span style="color:#999; font-size:0.9em; font-style:italic;">(Co-Admin)</span>';
                    } else {
                        // Else show edit link
                        actionHtml = '<a href="#" class="action-link edit-role-link">Edit Role</a>';
                    }

                    row.innerHTML = `
                        <td>${staff.full_name}</td>
                        <td><span class="${getRoleClass(staff.role)}">${staff.role}</span></td>
                        <td class="admin-only">
                            ${actionHtml}
                        </td>
                    `;
                    staffSummaryBody.appendChild(row);
                });
            })
            .catch(err => {
                console.error("Error loading staff summary:", err);
                staffSummaryBody.innerHTML = '<tr><td colspan="3">Error loading data</td></tr>';
            });
    }

    // --- 4. MODAL LOGIC (Edit Role) ---

    // Open Modal
    if(staffSummaryBody) {
        staffSummaryBody.addEventListener('click', (e) => {
            // Use closest to handle clicks on icon/text if nested
            if(e.target.classList.contains('edit-role-link')) {
                e.preventDefault();
                
                // Double check: if body has 'not-admin', do nothing (client-side security)
                if(document.body.classList.contains('not-admin')) return;

                const row = e.target.closest('tr');
                const name = row.cells[0].textContent;
                const id = row.dataset.staffId;
                const currentRole = row.dataset.currentRole;
                
                modalStaffName.textContent = name;
                editRoleModal.dataset.id = id;
                
                // Pre-select the role in dropdown
                // Mapping: If current role contains "Evaluator", select "Document Evaluator"
                // If contains "Analyzer", select "Analyzer"
                if (currentRole.includes('Evaluator')) {
                    roleSelect.value = "Document Evaluator";
                } else if (currentRole.includes('Analyzer')) {
                    roleSelect.value = "Analyzer";
                } else {
                    // Fallback to first option
                    roleSelect.selectedIndex = 0;
                }

                editRoleModal.classList.add('visible');
            }
        });
    }

    // --- 5. CHANGE PASSWORD LOGIC ---
    const btnChangePass = document.getElementById('btn-change-password');
    const inputCurrent = document.getElementById('current-password');
    const inputNew = document.getElementById('new-password');
    const inputConfirm = document.getElementById('confirm-password');
    const passMessage = document.getElementById('password-message');

    if (btnChangePass) {
        btnChangePass.addEventListener('click', () => {
            // Clear previous messages
            passMessage.textContent = '';
            passMessage.style.color = '#333';

            const currentVal = inputCurrent.value;
            const newVal = inputNew.value;
            const confirmVal = inputConfirm.value;

            // Simple Client-side Validation
            if (!currentVal || !newVal || !confirmVal) {
                passMessage.textContent = "Please fill in all password fields.";
                passMessage.style.color = "var(--color-red)";
                return;
            }

            if (newVal !== confirmVal) {
                passMessage.textContent = "New passwords do not match.";
                passMessage.style.color = "var(--color-red)";
                return;
            }

            if (newVal.length < 6) {
                passMessage.textContent = "Password must be at least 6 characters.";
                passMessage.style.color = "var(--color-red)";
                return;
            }

            // Disable button
            btnChangePass.textContent = "Updating...";
            btnChangePass.disabled = true;

            // API Call
            fetch('api/change_password.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    current_password: currentVal,
                    new_password: newVal,
                    confirm_password: confirmVal
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    passMessage.textContent = "Password updated successfully!";
                    passMessage.style.color = "var(--color-green)";
                    // Clear inputs
                    inputCurrent.value = '';
                    inputNew.value = '';
                    inputConfirm.value = '';
                } else {
                    passMessage.textContent = "Error: " + data.message;
                    passMessage.style.color = "var(--color-red)";
                }
            })
            .catch(err => {
                console.error(err);
                passMessage.textContent = "A network error occurred.";
                passMessage.style.color = "var(--color-red)";
            })
            .finally(() => {
                btnChangePass.textContent = "Update Password";
                btnChangePass.disabled = false;
            });
        });
    }

    // Save Role via API
    if(saveRoleBtn) {
        saveRoleBtn.addEventListener('click', () => {
            const id = editRoleModal.dataset.id;
            const newRole = roleSelect.value;
            
            saveRoleBtn.textContent = 'Saving...';
            saveRoleBtn.disabled = true;

            fetch('api/update_staff_role.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ staff_id: id, new_role: newRole })
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    editRoleModal.classList.remove('visible');
                    loadStaffSummary(); // Refresh the table to show new role
                } else {
                    alert('Error updating role: ' + data.message);
                }
            })
            .catch(err => {
                console.error(err);
                alert('Network error occurred.');
            })
            .finally(() => {
                saveRoleBtn.textContent = 'Save Role';
                saveRoleBtn.disabled = false;
            });
        });
    }

    // Close Modal
    if(cancelRoleBtn) {
        cancelRoleBtn.addEventListener('click', () => {
            editRoleModal.classList.remove('visible');
        });
    }

    // --- INITIALIZATION ---
    loadSettings();
    loadStaffSummary();
});