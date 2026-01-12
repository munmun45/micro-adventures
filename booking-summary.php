<?php
require_once 'config.php';
$conn = getDBConnection();

// Get all booking data from URL parameters
// Handle places - can be string (comma-separated) or array
if (isset($_GET['places'])) {
    if (is_array($_GET['places'])) {
        $selected_places = array_filter(array_map('intval', $_GET['places']));
    } else {
        $selected_places = array_filter(array_map('intval', explode(',', $_GET['places'])));
    }
} else {
    $selected_places = [];
}

// Handle activities - can be string (comma-separated) or array
if (isset($_GET['activities'])) {
    if (is_array($_GET['activities'])) {
        $selected_activities = array_filter(array_map('intval', $_GET['activities']));
    } else {
        $selected_activities = array_filter(array_map('intval', explode(',', $_GET['activities'])));
    }
} else {
    $selected_activities = [];
}
$transport_rule_id = isset($_GET['transport_rule_id']) ? (int)$_GET['transport_rule_id'] : 0;
$transport_distance = isset($_GET['distance']) ? floatval($_GET['distance']) : 0;
$transport_price = isset($_GET['transport_price']) ? floatval($_GET['transport_price']) : 0;
$transport_price_min = isset($_GET['transport_price_min']) ? floatval($_GET['transport_price_min']) : 0;
$transport_price_max = isset($_GET['transport_price_max']) ? floatval($_GET['transport_price_max']) : 0;

$hotel_id = isset($_GET['hotel_id']) ? (int)$_GET['hotel_id'] : 0;
$hotel_price = isset($_GET['hotel_price']) ? trim($_GET['hotel_price']) : '';
$hotel_price_min = isset($_GET['hotel_price_min']) ? floatval($_GET['hotel_price_min']) : 0;
$hotel_price_max = isset($_GET['hotel_price_max']) ? floatval($_GET['hotel_price_max']) : 0;

// Get prices from URL
$places_price_min = isset($_GET['places_price_min']) ? floatval($_GET['places_price_min']) : 0;
$places_price_max = isset($_GET['places_price_max']) ? floatval($_GET['places_price_max']) : 0;
$activities_price_min = isset($_GET['activities_price_min']) ? floatval($_GET['activities_price_min']) : 0;
$activities_price_max = isset($_GET['activities_price_max']) ? floatval($_GET['activities_price_max']) : 0;

// Get selected places information
$places_info = [];
if (!empty($selected_places)) {
    $place_ids = array_map('intval', array_filter($selected_places));
    if (!empty($place_ids)) {
        $place_ids_str = implode(',', $place_ids);
        $places_query = "SELECT p.id, p.name as place_name, p.image as place_image, p.description as place_description,
                        d.id as city_id, d.name as city_name, d.latitude, d.longitude,
                        s.id as state_id, s.name as state_name,
                        p.ticket_price_min, p.ticket_price_max
                        FROM places p
                        JOIN districts d ON p.city_id = d.id
                        JOIN states s ON d.state_id = s.id
                        WHERE p.id IN ($place_ids_str) AND p.status = 'active'";
        $places_result = $conn->query($places_query);
        if ($places_result) {
            while ($row = $places_result->fetch_assoc()) {
                $places_info[] = $row;
            }
        }
    }
}

// Get selected activities information and group by place
$activities_data_from_url = [];
$activities_by_place = []; // Group activities by placeId

// Try to get activities from JSON format first
if (isset($_GET['activities'])) {
    $activities_json = json_decode($_GET['activities'], true);
    
    if (json_last_error() === JSON_ERROR_NONE && is_array($activities_json)) {
        $activities_data_from_url = $activities_json;
        $activity_ids = array_column($activities_json, 'id');
        
        // Group activities by placeId
        foreach ($activities_json as $act_data) {
            $placeId = $act_data['placeId'] ?? 0;
            if ($placeId > 0) {
                if (!isset($activities_by_place[$placeId])) {
                    $activities_by_place[$placeId] = [];
                }
                $activities_by_place[$placeId][] = $act_data;
            }
        }
    } else {
        // Fallback to comma-separated
        $activity_ids = array_map('intval', array_filter(explode(',', $_GET['activities'])));
    }
} else {
    $activity_ids = array_map('intval', array_filter($selected_activities));
}

