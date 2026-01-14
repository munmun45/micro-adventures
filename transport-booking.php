<?php
require_once 'config.php';
$conn = getDBConnection();

// Get selected places and activities from URL or session
$selected_places = isset($_GET['places']) ? explode(',', $_GET['places']) : [];
$places_price_min = isset($_GET['places_price_min']) ? floatval($_GET['places_price_min']) : 0;
$places_price_max = isset($_GET['places_price_max']) ? floatval($_GET['places_price_max']) : 0;

// Get activities - can be JSON string or comma-separated
$selected_activities = [];
$activities_price_min = isset($_GET['activities_price_min']) ? floatval($_GET['activities_price_min']) : 0;
$activities_price_max = isset($_GET['activities_price_max']) ? floatval($_GET['activities_price_max']) : 0;

if (isset($_GET['activities'])) {
    // Try to decode as JSON first
    $activities_data = json_decode($_GET['activities'], true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($activities_data)) {
        // It's JSON format with activity details
        foreach ($activities_data as $activity) {
            if (isset($activity['id'])) {
                $selected_activities[] = $activity['id'];
            }
        }
    } else {
        // It's comma-separated format
        $selected_activities = explode(',', $_GET['activities']);
    }
}

// Get city information from selected places
$selected_city = null;
$selected_state = null;
$places_info = [];

if (!empty($selected_places)) {
    $place_ids = array_map('intval', array_filter($selected_places));
    if (!empty($place_ids)) {
        $place_ids_str = implode(',', $place_ids);
        $places_query = "SELECT p.id, p.name as place_name, d.id as city_id, d.name as city_name, 
                        d.latitude, d.longitude, s.id as state_id, s.name as state_name
                        FROM places p
                        JOIN districts d ON p.city_id = d.id
                        JOIN states s ON d.state_id = s.id
                        WHERE p.id IN ($place_ids_str) AND p.status = 'active'
                        LIMIT 1";
        $places_result = $conn->query($places_query);
        if ($places_result && $places_result->num_rows > 0) {
            $selected_city = $places_result->fetch_assoc();
            $selected_state = [
                'id' => $selected_city['state_id'],
                'name' => $selected_city['state_name']
            ];
        }
        
        // Get all selected places info with ticket prices
        $all_places_query = "SELECT p.id, p.name as place_name, p.ticket_price_min, p.ticket_price_max, d.name as city_name, s.name as state_name
                            FROM places p
                            JOIN districts d ON p.city_id = d.id
                            JOIN states s ON d.state_id = s.id
                            WHERE p.id IN ($place_ids_str) AND p.status = 'active'";
        $all_places_result = $conn->query($all_places_query);
        if ($all_places_result) {
            while ($row = $all_places_result->fetch_assoc()) {
                $places_info[] = $row;
            }
        }
    }
}

// Get all states and cities
$states = [];
$states_query = "SELECT id, name FROM states WHERE status = 'active' ORDER BY name";
$states_result = $conn->query($states_query);
if ($states_result) {
    while ($row = $states_result->fetch_assoc()) {
        $states[] = $row;
    }
}

// Get all transport types
$transport_types = [];
$transport_query = "SELECT * FROM transport_types WHERE status = 'active' ORDER BY name";
$transport_result = $conn->query($transport_query);
if ($transport_result) {
    while ($row = $transport_result->fetch_assoc()) {
        $transport_types[] = $row;
    }
}

// Get transport price rules
$price_rules = [];
$price_rules_query = "SELECT tpr.*, tt.name as transport_name 
                      FROM transport_price_rules tpr
                      JOIN transport_types tt ON tpr.transport_type_id = tt.id
                      WHERE tpr.is_active = 'yes' AND tt.status = 'active'
                      ORDER BY tt.name, tpr.sub_type";
$price_rules_result = $conn->query($price_rules_query);
if ($price_rules_result) {
    while ($row = $price_rules_result->fetch_assoc()) {
        if (!isset($price_rules[$row['transport_type_id']])) {
            $price_rules[$row['transport_type_id']] = [];
        }
        $price_rules[$row['transport_type_id']][] = $row;
    }
}

$pageTitle = 'Transport Booking | TravelApp';
$pageCSS = 'transport-booking.css';
include 'includes/head.php';
include 'includes/navbar.php';
include 'includes/overlay.php';
include 'includes/sidebar.php';
?>


