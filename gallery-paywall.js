/**
 * Gallery Paywall Manager
 * Controls access to gallery content based on purchase status
 */

class GalleryPaywall {
    constructor() {
        this.hasAccess = false;
        this.checkingAccess = false;
        this.initialized = false;
    }

    /**
     * Initialize the gallery paywall
     */
    async init() {
        if (this.initialized) return;

        // Wait for auth manager to be ready
        await this.waitForAuth();

        // Check if user is authenticated
        if (!authManager.isAuthenticated()) {
            this.showAuthModal();
            this.lockGallery();
            this.initialized = true;
            return;
        }

        // Check if user has gallery access
        await this.checkAccess();

        if (!this.hasAccess) {
            this.showPaywall();
            this.lockGallery();
        } else {
            this.unlockGallery();
        }

        this.setupEventListeners();
        this.initialized = true;
    }

    /**
     * Wait for auth manager to be initialized
     */
    async waitForAuth() {
        return new Promise((resolve) => {
            if (typeof authManager !== 'undefined' && authManager.initialized) {
                resolve();
            } else {
                window.addEventListener('authStateChanged', () => resolve(), { once: true });
                // Timeout fallback
                setTimeout(resolve, 2000);
            }
        });
    }

    /**
     * Check if user has gallery access
     */
    async checkAccess() {
        if (this.checkingAccess) return this.hasAccess;
        this.checkingAccess = true;

        try {
            const response = await fetch('/user-api.php?action=check-gallery-access');
            const data = await response.json();
            this.hasAccess = data.hasAccess;
            this.checkingAccess = false;
            return this.hasAccess;
        } catch (error) {
            console.error('Access check failed:', error);
            this.checkingAccess = false;
            return false;
        }
    }

    /**
     * Show authentication modal
     */
    showAuthModal() {
        if (typeof authManager !== 'undefined') {
            authManager.showAuthModal('login');
        } else {
            const modal = document.getElementById('auth-modal');
            if (modal) {
                modal.classList.remove('hidden');
            }
        }
    }

    /**
     * Show paywall modal
     */
    showPaywall() {
        const modal = document.getElementById('paywall-modal');
        if (modal) {
            modal.classList.remove('hidden');
        }
    }

    /**
     * Hide paywall modal
     */
    hidePaywall() {
        const modal = document.getElementById('paywall-modal');
        if (modal) {
            modal.classList.add('hidden');
        }
    }

    /**
     * Lock gallery content (blur effect)
     */
    lockGallery() {
        const gallerySection = document.getElementById('gallery');
        if (gallerySection) {
            gallerySection.style.filter = 'blur(20px)';
            gallerySection.style.pointerEvents = 'none';
            gallerySection.style.userSelect = 'none';
        }
    }

    /**
     * Unlock gallery content
     */
    unlockGallery() {
        const gallerySection = document.getElementById('gallery');
        if (gallerySection) {
            gallerySection.style.filter = 'none';
            gallerySection.style.pointerEvents = 'auto';
            gallerySection.style.userSelect = 'auto';
        }

        // Hide paywall modal
        this.hidePaywall();

        // Load gallery data if not already loaded
        if (typeof loadGalleryData === 'function') {
            loadGalleryData();
        }
    }

    /**
     * Purchase gallery access via Stripe
     */
    async purchaseAccess() {
        if (!authManager.isAuthenticated()) {
            this.showAuthModal();
            return;
        }

        const purchaseBtn = document.getElementById('purchase-gallery-btn');
        if (purchaseBtn) {
            purchaseBtn.disabled = true;
            purchaseBtn.textContent = 'Processing...';
        }

        try {
            const response = await fetch('/payment-api.php?action=create-gallery-checkout', {
                method: 'POST'
            });

            const data = await response.json();

            if (data.success) {
                // Redirect to Stripe Checkout
                window.location.href = data.url;
            } else {
                alert('Error: ' + data.message);
                if (purchaseBtn) {
                    purchaseBtn.disabled = false;
                    purchaseBtn.textContent = 'Purchase Gallery Access';
                }
            }
        } catch (error) {
            console.error('Purchase error:', error);
            alert('Failed to initiate checkout. Please try again.');
            if (purchaseBtn) {
                purchaseBtn.disabled = false;
                purchaseBtn.textContent = 'Purchase Gallery Access';
            }
        }
    }

    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // Purchase button
        const purchaseBtn = document.getElementById('purchase-gallery-btn');
        if (purchaseBtn) {
            purchaseBtn.addEventListener('click', () => this.purchaseAccess());
        }

        // Paywall modal close button
        const paywallClose = document.querySelector('#paywall-modal .modal-close');
        if (paywallClose) {
            paywallClose.addEventListener('click', () => {
                // Don't allow closing if no access
                if (!this.hasAccess) {
                    alert('Gallery access required to view content');
                } else {
                    this.hidePaywall();
                }
            });
        }

        // Listen for auth state changes
        window.addEventListener('authStateChanged', async (e) => {
            if (e.detail.authenticated) {
                // User just logged in, check access
                await this.checkAccess();
                if (this.hasAccess) {
                    this.unlockGallery();
                } else {
                    this.showPaywall();
                }
            } else {
                // User logged out
                this.hasAccess = false;
                this.showAuthModal();
                this.lockGallery();
            }
        });

        // Check for purchase success/cancel in URL
        this.handlePurchaseRedirect();
    }

    /**
     * Handle redirect after Stripe Checkout
     */
    handlePurchaseRedirect() {
        const urlParams = new URLSearchParams(window.location.search);
        const purchaseStatus = urlParams.get('purchase');

        if (purchaseStatus === 'success') {
            this.showPurchaseSuccess();
            // Remove query param from URL
            window.history.replaceState({}, document.title, window.location.pathname);
            // Recheck access (webhook may have processed)
            setTimeout(async () => {
                await this.checkAccess();
                if (this.hasAccess) {
                    this.unlockGallery();
                }
            }, 2000);
        } else if (purchaseStatus === 'cancelled') {
            this.showPurchaseCancelled();
            // Remove query param from URL
            window.history.replaceState({}, document.title, window.location.pathname);
        }
    }

    /**
     * Show purchase success message
     */
    showPurchaseSuccess() {
        const message = document.createElement('div');
        message.className = 'purchase-success-message';
        message.innerHTML = `
            <div class="success-content">
                <h3>✓ Purchase Successful!</h3>
                <p>Thank you for your purchase. You now have lifetime access to the gallery.</p>
            </div>
        `;
        document.body.appendChild(message);

        setTimeout(() => {
            message.style.opacity = '0';
            setTimeout(() => message.remove(), 500);
        }, 5000);
    }

    /**
     * Show purchase cancelled message
     */
    showPurchaseCancelled() {
        const message = document.createElement('div');
        message.className = 'purchase-cancelled-message';
        message.innerHTML = `
            <div class="cancelled-content">
                <h3>Purchase Cancelled</h3>
                <p>Your purchase was cancelled. Gallery access is still required to view content.</p>
            </div>
        `;
        document.body.appendChild(message);

        setTimeout(() => {
            message.style.opacity = '0';
            setTimeout(() => message.remove(), 500);
        }, 4000);
    }
}

// Create global instance
const galleryPaywall = new GalleryPaywall();

// Initialize on gallery page only
if (document.getElementById('gallery')) {
    // Wait for DOM and auth to be ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => galleryPaywall.init(), 100);
        });
    } else {
        setTimeout(() => galleryPaywall.init(), 100);
    }
}
