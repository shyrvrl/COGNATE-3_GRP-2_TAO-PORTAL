/*
  This is the new, combined global script.js
  It works WITH componentLoader.js
*/

// --- 1. DEFINE OUR INTERACTIVITY FUNCTIONS ---

/**
 * Finds the correct sidebar link and adds the 'active' class.
 * (This is your working sidebar fix)
 */
function initSidebarHighlighting() {
    const dashboardLink = document.querySelector('#nav-dashboard');
    if (!dashboardLink) {
        return; 
    }
    const allLinks = document.querySelectorAll('.nav-item');
    allLinks.forEach(item => {
        item.classList.remove('active');
    });
    if (document.body.classList.contains('page-dashboard')) {
        dashboardLink.classList.add('active');
    } else if (document.body.classList.contains('page-applicants')) {
        document.querySelector('#nav-applicants')?.classList.add('active');
    } else if (document.body.classList.contains('page-interview')) {
        document.querySelector('#nav-interview')?.classList.add('active');
    }
}

/**
 * Adds the show/hide logic to the password toggle icon.
 * (This is your working password toggle)
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
 * (This is your working tab script)
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
 * (THIS IS THE NEW FUNCTION FOR YOUR MODAL)
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
    initFilterModal(); // <-- THIS IS THE ONLY NEW LINE IN THIS BLOCK
    
    
    // --- 3. THE WORKING SIDEBAR FIX (Untouched) ---
    // We wait for the sidebar to be loaded by componentLoader.js
    const sidebarPlaceholder = document.getElementById('app-sidebar');

    if (!sidebarPlaceholder) {
        return; // This is the login page
    }

    // Create an observer to "watch" the placeholder
    const observer = new MutationObserver((mutationsList, obs) => {
        for(const mutation of mutationsList) {
            if (mutation.type === 'childList') {
                if (document.querySelector('.nav-item')) {
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