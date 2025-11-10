// Global references to DOM elements
const form = document.querySelector('.scheduling-form');
const statusDisplay = document.getElementById('status-message-display');
const statusText = statusDisplay ? statusDisplay.querySelector('.current-status-text') : null;
const modal = document.getElementById('schedule-success-modal');
const modalText = modal ? modal.querySelector('.modal-content p') : null;
const closeModalBtn = document.getElementById('close-modal-btn');
const dateInput = document.getElementById('date');

/**
 * Basic validation check for the date format (dd/mm/yyyy).
 * In a production environment, a date picker library would handle this more robustly.
 * @param {string} dateString - The date string from the input.
 * @returns {boolean} True if the format is correct, false otherwise.
 */
function isValidDateFormat(dateString) {
    // Regex for dd/mm/yyyy format
    return /^\d{2}\/\d{2}\/\d{4}$/.test(dateString);
}

/**
 * Handles the form submission event, updates the UI, and shows the success modal.
 * @param {Event} event - The form submission event.
 */
function handleFormSubmit(event) {
    event.preventDefault();

    const interviewer = document.getElementById('interviewer').value;
    const time = document.getElementById('time').value;
    const date = dateInput.value;
    const mode = document.getElementById('mode').value;
    const modeDisplay = mode === 'virtual' ? 'Online' : 'In-Person';

    // 1. Validation Check
    if (!interviewer || !time || !date || !mode || !isValidDateFormat(date)) {
        // Display a client-side error message or notification instead of alert()
        console.error('Validation Error: Please ensure all fields are selected and the date is in dd/mm/yyyy format.');
        // TODO: Implement a visible custom error message display here
        return;
    }

    // 2. Construct the scheduled message
    // We replace / with - for a slightly cleaner display, but keep the input logic simple.
    const displayDate = date.replace(/\//g, '-');
    const scheduledMessage = `Scheduled for ${displayDate} at ${time} (${modeDisplay}). Interviewer: ${interviewer}. The applicant has been automatically notified.`;

    // 3. Update On-Page Status Display (Bottom left)
    if (statusDisplay && statusText) {
        statusText.textContent = `STATUS: Scheduled for ${displayDate} at ${time} (${modeDisplay})`;
        // Remove the class that hides the container
        statusDisplay.classList.remove('hidden-status');
        // Ensure visibility in case initial CSS set display: none
        statusDisplay.style.display = 'flex';
    }

    // 4. Update Modal Content and Show Modal
    if (modal && modalText) {
        modalText.innerHTML = `The interview is now scheduled for **${displayDate} at ${time} (${modeDisplay})**. The applicant has been automatically notified.`;
        modal.classList.remove('hidden');
    }
}

/**
 * Hides the success modal.
 */
function closeModal() {
    if (modal) {
        modal.classList.add('hidden');
    }
}

// Initialization and Event Listener Setup
document.addEventListener('DOMContentLoaded', () => {
    // Check for necessary elements before attaching listeners
    if (form) {
        form.addEventListener('submit', handleFormSubmit);
    }

    if (closeModalBtn) {
        closeModalBtn.addEventListener('click', closeModal);
    }

    // Add functionality to the calendar icon to focus the date input
    const calendarIcon = document.querySelector('.calendar-icon');
    if (calendarIcon && dateInput) {
        calendarIcon.addEventListener('click', () => {
            dateInput.focus();
        });
    }

    // Since this is a collaborative environment requiring Firebase authentication,
    // we assume the necessary initialization (like signing in with __initial_auth_token)
    // is handled in js/global.js as per best practices.
});