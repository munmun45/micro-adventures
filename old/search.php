<?php
// Get destination from URL parameter or default to "Odisha"
$destination = isset($_GET['destination']) && !empty($_GET['destination']) ? htmlspecialchars($_GET['destination']) : 'Odisha';
$pageTitle = $destination . ' - Search Results | TravelApp';
$pageCSS = 'search.css';
include 'includes/head.php';
include 'includes/navbar.php';
include 'includes/overlay.php';
include 'includes/sidebar.php';
?>

<!-- Page Header -->
<div class="pt-20 sm:pt-24 pb-4 bg-white border-b border-gray-200">
    <div class="max-w-6xl mx-auto px-4 sm:px-6">
        <h1 class="text-xl sm:text-2xl font-semibold text-gray-900">Places in <?php echo $destination; ?></h1>
        <p class="text-xs sm:text-sm text-gray-500 mt-1">Select places and activities</p>
    </div>
</div>

<!-- Places Container -->
<section class="max-w-6xl mx-auto px-4 py-6 space-y-4 pb-24 sm:pb-28" id="placesContainer">
    <!-- Places will be rendered here -->
</section>

<!-- Selection Summary Bar -->
<div id="selectionSummary" class="selection-summary">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 py-3 sm:py-4">
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 sm:gap-4">
            <div class="flex items-center justify-between sm:justify-start gap-4 sm:gap-6 flex-wrap">
                <div class="flex items-center gap-2">
                    <span class="text-xs sm:text-sm text-gray-600">Places:</span>
                    <span id="placesCount" class="text-sm sm:text-base font-bold text-gray-900 bg-gray-100 px-2 py-1 rounded">0</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs sm:text-sm text-gray-600">Activities:</span>
                    <span id="activitiesCount" class="text-sm sm:text-base font-bold text-gray-900 bg-gray-100 px-2 py-1 rounded">0</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs sm:text-sm text-gray-600">Total:</span>
                    <span id="totalPrice" class="text-base sm:text-lg font-bold text-[#009688]">₹0</span>
                </div>
            </div>
            <button id="proceedBtn" onclick="proceedToCheckout()" 
                class="w-full sm:w-auto px-6 sm:px-8 py-2.5 sm:py-3 rounded-xl bg-[#009688] text-white text-sm sm:text-base font-medium shadow-lg disabled:opacity-50 disabled:cursor-not-allowed">
                Proceed
            </button>
        </div>
    </div>
</div>

<!-- DOCUMENT MODAL -->
<div id="docModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4">
    <div class="bg-white w-full max-w-md rounded-2xl p-5 mx-4 shadow-2xl">
        <h3 class="text-lg font-semibold mb-4">Required Documents</h3>
        <div id="docContent" class="space-y-2"></div>
        <button onclick="closeDocs()" class="mt-5 w-full py-2.5 rounded-xl bg-black text-white">
            Close
        </button>
    </div>
</div>

