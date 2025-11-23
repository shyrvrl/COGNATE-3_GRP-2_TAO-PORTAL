<?php include 'api/session_check.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applicant Details | TAO Portal</title>
    
    <link rel="stylesheet" href="css/style.css"> 
    <link rel="stylesheet" href="css/sidebar-layout.css"> 
    <link rel="stylesheet" href="css/header.css"> 
    
    <link rel="stylesheet" href="css/applicant-details.css"> 
    
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
                                            
                        <a href="applicants.php#list-content" class="back-link">&larr; Back to Application List</a>
                        
                        <div class="details-header">
                            <div class="header-left">
                                <h1 class="applicant-name-title" id="applicant-name-title">Loading...</h1>
                                <p class="applicant-meta">
                                    Application No: <span id="application-no">...</span> | Program: <span id="program-applied">...</span>
                                </p>
                            </div>
                            <div class="header-right">
                                <span class="status-label">Current Status:</span>
                                <span class="status-value" id="application-status">...</span>
                            </div>
                        </div>

                        <div class="details-card">
                            <div class="details-card-grid">

                                <img src="assets/placeholder-image.png" alt="Applicant ID Picture" class="picture-placeholder" id="applicant-picture">

                                <section class="info-section" id="personal-info">
                                    <h3>Personal Information</h3>
                                    <div class="info-grid">
                                        <div class="info-field"><label>Full Name</label><span id="info-fullname">...</span></div>
                                        <div class="info-field"><label>Birthdate</label><span id="info-birthdate">...</span></div>
                                        <div class="info-field"><label>Sex</label><span id="info-sex">...</span></div>
                                        <div class="info-field"><label>Nationality</label><span id="info-nationality">...</span></div>
                                        <div class="info-field full-width"><label>Address</label><span id="info-address">...</span></div>
                                        <div class="info-field"><label>Email Address</label><span id="info-email">...</span></div>
                                        <div class="info-field"><label>Mobile No.</label><span id="info-mobile">...</span></div>
                                    </div>
                                </section>

                                <section class="info-section" id="emergency-contact">
                                    <h3>Emergency Contact Person</h3>
                                    <div class="info-grid">
                                        <div class="info-field"><label>Name</label><span id="info-name">...</span></div>
                                        <div class="info-field"><label>Relationship</label><span id="info-relationship">...</span></div>
                                        <div class="info-field"><label>Contact No.</label><span id="info-contact">...</span></div>
                                    </div>
                                </section>

                                <section class="info-section" id="educational-info">
                                    <h3>Educational Information</h3>
                                    <div class="info-grid">
                                        <div class="info-field"><label>SHS School Name</label><span id="info-school-name">...</span></div>
                                        <div class="info-field"><label>SHS Email (Optional)</label><span id="info-shs-email">...</span></div>
                                        <div class="info-field"><label>School Type</label><span id="info-schooltype">...</span></div>
                                        <div class="info-field"><label>Strand</label><span id="info-strand">...</span></div>
                                        <div class="info-field"><label>Track</label><span id="info-track">...</span></div>
                                        <div class="info-field"><label>Specialization</label><span id="info-specialization">...</span></div>
                                        <div class="info-field"><label>JHS Completion Year</label><span id="info-jhs-year">...</span></div>
                                        <div class="info-field"><label>SHS Completion Year</label><span id="info-shs-year">...</span></div>
                                    </div>
                                </section>

                                <!-- GRADES SECTION -->
                                <section class="info-section" id="grades-info">
                                    <h3>Junior and Senior High School Final Grades</h3>
                                    <!-- JHS Grades -->
                                    <h4>Junior High School</h4>
                                    <div class="info-grid">
                                        <div class="info-field"><label>Mathematics Grade</label><span id="jhs-math-grade">...</span></div>
                                        <div class="info-field"><label>Science Grade</label><span id="jhs-science-grade">...</span></div>
                                        <div class="info-field"><label>English Grade</label><span id="jhs-english-grade">...</span></div>
                                    </div>
                                    <!-- SHS Grades -->
                                    <h4>Senior High School - 1st Semester</h4>
                                    <div class="info-grid">
                                        <div class="info-field"><label id="shs-sem1-math-subj-label">Math Subject</label><span id="shs-sem1-math-grade">...</span></div>
                                        <div class="info-field"><label id="shs-sem1-science-subj-label">Science Subject</label><span id="shs-sem1-science-grade">...</span></div>
                                        <div class="info-field"><label id="shs-sem1-english-subj-label">English Subject</label><span id="shs-sem1-english-grade">...</span></div>
                                    </div>
                                    <h4>Senior High School - 2nd Semester</h4>
                                    <div class="info-grid">
                                        <div class="info-field"><label id="shs-sem2-math-subj-label">Math Subject</label><span id="shs-sem2-math-grade">...</span></div>
                                        <div class="info-field"><label id="shs-sem2-science-subj-label">Science Subject</label><span id="shs-sem2-science-grade">...</span></div>
                                        <div class="info-field"><label id="shs-sem2-english-subj-label">English Subject</label><span id="shs-sem2-english-grade">...</span></div>
                                    </div>
                                </section>

                                <!-- PROGRAM CHOICES SECTION -->
                                <section class="info-section" id="choices-info">
                                    <h3>Top 3 Program Choices</h3>
                                    <div class="info-grid">
                                        <div class="info-field"><label>Choice #1 Program</label><span id="choice1-program">...</span></div>
                                        <div class="info-field"><label>Campus</label><span id="choice1-campus">...</span></div>
                                        <div class="info-field"><label>Choice #2 Program</label><span id="choice2-program">...</span></div>
                                        <div class="info-field"><label>Campus</label><span id="choice2-campus">...</span></div>
                                        <div class="info-field"><label>Choice #3 Program</label><span id="choice3-program">...</span></div>
                                        <div class="info-field"><label>Campus</label><span id="choice3-campus">...</span></div>
                                    </div>
                                </section>

                            </div>
                            <div class="button-footer">
                                <a href="ViewDocuments.php" class="btn-view-docs" id="view-docs-btn">
                                    <span class="icon">📄</span> View Documents
                                </a>
                            </div>
                        </div> 
                    </main>
                </div>
            </div>
        </div>
        
    </div> 
    
    <script src="js/componentLoader.js"></script>
    <script src="js/script.js"></script>
    <script src="js/details.js"></script>

</body>
</html>