<?php include 'api/session_check.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applicants | TAO Portal</title>
    
    <link rel="stylesheet" href="css/style.css"> 
    <link rel="stylesheet" href="css/sidebar-layout.css"> 
    <link rel="stylesheet" href="css/header.css"> 
    <link rel="stylesheet" href="css/dashboard.css"> <link rel="stylesheet" href="css/applicants.css"> 
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    
</head>
<body class="page-applicants">

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
                        
                        <h1 class="page-title">Application Management</h1>
                        <p class="page-subtitle">
                            Access, filter, and manage all students applications based on status and program.
                        </p>

                        <nav class="tab-nav">
                            <a href="#summary" class="tab-link active" data-tab="summary-content">Summary</a>
                            <a href="#list" class="tab-link" data-tab="list-content">Application List</a>
                        </nav>

                        <div class="tab-content-wrapper">
                            <div id="summary-content" class="tab-content active">
                            
                                <section class="summary-stats-grid">
                                    <div class="summary-card" style="--card-color: var(--color-green);">
                                        <span class="summary-card__label">Total Applications</span>
                                        <span class="summary-card__value" id="summary-total-apps">0</span>
                                        <span class="summary-card__meta">Overall received for year 2025</span>
                                    </div>
                                    <div class="summary-card" style="--card-color: var(--color-yellow);">
                                        <span class="summary-card__label">Admission Rate</span>
                                        <span class="summary-card__value" id="summary-admission-rate">0%</span>
                                        <span class="summary-card__meta">Approved vs. Total Applications</span>
                                    </div>
                                    <div class="summary-card" style="--card-color: var(--color-blue);">
                                        <span class="summary-card__label">Days Left to Evaluate</span>
                                        <span class="summary-card__value" id="summary-days-left">...</span>
                                        <span class="summary-card__meta" id="summary-deadline-text">Loading deadline...</span>
                                    </div>
                                </section>
                                
                                <div class="summary-table-wrapper">
                                    <table class="summary-table">
                                        <thead>
                                            <tr>
                                                <th>Status</th>
                                                <th>Count</th>
                                                <th>Percentage</th>
                                            </tr>
                                        </thead>
                                        <tbody id="status-summary-tbody">
                                            <!-- JavaScript will populate this area -->
                                        </tbody>
                                    </table>
                                </div>
                                
                                <div class="program-table-wrapper">
                                    <h3 class="table-title">Top Program Applications</h3>
                                    <table class="program-table">
                                        <thead>
                                            <tr>
                                                <th>Program</th>
                                                <th>Count</th>
                                                <th>Acceptance Rate</th>
                                            </tr>
                                        </thead>
                                        <tbody id="top-programs-tbody">
                                            <!-- JavaScript will populate this area -->
                                        </tbody>
                                    </table>
                                </div>

                            </div>

                            </div>   
                        </div>
                            <div id="list-content" class="tab-content">
                                                            
                                <div class="content-card">
                                    <div class="filter-box" id="filter-box">
                                        <div class="filter-header">
                                            <h3>Filter Applications</h3>
                                            <a href="#" id="hide-filters-btn">Hide Filters</a>
                                        </div>
                                        <form class="filter-form" id="filter-form">
                                            <div class="form-row">
                                                <div class="form-group">
                                                    <label for="filter-status">Status</label>
                                                    <select id="filter-status">
                                                        <option value="">All Statuses</option>
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label for="filter-program">Program</label>
                                                    <select id="filter-program">
                                                        <option value="">All Programs</option>
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label for="filter-assignment">Assignment Status</label>
                                                    <select id="filter-assignment">
                                                        <option value="assignedToAll">Assigned (All)</option>
                                                        <option value="me">Assigned to Me</option>
                                                        <option value="unassigned">Unassigned</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="form-row">
                                                <div class="form-group date-range">
                                                    <label>Date Submitted Range</label>
                                                    <div class="date-inputs">
                                                        <input type="date" id="date-start">
                                                        <span>to</span>
                                                        <input type="date" id="date-end">
                                                    </div>
                                                </div>
                                                <div class="form-buttons">
                                                    <button type="submit" class="btn-primary">Apply Filters</button>
                                                    <button type="reset" class="btn-secondary">Clear Filters</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                    <div class="search-export-bar">
                                        <div class="search-input-wrapper">
                                            <span class="search-icon">🔍</span>
                                            <input type="text" id="search-input" placeholder="Search by Applicant Name or No.">
                                        </div>
                                        <button class="btn-export" id="export-btn">Export List</button>
                                    </div>
                                    <h3 class="table-title">All Applications</h3>
                                    <div class="applicant-table-container">
                                        <table class="applicant-table">
                                            <thead>
                                                <tr>
                                                    <th>APPLICANT NO.</th>
                                                    <th>NAME</th>
                                                    <th>PROGRAM APPLIED</th>
                                                    <th>SUBMISSION DATE</th>
                                                    <th>TIMESTAMP</th> <!-- Added New Column Header -->
                                                    <th>STATUS</th>
                                                    <th>EVALUATOR</th> 
                                                </tr>
                                            </thead>
                                            <tbody id="applicant-list-body">
                                                <!-- JS will populate this table -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        </main>
                </div>
            </div>
        </div>
        
    </div> 
    
    <div class="modal-overlay hidden" id="filter-modal">
        <div class="modal-box">
            <h2>Filters Applied</h2>
            <p>The list is now filtered using the following criteria:</p>
            <ul class="filter-summary-list" id="filter-summary-list">
            </ul>
            <button class="btn-primary" id="close-modal-btn">Close</button>
        </div>
    </div>

    <div class="modal-overlay hidden" id="assign-confirm-modal">
        <div class="modal-box">
            <h2 id="assign-modal-title">Confirm Assignment</h2>
            <p id="assign-modal-text">Are you sure you want to assign this applicant to yourself? This action cannot be undone.</p>
            
            <div class="modal-loader hidden" id="assign-modal-loader"></div>
            
            <div class="modal-actions" id="assign-modal-buttons">
                <button class="btn-primary" id="confirm-assign-btn">Yes, Assign to Me</button>
                <button class="btn-secondary" id="cancel-assign-btn">Cancel</button>
            </div>
        </div>
    </div>

    <script src="js/componentLoader.js"></script>
    <script src="js/script.js"></script>
    <script src="js/applicants.js"></script>
    <script src="js/applicants_summary.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

</body>
</html>