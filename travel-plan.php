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

// Get transport info
$transport_rule_id = isset($_GET['transport_rule_id']) ? (int)$_GET['transport_rule_id'] : 0;
$transport_distance = isset($_GET['distance']) ? floatval($_GET['distance']) : 0;
$transport_price = isset($_GET['transport_price']) ? floatval($_GET['transport_price']) : 0;
$transport_price_min = isset($_GET['transport_price_min']) ? floatval($_GET['transport_price_min']) : 0;
$transport_price_max = isset($_GET['transport_price_max']) ? floatval($_GET['transport_price_max']) : 0;

// Get user's current location (passed from transport-booking.php)
$my_location_lat = isset($_GET['my_lat']) ? floatval($_GET['my_lat']) : 0;
$my_location_lng = isset($_GET['my_lng']) ? floatval($_GET['my_lng']) : 0;
$my_location_address = isset($_GET['my_address']) ? $_GET['my_address'] : '';

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

// Get selected activities information
$activities_data_from_url = [];
$activities_by_place = [];

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

// Calculate base cost (without food)
$base_cost_min = $places_price_min + $activities_price_min + ($transport_price_min > 0 ? $transport_price_min : $transport_price) + ($hotel_price_min > 0 ? $hotel_price_min : 0);
$base_cost_max = $places_price_max + $activities_price_max + ($transport_price_max > 0 ? $transport_price_max : $transport_price) + ($hotel_price_max > 0 ? $hotel_price_max : 0);

// Build comprehensive prompt with ALL database information
$prompt = "==============================================\n";
$prompt .= "TRAVEL PLANNING REQUEST\n";
$prompt .= "==============================================\n\n";

// Add user's starting location
if ($my_location_lat && $my_location_lng) {
    $prompt .= "MY STARTING LOCATION:\n";
    $prompt .= "Coordinates: " . $my_location_lat . ", " . $my_location_lng . "\n";
    if ($my_location_address) {
        $prompt .= "Address: " . $my_location_address . "\n";
    }
    $prompt .= "\nIMPORTANT: Identify nearest major railway station or airport to my starting location.\n";
    $prompt .= "Mention this as my departure point for journey planning.\n\n";
}

$prompt .= "==============================================\n";
$prompt .= "DESTINATIONS & DETAILED INFO:\n";
$prompt .= "==============================================\n\n";

// Get full place information with all fields from database
$all_places_data = [];
if (!empty($selected_places)) {
    $place_ids = array_map('intval', array_filter($selected_places));
    if (!empty($place_ids)) {
        $place_ids_str = implode(',', $place_ids);
        $full_places_query = "SELECT p.*, 
                        d.id as city_id, d.name as city_name, d.latitude, d.longitude,
                        s.id as state_id, s.name as state_name
                        FROM places p
                        JOIN districts d ON p.city_id = d.id
                        JOIN states s ON d.state_id = s.id
                        WHERE p.id IN ($place_ids_str) AND p.status = 'active'";
        $full_places_result = $conn->query($full_places_query);
        if ($full_places_result) {
            while ($row = $full_places_result->fetch_assoc()) {
                $all_places_data[] = $row;
            }
        }
    }
}

// Group places by city for better planning
$places_by_city = [];
foreach ($all_places_data as $place) {
    $city_key = $place['city_name'];
    if (!isset($places_by_city[$city_key])) {
        $places_by_city[$city_key] = [
            'city_name' => $place['city_name'],
            'state_name' => $place['state_name'],
            'latitude' => $place['latitude'],
            'longitude' => $place['longitude'],
            'places' => []
        ];
    }
    $places_by_city[$city_key]['places'][] = $place;
}

foreach ($places_by_city as $city_key => $city_data) {
    $prompt .= "CITY: " . $city_data['city_name'] . ", " . $city_data['state_name'] . "\n";
    $prompt .= "==============================================\n\n";
    
    foreach ($city_data['places'] as $place) {
        $prompt .= "PLACE: " . $place['name'] . "\n";
        
        // Visit Time (CRITICAL FOR PLANNING)
        $avg_visit_hours = $place['avg_visit_time_hours'] ?? 2;
        $prompt .= "Average Visit Time: " . $avg_visit_hours . " hours\n";
        
        // Best Time to Visit
        $best_time = $place['best_time_of_day'] ?? 'anytime';
        $prompt .= "Best Time to Visit: " . ucfirst($best_time) . "\n";
        
        // Crowd Level & Waiting
        $crowd_level = $place['crowd_level'] ?? 'medium';
        $wait_time = $place['avg_waiting_time_minutes'] ?? 15;
        $prompt .= "Crowd Level: " . ucfirst($crowd_level) . " (Avg Wait: " . $wait_time . " mins)\n";
        
        // Entry Pricing
        $place_min = $place['ticket_price_min'] ?? 0;
        $place_max = $place['ticket_price_max'] ?? 0;
        if ($place_min > 0 || $place_max > 0) {
            if ($place_min == $place_max) {
                $prompt .= "Entry Fee: ₹" . number_format($place_min, 2) . " per person\n";
            } else {
                $prompt .= "Entry Fee: ₹" . number_format($place_min, 2) . " - ₹" . number_format($place_max, 2) . " per person\n";
            }
        } else {
            $prompt .= "Entry Fee: FREE\n";
        }
        
        // Safety Information
        $safety_level = $place['safety_level'] ?? 'Medium';
        $prompt .= "Safety Level: " . $safety_level . "\n";
        
        // Physical Effort
        $physical_effort = $place['physical_effort'] ?? 'low';
        $prompt .= "Physical Effort: " . ucfirst($physical_effort) . "\n";
        
        // Family Friendliness
        $child_friendly = $place['child_friendly'] ?? 'yes';
        $senior_friendly = $place['senior_friendly'] ?? 'yes';
        $prompt .= "Child Friendly: " . ucfirst($child_friendly) . " | Senior Friendly: " . ucfirst($senior_friendly) . "\n";
        
        // Weather & Monsoon Risk
        $weather_dep = $place['weather_dependency'] ?? 'low';
        $monsoon_risk = $place['monsoon_risk'] ?? 'safe';
        $prompt .= "Weather Dependency: " . ucfirst($weather_dep) . " | Monsoon Risk: " . ucfirst($monsoon_risk) . "\n";
        
        // Night Travel Safety
        if (!empty($place['night_travel_safety'])) {
            $prompt .= "Night Travel: " . $place['night_travel_safety'] . "\n";
        }
        
        // Activities at this place
        if (isset($activities_by_place[$place['id']]) && !empty($activities_by_place[$place['id']])) {
            $prompt .= "Activities:\n";
            foreach ($activities_by_place[$place['id']] as $act_data) {
                $activity_info = $activities_info_map[$act_data['id']] ?? null;
                if ($activity_info) {
                    $act_price_min = $act_data['priceMin'] ?? 0;
                    $act_price_max = $act_data['priceMax'] ?? 0;
                    
                    $prompt .= "  - " . $activity_info['name'];
                    if ($act_price_min > 0 || $act_price_max > 0) {
                        if ($act_price_min == $act_price_max) {
                            $prompt .= " (₹" . number_format($act_price_min, 2) . ")";
                        } else {
                            $prompt .= " (₹" . number_format($act_price_min, 2) . "-₹" . number_format($act_price_max, 2) . ")";
                        }
                    } else {
                        $prompt .= " (FREE)";
                    }
                    $prompt .= "\n";
                }
            }
        }
        
        $prompt .= "\n";
    }
    $prompt .= "\n";
}

