document.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    const applicationId = urlParams.get('id');
    const checklistTbody = document.getElementById('checklist-tbody');
    const evalForm = document.getElementById('evaluation-form');
    const applicantDetailsP = document.getElementById('applicant-review-details');
    const backLink = document.getElementById('back-link');
    const statusSelect = document.getElementById('app-status');
    const commentsTextarea = document.getElementById('reviewer-comments');

    if (!applicationId) {
        document.querySelector('.page-content').innerHTML = '<h1>Error: No Application ID Provided</h1>';
        return;
    }

    backLink.href = `ViewDocuments.php?id=${applicationId}`;

    // --- Configuration ---
    const gradingTooltip = "For Engineering and Programs with Board Examination - Must have a final grade of 80% or above in Math, Science, and English subjects during Grade 10 and Grade 11 (First and Second Semester).<br><br>For the Bachelor of Secondary Education major in Mathematics / Science - No grade below 85%";
    const stemTooltip = "Engineering programs require a STEM Track";

    const structureConfig = [
        {
            documentName: "Certificate of Enrollment",
            checks: [
                { label: "Blurred File Detection", apiKey: "filter_blurred" },
                { label: "Cropped File Detection", apiKey: "filter_cropped" },
                { label: "File Size Check", apiKey: "filter_file_size" }
            ]
        },
        {
            documentName: "Grades Form 1",
            checks: [
                { label: "Blurred File Detection", apiKey: "filter_blurred" },
                { label: "Cropped File Detection", apiKey: "filter_cropped" },
                { label: "File Size Check", apiKey: "filter_file_size" },
                { label: "Program-Specific Screening", apiKey: "program_specific_screening", tooltip: stemTooltip },
                { label: "Grade Requirements Screening", apiKey: "grade_requirements_screening", tooltip: gradingTooltip },
                { label: "Autofill Completeness", apiKey: "check_autofill_completeness" }
            ]
        },
        {
            documentName: "JHS Form 137",
            checks: [
                { label: "Blurred File Detection", apiKey: "filter_blurred" },
                { label: "Cropped File Detection", apiKey: "filter_cropped" },
                { label: "File Size Check", apiKey: "filter_file_size" },
                { label: "Grade Requirements Screening", apiKey: "grade_requirements_screening", tooltip: gradingTooltip }
            ]
        },
        {
            documentName: "SHS Form 137",
            checks: [
                { label: "Blurred File Detection", apiKey: "filter_blurred" },
                { label: "Cropped File Detection", apiKey: "filter_cropped" },
                { label: "File Size Check", apiKey: "filter_file_size" },
                { label: "Program-Specific Screening", apiKey: "program_specific_screening", tooltip: stemTooltip },
                { label: "Grade Requirements Screening", apiKey: "grade_requirements_screening", tooltip: gradingTooltip }
            ]
        }
    ];

    // --- Helpers ---
    const createStatusSpan = (status, isParent = false) => {
        if (!status || status === 'N/A') return '<span class="status-na">N/A</span>';
        const s = status.toLowerCase();
        
        if (isParent) {
            if (s === 'pass' || s === 'approved') return '<span class="status-approved">Approved</span>';
            if (s === 'fail' || s === 'rejected') return '<span class="status-rejected">Rejected</span>';
            return '<span class="status-pending">Pending</span>';
        } else {
            if (s === 'pass') return '<span class="status-pass">Pass</span>';
            if (s === 'fail') return '<span class="status-fail">Fail</span>';
            return '<span class="status-pending">Pending</span>';
        }
    };

    const createTooltipHTML = (text) => {
        if (!text) return '';
        return `<span class="info-icon">i<span class="tooltip-text">${text}</span></span>`;
    };

    // --- Document Status Logic ---
    const updateDocumentStatus = (docName) => {
        const selects = document.querySelectorAll(`select[data-parent-doc="${docName}"]`);
        let hasFail = false;
        let hasPending = false;
        
        selects.forEach(sel => {
            if (sel.value === 'Fail') hasFail = true;
            if (sel.value === 'Pending') hasPending = true;
        });

        const parentStatusCell = document.getElementById(`status-cell-${docName}`);
        if(!parentStatusCell) return;
        
        if (hasFail) {
            parentStatusCell.innerHTML = createStatusSpan('Rejected', true);
        } else if (hasPending) {
            parentStatusCell.innerHTML = createStatusSpan('Pending', true);
        } else {
            parentStatusCell.innerHTML = createStatusSpan('Approved', true);
        }
    };

    // --- Main Fetch ---
    fetch(`api/get_evaluation_data.php?id=${applicationId}`)
        .then(r => r.text().then(t => { try { return JSON.parse(t) } catch(e){ throw new Error("Server JSON Error: " + t.substring(0,50)) }}))
        .then(data => {
            if (data.error) throw new Error(data.message);
            if (!data.details) throw new Error("Applicant not found");

            applicantDetailsP.textContent = `Reviewing: ${data.details.student_name} (${data.details.application_no}) for ${data.details.choice1_program}`;
            checklistTbody.innerHTML = '';

            structureConfig.forEach((section) => {
                
                // 1. Parent Row
                const docRow = document.createElement('tr');
                docRow.className = 'row-document-header';
                const statusCellId = `status-cell-${section.documentName}`;

                docRow.innerHTML = `
                    <td>${section.documentName}</td>
                    <td></td> 
                    <td id="${statusCellId}">${createStatusSpan('Pending', true)}</td>
                `;
                checklistTbody.appendChild(docRow);

                // 2. Child Rows
                section.checks.forEach((check) => {
                    const checkRow = document.createElement('tr');
                    checkRow.className = 'row-sub-check';
                    
                    // --- NEW LOGIC FOR AI RESULTS ---
                    // Access format: data.ai_results['DocumentName']['columnName']
                    let aiValue = 'N/A';
                    if (data.ai_results && data.ai_results[section.documentName]) {
                        aiValue = data.ai_results[section.documentName][check.apiKey] || 'N/A';
                    }

                    // Determine Saved or Default Human Status
                    const uniqueKey = `${section.documentName}::${check.label}`;
                    let savedValue = data.human_checklist[uniqueKey];

                    if (!savedValue) {
                        // Auto-select based on AI result
                        if (aiValue === 'Pass') savedValue = 'Pass';
                        else if (aiValue === 'Fail') savedValue = 'Fail';
                        else savedValue = 'Pending';
                    }

                    checkRow.innerHTML = `
                        <td>${check.label} ${createTooltipHTML(check.tooltip)}</td>
                        <td>${createStatusSpan(aiValue)}</td>
                        <td>
                            <select class="human-eval-select" 
                                    data-parent-doc="${section.documentName}" 
                                    data-unique-key="${uniqueKey}">
                                <option value="Pending" ${savedValue === 'Pending' ? 'selected' : ''}>Pending</option>
                                <option value="Pass" ${savedValue === 'Pass' ? 'selected' : ''}>Pass</option>
                                <option value="Fail" ${savedValue === 'Fail' ? 'selected' : ''}>Fail</option>
                            </select>
                        </td>
                    `;
                    checklistTbody.appendChild(checkRow);
                });

                // Calculate initial status
                updateDocumentStatus(section.documentName);
            });

            // Add Listeners
            document.querySelectorAll('.human-eval-select').forEach(select => {
                select.addEventListener('change', (e) => {
                    updateDocumentStatus(e.target.dataset.parentDoc);
                });
            });
        })
        .catch(err => {
            console.error(err);
            checklistTbody.innerHTML = `<tr><td colspan="3" style="color:red; text-align:center;">Error: ${err.message}</td></tr>`;
        });

    // --- Submit Logic ---
    evalForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const checklistData = {};
        document.querySelectorAll('.human-eval-select').forEach(s => {
            checklistData[s.dataset.uniqueKey] = s.value;
        });

        const btn = evalForm.querySelector('.btn-submit-evaluation');
        btn.disabled = true;
        btn.textContent = 'Submitting...';

        fetch('api/submit_evaluation.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                application_id: applicationId,
                checklist: checklistData,
                final_status: statusSelect.value,
                comments: commentsTextarea.value
            })
        })
        .then(r => r.json())
        .then(res => {
            if(res.success) {
                alert('Success!');
                window.location.href = `Applicant-Details.php?id=${applicationId}`;
            } else {
                alert('Error: ' + res.message);
                btn.disabled = false;
                btn.textContent = 'Submit Final Evaluation';
            }
        })
        .catch(err => {
            alert('Error: ' + err.message);
            btn.disabled = false;
        });
    });
});