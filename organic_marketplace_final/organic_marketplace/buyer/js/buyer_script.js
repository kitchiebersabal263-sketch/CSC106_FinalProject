// Buyer Dashboard Scripts

document.addEventListener('DOMContentLoaded', function() {
    console.log('Buyer dashboard loaded');

    // Setup sidebar mobile toggle only
    setupSidebars();

    // Filter buttons functionality
    const filterButtons = document.querySelectorAll('.filter-btn');
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Remove active class from all buttons
            filterButtons.forEach(btn => btn.classList.remove('active'));
            // Add active class to clicked button
            this.classList.add('active');
            
            // Get filter type
            const filterType = this.getAttribute('data-filter');
            // If data-filter exists, perform client-side filtering; otherwise navigation will handle it.
            if (filterType) {
                filterProducts(filterType);
            }
        });
    });

    // Filter panel toggle
    const filterToggle = document.getElementById('filter-toggle');
    const filterPanel = document.getElementById('filter-panel');
    if (filterToggle && filterPanel) {
        filterToggle.addEventListener('click', function() {
            filterPanel.classList.toggle('visible');
            // Update aria-expanded for accessibility
            const expanded = filterPanel.classList.contains('visible');
            filterToggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        });
    }
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

function filterProducts(filterType) {
    const productCards = document.querySelectorAll('.product-card');
    
    productCards.forEach(card => {
        if (filterType === 'all') {
            card.style.display = 'block';
        } else {
            const category = card.getAttribute('data-category');
            if (category === filterType) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        }
    });
}

// Add to cart functionality
function addToCart(productId) {
    // This will be handled by PHP, but we can add confirmation here
    return confirm('Add this product to cart?');
}

