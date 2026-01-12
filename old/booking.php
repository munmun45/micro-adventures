<?php
$pageTitle = 'Booking Details | TravelApp';
$pageCSS = 'booking.css';
include 'includes/head.php';
include 'includes/navbar.php';
include 'includes/overlay.php';
include 'includes/sidebar.php';
?>

<!-- Page Header -->
<div class="pt-20 sm:pt-24 pb-6 bg-white border-b border-gray-200">
    <div class="max-w-6xl mx-auto px-4 sm:px-6">
        <h1 class="text-xl sm:text-2xl font-semibold text-gray-900">Select Accommodation</h1>
        <p class="text-xs sm:text-sm text-gray-500 mt-1">Choose your preferred accommodation type</p>
    </div>
</div>

<!-- Booking Options -->
<section class="max-w-7xl mx-auto px-4 sm:px-6 py-6 pb-24">

    <!-- Hotel Type Section -->
    <div class="mb-8">
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4" id="hotelTypeContainer">
            <!-- Hotel types will be rendered here -->
        </div>
    </div>

    <br>
    <br>
    <br>

</section>

<!-- Selection Summary Bar -->
<div id="selectionSummary" class="selection-summary">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-3 sm:py-4">
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 sm:gap-4">
            <div class="flex items-center justify-between sm:justify-start gap-4 sm:gap-6 flex-wrap">
                <div class="flex items-center gap-2">
                    <span class="text-xs sm:text-sm text-gray-600">Accommodation:</span>
                    <span id="hotelTypeName" class="text-sm sm:text-base font-bold text-gray-900 bg-gray-100 px-2 py-1 rounded">-</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs sm:text-sm text-gray-600">Price Range:</span>
                    <span id="priceRangeDisplay" class="text-base sm:text-lg font-bold text-[#009688]">₹0 - ₹0</span>
                </div>
            </div>
            <button id="continueBtn" onclick="continueToTravel()"
                class="px-6 sm:px-8 py-2.5 sm:py-3 rounded-xl bg-[#009688] text-white text-sm sm:text-base font-semibold shadow-lg disabled:opacity-50 disabled:cursor-not-allowed whitespace-nowrap">
                Continue
            </button>
        </div>
    </div>
</div>