// Get activity details from database
$activities_info_map = [];
if (!empty($activity_ids)) {
    $activity_ids_str = implode(',', array_unique($activity_ids));
    $activities_query = "SELECT * FROM activity_types WHERE id IN ($activity_ids_str) AND status = 'active'";
    $activities_result = $conn->query($activities_query);
    if ($activities_result) {
        while ($row = $activities_result->fetch_assoc()) {
            // Merge with price data from URL if available
            if (!empty($activities_data_from_url)) {
                foreach ($activities_data_from_url as $act_data) {
                    if ($act_data['id'] == $row['id']) {
                        $row['price_min'] = $act_data['priceMin'] ?? 0;
                        $row['price_max'] = $act_data['priceMax'] ?? 0;
                        $row['place_id'] = $act_data['placeId'] ?? 0;
                        break;
                    }
                }
            }
            $activities_info_map[$row['id']] = $row;
        }
    }
}

// Get transport information
$transport_info = null;
if ($transport_rule_id > 0) {
    $transport_query = "SELECT tpr.*, tt.name as transport_name, tt.image as transport_image, tt.description as transport_description
                       FROM transport_price_rules tpr
                       JOIN transport_types tt ON tpr.transport_type_id = tt.id
                       WHERE tpr.id = ? AND tpr.is_active = 'yes' AND tt.status = 'active'";
    $stmt = $conn->prepare($transport_query);
    $stmt->bind_param("i", $transport_rule_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $transport_info = $result->fetch_assoc();
    }
    $stmt->close();
}

// Get hotel information
$hotel_info = null;
if ($hotel_id > 0) {
    $hotel_query = "SELECT * FROM hotel_types WHERE id = ? AND status = 'active'";
    $stmt = $conn->prepare($hotel_query);
    $stmt->bind_param("i", $hotel_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $hotel_info = $result->fetch_assoc();
    }
    $stmt->close();
}

// Calculate total price using data from URL (preserves exact selections)
$total_price_min = 0;
$total_price_max = 0;
$price_breakdown = [];

// Add places ticket prices (use URL data if available, otherwise calculate)
if ($places_price_min > 0 || $places_price_max > 0) {
    $total_price_min += $places_price_min;
    $total_price_max += $places_price_max;
    if ($places_price_min == $places_price_max) {
        $price_breakdown[] = ['label' => 'Entry Tickets', 'price' => $places_price_max, 'min_price' => $places_price_min];
    } else {
        $price_breakdown[] = ['label' => 'Entry Tickets', 'price' => $places_price_max, 'min_price' => $places_price_min];
    }
} else {
    // Fallback: calculate from places_info
    $places_ticket_total = 0;
    $places_ticket_min = 0;
    foreach ($places_info as $place) {
        $place_min = $place['ticket_price_min'] ?? 0;
        $place_max = $place['ticket_price_max'] ?? 0;
        if ($place_max > 0) {
            $places_ticket_total += $place_max;
            $places_ticket_min += $place_min;
        } elseif ($place_min > 0) {
            $places_ticket_total += $place_min;
            $places_ticket_min += $place_min;
        }
    }
    if ($places_ticket_total > 0) {
        $total_price_min += $places_ticket_min;
        $total_price_max += $places_ticket_total;
        if ($places_ticket_min == $places_ticket_total) {
            $price_breakdown[] = ['label' => 'Entry Tickets', 'price' => $places_ticket_total];
        } else {
            $price_breakdown[] = ['label' => 'Entry Tickets', 'price' => $places_ticket_total, 'min_price' => $places_ticket_min];
        }
    }
}

// Add activities prices (use URL data if available)
if ($activities_price_min > 0 || $activities_price_max > 0) {
    $total_price_min += $activities_price_min;
    $total_price_max += $activities_price_max;
    if ($activities_price_min == $activities_price_max) {
        $price_breakdown[] = ['label' => 'Activities', 'price' => $activities_price_max];
    } else {
        $price_breakdown[] = ['label' => 'Activities', 'price' => $activities_price_max, 'min_price' => $activities_price_min];
    }
}

