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
            <a href="index.php" class="text-sm text-[#009688] hover:underline flex items-center gap-1">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>
</div>

<!-- Places Container -->
<section class="max-w-6xl mx-auto px-4 py-4 sm:py-6 space-y-6 pb-20">
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
                    <button id="btn-<?php echo $group_id; ?>" onclick="toggleGroup('<?php echo $group_id; ?>')" class="w-full relative h-20 sm:h-24 rounded-xl overflow-hidden shadow-lg hover:shadow-xl transition-all duration-300 text-left group">
                        <!-- Background Image -->
                        <div class="absolute inset-0">
                            <img src="<?php echo htmlspecialchars($header_image); ?>" 
                                 alt="<?php echo htmlspecialchars($group['state_name']); ?>"
                                 class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
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
                    <button id="btn-<?php echo $group_id; ?>" onclick="toggleGroup('<?php echo $group_id; ?>')" class="w-full relative h-20 sm:h-24 rounded-xl overflow-hidden shadow-lg hover:shadow-xl transition-all duration-300 text-left group">
                        <!-- Background Image -->
                        <div class="absolute inset-0">
                            <img src="<?php echo htmlspecialchars($header_image); ?>" 
                                 alt="<?php echo htmlspecialchars($group['city_name']); ?>"
                                 class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
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
                    <div class="bg-white rounded-2xl shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden group">
                        <a href="destination-details.php?id=<?php echo $place['id']; ?>" class="block">
                            <div class="flex flex-col sm:flex-row">
                                <!-- Image Section - Reduced Height -->
                                <div class="sm:w-2/5 relative h-32 sm:h-36 overflow-hidden bg-gray-200">
                                    <img src="<?php echo htmlspecialchars($place['image'] ?: 'https://images.unsplash.com/photo-1501785888041-af3ef285b470'); ?>" 
                                         alt="<?php echo htmlspecialchars($place['name']); ?>"
                                         class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
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
                                                        <div class="activity-card min-w-[180px] sm:min-w-[200px] bg-white rounded-xl p-2 shadow-sm hover:shadow-md transition-all shrink-0" style="border: 1px solid #1b191747;">
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
                                            <a href="destination-details.php?id=<?php echo $place['id']; ?>" onclick="event.stopPropagation();" class="text-xs font-semibold text-[#009688] flex items-center gap-1.5 hover:gap-2 transition-all cursor-pointer hover:text-[#00796b]">
                                                View Details <i class="bi bi-arrow-right"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</section>

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
</style>

<script>
// Accordion/Collapse functionality - Only one group open at a time with smooth animations
let openGroupId = null;

// Open first group by default
document.addEventListener('DOMContentLoaded', function() {
    const firstGroup = document.querySelector('.group-content.open');
    if (firstGroup) {
        openGroupId = firstGroup.id;
        // Set initial max-height for smooth animation
        firstGroup.style.maxHeight = firstGroup.scrollHeight + 'px';
    }
});

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