// Calculate distances between cities (using Haversine formula)
$city_coords = [];
foreach ($places_by_city as $city_key => $city_data) {
    $city_coords[$city_key] = [
        'lat' => $city_data['latitude'],
        'lon' => $city_data['longitude']
    ];
}

// Transport info with detailed journey planning
$prompt .= "MAIN TRANSPORT (From Starting Location to Destination):\n";
$prompt .= "========================================================\n";
if ($transport_info) {
    $transport_mode = strtolower($transport_info['transport_name']);
    $prompt .= "Selected Mode: " . $transport_info['transport_name'];
    if (!empty($transport_info['sub_type'])) {
        $prompt .= " (" . $transport_info['sub_type'] . ")";
    }
    $prompt .= "\n";
    $prompt .= "Total Distance: " . number_format($transport_distance, 2) . " km\n";
    
    // Calculate travel time based on transport mode
    if (stripos($transport_mode, 'car') !== false || stripos($transport_mode, 'taxi') !== false) {
        // Car/Taxi: Average 50-60 km/hr on highways
        $travel_hours = round($transport_distance / 55, 1);
        $travel_days = ceil($travel_hours / 8); // Max 8 hours driving per day for safety
        
        $prompt .= "Estimated Travel Time: " . $travel_hours . " hours\n";
        if ($travel_days > 1) {
            $prompt .= "Recommended Road Travel Days: " . $travel_days . " days (8 hours driving per day max for safety)\n";
            $prompt .= "Overnight Stops Needed: " . ($travel_days - 1) . " night(s) on the way\n";
            $prompt .= "IMPORTANT: Plan overnight stays at midway cities for safety. No night driving.\n";
        } else {
            $prompt .= "Can be completed in 1 day\n";
        }
        
    } elseif (stripos($transport_mode, 'bus') !== false) {
        // Bus: Average 40-50 km/hr
        $travel_hours = round($transport_distance / 45, 1);
        $prompt .= "Estimated Travel Time: " . $travel_hours . " hours\n";
        if ($travel_hours > 8) {
            $prompt .= "Consider overnight bus or break journey with 1 night stay\n";
        }
        
    } elseif (stripos($transport_mode, 'train') !== false) {
        // Train: Average 50-80 km/hr depending on train type
        $travel_hours = round($transport_distance / 60, 1);
        $prompt .= "Estimated Travel Time: " . $travel_hours . " hours\n";
        
        // Get destination city for train info
        $destination_city = !empty($places_info) ? $places_info[0]['city_name'] : 'destination';
        $prompt .= "\nTRAIN JOURNEY DETAILS TO PROVIDE:\n";
        $prompt .= "- Suggest nearest major railway station to " . $destination_city . "\n";
        $prompt .= "- Mention popular trains on this route\n";
        $prompt .= "- Station to hotel distance (estimate 5-15 km for city center hotels)\n";
        $prompt .= "- Local transport from station to hotel (auto/taxi ₹50-200)\n";
        $prompt .= "- Best train timing (prefer morning/afternoon arrival, avoid late night)\n";
        
    } elseif (stripos($transport_mode, 'flight') !== false || stripos($transport_mode, 'air') !== false) {
        // Flight: Much faster
        $flight_hours = round($transport_distance / 600, 1); // Average flight speed ~600 km/hr
        $prompt .= "Flight Duration: ~" . $flight_hours . " hour(s)\n";
        
        // Get destination city for airport info
        $destination_city = !empty($places_info) ? $places_info[0]['city_name'] : 'destination';
        $destination_state = !empty($places_info) ? $places_info[0]['state_name'] : '';
        
        $prompt .= "\nFLIGHT & AIRPORT DETAILS TO PROVIDE:\n";
        $prompt .= "- Nearest airport to " . $destination_city . ($destination_state ? ", " . $destination_state : "") . "\n";
        $prompt .= "- Airport name and code (e.g., Biju Patnaik Airport - BBI for Bhubaneswar)\n";
        $prompt .= "- Airport to city/hotel distance (estimate 10-30 km)\n";
        $prompt .= "- Transport options from airport:\n";
        $prompt .= "  * Prepaid taxi: ₹300-800 (depending on distance)\n";
        $prompt .= "  * Airport shuttle/bus: ₹50-150\n";
        $prompt .= "  * App-based cab: ₹250-600\n";
        $prompt .= "- Travel time from airport to hotel area: 30-60 minutes\n";
        $prompt .= "- Suggest reaching hotel by afternoon for fresh start next day\n";
        
    } elseif (stripos($transport_mode, 'bike') !== false) {
        // Bike: Average 40-50 km/hr
        $travel_hours = round($transport_distance / 45, 1);
        $travel_days = ceil($travel_hours / 6); // Max 6 hours biking per day
        
        $prompt .= "Estimated Travel Time: " . $travel_hours . " hours\n";
        $prompt .= "Recommended Biking Days: " . $travel_days . " days (6 hours riding per day max)\n";
        if ($travel_days > 1) {
            $prompt .= "Overnight Stops Needed: " . ($travel_days - 1) . " night(s)\n";
            $prompt .= "IMPORTANT: Plan overnight stays. Take rest breaks every 2 hours.\n";
        }
    }
    
    // Cost information
    if ($transport_price_min > 0 || $transport_price_max > 0) {
        if ($transport_price_min == $transport_price_max) {
            $prompt .= "Transport Cost: ₹" . number_format($transport_price_min, 2) . "\n";
        } else {
            $prompt .= "Transport Cost: ₹" . number_format($transport_price_min, 2) . " - ₹" . number_format($transport_price_max, 2) . "\n";
        }
    } elseif ($transport_price > 0) {
        $prompt .= "Transport Cost: ₹" . number_format($transport_price, 2) . "\n";
    }
    
} else {
    $prompt .= "Not selected (Assuming already at destination or local travel only)\n";
}
$prompt .= "\n";

