<?php include 'api/session_check.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Documents | TAO Portal</title>
    
    <link rel="stylesheet" href="css/style.css"> 
    <link rel="stylesheet" href="css/sidebar-layout.css"> 
    <link rel="stylesheet" href="css/header.css"> 
    <link rel="stylesheet" href="css/view-documents.css"> 
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    
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
                        
                        <div class="applicant-details-summary">
                            <div class="applicant-details-header">
                                <a href="Applicant-Details.php" id="back-to-details-link" class="back-link">← Back to Applicant Details</a>
                                <div class="action-buttons-wrapper">
                                    <a href="Evaluation.php" id="start-evaluation-link" class="btn btn-primary-green">Start Evaluation</a>
                                </div>
                            </div>
                            <p class="applicant-name" id="applicant-name">Dela Cruz, Juan M.</p>
                            <p class="applicant-program-details">
                                Application No: <span id="application-no">2025-0001</span> | Program: <span id="program-applied">BS Information Technology</span>
                            </p>
                        </div>


                        <div class="documents-content-grid">
                            <div class="document-list-container">
                                <div class="list-header">
                                    <h3>Required Documents</h3>
                                </div>
                                
                                <div class="document-list" id="document-list-container">
                                    <p>Loading documents...</p>
                                </div>
                            </div>
                            
                            <div class="document-preview-container">
                                <div class="preview-content" id="document-preview-content">
                                    <span class="material-icons preview-icon">description</span>
                                    <p class="text-center-title">Select a document to preview...</p>
                                    <p class="text-center-meta">Select an item from the list to view its contents and perform verification.</p>
                                </div>
                
                            </div>
                            
                        </div>
                        
                    </main>
                </div>
            </div>
        </div>
        
    </div> 

    <script src="js/componentLoader.js"></script>
    <script src="js/script.js"></script>
    <script src="js/documents.js"></script>
    

</body>
</html>