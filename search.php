<?php
require_once 'config.php';
$conn = getDBConnection();

// Get search parameters
$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$country_id = isset($_GET['country']) ? (int)$_GET['country'] : 0;
$state_id = isset($_GET['state']) ? (int)$_GET['state'] : 0;
$city_id = isset($_GET['city']) ? (int)$_GET['city'] : 0;
$activity_id = isset($_GET['activity']) ? (int)$_GET['activity'] : 0;
$place_id = isset($_GET['place']) ? (int)$_GET['place'] : 0;

// Parse query for keywords (e.g., "boating in puri")
$activity_keyword = '';
$location_keyword = '';
$current_month = date('F');

if (!empty($query)) {
    // Check if query contains activity keywords
    $activity_types_query = "SELECT id, name FROM activity_types WHERE status = 'active'";
    $activity_types_result = $conn->query($activity_types_query);
    $activity_keywords = [];
    if ($activity_types_result) {
        while ($row = $activity_types_result->fetch_assoc()) {
            $activity_keywords[strtolower($row['name'])] = $row['id'];
        }
    }
    
    // Check for activity in query
    foreach ($activity_keywords as $keyword => $id) {
        if (stripos($query, $keyword) !== false) {
            $activity_id = $id;
            $activity_keyword = $keyword;
            $location_keyword = trim(str_ireplace($keyword, '', $query));
            $location_keyword = trim(str_ireplace('in', '', $location_keyword));
            break;
        }
    }
    
    // If no activity found, treat entire query as location
    if (empty($activity_keyword)) {
        $location_keyword = trim($query);
    }
    
    // Clean up location keyword - remove common prepositions but keep the main search term
    $location_keyword = preg_replace('/\b(in|at|for|the|and|or)\b/i', '', $location_keyword);
    $location_keyword = trim($location_keyword);
}

// Build search query
$places = [];
$grouped_places = [];

if ($place_id > 0) {
    // Show specific place
    $sql = "SELECT p.*, p.ticket_price_min, p.ticket_price_max, d.name as city_name, d.id as city_id, s.name as state_name, s.id as state_id, c.name as country_name
            FROM places p
            JOIN districts d ON p.city_id = d.id
            JOIN states s ON d.state_id = s.id
            JOIN countries c ON s.country_id = c.id
            WHERE p.id = ? AND p.status = 'active'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $place_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $places[] = $row;
    }
    $stmt->close();
} else {
    // Build dynamic WHERE clause
    $where_conditions = ["p.status = 'active'"];
    $params = [];
    $types = "";
    
    if ($city_id > 0) {
        $where_conditions[] = "p.city_id = ?";
        $params[] = $city_id;
        $types .= "i";
    } elseif ($state_id > 0) {
        $where_conditions[] = "d.state_id = ?";
        $params[] = $state_id;
        $types .= "i";
    } elseif ($country_id > 0) {
        $where_conditions[] = "c.id = ?";
        $params[] = $country_id;
        $types .= "i";
    }
    
    // Location keyword search - always search if query provided (case-insensitive)
    if (!empty($location_keyword)) {
        $where_conditions[] = "(LOWER(d.name) LIKE LOWER(?) OR LOWER(s.name) LIKE LOWER(?) OR LOWER(c.name) LIKE LOWER(?) OR LOWER(p.name) LIKE LOWER(?) OR LOWER(p.description) LIKE LOWER(?))";
        $search_term = "%{$location_keyword}%";
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
        $types .= "sssss";
    }
    
    // Activity filter
    if ($activity_id > 0) {
        $where_conditions[] = "EXISTS (
            SELECT 1 FROM place_activities pa 
            WHERE pa.place_id = p.id 
            AND pa.activity_type_id = ? 
            AND pa.status = 'active'
        )";
        $params[] = $activity_id;
        $types .= "i";
    }
    
    // Build SQL with best month check
    $sql = "SELECT p.*, d.name as city_name, d.id as city_id, s.name as state_name, s.id as state_id, c.name as country_name,
            p.ticket_price_min, p.ticket_price_max,
            CASE 
                WHEN EXISTS (
                    SELECT 1 FROM place_activities pa 
                    WHERE pa.place_id = p.id 
                    AND pa.available_months LIKE ?
                    AND pa.status = 'active'
                ) THEN 1
                ELSE 0
            END as is_best_month
            FROM places p
            JOIN districts d ON p.city_id = d.id
            JOIN states s ON d.state_id = s.id
            JOIN countries c ON s.country_id = c.id
            WHERE " . implode(" AND ", $where_conditions) . "
            ORDER BY is_best_month DESC, p.priority_level DESC, p.experience_score DESC, p.name ASC
            LIMIT 100";
    
    // Add current month to params for best month check (always first)
    array_unshift($params, "%{$current_month}%");
    $types = "s" . $types;
    
    // Execute query
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $places[] = $row;
            }
        } else {
            // Log error for debugging
            error_log("Search query error: " . $stmt->error);
        }
        $stmt->close();
    } else {
        // Debug: Show SQL error if query fails
        error_log("Search SQL prepare error: " . $conn->error);
        error_log("SQL: " . $sql);
        error_log("Params: " . print_r($params, true));
    }
}

// Smart grouping based on search type
$grouped_places = [];
$search_type = 'all'; // 'country', 'state', 'city', 'all'