// Stay info
$prompt .= "ACCOMMODATION:\n";
if ($hotel_info) {
    $prompt .= "Type: " . $hotel_info['name'] . "\n";
    
    if ($hotel_price_min > 0 || $hotel_price_max > 0) {
        if ($hotel_price_min == $hotel_price_max) {
            $prompt .= "Cost: ₹" . number_format($hotel_price_min, 2) . " per night\n";
        } else {
            $prompt .= "Cost: ₹" . number_format($hotel_price_min, 2) . " - ₹" . number_format($hotel_price_max, 2) . " per night\n";
        }
    } elseif ($hotel_price) {
        $prompt .= "Cost: " . $hotel_price . " per night\n";
    }
} else {
    $prompt .= "Not selected\n";
}
$prompt .= "\n";

// Base cost
$prompt .= "BASE COST (Entry + Activities + Transport + Stay):\n";
if ($base_cost_min != $base_cost_max) {
    $prompt .= "₹" . number_format($base_cost_min, 2) . " - ₹" . number_format($base_cost_max, 2) . "\n\n";
} else {
    $prompt .= "₹" . number_format($base_cost_max, 2) . "\n\n";
}

// Planning Rules
$prompt .= "PLANNING RULES (MUST FOLLOW):\n\n";
$prompt .= "1. TIME MANAGEMENT:\n";
$prompt .= "   - Use 'Average Visit Time' mentioned for each place\n";
$prompt .= "   - Maximum 6 hours sightseeing per day\n";
$prompt .= "   - Start day early (by 7-8 AM) to avoid crowds\n";
$prompt .= "   - End day by 6-7 PM for safety\n\n";

$prompt .= "2. BEST TIME SCHEDULING:\n";
$prompt .= "   - Visit temples in MORNING (as per database)\n";
$prompt .= "   - Follow 'Best Time to Visit' for each place\n";
$prompt .= "   - Avoid high crowd level places during peak hours\n\n";

$prompt .= "3. TRAVEL SAFETY:\n";
$prompt .= "   - NO NIGHT TRAVEL (after 6 PM)\n";
$prompt .= "   - If main journey > 8 hours: Include as separate travel day(s)\n";
$prompt .= "   - If travel between cities > 6 hours: Stay overnight in that city\n";
$prompt .= "   - Start next morning fresh after arrival\n";
$prompt .= "   - Use 'Night Travel Safety' info from database\n";
$prompt .= "   - For flights/trains: Suggest afternoon arrival for fresh start\n\n";

$prompt .= "4. ACCOMMODATION:\n";
// Check for specific cities
$has_puri = false;
$main_cities = [];
foreach ($places_by_city as $city_key => $city_data) {
    $main_cities[] = $city_data['city_name'];
    if (stripos($city_data['city_name'], 'Puri') !== false) {
        $has_puri = true;
    }
}

if ($has_puri) {
    $prompt .= "   - Suggest BEST AREA TO STAY IN PURI (near beach/temple)\n";
}
if (count($main_cities) > 1) {
    $prompt .= "   - For multi-city trip: Stay in each city if travel > 6 hrs\n";
    $prompt .= "   - Suggest best area in each city: " . implode(", ", $main_cities) . "\n";
} elseif (!empty($main_cities)) {
    $prompt .= "   - Suggest best area to stay in " . $main_cities[0] . "\n";
}
$prompt .= "\n";

$prompt .= "5. MEAL PLANNING:\n";
$prompt .= "   - Breakfast: Near hotel (₹100-200)\n";
$prompt .= "   - Lunch: Near attraction (₹150-300)\n";
$prompt .= "   - Dinner: Near beach/market (₹200-400)\n";
$prompt .= "   - Include local cuisine suggestions\n\n";

$prompt .= "6. SPECIAL CONSIDERATIONS:\n";
$prompt .= "   - Consider 'Physical Effort' level for elderly/children\n";
$prompt .= "   - Check 'Child Friendly' & 'Senior Friendly' ratings\n";
$prompt .= "   - Add buffer time for 'Average Waiting Time'\n";
$prompt .= "   - Consider 'Weather Dependency' & 'Monsoon Risk'\n\n";

// Output format with proper headings
$prompt .= "==============================================\n";
$prompt .= "OUTPUT FORMAT (USE PROPER HEADINGS):\n";
$prompt .= "==============================================\n\n";

$prompt .= "SECTION 1: JOURNEY FROM MY LOCATION TO DESTINATION\n";
$prompt .= "Write proper heading: \"## Journey from [My Location] to [Destination]\"\n\n";
if ($transport_info) {
    $transport_mode = strtolower($transport_info['transport_name']);
    if (stripos($transport_mode, 'train') !== false) {
        $prompt .= "Include:\n";
        $prompt .= "   - Nearest railway station to my location (departure point)\n";
        $prompt .= "   - Railway station at destination (arrival point)\n";
        $prompt .= "   - Major trains on this route with timing\n";
        $prompt .= "   - Station to hotel distance at destination\n";
        $prompt .= "   - How to reach hotel from station (cost)\n";
    } elseif (stripos($transport_mode, 'flight') !== false || stripos($transport_mode, 'air') !== false) {
        $prompt .= "Include:\n";
        $prompt .= "   - Nearest airport to my location (departure)\n";
        $prompt .= "   - Airport at destination (arrival) with code\n";
        $prompt .= "   - Flight duration\n";
        $prompt .= "   - Airport to hotel distance\n";
        $prompt .= "   - Transfer options (taxi/shuttle/cab) with costs\n";
    } elseif (stripos($transport_mode, 'car') !== false || stripos($transport_mode, 'bike') !== false) {
        $prompt .= "Include:\n";
        $prompt .= "   - Road travel days needed\n";
        $prompt .= "   - Route: City 1 → City 2 → Destination\n";
        $prompt .= "   - Overnight stop cities if journey > 8 hours\n";
        $prompt .= "   - Rest stops and refueling points\n";
    } elseif (stripos($transport_mode, 'bus') !== false) {
        $prompt .= "Include:\n";
        $prompt .= "   - Bus stand at my location\n";
        $prompt .= "   - Bus terminal at destination\n";
        $prompt .= "   - Terminal to hotel distance\n";
    }
}
$prompt .= "\n";

