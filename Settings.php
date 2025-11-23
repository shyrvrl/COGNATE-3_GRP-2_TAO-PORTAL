<?php include 'api/session_check.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Settings | TAO Portal</title>
    
    <link rel="stylesheet" href="css/style.css"> 
    <link rel="stylesheet" href="css/sidebar-layout.css"> 
    <link rel="stylesheet" href="css/header.css"> 
    <link rel="stylesheet" href="css/settings.css">
</head>
<body class="page-settings">

    <div class="app-shell">
        <div id="app-header"></div> 
        <div class="main-layout-wrapper">
            <div id="app-sidebar"></div>
            <div class="main-content-area">
                <div class="portal-content-wrapper">
                    <main class="page-content">
                        
                        <h1 class="settings-title">Portal Settings</h1>
                        <p class="settings-subtitle">
                            Manage key parameters for the current admission cycle.
                        </p>

                        <form class="settings-form-wrapper" id="settings-form">
                            
                            <!-- SECTION 1: ADMISSION CYCLE -->
                            <div class="settings-section-card">
                                <h2 class="section-title">1. Admission Cycle Control</h2>
                                
                                <div class="cycle-control-grid">
                                    <div class="form-group-settings">
                                        <label for="application-open-date">Application Open Date</label>
                                        <input type="date" id="application-open-date" name="application-open-date">
                                    </div>
                                    <div class="form-group-settings">
                                        <label for="application-deadline">Application Deadline</label>
                                        <input type="date" id="application-deadline" name="application-deadline">
                                    </div>
                                    <!-- MOVED SAVE BUTTON HERE -->
                                    <div class="form-group-settings" style="justify-content: flex-end;">
                                        <label>&nbsp;</label> <!-- Spacer -->
                                        <button type="button" id="btn-save-cycle" class="btn-save-settings-green">Save Settings</button>
                                    </div>
                                </div>
                                
                                <div class="portal-status-toggle">
                                    <span class="toggle-label">Student Portal Status Toggle:</span>
                                    <label class="switch">
                                        <input type="checkbox" id="portal-open-toggle">
                                        <span class="slider"></span>
                                    </label>
                                    <span class="toggle-label" id="toggle-status-text">LOADING STATUS...</span>
                                </div>
                                <p class="form-note" style="margin-top: 5px; font-size: 0.85rem; color: #666;">
                                    * Toggling this updates the database immediately.
                                </p>
                            </div>

                            <!-- SECTION 2: STAFF ACCESS -->
                            <div class="settings-section-card">
                                <h2 class="section-title">2. Staff Access Management</h2>
                                <p class="settings-subtitle">Quickly manage the roles of the staff members.</p>
                                
                                <div class="staff-table-container">
                                    <table class="staff-table">
                                        <thead>
                                            <tr>
                                                <th>NAME</th>
                                                <th>ROLE</th>
                                                <th class="admin-only">ACTIONS</th>
                                            </tr>
                                        </thead>
                                        <tbody id="staff-summary-body">
                                            <!-- Populated by JS -->
                                        </tbody>
                                    </table>
                                </div>

                                <div style="text-align: right; margin-top: 20px;">
                                    <a href="StaffList.php" class="btn-view-all-staff">View All Staff</a>
                                </div>
                            </div>
                        </form>
                        
                    </main>
                </div>
            </div>
        </div>
    </div> 

    <!-- Modal for Edit Role -->
    <div class="modal-overlay" id="edit-role-modal">
        <div class="modal-content-container"> 
            <h3 class="modal-title">Edit Staff Role</h3>
            <span class="modal-staff-name" id="modal-staff-name">Staff Name</span>
            
            <div class="modal-form-group">
                <label for="modal-role-select">Assign New Role:</label>
                <select id="modal-role-select">
                    <option value="Document Evaluator">Document Evaluator</option>  
                    <option value="Analyzer">Analyzer</option>       
                </select>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-cancel" id="cancel-role-btn">Cancel</button>
                <button type="button" class="btn-save-modal" id="save-role-btn">Save Role</button>
            </div>
        </div>
    </div>

    <script src="js/componentLoader.js"></script>
    <script src="js/script.js"></script>
    <script src="js/settings.js"></script>

</body>
</html>