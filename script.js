// ===========================
// Age Verification
// ===========================

const ageModal = document.getElementById('age-modal');
const ageConfirm = document.getElementById('age-confirm');
const ageDecline = document.getElementById('age-decline');

if (ageModal && ageConfirm && ageDecline) {
    // Check if user has already verified age
    const hasVerifiedAge = localStorage.getItem('ageVerified');

    if (hasVerifiedAge === 'true') {
        ageModal.classList.add('hidden');
    } else {
        ageModal.classList.remove('hidden');
    }

    ageConfirm.addEventListener('click', () => {
        localStorage.setItem('ageVerified', 'true');
        ageModal.classList.add('hidden');
    });

    ageDecline.addEventListener('click', () => {
        window.location.href = 'https://www.google.com';
    });
}

// ===========================
// Navigation
// ===========================

const nav = document.getElementById('nav');
const navToggle = document.getElementById('nav-toggle');
const navMenu = document.getElementById('nav-menu');
const navLinks = document.querySelectorAll('.nav-link');

// Sticky navigation on scroll
if (nav) {
    window.addEventListener('scroll', () => {
        if (window.scrollY > 100) {
            nav.classList.add('scrolled');
        } else {
            nav.classList.remove('scrolled');
        }
    });
}

// Mobile menu toggle
if (navToggle && navMenu) {
    navToggle.addEventListener('click', () => {
        navMenu.classList.toggle('active');
    });

    // Close mobile menu when clicking a link
    navLinks.forEach(link => {
        link.addEventListener('click', () => {
            navMenu.classList.remove('active');
        });
    });
}

// ===========================
// Scroll Reveal Animation
// ===========================

const revealElements = document.querySelectorAll('.reveal, .reveal-delay, .reveal-delay-2');

const revealOnScroll = () => {
    const triggerBottom = window.innerHeight * 0.85;
    
    revealElements.forEach(element => {
        const elementTop = element.getBoundingClientRect().top;
        
        if (elementTop < triggerBottom) {
            element.classList.add('active');
        }
    });
};

// Initial check on page load
revealOnScroll();

// Check on scroll
window.addEventListener('scroll', revealOnScroll);

// ===========================
// Load Gallery Data and Initialize
// ===========================

async function loadGalleryData() {
    try {
        const response = await fetch('gallery-data.json');
        const data = await response.json();

        // Load images into carousel
        loadImages(data.images);

        // Load videos into grid
        loadVideos(data.videos);

        // Initialize carousel after images are loaded
        initializeCarousel();

        // Initialize video handlers
        initializeVideoHandlers();
    } catch (error) {
        console.error('Failed to load gallery data:', error);
        showGalleryError();
    }
}

function loadImages(images) {
    const slidesContainer = document.getElementById('gallery-slides');
    if (!slidesContainer) return;

    if (!images || images.length === 0) {
        slidesContainer.innerHTML = '<div class="gallery-slide active"><div style="display: flex; align-items: center; justify-content: center; height: 600px; background: rgba(0,0,0,0.8); color: rgba(255,255,255,0.5);">No images available</div></div>';
        return;
    }

    // Sort by order
    images.sort((a, b) => a.order - b.order);

    // Create slides HTML
    const slidesHTML = images.map((image, index) => `
        <div class="gallery-slide ${index === 0 ? 'active' : ''}">
            <img src="${image.path}" alt="${image.alt}" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22800%22 height=%22600%22%3E%3Crect fill=%22%23333%22 width=%22800%22 height=%22600%22/%3E%3Ctext fill=%22%23fff%22 x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dy=%22.3em%22%3EImage Not Found%3C/text%3E%3C/svg%3E'">
        </div>
    `).join('');

    slidesContainer.innerHTML = slidesHTML;
}

function loadVideos(videos) {
    const videosContainer = document.getElementById('videos-grid');
    if (!videosContainer) return;

    if (!videos || videos.length === 0) {
        videosContainer.innerHTML = '<div class="video-item"><div style="display: flex; align-items: center; justify-content: center; height: 200px; background: rgba(0,0,0,0.8); color: rgba(255,255,255,0.5);">No videos available</div></div>';
        return;
    }

    // Sort by order
    videos.sort((a, b) => a.order - b.order);

    // Create videos HTML
    const videosHTML = videos.map(video => `
        <div class="video-item">
            <video controls poster="${video.thumbnailPath}">
                <source src="${video.videoPath}" type="video/mp4">
                Your browser does not support the video tag.
            </video>
            <div class="video-overlay">
                <span class="video-play-icon">▶</span>
            </div>
        </div>
    `).join('');

    videosContainer.innerHTML = videosHTML;
}

