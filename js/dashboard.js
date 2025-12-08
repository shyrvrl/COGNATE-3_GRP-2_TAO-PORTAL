document.addEventListener('DOMContentLoaded', () => {
    // Helper function to safely update text
    const setText = (id, text) => {
        const element = document.getElementById(id);
        if (element) {
            // Display '0' if the value is null or undefined
            element.textContent = (text !== undefined && text !== null) ? text : '0';
        } else {
            console.warn(`Element with ID '${id}' not found.`);
        }
    };

    // Fetch dashboard data
    fetch('api/get_dashboard_data.php')
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error("API Error:", data.message);
                return;
            }

            // --- 1. Populate Stats ---
            // Top Row
            setText('stat-total-count', data.stats.total_applications);
            setText('stat-approved-count', data.stats.approved);
            setText('stat-rejected-count', data.stats.rejected);
            setText('stat-my-evaluated-count', data.stats.my_evaluated);

            // Bottom Row
            setText('stat-evaluation-count', data.stats.for_evaluation);
            setText('stat-interview-count', data.stats.for_interview);
            setText('stat-approving-count', data.stats.for_approving);

            // --- 2. Populate Notices ---
            const noticesContainer = document.getElementById('notices-list-container');
            if (noticesContainer) {
                noticesContainer.innerHTML = ''; // Clear loading text

                if (data.notices && data.notices.length > 0) {
                    data.notices.forEach(notice => {
                        const noticeDiv = document.createElement('div');
                        // Apply class based on DB type (blue, green, red)
                        noticeDiv.className = `notice-item notice-${notice.notice_type}`;
                        noticeDiv.innerHTML = `
                            <h3>${notice.notice_title}</h3>
                            <p>${notice.notice_content}</p>
                        `;
                        noticesContainer.appendChild(noticeDiv);
                    });
                } else {
                    noticesContainer.innerHTML = '<p style="text-align:center; padding:10px;">No new notices.</p>';
                }
            }
        })
        .catch(error => {
            console.error('Network/Parsing Error:', error);
            document.getElementById('notices-list-container').innerHTML = '<p style="color:red;">Error loading data.</p>';
        });
});