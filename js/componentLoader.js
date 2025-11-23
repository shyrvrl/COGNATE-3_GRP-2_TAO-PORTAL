/**
 * Loads and inserts HTML content from a given file path into a target element ID.
 * It accepts an optional 'callback' function to run after loading.
 */
function loadComponent(componentFile, targetId, callback) {
    const targetElement = document.getElementById(targetId);
    
    if (!targetElement) {
        return; 
    }

    fetch(componentFile)
        .then(response => {
            if (!response.ok) {
                console.error(`Status ${response.status}: Failed to load ${componentFile}`);
                targetElement.innerHTML = `<div style="color: red;">Error loading ${targetId}</div>`;
                throw new Error(`Failed to load ${componentFile}`);
            }
            return response.text();
        })
        .then(html => {
            targetElement.innerHTML = html;
            // If a callback function was provided, run it now.
            if (typeof callback === 'function') {
                callback();
            }
        })
        .catch(error => {
            console.error("Error during component loading:", error);
        });
}

/**
 * This function will fetch the logged-in user's data
 * and populate the sidebar elements.
 */
function populateUserInfo() {
    fetch('api/get_session_user.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // --- Part 1: Populate Sidebar Info  ---
                const userNameElement = document.getElementById('sidebar-user-name');
                const userRoleElement = document.getElementById('sidebar-user-role');
                if (userNameElement) userNameElement.textContent = data.name;
                if (userRoleElement) userRoleElement.textContent = data.role;

                // --- Part 2: Add Role-Based Body Class ---
                // This is the key to our UI hiding strategy.
                let roleClass = '';
                if (data.role.includes('Admin')) {
                    roleClass = 'user-role-admin';
                } else if (data.role.includes('Evaluator')) {
                    roleClass = 'user-role-evaluator';
                }
                // (You can add more roles here in the future, like 'user-role-interviewer')

                if (roleClass) {
                    document.body.classList.add(roleClass);
                }
            } else {
                console.log("No active user session found.");
            }
        })
        .catch(error => {
            console.error("Error fetching user session data:", error);
        });
}


// Load the reusable components when the page content is fully loaded
document.addEventListener('DOMContentLoaded', () => {
    // 1. Load the Header/Banner (no callback needed)
    loadComponent('components/header.html', 'app-header');

    // 2. Load the Sidebar Navigation
    // This ensures it only runs AFTER the sidebar HTML is on the page.
    loadComponent('components/sidebar.html', 'app-sidebar', populateUserInfo);
});