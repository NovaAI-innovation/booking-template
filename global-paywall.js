/**
 * Global Paywall Manager
 * Controls access to all website content based on authentication and payment status
 */

class GlobalPaywall {
    constructor() {
        this.hasAccess = false;
        this.checkingAccess = false;
        this.initialized = false;
        this.allowedWithoutAuth = []; // Pages that don't require auth (none for now)
    }

    /**
     * Initialize the global paywall
     */
    async init() {
        if (this.initialized) return;

        // Lock content immediately
        this.lockContent();

        // Wait for auth manager to be ready
        await this.waitForAuth();

        // Check if user is authenticated
        if (!authManager.isAuthenticated()) {
            this.showAuthModal();
            this.initialized = true;
            return;
        }

        // Check if user has paid for access
        await this.checkAccess();

        if (!this.hasAccess) {
            this.showPaywall();
        } else {
            this.unlockContent();
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
     * Check if user has paid for access
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
     * Lock all content (blur effect and disable interaction)
     */
    lockContent() {
        // Create a full-screen overlay
        let overlay = document.getElementById('paywall-overlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.id = 'paywall-overlay';
            overlay.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.8);
                backdrop-filter: blur(20px);
                z-index: 9998;
                pointer-events: all;
            `;
            document.body.appendChild(overlay);
        }

        // Lock main content
        const body = document.body;
        body.style.overflow = 'hidden';

        // Lock navigation except logo
        const nav = document.querySelector('.nav');
        if (nav) {
            nav.style.pointerEvents = 'none';
            // Keep logo clickable
            const logo = nav.querySelector('.nav-logo');
            if (logo) {
                logo.style.pointerEvents = 'all';
            }
        }
    }

    /**
     * Unlock all content
     */
    unlockContent() {
        // Remove overlay
        const overlay = document.getElementById('paywall-overlay');
        if (overlay) {
            overlay.remove();
        }

        // Unlock body
        const body = document.body;
        body.style.overflow = '';

        // Unlock navigation
        const nav = document.querySelector('.nav');
        if (nav) {
            nav.style.pointerEvents = '';
        }

        // Hide modals
        this.hidePaywall();

        // Load page-specific content if needed
        this.loadPageContent();
    }

    /**
     * Load page-specific content after unlock
     */
    loadPageContent() {
        // For gallery page, load gallery data
        if (window.location.pathname.includes('gallery.html')) {
            if (typeof loadGalleryData === 'function') {
                loadGalleryData();
            }
        }
    }

    /**
     * Purchase website access via Stripe
     */
    async purchaseAccess() {
        if (!authManager.isAuthenticated()) {
            this.showAuthModal();
            return;
        }

        const purchaseBtn = document.getElementById('purchase-access-btn');
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
                    purchaseBtn.textContent = 'Purchase Access';
                }
            }
        } catch (error) {
            console.error('Purchase error:', error);
            alert('Failed to initiate checkout. Please try again.');
            if (purchaseBtn) {
                purchaseBtn.disabled = false;
                purchaseBtn.textContent = 'Purchase Access';
            }
        }
    }

    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // Purchase button
        const purchaseBtn = document.getElementById('purchase-access-btn');
        if (purchaseBtn) {
            purchaseBtn.addEventListener('click', () => this.purchaseAccess());
        }

        // Paywall modal close button
        const paywallClose = document.querySelector('#paywall-modal .modal-close');
        if (paywallClose) {
            paywallClose.addEventListener('click', () => {
                // Don't allow closing if no access
                if (!this.hasAccess) {
                    alert('Access required to view content');
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
                    this.unlockContent();
                } else {
                    this.showPaywall();
                }
            } else {
                // User logged out
                this.hasAccess = false;
                this.showAuthModal();
                this.lockContent();
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
                    this.unlockContent();
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
        message.style.cssText = `
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(76, 175, 80, 0.95);
            color: white;
            padding: 2rem 3rem;
            border-radius: 12px;
            z-index: 10001;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        `;
        message.innerHTML = `
            <h3 style="margin: 0 0 0.5rem 0; font-size: 1.5rem;">✓ Purchase Successful!</h3>
            <p style="margin: 0;">Thank you for your purchase. You now have full access to the website.</p>
        `;
        document.body.appendChild(message);

        setTimeout(() => {
            message.style.opacity = '0';
            message.style.transition = 'opacity 0.5s';
            setTimeout(() => message.remove(), 500);
        }, 5000);
    }

    /**
     * Show purchase cancelled message
     */
    showPurchaseCancelled() {
        const message = document.createElement('div');
        message.className = 'purchase-cancelled-message';
        message.style.cssText = `
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(244, 67, 54, 0.95);
            color: white;
            padding: 2rem 3rem;
            border-radius: 12px;
            z-index: 10001;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        `;
        message.innerHTML = `
            <h3 style="margin: 0 0 0.5rem 0; font-size: 1.5rem;">Purchase Cancelled</h3>
            <p style="margin: 0;">Your purchase was cancelled. Access is still required to view content.</p>
        `;
        document.body.appendChild(message);

        setTimeout(() => {
            message.style.opacity = '0';
            message.style.transition = 'opacity 0.5s';
            setTimeout(() => message.remove(), 500);
        }, 4000);
    }
}

// Create global instance
const globalPaywall = new GlobalPaywall();

// Auto-initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        setTimeout(() => globalPaywall.init(), 100);
    });
} else {
    setTimeout(() => globalPaywall.init(), 100);
}