// Detect search type based on query and results
if (!empty($location_keyword)) {
    // Check if search matches a country name
    $country_check = $conn->query("SELECT id, name FROM countries WHERE LOWER(name) LIKE LOWER('%{$location_keyword}%') LIMIT 1");
    if ($country_check && $country_check->num_rows > 0) {
        $country_row = $country_check->fetch_assoc();
        // If all places are from same country, group by state
        $all_same_country = true;
        $first_country = null;
        foreach ($places as $place) {
            if ($first_country === null) {
                $first_country = $place['country_name'];
            }
            if ($place['country_name'] !== $first_country) {
                $all_same_country = false;
                break;
            }
        }
        if ($all_same_country && count($places) > 1) {
            $search_type = 'country';
        }
    }
    
    // Check if search matches a state name
    if ($search_type === 'all') {
        $state_check = $conn->query("SELECT id, name FROM states WHERE LOWER(name) LIKE LOWER('%{$location_keyword}%') LIMIT 1");
        if ($state_check && $state_check->num_rows > 0) {
            $state_row = $state_check->fetch_assoc();
            // If all places are from same state, group by city
            $all_same_state = true;
            $first_state = null;
            foreach ($places as $place) {
                if ($first_state === null) {
                    $first_state = $place['state_name'];
                }
                if ($place['state_name'] !== $first_state) {
                    $all_same_state = false;
                    break;
                }
            }
            if ($all_same_state && count($places) > 1) {
                $search_type = 'state';
            }
        }
    }
    
    // Check if search matches a city name
    if ($search_type === 'all') {
        $city_check = $conn->query("SELECT id, name FROM districts WHERE LOWER(name) LIKE LOWER('%{$location_keyword}%') LIMIT 1");
        if ($city_check && $city_check->num_rows > 0 && count($places) > 0) {
            // If all places are from same city, don't group
            $all_same_city = true;
            $first_city = null;
            foreach ($places as $place) {
                if ($first_city === null) {
                    $first_city = $place['city_name'];
                }
                if ($place['city_name'] !== $first_city) {
                    $all_same_city = false;
                    break;
                }
            }
            if ($all_same_city) {
                $search_type = 'city';
            }
        }
    }
}

// Also check explicit filters
if ($country_id > 0 && $state_id == 0 && $city_id == 0) {
    $search_type = 'country';
} elseif ($state_id > 0 && $city_id == 0) {
    $search_type = 'state';
} elseif ($city_id > 0) {
    $search_type = 'city';
}

// Group places based on detected search type
if ($search_type === 'country') {
    // Group by state
    foreach ($places as $place) {
        $grouped_places[$place['state_id']]['state_name'] = $place['state_name'];
        $grouped_places[$place['state_id']]['state_id'] = $place['state_id'];
        if (!isset($grouped_places[$place['state_id']]['places'])) {
            $grouped_places[$place['state_id']]['places'] = [];
        }
        $grouped_places[$place['state_id']]['places'][] = $place;
    }
} elseif ($search_type === 'state') {
    // Group by city
    foreach ($places as $place) {
        $grouped_places[$place['city_id']]['city_name'] = $place['city_name'];
        $grouped_places[$place['city_id']]['city_id'] = $place['city_id'];
        if (!isset($grouped_places[$place['city_id']]['places'])) {
            $grouped_places[$place['city_id']]['places'] = [];
        }
        $grouped_places[$place['city_id']]['places'][] = $place;
    }
} else {
    // Show all places without grouping (city search or mixed results)
    $grouped_places[0]['places'] = $places;
}

// Get activities for places
$place_ids = array_column($places, 'id');
$place_activities_map = [];
if (!empty($place_ids)) {
    $place_ids_str = implode(',', array_map('intval', $place_ids));
    $activities_sql = "SELECT pa.*, at.name as activity_name, at.image as activity_image
                       FROM place_activities pa
                       JOIN activity_types at ON pa.activity_type_id = at.id
                       WHERE pa.place_id IN ($place_ids_str) AND pa.status = 'active' AND at.status = 'active'
                       ORDER BY pa.priority_level DESC";
    $activities_result = $conn->query($activities_sql);
    if ($activities_result) {
        while ($row = $activities_result->fetch_assoc()) {
            if (!isset($place_activities_map[$row['place_id']])) {
                $place_activities_map[$row['place_id']] = [];
            }
            $place_activities_map[$row['place_id']][] = $row;
        }
    }
}

$pageTitle = 'Search Results | TravelApp';
$pageCSS = 'search.css';
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
                <h1 class="text-lg sm:text-xl font-bold text-gray-900">
                    <?php 
                    if (!empty($query)) {
                        echo "Search: " . htmlspecialchars($query);
                    } elseif ($city_id > 0) {
                        echo "Places in " . htmlspecialchars($places[0]['city_name'] ?? 'City');
                    } elseif ($state_id > 0) {
                        echo "Places in " . htmlspecialchars($places[0]['state_name'] ?? 'State');
                    } elseif ($country_id > 0) {
                        echo "Places in " . htmlspecialchars($places[0]['country_name'] ?? 'Country');
                    } else {
                        echo "All Places";
                    }
                    ?>
                </h1>
                <p class="text-xs sm:text-sm text-gray-500 mt-1">
                    <?php echo count($places); ?> place(s) found
                </p>
            </div>
            <a href="index.php" class="text-sm text-[#009688] flex items-center gap-1">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>
</div>

