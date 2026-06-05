/**
 * Modal Utilities
 * Provides consistent modal handling across all pages
 */

/**
 * Open a modal by ID
 * @param {string} modalId - The ID of the modal element
 */
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('hidden');
        // Prevent body scroll when modal is open
        document.body.style.overflow = 'hidden';
    }
}

/**
 * Close a modal by ID
 * @param {string} modalId - The ID of the modal element
 */
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('hidden');
        // Restore body scroll
        document.body.style.overflow = 'auto';
    }
}

/**
 * Toggle a modal by ID
 * @param {string} modalId - The ID of the modal element
 */
function toggleModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        if (modal.classList.contains('hidden')) {
            openModal(modalId);
        } else {
            closeModal(modalId);
        }
    }
}

/**
 * Close modal when clicking outside the modal content
 * @param {Event} event - The click event
 * @param {string} modalId - The ID of the modal element
 */
function closeModalOnBackdropClick(event, modalId) {
    const modal = document.getElementById(modalId);
    if (event.target === modal) {
        closeModal(modalId);
    }
}

/**
 * Setup backdrop click handler for a modal
 * @param {string} modalId - The ID of the modal element
 */
function setupModalBackdropHandler(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.addEventListener('click', (event) => closeModalOnBackdropClick(event, modalId));
    }
}

/**
 * Close all modals
 */
function closeAllModals() {
    const modals = document.querySelectorAll('[id$="Modal"]');
    modals.forEach(modal => {
        modal.classList.add('hidden');
    });
    document.body.style.overflow = 'auto';
}

/**
 * Initialize all modals with backdrop click handlers
 */
function initializeModals() {
    const modals = document.querySelectorAll('[id$="Modal"]');
    modals.forEach(modal => {
        setupModalBackdropHandler(modal.id);
    });
}

// Initialize modals when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeModals);
} else {
    initializeModals();
}