<script>
    let selectedPlaces = new Set();
    let selectedActivities = new Map();

    const placesData = [
        {
            id: 'puri',
            name: 'Puri',
            location: 'Beach & Temple',
            image: 'https://images.unsplash.com/photo-1548013146-72479768bada',
            disabled: false,
            entryTicket: '₹50',
            entryPrice: 50,
            totalTime: '6-8 hrs',
            safety: 'High',
            bestTime: 'Morning',
            activities: [
                { name: 'Beach Activities', image: 'https://images.unsplash.com/photo-1500534314209-a25ddb2bd429', time: '6:00 AM – 7:00 PM', price: '₹200', priceValue: 200, status: 'Optional', disabled: false },
                { name: 'Temple Visit', image: 'https://images.unsplash.com/photo-1589182373726-e4f658ab50f0', time: '5:00 AM – 9:00 PM', price: 'FREE', priceValue: 0, status: 'Included', disabled: false },
                { name: 'Boating', image: 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e', time: '8:00 AM – 5:00 PM', price: '₹150', priceValue: 150, status: 'Optional', disabled: true }
            ],
            documents: ["Valid Photo ID (Aadhaar / Passport)", "Temple Entry Pass (if applicable)", "Hotel Booking Proof"]
        },
        {
            id: 'konark',
            name: 'Konark Sun Temple',
            location: 'Heritage Site',
            image: 'https://images.unsplash.com/photo-1589182373726-e4f658ab50f0',
            disabled: false,
            entryTicket: '₹40',
            entryPrice: 40,
            totalTime: '3-4 hrs',
            safety: 'High',
            bestTime: 'Morning',
            activities: [
                { name: 'Temple Tour', image: 'https://images.unsplash.com/photo-1548013146-72479768bada', time: '6:00 AM – 8:00 PM', price: 'FREE', priceValue: 0, status: 'Included', disabled: false },
                { name: 'Light & Sound Show', image: 'https://images.unsplash.com/photo-1501785888041-af3ef285b470', time: '7:00 PM – 8:00 PM', price: '₹100', priceValue: 100, status: 'Optional', disabled: false },
                { name: 'Photography Tour', image: 'https://images.unsplash.com/photo-1500530855697-b586d89ba3ee', time: '6:00 AM – 10:00 AM', price: '₹300', priceValue: 300, status: 'Optional', disabled: true }
            ],
            documents: ["Valid Photo ID", "Entry Ticket", "Camera Permit (for professional photography)"]
        },
        {
            id: 'chilika',
            name: 'Chilika Lake',
            location: 'Lake & Wildlife',
            image: 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e',
            disabled: false,
            entryTicket: '₹30',
            entryPrice: 30,
            totalTime: '4-5 hrs',
            safety: 'High',
            bestTime: 'Morning',
            activities: [
                { name: 'Boat Safari', image: 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e', time: '6:00 AM – 5:00 PM', price: '₹500', priceValue: 500, status: 'Optional', disabled: false },
                { name: 'Bird Watching', image: 'https://images.unsplash.com/photo-1500530855697-b586d89ba3ee', time: '6:00 AM – 10:00 AM', price: '₹200', priceValue: 200, status: 'Optional', disabled: false }
            ],
            documents: ["Valid Photo ID", "Entry Permit", "Binoculars (optional)"]
        },
        {
            id: 'bhubaneswar',
            name: 'Bhubaneswar',
            location: 'Temple City',
            image: 'https://images.unsplash.com/photo-1593693397690-362cb9666fc2',
            disabled: false,
            entryTicket: '₹20',
            entryPrice: 20,
            totalTime: '5-6 hrs',
            safety: 'High',
            bestTime: 'Morning',
            activities: [
                { name: 'Temple Circuit Tour', image: 'https://images.unsplash.com/photo-1548013146-72479768bada', time: '6:00 AM – 8:00 PM', price: 'FREE', priceValue: 0, status: 'Included', disabled: false },
                { name: 'Heritage Walk', image: 'https://images.unsplash.com/photo-1501785888041-af3ef285b470', time: '7:00 AM – 9:00 AM', price: '₹150', priceValue: 150, status: 'Optional', disabled: false },
                { name: 'Museum Visit', image: 'https://images.unsplash.com/photo-1589182373726-e4f658ab50f0', time: '10:00 AM – 5:00 PM', price: '₹50', priceValue: 50, status: 'Optional', disabled: true }
            ],
            documents: ["Valid Photo ID", "Entry Ticket"]
        },
        {
            id: 'dhauli',
            name: 'Dhauli Hill',
            location: 'Historical Site',
            image: 'https://images.unsplash.com/photo-1501785888041-af3ef285b470',
            disabled: false,
            entryTicket: 'FREE',
            entryPrice: 0,
            totalTime: '2-3 hrs',
            safety: 'High',
            bestTime: 'Evening',
            activities: [],
            documents: ["Valid Photo ID (optional)"]
        },
        {
            id: 'gopalpur',
            name: 'Gopalpur Beach',
            location: 'Beach Destination',
            image: 'https://images.unsplash.com/photo-1500534314209-a25ddb2bd429',
            disabled: true,
            entryTicket: 'FREE',
            entryPrice: 0,
            totalTime: '4-5 hrs',
            safety: 'Medium',
            bestTime: 'Morning',
            activities: [
                { name: 'Beach Activities', image: 'https://images.unsplash.com/photo-1500534314209-a25ddb2bd429', time: '6:00 AM – 7:00 PM', price: '₹100', priceValue: 100, status: 'Optional', disabled: false }
            ],
            documents: ["Valid Photo ID"]
        }
    ];

    const docsData = {};
    placesData.forEach(place => { docsData[place.id] = place.documents; });

    // Cache DOM elements for better performance
    const domCache = {
        placesCount: null,
        activitiesCount: null,
        totalPrice: null,
        proceedBtn: null,
        selectionSummary: null
    };

    function initDOMCache() {
        domCache.placesCount = document.getElementById('placesCount');
        domCache.activitiesCount = document.getElementById('activitiesCount');
        domCache.totalPrice = document.getElementById('totalPrice');
        domCache.proceedBtn = document.getElementById('proceedBtn');
        domCache.selectionSummary = document.getElementById('selectionSummary');
    }

    function togglePlace(placeId, event) {
        if (event) event.stopPropagation();
        const place = placesData.find(p => p.id === placeId);
        if (!place || place.disabled) return;
        
        const wasSelected = selectedPlaces.has(placeId);
        
        if (wasSelected) {
            selectedPlaces.delete(placeId);
            selectedActivities.delete(placeId);
        } else {
            selectedPlaces.add(placeId);
            if (!selectedActivities.has(placeId)) {
                selectedActivities.set(placeId, new Set());
            }
        }
        
        // Immediate UI update for instant feedback
        updatePlaceUI(placeId, !wasSelected);
        updateSummary();
    }

    function selectPlaceButton(placeId, event) {
        if (event) event.stopPropagation();
        togglePlace(placeId, event);
    }

    function toggleActivity(placeId, activityIndex, event) {
        if (event) event.stopPropagation();
        const place = placesData.find(p => p.id === placeId);
        if (!place) return;
        const activity = place.activities[activityIndex];
        if (!activity || activity.disabled || place.disabled) return;
        
        if (!selectedActivities.has(placeId)) {
            selectedActivities.set(placeId, new Set());
        }
        const activities = selectedActivities.get(placeId);
        const wasSelected = activities.has(activityIndex);
        
        if (wasSelected) {
            activities.delete(activityIndex);
        } else {
            activities.add(activityIndex);
            // Auto-select the place when activity is selected
            if (!selectedPlaces.has(placeId)) {
                selectedPlaces.add(placeId);
                updatePlaceUI(placeId, true);
            }
        }
        
        // Immediate UI update for instant feedback
        const actCard = document.getElementById(`activity-card-${placeId}-${activityIndex}`);
        if (actCard) {
            actCard.classList.toggle('selected', !wasSelected);
        }
        
        updateSummary();
    }

    function updatePlaceUI(placeId, isSelected) {
        // Immediate update for instant feedback
        const card = document.getElementById(`place-card-${placeId}`);
        if (card) {
            card.classList.toggle('selected', isSelected);
        }
        
        const selectBtn = document.getElementById(`select-btn-${placeId}`);
        if (selectBtn) {
            if (isSelected) {
                selectBtn.innerHTML = '<i class="bi bi-check-circle mr-2"></i>Selected';
                selectBtn.style.backgroundColor = '#16a34a';
            } else {
                selectBtn.innerHTML = 'Select Place';
                selectBtn.style.backgroundColor = '#009688';
            }
        }
    }

    function updateSelectionUI() {
        // Batch DOM updates using requestAnimationFrame for smooth performance
        requestAnimationFrame(() => {
            placesData.forEach(place => {
                const isSelected = selectedPlaces.has(place.id);
                updatePlaceUI(place.id, isSelected);
                
                place.activities.forEach((activity, actIndex) => {
                    const actCard = document.getElementById(`activity-card-${place.id}-${actIndex}`);
                    if (actCard) {
                        const actSelected = selectedActivities.get(place.id)?.has(actIndex) || false;
                        actCard.classList.toggle('selected', actSelected);
                    }
                });
            });
        });
    }

    function updateSummary() {
        let placesCount = selectedPlaces.size;
        let activitiesCount = 0;
        let totalPrice = 0;

        // Optimized calculation using Map for O(1) lookup
        const placeMap = new Map(placesData.map(p => [p.id, p]));
        
        selectedPlaces.forEach(placeId => {
            const place = placeMap.get(placeId);
            if (place && !place.disabled) {
                totalPrice += place.entryPrice;
            }
        });

        selectedActivities.forEach((activities, placeId) => {
            const place = placeMap.get(placeId);
            if (place) {
                activities.forEach(actIndex => {
                    const activity = place.activities[actIndex];
                    if (activity && !activity.disabled) {
                        activitiesCount++;
                        totalPrice += activity.priceValue;
                    }
                });
            }
        });

        // Immediate DOM updates for instant feedback
        if (domCache.placesCount) domCache.placesCount.textContent = placesCount;
        if (domCache.activitiesCount) domCache.activitiesCount.textContent = activitiesCount;
        if (domCache.totalPrice) domCache.totalPrice.textContent = `₹${totalPrice}`;
        if (domCache.proceedBtn) {
            domCache.proceedBtn.disabled = placesCount === 0 && activitiesCount === 0;
        }
        if (domCache.selectionSummary) {
            if (placesCount > 0 || activitiesCount > 0) {
                domCache.selectionSummary.classList.add('show');
            } else {
                domCache.selectionSummary.classList.remove('show');
            }
        }
    }

    function proceedToCheckout() {
        const placesCount = selectedPlaces.size;
        let activitiesCount = 0;
        selectedActivities.forEach(activities => { activitiesCount += activities.size; });
        if (placesCount === 0 && activitiesCount === 0) {
            alert('Please select at least one place or activity');
            return;
        }
        // Save selection data to localStorage
        const selectionData = {
            destination: '<?php echo $destination; ?>',
            places: Array.from(selectedPlaces),
            activities: Array.from(selectedActivities.entries()).map(([placeId, acts]) => ({
                placeId: placeId,
                activityIndices: Array.from(acts)
            })),
            totalPrice: document.getElementById('totalPrice').textContent.replace('₹', '')
        };
        localStorage.setItem('bookingSelection', JSON.stringify(selectionData));
        // Navigate to destination details page
        window.location.href = 'destination-details.php?destination=<?php echo urlencode($destination); ?>';
    }

    function explorePlace(placeId) {
        const place = placesData.find(p => p.id === placeId);
        if (place) {
            // Navigate to destination details page
            window.location.href = 'destination-details.php?destination=<?php echo urlencode($destination); ?>&place=' + placeId;
        }
    }

    function renderPlaces() {
        const container = document.getElementById('placesContainer');
        container.innerHTML = '';

        placesData.forEach(place => {
            const activitiesHTML = place.activities.length > 0 ? `
                <div class="mt-4">
                    <h4 class="text-xs font-semibold text-gray-700 mb-2.5 uppercase tracking-wide">Activities Available</h4>
                    <div class="flex gap-3 overflow-x-auto pb-2 custom-scrollbar">
                        ${place.activities.map((activity, actIndex) => `
                            <div id="activity-card-${place.id}-${actIndex}" 
                                 class="activity-card min-w-[200px] bg-white border border-gray-200 rounded-xl p-2 pl-2 flex gap-3 shadow-sm ${activity.disabled ? 'disabled-activity' : ''}"
                                 onclick="toggleActivity('${place.id}', ${actIndex}, event)">
                                <div class="activity-checkbox"></div>
                                
                                <div class="w-14 h-14 rounded-lg overflow-hidden shrink-0">
                                    <img src="${activity.image}" class="w-full h-full object-cover">
                                </div>
                                <div class="flex-1 flex flex-col justify-between min-w-0">
                                    <div>
                                        <h5 class="text-xs font-bold text-gray-900 mb-1">${activity.name}</h5>
                                        <div class="flex items-center gap-1.5 text-[10px] text-gray-500 mb-1.5">
                                            <i class="bi bi-clock text-purple-500"></i>
                                            <span>${activity.time}</span>
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-between mt-1.5">
                                        <span class="text-xs font-bold text-gray-900 flex items-center gap-1">
                                            <i class="bi bi-cash-coin text-[#009688] text-[11px]"></i>
                                            <span>${activity.price}</span>
                                        </span>
                                        <span class="text-[10px] px-2 py-0.5 rounded-full ${activity.status === 'Included' ? 'bg-green-100 text-green-700 font-semibold' : 'bg-blue-100 text-blue-700 font-semibold'}">
                                            ${activity.status}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
            ` : '';

            const placeCard = `
                <div id="place-card-${place.id}" 
                     class="place-card bg-white rounded-2xl shadow-md overflow-hidden flex flex-col sm:flex-row ${place.disabled ? 'disabled-place' : ''}"
                     onclick="togglePlace('${place.id}', event)">
                    <div class="card-checkbox"></div>
                    <div class="sm:w-1/3 relative" style="height:130px;">
                        <img src="${place.image}" class="w-full h-full object-cover">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/40 to-transparent"></div>
                        <div class="absolute bottom-4 left-4">
                            <h3 class="text-lg font-semibold text-white">${place.name}</h3>
                            <p class="text-sm text-white/90">${place.location}</p>
                        </div>
                    </div>
        <div class="sm:w-2/3 p-4 flex flex-col justify-between">
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div class="flex items-center gap-3">
                    <span class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                        <i class="bi bi-ticket-perforated"></i>
                    </span>
                    <div>
                        <div class="text-xs text-gray-500">Entry Ticket</div>
                                <div class="font-medium">${place.entryTicket}</div>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <span class="w-8 h-8 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center">
                        <i class="bi bi-clock"></i>
                    </span>
                    <div>
                        <div class="text-xs text-gray-500">Total Time</div>
                                <div class="font-medium">${place.totalTime}</div>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <span class="w-8 h-8 rounded-lg bg-green-50 text-green-600 flex items-center justify-center">
                        <i class="bi bi-shield-check"></i>
                    </span>
                    <div>
                        <div class="text-xs text-gray-500">Safety</div>
                                <div class="font-medium">${place.safety}</div>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <span class="w-8 h-8 rounded-lg bg-orange-50 text-orange-600 flex items-center justify-center">
                        <i class="bi bi-sunrise"></i>
                    </span>
                    <div>
                        <div class="text-xs text-gray-500">Best Time</div>
                                <div class="font-medium">${place.bestTime}</div>
                    </div>
                </div>
                <div class="col-span-2 mt-1">
                                <button onclick="openDocs('${place.id}'); event.stopPropagation();"
                        class="flex items-center gap-2 text-blue-600 text-sm font-medium">
                        <span class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center">
                            <i class="bi bi-file-earmark-text"></i>
                        </span>
                        Documents Needed
                    </button>
                </div>
            </div>
                        ${activitiesHTML}
                        <div class="mt-5 flex gap-3" style="display: flex; gap: 11px;">
                            <button id="select-btn-${place.id}" onclick="selectPlaceButton('${place.id}', event); event.stopPropagation();"
                                class="w-full sm:w-auto px-6 py-2.5 rounded-xl text-white text-sm font-medium ${place.disabled ? 'bg-gray-400 cursor-not-allowed' : 'bg-[#009688]'}"
                                style="background-color: ${place.disabled ? '#9ca3af' : '#009688'};"
                                ${place.disabled ? 'disabled' : ''}>
                    Select Place
                </button>
                            <button onclick="explorePlace('${place.id}'); event.stopPropagation();"
                                class="w-full sm:w-auto px-6 py-2.5 rounded-xl bg-black text-white text-sm font-medium ${place.disabled ? 'opacity-50 cursor-not-allowed' : ''}"
                                ${place.disabled ? 'disabled' : ''}>
                    Explore More
                </button>
            </div>
        </div>
    </div>
            `;

            container.innerHTML += placeCard;
        });

        updateSelectionUI();
    }

    function openDocs(placeId) {
        const modal = document.getElementById("docModal");
        const content = document.getElementById("docContent");
        content.innerHTML = "";
        if (docsData[placeId]) {
            docsData[placeId].forEach(doc => {
            content.innerHTML += `
            <div class="px-4 py-2 bg-gray-100 rounded-lg text-sm flex items-center gap-2">
                <i class="bi bi-check-circle-fill text-green-600"></i>${doc}
            </div>`;
        });
        }
        modal.classList.remove("hidden");
        modal.classList.add("flex");
    }

    function closeDocs() {
        document.getElementById("docModal").classList.add("hidden");
        document.getElementById("docModal").classList.remove("flex");
    }

    // Initialize DOM cache and render
    initDOMCache();
    renderPlaces();
</script>

<script src="includes/mobile-menu.js"></script>

</body>

</html>

