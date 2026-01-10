<?php
// Get destination and place from URL parameters
$destination = isset($_GET['destination']) && !empty($_GET['destination']) ? htmlspecialchars($_GET['destination']) : 'Odisha';
$placeId = isset($_GET['place']) ? htmlspecialchars($_GET['place']) : null;

$pageTitle = $destination . ' - Destination Details | TravelApp';
$pageCSS = 'destination-details.css';
include 'includes/head.php';
include 'includes/navbar.php';
include 'includes/overlay.php';
include 'includes/sidebar.php';
?>

<!-- Page Header -->
<div class="pt-20 sm:pt-24 pb-4 bg-white border-b border-gray-200">
    <div class="max-w-6xl mx-auto px-4 sm:px-6">
        <div class="flex items-center gap-3 mb-2">
            <button onclick="goBack()" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
                <i class="bi bi-arrow-left text-xl text-gray-700"></i>
            </button>
            <div>
                <h1 class="text-xl sm:text-2xl font-semibold text-gray-900"><?php echo $destination; ?> - Complete Guide</h1>
                <p class="text-xs sm:text-sm text-gray-500 mt-1">Detailed information about <?php echo $destination; ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Destination Details Container -->
<section class="max-w-6xl mx-auto px-4 py-6 pb-24 sm:pb-28" id="destinationDetailsContainer">
    <!-- Destination details will be rendered here -->
</section>

