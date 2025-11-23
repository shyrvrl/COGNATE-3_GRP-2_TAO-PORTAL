<?php include 'api/session_check.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Access List | TAO Portal</title>
    
    <link rel="stylesheet" href="css/style.css"> 
    <link rel="stylesheet" href="css/sidebar-layout.css"> 
    <link rel="stylesheet" href="css/header.css"> 
    <link rel="stylesheet" href="css/settings.css">
</head>
<body class="page-settings">

    <div class="app-shell">
        
        <div id="app-header"></div> 
    
        <div class="data-privacy-notice-full">
            <p>Pursuant to <strong>Republic Act No. 10173</strong>, also known as the <strong>Data Privacy Act of 2012</strong>, the Batangas State University, the National Engineering University recognizes its commitment to protect and respect the privacy of its customers and/or stakeholders and ensure that all information collected from them are all processed in accordance with the principles of transparency, legitimate purpose and proportionality mandated under the Data Privacy Act of 2012</p>
        </div>
        
        <div class="main-layout-wrapper">
            
            <div id="app-sidebar"></div>

            <div class="main-content-area">
                
                <div class="portal-content-wrapper">
                    
                    <main class="page-content">
                        
                        <a href="Settings.php" class="back-link">&larr; Back to Portal Settings</a>
                        
                        <h1 class="settings-title">All Staff Access Management</h1>
                        <p class="settings-subtitle">
                            Full directory of staff members and their assigned roles within the TAO Portal.
                        </p>
                        
                        <div class="settings-section-card" style="padding: 20px;">
                            <div class="staff-table-container">
                                <table class="staff-table">
                                    <thead>
                                        <tr>
                                            <th>NAME / ID</th>
                                            <th>ROLE</th>
                                            <th class="admin-only">ACTIONS</th>
                                        </tr>
                                    </thead>
                                    <tbody id="staff-table-body">
                                        <!-- Content populated by js/staff.js -->
                                        <tr><td colspan="3">Loading staff list...</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </main>
                </div>
            </div>
        </div>
    </div> 

    <!-- MODAL FOR EDITING ROLES (Copied from Settings.php) -->
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
    <script src="js/staff.js"></script>

</body>
</html>