function showGalleryError() {
    const slidesContainer = document.getElementById('gallery-slides');
    const videosContainer = document.getElementById('videos-grid');

    if (slidesContainer) {
        slidesContainer.innerHTML = '<div class="gallery-slide active"><div style="display: flex; align-items: center; justify-content: center; height: 600px; background: rgba(0,0,0,0.8); color: rgba(255,255,255,0.5);">Failed to load gallery. Please refresh the page.</div></div>';
    }

    if (videosContainer) {
        videosContainer.innerHTML = '<div class="video-item"><div style="display: flex; align-items: center; justify-content: center; height: 200px; background: rgba(0,0,0,0.8); color: rgba(255,255,255,0.5);">Failed to load videos.</div></div>';
    }
}

// ===========================
// Gallery Carousel
// ===========================

function initializeCarousel() {
    const carouselSlides = document.querySelectorAll('.gallery-slide');
    const carouselPrevBtn = document.getElementById('carousel-prev');
    const carouselNextBtn = document.getElementById('carousel-next');
    const carouselIndicatorsContainer = document.getElementById('carousel-indicators');

    // Only initialize carousel if gallery elements exist
    if (carouselSlides.length === 0 || !carouselIndicatorsContainer) {
        return;
    }

    let currentSlideIndex = 0;
    const totalSlides = carouselSlides.length;

    // Create carousel indicators
    function createIndicators() {
        carouselIndicatorsContainer.innerHTML = '';
        for (let i = 0; i < totalSlides; i++) {
            const indicator = document.createElement('div');
            indicator.classList.add('carousel-indicator');
            if (i === 0) indicator.classList.add('active');
            indicator.addEventListener('click', () => goToSlide(i));
            carouselIndicatorsContainer.appendChild(indicator);
        }
    }

    // Update carousel display
    function updateCarousel() {
        // Remove active class from all slides
        carouselSlides.forEach(slide => slide.classList.remove('active'));

        // Add active class to current slide
        carouselSlides[currentSlideIndex].classList.add('active');

        // Update indicators
        const indicators = document.querySelectorAll('.carousel-indicator');
        indicators.forEach((indicator, index) => {
            if (index === currentSlideIndex) {
                indicator.classList.add('active');
            } else {
                indicator.classList.remove('active');
            }
        });
    }

    // Go to specific slide
    function goToSlide(index) {
        currentSlideIndex = index;
        updateCarousel();
    }

    // Navigate to previous slide
    function prevSlide() {
        currentSlideIndex = (currentSlideIndex - 1 + totalSlides) % totalSlides;
        updateCarousel();
    }

    // Navigate to next slide
    function nextSlide() {
        currentSlideIndex = (currentSlideIndex + 1) % totalSlides;
        updateCarousel();
    }

    // Event listeners for carousel buttons
    if (carouselPrevBtn) {
        carouselPrevBtn.addEventListener('click', prevSlide);
    }

    if (carouselNextBtn) {
        carouselNextBtn.addEventListener('click', nextSlide);
    }

    // Keyboard navigation for carousel
    document.addEventListener('keydown', (e) => {
        // Only navigate if we're in the gallery section
        const gallerySection = document.getElementById('gallery');
        if (!gallerySection) return;

        const rect = gallerySection.getBoundingClientRect();
        const isInView = rect.top < window.innerHeight && rect.bottom > 0;

        if (isInView) {
            if (e.key === 'ArrowLeft') {
                prevSlide();
            } else if (e.key === 'ArrowRight') {
                nextSlide();
            }
        }
    });

    // Initialize carousel
    createIndicators();
    updateCarousel();

    // Touch/Swipe support for mobile
    let touchStartX = 0;
    let touchEndX = 0;

    const gallerySlides = document.querySelector('.gallery-slides');
    if (gallerySlides) {
        gallerySlides.addEventListener('touchstart', (e) => {
            touchStartX = e.changedTouches[0].screenX;
        });

        gallerySlides.addEventListener('touchend', (e) => {
            touchEndX = e.changedTouches[0].screenX;
            handleSwipe();
        });
    }

    function handleSwipe() {
        const swipeThreshold = 50;
        const diff = touchStartX - touchEndX;

        if (Math.abs(diff) > swipeThreshold) {
            if (diff > 0) {
                // Swipe left - next slide
                nextSlide();
            } else {
                // Swipe right - previous slide
                prevSlide();
            }
        }
    }
}

// Load gallery data on page load
if (document.getElementById('gallery')) {
    loadGalleryData();
}

// ===========================
// EmailJS Configuration
// ===========================

// Initialize EmailJS with your public key
const EMAILJS_PUBLIC_KEY = '[YOUR_EMAILJS_PUBLIC_KEY]';
const EMAILJS_SERVICE_ID = '[YOUR_EMAILJS_SERVICE_ID]';
const EMAILJS_TEMPLATE_ID = '[YOUR_EMAILJS_TEMPLATE_ID]';
const RECIPIENT_EMAIL = '[YOUR_EMAIL_ADDRESS]';