// Add transport price (use URL data if available)
if ($transport_price_min > 0 || $transport_price_max > 0) {
    $total_price_min += $transport_price_min;
    $total_price_max += $transport_price_max;
    if ($transport_price_min == $transport_price_max) {
        $price_breakdown[] = ['label' => 'Transport', 'price' => $transport_price_max];
    } else {
        $price_breakdown[] = ['label' => 'Transport', 'price' => $transport_price_max, 'min_price' => $transport_price_min];
    }
} elseif ($transport_price > 0) {
    $total_price_min += $transport_price;
    $total_price_max += $transport_price;
    $price_breakdown[] = ['label' => 'Transport', 'price' => $transport_price];
}

// Add hotel price (use URL data if available)
if ($hotel_price_min > 0 || $hotel_price_max > 0) {
    $total_price_min += $hotel_price_min;
    $total_price_max += $hotel_price_max;
    if ($hotel_price_min == $hotel_price_max) {
        $price_breakdown[] = ['label' => 'Accommodation (per night)', 'price' => $hotel_price_max];
    } else {
        $price_breakdown[] = ['label' => 'Accommodation (per night)', 'price' => $hotel_price_max, 'min_price' => $hotel_price_min];
    }
} elseif ($hotel_price) {
    // Fallback: extract from price text
    $hotel_price_match = preg_match('/₹([\d,]+)/', $hotel_price, $matches);
    if ($hotel_price_match && isset($matches[1])) {
        $hotel_price_numeric = floatval(str_replace(',', '', $matches[1]));
        $total_price_min += $hotel_price_numeric;
        $total_price_max += $hotel_price_numeric;
        $price_breakdown[] = ['label' => 'Accommodation (per night)', 'price' => $hotel_price_numeric];
    }
}

$total_price = $total_price_max; // Use max for display

$pageTitle = 'Booking Summary | TravelApp';
$pageCSS = 'booking-summary.css';
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
                <h1 class="text-lg sm:text-xl font-bold text-gray-900">Booking Summary</h1>
                <p class="text-xs sm:text-sm text-gray-500 mt-1">Review your booking details</p>
            </div>
            <a href="stay-booking.php?<?php echo http_build_query($_GET); ?>" class="text-sm text-[#009688] hover:underline flex items-center gap-1">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>
</div>