$prompt .= "SECTION 2: TRIP OVERVIEW\n";
$prompt .= "Write proper heading: \"## Trip Overview\"\n";
$prompt .= "Include:\n";
$prompt .= "   - Total Days: X days (breakdown: travel + sightseeing)\n";
$prompt .= "   - Best Area to Stay: [Specific locality with reason]\n";
$prompt .= "   - Trip Type: [Family/Solo/Adventure]\n\n";

$prompt .= "SECTION 3: DAY-BY-DAY DETAILED ITINERARY\n";
$prompt .= "Write proper heading: \"## Day-by-Day Itinerary\"\n\n";
$prompt .= "For Day 1 (Arrival Day):\n";
$prompt .= "   Write heading: \"### Day 1: Arrival at [Destination]\"\n";
$prompt .= "   - Morning/Afternoon: Arrival details\n";
$prompt .= "   - Transfer to hotel\n";
$prompt .= "   - Check-in and rest\n";
$prompt .= "   - Evening: Light activity or rest\n";
$prompt .= "   - Day 1 Cost: ₹XXX\n\n";

$prompt .= "For Each Sightseeing Day:\n";
$prompt .= "   Write heading: \"### Day X: [Main Activity/Place]\"\n";
$prompt .= "   - Morning (7 AM - 12 PM): Place + Time + Cost\n";
$prompt .= "   - Afternoon (12 PM - 4 PM): Place + Time + Cost\n";
$prompt .= "   - Evening (4 PM - 7 PM): Activity or free time\n";
$prompt .= "   - Meals: Breakfast ₹XXX | Lunch ₹XXX | Dinner ₹XXX\n";
$prompt .= "   - Local Transport: ₹XXX\n";
$prompt .= "   - Day X Total Cost: ₹XXX - ₹XXX\n\n";

$prompt .= "For Last Day (Departure):\n";
$prompt .= "   Write heading: \"### Day X: Departure\"\n";
$prompt .= "   - Check-out time\n";
$prompt .= "   - Transfer to station/airport\n";
$prompt .= "   - Journey back\n";
$prompt .= "   - Day X Cost: ₹XXX\n\n";

$prompt .= "4. PLACE-BY-PLACE COST SUMMARY:\n";
$prompt .= "   Write in simple bullet format (NOT table):\n";
$prompt .= "   \n";
$prompt .= "   Place 1: [Name]\n";
$prompt .= "   - Visit Time: X hours\n";
$prompt .= "   - Entry Fee: ₹XXX (or FREE)\n";
$prompt .= "   - Activity 1: ₹XXX\n";
$prompt .= "   - Activity 2: ₹XXX\n";
$prompt .= "   - Total for this place: ₹XXX\n";
$prompt .= "   \n";
$prompt .= "   [Repeat for each place]\n\n";

$prompt .= "5. COMPLETE PRICE BREAKDOWN:\n";
$prompt .= "   Write in simple format (NO pipes, NO tables, NO markdown):\n";
$prompt .= "   \n";
$prompt .= "   A. JOURNEY COSTS:\n";
$prompt .= "      1. Main Transport (Your location to destination):\n";
if ($transport_info) {
    $transport_mode = strtolower($transport_info['transport_name']);
    if (stripos($transport_mode, 'train') !== false) {
        $prompt .= "         - Train tickets: ₹XXX - ₹XXX\n";
        $prompt .= "         - Station to hotel (arrival): ₹80 - ₹150\n";
        $prompt .= "         - Hotel to station (departure): ₹80 - ₹150\n";
    } elseif (stripos($transport_mode, 'flight') !== false || stripos($transport_mode, 'air') !== false) {
        $prompt .= "         - Flight tickets: ₹XXX - ₹XXX\n";
        $prompt .= "         - Airport to hotel (arrival): ₹400 - ₹800\n";
        $prompt .= "         - Hotel to airport (departure): ₹400 - ₹800\n";
    } else {
        $prompt .= "         - " . $transport_info['transport_name'] . " cost: ₹XXX - ₹XXX\n";
    }
} else {
    $prompt .= "         - Local travel only\n";
}
$prompt .= "      2. Local Transport (during sightseeing):\n";
$prompt .= "         - Day 1: ₹200 - ₹400\n";
$prompt .= "         - Day 2: ₹200 - ₹400\n";
$prompt .= "         [continue for all days]\n";
$prompt .= "         - Total Local Transport: ₹XXX - ₹XXX\n";
$prompt .= "   \n";
$prompt .= "   B. ACCOMMODATION:\n";
$prompt .= "      - Night 1: ₹XXX - ₹XXX\n";
$prompt .= "      - Night 2: ₹XXX - ₹XXX\n";
$prompt .= "      [continue for all nights]\n";
$prompt .= "      - Total Accommodation: ₹XXX - ₹XXX\n";
$prompt .= "   \n";
$prompt .= "   C. ENTRY TICKETS:\n";
$prompt .= "      - [Place 1 name]: ₹XXX\n";
$prompt .= "      - [Place 2 name]: ₹XXX or FREE\n";
$prompt .= "      - Total Entry Tickets: ₹XXX - ₹XXX\n";
$prompt .= "   \n";
$prompt .= "   D. ACTIVITIES:\n";
$prompt .= "      - [Activity 1 at Place 1]: ₹XXX - ₹XXX\n";
$prompt .= "      - [Activity 2 at Place 2]: ₹XXX - ₹XXX\n";
$prompt .= "      - Total Activities: ₹XXX - ₹XXX\n";
$prompt .= "   \n";
$prompt .= "   E. FOOD EXPENSES:\n";
$prompt .= "      - Day 1 (Breakfast + Lunch + Dinner): ₹500 - ₹900\n";
$prompt .= "      - Day 2 (Breakfast + Lunch + Dinner): ₹500 - ₹900\n";
$prompt .= "      [continue for all days]\n";
$prompt .= "      - Total Food: ₹XXX - ₹XXX\n";
$prompt .= "   \n";
$prompt .= "   F. GRAND TOTAL:\n";
$prompt .= "      Minimum: ₹XX,XXX\n";
$prompt .= "      Maximum: ₹XX,XXX\n";
$prompt .= "   \n";
$prompt .= "   (Show amount with thousands separator for clarity)\n\n";