<!-- Places Container -->
<section class="max-w-6xl mx-auto px-4 py-4 sm:py-6 space-y-6 pb-24 sm:pb-28">
    <?php if (empty($places)): ?>
        <div class="text-center py-12">
            <i class="bi bi-search text-4xl text-gray-300 mb-4"></i>
            <p class="text-gray-500 text-sm sm:text-base">No places found. Try a different search.</p>
            <a href="index.php" class="mt-4 inline-block px-6 py-2 bg-[#009688] text-white rounded-lg text-sm">Back to Search</a>
        </div>
    <?php else: ?>
        <?php 
        $group_index = 0;
        foreach ($grouped_places as $group_key => $group): 
            $group_index++;
            $is_first = $group_index === 1;
            $group_id = 'group-' . $group_key;
            // Get first place image for background
            $header_image = !empty($group['places']) ? ($group['places'][0]['image'] ?? 'https://images.unsplash.com/photo-1501785888041-af3ef285b470') : 'https://images.unsplash.com/photo-1501785888041-af3ef285b470';
        ?>
            <?php if (isset($group['state_name'])): ?>
                <!-- State Group Header - Compact with Background Image -->
                <div class="mb-2 mt-4 first:mt-0">
                    <button id="btn-<?php echo $group_id; ?>" onclick="toggleGroup('<?php echo $group_id; ?>')" class="w-full relative h-20 sm:h-24 rounded-xl overflow-hidden shadow-lg transition-all duration-300 text-left group">
                        <!-- Background Image -->
                        <div class="absolute inset-0">
                            <img src="<?php echo htmlspecialchars($header_image); ?>" 
                                 alt="<?php echo htmlspecialchars($group['state_name']); ?>"
                                 class="w-full h-full object-cover transition-transform duration-500">
                            <div class="absolute inset-0 bg-black/60"></div>
                        </div>
                        <!-- Content -->
                        <div class="relative h-full flex items-center justify-between gap-3 px-4 sm:px-5">
                            <div class="flex items-center gap-3 flex-1 min-w-0">
                                <div class="w-9 h-9 sm:w-10 sm:h-10 bg-white/20 backdrop-blur-sm rounded-lg flex items-center justify-center shrink-0">
                                    <i class="bi bi-geo-alt-fill text-white text-base sm:text-lg"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h2 class="text-base sm:text-lg md:text-xl font-bold text-white mb-0.5 line-clamp-1 drop-shadow-lg">
                                        <?php echo htmlspecialchars($group['state_name']); ?>
                                    </h2>
                                    <p class="text-xs text-white/95 flex items-center gap-1.5 line-clamp-1">
                                        <i class="bi bi-map text-[10px]"></i>
                                        <span><?php echo count($group['places']); ?> places</span>
                                    </p>
                                </div>
                            </div>
                            <div class="shrink-0">
                                <i id="icon-<?php echo $group_id; ?>" class="bi bi-chevron-down text-white text-lg sm:text-xl transition-transform duration-500 <?php echo $is_first ? 'rotate-180' : ''; ?> drop-shadow-lg"></i>
                            </div>
                        </div>
                    </button>
                </div>
            <?php elseif (isset($group['city_name'])): ?>
                <!-- City Group Header - Compact with Background Image -->
                <div class="mb-2 mt-4 first:mt-0">
                    <button id="btn-<?php echo $group_id; ?>" onclick="toggleGroup('<?php echo $group_id; ?>')" class="w-full relative h-20 sm:h-24 rounded-xl overflow-hidden shadow-lg transition-all duration-300 text-left group">
                        <!-- Background Image -->
                        <div class="absolute inset-0">
                            <img src="<?php echo htmlspecialchars($header_image); ?>" 
                                 alt="<?php echo htmlspecialchars($group['city_name']); ?>"
                                 class="w-full h-full object-cover transition-transform duration-500">
                            <div class="absolute inset-0 bg-black/60"></div>
                        </div>
                        <!-- Content -->
                        <div class="relative h-full flex items-center justify-between gap-3 px-4 sm:px-5">
                            <div class="flex items-center gap-3 flex-1 min-w-0">
                                <div class="w-9 h-9 sm:w-10 sm:h-10 bg-white/20 backdrop-blur-sm rounded-lg flex items-center justify-center shrink-0">
                                    <i class="bi bi-geo-alt text-white text-base sm:text-lg"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h2 class="text-base sm:text-lg md:text-xl font-bold text-white mb-0.5 line-clamp-1 drop-shadow-lg">
                                        <?php echo htmlspecialchars($group['city_name']); ?>
                                    </h2>
                                    <p class="text-xs text-white/95 flex items-center gap-1.5 line-clamp-1">
                                        <i class="bi bi-pin-map text-[10px]"></i>
                                        <span><?php echo count($group['places']); ?> places</span>
                                    </p>
                                </div>
                            </div>
                            <div class="shrink-0">
                                <i id="icon-<?php echo $group_id; ?>" class="bi bi-chevron-down text-white text-lg sm:text-xl transition-transform duration-500 <?php echo $is_first ? 'rotate-180' : ''; ?> drop-shadow-lg"></i>
                            </div>
                        </div>
                    </button>
                </div>
            <?php endif; ?>
            
            <div id="<?php echo $group_id; ?>" class="group-content grid grid-cols-1 gap-5 sm:gap-6 mb-8 last:mb-0 <?php echo $is_first ? 'open' : 'closed'; ?>">
                <?php foreach ($group['places'] as $place): ?>
                    <?php
                    $place_activities = $place_activities_map[$place['id']] ?? [];
                    $is_best_month = isset($place['is_best_month']) && $place['is_best_month'] == 1;
                    ?>
                    <!-- Premium Place Card - Compact Design -->
                    <div class="bg-white rounded-2xl shadow-md transition-all duration-300 overflow-hidden group place-card relative cursor-pointer" 
                         data-place-id="<?php echo $place['id']; ?>"
                         data-city-id="<?php echo $place['city_id']; ?>"
                         data-state-id="<?php echo $place['state_id']; ?>"
                         data-price-min="<?php echo $place['ticket_price_min'] ?? 0; ?>"
                         data-price-max="<?php echo $place['ticket_price_max'] ?? 0; ?>"
                         onclick="handleCardClick(<?php echo $place['id']; ?>, event)">
                        <!-- Checkbox for Place Selection -->
                        <div class="absolute top-3 right-3 z-20">
                            <label class="custom-checkbox-container cursor-pointer" onclick="event.stopPropagation();">
                                <input type="checkbox" 
                                       class="place-checkbox" 
                                       id="place-<?php echo $place['id']; ?>"
                                       data-place-id="<?php echo $place['id']; ?>"
                                       onclick="togglePlaceSelection(<?php echo $place['id']; ?>, event)">
                                <span class="checkmark"></span>
                            </label>
                        </div>
                            <div class="flex flex-col sm:flex-row">
                                    <!-- Image Section - Reduced Height -->
                                    <div class="sm:w-2/5 relative h-32 sm:h-36 overflow-hidden bg-gray-200">
                                    <img src="<?php echo htmlspecialchars($place['image'] ?: 'https://images.unsplash.com/photo-1501785888041-af3ef285b470'); ?>" 
                                         alt="<?php echo htmlspecialchars($place['name']); ?>"
                                         class="w-full h-full object-cover transition-transform duration-500">
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/40 to-transparent"></div>
                                    <div class="absolute bottom-3 left-3 right-3">

                                        <h3 class="text-base sm:text-lg font-bold text-white drop-shadow-lg line-clamp-1 flex-1">
                                            <?php echo htmlspecialchars($place['name']); ?>
                                        </h3>
                                            
                                        <div class="flex items-center justify-between gap-2 mb-0.5">

                                            <p class="text-xs text-white/90 flex items-center gap-1">
                                                <i class="bi bi-geo-alt"></i>
                                                <span><?php echo htmlspecialchars($place['city_name']); ?>, <?php echo htmlspecialchars($place['state_name']); ?></span>
                                            </p>
                                            <div class="flex items-center gap-1 bg-black/30 backdrop-blur-sm px-2 py-0.5 rounded-full shrink-0">
                                                <i class="bi bi-star-fill text-yellow-400 text-xs sm:text-sm drop-shadow-lg"></i>
                                                <span class="text-white text-xs sm:text-sm font-semibold drop-shadow-lg">
                                                    <?php echo $place['experience_score'] ?: '4'; ?>/5
                                                </span>
                                            </div>
                                            
                                        </div>
                                        
                                    </div>
                                </div>
                                
                                <!-- Content Section - Compact -->
                                <div class="sm:w-3/5 p-4 sm:p-5 flex flex-col justify-between">
                                    <div>
                                        <!-- Info Grid - Compact -->
                                        <div class="grid grid-cols-2 gap-3 mb-3">
                                            <div class="flex items-center gap-2">
                                                <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                                                    <i class="bi bi-clock text-sm"></i>
                                                </div>
                                                <div>
                                                    <div class="text-[10px] text-gray-500">Time</div>
                                                    <div class="text-xs font-semibold text-gray-900"><?php echo $place['avg_visit_time_hours'] ?: '2-3'; ?> hrs</div>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <div class="w-8 h-8 rounded-lg bg-green-50 text-green-600 flex items-center justify-center shrink-0">
                                                    <i class="bi bi-shield-check text-sm"></i>
                                                </div>
                                                <div>
                                                    <div class="text-[10px] text-gray-500">Safety</div>
                                                    <div class="text-xs font-semibold text-gray-900"><?php echo htmlspecialchars($place['safety_level'] ?: 'Medium'); ?></div>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <div class="w-8 h-8 rounded-lg bg-orange-50 text-orange-600 flex items-center justify-center shrink-0">
                                                    <i class="bi bi-sunrise text-sm"></i>
                                                </div>
                                                <div>
                                                    <div class="text-[10px] text-gray-500">Best Time</div>
                                                    <div class="text-xs font-semibold text-gray-900"><?php echo ucfirst($place['best_time_of_day'] ?: 'Anytime'); ?></div>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <div class="w-8 h-8 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center shrink-0">
                                                    <i class="bi bi-ticket-perforated text-sm"></i>
                                                </div>
                                                <div>
                                                    <div class="text-[10px] text-gray-500">Ticket Price</div>
                                                    <div class="text-xs font-semibold text-gray-900">
                                                        <?php 
                                                        $price_min = $place['ticket_price_min'] ?? 0;
                                                        $price_max = $place['ticket_price_max'] ?? 0;
                                                        if ($price_min == 0 && $price_max == 0) {
                                                            echo '<span class="text-green-600">FREE</span>';
                                                        } elseif ($price_min == $price_max) {
                                                            echo '₹' . number_format($price_min, 0);
                                                        } else {
                                                            echo '₹' . number_format($price_min, 0) . ' - ₹' . number_format($price_max, 0);
                                                        }
                                                        ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Activities Section - Compact, No Border -->
                                        <?php if (!empty($place_activities)): ?>
                                            <div class="mt-3">
                                                <h4 class="text-xs font-semibold text-gray-700 mb-2 uppercase tracking-wide flex items-center gap-2">
                                                    <i class="bi bi-activity text-[#009688]"></i> Activities
                                                </h4>
                                                <div class="flex gap-2.5 overflow-x-auto pb-2 custom-scrollbar">
                                                    <?php foreach ($place_activities as $activity): ?>
                                                        <?php
                                                        // Parse activity price (min and max for ranges)
                                                        $activity_price_min = 0;
                                                        $activity_price_max = 0;
                                                        $activity_price_str = trim($activity['ticket_price'] ?? '');
                                                        if ($activity_price_str && strtoupper($activity_price_str) !== 'FREE') {
                                                            // Extract numbers from price string (e.g., "₹500" or "500" or "₹500-1000" or "₹500 to ₹1000")
                                                            preg_match_all('/\d+/', $activity_price_str, $matches);
                                                            if (!empty($matches[0])) {
                                                                $numbers = array_map('floatval', $matches[0]);
                                                                // If range, use first as min and last as max; otherwise use same for both
                                                                if (count($numbers) > 1) {
                                                                    $activity_price_min = $numbers[0];
                                                                    $activity_price_max = $numbers[count($numbers) - 1];
                                                                } else {
                                                                    $activity_price_min = $numbers[0];
                                                                    $activity_price_max = $numbers[0];
                                                                }
                                                            }
                                                        }
                                                        ?>
                                                        <div class="activity-card min-w-[180px] sm:min-w-[200px] bg-white rounded-xl p-2 shadow-sm shrink-0 relative cursor-pointer" 
                                                             style="border: 1px solid #1b191747; background: white;"
                                                             data-activity-id="<?php echo $activity['activity_type_id']; ?>"
                                                             data-place-id="<?php echo $place['id']; ?>"
                                                             data-activity-price-min="<?php echo $activity_price_min; ?>"
                                                             data-activity-price-max="<?php echo $activity_price_max; ?>"
                                                             onclick="handleActivityCardClick(<?php echo $activity['activity_type_id']; ?>, <?php echo $place['id']; ?>, event)">
                                                            <!-- Activity Checkbox -->
                                                            <div class="absolute top-2 right-2 z-10">
                                                                <label class="custom-checkbox-container-small cursor-pointer" onclick="event.stopPropagation();">
                                                                    <input type="checkbox" 
                                                                           class="activity-checkbox" 
                                                                           id="activity-<?php echo $activity['activity_type_id']; ?>-<?php echo $place['id']; ?>"
                                                                           data-activity-id="<?php echo $activity['activity_type_id']; ?>"
                                                                           data-place-id="<?php echo $place['id']; ?>"
                                                                           onclick="toggleActivitySelection(<?php echo $activity['activity_type_id']; ?>, <?php echo $place['id']; ?>, event)">
                                                                    <span class="checkmark-small"></span>
                                                                </label>
                                                            </div>
                                                            <div class="flex gap-2.5">
                                                                <div class="w-12 h-12 rounded-lg overflow-hidden shrink-0">
                                                                    <img src="<?php echo htmlspecialchars($activity['activity_image']); ?>" 
                                                                         alt="<?php echo htmlspecialchars($activity['activity_name']); ?>"
                                                                         class="w-full h-full object-cover">
                                                                </div>
                                                                <div class="flex-1 flex flex-col justify-between min-w-0">
                                                                    <div>
                                                                        <h5 class="text-xs font-bold text-gray-900 mb-0.5 line-clamp-1">
                                                                            <?php echo htmlspecialchars($activity['activity_name']); ?>
                                                                        </h5>
                                                                        <?php if ($activity['open_time'] && $activity['close_time']): ?>
                                                                            <div class="flex items-center gap-1 text-[10px] text-gray-500 mb-1">
                                                                                <i class="bi bi-clock text-purple-500"></i>
                                                                                <span><?php echo date('g:i A', strtotime($activity['open_time'])); ?> – <?php echo date('g:i A', strtotime($activity['close_time'])); ?></span>
                                                                            </div>
                                                                        <?php endif; ?>
                                                                        <?php if ($activity['safety_level']): ?>
                                                                            <div class="flex items-center gap-1 text-[10px] mb-1">
                                                                                <i class="bi bi-shield-check text-green-600"></i>
                                                                                <span class="text-gray-600"><?php echo htmlspecialchars($activity['safety_level']); ?></span>
                                                                            </div>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                    <div class="flex items-center justify-between mt-1">
                                                                        <?php if ($activity['ticket_price'] && strtoupper($activity['ticket_price']) !== 'FREE'): ?>
                                                                            <span class="text-xs font-bold text-gray-900 flex items-center gap-1">
                                                                                <i class="bi bi-cash-coin text-[#009688] text-[10px]"></i>
                                                                                <span><?php echo htmlspecialchars($activity['ticket_price']); ?></span>
                                                                            </span>
                                                                        <?php else: ?>
                                                                            <span class="text-xs font-bold text-green-600">FREE</span>
                                                                        <?php endif; ?>
                                                                        <span class="text-[10px] px-1.5 py-0.5 rounded-full bg-blue-100 text-blue-700 font-semibold">
                                                                            Optional
                                                                        </span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Action Button - Compact -->
                                    <div class="pt-3 mt-3 border-t border-gray-100">
                                        <div class="flex items-center justify-end">
                                            <a href="destination-details.php?id=<?php echo $place['id']; ?>" onclick="event.stopPropagation();" class="text-xs font-semibold text-[#009688] flex items-center gap-1.5 transition-all cursor-pointer">
                                                View Details <i class="bi bi-arrow-right"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</section>

