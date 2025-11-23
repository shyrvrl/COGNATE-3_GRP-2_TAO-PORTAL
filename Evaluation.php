<?php include 'api/session_check.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Start Evaluation | TAO Portal</title>
    
    <!-- CSS Links -->
    <link rel="stylesheet" href="css/style.css"> 
    <link rel="stylesheet" href="css/sidebar-layout.css"> 
    <link rel="stylesheet" href="css/header.css"> 
    <link rel="stylesheet" href="css/evaluation.css"> 
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
                        <div class="applicant-info-header">
                            <a href="#" id="back-link" class="back-link">← Back to View Documents</a>
                            <h2 class="form-page-title">Applicant Evaluation Form</h2>
                            <p class="applicant-review-details" id="applicant-review-details">Reviewing: ...</p>
                        </div>

                        <form class="evaluation-form-wrapper" id="evaluation-form">
                            <div class="evaluation-section">
                                <h3 class="section-title">1. Document and Integrity Verification Checklist</h3>
                                <div class="verification-checklist-table">
                                    <table>
                                        <thead>
                                            <tr>
                                                <th style="width: 40%;">Document / Requirement</th>
                                                <th style="width: 20%;">AI Result</th>
                                                <th style="width: 40%;">Human Evaluation</th>
                                            </tr>
                                        </thead>
                                        <tbody id="checklist-tbody">
                                            <!-- JS will build this hierarchically -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="evaluation-section">
                                <h3 class="section-title">2. Final Assessment</h3>
                                <div class="final-assessment-grid">
                                    <div class="form-group third-width">
                                        <label for="app-status">Update Application Status</label>
                                        <select id="app-status" name="app-status" required>
                                            <option value="For Evaluation">For Evaluation</option>
                                            <option value="For Interview">For Interview</option>
                                            <option value="For Approving">For Approving</option>
                                            <option value="Approved">Approved</option>
                                            <option value="Rejected">Rejected</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group full-width">
                                    <label for="reviewer-comments">Reviewer Comments</label>
                                    <textarea id="reviewer-comments" name="reviewer-comments" rows="4" placeholder="Provide detailed notes..."></textarea>
                                </div>
                                <p class="form-required-note">Required for Audit/Record keeping.</p>
                                <button type="submit" class="btn btn-submit-evaluation">Submit Final Evaluation</button>
                            </div>
                        </form>
                    </main>
                </div>
            </div>
        </div>
    </div> 

    <script src="js/componentLoader.js"></script>
    <script src="js/script.js"></script>
    <script src="js/evaluation.js"></script>

</body>
</html>