// js/global.js - Handles global tasks like component loading

const loadComponent = (selector, path) => {
    fetch(path)
        .then(response => {
            if (!response.ok) {
                if (document.querySelector(selector)) {
                    console.error(`Status ${response.status}: Failed to load component: ${path}`);
                    document.querySelector(selector).innerHTML = `<div style="padding: 15px; color: red;">Error loading component: ${path}</div>`;
                }
                throw new Error(`Failed to load component: ${path}`);
            }
            return response.text();
        })
        .then(data => {
            const element = document.querySelector(selector);
            if (element) {
                element.innerHTML = data;
            }
        })
        .catch(error => console.error(error));
};

document.addEventListener("DOMContentLoaded", () => {
    
    // Load Header (Used on all pages)
    loadComponent("#app-header", "components/header.html"); 

    // Load Sidebar (Used only on portal pages, will skip gracefully on Login)
    loadComponent("#app-sidebar", "components/sidebar.html");

    console.log("TAO Portal global.js components loaded!");
});