<!-- Selection Summary Navbar -->
<div id="selection-navbar" class="fixed bottom-0 left-0 right-0 bg-white border-t-2 border-[#009688] shadow-2xl z-50 transform translate-y-full transition-transform duration-300">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-3 sm:py-4">
        <!-- Mobile: Stacked Layout -->
        <div class="flex flex-col md:hidden gap-3">
            <!-- Row 1: Summary Stats -->
            <div class="flex items-center gap-2 text-xs justify-center overflow-x-auto pb-1">
                <div class="flex items-center gap-1.5 bg-gray-50 px-2 py-1 rounded-lg shrink-0">
                    <i class="bi bi-geo-alt text-[#009688] text-sm"></i>
                    <span class="font-semibold text-gray-700"><span id="selected-places-count" class="text-[#009688]">0</span> Places</span>
                </div>
                <div class="flex items-center gap-1.5 bg-gray-50 px-2 py-1 rounded-lg shrink-0">
                    <i class="bi bi-pin-map text-[#009688] text-sm"></i>
                    <span class="font-semibold text-gray-700"><span id="selected-cities-count" class="text-[#009688]">0</span> Cities</span>
                </div>
                <div class="flex items-center gap-1.5 bg-gray-50 px-2 py-1 rounded-lg shrink-0">
                    <i class="bi bi-map text-[#009688] text-sm"></i>
                    <span class="font-semibold text-gray-700"><span id="selected-states-count" class="text-[#009688]">0</span> States</span>
                </div>
                <div class="flex items-center gap-1.5 bg-gray-50 px-2 py-1 rounded-lg shrink-0">
                    <i class="bi bi-activity text-[#009688] text-sm"></i>
                    <span class="font-semibold text-gray-700"><span id="selected-activities-count" class="text-[#009688]">0</span> Activities</span>
                </div>
            </div>
            <!-- Row 2: Price and Button -->
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-1.5 bg-[#009688]/10 px-3 py-2 rounded-lg flex-1">
                    <i class="bi bi-cash-coin text-[#009688] text-sm"></i>
                    <span class="font-semibold text-gray-900 text-sm" id="price-range">₹0 - ₹0</span>
                </div>
                <button onclick="proceedToNext()" 
                        class="px-6 py-2.5 bg-[#009688] text-white rounded-lg font-semibold text-sm transition-colors flex items-center justify-center gap-2 shadow-lg shrink-0">
                    Next <i class="bi bi-arrow-right"></i>
                </button>
            </div>
        </div>
        
        <!-- Tablet & Desktop: Single Row Layout -->
        <div class="hidden md:flex items-center justify-between gap-4 lg:gap-6">
            <!-- Summary Stats -->
            <div class="flex items-center gap-3 lg:gap-4 text-sm lg:text-base flex-1">
                <div class="flex items-center gap-2 bg-gray-50 px-3 py-1.5 rounded-lg">
                    <i class="bi bi-geo-alt text-[#009688] text-base"></i>
                    <span class="font-semibold text-gray-700"><span id="selected-places-count-md" class="text-[#009688]">0</span> Places</span>
                </div>
                <div class="flex items-center gap-2 bg-gray-50 px-3 py-1.5 rounded-lg">
                    <i class="bi bi-pin-map text-[#009688] text-base"></i>
                    <span class="font-semibold text-gray-700"><span id="selected-cities-count-md" class="text-[#009688]">0</span> Cities</span>
                </div>
                <div class="flex items-center gap-2 bg-gray-50 px-3 py-1.5 rounded-lg">
                    <i class="bi bi-map text-[#009688] text-base"></i>
                    <span class="font-semibold text-gray-700"><span id="selected-states-count-md" class="text-[#009688]">0</span> States</span>
                </div>
                <div class="flex items-center gap-2 bg-gray-50 px-3 py-1.5 rounded-lg">
                    <i class="bi bi-activity text-[#009688] text-base"></i>
                    <span class="font-semibold text-gray-700"><span id="selected-activities-count-md" class="text-[#009688]">0</span> Activities</span>
                </div>
            </div>
            
            <!-- Price and Button -->
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2 bg-[#009688]/10 px-4 py-2 rounded-lg">
                    <i class="bi bi-cash-coin text-[#009688] text-base"></i>
                    <span class="font-semibold text-gray-900 text-base lg:text-lg" id="price-range-md">₹0 - ₹0</span>
                </div>
                <button onclick="proceedToNext()" 
                        class="px-6 lg:px-8 py-2.5 lg:py-3 bg-[#009688] text-white rounded-lg font-semibold text-sm lg:text-base transition-colors flex items-center justify-center gap-2 shadow-lg hover:bg-[#00796b] shrink-0">
                    Next <i class="bi bi-arrow-right"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Smooth Collapse Animation */
.group-content {
    max-height: 0;
    opacity: 0;
    overflow: hidden;
    transition: max-height 0.5s cubic-bezier(0.4, 0, 0.2, 1), 
                opacity 0.4s ease-in-out,
                margin-bottom 0.3s ease;
}

.group-content.open {
    max-height: 10000px;
    opacity: 1;
    margin-bottom: 2rem;
}

.group-content.closed {
    max-height: 0;
    opacity: 0;
    margin-bottom: 0;
}

/* Place Card Selection */
.place-card.selected {
    border: 2px solid #009688;
    box-shadow: 0 0 0 3px rgba(0, 150, 136, 0.1);
}

.activity-card.selected {
    border: 2px solid #009688 !important;
    box-shadow: 0 0 0 2px rgba(0, 150, 136, 0.2), 0 4px 12px rgba(0, 150, 136, 0.15) !important;
}

/* Custom Checkbox Styles - Modern Design */
.custom-checkbox-container {
    position: relative;
    display: inline-block;
    width: 28px;
    height: 28px;
}

.custom-checkbox-container input {
    position: absolute;
    opacity: 0;
    cursor: pointer;
    height: 0;
    width: 0;
}

.checkmark {
    position: absolute;
    top: 0;
    left: 0;
    height: 28px;
    width: 28px;
    background: white;
    border: 2.5px solid #d1d5db;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}


.custom-checkbox-container input:checked ~ .checkmark {
    background: linear-gradient(135deg, #009688 0%, #00796b 100%);
    border-color: #009688;
    box-shadow: 0 4px 16px rgba(0, 150, 136, 0.4);
}

.checkmark:after {
    content: "";
    position: absolute;
    display: none;
    left: 9px;
    top: 5px;
    width: 6px;
    height: 12px;
    border: solid white;
    border-width: 0 3px 3px 0;
    transform: rotate(45deg);
}

.custom-checkbox-container input:checked ~ .checkmark:after {
    display: block;
}


/* Small Checkbox for Activities */
.custom-checkbox-container-small {
    position: relative;
    display: inline-block;
    width: 22px;
    height: 22px;
}

.custom-checkbox-container-small input {
    position: absolute;
    opacity: 0;
    cursor: pointer;
    height: 0;
    width: 0;
}

.checkmark-small {
    position: absolute;
    top: 0;
    left: 0;
    height: 22px;
    width: 22px;
    background: white;
    border: 2px solid #d1d5db;
    border-radius: 6px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}


.custom-checkbox-container-small input:checked ~ .checkmark-small {
    background: linear-gradient(135deg, #009688 0%, #00796b 100%);
    border-color: #009688;
    box-shadow: 0 3px 12px rgba(0, 150, 136, 0.4);
}

.checkmark-small:after {
    content: "";
    position: absolute;
    display: none;
    left: 7px;
    top: 3px;
    width: 5px;
    height: 10px;
    border: solid white;
    border-width: 0 2px 2px 0;
    transform: rotate(45deg);
}

.custom-checkbox-container-small input:checked ~ .checkmark-small:after {
    display: block;
}

/* Selection Navbar */
#selection-navbar.show {
    transform: translateY(0);
}
</style>

<script>
// Selection Management
let selectedPlaces = new Set();
let selectedActivities = new Set();
let placeDataMap = {}; // Store place data for calculations
let activityDataMap = {}; // Store activity data for calculations

// Initialize place data from DOM
document.addEventListener('DOMContentLoaded', function() {
    const placeCards = document.querySelectorAll('.place-card');
    placeCards.forEach(card => {
        const placeId = parseInt(card.dataset.placeId);
        placeDataMap[placeId] = {
            id: placeId,
            cityId: parseInt(card.dataset.cityId),
            stateId: parseInt(card.dataset.stateId),
            priceMin: parseFloat(card.dataset.priceMin) || 0,
            priceMax: parseFloat(card.dataset.priceMax) || 0
        };
    });
    
    // Initialize activity data from DOM
    const activityCards = document.querySelectorAll('.activity-card');
    activityCards.forEach(card => {
        const activityId = parseInt(card.dataset.activityId);
        const placeId = parseInt(card.dataset.placeId);
        const activityPriceMin = parseFloat(card.dataset.activityPriceMin) || 0;
        const activityPriceMax = parseFloat(card.dataset.activityPriceMax) || 0;
        const activityKey = activityId + '-' + placeId;
        activityDataMap[activityKey] = {
            activityId: activityId,
            placeId: placeId,
            priceMin: activityPriceMin,
            priceMax: activityPriceMax
        };
    });
    
    // Don't load saved selections - clear on refresh
    // loadSelections();
    updateSelectionSummary();
    
    // Initialize accordion
    const firstGroup = document.querySelector('.group-content.open');
    if (firstGroup) {
        openGroupId = firstGroup.id;
        firstGroup.style.maxHeight = firstGroup.scrollHeight + 'px';
    }
});

// Handle card click to toggle selection
function handleCardClick(placeId, event) {
    // Don't toggle if clicking on checkbox, activity card, or view details button
    if (event.target.closest('.custom-checkbox-container') || 
        event.target.closest('.activity-card') || 
        event.target.closest('a[href*="destination-details"]')) {
        return;
    }
    
    const checkbox = document.getElementById('place-' + placeId);
    if (checkbox) {
        checkbox.checked = !checkbox.checked;
        togglePlaceSelection(placeId, event);
    }
}

// Toggle Place Selection
function togglePlaceSelection(placeId, event) {
    if (event) {
        event.stopPropagation();
    }
    const checkbox = document.getElementById('place-' + placeId);
    const card = checkbox.closest('.place-card');
    
    if (checkbox.checked) {
        selectedPlaces.add(placeId);
        card.classList.add('selected');
    } else {
        selectedPlaces.delete(placeId);
        card.classList.remove('selected');
        // Remove activities from this place
        const activityCheckboxes = card.querySelectorAll('.activity-checkbox');
        activityCheckboxes.forEach(acb => {
            if (acb.checked) {
                acb.checked = false;
                const activityId = parseInt(acb.dataset.activityId);
                const placeIdFromActivity = parseInt(acb.dataset.placeId);
                selectedActivities.delete(activityId + '-' + placeIdFromActivity);
                // Update visual state
                const activityCard = acb.closest('.activity-card');
                if (activityCard) {
                    activityCard.classList.remove('selected');
                }
            }
        });
    }
    
    saveSelections();
    updateSelectionSummary();
}

// Handle activity card click to toggle selection
function handleActivityCardClick(activityId, placeId, event) {
    // Don't toggle if clicking on checkbox
    if (event.target.closest('.custom-checkbox-container-small')) {
        return;
    }
    
    const checkbox = document.getElementById('activity-' + activityId + '-' + placeId);
    if (checkbox) {
        checkbox.checked = !checkbox.checked;
        toggleActivitySelection(activityId, placeId, event);
    }
}

// Toggle Activity Selection
function toggleActivitySelection(activityId, placeId, event) {
    if (event) {
        event.stopPropagation();
    }
    const checkbox = document.getElementById('activity-' + activityId + '-' + placeId);
    const activityKey = activityId + '-' + placeId;
    const activityCard = checkbox.closest('.activity-card');
    
    if (checkbox.checked) {
        selectedActivities.add(activityKey);
        if (activityCard) {
            activityCard.classList.add('selected');
        }
        
        // Auto-select place if no places are selected
        if (selectedPlaces.size === 0) {
            const placeCheckbox = document.getElementById('place-' + placeId);
            if (placeCheckbox && !placeCheckbox.checked) {
                placeCheckbox.checked = true;
                selectedPlaces.add(placeId);
                const placeCard = placeCheckbox.closest('.place-card');
                if (placeCard) {
                    placeCard.classList.add('selected');
                }
            }
        }
        
        // Ensure the place is selected
        if (!selectedPlaces.has(placeId)) {
            const placeCheckbox = document.getElementById('place-' + placeId);
            if (placeCheckbox) {
                placeCheckbox.checked = true;
                selectedPlaces.add(placeId);
                const placeCard = placeCheckbox.closest('.place-card');
                if (placeCard) {
                    placeCard.classList.add('selected');
                }
            }
        }
    } else {
        selectedActivities.delete(activityKey);
        if (activityCard) {
            activityCard.classList.remove('selected');
        }
    }
    
    saveSelections();
    updateSelectionSummary();
}

// Update Selection Summary
function updateSelectionSummary() {
    const placesCount = selectedPlaces.size;
    const citiesSet = new Set();
    const statesSet = new Set();
    const activitiesSet = new Set();
    let placeTotalMin = 0;
    let placeTotalMax = 0;
    let activityTotalMin = 0;
    let activityTotalMax = 0;
    let hasFreePlace = false;
    let hasPaidActivity = false;
    
    // Calculate cities, states, and price range from selected places
    selectedPlaces.forEach(placeId => {
        const placeData = placeDataMap[placeId];
        if (placeData) {
            citiesSet.add(placeData.cityId);
            statesSet.add(placeData.stateId);
            
            if (placeData.priceMin === 0 && placeData.priceMax === 0) {
                hasFreePlace = true;
            } else {
                placeTotalMin += placeData.priceMin || 0;
                placeTotalMax += placeData.priceMax || 0;
            }
        }
    });
    
    // Calculate activity prices
    selectedActivities.forEach(activityKey => {
        const activityId = activityKey.split('-')[0];
        activitiesSet.add(activityId);
        
        const activityData = activityDataMap[activityKey];
        if (activityData) {
            if (activityData.priceMin > 0 || activityData.priceMax > 0) {
                activityTotalMin += activityData.priceMin || 0;
                activityTotalMax += activityData.priceMax || 0;
                hasPaidActivity = true;
            }
        }
    });
    
    // Update UI - Mobile
    document.getElementById('selected-places-count').textContent = placesCount;
    document.getElementById('selected-cities-count').textContent = citiesSet.size;
    document.getElementById('selected-states-count').textContent = statesSet.size;
    document.getElementById('selected-activities-count').textContent = activitiesSet.size;
    
    // Update UI - Tablet/Desktop
    const placesCountMd = document.getElementById('selected-places-count-md');
    const citiesCountMd = document.getElementById('selected-cities-count-md');
    const statesCountMd = document.getElementById('selected-states-count-md');
    const activitiesCountMd = document.getElementById('selected-activities-count-md');
    if (placesCountMd) placesCountMd.textContent = placesCount;
    if (citiesCountMd) citiesCountMd.textContent = citiesSet.size;
    if (statesCountMd) statesCountMd.textContent = statesSet.size;
    if (activitiesCountMd) activitiesCountMd.textContent = activitiesSet.size;
    
    // Calculate total price range (places + activities)
    const totalMin = placeTotalMin + activityTotalMin;
    const totalMax = placeTotalMax + activityTotalMax;
    
    // Update price range
    let priceText = '';
    if (hasPaidActivity || placeTotalMin > 0 || placeTotalMax > 0) {
        // If there are paid items, just show the price (don't show "FREE +")
        if (totalMin === totalMax) {
            priceText = '₹' + numberFormat(totalMin);
        } else {
            priceText = '₹' + numberFormat(totalMin) + ' - ₹' + numberFormat(totalMax);
        }
    } else if (hasFreePlace && !hasPaidActivity) {
        // Only show "FREE" if everything is actually free
        priceText = 'FREE';
    } else {
        priceText = '₹0';
    }
    
    document.getElementById('price-range').textContent = priceText;
    const priceRangeMd = document.getElementById('price-range-md');
    if (priceRangeMd) priceRangeMd.textContent = priceText;
    
    // Show/hide navbar
    const navbar = document.getElementById('selection-navbar');
    if (placesCount > 0 || activitiesSet.size > 0) {
        navbar.classList.add('show');
    } else {
        navbar.classList.remove('show');
    }
}

// Save selections to localStorage
function saveSelections() {
    localStorage.setItem('selectedPlaces', JSON.stringify(Array.from(selectedPlaces)));
    localStorage.setItem('selectedActivities', JSON.stringify(Array.from(selectedActivities)));
}

// Load selections from localStorage
function loadSelections() {
    const savedPlaces = localStorage.getItem('selectedPlaces');
    const savedActivities = localStorage.getItem('selectedActivities');
    
    if (savedPlaces) {
        const places = JSON.parse(savedPlaces);
        places.forEach(placeId => {
            selectedPlaces.add(placeId);
            const checkbox = document.getElementById('place-' + placeId);
            if (checkbox) {
                checkbox.checked = true;
                checkbox.closest('.place-card').classList.add('selected');
            }
        });
    }
    
    if (savedActivities) {
        const activities = JSON.parse(savedActivities);
        activities.forEach(activityKey => {
            selectedActivities.add(activityKey);
            const parts = activityKey.split('-');
            const activityId = parts[0];
            const placeId = parts[1];
            const checkbox = document.getElementById('activity-' + activityId + '-' + placeId);
            if (checkbox) {
                checkbox.checked = true;
                checkbox.closest('.activity-card').classList.add('selected');
            }
        });
    }
}

// Number formatting helper
function numberFormat(num) {
    return new Intl.NumberFormat('en-IN').format(Math.round(num));
}

// Clear all selections
function clearAllSelections() {
    if (confirm('Are you sure you want to clear all selections?')) {
        // Clear place selections
        selectedPlaces.forEach(placeId => {
            const checkbox = document.getElementById('place-' + placeId);
            if (checkbox) {
                checkbox.checked = false;
                checkbox.closest('.place-card').classList.remove('selected');
            }
        });
        selectedPlaces.clear();
        
        // Clear activity selections
        selectedActivities.forEach(activityKey => {
            const parts = activityKey.split('-');
            const activityId = parts[0];
            const placeId = parts[1];
            const checkbox = document.getElementById('activity-' + activityId + '-' + placeId);
            if (checkbox) {
                checkbox.checked = false;
                checkbox.closest('.activity-card').classList.remove('selected');
            }
        });
        selectedActivities.clear();
        
        saveSelections();
        updateSelectionSummary();
    }
}

// Select all visible places
function selectAllVisible() {
    const visiblePlaceCards = document.querySelectorAll('.group-content.open .place-card');
    visiblePlaceCards.forEach(card => {
        const placeId = parseInt(card.dataset.placeId);
        if (!selectedPlaces.has(placeId)) {
            const checkbox = document.getElementById('place-' + placeId);
            if (checkbox) {
                checkbox.checked = true;
                selectedPlaces.add(placeId);
                card.classList.add('selected');
            }
        }
    });
    
    saveSelections();
    updateSelectionSummary();
}

// Proceed to next step
function proceedToNext() {
    const placesArray = Array.from(selectedPlaces);
    
    // Extract activity data with prices (format: "activityId-placeId")
    const activitiesData = [];
    selectedActivities.forEach(activityKey => {
        const parts = activityKey.split('-');
        const activityId = parseInt(parts[0]);
        const placeId = parseInt(parts[1]);
        const activityData = activityDataMap[activityKey];
        if (activityData) {
            activitiesData.push({
                id: activityId,
                placeId: placeId,
                priceMin: activityData.priceMin || 0,
                priceMax: activityData.priceMax || 0
            });
        }
    });
    
    // Calculate places price range
    let placesPriceMin = 0;
    let placesPriceMax = 0;
    placesArray.forEach(placeId => {
        const placeData = placeDataMap[placeId];
        if (placeData) {
            placesPriceMin += placeData.priceMin || 0;
            placesPriceMax += placeData.priceMax || 0;
        }
    });
    
    // Calculate activities price range
    let activitiesPriceMin = 0;
    let activitiesPriceMax = 0;
    activitiesData.forEach(activity => {
        activitiesPriceMin += activity.priceMin || 0;
        activitiesPriceMax += activity.priceMax || 0;
    });
    
    if (placesArray.length === 0 && activitiesData.length === 0) {
        alert('Please select at least one place or activity');
        return;
    }
    
    // Redirect to transport booking page with all data
    const params = new URLSearchParams();
    if (placesArray.length > 0) {
        params.append('places', placesArray.join(','));
        if (placesPriceMin > 0 || placesPriceMax > 0) {
            params.append('places_price_min', placesPriceMin);
            params.append('places_price_max', placesPriceMax);
        }
    }
    if (activitiesData.length > 0) {
        // Pass activities as JSON string
        params.append('activities', JSON.stringify(activitiesData));
        if (activitiesPriceMin > 0 || activitiesPriceMax > 0) {
            params.append('activities_price_min', activitiesPriceMin);
            params.append('activities_price_max', activitiesPriceMax);
        }
    }
    
    window.location.href = 'transport-booking.php?' + params.toString();
}

// Accordion/Collapse functionality - Only one group open at a time with smooth animations
let openGroupId = null;

// Accordion functionality is now in the DOMContentLoaded above

function toggleGroup(groupId) {
    const groupContent = document.getElementById(groupId);
    const groupIcon = document.getElementById('icon-' + groupId);
    
    if (!groupContent) return;
    
    // If this group is already open, close it
    if (openGroupId === groupId) {
        groupContent.style.maxHeight = '0px';
        groupContent.classList.remove('open');
        groupContent.classList.add('closed');
        groupIcon.classList.remove('rotate-180');
        openGroupId = null;
    } else {
        // Close currently open group with animation
        if (openGroupId) {
            const currentGroup = document.getElementById(openGroupId);
            const currentIcon = document.getElementById('icon-' + openGroupId);
            if (currentGroup) {
                currentGroup.style.maxHeight = '0px';
                currentGroup.classList.remove('open');
                currentGroup.classList.add('closed');
            }
            if (currentIcon) {
                currentIcon.classList.remove('rotate-180');
            }
        }
        
        // Open clicked group with animation
        groupContent.classList.remove('closed');
        groupContent.classList.add('open');
        groupContent.style.maxHeight = groupContent.scrollHeight + 'px';
        groupIcon.classList.add('rotate-180');
        openGroupId = groupId;
        
        // Update max-height after content is rendered
        setTimeout(() => {
            groupContent.style.maxHeight = groupContent.scrollHeight + 'px';
        }, 10);
        
        // Smooth scroll to the group header button
        const groupButton = document.getElementById('btn-' + groupId);
        if (groupButton) {
            setTimeout(() => {
                groupButton.scrollIntoView({ 
                    behavior: 'smooth', 
                    block: 'start',
                    inline: 'nearest'
                });
            }, 100);
        }
    }
}
</script>

<?php include 'includes/footer.php'; ?>

