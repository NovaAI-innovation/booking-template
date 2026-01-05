/**
 * User Authentication Manager
 * Handles user registration, login, logout, and session management on the frontend
 */

class AuthManager {
    constructor() {
        this.user = null;
        this.csrfToken = null;
        this.initialized = false;
    }

    /**
     * Initialize the auth manager
     */
    async init() {
        if (this.initialized) return;

        await this.fetchCsrfToken();
        await this.checkAuth();
        this.setupEventListeners();
        this.initialized = true;
    }

    /**
     * Fetch CSRF token from server
     */
    async fetchCsrfToken() {
        try {
            const response = await fetch('/config.php?get_csrf_token=1');
            const data = await response.json();
            this.csrfToken = data.csrf_token;
        } catch (error) {
            console.error('Failed to fetch CSRF token:', error);
            // Fallback: generate client-side token (will be replaced by server)
            this.csrfToken = 'csrf-' + Math.random().toString(36).substring(2);
        }
    }

    /**
     * Check if user is authenticated
     */
    async checkAuth() {
        try {
            const response = await fetch('/user-api.php?action=check-auth');
            const data = await response.json();

            if (data.authenticated) {
                this.user = data.user;
                this.updateUI();
                return true;
            }
            this.user = null;
            this.updateUI();
            return false;
        } catch (error) {
            console.error('Auth check failed:', error);
            return false;
        }
    }