<!-- Page Header -->
<div class="pt-14 sm:pt-16 pb-4 bg-white border-b border-gray-200">
    <div class="max-w-6xl mx-auto px-4 sm:px-6">
        <div class="flex items-center justify-between mb-2">
            <div>
                <h1 class="text-lg sm:text-xl font-bold text-gray-900">Transport Booking</h1>
                <p class="text-xs sm:text-sm text-gray-500 mt-1">Select your location and transport type</p>
            </div>
            <a href="search.php" class="text-sm text-[#009688] hover:underline flex items-center gap-1">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>
</div>

<!-- Main Content -->
<section class="max-w-6xl mx-auto px-4 py-6 pb-24">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Left Column: Location Selection -->
        <div class="space-y-4 sm:space-y-6">
            <!-- My Location -->
            <div class="bg-white rounded-2xl shadow-md p-4 sm:p-5">
                <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                    <i class="bi bi-geo-alt-fill text-[#009688]"></i> My Location
                </h2>
                <div id="location-status" class="mb-4">
                    <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                        <i class="bi bi-geo-alt text-gray-400 text-xl"></i>
                        <div class="flex-1">
                            <p class="text-sm font-semibold text-gray-700">Detecting location...</p>
                            <p class="text-xs text-gray-500">Please allow location access</p>
                        </div>
                        <button onclick="getMyLocation()" class="px-4 py-2 bg-[#009688] text-white rounded-lg text-sm font-semibold">
                            <i class="bi bi-crosshair"></i> Detect
                        </button>
                    </div>
                </div>
                <div id="my-location-info" class="hidden">
                    <div class="p-3 bg-green-50 border border-green-200 rounded-lg">
                        <p class="text-sm font-semibold text-gray-900 mb-1">
                            <i class="bi bi-check-circle text-green-600"></i> Location Detected
                        </p>
                        <p class="text-xs text-gray-600" id="location-address"></p>
                        <input type="hidden" id="my-lat" value="">
                        <input type="hidden" id="my-lng" value="">
                    </div>
                </div>
            </div>

            <!-- Selected Places Info -->
            <?php if (!empty($places_info)): ?>
            <div class="bg-white rounded-2xl shadow-md p-4 sm:p-5">
                <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                    <i class="bi bi-map-fill text-[#009688]"></i> Selected Places
                </h2>
                <div class="space-y-2">
                    <?php foreach ($places_info as $place): ?>
                        <div class="p-3 bg-gray-50 rounded-lg">
                            <p class="text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($place['place_name']); ?></p>
                            <p class="text-xs text-gray-600"><?php echo htmlspecialchars($place['city_name']); ?>, <?php echo htmlspecialchars($place['state_name']); ?></p>
                            <?php 
                            $price_min = $place['ticket_price_min'] ?? 0;
                            $price_max = $place['ticket_price_max'] ?? 0;
                            if ($price_min > 0 || $price_max > 0): ?>
                                <div class="mt-2 p-2 bg-[#009688]/10 rounded-lg">
                                    <p class="text-xs text-gray-600 mb-0.5">Entry Ticket:</p>
                                    <p class="text-xs font-bold text-[#009688]">
                                        <?php 
                                        if ($price_min == $price_max) {
                                            echo '₹' . number_format($price_min, 2);
                                        } else {
                                            echo '₹' . number_format($price_min, 2) . ' - ₹' . number_format($price_max, 2);
                                        }
                                        ?>
                                    </p>
                                </div>
                            <?php else: ?>
                                <p class="text-xs text-green-600 font-semibold mt-1">FREE Entry</p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Destination Selection -->
            <div class="bg-white rounded-2xl shadow-md p-4 sm:p-5">
                <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                    <i class="bi bi-pin-map-fill text-[#009688]"></i> Destination
                </h2>
                <?php if ($selected_city): ?>
                    <!-- Auto-selected from places -->
                    <div class="p-4 bg-green-50 border border-green-200 rounded-lg">
                        <p class="text-sm font-semibold text-gray-900 mb-1">
                            <i class="bi bi-check-circle text-green-600"></i> Auto-selected from your places
                        </p>
                        <p class="text-base font-bold text-gray-900"><?php echo htmlspecialchars($selected_city['city_name']); ?>, <?php echo htmlspecialchars($selected_city['state_name']); ?></p>
                        <p class="text-xs text-gray-600 mt-2">Coordinates: <?php echo htmlspecialchars($selected_city['latitude']); ?>, <?php echo htmlspecialchars($selected_city['longitude']); ?></p>
                        <input type="hidden" id="dest-lat" value="<?php echo htmlspecialchars($selected_city['latitude']); ?>">
                        <input type="hidden" id="dest-lng" value="<?php echo htmlspecialchars($selected_city['longitude']); ?>">
                        <input type="hidden" id="dest-city-id" value="<?php echo htmlspecialchars($selected_city['city_id']); ?>">
                    </div>
                    <button onclick="showDestinationSelector()" class="mt-3 w-full px-4 py-2 border border-gray-300 rounded-lg text-sm font-semibold text-gray-700 hover:bg-gray-50">
                        <i class="bi bi-pencil"></i> Change Destination
                    </button>
                <?php else: ?>
                    <!-- Manual selection if no places selected -->
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">State</label>
                            <select id="state-select" onchange="loadCities(this.value)" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#009688] focus:border-[#009688]">
                                <option value="">Select State</option>
                                <?php foreach ($states as $state): ?>
                                    <option value="<?php echo $state['id']; ?>"><?php echo htmlspecialchars($state['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">City</label>
                            <select id="city-select" onchange="loadCityDetails(this.value)" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#009688] focus:border-[#009688]" disabled>
                                <option value="">Select State First</option>
                            </select>
                        </div>
                        <div id="city-details" class="hidden mt-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                            <p class="text-sm font-semibold text-gray-900 mb-1">City Information</p>
                            <p class="text-xs text-gray-600" id="city-info"></p>
                            <input type="hidden" id="dest-lat" value="">
                            <input type="hidden" id="dest-lng" value="">
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Hidden selector for changing destination -->
                <div id="destination-selector" class="hidden mt-4 space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">State</label>
                        <select id="state-select-alt" onchange="loadCitiesAlt(this.value)" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#009688] focus:border-[#009688]">
                            <option value="">Select State</option>
                            <?php foreach ($states as $state): ?>
                                <option value="<?php echo $state['id']; ?>"><?php echo htmlspecialchars($state['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">City</label>
                        <select id="city-select-alt" onchange="loadCityDetailsAlt(this.value)" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#009688] focus:border-[#009688]" disabled>
                            <option value="">Select State First</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Transport Types -->
        <div class="space-y-4 sm:space-y-6">
            <div class="bg-white rounded-2xl shadow-md p-4 sm:p-5">
                <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                    <i class="bi bi-truck text-[#009688]"></i> Transport Options
                </h2>
                <div id="distance-info" class="mb-3 sm:mb-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg hidden">
                    <p class="text-sm font-semibold text-gray-900">
                        <i class="bi bi-route text-yellow-600"></i> Distance: <span id="distance-km">0</span> km
                    </p>
                </div>
                <div class="grid grid-cols-1 gap-4" id="transport-list">
                    <?php foreach ($transport_types as $transport): ?>
                        <?php if (isset($price_rules[$transport['id']]) && !empty($price_rules[$transport['id']])): ?>
                            <div class="transport-card bg-white border-2 border-gray-200 rounded-2xl p-4 sm:p-5 shadow-sm hover:shadow-md hover:border-[#009688] transition-all" data-transport-id="<?php echo $transport['id']; ?>">
                                <div class="flex items-center gap-3 sm:gap-4 mb-3 sm:mb-4">
                                    <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-xl overflow-hidden bg-gray-100 shrink-0 shadow-md">
                                        <img src="<?php echo htmlspecialchars($transport['image'] ?: 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64'); ?>" 
                                             alt="<?php echo htmlspecialchars($transport['name']); ?>"
                                             class="w-full h-full object-cover">
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h3 class="text-base sm:text-lg font-bold text-gray-900 mb-0.5"><?php echo htmlspecialchars($transport['name']); ?></h3>
                                        <p class="text-xs text-gray-500">Select an option below</p>
                                    </div>
                                </div>
                                
                                <div class="space-y-2 sm:space-y-3">
                                    <?php foreach ($price_rules[$transport['id']] as $index => $rule): ?>
                                        <div class="transport-option p-3 sm:p-4 bg-gradient-to-r from-gray-50 to-white border border-gray-200 rounded-xl hover:border-[#009688] hover:shadow-md transition-all cursor-pointer" 
                                             data-rule-id="<?php echo $rule['id']; ?>"
                                             data-min-cost="<?php echo $rule['min_cost_per_km']; ?>"
                                             data-max-cost="<?php echo $rule['max_cost_per_km']; ?>"
                                             onclick="selectTransportOption(this)">
                                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 sm:gap-4">
                                                <div class="flex-1 min-w-0">
                                                    <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-2 mb-1">
                                                        <h4 class="text-sm sm:text-base font-bold text-gray-900"><?php echo htmlspecialchars($rule['sub_type']); ?></h4>
                                                        <?php if ($rule['notes']): ?>
                                                            <span class="text-xs px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full self-start sm:self-auto"><?php echo htmlspecialchars($rule['notes']); ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <p class="text-xs text-gray-500">
                                                        <i class="bi bi-currency-rupee"></i>
                                                        <?php echo number_format($rule['min_cost_per_km'], 2); ?> - <?php echo number_format($rule['max_cost_per_km'], 2); ?> per km
                                                    </p>
                                                </div>
                                                <div class="text-left sm:text-right sm:ml-4 shrink-0">
                                                    <p class="text-lg sm:text-xl font-bold text-[#009688] price-display mb-0.5">₹0</p>
                                                    <p class="text-xs text-gray-500">Total Price</p>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Selection Summary Navbar -->
<div id="selection-navbar" class="fixed bottom-0 left-0 right-0 bg-white border-t-2 border-[#009688] shadow-2xl z-50 transform translate-y-full transition-transform duration-300">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-3 sm:py-4">
        <!-- Mobile: Stacked Layout -->
        <div class="flex flex-col md:hidden gap-3">
            <!-- Row 1: Transport Info -->
            <div class="flex items-center gap-2 text-xs justify-center overflow-x-auto pb-1">
                <div class="flex items-center gap-1.5 bg-gray-50 px-2 py-1 rounded-lg shrink-0">
                    <i class="bi bi-truck text-[#009688] text-sm"></i>
                    <span class="font-semibold text-gray-700" id="selected-transport-name-mobile">
                        <span class="text-gray-400">No transport selected</span>
                    </span>
                </div>
                <div class="flex items-center gap-1.5 bg-gray-50 px-2 py-1 rounded-lg shrink-0">
                    <i class="bi bi-route text-[#009688] text-sm"></i>
                    <span class="font-semibold text-gray-700">
                        <span id="distance-display-mobile" class="text-[#009688]">0</span> km
                    </span>
                </div>
            </div>
            <!-- Row 2: Price and Button -->
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-1.5 bg-[#009688]/10 px-3 py-2 rounded-lg flex-1">
                    <i class="bi bi-cash-coin text-[#009688] text-sm"></i>
                    <span class="font-semibold text-gray-900 text-sm" id="price-display-mobile">₹0</span>
                </div>
                <button id="next-button-mobile" 
                        onclick="proceedToNext()" 
                        disabled
                        class="px-6 py-2.5 bg-[#009688] text-white rounded-lg font-semibold text-sm transition-colors flex items-center justify-center gap-2 shadow-lg shrink-0 opacity-50 cursor-not-allowed">
                    Next <i class="bi bi-arrow-right"></i>
                </button>
            </div>
        </div>
        
        <!-- Tablet & Desktop: Single Row Layout -->
        <div class="hidden md:flex items-center justify-between gap-4 lg:gap-6">
            <!-- Transport Info -->
            <div class="flex items-center gap-3 lg:gap-4 text-sm lg:text-base flex-1">
                <div class="flex items-center gap-2 bg-gray-50 px-3 py-1.5 rounded-lg">
                    <i class="bi bi-truck text-[#009688] text-base"></i>
                    <span class="font-semibold text-gray-700" id="selected-transport-name-desktop">
                        <span class="text-gray-400">No transport selected</span>
                    </span>
                </div>
                <div class="flex items-center gap-2 bg-gray-50 px-3 py-1.5 rounded-lg">
                    <i class="bi bi-route text-[#009688] text-base"></i>
                    <span class="font-semibold text-gray-700">
                        <span id="distance-display-desktop" class="text-[#009688]">0</span> km
                    </span>
                </div>
            </div>
            
            <!-- Price and Button -->
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2 bg-[#009688]/10 px-4 py-2 rounded-lg">
                    <i class="bi bi-cash-coin text-[#009688] text-base"></i>
                    <span class="font-semibold text-gray-900 text-base lg:text-lg" id="price-display-desktop">₹0</span>
                </div>
                <button id="next-button-desktop" 
                        onclick="proceedToNext()" 
                        disabled
                        class="px-6 lg:px-8 py-2.5 lg:py-3 bg-[#009688] text-white rounded-lg font-semibold text-sm lg:text-base transition-colors flex items-center justify-center gap-2 shadow-lg hover:bg-[#00796b] shrink-0 opacity-50 cursor-not-allowed">
                    Next <i class="bi bi-arrow-right"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let myLocation = { lat: null, lng: null };
let destination = { lat: null, lng: null, cityId: null };
let distance = 0;
let selectedTransport = { ruleId: null, price: null, priceText: null };

// Auto-detect location on page load
document.addEventListener('DOMContentLoaded', function() {
    getMyLocation();
    
    // Auto-set destination if city is pre-selected
    <?php if ($selected_city): ?>
    destination.lat = parseFloat(document.getElementById('dest-lat').value);
    destination.lng = parseFloat(document.getElementById('dest-lng').value);
    destination.cityId = document.getElementById('dest-city-id').value;
    
    // Calculate distance if my location is available
    if (myLocation.lat && myLocation.lng) {
        calculateDistance();
    }
    <?php endif; ?>
    
    // Initialize price display
    updatePriceDisplay();
});

// Get user's current location
function getMyLocation() {
    const statusDiv = document.getElementById('location-status');
    const infoDiv = document.getElementById('my-location-info');
    
    if (!navigator.geolocation) {
        statusDiv.innerHTML = '<div class="p-3 bg-red-50 border border-red-200 rounded-lg"><p class="text-sm text-red-700">Geolocation is not supported by your browser</p></div>';
        return;
    }
    
    statusDiv.innerHTML = '<div class="flex items-center gap-3 p-3 bg-blue-50 rounded-lg"><i class="bi bi-hourglass-split text-blue-500 text-xl animate-spin"></i><div class="flex-1"><p class="text-sm font-semibold text-gray-700">Getting location...</p><p class="text-xs text-gray-500">Please wait</p></div></div>';
    
    navigator.geolocation.getCurrentPosition(
        function(position) {
            myLocation.lat = position.coords.latitude;
            myLocation.lng = position.coords.longitude;
            
            document.getElementById('my-lat').value = myLocation.lat;
            document.getElementById('my-lng').value = myLocation.lng;
            
            // Reverse geocode to get address
            reverseGeocode(myLocation.lat, myLocation.lng);
            
            statusDiv.classList.add('hidden');
            infoDiv.classList.remove('hidden');
            
            // If destination is selected, calculate distance
            if (destination.lat && destination.lng) {
                calculateDistance();
            }
        },
        function(error) {
            let errorMsg = 'Unable to get your location. ';
            switch(error.code) {
                case error.PERMISSION_DENIED:
                    errorMsg += 'Location access denied.';
                    break;
                case error.POSITION_UNAVAILABLE:
                    errorMsg += 'Location information unavailable.';
                    break;
                case error.TIMEOUT:
                    errorMsg += 'Location request timed out.';
                    break;
            }
            statusDiv.innerHTML = `<div class="p-3 bg-red-50 border border-red-200 rounded-lg"><p class="text-sm text-red-700">${errorMsg}</p><button onclick="getMyLocation()" class="mt-2 px-4 py-2 bg-[#009688] text-white rounded-lg text-sm">Try Again</button></div>`;
        }
    );
}

// Reverse geocode to get address
function reverseGeocode(lat, lng) {
    // Using Nominatim (OpenStreetMap) for reverse geocoding
    fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`)
        .then(response => response.json())
        .then(data => {
            const address = data.display_name || `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
            document.getElementById('location-address').textContent = address;
        })
        .catch(error => {
            document.getElementById('location-address').textContent = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
        });
}

// Load cities when state is selected
function loadCities(stateId) {
    const citySelect = document.getElementById('city-select');
    citySelect.innerHTML = '<option value="">Loading cities...</option>';
    citySelect.disabled = true;
    
    if (!stateId) {
        citySelect.innerHTML = '<option value="">Select State First</option>';
        return;
    }
    
    fetch(`get-cities.php?state_id=${stateId}`)
        .then(response => response.json())
        .then(data => {
            citySelect.innerHTML = '<option value="">Select City</option>';
            data.forEach(city => {
                const option = document.createElement('option');
                option.value = city.id;
                option.textContent = city.name;
                option.dataset.lat = city.latitude || '';
                option.dataset.lng = city.longitude || '';
                citySelect.appendChild(option);
            });
            citySelect.disabled = false;
        })
        .catch(error => {
            citySelect.innerHTML = '<option value="">Error loading cities</option>';
            console.error('Error:', error);
        });
}

// Load city details when city is selected
function loadCityDetails(cityId) {
    const citySelect = document.getElementById('city-select');
    const selectedOption = citySelect.options[citySelect.selectedIndex];
    const cityDetails = document.getElementById('city-details');
    const cityInfo = document.getElementById('city-info');
    
    if (!cityId || !selectedOption) {
        cityDetails.classList.add('hidden');
        destination.lat = null;
        destination.lng = null;
        destination.cityId = null;
        return;
    }
    
    const lat = parseFloat(selectedOption.dataset.lat);
    const lng = parseFloat(selectedOption.dataset.lng);
    
    if (lat && lng) {
        destination.lat = lat;
        destination.lng = lng;
        destination.cityId = cityId;
        
        document.getElementById('dest-lat').value = lat;
        document.getElementById('dest-lng').value = lng;
        
        cityInfo.textContent = `${selectedOption.textContent} (${lat.toFixed(6)}, ${lng.toFixed(6)})`;
        cityDetails.classList.remove('hidden');
        
        // Calculate distance if my location is available
        if (myLocation.lat && myLocation.lng) {
            calculateDistance();
        }
    } else {
        cityInfo.textContent = selectedOption.textContent + ' (Coordinates not available)';
        cityDetails.classList.remove('hidden');
    }
}

// Calculate distance between two points using Haversine formula
function calculateDistance() {
    if (!myLocation.lat || !myLocation.lng || !destination.lat || !destination.lng) {
        return;
    }
    
    const R = 6371; // Earth's radius in km
    const dLat = toRad(destination.lat - myLocation.lat);
    const dLon = toRad(destination.lng - myLocation.lng);
    
    const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
              Math.cos(toRad(myLocation.lat)) * Math.cos(toRad(destination.lat)) *
              Math.sin(dLon/2) * Math.sin(dLon/2);
    
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    distance = R * c; // Distance in km
    
    document.getElementById('distance-km').textContent = distance.toFixed(2);
    document.getElementById('distance-info').classList.remove('hidden');
    
    // Update all transport prices
    updateTransportPrices();
    
    // Update bottom navbar distance display
    updatePriceDisplay();
}

function toRad(degrees) {
    return degrees * (Math.PI / 180);
}

// Show destination selector
function showDestinationSelector() {
    document.getElementById('destination-selector').classList.remove('hidden');
}

// Load cities for alternative selector
function loadCitiesAlt(stateId) {
    const citySelect = document.getElementById('city-select-alt');
    citySelect.innerHTML = '<option value="">Loading cities...</option>';
    citySelect.disabled = true;
    
    if (!stateId) {
        citySelect.innerHTML = '<option value="">Select State First</option>';
        return;
    }
    
    fetch(`get-cities.php?state_id=${stateId}`)
        .then(response => response.json())
        .then(data => {
            citySelect.innerHTML = '<option value="">Select City</option>';
            data.forEach(city => {
                const option = document.createElement('option');
                option.value = city.id;
                option.textContent = city.name;
                option.dataset.lat = city.latitude || '';
                option.dataset.lng = city.longitude || '';
                citySelect.appendChild(option);
            });
            citySelect.disabled = false;
        })
        .catch(error => {
            citySelect.innerHTML = '<option value="">Error loading cities</option>';
            console.error('Error:', error);
        });
}

// Load city details for alternative selector
function loadCityDetailsAlt(cityId) {
    const citySelect = document.getElementById('city-select-alt');
    const selectedOption = citySelect.options[citySelect.selectedIndex];
    
    if (!cityId || !selectedOption) {
        return;
    }
    
    const lat = parseFloat(selectedOption.dataset.lat);
    const lng = parseFloat(selectedOption.dataset.lng);
    
    if (lat && lng) {
        destination.lat = lat;
        destination.lng = lng;
        destination.cityId = cityId;
        
        document.getElementById('dest-lat').value = lat;
        document.getElementById('dest-lng').value = lng;
        document.getElementById('dest-city-id').value = cityId;
        
        // Hide selector and show auto-selected
        document.getElementById('destination-selector').classList.add('hidden');
        document.querySelector('.bg-green-50').innerHTML = `
            <p class="text-sm font-semibold text-gray-900 mb-1">
                <i class="bi bi-check-circle text-green-600"></i> Destination Updated
            </p>
            <p class="text-base font-bold text-gray-900">${selectedOption.textContent}</p>
            <p class="text-xs text-gray-600 mt-2">Coordinates: ${lat.toFixed(6)}, ${lng.toFixed(6)}</p>
        `;
        
        // Calculate distance if my location is available
        if (myLocation.lat && myLocation.lng) {
            calculateDistance();
        }
    }
}

// Select transport option
function selectTransportOption(element) {
    // Remove previous selection
    document.querySelectorAll('.transport-option').forEach(opt => {
        opt.classList.remove('border-[#009688]', 'bg-[#009688]/5', 'ring-2', 'ring-[#009688]/20');
    });
    
    // Add selection to clicked option
    element.classList.add('border-[#009688]', 'bg-[#009688]/5', 'ring-2', 'ring-[#009688]/20');
    
    // Store selected transport info
    selectedTransport.ruleId = element.dataset.ruleId;
    const priceDisplay = element.querySelector('.price-display');
    selectedTransport.priceText = priceDisplay.textContent;
    
    // Extract numeric price for calculations
    const priceMatch = priceDisplay.textContent.match(/₹([\d,]+)/);
    if (priceMatch) {
        selectedTransport.price = parseInt(priceMatch[1].replace(/,/g, ''));
    }
    
    // Update selected transport name
    const transportCard = element.closest('.transport-card');
    const transportName = transportCard ? transportCard.querySelector('h3').textContent : '';
    const subType = element.querySelector('h4').textContent;
    const transportInfo = `${transportName} - ${subType}`;
    
    // Update transport name displays
    const selectedNameMobile = document.getElementById('selected-transport-name-mobile');
    const selectedNameDesktop = document.getElementById('selected-transport-name-desktop');
    if (selectedNameMobile) {
        selectedNameMobile.innerHTML = `<span class="text-[#009688]">${transportInfo}</span>`;
    }
    if (selectedNameDesktop) {
        selectedNameDesktop.innerHTML = `<span class="text-[#009688]">${transportInfo}</span>`;
    }
    
    // Show bottom navbar
    const bottomNavbar = document.getElementById('selection-navbar');
    if (bottomNavbar) {
        bottomNavbar.classList.remove('translate-y-full');
    }
    
    // Update price displays
    updatePriceDisplay();
}

// Update transport prices based on distance
function updateTransportPrices() {
    if (distance <= 0) {
        document.querySelectorAll('.price-display').forEach(display => {
            display.textContent = '₹0';
        });
        // Clear selection if distance is 0
        if (selectedTransport.ruleId) {
            selectedTransport = { ruleId: null, price: null, priceText: null };
            document.querySelectorAll('.transport-option').forEach(opt => {
                opt.classList.remove('border-[#009688]', 'bg-[#009688]/5', 'ring-2', 'ring-[#009688]/20');
            });
            updatePriceDisplay();
        }
        return;
    }
    
    document.querySelectorAll('.transport-option').forEach(option => {
        const minCost = parseFloat(option.dataset.minCost);
        const maxCost = parseFloat(option.dataset.maxCost);
        
        const minPrice = minCost * distance;
        const maxPrice = maxCost * distance;
        
        const priceDisplay = option.querySelector('.price-display');
        if (minPrice === maxPrice) {
            priceDisplay.textContent = '₹' + Math.round(minPrice).toLocaleString('en-IN');
        } else {
            priceDisplay.textContent = '₹' + Math.round(minPrice).toLocaleString('en-IN') + ' - ₹' + Math.round(maxPrice).toLocaleString('en-IN');
        }
    });
    
    // Update selected transport price if one is selected
    if (selectedTransport.ruleId) {
        const selectedOption = document.querySelector(`[data-rule-id="${selectedTransport.ruleId}"]`);
        if (selectedOption) {
            const priceDisplay = selectedOption.querySelector('.price-display');
            selectedTransport.priceText = priceDisplay.textContent;
            const priceMatch = priceDisplay.textContent.match(/₹([\d,]+)/);
            if (priceMatch) {
                selectedTransport.price = parseInt(priceMatch[1].replace(/,/g, ''));
            }
            updatePriceDisplay();
        }
    }
}

// Update price display in bottom navbar
function updatePriceDisplay() {
    const priceDisplayMobile = document.getElementById('price-display-mobile');
    const priceDisplayDesktop = document.getElementById('price-display-desktop');
    const nextButtonMobile = document.getElementById('next-button-mobile');
    const nextButtonDesktop = document.getElementById('next-button-desktop');
    const bottomNavbar = document.getElementById('selection-navbar');
    
    // Update distance display
    const distanceMobile = document.getElementById('distance-display-mobile');
    const distanceDesktop = document.getElementById('distance-display-desktop');
    if (distanceMobile) {
        distanceMobile.textContent = distance.toFixed(2);
    }
    if (distanceDesktop) {
        distanceDesktop.textContent = distance.toFixed(2);
    }
    
    if (selectedTransport.ruleId && selectedTransport.priceText) {
        // Update price displays
        if (priceDisplayMobile) {
            priceDisplayMobile.textContent = selectedTransport.priceText;
        }
        if (priceDisplayDesktop) {
            priceDisplayDesktop.textContent = selectedTransport.priceText;
        }
        
        // Enable next buttons
        if (nextButtonMobile) {
            nextButtonMobile.classList.remove('opacity-50', 'cursor-not-allowed');
            nextButtonMobile.disabled = false;
        }
        if (nextButtonDesktop) {
            nextButtonDesktop.classList.remove('opacity-50', 'cursor-not-allowed');
            nextButtonDesktop.disabled = false;
        }
        
        // Show bottom navbar
        if (bottomNavbar) {
            bottomNavbar.classList.remove('translate-y-full');
        }
    } else {
        // Reset price displays
        if (priceDisplayMobile) {
            priceDisplayMobile.textContent = '₹0';
        }
        if (priceDisplayDesktop) {
            priceDisplayDesktop.textContent = '₹0';
        }
        
        // Disable next buttons
        if (nextButtonMobile) {
            nextButtonMobile.classList.add('opacity-50', 'cursor-not-allowed');
            nextButtonMobile.disabled = true;
        }
        if (nextButtonDesktop) {
            nextButtonDesktop.classList.add('opacity-50', 'cursor-not-allowed');
            nextButtonDesktop.disabled = true;
        }
        
        // Reset selected transport name
        const selectedNameMobile = document.getElementById('selected-transport-name-mobile');
        const selectedNameDesktop = document.getElementById('selected-transport-name-desktop');
        if (selectedNameMobile) {
            selectedNameMobile.innerHTML = '<span class="text-gray-400">No transport selected</span>';
        }
        if (selectedNameDesktop) {
            selectedNameDesktop.innerHTML = '<span class="text-gray-400">No transport selected</span>';
        }
        
        // Hide bottom navbar if no selection
        if (bottomNavbar && !selectedTransport.ruleId) {
            bottomNavbar.classList.add('translate-y-full');
        }
    }
}

// Proceed to next step
function proceedToNext() {
    if (!selectedTransport.ruleId) {
        alert('Please select a transport option');
        return;
    }
    
    if (!myLocation.lat || !myLocation.lng) {
        alert('Please allow location access');
        return;
    }
    
    if (!destination.lat || !destination.lng) {
        alert('Please select a destination');
        return;
    }
    
    // Store selected transport in session/localStorage or pass via URL
    // Preserve all previous data and add transport data
    const params = new URLSearchParams();
    
    // Preserve places data
    <?php if (!empty($selected_places)): ?>
    params.append('places', '<?php echo implode(',', $selected_places); ?>');
    <?php if ($places_price_min > 0 || $places_price_max > 0): ?>
    params.append('places_price_min', '<?php echo $places_price_min; ?>');
    params.append('places_price_max', '<?php echo $places_price_max; ?>');
    <?php endif; ?>
    <?php endif; ?>
    
    // Preserve activities data
    <?php if (isset($_GET['activities'])): ?>
    params.append('activities', <?php echo json_encode($_GET['activities']); ?>);
    <?php if ($activities_price_min > 0 || $activities_price_max > 0): ?>
    params.append('activities_price_min', '<?php echo $activities_price_min; ?>');
    params.append('activities_price_max', '<?php echo $activities_price_max; ?>');
    <?php endif; ?>
    <?php endif; ?>
    
    // Add transport data
    params.append('transport_rule_id', selectedTransport.ruleId);
    params.append('distance', distance.toFixed(2));
    params.append('transport_price', selectedTransport.price || 0);
    
    // Extract transport price range if available
    const priceText = selectedTransport.priceText || '';
    const priceMatch = priceText.match(/₹([\d,]+)\s*(?:-\s*₹([\d,]+))?/);
    if (priceMatch) {
        const transportPriceMin = parseInt(priceMatch[1].replace(/,/g, ''));
        params.append('transport_price_min', transportPriceMin);
        if (priceMatch[2]) {
            const transportPriceMax = parseInt(priceMatch[2].replace(/,/g, ''));
            params.append('transport_price_max', transportPriceMax);
        } else {
            params.append('transport_price_max', transportPriceMin);
        }
    }
    
    // Add user's current location data
    params.append('my_lat', myLocation.lat);
    params.append('my_lng', myLocation.lng);
    
    // Get address from location
    const locationAddress = document.getElementById('location-address');
    if (locationAddress && locationAddress.textContent) {
        params.append('my_address', locationAddress.textContent);
    }
    
    // Redirect to stay booking page
    window.location.href = 'stay-booking.php?' + params.toString();
}
</script>

<?php include 'includes/footer.php'; ?>

