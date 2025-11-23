document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const applicationId = urlParams.get('id');

    // If no ID is found, stop and show an error.
    if (!applicationId) {
        const mainContent = document.querySelector('.page-content');
        if (mainContent) {
            mainContent.innerHTML = '<h1>No Applicant ID Provided</h1><p>Please return to the applicant list and select an applicant to view.</p>';
        }
        return;
    }

    // Helper function to get the correct CSS class for a status 
    function getStatusClass(status) {
        if (!status) return 'status-default';
        const sanitizedStatus = status.toLowerCase().replace(/\s+/g, '-');
        const map = {
            'approved': 'status-green',
            'for-approving': 'status-blue',
            'for-evaluation': 'status-red',
            'for-interview': 'status-yellow',
            'rejected': 'status-gray'
        };
        return map[sanitizedStatus] || 'status-default';
    }

    // Fetch all the details for the specific applicant
    fetch(`http://localhost/TAO_portal/api/get_application_details.php?id=${applicationId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            const details = data.details;
            if (!details) {
                document.body.innerHTML = '<h1>Applicant not found.</h1>';
                return;
            }

            // --- Helper function to safely update the text content ---
            const setText = (id, text) => {
                const element = document.getElementById(id);
                if (element) element.textContent = text || 'N/A';
            };
            const setLabel = (id, text) => {
                const element = document.getElementById(id);
                if (element) element.textContent = text || 'Subject';
            }

            // --- Populate the header ---
            setText('applicant-name-title', details.student_name);
            setText('application-no', details.application_no);
            setText('program-applied', details.choice1_program);
            
            // --- Populate Status with Dynamic Color ---
            const statusElement = document.getElementById('application-status');
            if (statusElement) {
                statusElement.textContent = details.application_status;
                statusElement.className = 'status-value'; // Reset classes first
                statusElement.classList.add(getStatusClass(details.application_status));
            }

            // --- Populate Applicant 2x2 Picture ---
            const pictureElement = document.getElementById('applicant-picture');
            if (pictureElement) {
                if (details.picture_path) {
                    // Prepend the base path if necessary. Assumes 'uploads' is in the root.
                    pictureElement.src = details.picture_path;
                } else {
                    // Set a default placeholder if no picture is available
                    pictureElement.src = 'assets/placeholder-image.png';
                }
            }

            // --- Populate Personal Information ---
            setText('info-fullname', details.student_name);
            setText('info-birthdate', details.birthdate ? new Date(details.birthdate).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) : 'N/A');
            setText('info-sex', details.sex);
            setText('info-nationality', details.nationality);
            setText('info-address', details.address);
            setText('info-email', details.email);
            setText('info-mobile', details.mobile_no);

            // --- Format Full Address ---
            const addressParts = [
                details.address_street,
                details.address_barangay,
                details.address_city,
                details.address_province,
                details.address_region
            ].filter(Boolean); // Filter out null/empty parts
            setText('info-address', addressParts.join(', ') || 'N/A');
            
            // --- Populate Emergency Contact Information ---
            setText('info-name', details.emergency_contact_name);
            setText('info-relationship', details.emergency_contact_relationship);
            setText('info-contact', details.emergency_contact_phone);

            // --- Populate Educational Information ---
            setText('info-school-name', details.shs_school_name);
            setText('info-shs-email', details.shs_email);
            setText('info-schooltype', details.school_type);
            setText('info-strand', details.shs_strand);
            setText('info-track', details.shs_track);
            setText('info-specialization', details.specialization);
            setText('info-jhs-year', details.jhs_completion_year);
            setText('info-shs-year', details.shs_completion_year);

            // --- Populate SHS Grades ---
            // Semester 1
            setLabel('shs-sem1-math-subj-label', details.shs_sem1_math_subj);
            setText('shs-sem1-math-grade', details.shs_sem1_math_grade);
            setLabel('shs-sem1-science-subj-label', details.shs_sem1_science_subj);
            setText('shs-sem1-science-grade', details.shs_sem1_science_grade);
            setLabel('shs-sem1-english-subj-label', details.shs_sem1_english_subj);
            setText('shs-sem1-english-grade', details.shs_sem1_english_grade);
            // Semester 2
            setLabel('shs-sem2-math-subj-label', details.shs_sem2_math_subj);
            setText('shs-sem2-math-grade', details.shs_sem2_math_grade);
            setLabel('shs-sem2-science-subj-label', details.shs_sem2_science_subj);
            setText('shs-sem2-science-grade', details.shs_sem2_science_grade);
            setLabel('shs-sem2-english-subj-label', details.shs_sem2_english_subj);
            setText('shs-sem2-english-grade', details.shs_sem2_english_grade);

            // --- Populate JHS Grades ---
            setText('jhs-math-grade', details.jhs_math_grade);
            setText('jhs-science-grade', details.jhs_science_grade);
            setText('jhs-english-grade', details.jhs_english_grade);

            // --- Populate Program Choices ---
            setText('choice1-program', details.choice1_program);
            setText('choice1-campus', details.choice1_campus);
            setText('choice2-program', details.choice2_program);
            setText('choice2-campus', details.choice2_campus);
            setText('choice3-program', details.choice3_program);
            setText('choice3-campus', details.choice3_campus);

            // --- Update Button Links ---
            const viewDocsBtn = document.getElementById('view-docs-btn');
            if (viewDocsBtn) {
                viewDocsBtn.href = `ViewDocuments.php?id=${applicationId}`;
            }
        })
        .catch(error => {
            console.error('Error fetching applicant details:', error);
            const mainContent = document.querySelector('.page-content');
            if (mainContent) {
                mainContent.innerHTML = '<h1>Error</h1><p>Could not load applicant details. Please try again later.</p>';
            }
        });
    })