$prompt .= "6. IMPORTANT TRAVEL TIPS:\n";
$prompt .= "   A. ARRIVAL & DEPARTURE:\n";
if ($transport_info) {
    $transport_mode = strtolower($transport_info['transport_name']);
    if (stripos($transport_mode, 'train') !== false) {
        $prompt .= "      - Book train tickets in advance\n";
        $prompt .= "      - Keep station name and train number handy\n";
        $prompt .= "      - Prebook station pickup for convenience\n";
    } elseif (stripos($transport_mode, 'flight') !== false || stripos($transport_mode, 'air') !== false) {
        $prompt .= "      - Reach airport 2 hours before departure\n";
        $prompt .= "      - Prebook airport taxi for hassle-free transfer\n";
        $prompt .= "      - Keep airport location and terminal info\n";
    } elseif (stripos($transport_mode, 'car') !== false || stripos($transport_mode, 'bike') !== false) {
        $prompt .= "      - Plan overnight stops if journey > 8 hours\n";
        $prompt .= "      - Take breaks every 2-3 hours\n";
        $prompt .= "      - No night driving for safety\n";
    }
}
$prompt .= "   B. GENERAL TIPS:\n";
$prompt .= "      - Best time to visit (season/months)\n";
$prompt .= "      - What to pack (based on activities & weather)\n";
$prompt .= "      - Safety reminders\n";
$prompt .= "      - Emergency contacts\n\n";

$prompt .= "CRITICAL FORMATTING INSTRUCTIONS:\n";
$prompt .= "- Use PROPER HEADINGS with markdown format:\n";
$prompt .= "  * ## for main sections (Journey, Overview, Itinerary, Cost Summary)\n";
$prompt .= "  * ### for days (Day 1: Arrival, Day 2: Jagannath Temple Tour, etc.)\n";
$prompt .= "  * Make day names descriptive (Day 1: Arrival, Day 2: Temple Tour, Day 3: Beach & Lake)\n";
$prompt .= "- Do NOT use markdown tables (no pipes |, no dashes ---)\n";
$prompt .= "- Use simple bullet points with dashes (-)\n";
$prompt .= "- Format prices clearly: ₹1,500 or ₹1,500 - ₹2,000\n";
$prompt .= "- Add proper line breaks between sections\n";
$prompt .= "- Keep it clean and readable\n\n";

$prompt .= "CRITICAL CONTENT INSTRUCTIONS:\n";
$prompt .= "- Use ONLY database values (visit times, fees, safety info)\n";
$prompt .= "- Do NOT invent prices or timings\n";
$prompt .= "- Show ACTUAL costs wherever money will be spent\n";
$prompt .= "- Follow time management strictly (6 hrs max per day)\n";
$prompt .= "- NO night travel - stay overnight if needed\n";
$prompt .= "- Include SPECIFIC details:\n";
$prompt .= "  * For Train: Exact station name, major trains on route, station-hotel distance\n";
$prompt .= "  * For Flight: Airport name with code, distance to city, transfer options\n";
$prompt .= "  * For Car/Bike: Number of travel days, overnight stop cities, refueling points\n";
$prompt .= "  * For Bus: Bus terminal name, terminal-hotel distance\n";
$prompt .= "- Count total days = Travel days + Sightseeing days\n";
$prompt .= "- Be realistic and practical\n";
$prompt .= "- Use simple, clear language\n";
$prompt .= "- Show prices in simple format: Place name: ₹500 or ₹500-₹800";

// Initialize generated_plan variable
$generated_plan = '';
$error_message = '';
$is_loading = true;

// If this is an AJAX request to generate the plan
if (isset($_POST['generate_plan'])) {
    header('Content-Type: application/json');
    
    // Use API key from config (secure)
    $api_key = defined('GEMINI_API_KEY') ? GEMINI_API_KEY : getenv('GEMINI_API_KEY');
    
    if (empty($api_key)) {
        echo json_encode(['success' => false, 'error' => 'Gemini API key not configured']);
        exit;
    }
    
    // Use Gemini 3 Flash Preview (2026 version - working endpoint)
    $api_url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3-flash-preview:generateContent?key=' . $api_key;
    
    $request_data = [
        'contents' => [
            [
                'parts' => [
                    ['text' => $prompt]
                ]
            ]
        ],
        'generationConfig' => [
            'temperature' => 0.7,
            'topK' => 40,
            'topP' => 0.95,
            'maxOutputTokens' => 4096, // Increased for comprehensive plan with detailed info
        ]
    ];
    
    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request_data));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30); // 30 second timeout protection
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); // 10 second connection timeout
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    $curl_errno = curl_errno($ch);
    curl_close($ch);
    
    // Handle connection errors
    if ($curl_error) {
        $error_msg = 'Connection error: ' . $curl_error;
        if ($curl_errno == CURLE_OPERATION_TIMEOUTED) {
            $error_msg = 'Request timed out. The AI is taking longer than expected. Please try again.';
        }
        echo json_encode(['success' => false, 'error' => $error_msg]);
        exit;
    }
    
    // Handle non-200 responses
    if ($http_code !== 200) {
        $error_detail = '';
        if ($http_code == 404) {
            $error_detail = 'API endpoint not found. Please verify your API key is valid and enabled for Gemini API.';
        } elseif ($http_code == 403 || $http_code == 401) {
            $error_detail = 'Invalid API key. Please check your Gemini API key in Google AI Studio: https://aistudio.google.com/app/apikey';
        } elseif ($http_code == 429) {
            $error_detail = 'API quota exceeded. Please wait a few minutes and try again, or check your quota at Google AI Studio.';
        } else {
            $error_detail = 'API returned status code: ' . $http_code;
        }
        echo json_encode(['success' => false, 'error' => $error_detail, 'response' => $response]);
        exit;
    }
    
    $result = json_decode($response, true);
    
    if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
        $plan_text = $result['candidates'][0]['content']['parts'][0]['text'];
        echo json_encode(['success' => true, 'plan' => $plan_text]);
    } else {
        echo json_encode(['success' => false, 'error' => 'No content in API response', 'response' => $result]);
    }
    exit;
}

