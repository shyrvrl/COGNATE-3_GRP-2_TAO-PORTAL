/* This is js/script.js */

// This function loads reusable HTML components
const loadComponent = (selector, path) => {
    fetch(path)
        .then(response => {
            if (!response.ok) {
                throw new Error(`Failed to load component: ${path}`);
            }
            return response.text();
        })
        .then(data => {
            const element = document.querySelector(selector);
            if (element) {
                element.innerHTML = data;
            } else {
                console.warn(`Selector "${selector}" not found for component "${path}".`);
            }
        })
        .catch(error => console.error(error));
};

// --- Wait for the page to be fully loaded before running our script ---
document.addEventListener("DOMContentLoaded", () => {
    
    // --- Load all our reusable components ---
    // This looks for an element with id="header-placeholder" and injects header.html into it
    loadComponent("#header-placeholder", "components/header.html");

    // This is a test to make sure our script.js is loaded
    console.log("TAO Portal script.js is loaded!");

});