    /**
     * Register a new user
     */
    async register(username, email, password, dateOfBirth) {
        try {
            const response = await fetch('/user-api.php?action=register', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    username,
                    email,
                    password,
                    date_of_birth: dateOfBirth,
                    csrf_token: this.csrfToken
                })
            });

            const data = await response.json();

            if (data.success) {
                this.user = data.user;
                this.updateUI();
                return { success: true, message: data.message };
            }
            return { success: false, message: data.message };
        } catch (error) {
            console.error('Registration error:', error);
            return { success: false, message: 'Network error. Please try again.' };
        }
    }

    /**
     * Login user
     */
    async login(username, password) {
        try {
            const response = await fetch('/user-api.php?action=login', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    username,
                    password,
                    csrf_token: this.csrfToken
                })
            });

            const data = await response.json();

            if (data.success) {
                this.user = data.user;
                this.updateUI();
                return { success: true, message: data.message };
            }
            return { success: false, message: data.message };
        } catch (error) {
            console.error('Login error:', error);
            return { success: false, message: 'Network error. Please try again.' };
        }
    }

    /**
     * Logout user
     */
    async logout() {
        try {
            await fetch('/user-api.php?action=logout');
            this.user = null;
            this.updateUI();
            // Reload page to clear any cached data
            window.location.reload();
        } catch (error) {
            console.error('Logout failed:', error);
            // Force logout on client side even if server request fails
            this.user = null;
            this.updateUI();
            window.location.reload();
        }
    }

    /**
     * Update UI based on authentication state
     */
    updateUI() {
        const userMenu = document.getElementById('user-menu');
        const usernameDisplay = document.getElementById('username-display');
        const loginBtn = document.getElementById('show-login-btn');

        if (this.user) {
            // User is logged in
            if (userMenu) {
                userMenu.classList.remove('hidden');
            }
            if (usernameDisplay) {
                usernameDisplay.textContent = this.user.username;
            }
            if (loginBtn) {
                loginBtn.classList.add('hidden');
            }
        } else {
            // User is not logged in
            if (userMenu) {
                userMenu.classList.add('hidden');
            }
            if (loginBtn) {
                loginBtn.classList.remove('hidden');
            }
        }

        // Trigger custom event for other components to react
        window.dispatchEvent(new CustomEvent('authStateChanged', {
            detail: { authenticated: this.isAuthenticated(), user: this.user }
        }));
    }

    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // Logout button
        const logoutBtn = document.getElementById('logout-btn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.logout();
            });
        }

        // Auth modal close button
        const authModalClose = document.querySelector('#auth-modal .modal-close');
        if (authModalClose) {
            authModalClose.addEventListener('click', () => {
                this.hideAuthModal();
            });
        }

        // Tab switching
        const authTabs = document.querySelectorAll('.auth-tab');
        authTabs.forEach(tab => {
            tab.addEventListener('click', (e) => {
                const targetTab = e.target.dataset.tab;
                this.switchAuthTab(targetTab);
            });
        });

        // Login form
        const loginForm = document.getElementById('login-form');
        if (loginForm) {
            loginForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                await this.handleLoginSubmit(e.target);
            });
        }

        // Register form
        const registerForm = document.getElementById('register-form');
        if (registerForm) {
            registerForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                await this.handleRegisterSubmit(e.target);
            });
        }

        // Show login button
        const showLoginBtn = document.getElementById('show-login-btn');
        if (showLoginBtn) {
            showLoginBtn.addEventListener('click', () => {
                this.showAuthModal('login');
            });
        }
    }

    /**
     * Handle login form submission
     */
    async handleLoginSubmit(form) {
        const username = form.username.value.trim();
        const password = form.password.value;
        const messageEl = form.querySelector('.form-message');

        if (!username || !password) {
            this.showFormMessage(messageEl, 'Please fill in all fields', 'error');
            return;
        }

        // Show loading state
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.textContent = 'Logging in...';

        const result = await this.login(username, password);

        submitBtn.disabled = false;
        submitBtn.textContent = originalText;

        if (result.success) {
            this.showFormMessage(messageEl, result.message, 'success');
            setTimeout(() => {
                this.hideAuthModal();
                // Trigger reload if on gallery page
                if (window.location.pathname.includes('gallery.html')) {
                    window.location.reload();
                }
            }, 1000);
        } else {
            this.showFormMessage(messageEl, result.message, 'error');
        }
    }

    /**
     * Handle registration form submission
     */
    async handleRegisterSubmit(form) {
        const username = form.username.value.trim();
        const email = form.email.value.trim();
        const password = form.password.value;
        const dateOfBirth = form.date_of_birth.value;
        const messageEl = form.querySelector('.form-message');

        if (!username || !email || !password || !dateOfBirth) {
            this.showFormMessage(messageEl, 'Please fill in all fields', 'error');
            return;
        }

        // Show loading state
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.textContent = 'Creating account...';

        const result = await this.register(username, email, password, dateOfBirth);

        submitBtn.disabled = false;
        submitBtn.textContent = originalText;

        if (result.success) {
            this.showFormMessage(messageEl, result.message, 'success');
            setTimeout(() => {
                this.hideAuthModal();
                // Trigger reload if on gallery page
                if (window.location.pathname.includes('gallery.html')) {
                    window.location.reload();
                }
            }, 1000);
        } else {
            this.showFormMessage(messageEl, result.message, 'error');
        }
    }

    /**
     * Show form message
     */
    showFormMessage(messageEl, message, type) {
        if (!messageEl) return;
        messageEl.textContent = message;
        messageEl.className = 'form-message ' + type;
        messageEl.style.display = 'block';
    }

    /**
     * Switch between login and register tabs
     */
    switchAuthTab(tabName) {
        // Update tab buttons
        document.querySelectorAll('.auth-tab').forEach(tab => {
            if (tab.dataset.tab === tabName) {
                tab.classList.add('active');
            } else {
                tab.classList.remove('active');
            }
        });

        // Update form containers
        const loginContainer = document.getElementById('login-form-container');
        const registerContainer = document.getElementById('register-form-container');

        if (tabName === 'login') {
            loginContainer?.classList.remove('hidden');
            registerContainer?.classList.add('hidden');
        } else {
            loginContainer?.classList.add('hidden');
            registerContainer?.classList.remove('hidden');
        }
    }

    /**
     * Show auth modal
     */
    showAuthModal(tab = 'login') {
        const modal = document.getElementById('auth-modal');
        if (modal) {
            modal.classList.remove('hidden');
            this.switchAuthTab(tab);
        }
    }

    /**
     * Hide auth modal
     */
    hideAuthModal() {
        const modal = document.getElementById('auth-modal');
        if (modal) {
            modal.classList.add('hidden');
            // Clear form messages
            document.querySelectorAll('.form-message').forEach(msg => {
                msg.style.display = 'none';
            });
        }
    }

    /**
     * Check if user is authenticated
     */
    isAuthenticated() {
        return this.user !== null;
    }

    /**
     * Get current user
     */
    getUser() {
        return this.user;
    }
}

// Create global instance
const authManager = new AuthManager();

// Auto-initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => authManager.init());
} else {
    authManager.init();
}