$pageTitle = 'Your Travel Plan | TravelApp';
$pageCSS = 'travel-plan.css';
include 'includes/head.php';
include 'includes/navbar.php';
include 'includes/overlay.php';
include 'includes/sidebar.php';
?>

<!-- Page Header -->
<div class="pt-14 sm:pt-16 pb-4 bg-gradient-to-r from-[#009688] to-[#00796b] border-b border-gray-200 no-print">
    <div class="max-w-6xl mx-auto px-4 sm:px-6">
        <div class="flex items-center justify-between mb-2">
            <div>
                <h1 class="text-lg sm:text-xl font-bold text-white">Your Personalized Travel Plan</h1>
                <p class="text-xs sm:text-sm text-white/90 mt-1">AI-generated day-by-day itinerary</p>
            </div>
            <a href="booking-summary.php?<?php echo http_build_query($_GET); ?>" class="text-sm text-white hover:underline flex items-center gap-1">
                <i class="bi bi-arrow-left"></i> Back to Summary
            </a>
        </div>
    </div>
</div>

<!-- Loading State -->
<div id="loading-state" class="max-w-5xl mx-auto px-4 py-12 no-print">
    <div class="bg-white rounded-2xl shadow-lg p-8 text-center">
        <div class="animate-spin inline-block w-16 h-16 border-4 border-[#009688] border-t-transparent rounded-full mb-4"></div>
        <h2 class="text-2xl font-bold text-gray-900 mb-2">Creating Your Perfect Travel Plan...</h2>
        <p class="text-gray-600">Our AI is analyzing visit times, safety info, best timings, and travel distances to create the most convenient and safe itinerary for you.</p>
        <div class="mt-6 flex flex-wrap items-center justify-center gap-2 text-sm text-gray-500">
            <i class="bi bi-check-circle text-[#009688]"></i>
            <span>Analyzing places & visit times</span>
            <span class="mx-2">•</span>
            <i class="bi bi-check-circle text-[#009688]"></i>
            <span>Planning safe travel routes</span>
            <span class="mx-2">•</span>
            <i class="bi bi-check-circle text-[#009688]"></i>
            <span>Calculating all costs</span>
            <span class="mx-2">•</span>
            <i class="bi bi-check-circle text-[#009688]"></i>
            <span>Optimizing daily schedule</span>
        </div>
    </div>
</div>

<!-- Main Content -->
<section id="plan-content" class="max-w-5xl mx-auto px-4 py-6 pb-24" style="display: none;">
    <div class="bg-white rounded-2xl shadow-lg p-6 sm:p-8">
        
        <!-- Trip Overview -->
        <div class="mb-8 pb-6 border-b-2 border-[#009688]/20">
            <div class="flex items-start justify-between mb-4 no-print">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-2 flex items-center gap-2">
                        <i class="bi bi-calendar-check text-[#009688]"></i> Trip Overview
                    </h2>
                    <p class="text-gray-600">Visiting <?php echo count($places_info); ?> destination<?php echo count($places_info) > 1 ? 's' : ''; ?></p>
                </div>
                <button onclick="window.print()" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors flex items-center gap-2">
                    <i class="bi bi-printer"></i> Print Plan
                </button>
            </div>
            
            <!-- Quick Price Summary -->
            <?php
            // Calculate totals
            $total_min = $places_price_min + $activities_price_min + ($transport_price_min > 0 ? $transport_price_min : $transport_price) + ($hotel_price_min > 0 ? $hotel_price_min : 0);
            $total_max = $places_price_max + $activities_price_max + ($transport_price_max > 0 ? $transport_price_max : $transport_price) + ($hotel_price_max > 0 ? $hotel_price_max : 0);
            ?>
            <?php if ($total_min > 0 || $total_max > 0): ?>
            <div class="bg-gradient-to-r from-[#009688]/10 to-[#00796b]/10 rounded-lg p-4 border-l-4 border-[#009688]">
                <h3 class="font-bold text-gray-900 mb-2 flex items-center gap-2">
                    <i class="bi bi-cash-stack text-[#009688]"></i> Estimated Total Cost
                </h3>
                <div class="flex items-baseline gap-2">
                    <?php if ($total_min != $total_max): ?>
                        <span class="text-2xl font-bold text-[#009688]">₹<?php echo number_format($total_min, 2); ?> - ₹<?php echo number_format($total_max, 2); ?></span>
                    <?php else: ?>
                        <span class="text-2xl font-bold text-[#009688]">₹<?php echo number_format($total_max, 2); ?></span>
                    <?php endif; ?>
                    <span class="text-sm text-gray-600">(excluding meals)</span>
                </div>
                <p class="text-xs text-gray-500 mt-2">
                    <i class="bi bi-info-circle"></i> Detailed day-by-day and place-by-place breakdown included in the itinerary below
                </p>
            </div>
            <?php endif; ?>
        </div>

        <!-- AI Generated Plan -->
        <div id="ai-plan" class="prose max-w-none">
            <!-- Content will be loaded here -->
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-col sm:flex-row gap-4 mt-8 pt-6 border-t-2 border-gray-200 no-print">
            <button onclick="regeneratePlan()" class="flex-1 px-6 py-3 bg-gray-100 text-gray-700 rounded-lg font-semibold hover:bg-gray-200 transition-colors flex items-center justify-center gap-2">
                <i class="bi bi-arrow-clockwise"></i> Regenerate Plan
            </button>
            <button onclick="downloadPlan()" class="flex-1 px-6 py-3 bg-[#009688] text-white rounded-lg font-semibold hover:bg-[#00796b] transition-colors flex items-center justify-center gap-2">
                <i class="bi bi-download"></i> Download as PDF
            </button>
        </div>
        
    </div>
</section>

<!-- Error State -->
<div id="error-state" class="max-w-5xl mx-auto px-4 py-12 no-print" style="display: none;">
    <div class="bg-white rounded-2xl shadow-lg p-8 text-center">
        <i class="bi bi-exclamation-triangle text-red-500 text-5xl mb-4"></i>
        <h2 class="text-2xl font-bold text-gray-900 mb-2">Oops! Something went wrong</h2>
        <p class="text-gray-600 mb-6" id="error-message">We couldn't generate your travel plan. Please try again.</p>
        <button onclick="generatePlan()" class="px-6 py-3 bg-[#009688] text-white rounded-lg font-semibold hover:bg-[#00796b] transition-colors">
            <i class="bi bi-arrow-clockwise"></i> Try Again
        </button>
    </div>