// Initialize EmailJS
if (typeof emailjs !== 'undefined') {
    (function() {
        emailjs.init(EMAILJS_PUBLIC_KEY);
    })();
}

// ===========================
// Booking Form Handler
// ===========================

const bookingForm = document.getElementById('booking-form');

// Format datetime for display
const formatDateTime = (datetime) => {
    const date = new Date(datetime);
    return date.toLocaleString('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

if (bookingForm) {
    bookingForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        // Show loading state
        const submitButton = bookingForm.querySelector('button[type="submit"]');
        const originalButtonText = submitButton.textContent;
        submitButton.textContent = 'Sending...';
        submitButton.disabled = true;

        try {
            // Collect all form data
            const formData = new FormData(bookingForm);
            const bookingData = {};

            formData.forEach((value, key) => {
                bookingData[key] = value;
            });

            // Calculate age from date of birth
            let calculatedAge = 'Not provided';
            if (bookingData.date_of_birth) {
                const birthDate = new Date(bookingData.date_of_birth);
                const today = new Date();
                calculatedAge = Math.floor((today - birthDate) / (365.25 * 24 * 60 * 60 * 1000));
            }

            // Format the email parameters
            const emailParams = {
                to_email: RECIPIENT_EMAIL,
                from_name: bookingData.name,
                from_email: bookingData.email,
                reply_to: bookingData.email,
                subject: `New Booking Request from ${bookingData.name}`,

                // All form fields for template use
                client_name: bookingData.name,
                client_email: bookingData.email,
                client_phone: bookingData.phone,
                client_age: calculatedAge,
                client_dob: bookingData.date_of_birth || 'Not provided',
                service_type: bookingData.service_type,
                preferred_date: bookingData.preferred_date,
                preferred_time: bookingData.preferred_time,
                meeting_type: bookingData.meeting_type,
                location_details: bookingData.location_details,
                special_requests: bookingData.special_requests || 'None',
                deposit_ack: bookingData.deposit_acknowledgment ? 'Yes' : 'No',
                etiquette_ack: bookingData.etiquette_acknowledgment ? 'Yes' : 'No',

                // Formatted message body
                message: `
═══════════════════════════════════════════════════════════
                    NEW BOOKING REQUEST
═══════════════════════════════════════════════════════════

📋 PERSONAL INFORMATION
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Name:       ${bookingData.name}
Email:      ${bookingData.email}
Phone:      ${bookingData.phone}
Age:        ${calculatedAge} years old
DOB:        ${bookingData.date_of_birth || 'Not provided'}

📅 APPOINTMENT DETAILS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Service:    ${bookingData.service_type}
Date:       ${bookingData.preferred_date}
Time:       ${bookingData.preferred_time}
Location:   ${bookingData.meeting_type}
City:       ${bookingData.location_details}

💬 SPECIAL REQUESTS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
${bookingData.special_requests || 'None provided'}

✅ ACKNOWLEDGMENTS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Deposit Policy:     ${bookingData.deposit_acknowledgment ? '✓ Agreed' : '✗ Not agreed'}
Etiquette Read:     ${bookingData.etiquette_acknowledgment ? '✓ Agreed' : '✗ Not agreed'}

═══════════════════════════════════════════════════════════
Submitted: ${new Date().toLocaleString('en-US', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    timeZoneName: 'short'
})}
═══════════════════════════════════════════════════════════
                `
            };

            // Send email via EmailJS
            const response = await emailjs.send(
                EMAILJS_SERVICE_ID,
                EMAILJS_TEMPLATE_ID,
                emailParams
            );

            if (response.status === 200) {
                alert('✅ Thank you for your booking request!\n\nYour information has been received. You will receive a response within 24 hours via your preferred contact method.');
                bookingForm.reset();
            }

        } catch (error) {
            console.error('EmailJS Error:', error);
            
            // User-friendly error messages
            let errorMessage = '❌ Booking Submission Failed\n\n';
            
            if (error.text && error.text.includes('Invalid')) {
                errorMessage += 'EmailJS configuration error. Please contact the site administrator.\n\n';
                errorMessage += `Alternative: Email your booking to ${RECIPIENT_EMAIL}`;
            } else if (!navigator.onLine) {
                errorMessage += 'No internet connection detected.\n\n';
                errorMessage += 'Please check your connection and try again.';
            } else {
                errorMessage += 'Network or service error.\n\n';
                errorMessage += `Please try again, or email directly to:\n${RECIPIENT_EMAIL}`;
            }
            
            alert(errorMessage);
        } finally {
            // Restore button state
            submitButton.textContent = originalButtonText;
            submitButton.disabled = false;
        }
    });
}

// ===========================
// Intersection Observer for Performance
// ===========================

// Lazy load images
const images = document.querySelectorAll('img[src]');

