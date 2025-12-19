// Farmer Dashboard Scripts

document.addEventListener('DOMContentLoaded', function() {
    console.log('Farmer dashboard loaded');
    
    // Setup sidebar mobile toggle only
    setupSidebars();
    
    // Confirm delete actions
    const deleteButtons = document.querySelectorAll('.btn-danger');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to delete this product?')) {
                e.preventDefault();
            }
        });
    });
});

/* Setup sidebar mobile toggle only (no collapse) */
function setupSidebars() {
    const overlay = document.getElementById('mobile-overlay');
    document.querySelectorAll('.mobile-menu-toggle').forEach(toggle => {
        const sidebar = document.querySelector('[id$="-sidebar"]');
        if (!sidebar) return;
        toggle.addEventListener('click', function() {
            sidebar.classList.toggle('mobile-open');
            if (overlay) overlay.classList.toggle('active');
        });
    });

    if (overlay) {
        overlay.addEventListener('click', function() {
            const sidebar = document.querySelector('[id$="-sidebar"]');
            if (sidebar) sidebar.classList.remove('mobile-open');
            overlay.classList.remove('active');
        });
    }
}