</div>

<style>
/* Main heading styles */
#ai-plan h1, #ai-plan h2, #ai-plan h3, #ai-plan h4 {
    color: #009688;
    font-weight: bold;
    margin-top: 2rem;
    margin-bottom: 1rem;
    line-height: 1.3;
}

#ai-plan h1 { 
    font-size: 2rem; 
    border-bottom: 3px solid #009688;
    padding-bottom: 0.5rem;
}

#ai-plan h2 { 
    font-size: 1.75rem;
    border-bottom: 2px solid #00968820;
    padding-bottom: 0.5rem;
    background: linear-gradient(90deg, #00968810 0%, transparent 100%);
    padding: 0.75rem 1rem;
    border-radius: 0.5rem;
    margin-top: 2.5rem;
}

#ai-plan h3 { 
    font-size: 1.5rem;
    background: linear-gradient(135deg, #e0f2f1 0%, #b2dfdb 50%, transparent 100%);
    padding: 0.75rem 1rem;
    border-left: 4px solid #009688;
    border-radius: 0.5rem;
    margin-top: 2rem;
}

#ai-plan h4 { 
    font-size: 1.25rem;
    color: #00796b;
}

/* Special heading classes */
.main-heading {
    background: linear-gradient(135deg, #009688 0%, #00796b 100%);
    color: white !important;
    padding: 1rem 1.5rem;
    border-radius: 0.75rem;
    box-shadow: 0 4px 6px rgba(0, 150, 136, 0.3);
}

.section-heading {
    background: linear-gradient(90deg, #00968815 0%, transparent 100%);
    border-left: 5px solid #009688;
    padding: 0.875rem 1.25rem;
}

.day-heading {
    background: linear-gradient(135deg, #e0f2f1 0%, #b2dfdb 50%, #ffffff 100%);
    border-left: 5px solid #009688;
    padding: 0.875rem 1.25rem;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

#ai-plan p {
    margin-bottom: 1rem;
    line-height: 1.75;
    color: #374151;
}

#ai-plan ul, #ai-plan ol {
    margin-left: 1.5rem;
    margin-bottom: 1rem;
}

#ai-plan li {
    margin-bottom: 0.5rem;
    line-height: 1.75;
}

#ai-plan strong {
    color: #009688;
    font-weight: 600;
}

#ai-plan code {
    background: #f3f4f6;
    padding: 0.125rem 0.375rem;
    border-radius: 0.25rem;
    font-size: 0.875rem;
}

/* Price highlighting */
#ai-plan :contains("₹"), #ai-plan :contains("Rs"), #ai-plan :contains("INR") {
    font-weight: 600;
}

/* Style for price information */
#ai-plan p:has-text("₹"), #ai-plan li:has-text("₹") {
    position: relative;
}

/* Highlight prices in text */
#ai-plan {
    font-size: 1rem;
    line-height: 1.8;
}

/* Price badges and tables */
#ai-plan table {
    width: 100%;
    border-collapse: collapse;
    margin: 1rem 0;
    background: white;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

#ai-plan table th {
    background: #009688;
    color: white;
    padding: 0.75rem;
    text-align: left;
    font-weight: 600;
}

#ai-plan table td {
    padding: 0.75rem;
    border-bottom: 1px solid #e5e7eb;
}

#ai-plan table tr:hover {
    background: #f9fafb;
}

/* Day sections with pricing */
.day-section {
    background: linear-gradient(to right, #f0fdfa, #ccfbf1);
    border-left: 4px solid #009688;
    padding: 1.5rem;
    margin: 1.5rem 0;
    border-radius: 0.5rem;
}

/* Price summary boxes */
.price-box {
    background: linear-gradient(135deg, #e0f2f1 0%, #b2dfdb 100%);
    border: 2px solid #009688;
    border-radius: 0.5rem;
    padding: 1.25rem;
    margin: 1rem 0;
}

.price-highlight {
    color: #00796b;
    font-weight: 700;
    font-size: 1.1em;
}

/* Cost breakdown styling */
.cost-breakdown {
    background: #fff9e6;
    border-left: 4px solid #ffc107;
    padding: 1rem;
    margin: 1rem 0;
    border-radius: 0.25rem;
}

/* Activity pricing */
.activity-price {
    color: #00796b;
    font-weight: 600;
    background: #e0f2f1;
    padding: 0.125rem 0.5rem;
    border-radius: 0.25rem;
    display: inline-block;
    margin-left: 0.5rem;
}

/* Print Styles - Only show travel plan content */
@media print {
    /* Hide everything by default */
    body * {
        visibility: hidden;
    }
    
    /* Only show the travel plan content */
    #ai-plan,
    #ai-plan * {
        visibility: visible;
    }
    
    /* Hide specific elements */
    .no-print,
    nav,
    header,
    footer,
    button,
    .btn,
    .fixed,
    .sticky,
    #sidebar,
    #navbar,
    #page-header,
    .border-t-2.border-gray-200,
    script,
    noscript {
        display: none !important;
        visibility: hidden !important;
    }
    
    /* Position the content for printing */
    #ai-plan {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        visibility: visible;
    }
    
    /* Clean background */
    body {
        background: white !important;
        margin: 0;
        padding: 20px;
    }
    
    /* Better heading styling for print */
    #ai-plan h1, #ai-plan .main-heading {
        background: none !important;
        color: #009688 !important;
        box-shadow: none !important;
        page-break-after: avoid;
    }
    
    #ai-plan h2, #ai-plan .section-heading {
        page-break-after: avoid;
        background: none !important;
        border-left: 4px solid #009688 !important;
    }
    
    #ai-plan h3, #ai-plan .day-heading {
        page-break-after: avoid;
        background: none !important;
        border-left: 3px solid #009688 !important;
    }
    
    /* Ensure lists don't break awkwardly */
    #ai-plan ul, #ai-plan ol {
        page-break-inside: avoid;
    }
    
    /* Price highlights remain visible */
    .price-highlight {
        color: #00796b !important;
        font-weight: 700 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    
    /* Remove shadows and gradients */
    #ai-plan * {
        box-shadow: none !important;
        text-shadow: none !important;
    }
    
    /* Page break control */
    .day-section {
        page-break-inside: avoid;
        background: none !important;
        border-left: 3px solid #009688 !important;
    }
}
</style>