<!-- Main Content -->
<section class="max-w-4xl mx-auto px-4 py-6 pb-24">
    <div class="bg-white rounded-2xl shadow-lg p-6 sm:p-8">
        
        <!-- Booking Summary Paragraph Format -->
        <div class="prose max-w-none">
            
            <!-- Introduction -->
            <div class="mb-8 pb-6 border-b-2 border-[#009688]/20">
                <h2 class="text-2xl font-bold text-gray-900 mb-3 flex items-center gap-2">
                    <i class="bi bi-journal-text text-[#009688]"></i> Your Booking Summary
                </h2>
                <p class="text-gray-700 leading-relaxed">
                    Thank you for choosing our travel service! Below is a detailed summary of your selected destinations, activities, transportation, and accommodation. Please review all details carefully before confirming your booking.
                </p>
            </div>

            <!-- Selected Places and Activities -->
            <?php if (!empty($places_info)): ?>
            <div class="mb-8">
                <h3 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                    <i class="bi bi-map-fill text-[#009688]"></i> Destinations
                </h3>
                
                <?php 
                $place_count = count($places_info);
                $current = 0;
                foreach ($places_info as $place): 
                    $current++;
                ?>
                    <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                        <h4 class="text-lg font-bold text-gray-900 mb-2">
                            <?php echo $current; ?>. <?php echo htmlspecialchars($place['place_name']); ?>
                        </h4>
                        
                        <p class="text-gray-700 leading-relaxed mb-3">
                            <i class="bi bi-geo-alt text-[#009688]"></i>
                            You have selected <strong><?php echo htmlspecialchars($place['place_name']); ?></strong>, located in 
                            <strong><?php echo htmlspecialchars($place['city_name']); ?>, <?php echo htmlspecialchars($place['state_name']); ?></strong>. 
                            
                            <?php if ($place['ticket_price_min'] > 0 || $place['ticket_price_max'] > 0): ?>
                                The entry ticket price for this destination is 
                                <strong class="text-[#009688]">
                                <?php 
                                if ($place['ticket_price_min'] == $place['ticket_price_max']) {
                                    echo '₹' . number_format($place['ticket_price_min'], 2);
                                } else {
                                    echo '₹' . number_format($place['ticket_price_min'], 2) . ' - ₹' . number_format($place['ticket_price_max'], 2);
                                }
                                ?></strong> per person.
                            <?php else: ?>
                                This destination offers <strong class="text-green-600">FREE entry</strong>, allowing you to explore without an entrance fee.
                            <?php endif; ?>
                        </p>
                        
                        <!-- Activities for this place -->
                        <?php if (isset($activities_by_place[$place['id']]) && !empty($activities_by_place[$place['id']])): ?>
                            <div class="mt-4 pl-4 border-l-4 border-[#009688]/30">
                                <p class="font-semibold text-gray-900 mb-2">
                                    <i class="bi bi-activity text-[#009688]"></i> Activities at this destination:
                                </p>
                                <ul class="space-y-2">
                                    <?php foreach ($activities_by_place[$place['id']] as $act_data): 
                                        $activity_info = $activities_info_map[$act_data['id']] ?? null;
                                        if ($activity_info):
                                            $act_price_min = $act_data['priceMin'] ?? 0;
                                            $act_price_max = $act_data['priceMax'] ?? 0;
                                    ?>
                                        <li class="text-gray-700">
                                            <strong><?php echo htmlspecialchars($activity_info['name']); ?></strong>
                                            <?php if ($act_price_min > 0 || $act_price_max > 0): ?>
                                                - Ticket price: <strong class="text-[#009688]">
                                                <?php 
                                                if ($act_price_min == $act_price_max) {
                                                    echo '₹' . number_format($act_price_min, 2);
                                                } else {
                                                    echo '₹' . number_format($act_price_min, 2) . ' - ₹' . number_format($act_price_max, 2);
                                                }
                                                ?></strong> per person
                                            <?php else: ?>
                                                - <strong class="text-green-600">FREE</strong>
                                            <?php endif; ?>
                                        </li>
                                    <?php endif; endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Transport Details -->
            <?php if ($transport_info): ?>
            <div class="mb-8">
                <h3 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                    <i class="bi bi-truck text-[#009688]"></i> Transportation
                </h3>
                <div class="p-4 bg-gray-50 rounded-lg">
                    <p class="text-gray-700 leading-relaxed">
                        For your journey, you have selected <strong><?php echo htmlspecialchars($transport_info['transport_name']); ?></strong> 
                        as your mode of transportation. Specifically, you have chosen the 
                        <strong><?php echo htmlspecialchars($transport_info['sub_type']); ?></strong> option.
                        <?php if ($transport_info['notes']): ?>
                            <em class="text-gray-600"><?php echo htmlspecialchars($transport_info['notes']); ?></em>
                        <?php endif; ?>
                    </p>
                    <p class="text-gray-700 leading-relaxed mt-3">
                        The total travel distance for your trip is approximately <strong><?php echo number_format($transport_distance, 2); ?> kilometers</strong>. 
                        Based on this distance and your selected transport type, the estimated transportation cost is 
                        <strong class="text-[#009688]">
                        <?php 
                        if ($transport_price_min > 0 && $transport_price_max > 0 && $transport_price_min != $transport_price_max) {
                            echo '₹' . number_format($transport_price_min, 2) . ' - ₹' . number_format($transport_price_max, 2);
                        } else {
                            echo '₹' . number_format($transport_price, 2);
                        }
                        ?></strong>.
                    </p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Accommodation Details -->
            <?php if ($hotel_info): ?>
            <div class="mb-8">
                <h3 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                    <i class="bi bi-building text-[#009688]"></i> Accommodation
                </h3>
                <div class="p-4 bg-gray-50 rounded-lg">
                    <p class="text-gray-700 leading-relaxed">
                        You have selected <strong><?php echo htmlspecialchars($hotel_info['name']); ?></strong> as your accommodation type. 
                        <?php if ($hotel_info['description']): ?>
                            <?php echo htmlspecialchars($hotel_info['description']); ?>
                        <?php endif; ?>
                    </p>
                    <p class="text-gray-700 leading-relaxed mt-3">
                        The rate for this accommodation is <strong class="text-[#009688]"><?php echo htmlspecialchars($hotel_price); ?></strong>. 
                        This price is based on the accommodation type you have selected and may vary depending on the season and availability.
                    </p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Price Summary -->
            <div class="mb-8 pb-6 border-t-2 border-[#009688]/20 pt-6">
                <h3 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                    <i class="bi bi-cash-coin text-[#009688]"></i> Cost Breakdown
                </h3>
                <div class="p-4 bg-gradient-to-r from-[#009688]/10 to-[#00796b]/10 rounded-lg">
                    <p class="text-gray-700 leading-relaxed mb-4">
                        Here is a detailed breakdown of the estimated costs for your trip. Please note that prices may vary based on season, availability, and other factors.
                    </p>
                    <ul class="space-y-3 mb-4">
                        <?php 
                        $total_min = 0;
                        $total_max = 0;
                        foreach ($price_breakdown as $item): 
                            if (isset($item['min_price']) && $item['min_price'] != $item['price']) {
                                $total_min += $item['min_price'];
                                $total_max += $item['price'];
                            } else {
                                $total_min += $item['price'];
                                $total_max += $item['price'];
                            }
                        ?>
                            <li class="flex justify-between items-center text-gray-700 py-2 border-b border-gray-300">
                                <span class="font-medium"><?php echo htmlspecialchars($item['label']); ?>:</span>
                                <span class="font-bold text-[#009688]">
                                    <?php if (isset($item['min_price']) && $item['min_price'] != $item['price']): ?>
                                        ₹<?php echo number_format($item['min_price'], 2); ?> - ₹<?php echo number_format($item['price'], 2); ?>
                                    <?php else: ?>
                                        ₹<?php echo number_format($item['price'], 2); ?>
                                    <?php endif; ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    
                    <div class="mt-6 p-4 bg-white rounded-lg border-2 border-[#009688]">
                        <p class="text-gray-700 leading-relaxed">
                            <strong class="text-lg text-gray-900">Total Estimated Cost:</strong> 
                            <span class="text-2xl font-bold text-[#009688] ml-2">
                                <?php if ($total_min != $total_max): ?>
                                    ₹<?php echo number_format($total_min, 2); ?> - ₹<?php echo number_format($total_max, 2); ?>
                                <?php else: ?>
                                    ₹<?php echo number_format($total_price, 2); ?>
                                <?php endif; ?>
                            </span>
                        </p>
                        <p class="text-sm text-gray-600 mt-2">
                            <em>* This is an estimated total cost based on the options you have selected. Final prices may vary.</em>
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Final Confirmation Note -->
            <div class="mt-8 pt-6 border-t-2 border-gray-200">
                <h3 class="text-xl font-bold text-gray-900 mb-3">
                    <i class="bi bi-info-circle text-[#009688]"></i> Important Information
                </h3>
                <div class="p-4 bg-blue-50 border-l-4 border-blue-500 rounded">
                    <p class="text-gray-700 leading-relaxed">
                        Please review all the details above carefully. Once you confirm your booking, our team will process your request and send you a confirmation email with detailed itinerary and payment instructions. If you need to make any changes, please click the "Back" button at the top of this page.
                    </p>
                </div>
            </div>
            
        </div> <!-- End of prose container -->
        
        <!-- Action Buttons -->
        <div class="flex flex-col sm:flex-row gap-4 mt-6">
            <button onclick="window.print()" class="flex-1 px-6 py-4 bg-gray-100 text-gray-700 rounded-lg font-semibold hover:bg-gray-200 transition-colors flex items-center justify-center gap-2 border-2 border-gray-300">
                <i class="bi bi-printer text-lg"></i> Print Summary
            </button>
            <button onclick="confirmBooking()" class="flex-1 px-6 py-4 bg-[#009688] text-white rounded-lg font-semibold hover:bg-[#00796b] transition-colors flex items-center justify-center gap-2 shadow-lg text-lg">
                <i class="bi bi-check-circle text-lg"></i> Confirm Booking
            </button>
        </div>
        
    </div> <!-- End of white card -->
</section>

<script>
function confirmBooking() {
    // Here you can add code to save booking to database or redirect to payment page
    alert('Booking confirmed! Thank you for your booking.\n\nTotal Amount: ₹<?php echo number_format($total_price, 2); ?>\n\nYou will be redirected to payment page.');
    // window.location.href = 'payment.php?<?php echo http_build_query($_GET); ?>';
}
</script>

<?php include 'includes/footer.php'; ?>

