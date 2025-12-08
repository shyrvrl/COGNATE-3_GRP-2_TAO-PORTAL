document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const applicationId = urlParams.get('id');
    const docListContainer = document.getElementById('document-list-container');
    const docPreviewContent = document.getElementById('document-preview-content');

    if (!applicationId) {
        document.querySelector('.page-content').innerHTML = '<h1>No Applicant ID Provided</h1>';
        return;
    }

    const getStatusClass = (status) => {
        const s = status ? status.toLowerCase() : '';
        if (s === 'approved' || s === 'pass') return 'status-approved';
        if (s === 'rejected' || s === 'fail') return 'status-rejected';
        return 'status-pending'; // Default (Yellow/Orange)
    };

    // --- Function to show the document preview ---
    const showPreview = (document) => {
        // Use relative path to ensure it works on server and localhost
        const documentUrl = `api/get_document.php?id=${document.id}`;
        
        let previewHtml = '';
        const fileType = document.file_path.split('.').pop().toLowerCase();

        if (['pdf'].includes(fileType)) {
            previewHtml = `<embed src="${documentUrl}" type="application/pdf" width="100%" height="100%">`;
        } else if (['png', 'jpg', 'jpeg', 'gif'].includes(fileType)) {
            previewHtml = `<img src="${documentUrl}" alt="${document.document_type}" style="max-width: 100%; max-height: 100%; object-fit: contain;">`;
        } else {
            previewHtml = `
                <span class="material-icons preview-icon">folder_zip</span>
                <p class="text-center-title">Preview not available</p>
                <p class="text-center-meta">This file type cannot be displayed directly.</p>
                <a href="${documentUrl}" download class="btn btn-primary-green">Download File</a>
            `;
        }
        
        docPreviewContent.innerHTML = previewHtml;
    };


    // --- Fetch all application data ---
    fetch(`api/get_application_details.php?id=${applicationId}`)
        .then(response => response.json())
        .then(data => {
            if(data.error) throw new Error(data.message);

            // --- Populate header info ---
            const details = data.details;
            document.getElementById('applicant-name').textContent = details.student_name;
            document.getElementById('application-no').textContent = details.application_no;
            document.getElementById('program-applied').textContent = details.choice1_program;

            // Update links
            document.getElementById('back-to-details-link').href = `Applicant-Details.php?id=${applicationId}`;
            document.getElementById('start-evaluation-link').href = `Evaluation.php?id=${applicationId}`;

            // --- Configure Evaluation Button ---
            const docListContainer = document.getElementById('document-list-container');
            docListContainer.innerHTML = ''; 

            const evalBtn = document.getElementById('start-evaluation-link');
            const status = data.details.application_status;

            if (status === 'Approved' || status === 'Rejected') {
                evalBtn.textContent = 'Evaluation Finalized';
                evalBtn.classList.add('disabled'); 
                evalBtn.style.pointerEvents = 'none'; // Ensure it's unclickable
            } else if (status === 'For Interview' || status === 'For Approval') {
                evalBtn.textContent = 'Edit Evaluation';
            } else { 
                evalBtn.textContent = 'Start Evaluation';
            }

            // --- Populate document list ---
            if (data.documents && data.documents.length > 0) {
                data.documents.forEach((doc, index) => {
                    const docItem = document.createElement('div');
                    docItem.className = 'document-item';
                    
                    if (index === 0) {
                        docItem.classList.add('active');
                    }

                    // Get color class based on the status merged in PHP
                    const statusClass = getStatusClass(doc.file_status);

                    docItem.innerHTML = `
                        <div class="document-details">
                            <p class="document-title">${doc.document_type}</p>
                            <p class="document-meta">File type: ${doc.file_path.split('.').pop().toUpperCase()}</p>
                        </div>
                        <span class="status-badge ${statusClass}">${doc.file_status}</span>
                    `;
                    
                    docItem.addEventListener('click', () => {
                        document.querySelectorAll('.document-item').forEach(item => item.classList.remove('active'));
                        docItem.classList.add('active');
                        showPreview(doc);
                    });

                    docListContainer.appendChild(docItem);
                });

                // Show first doc
                showPreview(data.documents[0]);

            } else {
                docListContainer.innerHTML = '<div style="padding:20px;">No documents found.</div>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            docListContainer.innerHTML = `<div style="padding:20px; color:red">Error loading data: ${error.message}</div>`;
        });
});