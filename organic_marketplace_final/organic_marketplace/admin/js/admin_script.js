// Admin Dashboard Scripts

// Confirm delete actions
function confirmDelete(message) {
    return confirm(message || 'Are you sure you want to delete this item?');
}

// Animate bar chart on page load
document.addEventListener('DOMContentLoaded', function() {
    console.log('Admin dashboard loaded');
    
    // Setup sidebar mobile toggle only
    setupSidebars();
    
    // Animate bar chart bars
    const barFills = document.querySelectorAll('.bar-fill');
    barFills.forEach((bar, index) => {
        // Get target width from data attribute
        const targetWidth = bar.getAttribute('data-width');
        
        // Set initial width to 0
        bar.style.width = '0%';
        bar.style.transition = 'none';
        
        // Animate to target width with delay
        setTimeout(() => {
            bar.style.transition = 'width 1.2s ease-out';
            bar.style.width = targetWidth + '%';
        }, index * 200);
    });
    
    // Add hover effect to stat cards
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-8px)';
        });
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
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

