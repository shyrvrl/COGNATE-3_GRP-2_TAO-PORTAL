// js/script.js

/*
  Combined global script.js
  It works WITH componentLoader.js
*/

// --- 1. DEFINE OUR INTERACTIVITY FUNCTIONS ---

/**
 * Finds the correct sidebar link and adds the 'active' class based on the current page's URL.
 * This handles top-level navigation as well as nested pages (like Applicant Details under Applicants).
 */
function initSidebarHighlighting() {
    const allLinks = document.querySelectorAll('.nav-item');
    allLinks.forEach(item => {
        item.classList.remove('active');
    });

    // Get the current URL path (e.g., /Applicants.html or /Applicant-Details.html)
    const path = window.location.pathname;
    
    // Determine which top-level module the current page belongs to
    let activeModuleId = null;

    if (path.includes('Dashboard.html') || path.includes('DB-Notices-Archive.html')) {
        activeModuleId = '#nav-dashboard';
    } 
    // The following pages belong to the 'Applicants' module
    else if (path.includes('Applicants.html') || 
             path.includes('Applicant-Details.html') ||
             path.includes('ViewDocuments.html') ||
             path.includes('StartEvaluation.html')) {
        activeModuleId = '#nav-applicants';
    } 
    else if (path.includes('InterviewStatus.html')) {
        activeModuleId = '#nav-interview';
    } 
    // The following pages belong to the 'Settings' module
    else if (path.includes('Settings.html') || path.includes('StaffList.html')) {
        activeModuleId = '#nav-settings';
    }

    // Apply the active class to the determined module link
    if (activeModuleId) {
        document.querySelector(activeModuleId)?.classList.add('active');
    }
}

/**
 * Adds the show/hide logic to the password toggle icon.
 * (Working password toggle)
 */
function initPasswordToggle() {
    const eyeOpenIcon = "assets/eye-open.png";
    const eyeSlashIcon = "assets/eye-slash.png";
    const togglePassword = document.querySelector('#toggle-password');
    const password = document.querySelector('#password');

    if (togglePassword && password) {
        togglePassword.addEventListener('click', function () {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            if (type === 'password') {
                togglePassword.src = eyeOpenIcon;
                togglePassword.alt = "Show password";
            } else {
                togglePassword.src = eyeSlashIcon;
                togglePassword.alt = "Hide password";
            }
        });
    }
}

/**
 * Adds the click event listeners for the tab navigation.
 * (Working tab script)
 */
function initTabSwitching() {
    const tabNav = document.querySelector('.tab-nav');
    if (tabNav) {
        const tabLinks = tabNav.querySelectorAll('.tab-link');
        const tabContents = document.querySelectorAll('.tab-content');
        tabNav.addEventListener('click', (e) => {
            const clickedLink = e.target.closest('.tab-link');
            if (!clickedLink) return;
            e.preventDefault(); 
            tabLinks.forEach(link => link.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));
            clickedLink.classList.add('active');
            const tabId = clickedLink.dataset.tab;
            const activeContent = document.getElementById(tabId);
            if (activeContent) {
                activeContent.classList.add('active');
            }
        });
    }
}

/**
 * (NEW FUNCTION FOR MODAL)
 * Adds event listeners for the filter modal popup.
 */
function initFilterModal() {
    const filterForm = document.querySelector('#filter-form');
    const modal = document.querySelector('#filter-modal');
    const closeModalBtn = document.querySelector('#close-modal-btn');

    // Check if all the modal elements exist on the page
    if (filterForm && modal && closeModalBtn) {
        
        // Show the modal when "Apply Filters" is clicked
        filterForm.addEventListener('submit', (e) => {
            e.preventDefault(); // Stop the page from reloading
            modal.classList.remove('hidden');
        });

        // Hide the modal when "Close" is clicked
        closeModalBtn.addEventListener('click', () => {
            modal.classList.add('hidden');
        });

        // Also hide it if they click on the dark overlay
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.classList.add('hidden');
            }
        });
    }
}


// --- 2. RUN EVERYTHING AFTER THE PAGE LOADS ---
document.addEventListener("DOMContentLoaded", () => {
    
    console.log("TAO Portal script.js is loaded!");
    
    // Run these scripts immediately. They are safe.
    initPasswordToggle();
    initTabSwitching();
    initFilterModal(); 
    
    
    // --- 3. THE WORKING SIDEBAR FIX (Untouched) ---
    // We wait for the sidebar to be loaded by componentLoader.js
    const sidebarPlaceholder = document.getElementById('app-sidebar');

    if (!sidebarPlaceholder) {
        return; // This is the login page or a page without a sidebar
    }

    // Create an observer to "watch" the placeholder
    const observer = new MutationObserver((mutationsList, obs) => {
        for(const mutation of mutationsList) {
            if (mutation.type === 'childList') {
                if (document.querySelector('.nav-links')) {
                    // Sidebar is loaded! Run our highlighting function.
                    initSidebarHighlighting();
                    // We're done, stop watching.
                    obs.disconnect();
                    return;
                }
            }
        }
    });

    // Start watching the placeholder for new children
    observer.observe(sidebarPlaceholder, { childList: true });
});