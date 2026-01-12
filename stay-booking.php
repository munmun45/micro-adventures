<?php
require_once 'config.php';
$conn = getDBConnection();

// Get selected places, activities, and transport from URL
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

$transport_rule_id = isset($_GET['transport_rule_id']) ? (int)$_GET['transport_rule_id'] : 0;
$transport_distance = isset($_GET['distance']) ? floatval($_GET['distance']) : 0;
$transport_price = isset($_GET['transport_price']) ? floatval($_GET['transport_price']) : 0;
$transport_price_min = isset($_GET['transport_price_min']) ? floatval($_GET['transport_price_min']) : 0;
$transport_price_max = isset($_GET['transport_price_max']) ? floatval($_GET['transport_price_max']) : 0;

// Get city information from selected places
$selected_city = null;
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

// Get all hotel types
$hotel_types = [];
$hotel_query = "SELECT * FROM hotel_types WHERE status = 'active' ORDER BY min_price_per_night ASC";
$hotel_result = $conn->query($hotel_query);
if ($hotel_result) {
    while ($row = $hotel_result->fetch_assoc()) {
        $hotel_types[] = $row;
    }
}

$pageTitle = 'Stay Booking | TravelApp';
$pageCSS = 'stay-booking.css';
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
                <h1 class="text-lg sm:text-xl font-bold text-gray-900">Stay Booking</h1>
                <p class="text-xs sm:text-sm text-gray-500 mt-1">Select your accommodation type</p>
            </div>
            <a href="transport-booking.php<?php echo !empty($selected_places) ? '?places=' . implode(',', $selected_places) : ''; ?>" class="text-sm text-[#009688] hover:underline flex items-center gap-1">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>
</div>

<!-- Main Content -->
<section class="max-w-6xl mx-auto px-4 py-6 pb-24">
    <div class="space-y-4 sm:space-y-6">
        <div class="bg-white rounded-2xl shadow-md p-4 sm:p-5">
            <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                <i class="bi bi-building text-[#009688]"></i> Hotel Types
            </h2>
            <div class="grid grid-cols-1 gap-4" id="hotel-list">
                <?php foreach ($hotel_types as $hotel): ?>
                    <div class="hotel-card bg-white border-2 border-gray-200 rounded-2xl p-4 sm:p-5 shadow-sm hover:shadow-md hover:border-[#009688] transition-all cursor-pointer" 
                         data-hotel-id="<?php echo $hotel['id']; ?>"
                         data-min-price="<?php echo $hotel['min_price_per_night']; ?>"
                         data-max-price="<?php echo $hotel['max_price_per_night']; ?>"
                         onclick="selectHotelOption(this)">
                        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
                            <div class="w-full sm:w-24 h-32 sm:h-24 rounded-xl overflow-hidden bg-gray-100 shrink-0 shadow-md">
                                <img src="<?php echo htmlspecialchars($hotel['image'] ?: 'https://images.unsplash.com/photo-1566073771259-6a8506099945'); ?>" 
                                     alt="<?php echo htmlspecialchars($hotel['name']); ?>"
                                     class="w-full h-full object-cover">
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="text-base sm:text-lg font-bold text-gray-900 mb-1"><?php echo htmlspecialchars($hotel['name']); ?></h3>
                                <?php if ($hotel['description']): ?>
                                    <p class="text-xs sm:text-sm text-gray-600 mb-2"><?php echo htmlspecialchars($hotel['description']); ?></p>
                                <?php endif; ?>
                                <div class="flex items-center gap-2">
                                    <i class="bi bi-currency-rupee text-[#009688]"></i>
                                    <span class="text-sm text-gray-600">
                                        <?php 
                                        if ($hotel['min_price_per_night'] == $hotel['max_price_per_night']) {
                                            echo '₹' . number_format($hotel['min_price_per_night'], 2) . ' per night';
                                        } else {
                                            echo '₹' . number_format($hotel['min_price_per_night'], 2) . ' - ₹' . number_format($hotel['max_price_per_night'], 2) . ' per night';
                                        }
                                        ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<!-- Selection Summary Navbar -->
<div id="selection-navbar" class="fixed bottom-0 left-0 right-0 bg-white border-t-2 border-[#009688] shadow-2xl z-50 transform translate-y-full transition-transform duration-300">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-3 sm:py-4">
        <!-- Mobile: Stacked Layout -->
        <div class="flex flex-col md:hidden gap-3">
            <!-- Row 1: Stay Info -->
            <div class="flex items-center gap-2 text-xs justify-center overflow-x-auto pb-1">
                <div class="flex items-center gap-1.5 bg-gray-50 px-2 py-1 rounded-lg shrink-0">
                    <i class="bi bi-building text-[#009688] text-sm"></i>
                    <span class="font-semibold text-gray-700" id="selected-hotel-name-mobile">
                        <span class="text-gray-400">No hotel selected</span>
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
                        onclick="submitBooking()" 
                        disabled
                        class="px-6 py-2.5 bg-[#009688] text-white rounded-lg font-semibold text-sm transition-colors flex items-center justify-center gap-2 shadow-lg shrink-0 opacity-50 cursor-not-allowed">
                    Submit <i class="bi bi-check-circle"></i>
                </button>
            </div>
        </div>
        
        <!-- Tablet & Desktop: Single Row Layout -->
        <div class="hidden md:flex items-center justify-between gap-4 lg:gap-6">
            <!-- Stay Info -->
            <div class="flex items-center gap-3 lg:gap-4 text-sm lg:text-base flex-1">
                <div class="flex items-center gap-2 bg-gray-50 px-3 py-1.5 rounded-lg">
                    <i class="bi bi-building text-[#009688] text-base"></i>
                    <span class="font-semibold text-gray-700" id="selected-hotel-name-desktop">
                        <span class="text-gray-400">No hotel selected</span>
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
                        onclick="submitBooking()" 
                        disabled
                        class="px-6 lg:px-8 py-2.5 lg:py-3 bg-[#009688] text-white rounded-lg font-semibold text-sm lg:text-base transition-colors flex items-center justify-center gap-2 shadow-lg hover:bg-[#00796b] shrink-0 opacity-50 cursor-not-allowed">
                    Submit <i class="bi bi-check-circle"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let selectedHotel = { id: null, name: null, minPrice: null, maxPrice: null, priceText: null };

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    updatePriceDisplay();
});

