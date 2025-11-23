<?php include 'api/session_check.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | TAO Portal</title>
    
    <link rel="stylesheet" href="css/style.css"> 
    <link rel="stylesheet" href="css/sidebar-layout.css"> 
    <link rel="stylesheet" href="css/header.css"> 
    <link rel="stylesheet" href="css/dashboard.css"> 
</head>
<body class="page-dashboard">

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
                        
                        <h1 class="dashboard-title">Dashboard</h1>
                        <p class="dashboard-subtitle">
                            Welcome! Here's a summary of the admission status.
                        </p>
            
                        <!-- TOP ROW: Total, Approved, Rejected, Assigned -->
                        <section class="stats-card-row">
                            <!-- 1. Total (Gray/Neutral) -->
                            <div class="stat-card" style="--card-color: #555;">
                                <span class="stat-card__value" id="stat-total-count">...</span>
                                <span class="stat-card__label">Total Applications</span>
                                <span class="stat-card__meta">Total received</span>
                            </div>

                            <!-- 2. Approved (Green) -->
                            <div class="stat-card" style="--card-color: #5CB85C;">
                                <span class="stat-card__value" id="stat-approved-count">...</span>
                                <span class="stat-card__label">Approved</span>
                                <span class="stat-card__meta">Accepted students</span>
                            </div>

                            <!-- 3. Rejected (Red) -->
                            <div class="stat-card" style="--card-color: #D84046;">
                                <span class="stat-card__value" id="stat-rejected-count">...</span>
                                <span class="stat-card__label">Rejected</span>
                                <span class="stat-card__meta">Rejected applications</span>
                            </div>

                            <!-- 4. Assigned (Blue) -->
                            <div class="stat-card" style="--card-color: #0086C9;">
                                <span class="stat-card__value" id="stat-my-evaluated-count">...</span>
                                <span class="stat-card__label">Applicants Assigned</span>
                                <span class="stat-card__meta">Number of applicants you are assigned</span>
                            </div>
                        </section>
            
                        <div class="dashboard-grid">
                            
                            <!-- NOTICES SECTION -->
                            <section class="notices-card">
                                <div class="card-header notices-header">
                                    <h2>System Notices & Announcements</h2>
                                </div>
                                <!-- Added ID here for JS to target -->
                                <div class="notices-list" id="notices-list-container">
                                    <p>Loading notices...</p>
                                </div>
                                <a href="DB-notices-archive.php" class="view-all-notices-btn">View All Notices &rarr;</a>
                            </section>
            
                        </div> 

                        <!-- BOTTOM ROW: For Evaluation, For Interview, For Approving -->
                        <section class="dashboard-grid-footer grid-span-2">
                            <!-- 1. For Evaluation (Red/Orange) -->
                            <div class="stat-card" style="--card-color: #D84046;">
                                <span class="stat-card__label">For Evaluation</span>
                                <span class="stat-card__value" id="stat-evaluation-count">...</span>
                                <span class="stat-card__meta">Pending first-level review</span>
                            </div>

                            <!-- 2. For Interview (Yellow) -->
                            <div class="stat-card" style="--card-color: #E3B40B;">
                                <span class="stat-card__label">For Interview</span>
                                <span class="stat-card__value" id="stat-interview-count">...</span>
                                <span class="stat-card__meta">Awaiting interview results</span>
                            </div>
                            
                            <!-- 3. For Approving (Blue) -->
                            <div class="stat-card" style="--card-color: #0086C9;">
                                <span class="stat-card__label">For Approving</span>
                                <span class="stat-card__value" id="stat-approving-count">...</span>
                                <span class="stat-card__meta">Final validation stage</span>
                            </div>
                        </section>
                        
                    </main>
                </div>
            </div>
        </div>
    </div> 
    
    <script src="js/componentLoader.js"></script>
    <script src="js/script.js"></script>
    <script src="js/dashboard.js"></script>

</body>
</html>