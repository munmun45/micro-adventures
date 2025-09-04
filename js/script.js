// Mobile-first JavaScript for Micro Adventures

document.addEventListener('DOMContentLoaded', function() {
    // Initialize hero slider functionality
    initHeroSlider();
    
    // Function to initialize and control the hero slider
    function initHeroSlider() {
        const sliderContainer = document.querySelector('.hero-slider');
        const slides = document.querySelectorAll('.hero-slider .slide');
        const dots = document.querySelectorAll('.hero-slider .dot');
        
        if (!slides.length || !sliderContainer) return;
        
        let currentSlide = 0;
        let slideInterval;
        let touchStartX = 0;
        let touchEndX = 0;
        
        // Start automatic slideshow
        startSlideshow();
        
        // Touch events for swipe functionality
        sliderContainer.addEventListener('touchstart', (e) => {
            touchStartX = e.changedTouches[0].screenX;
        }, {passive: true});
        
        sliderContainer.addEventListener('touchend', (e) => {
            touchEndX = e.changedTouches[0].screenX;
            handleSwipe();
        }, {passive: true});
        
        // Handle swipe direction
        function handleSwipe() {
            const swipeThreshold = 50; // Minimum distance required for a swipe
            const swipeDistance = touchEndX - touchStartX;
            
            if (Math.abs(swipeDistance) < swipeThreshold) return; // Not a significant swipe
            
            clearInterval(slideInterval);
            
            if (swipeDistance > 0) {
                // Swipe right - go to previous slide
                currentSlide = (currentSlide === 0) ? slides.length - 1 : currentSlide - 1;
            } else {
                // Swipe left - go to next slide
                currentSlide = (currentSlide === slides.length - 1) ? 0 : currentSlide + 1;
            }
            
            updateSlider();
            startSlideshow();
        }
        
        // Dot indicators click events
        dots.forEach((dot, index) => {
            dot.addEventListener('click', () => {
                clearInterval(slideInterval);
                currentSlide = index;
                updateSlider();
                startSlideshow();
            });
        });
        
        // Function to update slider state
        function updateSlider() {
            // Update slides
            slides.forEach((slide, index) => {
                if (index === currentSlide) {
                    slide.classList.add('active');
                } else {
                    slide.classList.remove('active');
                }
            });
            
            // Update dots
            dots.forEach((dot, index) => {
                if (index === currentSlide) {
                    dot.classList.add('active');
                } else {
                    dot.classList.remove('active');
                }
            });
        }
        
        // Function to start automatic slideshow
        function startSlideshow() {
            clearInterval(slideInterval);
            slideInterval = setInterval(() => {
                currentSlide = (currentSlide === slides.length - 1) ? 0 : currentSlide + 1;
                updateSlider();
            }, 5000); // Change slide every 5 seconds
        }
    }
    
    // Initialize mobile search functionality
    const mobileSearchInput = document.querySelector('.mobile-search-bar .search-input');
    const submitBtn = document.querySelector('.submit-btn');
    
    if (mobileSearchInput) {
        mobileSearchInput.addEventListener('focus', function() {
            // You could expand the search bar or show search suggestions here
            console.log('Search focused');
        });
        
        mobileSearchInput.addEventListener('input', function() {
            // Handle search input changes
            console.log('Search input:', this.value);
        });
        
        // Add event listener for Enter key
        mobileSearchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                handleSearch();
            }
        });
    }
    
    if (submitBtn) {
        submitBtn.addEventListener('click', function() {
            // Handle search submission
            handleSearch();
        });
    }
    
    // Function to handle search submission
    function handleSearch() {
        const searchValue = mobileSearchInput.value.trim();
        if (searchValue) {
            console.log('Search submitted:', searchValue);
            // Implement search functionality here
        }
    }
    
    // Handle profile avatar click
    const profileAvatar = document.querySelector('.profile-avatar');
    if (profileAvatar) {
        profileAvatar.addEventListener('click', function() {
            console.log('Profile clicked');
            // You could navigate to profile page or show profile dropdown here
        });
    }
    
    
    // Set current location with Geolocation API
    const currentLocationElement = document.getElementById('current-location');
    const locationDisplay = document.querySelector('.location-display');
    
    // Function to update location display
    function updateLocationDisplay(location) {
        if (currentLocationElement) {
            currentLocationElement.textContent = location;
        }
    }
    
    // Initialize with default location
    updateLocationDisplay('Balianta, IN-OD');
    
    // Get user's location with permission
    function getUserLocation() {
        if (navigator.geolocation) {
            showToast('Requesting location permission...');
            navigator.geolocation.getCurrentPosition(
                // Success callback
                function(position) {
                    // In a real app, you would use reverse geocoding to get the city name
                    // For now, we'll just show the coordinates
                    const lat = position.coords.latitude.toFixed(4);
                    const lng = position.coords.longitude.toFixed(4);
                    
                    // Simulate reverse geocoding with our target location
                    updateLocationDisplay('Balianta, IN-OD');
                    showToast('Location updated successfully');
                },
                // Error callback
                function(error) {
                    console.error('Error getting location:', error);
                    switch(error.code) {
                        case error.PERMISSION_DENIED:
                            showToast('Location permission denied');
                            break;
                        case error.POSITION_UNAVAILABLE:
                            showToast('Location information unavailable');
                            break;
                        case error.TIMEOUT:
                            showToast('Location request timed out');
                            break;
                        default:
                            showToast('Unknown error getting location');
                            break;
                    }
                },
                // Options
                {
                    enableHighAccuracy: true,
                    timeout: 5000,
                    maximumAge: 0
                }
            );
        } else {
            showToast('Geolocation is not supported by this browser');
        }
    }
    
    // Location popup functionality
    const locationPopup = document.querySelector('.location-popup');
    const closePopupBtn = document.querySelector('.close-popup');
    const locationOptions = document.querySelectorAll('.location-option');
    const locationSearchInput = document.getElementById('location-search-input');
    
    // Function to open location popup
    function openLocationPopup(e) {
        e.stopPropagation(); // Prevent event bubbling
        locationPopup.classList.add('active');
        
        // Close popup when clicking outside
        document.addEventListener('click', closePopupOnClickOutside);
    }
    
    // Function to close location popup
    function closeLocationPopup() {
        locationPopup.classList.remove('active');
        document.removeEventListener('click', closePopupOnClickOutside);
    }
    
    // Close popup when clicking outside
    function closePopupOnClickOutside(e) {
        if (!locationPopup.contains(e.target) && !locationDisplay.contains(e.target)) {
            closeLocationPopup();
        }
    }
    
    // Handle location option selection
    function selectLocation(e) {
        const selectedLocation = e.currentTarget.querySelector('span').textContent;
        
        // If "Use current location" is selected
        if (selectedLocation === 'Use current location') {
            getUserLocation();
        } else {
            updateLocationDisplay(selectedLocation);
            showToast(`Location changed to ${selectedLocation}`);
        }
        
        closeLocationPopup();
    }
    
    // Filter location options based on search input
    function filterLocations() {
        const searchTerm = locationSearchInput.value.toLowerCase();
        
        locationOptions.forEach(option => {
            const locationText = option.querySelector('span').textContent.toLowerCase();
            
            // Skip "Use current location" option in filtering
            if (locationText === 'use current location') {
                return;
            }
            
            if (locationText.includes(searchTerm)) {
                option.style.display = 'flex';
            } else {
                option.style.display = 'none';
            }
        });
    }
    
    // Add event listeners for location popup
    if (locationDisplay && locationPopup) {
        locationDisplay.addEventListener('click', openLocationPopup);
        
        if (closePopupBtn) {
            closePopupBtn.addEventListener('click', closeLocationPopup);
        }
        
        if (locationOptions.length > 0) {
            locationOptions.forEach(option => {
                option.addEventListener('click', selectLocation);
            });
        }
        
        if (locationSearchInput) {
            locationSearchInput.addEventListener('input', filterLocations);
        }
    }
    
    // Get location on page load (but don't show popup)
    getUserLocation();
    // Mobile side menu functionality
    const menuToggle = document.querySelector('.menu-toggle');
    const closeMenu = document.querySelector('.close-menu');
    const sideMenu = document.querySelector('.side-menu');
    const overlay = document.querySelector('.side-menu-overlay');
    const body = document.body;
    
    // Function to open menu with animation
    function openMenu() {
        sideMenu.classList.add('active');
        overlay.classList.add('active');
        body.style.overflow = 'hidden'; // Prevent scrolling when menu is open
    }
    
    // Function to close menu with animation
    function closeMenuFunc() {
        sideMenu.classList.remove('active');
        overlay.classList.remove('active');
        body.style.overflow = ''; // Re-enable scrolling
    }
    
    // Event listeners for menu toggle
    if (menuToggle && sideMenu) {
        menuToggle.addEventListener('click', openMenu);
        
        // Close menu when clicking close button
        if (closeMenu) {
            closeMenu.addEventListener('click', closeMenuFunc);
        }
        
        // Close menu when clicking overlay
        if (overlay) {
            overlay.addEventListener('click', closeMenuFunc);
        }
        
        // Close menu when pressing Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && sideMenu.classList.contains('active')) {
                closeMenuFunc();
            }
        });
    }
    
    // Bookmark functionality
    const saveButtons = document.querySelectorAll('.save-btn');
    
    saveButtons.forEach(button => {
        button.addEventListener('click', function() {
            const icon = this.querySelector('i');
            if (icon.classList.contains('far')) {
                icon.classList.remove('far');
                icon.classList.add('fas');
                showToast('Adventure saved!');
            } else {
                icon.classList.remove('fas');
                icon.classList.add('far');
                showToast('Adventure removed from saved list');
            }
        });
    });
    
    // Search functionality placeholder
    const searchForm = document.querySelector('.search-container');
    const searchInput = document.querySelector('.search-input');
    
    if (searchForm && searchInput) {
        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const query = searchInput.value.trim();
            if (query) {
                showToast(`Searching for "${query}"...`);
                // In a real app, this would trigger an API call or page navigation
            }
        });
        
        // Add submit event to search button
        const searchBtn = document.querySelector('.search-btn');
        if (searchBtn) {
            searchBtn.addEventListener('click', function() {
                const event = new Event('submit');
                searchForm.dispatchEvent(event);
            });
        }
    }
    
    // Category filter functionality
    const categoryPills = document.querySelectorAll('.category-pill');
    
    categoryPills.forEach(pill => {
        pill.addEventListener('click', function(e) {
            e.preventDefault();
            // Remove active class from all pills
            categoryPills.forEach(p => p.classList.remove('active'));
            // Add active class to clicked pill
            this.classList.add('active');
            
            const category = this.textContent;
            showToast(`Showing ${category} adventures`);
            // In a real app, this would filter the displayed adventures
        });
    });
    
    // Direction button functionality
    const directionButtons = document.querySelectorAll('.direction-btn');
    
    directionButtons.forEach(button => {
        button.addEventListener('click', function() {
            const adventureTitle = this.closest('.adventure-item').querySelector('h3').textContent;
            showToast(`Getting directions to ${adventureTitle}`);
            // In a real app, this would open maps or directions
        });
    });
    
    // Bottom navigation functionality
    const navItems = document.querySelectorAll('.bottom-nav .nav-item');
    
    navItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            // Remove active class from all items
            navItems.forEach(i => i.classList.remove('active'));
            // Add active class to clicked item
            this.classList.add('active');
            
            const section = this.querySelector('span').textContent;
            showToast(`Navigating to ${section}`);
            // In a real app, this would navigate to the corresponding section
        });
    });
    
    // Toast notification function
    function showToast(message) {
        // Create toast element if it doesn't exist
        let toast = document.querySelector('.toast-notification');
        
        if (!toast) {
            toast = document.createElement('div');
            toast.className = 'toast-notification';
            document.body.appendChild(toast);
            
            // Add toast styles dynamically
            const style = document.createElement('style');
            style.textContent = `
                .toast-notification {
                    position: fixed;
                    bottom: 80px;
                    left: 50%;
                    transform: translateX(-50%);
                    background-color: rgba(0, 0, 0, 0.8);
                    color: white;
                    padding: 12px 20px;
                    border-radius: 20px;
                    font-size: 14px;
                    z-index: 1000;
                    opacity: 0;
                    transition: opacity 0.3s ease;
                }
                
                .toast-notification.show {
                    opacity: 1;
                }
                
                @media (min-width: 1024px) {
                    .toast-notification {
                        bottom: 20px;
                    }
                }
            `;
            document.head.appendChild(style);
        }
        
        // Set message and show toast
        toast.textContent = message;
        toast.classList.add('show');
        
        // Hide toast after 3 seconds
        setTimeout(() => {
            toast.classList.remove('show');
        }, 3000);
    }
    
    // Simulate loading state
    showToast('Welcome to Micro Adventures!');
});