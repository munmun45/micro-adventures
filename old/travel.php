<?php
$pageTitle = 'Select Travel Type | TravelApp';
$pageCSS = 'travel.css';
include 'includes/head.php';
include 'includes/navbar.php';
include 'includes/overlay.php';
include 'includes/sidebar.php';
?>

<!-- Page Header -->
<div class="pt-20 sm:pt-24 pb-6 bg-white border-b border-gray-200">
    <div class="max-w-6xl mx-auto px-4 sm:px-6">
        <div class="flex items-center gap-2 mb-2">
            <h1 class="text-xl sm:text-2xl font-semibold text-gray-900">Select Travel Type</h1>
        </div>
        <p class="text-xs sm:text-sm text-gray-500 mt-1">Choose your preferred mode of travel</p>
    </div>
</div>

<!-- Location Modal -->
<div id="locationModal" class="location-modal" style="display: flex;">
    <div class="location-modal-content" onclick="event.stopPropagation()">
        <h2 class="text-xl font-bold text-gray-900 mb-4">Get Your Current Location</h2>
        <p class="text-sm text-gray-600 mb-4">Location is required to proceed</p>
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Current Location</label>
            <input type="text" id="currentLocation" class="location-input" placeholder="Enter your current location or click to detect" readonly>
            <button onclick="getCurrentLocation()" class="mt-3 w-full px-4 py-3 rounded-xl bg-[#009688] text-white font-semibold hover:bg-[#00796b] flex items-center justify-center gap-2">
                <i class="bi bi-geo-alt"></i> Use Current Location
            </button>
        </div>
        <div>
            <button id="confirmLocationBtn" onclick="confirmLocation()" 
                class="w-full px-4 py-3 rounded-xl bg-[#009688] text-white font-semibold hover:bg-[#00796b] disabled:opacity-50 disabled:cursor-not-allowed"
                disabled>
                Confirm Location
            </button>
        </div>
    </div>
</div>

<!-- Travel Options -->
<section class="max-w-7xl mx-auto px-4 sm:px-6 py-6 pb-24">
    
    <!-- Travel Type Section -->
    <div class="mb-8">
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4" id="travelTypeContainer">
            <!-- Travel types will be rendered here -->
        </div>
    </div>

</section>

<!-- Selection Summary Bar -->
<div id="selectionSummary" class="selection-summary">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-3 sm:py-4">
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 sm:gap-4">
            <div class="flex items-center justify-between sm:justify-start gap-4 sm:gap-6 flex-wrap">
                <div class="flex items-center gap-2">
                    <span class="text-xs sm:text-sm text-gray-600">Travel:</span>
                    <span id="travelTypeName" class="text-sm sm:text-base font-bold text-gray-900 bg-gray-100 px-2 py-1 rounded">-</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs sm:text-sm text-gray-600">Price Range:</span>
                    <span id="priceRangeDisplay" class="text-base sm:text-lg font-bold text-[#009688]">₹0 - ₹0</span>
                </div>
            </div>
            <button id="finalProceedBtn" onclick="finalProceed()" 
                class="px-6 sm:px-8 py-2.5 sm:py-3 rounded-xl bg-[#009688] text-white text-sm sm:text-base font-semibold shadow-lg disabled:opacity-50 disabled:cursor-not-allowed whitespace-nowrap">
                Proceed
            </button>
        </div>
    </div>
</div>

