document.addEventListener('DOMContentLoaded', () => {
    
    const staffTableBody = document.getElementById('staff-table-body');
    
    // Modal Elements
    const editRoleModal = document.getElementById('edit-role-modal');
    const modalStaffName = document.getElementById('modal-staff-name');
    const roleSelect = document.getElementById('modal-role-select');
    const saveRoleBtn = document.getElementById('save-role-btn');
    const cancelRoleBtn = document.getElementById('cancel-role-btn');

    let isAdmin = false;

    // 1. Check Permissions first
    // We check get_settings because it returns the 'can_edit' boolean based on role
    function checkPermissions() {
        return fetch('api/get_settings.php')
            .then(res => res.json())
            .then(data => {
                // If can_edit is true, user is Admin
                isAdmin = data.can_edit;
                if (!isAdmin) {
                    document.body.classList.add('not-admin');
                }
            })
            .catch(err => console.error("Error checking permissions:", err));
    }

    // 2. Helper: Color coding for roles
    function getRoleClass(role) {
        if (!role) return '';
        if (role.includes('Admin')) return 'role-admin';
        if (role.includes('Evaluator')) return 'role-evaluator';
        // Covers 'Analyzer' or 'Interviewer' usually with green color
        return 'role-interviewer'; 
    }

    // 3. Fetch and Render Staff List
    function loadStaffList() {
        fetch('api/get_staff_list.php')
            .then(res => res.json())
            .then(data => {
                staffTableBody.innerHTML = '';
                
                if (data.length === 0) {
                    staffTableBody.innerHTML = '<tr><td colspan="3">No staff accounts found.</td></tr>';
                    return;
                }

                data.forEach(staff => {
                    const row = document.createElement('tr');
                    row.dataset.staffId = staff.id;
                    row.dataset.currentRole = staff.role;

                    // Name column includes Staff ID No if available
                    const nameDisplay = staff.staff_id_no 
                        ? `<strong>${staff.full_name}</strong><br><span style="font-size:0.85em; color:#666;">ID: ${staff.staff_id_no}</span>`
                        : `<strong>${staff.full_name}</strong>`;

                    // --- LOGIC: Prevent Admin from editing another Admin ---
                    let actionHtml = '';
                    
                    // Logic: 
                    // 1. If user is NOT admin, they see nothing (handled by .admin-only CSS usually, but explicit empty string is safer)
                    // 2. If target staff IS Admin, show "No Actions"
                    // 3. Otherwise, show Edit link
                    
                    if (!isAdmin) {
                        // Non-admins shouldn't see actions
                        actionHtml = ''; 
                    } else if (staff.role && staff.role.toLowerCase().includes('admin')) {
                        // Admin viewing another Admin
                        actionHtml = '<span style="color:#ccc; font-style:italic; font-size:0.9em; cursor:not-allowed;">No Actions</span>';
                    } else {
                        // Admin viewing normal staff
                        actionHtml = '<a href="#" class="action-link edit-role-link">Edit Role</a>';
                    }

                    row.innerHTML = `
                        <td>${nameDisplay}</td>
                        <td><span class="${getRoleClass(staff.role)}">${staff.role}</span></td>
                        <td class="admin-only">
                            ${actionHtml}
                        </td>
                    `;
                    staffTableBody.appendChild(row);
                });
            })
            .catch(err => {
                console.error(err);
                staffTableBody.innerHTML = '<tr><td colspan="3" style="color:red">Error loading data.</td></tr>';
            });
    }

    // 4. Event Delegation for Edit Click
    if (staffTableBody) {
        staffTableBody.addEventListener('click', (e) => {
            if (e.target.classList.contains('edit-role-link')) {
                e.preventDefault();
                
                // Double check permission
                if (!isAdmin) return;

                const row = e.target.closest('tr');
                // Extract just the name text from the strong tag
                const nameText = row.querySelector('strong').textContent;
                const staffId = row.dataset.staffId;
                const currentRole = row.dataset.currentRole;

                modalStaffName.textContent = nameText;
                editRoleModal.dataset.id = staffId;
                
                // Pre-select the role in dropdown
                // Matches the options in the HTML: "Document Evaluator" or "Analyzer"
                if (currentRole.includes('Evaluator')) {
                    roleSelect.value = "Document Evaluator";
                } else if (currentRole.includes('Analyzer')) {
                    roleSelect.value = "Analyzer";
                } else {
                    // Default fallback
                    roleSelect.selectedIndex = 0; 
                }

                editRoleModal.classList.add('visible');
            }
        });
    }

    // 5. Save Role Logic
    if (saveRoleBtn) {
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
                if (data.success) {
                    editRoleModal.classList.remove('visible');
                    loadStaffList(); // Reload table to show changes
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(err => {
                alert('Network error occurred');
                console.error(err);
            })
            .finally(() => {
                saveRoleBtn.textContent = 'Save Role';
                saveRoleBtn.disabled = false;
            });
        });
    }

    // Close Modal Logic
    if (cancelRoleBtn) {
        cancelRoleBtn.addEventListener('click', () => {
            editRoleModal.classList.remove('visible');
        });
    }
    
    // Close when clicking overlay
    if (editRoleModal) {
        editRoleModal.addEventListener('click', (e) => {
            if (e.target === editRoleModal) {
                editRoleModal.classList.remove('visible');
            }
        });
    }

    // --- INITIALIZE ---
    // Check permissions first, THEN load the list to ensure isAdmin is set correctly before rendering
    checkPermissions().then(() => {
        loadStaffList();
    });
});