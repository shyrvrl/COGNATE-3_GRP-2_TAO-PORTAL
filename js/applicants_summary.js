// js/applicants_summary.js

document.addEventListener('DOMContentLoaded', () => {

    const summaryContent = document.getElementById('summary-content');
    // Only run on Applicants page
    if (!summaryContent) return;

    // Helper
    const setText = (id, text) => {
        const el = document.getElementById(id);
        if (el) el.textContent = text;
    };

    function loadSummaryData() {
        fetch('api/get_applicants_page_data.php')
            .then(response => response.json())
            .then(data => {
                if (data.error) return console.error(data.error);

                // Populate Stats
                setText('summary-total-apps', data.summary_stats.total_applications);
                setText('summary-admission-rate', data.summary_stats.admission_rate + '%');

                // --- DATE SYNC LOGIC ---
                // retrieved from DB (e.g., "2025-11-30")
                if (data.summary_stats.deadline_date) {
                    calculateDaysLeft(data.summary_stats.deadline_date);
                }

                // Populate Tables...
                populateTables(data);
            })
            .catch(error => console.error('Error:', error));
    }

    function calculateDaysLeft(deadlineString) {
        // deadlineString is 'YYYY-MM-DD' from DB
        
        // 1. Calculate Days
        const deadline = new Date(deadlineString + 'T23:59:59'); 
        const today = new Date();
        const diffTime = deadline - today;
        const diffDays = Math.max(0, Math.ceil(diffTime / (1000 * 60 * 60 * 24)));
        
        // 2. Format the Date 
        // We use the string directly to avoid Timezone issues visually
        const dateParts = deadlineString.split('-'); // [2025, 11, 30]
        const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
        const monthName = months[parseInt(dateParts[1]) - 1];
        const day = parseInt(dateParts[2]);
        const prettyDate = `${monthName} ${day}`; // e.g., "Nov 30"

        // 3. Update DOM
        const daysLeftEl = document.getElementById('summary-days-left');
        const deadlineTextEl = document.getElementById('summary-deadline-text');

        if (daysLeftEl) {
            daysLeftEl.innerHTML = `${diffDays}<span class="value-unit">Days</span>`;
        }
        
        if (deadlineTextEl) {
            // This updates the text to match the settings
            deadlineTextEl.textContent = `Until evaluation deadline (${prettyDate})`;
        }
    }

    function populateTables(data) {
        // Status Table
        const statusTable = document.getElementById('status-summary-tbody');
        if(statusTable) {
            statusTable.innerHTML = '';
            data.status_breakdown.forEach(s => {
                statusTable.innerHTML += `<tr><td>${s.status}</td><td>${s.count}</td><td>${s.percentage}%</td></tr>`;
            });
        }
        // Program Table
        const progTable = document.getElementById('top-programs-tbody');
        if(progTable) {
            progTable.innerHTML = '';
            data.top_programs.forEach(p => {
                const rateClass = p.acceptance_rate >= 90 ? 'rate-green' : (p.acceptance_rate >= 75 ? 'rate-blue' : 'rate-yellow');
                progTable.innerHTML += `<tr><td>${p.program}</td><td>${p.count}</td><td><span class="${rateClass}">${p.acceptance_rate}%</span></td></tr>`;
            });
        }
    }

    loadSummaryData();
});