<script>
    let selectedTravelType = null;
    let priceRange = { min: null, max: null };
    let hotelData = null;
    let currentLocation = null;
    let locationConfirmed = false;

    const travelTypes = [
        { id: 'train', name: 'Train', priceRange: '₹500 - ₹2,000', minPrice: 500, maxPrice: 2000, icon: 'bi-train-front', image: 'https://images.unsplash.com/photo-1551590192-8070a16d9f67?w=400&h=300&fit=crop' },
        { id: 'bus', name: 'Bus', priceRange: '₹300 - ₹1,500', minPrice: 300, maxPrice: 1500, icon: 'bi-bus-front', image: 'https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?w=400&h=300&fit=crop' },
        { id: 'flight', name: 'Flight', priceRange: '₹3,000 - ₹15,000', minPrice: 3000, maxPrice: 15000, icon: 'bi-airplane', image: 'https://images.unsplash.com/photo-1436491865332-7a61a109cc05?w=400&h=300&fit=crop' },
        { id: 'taxi', name: 'Taxi', priceRange: '₹1,500 - ₹5,000', minPrice: 1500, maxPrice: 5000, icon: 'bi-car-front', image: 'https://images.unsplash.com/photo-1449824913935-59a10b8d2000?w=400&h=300&fit=crop' },
        { id: 'car', name: 'Car Rental', priceRange: '₹2,000 - ₹8,000', minPrice: 2000, maxPrice: 8000, icon: 'bi-car-front-fill', image: 'https://images.unsplash.com/photo-1449824913935-59a10b8d2000?w=400&h=300&fit=crop' },
        { id: 'auto', name: 'Auto Rickshaw', priceRange: '₹200 - ₹800', minPrice: 200, maxPrice: 800, icon: 'bi-three-dots-vertical', image: 'https://images.unsplash.com/photo-1605559424843-9e4c228bf1c2?w=400&h=300&fit=crop' },
        { id: 'metro', name: 'Metro', priceRange: '₹20 - ₹100', minPrice: 20, maxPrice: 100, icon: 'bi-train-front-fill', image: 'https://images.unsplash.com/photo-1551590192-8070a16d9f67?w=400&h=300&fit=crop' },
        { id: 'ferry', name: 'Ferry/Boat', priceRange: '₹100 - ₹500', minPrice: 100, maxPrice: 500, icon: 'bi-water', image: 'https://images.unsplash.com/photo-1544551763-46a013bb70d5?w=400&h=300&fit=crop' },
        { id: 'bike', name: 'Bike/Motorcycle', priceRange: '₹500 - ₹2,500', minPrice: 500, maxPrice: 2500, icon: 'bi-bicycle', image: 'https://images.unsplash.com/photo-1558980664-2506fca6bfc2?w=400&h=300&fit=crop' },
        { id: 'cycle', name: 'Cycle', priceRange: '₹100 - ₹500', minPrice: 100, maxPrice: 500, icon: 'bi-bicycle', image: 'https://images.unsplash.com/photo-1558980664-2506fca6bfc2?w=400&h=300&fit=crop' },
        { id: 'shared', name: 'Shared Cab', priceRange: '₹300 - ₹1,200', minPrice: 300, maxPrice: 1200, icon: 'bi-people', image: 'https://images.unsplash.com/photo-1449824913935-59a10b8d2000?w=400&h=300&fit=crop' },
        { id: 'rickshaw', name: 'Cycle Rickshaw', priceRange: '₹50 - ₹200', minPrice: 50, maxPrice: 200, icon: 'bi-bicycle', image: 'https://images.unsplash.com/photo-1605559424843-9e4c228bf1c2?w=400&h=300&fit=crop' },
        { id: 'cruise', name: 'Cruise', priceRange: '₹5,000 - ₹25,000', minPrice: 5000, maxPrice: 25000, icon: 'bi-water', image: 'https://images.unsplash.com/photo-1544551763-46a013bb70d5?w=400&h=300&fit=crop' },
        { id: 'helicopter', name: 'Helicopter', priceRange: '₹10,000 - ₹50,000', minPrice: 10000, maxPrice: 50000, icon: 'bi-airplane', image: 'https://images.unsplash.com/photo-1436491865332-7a61a109cc05?w=400&h=300&fit=crop' }
    ];

    // Load hotel selection from localStorage
    function loadHotelData() {
        const savedData = localStorage.getItem('hotelSelection');
        if (savedData) {
            hotelData = JSON.parse(savedData);
        } else {
            // If no hotel data, redirect back to booking page
            alert('Please select accommodation first');
            window.location.href = 'booking.php';
        }
    }

    function renderTravelTypes() {
        const container = document.getElementById('travelTypeContainer');
        container.innerHTML = travelTypes.map(travel => `
            <div id="travel-${travel.id}" 
                 class="option-card bg-white rounded-xl border-2 border-gray-200 p-3 shadow-sm"
                 onclick="selectTravelType('${travel.id}')">
                <div class="option-checkbox"></div>
                <img src="${travel.image}" alt="${travel.name}" class="option-image" onerror="this.src='https://via.placeholder.com/400x300?text=${travel.name}'">
                <div>
                    <h3 class="text-sm font-bold text-gray-900 mb-0.5">${travel.name}</h3>
                    <p class="text-xs text-gray-500">${travel.priceRange}</p>
                </div>
            </div>
        `).join('');
    }

    function selectTravelType(travelId) {
        if (!locationConfirmed) {
            showLocationModal();
            return;
        }
        
        selectedTravelType = travelId;
        const travel = travelTypes.find(t => t.id === travelId);
        if (travel && travel.minPrice && travel.maxPrice) {
            priceRange.min = travel.minPrice;
            priceRange.max = travel.maxPrice;
        }
        travelTypes.forEach(travel => {
            const card = document.getElementById(`travel-${travel.id}`);
            if (card) {
                card.classList.toggle('selected', travel.id === travelId);
            }
        });
        updateSummary();
        checkProceedButton();
    }

    function updateSummary() {
        const travelName = document.getElementById('travelTypeName');
        const priceRangeDisplay = document.getElementById('priceRangeDisplay');
        
        if (selectedTravelType && priceRange.min && priceRange.max) {
            const travel = travelTypes.find(t => t.id === selectedTravelType);
            travelName.textContent = travel.name;
            priceRangeDisplay.textContent = `₹${priceRange.min.toLocaleString()} - ₹${priceRange.max.toLocaleString()}`;
        } else {
            travelName.textContent = '-';
            priceRangeDisplay.textContent = '₹0 - ₹0';
        }
    }

    // Get current location using geolocation API
    function getCurrentLocation() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                async (position) => {
                    const lat = position.coords.latitude;
                    const lon = position.coords.longitude;
                    currentLocation = { lat, lon };
                    
                    // Try to get address from coordinates
                    try {
                        const response = await fetch(`https://api.bigdatacloud.net/data/reverse-geocode-client?latitude=${lat}&longitude=${lon}`);
                        const data = await response.json();
                        const locationText = data.city || data.locality || data.principalSubdivision || `${lat.toFixed(4)}, ${lon.toFixed(4)}`;
                        document.getElementById('currentLocation').value = locationText;
                        
                        // Enable confirm button
                        document.getElementById('confirmLocationBtn').disabled = false;
                    } catch (e) {
                        document.getElementById('currentLocation').value = `${lat.toFixed(4)}, ${lon.toFixed(4)}`;
                        // Enable confirm button
                        document.getElementById('confirmLocationBtn').disabled = false;
                    }
                },
                (error) => {
                    alert('Unable to get your location. Please enter manually.');
                    document.getElementById('currentLocation').removeAttribute('readonly');
                    document.getElementById('currentLocation').addEventListener('input', function() {
                        if (this.value.trim()) {
                            document.getElementById('confirmLocationBtn').disabled = false;
                        } else {
                            document.getElementById('confirmLocationBtn').disabled = true;
                        }
                    });
                    console.error('Geolocation error:', error);
                }
            );
        } else {
            alert('Geolocation is not supported by your browser. Please enter your location manually.');
            document.getElementById('currentLocation').removeAttribute('readonly');
            document.getElementById('currentLocation').addEventListener('input', function() {
                if (this.value.trim()) {
                    document.getElementById('confirmLocationBtn').disabled = false;
                } else {
                    document.getElementById('confirmLocationBtn').disabled = true;
                }
            });
        }
    }

    // Show location modal (always show on page load)
    function showLocationModal() {
        document.getElementById('locationModal').style.display = 'flex';
        // Reset location
        document.getElementById('currentLocation').value = '';
        document.getElementById('confirmLocationBtn').disabled = true;
        locationConfirmed = false;
    }

    // Confirm location
    function confirmLocation() {
        const currentLoc = document.getElementById('currentLocation').value;
        
        if (!currentLoc || !currentLoc.trim()) {
            alert('Please get your current location first');
            return;
        }
        
        // Save location data
        const locationData = {
            current: currentLoc,
            coordinates: currentLocation
        };
        localStorage.setItem('travelLocation', JSON.stringify(locationData));
        
        locationConfirmed = true;
        document.getElementById('locationModal').style.display = 'none';
    }

    function checkProceedButton() {
        const btn = document.getElementById('finalProceedBtn');
        if (selectedTravelType && priceRange.min && priceRange.max) {
            btn.disabled = false;
        } else {
            btn.disabled = true;
        }
    }

    function finalProceed() {
        if (!selectedTravelType || !priceRange.min || !priceRange.max) {
            alert('Please select a travel type before proceeding');
            return;
        }
        
        if (!hotelData) {
            alert('Hotel selection not found. Please start over.');
            window.location.href = 'booking.php';
            return;
        }
        
        const travel = travelTypes.find(t => t.id === selectedTravelType);
        
        // Calculate total price range
        let totalMin = priceRange.min;
        let totalMax = priceRange.max;
        if (hotelData.hotelMinPrice && hotelData.hotelMaxPrice) {
            totalMin = hotelData.hotelMinPrice + priceRange.min;
            totalMax = hotelData.hotelMaxPrice + priceRange.max;
        }
        
        // Save complete booking data
        const completeBookingData = {
            ...hotelData,
            travelType: selectedTravelType,
            travelName: travel.name,
            travelPriceRange: travel.priceRange,
            travelMinPrice: priceRange.min,
            travelMaxPrice: priceRange.max,
            totalMinPrice: totalMin,
            totalMaxPrice: totalMax
        };
        localStorage.setItem('completeBooking', JSON.stringify(completeBookingData));
        
        alert(`Booking Summary:\n\nAccommodation: ${hotelData.hotelName} (${hotelData.hotelPriceRange})\nTravel: ${travel.name} (${travel.priceRange})\nTotal Price Range: ₹${totalMin.toLocaleString()} - ₹${totalMax.toLocaleString()}\n\nProceeding to final booking...`);
        // Here you would redirect to final booking confirmation page
    }

    // Initialize
    loadHotelData();
    renderTravelTypes();
    checkProceedButton();
    updateSummary();
    
    // Initialize location modal - always show on page load
    window.addEventListener('load', function() {
        // Always show location modal on page load
        showLocationModal();
    });
</script>

<script src="includes/mobile-menu.js"></script>

</body>

</html>