<script>
    function goBack() {
        window.history.back();
    }

    // Get destination from PHP
    const destination = '<?php echo $destination; ?>';
    const selectedPlaceId = '<?php echo $placeId; ?>';

    const placesData = [
        {
            id: 'puri',
            name: 'Puri',
            location: 'Beach & Temple',
            image: 'https://images.unsplash.com/photo-1548013146-72479768bada',
            images: [
                'https://images.unsplash.com/photo-1548013146-72479768bada',
                'https://images.unsplash.com/photo-1500534314209-a25ddb2bd429',
                'https://images.unsplash.com/photo-1589182373726-e4f658ab50f0',
                'https://images.unsplash.com/photo-1507525428034-b723cf961d3e',
                'https://images.unsplash.com/photo-1501785888041-af3ef285b470',
                'https://images.unsplash.com/photo-1500530855697-b586d89ba3ee'
            ],
            disabled: false,
            entryTicket: '₹50',
            entryPrice: 50,
            totalTime: '6-8 hrs',
            safety: 'High',
            bestTime: 'Morning',
            description: 'Puri is a coastal city in the state of Odisha, famous for the Jagannath Temple and beautiful beaches. It is one of the four sacred pilgrimage sites (Char Dham) for Hindus.',
            // Transport Details
            returnTransportTime: 'Anytime / Day only',
            lastTransportTiming: '9:00 PM',
            // Money & Payment
            cashRequired: 'Yes',
            upiAccepted: 'Yes',
            atmAvailability: 'Yes',
            // Scam & Warnings
            knownScams: [
                'Overcharging by auto-rickshaw drivers near temple area',
                'Fake guides offering temple tours at inflated prices',
                'Beach vendors charging exorbitant prices for beach activities'
            ],
            overchargingRisk: 'Medium',
            restrictedPhotography: 'Photography not allowed inside Jagannath Temple main sanctum. Beach photography is allowed.',
            localSensitiveIssues: 'Respect temple dress code. Avoid visiting during major festivals without prior booking.',
            // User Experience
            experienceQualityScore: 4.5,
            peacefulCrowdedNature: 'Mixed',
            recommendedTimeBuffer: 30,
            // Emergency Response
            ambulanceResponseTime: 15,
            policeReachTime: 10,
            emergencyAccessibility: 'Good',
            // Closure History
            closureHistory: 'Rare',
            commonClosureReason: 'Weather (monsoon season)',
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
            images: [
                'https://images.unsplash.com/photo-1589182373726-e4f658ab50f0',
                'https://images.unsplash.com/photo-1548013146-72479768bada',
                'https://images.unsplash.com/photo-1501785888041-af3ef285b470',
                'https://images.unsplash.com/photo-1500530855697-b586d89ba3ee',
                'https://images.unsplash.com/photo-1507525428034-b723cf961d3e',
                'https://images.unsplash.com/photo-1500534314209-a25ddb2bd429'
            ],
            disabled: false,
            entryTicket: '₹40',
            entryPrice: 40,
            totalTime: '3-4 hrs',
            safety: 'High',
            bestTime: 'Morning',
            description: 'The Konark Sun Temple is a 13th-century CE Sun temple at Konark about 35 kilometers northeast from Puri on the coastline of Odisha, India. The temple is attributed to king Narasimhadeva I of the Eastern Ganga Dynasty.',
            returnTransportTime: '6:00 PM',
            lastTransportTiming: '6:00 PM',
            cashRequired: 'Yes',
            upiAccepted: 'Limited',
            atmAvailability: 'No',
            knownScams: [
                'Guides charging extra for photography permits',
                'Parking fee scams by unauthorized personnel'
            ],
            overchargingRisk: 'Low',
            restrictedPhotography: 'Professional photography requires permit. Mobile photography allowed in most areas.',
            localSensitiveIssues: 'Maintain decorum at heritage site. No climbing on temple structures.',
            experienceQualityScore: 4.8,
            peacefulCrowdedNature: 'Mixed',
            recommendedTimeBuffer: 20,
            ambulanceResponseTime: 25,
            policeReachTime: 15,
            emergencyAccessibility: 'Medium',
            closureHistory: 'Occasional',
            commonClosureReason: 'Authority maintenance / Weather',
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
            images: [
                'https://images.unsplash.com/photo-1507525428034-b723cf961d3e',
                'https://images.unsplash.com/photo-1500530855697-b586d89ba3ee',
                'https://images.unsplash.com/photo-1548013146-72479768bada',
                'https://images.unsplash.com/photo-1500534314209-a25ddb2bd429',
                'https://images.unsplash.com/photo-1589182373726-e4f658ab50f0',
                'https://images.unsplash.com/photo-1501785888041-af3ef285b470'
            ],
            disabled: false,
            entryTicket: '₹30',
            entryPrice: 30,
            totalTime: '4-5 hrs',
            safety: 'High',
            bestTime: 'Morning',
            description: 'Chilika Lake is a brackish water lagoon, spread over the Puri, Khurda and Ganjam districts of Odisha state on the east coast of India. It is the largest coastal lagoon in India and the second largest lagoon in the world.',
            returnTransportTime: '5:00 PM',
            lastTransportTiming: '5:00 PM',
            cashRequired: 'Yes',
            upiAccepted: 'Limited',
            atmAvailability: 'No',
            knownScams: [
                'Boat operators charging extra for bird watching tours',
                'Unauthorized guides at entry point'
            ],
            overchargingRisk: 'Medium',
            restrictedPhotography: 'Photography allowed. Flash photography restricted in bird watching areas.',
            localSensitiveIssues: 'Respect wildlife. Do not disturb birds during nesting season.',
            experienceQualityScore: 4.6,
            peacefulCrowdedNature: 'Peaceful',
            recommendedTimeBuffer: 45,
            ambulanceResponseTime: 30,
            policeReachTime: 20,
            emergencyAccessibility: 'Poor',
            closureHistory: 'Occasional',
            commonClosureReason: 'Weather (cyclone warnings)',
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
            images: [
                'https://images.unsplash.com/photo-1593693397690-362cb9666fc2',
                'https://images.unsplash.com/photo-1548013146-72479768bada',
                'https://images.unsplash.com/photo-1589182373726-e4f658ab50f0',
                'https://images.unsplash.com/photo-1501785888041-af3ef285b470',
                'https://images.unsplash.com/photo-1500530855697-b586d89ba3ee',
                'https://images.unsplash.com/photo-1507525428034-b723cf961d3e'
            ],
            disabled: false,
            entryTicket: '₹20',
            entryPrice: 20,
            totalTime: '5-6 hrs',
            safety: 'High',
            bestTime: 'Morning',
            description: 'Bhubaneswar is the capital and largest city of the Indian state of Odisha. The city is a center of economic and religious importance in Eastern India. It is known as the "Temple City of India" due to its many ancient temples.',
            returnTransportTime: 'Anytime / Day only',
            lastTransportTiming: '11:00 PM',
            cashRequired: 'No',
            upiAccepted: 'Yes',
            atmAvailability: 'Yes',
            knownScams: [
                'Temple priests asking for donations aggressively',
                'Parking fee scams at temple complexes'
            ],
            overchargingRisk: 'Low',
            restrictedPhotography: 'Photography allowed in most temples. Some inner sanctums may restrict photography.',
            localSensitiveIssues: 'Follow temple dress code. Remove footwear before entering temples.',
            experienceQualityScore: 4.3,
            peacefulCrowdedNature: 'Crowded',
            recommendedTimeBuffer: 15,
            ambulanceResponseTime: 10,
            policeReachTime: 8,
            emergencyAccessibility: 'Good',
            closureHistory: 'Rare',
            commonClosureReason: 'Festival days (limited access)',
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
            images: [
                'https://images.unsplash.com/photo-1501785888041-af3ef285b470',
                'https://images.unsplash.com/photo-1548013146-72479768bada',
                'https://images.unsplash.com/photo-1500530855697-b586d89ba3ee',
                'https://images.unsplash.com/photo-1589182373726-e4f658ab50f0',
                'https://images.unsplash.com/photo-1507525428034-b723cf961d3e',
                'https://images.unsplash.com/photo-1500534314209-a25ddb2bd429'
            ],
            disabled: false,
            entryTicket: 'FREE',
            entryPrice: 0,
            totalTime: '2-3 hrs',
            safety: 'High',
            bestTime: 'Evening',
            description: 'Dhauli Hill is located on the banks of the river Daya, 8 km south of Bhubaneswar in Odisha. It is famous for the "Peace Pagoda" and the rock edicts of Emperor Ashoka.',
            returnTransportTime: 'Anytime / Day only',
            lastTransportTiming: '8:00 PM',
            cashRequired: 'No',
            upiAccepted: 'Yes',
            atmAvailability: 'Yes',
            knownScams: [],
            overchargingRisk: 'Low',
            restrictedPhotography: 'Photography allowed everywhere.',
            localSensitiveIssues: 'Maintain silence at Peace Pagoda. Respect historical significance.',
            experienceQualityScore: 4.2,
            peacefulCrowdedNature: 'Peaceful',
            recommendedTimeBuffer: 10,
            ambulanceResponseTime: 12,
            policeReachTime: 10,
            emergencyAccessibility: 'Good',
            closureHistory: 'Rare',
            commonClosureReason: 'None',
            activities: [],
            documents: ["Valid Photo ID (optional)"]
        },
        {
            id: 'gopalpur',
            name: 'Gopalpur Beach',
            location: 'Beach Destination',
            image: 'https://images.unsplash.com/photo-1500534314209-a25ddb2bd429',
            images: [
                'https://images.unsplash.com/photo-1500534314209-a25ddb2bd429',
                'https://images.unsplash.com/photo-1507525428034-b723cf961d3e',
                'https://images.unsplash.com/photo-1548013146-72479768bada',
                'https://images.unsplash.com/photo-1501785888041-af3ef285b470',
                'https://images.unsplash.com/photo-1500530855697-b586d89ba3ee',
                'https://images.unsplash.com/photo-1589182373726-e4f658ab50f0'
            ],
            disabled: true,
            entryTicket: 'FREE',
            entryPrice: 0,
            totalTime: '4-5 hrs',
            safety: 'Medium',
            bestTime: 'Morning',
            description: 'Gopalpur is a coastal town and a notified area council in Ganjam district in the Indian state of Odisha. It is a popular beach destination known for its serene beaches and water sports.',
            returnTransportTime: '6:00 PM',
            lastTransportTiming: '6:00 PM',
            cashRequired: 'Yes',
            upiAccepted: 'Limited',
            atmAvailability: 'No',
            knownScams: [
                'Water sports operators charging extra during peak season',
                'Beach vendors overcharging for food items'
            ],
            overchargingRisk: 'High',
            restrictedPhotography: 'Photography allowed. No restrictions.',
            localSensitiveIssues: 'Be cautious during monsoon. Follow lifeguard instructions for water activities.',
            experienceQualityScore: 3.8,
            peacefulCrowdedNature: 'Mixed',
            recommendedTimeBuffer: 20,
            ambulanceResponseTime: 35,
            policeReachTime: 25,
            emergencyAccessibility: 'Poor',
            closureHistory: 'Frequent',
            commonClosureReason: 'Weather (monsoon / cyclone)',
            activities: [
                { name: 'Beach Activities', image: 'https://images.unsplash.com/photo-1500534314209-a25ddb2bd429', time: '6:00 AM – 7:00 PM', price: '₹100', priceValue: 100, status: 'Optional', disabled: false }
            ],
            documents: ["Valid Photo ID"]
        }
    ];

    const docsData = {};
    placesData.forEach(place => { docsData[place.id] = place.documents; });

    function getBadgeClass(value) {
        if (value === 'Low' || value === 'Good' || value === 'Rare' || value === 'Yes') {
            return 'badge-low';
        } else if (value === 'Medium' || value === 'Mixed' || value === 'Occasional' || value === 'Limited') {
            return 'badge-medium';
        } else if (value === 'High' || value === 'Poor' || value === 'Frequent' || value === 'No') {
            return 'badge-high';
        }
        return 'badge-medium';
    }

    function getEmergencyBadgeClass(value) {
        return value === 'Good' ? 'badge-good' : (value === 'Medium' ? 'badge-medium' : 'badge-poor');
    }

    function renderStars(rating) {
        const fullStars = Math.floor(rating);
        const hasHalfStar = rating % 1 >= 0.5;
        let stars = '';
        for (let i = 0; i < fullStars; i++) {
            stars += '<i class="bi bi-star-fill"></i>';
        }
        if (hasHalfStar) {
            stars += '<i class="bi bi-star-half"></i>';
        }
        const emptyStars = 5 - Math.ceil(rating);
        for (let i = 0; i < emptyStars; i++) {
            stars += '<i class="bi bi-star"></i>';
        }
        return stars;
    }

    function renderDestinationDetails() {
        const container = document.getElementById('destinationDetailsContainer');
        container.innerHTML = '';

        const placesToShow = selectedPlaceId 
            ? placesData.filter(p => p.id === selectedPlaceId)
            : placesData;

        placesToShow.forEach(place => {
            const galleryHTML = place.images ? `
                <div class="image-gallery">
                    ${place.images.map(img => `
                        <img src="${img}" alt="${place.name}" class="gallery-image" onclick="openImageModal('${img}')">
                    `).join('')}
                </div>
            ` : '';

            const activitiesHTML = place.activities.length > 0 ? `
                <div class="info-card">
                    <h3><i class="bi bi-activity"></i> Activities Available</h3>
                    <div class="flex gap-3 overflow-x-auto pb-2 custom-scrollbar">
                        ${place.activities.map((activity, actIndex) => `
                            <div class="min-w-[200px] bg-gray-50 border border-gray-200 rounded-xl p-3 flex gap-3 shadow-sm">
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
                <!-- Hero Section -->
                <div class="destination-hero rounded-2xl overflow-hidden mb-6">
                    <img src="${place.image}" class="w-full h-full object-cover" alt="${place.name}">
                    <div class="absolute inset-0 flex items-end p-6 z-10">
                        <div>
                            <h2 class="text-3xl sm:text-4xl font-bold text-white mb-2">${place.name}</h2>
                            <p class="text-lg text-white/90">${place.location}</p>
                        </div>
                    </div>
                </div>

                ${galleryHTML ? `<div class="info-card"><h3><i class="bi bi-images"></i> Photo Gallery</h3>${galleryHTML}</div>` : ''}

                <!-- Description -->
                <div class="info-card">
                    <h3><i class="bi bi-info-circle"></i> About</h3>
                    <p class="text-gray-600 leading-relaxed">${place.description}</p>
                </div>

                <!-- Basic Information -->
                <div class="info-card">
                    <h3><i class="bi bi-card-checklist"></i> Basic Information</h3>
                    <div class="info-item">
                        <span class="info-label">Entry Ticket</span>
                        <span class="info-value">${place.entryTicket}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Total Time</span>
                        <span class="info-value">${place.totalTime}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Safety Level</span>
                        <span class="info-value">${place.safety}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Best Time to Visit</span>
                        <span class="info-value">${place.bestTime}</span>
                    </div>
                </div>

                <!-- Transport Details -->
                <div class="info-card">
                    <h3><i class="bi bi-bus-front"></i> Transport Information</h3>
                    <div class="info-item">
                        <span class="info-label">Typical Return Transport Time</span>
                        <span class="info-value">${place.returnTransportTime}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Last Transport Timing</span>
                        <span class="info-value">${place.lastTransportTiming}</span>
                    </div>
                    <div class="warning-box">
                        <h4>⚠️ Important</h4>
                        <p>Plan your return journey before ${place.lastTransportTiming} to avoid being stuck. Public transport may be limited after this time.</p>
                    </div>
                </div>

                <!-- Money & Payment -->
                <div class="info-card">
                    <h3><i class="bi bi-cash-coin"></i> Money & Payment Practicals</h3>
                    <div class="info-item">
                        <span class="info-label">Cash Required</span>
                        <span class="info-value"><span class="badge ${getBadgeClass(place.cashRequired)}">${place.cashRequired}</span></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">UPI Accepted</span>
                        <span class="info-value"><span class="badge ${getBadgeClass(place.upiAccepted)}">${place.upiAccepted}</span></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">ATM Availability Nearby</span>
                        <span class="info-value"><span class="badge ${getBadgeClass(place.atmAvailability)}">${place.atmAvailability}</span></span>
                    </div>
                    <div class="payment-methods">
                        ${place.cashRequired === 'Yes' ? '<span class="payment-badge">💵 Cash</span>' : ''}
                        ${place.upiAccepted === 'Yes' ? '<span class="payment-badge yes">📱 UPI</span>' : place.upiAccepted === 'Limited' ? '<span class="payment-badge">📱 UPI (Limited)</span>' : ''}
                        ${place.atmAvailability === 'Yes' ? '<span class="payment-badge yes">🏦 ATM Available</span>' : '<span class="payment-badge no">🏦 No ATM</span>'}
                    </div>
                </div>

                <!-- Scam & Warnings -->
                <div class="info-card">
                    <h3><i class="bi bi-shield-exclamation"></i> Scam & Local Warning Details</h3>
                    <div class="info-item">
                        <span class="info-label">Overcharging Risk</span>
                        <span class="info-value"><span class="badge ${getBadgeClass(place.overchargingRisk)}">${place.overchargingRisk}</span></span>
                    </div>
                    ${place.knownScams && place.knownScams.length > 0 ? `
                        <div class="mt-4">
                            <h4 class="text-sm font-semibold text-gray-700 mb-2">Known Tourist Scams:</h4>
                            <ul class="scam-list">
                                ${place.knownScams.map(scam => `<li>${scam}</li>`).join('')}
                            </ul>
                        </div>
                    ` : ''}
                    <div class="mt-4">
                        <h4 class="text-sm font-semibold text-gray-700 mb-2">Restricted Photography Areas:</h4>
                        <p class="text-sm text-gray-600">${place.restrictedPhotography}</p>
                    </div>
                    <div class="mt-4">
                        <h4 class="text-sm font-semibold text-gray-700 mb-2">Local Sensitive Issues:</h4>
                        <p class="text-sm text-gray-600">${place.localSensitiveIssues}</p>
                    </div>
                </div>

                <!-- User Experience -->
                <div class="info-card">
                    <h3><i class="bi bi-star"></i> User Experience & Quality Score</h3>
                    <div class="info-item">
                        <span class="info-label">Experience Quality Score</span>
                        <span class="info-value">
                            <span class="rating-stars">${renderStars(place.experienceQualityScore)}</span>
                            <span class="ml-2 font-bold">${place.experienceQualityScore}/5</span>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Peaceful / Crowded Nature</span>
                        <span class="info-value"><span class="badge ${getBadgeClass(place.peacefulCrowdedNature)}">${place.peacefulCrowdedNature}</span></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Recommended Time Buffer</span>
                        <span class="info-value">${place.recommendedTimeBuffer} minutes</span>
                    </div>
                </div>

                <!-- Emergency Response -->
                <div class="emergency-card">
                    <h3><i class="bi bi-telephone"></i> Emergency Response Time</h3>
                    <div class="emergency-item">
                        <span class="info-label">Average Ambulance Response Time</span>
                        <span class="info-value">${place.ambulanceResponseTime} mins</span>
                    </div>
                    <div class="emergency-item">
                        <span class="info-label">Average Police Reach Time</span>
                        <span class="info-value">${place.policeReachTime} mins</span>
                    </div>
                    <div class="emergency-item">
                        <span class="info-label">Emergency Accessibility Level</span>
                        <span class="info-value"><span class="badge ${getEmergencyBadgeClass(place.emergencyAccessibility)}">${place.emergencyAccessibility}</span></span>
                    </div>
                </div>

                <!-- Closure History -->
                <div class="info-card">
                    <h3><i class="bi bi-calendar-x"></i> Last-Minute Closure History</h3>
                    <div class="info-item">
                        <span class="info-label">History of Sudden Closures</span>
                        <span class="info-value"><span class="badge ${getBadgeClass(place.closureHistory)}">${place.closureHistory}</span></span>
                    </div>
                    <div class="closure-history">
                        <h4>Common Closure Reason</h4>
                        <p>${place.commonClosureReason}</p>
                    </div>
                </div>

                ${activitiesHTML}

                <!-- Documents -->
                <div class="info-card">
                    <h3><i class="bi bi-file-earmark-text"></i> Required Documents</h3>
                    <div class="space-y-2">
                        ${place.documents.map(doc => `
                            <div class="px-4 py-2 bg-gray-100 rounded-lg text-sm flex items-center gap-2">
                                <i class="bi bi-check-circle-fill text-green-600"></i>${doc}
                            </div>
                        `).join('')}
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex gap-3 mt-6">
                    <button onclick="goToBooking('${place.id}')"
                        class="flex-1 px-6 py-3 rounded-xl bg-[#009688] text-white text-base font-semibold hover:bg-[#00796b] transition-colors shadow-lg">
                        <i class="bi bi-calendar-check mr-2"></i>Book Now
                    </button>
                    <button onclick="goToSearch('${place.id}')"
                        class="px-6 py-3 rounded-xl bg-gray-200 text-gray-700 text-base font-semibold hover:bg-gray-300 transition-colors">
                        <i class="bi bi-arrow-left mr-2"></i>Back to Search
                    </button>
                </div>
            `;

            container.innerHTML += placeCard;
        });
    }

    function openImageModal(imageSrc) {
        // Simple image modal (can be enhanced)
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 bg-black/90 z-50 flex items-center justify-center p-4';
        modal.onclick = () => modal.remove();
        modal.innerHTML = `
            <img src="${imageSrc}" class="max-w-full max-h-full object-contain" onclick="event.stopPropagation()">
            <button onclick="this.parentElement.remove()" class="absolute top-4 right-4 text-white text-2xl">×</button>
        `;
        document.body.appendChild(modal);
    }

    function goToBooking(placeId) {
        const selectionData = {
            destination: destination,
            places: [placeId],
            activities: [],
            totalPrice: '0'
        };
        localStorage.setItem('bookingSelection', JSON.stringify(selectionData));
        window.location.href = 'booking.php';
    }

    function goToSearch(placeId) {
        window.location.href = 'search.php?destination=' + encodeURIComponent(destination);
    }

    // Initialize
    renderDestinationDetails();
</script>

<script src="includes/mobile-menu.js"></script>

</body>

</html>
