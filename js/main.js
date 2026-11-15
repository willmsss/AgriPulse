/**
 * AgriPulse - Main JavaScript File
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Initialize Bootstrap Tooltips globally
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // 2. Generic Delete Modal Handler
    // Automatically wires up delete confirmation modals if they follow standard naming conventions
    const deleteModal = document.getElementById('deleteModal');
    if (deleteModal) {
        deleteModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            if (!button) return;
            
            const recordId = button.getAttribute('data-id');
            const recordName = button.getAttribute('data-name');
            
            // Find any potential name display element inside the delete modal
            const nameElement = deleteModal.querySelector('#deleteFarmerName, #deleteFarmName, #deleteCropName, #deletePlantingName, #deleteHarvestName');
            if (nameElement && recordName) {
                nameElement.textContent = recordName;
            }
            
            // Dynamically set the delete action URL based on the current page filename
            const confirmBtn = deleteModal.querySelector('#confirmDeleteBtn');
            if (confirmBtn && recordId) {
                const currentPage = window.location.pathname.split('/').pop();
                confirmBtn.setAttribute('href', `${currentPage}?delete_id=${recordId}`);
            }
        });
    }

    // 3. Auto-dismiss alerts after 5 seconds (optional helper)
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(function (alert) {
        setTimeout(function () {
            let bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
});