<script>
    let selectedHotelType = null;

    const hotelTypes = [
        { id: 'lodge', name: 'Lodge', priceRange: '₹500 - ₹1,500', minPrice: 500, maxPrice: 1500, icon: 'bi-house', image: 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=400&h=300&fit=crop' },
        { id: 'hotel', name: 'Hotel', priceRange: '₹2,000 - ₹5,000', minPrice: 2000, maxPrice: 5000, icon: 'bi-building', image: 'https://images.unsplash.com/photo-1564501049412-61c2a3083791?w=400&h=300&fit=crop' },
        { id: 'resort', name: 'Resort', priceRange: '₹3,000 - ₹8,000', minPrice: 3000, maxPrice: 8000, icon: 'bi-tropical-storm', image: 'https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?w=400&h=300&fit=crop' },
        { id: 'villa', name: 'Villa', priceRange: '₹5,000 - ₹10,000', minPrice: 5000, maxPrice: 10000, icon: 'bi-house-heart', image: 'https://images.unsplash.com/photo-1613490493576-7fde63acd811?w=400&h=300&fit=crop' },
        { id: 'apartment', name: 'Apartment', priceRange: '₹1,500 - ₹4,000', minPrice: 1500, maxPrice: 4000, icon: 'bi-building-fill', image: 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=400&h=300&fit=crop' },
        { id: 'guesthouse', name: 'Guesthouse', priceRange: '₹800 - ₹2,500', minPrice: 800, maxPrice: 2500, icon: 'bi-house-door', image: 'https://images.unsplash.com/photo-1555854877-bab0e564b8d5?w=400&h=300&fit=crop' },
        { id: 'hostel', name: 'Hostel', priceRange: '₹300 - ₹1,200', minPrice: 300, maxPrice: 1200, icon: 'bi-people', image: 'https://images.unsplash.com/photo-1555854877-bab0e564b8d5?w=400&h=300&fit=crop' },
        { id: 'homestay', name: 'Homestay', priceRange: '₹600 - ₹2,000', minPrice: 600, maxPrice: 2000, icon: 'bi-house-door-fill', image: 'https://images.unsplash.com/photo-1555854877-bab0e564b8d5?w=400&h=300&fit=crop' },
        { id: 'cottage', name: 'Cottage', priceRange: '₹1,200 - ₹3,500', minPrice: 1200, maxPrice: 3500, icon: 'bi-tree', image: 'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?w=400&h=300&fit=crop' },
        { id: 'cabin', name: 'Cabin', priceRange: '₹1,000 - ₹3,000', minPrice: 1000, maxPrice: 3000, icon: 'bi-tree-fill', image: 'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?w=400&h=300&fit=crop' },
        { id: 'bungalow', name: 'Bungalow', priceRange: '₹2,500 - ₹6,000', minPrice: 2500, maxPrice: 6000, icon: 'bi-house-door', image: 'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?w=400&h=300&fit=crop' },
        { id: 'treehouse', name: 'Treehouse', priceRange: '₹2,000 - ₹5,500', minPrice: 2000, maxPrice: 5500, icon: 'bi-tree', image: 'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?w=400&h=300&fit=crop' },
        { id: 'camping', name: 'Camping', priceRange: '₹200 - ₹800', minPrice: 200, maxPrice: 800, icon: 'bi-tent', image: 'https://images.unsplash.com/photo-1478131143081-80f7f84ca84d?w=400&h=300&fit=crop' }
    ];

    function renderHotelTypes() {
        const container = document.getElementById('hotelTypeContainer');
        container.innerHTML = hotelTypes.map(hotel => `
            <div id="hotel-${hotel.id}" 
                 class="option-card bg-white rounded-xl border-2 border-gray-200 p-3 shadow-sm"
                 onclick="selectHotelType('${hotel.id}')">
                <div class="option-checkbox"></div>
                <img src="${hotel.image}" alt="${hotel.name}" class="option-image" onerror="this.src='https://via.placeholder.com/400x300?text=${hotel.name}'">
                <div>
                    <h3 class="text-sm font-bold text-gray-900 mb-0.5">${hotel.name}</h3>
                    <p class="text-xs text-gray-500">${hotel.priceRange}</p>
                </div>
            </div>
        `).join('');
    }

    function selectHotelType(hotelId) {
        selectedHotelType = hotelId;
        hotelTypes.forEach(hotel => {
            const card = document.getElementById(`hotel-${hotel.id}`);
            if (card) {
                card.classList.toggle('selected', hotel.id === hotelId);
            }
        });
        updateSummary();
        checkContinueButton();
    }

    function updateSummary() {
        const summary = document.getElementById('selectionSummary');
        const hotelName = document.getElementById('hotelTypeName');
        const priceRangeDisplay = document.getElementById('priceRangeDisplay');

        if (selectedHotelType) {
            const hotel = hotelTypes.find(h => h.id === selectedHotelType);
            hotelName.textContent = hotel.name;

            // Show price range if available
            if (hotel.minPrice && hotel.maxPrice) {
                priceRangeDisplay.textContent = `₹${hotel.minPrice.toLocaleString()} - ₹${hotel.maxPrice.toLocaleString()}`;
            } else {
                priceRangeDisplay.textContent = hotel.priceRange;
            }

        } else {
            hotelName.textContent = '-';
            priceRangeDisplay.textContent = '₹0 - ₹0';
        }
    }

    function checkContinueButton() {
        const btn = document.getElementById('continueBtn');
        if (selectedHotelType) {
            btn.disabled = false;
        } else {
            btn.disabled = true;
        }
    }

    function continueToTravel() {
        if (!selectedHotelType) {
            alert('Please select an accommodation type before continuing');
            return;
        }

        const hotel = hotelTypes.find(h => h.id === selectedHotelType);

        // Save hotel selection to localStorage
        const bookingData = {
            hotelType: selectedHotelType,
            hotelName: hotel.name,
            hotelPriceRange: hotel.priceRange,
            hotelMinPrice: hotel.minPrice,
            hotelMaxPrice: hotel.maxPrice
        };
        localStorage.setItem('hotelSelection', JSON.stringify(bookingData));

        // Navigate to travel selection page
        window.location.href = 'travel.php';
    }

    // Initialize
    renderHotelTypes();
    checkContinueButton();
    updateSummary();
</script>

<script src="includes/mobile-menu.js"></script>

</body>

</html>