const imageObserver = new IntersectionObserver((entries, observer) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            const img = entry.target;
            img.classList.add('loaded');
            observer.unobserve(img);
        }
    });
}, {
    rootMargin: '50px'
});

images.forEach(img => imageObserver.observe(img));

// ===========================
// Parallax Effect on Hero
// ===========================

const hero = document.querySelector('.hero');

window.addEventListener('scroll', () => {
    const scrolled = window.pageYOffset;
    const parallaxSpeed = 0.5;
    
    if (hero && scrolled < window.innerHeight) {
        hero.style.transform = `translateY(${scrolled * parallaxSpeed}px)`;
    }
});

// ===========================
// Smooth Scroll to Top
// ===========================

// Add a scroll to top button
const createScrollTopButton = () => {
    const button = document.createElement('button');
    button.innerHTML = '↑';
    button.className = 'scroll-top-btn';
    button.setAttribute('aria-label', 'Scroll to top');
    
    document.body.appendChild(button);
    
    // Show/hide button based on scroll position
    window.addEventListener('scroll', () => {
        if (window.scrollY > 500) {
            button.classList.add('visible');
        } else {
            button.classList.remove('visible');
        }
    });
    
    // Scroll to top on click
    button.addEventListener('click', () => {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
};

createScrollTopButton();

// ===========================
// Loading Animation
// ===========================

window.addEventListener('load', () => {
    document.body.classList.add('loaded');
});

// ===========================
// Date Input Optimization
// ===========================

// Set minimum date to today for date input
const dateInput = document.getElementById('preferred_date');
if (dateInput) {
    const today = new Date();
    const year = today.getFullYear();
    const month = String(today.getMonth() + 1).padStart(2, '0');
    const day = String(today.getDate()).padStart(2, '0');
    const minDate = `${year}-${month}-${day}`;
    dateInput.min = minDate;
}

// ===========================
// Date of Birth Validation (18+)
// ===========================

const dobInput = document.getElementById('date_of_birth');
if (dobInput) {
    // Set maximum date to 18 years ago (user must be at least 18)
    const today = new Date();
    const maxDate = new Date(today.getFullYear() - 18, today.getMonth(), today.getDate());
    const maxYear = maxDate.getFullYear();
    const maxMonth = String(maxDate.getMonth() + 1).padStart(2, '0');
    const maxDay = String(maxDate.getDate()).padStart(2, '0');
    dobInput.max = `${maxYear}-${maxMonth}-${maxDay}`;

    // Set minimum date to 100 years ago (reasonable limit)
    const minDate = new Date(today.getFullYear() - 100, today.getMonth(), today.getDate());
    const minYear = minDate.getFullYear();
    const minMonth = String(minDate.getMonth() + 1).padStart(2, '0');
    const minDay = String(minDate.getDate()).padStart(2, '0');
    dobInput.min = `${minYear}-${minMonth}-${minDay}`;

    // Add real-time validation on blur (when user leaves field)
    dobInput.addEventListener('blur', function() {
        const selectedDate = new Date(this.value);
        const age = Math.floor((today - selectedDate) / (365.25 * 24 * 60 * 60 * 1000));

        if (age < 18) {
            this.setCustomValidity('You must be at least 18 years old to book.');
            this.reportValidity();
        } else {
            this.setCustomValidity('');
        }
    });

    // Clear validation message when user starts typing
    dobInput.addEventListener('input', function() {
        this.setCustomValidity('');
    });
}

// ===========================
// Video Gallery Handler
// ===========================

function initializeVideoHandlers() {
    const videoItems = document.querySelectorAll('.video-item');

    videoItems.forEach(item => {
        const video = item.querySelector('video');
        const overlay = item.querySelector('.video-overlay');

        if (video && overlay) {
            // Click on video item to play
            item.addEventListener('click', (e) => {
                // If video is paused, play it
                if (video.paused) {
                    video.play();
                    overlay.style.opacity = '0';
                }
            });

            // When video plays, hide overlay
            video.addEventListener('play', () => {
                overlay.style.opacity = '0';
            });

            // When video pauses, show overlay
            video.addEventListener('pause', () => {
                overlay.style.opacity = '1';
            });

            // When video ends, show overlay
            video.addEventListener('ended', () => {
                overlay.style.opacity = '1';
            });
        }
    });
}

// ===========================
// Console Message
// ===========================

console.log('%c [MODEL_NAME] Inc. ', 'background: #2d1b3d; color: #d4af37; font-size: 20px; font-weight: bold; padding: 10px;');
console.log('%c Website designed with ❤️ ', 'background: #f4e5d4; color: #2d1b3d; font-size: 14px; padding: 5px;');
console.log('%c Booking powered by JotForm ', 'background: #d4af37; color: #2d1b3d; font-size: 12px; padding: 5px;');
