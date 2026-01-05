/**
 * Tipping System Manager
 * Handles tip sending and recent tippers display
 */

class TippingManager {
    constructor() {
        this.initialized = false;
    }

    /**
     * Initialize the tipping manager
     */
    async init() {
        if (this.initialized) return;

        this.setupEventListeners();
        await this.loadRecentTippers();
        this.handleTipRedirect();
        this.initialized = true;
    }

    /**
     * Send a tip via Stripe Checkout
     */
    async sendTip(amount, message = '') {
        if (!authManager.isAuthenticated()) {
            alert('Please login to send a tip');
            authManager.showAuthModal('login');
            return;
        }

        // Validate amount
        if (amount < 1) {
            alert('Minimum tip amount is $1.00');
            return;
        }

        if (amount > 1000) {
            alert('Maximum tip amount is $1,000.00');
            return;
        }

        const tipBtn = document.querySelector('#tip-form button[type="submit"]');
        if (tipBtn) {
            tipBtn.disabled = true;
            tipBtn.textContent = 'Processing...';
        }

        try {
            const response = await fetch('/payment-api.php?action=create-tip-checkout', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    amount: Math.round(amount * 100), // Convert to cents
                    message: message
                })
            });

            const data = await response.json();

            if (data.success) {
                // Redirect to Stripe Checkout
                window.location.href = data.url;
            } else {
                alert('Error: ' + data.message);
                if (tipBtn) {
                    tipBtn.disabled = false;
                    tipBtn.textContent = 'Send Tip';
                }
            }
        } catch (error) {
            console.error('Tip error:', error);
            alert('Failed to process tip. Please try again.');
            if (tipBtn) {
                tipBtn.disabled = false;
                tipBtn.textContent = 'Send Tip';
            }
        }
    }

    /**
     * Load and display recent tippers
     */
    async loadRecentTippers() {
        const container = document.getElementById('recent-tippers-list');
        if (!container) return;

        try {
            const response = await fetch('/payment-api.php?action=get-recent-tippers&limit=10');
            const data = await response.json();

            if (data.success) {
                this.displayTippers(data.tippers);
            } else {
                container.innerHTML = '<p class="error-message">Failed to load tippers</p>';
            }
        } catch (error) {
            console.error('Failed to load tippers:', error);
            container.innerHTML = '<p class="error-message">Failed to load tippers</p>';
        }
    }

    /**
     * Display tippers in the UI
     */
    displayTippers(tippers) {
        const container = document.getElementById('recent-tippers-list');
        if (!container) return;

        if (tippers.length === 0) {
            container.innerHTML = '<p class="no-tippers">Be the first to tip!</p>';
            return;
        }

        const html = tippers.map((tipper, index) => `
            <div class="tipper-card glass-panel reveal" style="animation-delay: ${index * 0.1}s">
                <div class="tipper-rank">#${index + 1}</div>
                <div class="tipper-info">
                    <div class="tipper-username">${this.escapeHtml(tipper.username)}</div>
                    <div class="tipper-amount">$${tipper.amount}</div>
                    <div class="tipper-metadata">
                        <div class="tipper-detail">
                            <span class="detail-label">DOB:</span>
                            <span class="detail-value">${tipper.dob}</span>
                        </div>
                        <div class="tipper-detail">
                            <span class="detail-label">Date:</span>
                            <span class="detail-value">${tipper.date}</span>
                        </div>
                        <div class="tipper-detail">
                            <span class="detail-label">Time:</span>
                            <span class="detail-value">${tipper.time}</span>
                        </div>
                    </div>
                </div>
            </div>
        `).join('');

        container.innerHTML = html;

        // Trigger reveal animation
        setTimeout(() => {
            document.querySelectorAll('.tipper-card.reveal').forEach(card => {
                card.classList.add('revealed');
            });
        }, 100);
    }

    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // Tip form submission
        const tipForm = document.getElementById('tip-form');
        if (tipForm) {
            tipForm.addEventListener('submit', async (e) => {
                e.preventDefault();

                const amountInput = document.getElementById('tip-amount');
                const messageInput = document.getElementById('tip-message');

                const amount = parseFloat(amountInput.value);
                const message = messageInput.value.trim();

                await this.sendTip(amount, message);
            });
        }

        // Amount validation
        const amountInput = document.getElementById('tip-amount');
        if (amountInput) {
            amountInput.addEventListener('input', (e) => {
                const value = parseFloat(e.target.value);
                if (value < 0) e.target.value = '';
                if (value > 1000) e.target.value = '1000';
            });
        }
    }

    /**
     * Handle redirect after tip payment
     */
    handleTipRedirect() {
        const urlParams = new URLSearchParams(window.location.search);
        const tipStatus = urlParams.get('tip');

        if (tipStatus === 'success') {
            this.showTipSuccess();
            // Remove query param from URL
            window.history.replaceState({}, document.title, window.location.pathname);
            // Reload tippers list after a delay (webhook processing)
            setTimeout(() => {
                this.loadRecentTippers();
            }, 2000);
        } else if (tipStatus === 'cancelled') {
            this.showTipCancelled();
            // Remove query param from URL
            window.history.replaceState({}, document.title, window.location.pathname);
        }
    }

    /**
     * Show tip success message
     */
    showTipSuccess() {
        const message = document.createElement('div');
        message.className = 'tip-success-message';
        message.innerHTML = `
            <div class="success-content">
                <h3>✓ Thank You!</h3>
                <p>Your tip has been sent successfully. Thank you for your support!</p>
            </div>
        `;
        document.body.appendChild(message);

        setTimeout(() => {
            message.style.opacity = '0';
            setTimeout(() => message.remove(), 500);
        }, 5000);
    }

    /**
     * Show tip cancelled message
     */
    showTipCancelled() {
        const message = document.createElement('div');
        message.className = 'tip-cancelled-message';
        message.innerHTML = `
            <div class="cancelled-content">
                <h3>Tip Cancelled</h3>
                <p>Your tip was cancelled. No charges were made.</p>
            </div>
        `;
        document.body.appendChild(message);

        setTimeout(() => {
            message.style.opacity = '0';
            setTimeout(() => message.remove(), 500);
        }, 4000);
    }

    /**
     * Escape HTML to prevent XSS
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Create global instance
const tippingManager = new TippingManager();

// Initialize on pages with tipping section
if (document.getElementById('tipping')) {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => tippingManager.init());
    } else {
        tippingManager.init();
    }
}
