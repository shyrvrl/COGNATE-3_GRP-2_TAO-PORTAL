// js/componentLoader.js

/**
 * Loads and inserts HTML content from a given file path into a target element ID.
 */
function loadComponent(componentFile, targetId) {
    const targetElement = document.getElementById(targetId);
    
    // Check if the placeholder element exists on the current page
    if (!targetElement) {
        return; 
    }

    fetch(componentFile)
        .then(response => {
            if (!response.ok) {
                console.error(`Status ${response.status}: Failed to load ${componentFile}`);
                targetElement.innerHTML = `<div style="padding: 15px; color: red; font-weight: bold;">Error: Could not load ${componentFile}</div>`;
                throw new Error(`Failed to load ${componentFile}`);
            }
            return response.text();
        })
        .then(html => {
            targetElement.innerHTML = html;
        })
        .catch(error => {
            console.error("Error during component loading:", error);
        });
}

// Load the reusable components when the page content is fully loaded
document.addEventListener('DOMContentLoaded', () => {
    // 1. Load the Header/Banner (Used on Login and Portal Pages)
    loadComponent('components/header.html', 'app-header');

    // 2. Load the Sidebar Navigation (Used only on Portal Pages)
    loadComponent('components/sidebar.html', 'app-sidebar');
});