// Select hotel option
function selectHotelOption(element) {
    // Remove previous selection
    document.querySelectorAll('.hotel-card').forEach(card => {
        card.classList.remove('border-[#009688]', 'bg-[#009688]/5', 'ring-2', 'ring-[#009688]/20');
    });
    
    // Add selection to clicked card
    element.classList.add('border-[#009688]', 'bg-[#009688]/5', 'ring-2', 'ring-[#009688]/20');
    
    // Store selected hotel info
    selectedHotel.id = element.dataset.hotelId;
    selectedHotel.name = element.querySelector('h3').textContent;
    selectedHotel.minPrice = parseFloat(element.dataset.minPrice);
    selectedHotel.maxPrice = parseFloat(element.dataset.maxPrice);
    
    // Calculate price text (per night)
    if (selectedHotel.minPrice === selectedHotel.maxPrice) {
        selectedHotel.priceText = '₹' + Math.round(selectedHotel.minPrice).toLocaleString('en-IN') + ' per night';
    } else {
        selectedHotel.priceText = '₹' + Math.round(selectedHotel.minPrice).toLocaleString('en-IN') + ' - ₹' + Math.round(selectedHotel.maxPrice).toLocaleString('en-IN') + ' per night';
    }
    
    // Update price display
    updatePriceDisplay();
}

// Update price display in bottom navbar
function updatePriceDisplay() {
    const priceDisplayMobile = document.getElementById('price-display-mobile');
    const priceDisplayDesktop = document.getElementById('price-display-desktop');
    const nextButtonMobile = document.getElementById('next-button-mobile');
    const nextButtonDesktop = document.getElementById('next-button-desktop');
    const bottomNavbar = document.getElementById('selection-navbar');
    
    if (selectedHotel.id && selectedHotel.priceText) {
        // Update price displays
        if (priceDisplayMobile) {
            priceDisplayMobile.textContent = selectedHotel.priceText;
        }
        if (priceDisplayDesktop) {
            priceDisplayDesktop.textContent = selectedHotel.priceText;
        }
        
        // Update hotel name displays
        const selectedNameMobile = document.getElementById('selected-hotel-name-mobile');
        const selectedNameDesktop = document.getElementById('selected-hotel-name-desktop');
        if (selectedNameMobile) {
            selectedNameMobile.innerHTML = `<span class="text-[#009688]">${selectedHotel.name}</span>`;
        }
        if (selectedNameDesktop) {
            selectedNameDesktop.innerHTML = `<span class="text-[#009688]">${selectedHotel.name}</span>`;
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
        
        // Reset selected hotel name
        const selectedNameMobile = document.getElementById('selected-hotel-name-mobile');
        const selectedNameDesktop = document.getElementById('selected-hotel-name-desktop');
        if (selectedNameMobile) {
            selectedNameMobile.innerHTML = '<span class="text-gray-400">No hotel selected</span>';
        }
        if (selectedNameDesktop) {
            selectedNameDesktop.innerHTML = '<span class="text-gray-400">No hotel selected</span>';
        }
        
        // Hide bottom navbar if no selection
        if (bottomNavbar && !selectedHotel.id) {
            bottomNavbar.classList.add('translate-y-full');
        }
    }
}

// Submit booking
function submitBooking() {
    if (!selectedHotel.id) {
        alert('Please select a hotel type');
        return;
    }
    
    // Build URL parameters - preserve all previous data
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
    <?php if (!empty($_GET['activities'])): ?>
    params.append('activities', <?php echo json_encode($_GET['activities']); ?>);
    <?php if ($activities_price_min > 0 || $activities_price_max > 0): ?>
    params.append('activities_price_min', '<?php echo $activities_price_min; ?>');
    params.append('activities_price_max', '<?php echo $activities_price_max; ?>');
    <?php endif; ?>
    <?php endif; ?>
    
    // Preserve transport data
    params.append('transport_rule_id', '<?php echo $transport_rule_id; ?>');
    params.append('distance', '<?php echo $transport_distance; ?>');
    params.append('transport_price', '<?php echo $transport_price; ?>');
    <?php if ($transport_price_min > 0 || $transport_price_max > 0): ?>
    params.append('transport_price_min', '<?php echo $transport_price_min; ?>');
    params.append('transport_price_max', '<?php echo $transport_price_max; ?>');
    <?php endif; ?>
    
    // Add hotel data
    params.append('hotel_id', selectedHotel.id);
    params.append('hotel_price', selectedHotel.priceText);
    params.append('hotel_price_min', selectedHotel.minPrice);
    params.append('hotel_price_max', selectedHotel.maxPrice);
    
    // Redirect to booking summary page
    window.location.href = 'booking-summary.php?' + params.toString();
}
</script>

<?php include 'includes/footer.php'; ?>