<script>
// Auto-generate plan on page load
document.addEventListener('DOMContentLoaded', function() {
    generatePlan();
});

function generatePlan() {
    document.getElementById('loading-state').style.display = 'block';
    document.getElementById('plan-content').style.display = 'none';
    document.getElementById('error-state').style.display = 'none';
    
    // Make AJAX request to generate plan
    fetch(window.location.href, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'generate_plan=1'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayPlan(data.plan);
        } else {
            showError(data.error || 'Failed to generate travel plan');
        }
    })
    .catch(error => {
        showError('Network error: ' + error.message);
    });
}

function displayPlan(planText) {
    // Convert markdown-like formatting to HTML with better heading support
    let htmlPlan = planText;
    
    // Convert headings first (order matters: h3 before h2 before h1)
    htmlPlan = htmlPlan.replace(/^### (.+)$/gm, '<h3 class="day-heading">$1</h3>');
    htmlPlan = htmlPlan.replace(/^## (.+)$/gm, '<h2 class="section-heading">$1</h2>');
    htmlPlan = htmlPlan.replace(/^# (.+)$/gm, '<h1 class="main-heading">$1</h1>');
    
    // Convert bold and italic
    htmlPlan = htmlPlan
        .replace(/\*\*\*(.+?)\*\*\*/g, '<strong><em>$1</em></strong>')
        .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
        .replace(/\*(.+?)\*/g, '<em>$1</em>')
        .replace(/\n\n/g, '</p><p>');
    
    // Convert bullet points to lists (lines starting with - or number.)
    htmlPlan = htmlPlan.replace(/^[\s]*-\s+(.+)$/gm, '<li>$1</li>');
    htmlPlan = htmlPlan.replace(/^[\s]*\d+\.\s+(.+)$/gm, '<li>$1</li>');
    
    // Wrap consecutive list items in ul tags
    htmlPlan = htmlPlan.replace(/(<li>.*?<\/li>\n?)+/g, function(match) {
        return '<ul>' + match + '</ul>';
    });
    
    // Highlight prices - find all price patterns (₹, Rs, INR, etc.)
    htmlPlan = htmlPlan.replace(/(₹|Rs\.?|INR)\s*([\d,]+(?:\.[\d]{2})?)/gi, function(match, symbol, amount) {
        return '<span class="price-highlight">' + symbol + amount + '</span>';
    });
    
    // Highlight price ranges (₹500 - ₹800)
    htmlPlan = htmlPlan.replace(/(₹|Rs\.?|INR)\s*([\d,]+(?:\.[\d]{2})?)\s*-\s*(₹|Rs\.?|INR)\s*([\d,]+(?:\.[\d]{2})?)/gi, function(match, sym1, amt1, sym2, amt2) {
        return '<span class="price-highlight">' + sym1 + amt1 + ' - ' + sym2 + amt2 + '</span>';
    });
    
    // Wrap day sections
    htmlPlan = htmlPlan.replace(/<h2>(Day \d+[^<]*)<\/h2>/gi, function(match, dayText) {
        return '<div class="day-section"><h2>' + dayText + '</h2>';
    });
    htmlPlan = htmlPlan.replace(/<h2>Day \d+[^<]*<\/h2>[\s\S]*?(?=<h2>Day \d+|<h2>COMPLETE|<h2>FINAL|<h2>IMPORTANT|$)/gi, function(match) {
        if (!match.includes('</div>')) {
            return match + '</div>';
        }
        return match;
    });
    
    // Add paragraph tags
    htmlPlan = '<p>' + htmlPlan + '</p>';
    
    // Clean up extra tags
    htmlPlan = htmlPlan
        .replace(/<p><\/p>/g, '')
        .replace(/<p>\s*<\/p>/g, '')
        .replace(/<p>(<h\d>)/g, '$1')
        .replace(/(<\/h\d>)<\/p>/g, '$1')
        .replace(/<p>(<ul>)/g, '$1')
        .replace(/(<\/ul>)<\/p>/g, '$1')
        .replace(/<p>(<div)/g, '$1')
        .replace(/(<\/div>)<\/p>/g, '$1')
        .replace(/(<ul>\s*)+/g, '<ul>')
        .replace(/(\s*<\/ul>)+/g, '</ul>');
    
    document.getElementById('ai-plan').innerHTML = htmlPlan;
    document.getElementById('loading-state').style.display = 'none';
    document.getElementById('plan-content').style.display = 'block';
    
    // Additional styling for price elements after DOM update
    setTimeout(function() {
        const planElement = document.getElementById('ai-plan');
        const priceElements = planElement.querySelectorAll('.price-highlight');
        priceElements.forEach(function(el) {
            el.style.color = '#00796b';
            el.style.fontWeight = '700';
            el.style.fontSize = '1.05em';
        });
    }, 100);
}

function showError(message) {
    document.getElementById('error-message').textContent = message;
    document.getElementById('loading-state').style.display = 'none';
    document.getElementById('error-state').style.display = 'block';
}

function regeneratePlan() {
    if (confirm('Are you sure you want to generate a new travel plan? This will replace the current one.')) {
        generatePlan();
    }
}

function downloadPlan() {
    // Add a print title at the top
    const aiPlan = document.getElementById('ai-plan');
    const originalContent = aiPlan.innerHTML;
    
    // Add title and date for print
    const currentDate = new Date().toLocaleDateString('en-IN', { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    });
    
    const printTitle = `
        <div class="print-header" style="margin-bottom: 2rem; padding-bottom: 1rem; border-bottom: 3px solid #009688;">
            <h1 style="color: #009688; font-size: 2rem; margin-bottom: 0.5rem;">Your Travel Itinerary</h1>
            <p style="color: #666; font-size: 0.9rem;">Generated on ${currentDate}</p>
        </div>
    `;
    
    // Temporarily add print header
    aiPlan.innerHTML = printTitle + originalContent;
    
    // Trigger print
    window.print();
    
    // Restore original content after a short delay
    setTimeout(function() {
        aiPlan.innerHTML = originalContent;
    }, 100);
}
</script>

<?php include 'includes/footer.php'; ?>

