<?php include 'api/session_check.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notices Archive | TAO Portal</title>
    
    <link rel="stylesheet" href="css/style.css"> 
    <link rel="stylesheet" href="css/sidebar-layout.css"> 
    <link rel="stylesheet" href="css/header.css"> 
    <link rel="stylesheet" href="css/db-notices.css"> 
    
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
                        
                        <!-- Added Back Link -->
                        <div class="archive-header-row">
                            <a href="Dashboard.php" class="back-link">← Back to Dashboard</a>
                        </div>

                        <h1 class="archive-title">Notices & Announcements Archive</h1>
                        <p class="archive-subtitle">
                            Full history of system updates, policy changes and urgent notifications.
                        </p>

                        <!-- Added ID="search-form" and IDs to inputs -->
                        <form class="search-bar" id="search-form">
                            <div class="search-input-wrapper">
                                <span class="search-icon">🔍</span>
                                <input type="text" id="search-input" placeholder="Search title or content...">
                            </div>
                            
                            <!-- Updated values to match DB colors -->
                            <select class="filter-select" id="filter-select">
                                <option value="all">Filter by Type (All)</option>
                                <option value="blue">Policy Update (Blue)</option>
                                <option value="green">Maintenance (Green)</option>
                                <option value="red">Urgent Alert (Red)</option>
                            </select>
                            
                            <button type="submit" class="search-button">Search</button>
                        </form>

                        <!-- Empty Container for Dynamic Content -->
                        <div class="archive-list" id="archive-list-container">
                            <p class="loading-text">Loading notices...</p>
                        </div>

                        <!-- Pagination Controls -->
                        <nav class="pagination" id="pagination-controls" style="display:none;">
                            <button id="prev-btn" class="page-link">&larr; Previous</button>
                            <span class="page-info" id="page-info-text">Page 1 of 1</span>
                            <button id="next-btn" class="page-link">Next &rarr;</button>
                        </nav>
                        
                    </main>
                </div>
            </div>
        </div>
        
    </div> 
    
    <script src="js/componentLoader.js"></script>
    <script src="js/script.js"></script>
    <!-- New JS File -->
    <script src="js/db-notices.js"></script>

</body>
</html>