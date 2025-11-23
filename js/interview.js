// Global references to DOM elements
const form = document.querySelector('.scheduling-form');
const statusDisplay = document.getElementById('status-message-display');
const statusText = statusDisplay ? statusDisplay.querySelector('.current-status-text') : null;
const modal = document.getElementById('schedule-success-modal');
const modalDynamicText = modal ? modal.querySelector('#modal-dynamic-text') : null; // Targets the <span> inside the modal
const closeModalBtn = document.getElementById('close-modal-btn');
const dateInput = document.getElementById('date');

/**
 * Basic validation check for the date format (dd/mm/yyyy).
 * @param {string} dateString - The date string from the input.
 * @returns {boolean} True if the format is correct, false otherwise.
 */
function isValidDateFormat(dateString) {
    // Regex to strictly enforce dd/mm/yyyy format
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

    // 1. Validation Check (Checks for empty selections and date validity)
    if (!interviewer || !time || !mode || !date || date === dateInput.placeholder || !isValidDateFormat(date)) {
        console.error('Validation Error: Please ensure all fields are selected and the date is in dd/mm/yyyy format.');
        // Temporary UI feedback on date input
        if (dateInput) {
            dateInput.style.borderColor = 'var(--color-red)';
        }
        setTimeout(() => { 
            if (dateInput) dateInput.style.borderColor = '#ccc'; 
        }, 2000);
        return;
    }

    // 2. Construct the scheduled message
    const displayDate = date.replace(/\//g, '-');
    const scheduledMessage = `Scheduled for ${displayDate} at ${time} (${modeDisplay}). Interviewer: ${interviewer}.`;

    // 3. Update On-Page Status Display (Bottom left)
    if (statusDisplay && statusText) {
        statusText.textContent = `STATUS: Scheduled for ${displayDate} at ${time} (${modeDisplay})`;
        // Show the status container 
        statusDisplay.classList.remove('hidden-status');
    }

    // 4. Update Modal Content and Show Modal
    if (modal && modalDynamicText) {
        modalDynamicText.innerHTML = `The interview is now scheduled for <strong>${displayDate} at ${time} (${modeDisplay})</strong>. The applicant has been automatically notified.`;
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

    // We assume Firebase auth is handled in global.js
});