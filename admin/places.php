<?php
require_once 'config.php';
requireLogin();

$conn = getDBConnection();
$message = '';
$message_type = '';

// Handle AJAX requests
if (isset($_GET['get_place_data'])) {
    $place_id = intval($_GET['get_place_data']);
    $hotel_types = [];
    $transport_types = [];
    
    $result = $conn->query("SELECT hotel_type_id FROM place_hotel_types WHERE place_id = $place_id");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $hotel_types[] = $row['hotel_type_id'];
        }
    }
    
    $result = $conn->query("SELECT transport_type_id FROM place_transport_types WHERE place_id = $place_id");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $transport_types[] = $row['transport_type_id'];
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode(['hotel_types' => $hotel_types, 'transport_types' => $transport_types]);
    exit;
}

if (isset($_GET['get_activities'])) {
    $place_id = intval($_GET['get_activities']);
    $activities = [];
    
    $result = $conn->query("SELECT pa.*, at.name as activity_name FROM place_activities pa JOIN activity_types at ON pa.activity_type_id = at.id WHERE pa.place_id = $place_id ORDER BY at.name");
    if ($result) {
        $activities = $result->fetch_all(MYSQLI_ASSOC);
    }
    
    ob_start();
    if (count($activities) > 0): ?>
        <div class="table-responsive">
            <table class="table table-sm table-bordered">
                <thead class="table-light">
                    <tr>
                        <th>Activity</th>
                        <th>Safety Level</th>
                        <th>Safety Info</th>
                        <th>Age</th>
                        <th>Gender</th>
                        <th>Medical Restrictions</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($activities as $activity): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($activity['activity_name']); ?></strong></td>
                        <td>
                            <?php 
                            $safety_level = $activity['safety_level'] ?? 'Medium';
                            $badge_class = '';
                            if ($safety_level === 'High') $badge_class = 'bg-success';
                            elseif ($safety_level === 'Medium') $badge_class = 'bg-info';
                            elseif ($safety_level === 'Low') $badge_class = 'bg-warning';
                            elseif ($safety_level === 'Worst') $badge_class = 'bg-danger';
                            ?>
                            <span class="badge <?php echo $badge_class; ?>"><?php echo htmlspecialchars($safety_level); ?></span>
                        </td>
                        <td><?php echo htmlspecialchars(substr($activity['safety_info'] ?? '', 0, 50)); ?><?php echo strlen($activity['safety_info'] ?? '') > 50 ? '...' : ''; ?></td>
                        <td><?php echo htmlspecialchars($activity['age_restriction'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($activity['gender_restriction'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars(substr($activity['medical_restrictions'] ?? '', 0, 30)); ?><?php echo strlen($activity['medical_restrictions'] ?? '') > 30 ? '...' : ''; ?></td>
                        <td>
                            <button type="button" class="btn btn-sm btn-primary" onclick="openEditActivityModal(<?php echo $activity['id']; ?>, '<?php echo htmlspecialchars(addslashes($activity['activity_name'])); ?>', '<?php echo htmlspecialchars(addslashes($activity['safety_info'] ?? '')); ?>', '<?php echo htmlspecialchars(addslashes($activity['safety_level'] ?? 'Medium')); ?>', '<?php echo htmlspecialchars(addslashes($activity['age_restriction'] ?? '')); ?>', '<?php echo htmlspecialchars(addslashes($activity['gender_restriction'] ?? '')); ?>', '<?php echo htmlspecialchars(addslashes($activity['medical_restrictions'] ?? '')); ?>')">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-danger" onclick="deleteActivity(<?php echo $activity['id']; ?>, <?php echo $place_id; ?>)">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> No activities added yet. Add activities using the form above.
        </div>
    <?php endif;
    echo ob_get_clean();
    exit;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $city_id = intval($_POST['city_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $image = trim($_POST['image'] ?? '');
            $security_info = trim($_POST['security_info'] ?? '');
            $night_travel_safety = trim($_POST['night_travel_safety'] ?? '');
            $safety_level = trim($_POST['safety_level'] ?? 'Medium');
            $religion_restrictions = isset($_POST['religion_restrictions']) && !empty($_POST['religion_restrictions']) ? implode(',', $_POST['religion_restrictions']) : 'No Restriction';
            // Visit Timing & Crowd Data
            $best_time_of_day = trim($_POST['best_time_of_day'] ?? '');
            $avg_visit_time_hours = !empty($_POST['avg_visit_time_hours']) ? floatval($_POST['avg_visit_time_hours']) : null;
            $crowd_level = trim($_POST['crowd_level'] ?? '');
            $avg_waiting_time_minutes = !empty($_POST['avg_waiting_time_minutes']) ? intval($_POST['avg_waiting_time_minutes']) : null;
            // Seasonal / Weather Risk
            $weather_dependency = trim($_POST['weather_dependency'] ?? '');
            $monsoon_risk = trim($_POST['monsoon_risk'] ?? '');
            $sudden_closure_risk = trim($_POST['sudden_closure_risk'] ?? '');
            // Family / Health Usability
            $child_friendly = trim($_POST['child_friendly'] ?? '');
            $senior_friendly = trim($_POST['senior_friendly'] ?? '');
            $physical_effort = trim($_POST['physical_effort'] ?? '');
            // Emergency Accessibility
            $nearest_hospital_distance_km = !empty($_POST['nearest_hospital_distance_km']) ? floatval($_POST['nearest_hospital_distance_km']) : null;
            $police_response_level = trim($_POST['police_response_level'] ?? '');
            // Network & Power Availability
            $digital_amenities = trim($_POST['digital_amenities'] ?? '');
            $mobile_network = trim($_POST['mobile_network'] ?? '');
            $internet_availability = trim($_POST['internet_availability'] ?? '');
            // Place Priority & Experience Quality
            $experience_score = !empty($_POST['experience_score']) ? intval($_POST['experience_score']) : null;
            $priority_level = !empty($_POST['priority_level']) ? intval($_POST['priority_level']) : 3;
            $status = trim($_POST['status'] ?? 'active');
            $is_verified = trim($_POST['is_verified'] ?? 'no');
            
            if (!empty($name) && $city_id > 0) {
                $stmt = $conn->prepare("INSERT INTO places (city_id, name, description, image, security_info, night_travel_safety, safety_level, religion_restrictions, best_time_of_day, avg_visit_time_hours, crowd_level, avg_waiting_time_minutes, weather_dependency, monsoon_risk, sudden_closure_risk, child_friendly, senior_friendly, physical_effort, nearest_hospital_distance_km, police_response_level, digital_amenities, mobile_network, internet_availability, experience_score, priority_level, status, is_verified) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("issssssssdissdsssssssssssiiss", $city_id, $name, $description, $image, $security_info, $night_travel_safety, $safety_level, $religion_restrictions, $best_time_of_day, $avg_visit_time_hours, $crowd_level, $avg_waiting_time_minutes, $weather_dependency, $monsoon_risk, $sudden_closure_risk, $child_friendly, $senior_friendly, $physical_effort, $nearest_hospital_distance_km, $police_response_level, $digital_amenities, $mobile_network, $internet_availability, $experience_score, $priority_level, $status, $is_verified);
                if ($stmt->execute()) {
                    $place_id = $stmt->insert_id;
                    
                    // Handle hotel types
                    if (isset($_POST['hotel_types']) && is_array($_POST['hotel_types'])) {
                        foreach ($_POST['hotel_types'] as $hotel_type_id) {
                            $hotel_type_id = intval($hotel_type_id);
                            if ($hotel_type_id > 0) {
                                $stmt2 = $conn->prepare("INSERT INTO place_hotel_types (place_id, hotel_type_id) VALUES (?, ?)");
                                $stmt2->bind_param("ii", $place_id, $hotel_type_id);
                                $stmt2->execute();
                                $stmt2->close();
                            }
                        }
                    }
                    
                    // Handle transport types
                    if (isset($_POST['transport_types']) && is_array($_POST['transport_types'])) {
                        foreach ($_POST['transport_types'] as $transport_type_id) {
                            $transport_type_id = intval($transport_type_id);
                            if ($transport_type_id > 0) {
                                $stmt2 = $conn->prepare("INSERT INTO place_transport_types (place_id, transport_type_id) VALUES (?, ?)");
                                $stmt2->bind_param("ii", $place_id, $transport_type_id);
                                $stmt2->execute();
                                $stmt2->close();
                            }
                        }
                    }
                    
                    $message = "Place added successfully!";
                    $message_type = "success";
                    logActivity("Add Place", "Added place: $name");
                } else {
                    $message = "Error adding place: " . $conn->error;
                    $message_type = "error";
                }
                $stmt->close();
            } else {
                $message = "Place name and city are required!";
                $message_type = "error";
            }
        } elseif ($_POST['action'] === 'edit') {
            $id = intval($_POST['id'] ?? 0);
            $city_id = intval($_POST['city_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $image = trim($_POST['image'] ?? '');
            $security_info = trim($_POST['security_info'] ?? '');
            $night_travel_safety = trim($_POST['night_travel_safety'] ?? '');
            $safety_level = trim($_POST['safety_level'] ?? 'Medium');
            $religion_restrictions = isset($_POST['religion_restrictions']) && !empty($_POST['religion_restrictions']) ? implode(',', $_POST['religion_restrictions']) : 'No Restriction';
            // Visit Timing & Crowd Data
            $best_time_of_day = trim($_POST['best_time_of_day'] ?? '');
            $avg_visit_time_hours = !empty($_POST['avg_visit_time_hours']) ? floatval($_POST['avg_visit_time_hours']) : null;
            $crowd_level = trim($_POST['crowd_level'] ?? '');
            $avg_waiting_time_minutes = !empty($_POST['avg_waiting_time_minutes']) ? intval($_POST['avg_waiting_time_minutes']) : null;
            // Seasonal / Weather Risk
            $weather_dependency = trim($_POST['weather_dependency'] ?? '');
            $monsoon_risk = trim($_POST['monsoon_risk'] ?? '');
            $sudden_closure_risk = trim($_POST['sudden_closure_risk'] ?? '');
            // Family / Health Usability
            $child_friendly = trim($_POST['child_friendly'] ?? '');
            $senior_friendly = trim($_POST['senior_friendly'] ?? '');
            $physical_effort = trim($_POST['physical_effort'] ?? '');
            // Emergency Accessibility
            $nearest_hospital_distance_km = !empty($_POST['nearest_hospital_distance_km']) ? floatval($_POST['nearest_hospital_distance_km']) : null;
            $police_response_level = trim($_POST['police_response_level'] ?? '');
            // Network & Power Availability
            $digital_amenities = trim($_POST['digital_amenities'] ?? '');
            $mobile_network = trim($_POST['mobile_network'] ?? '');
            $internet_availability = trim($_POST['internet_availability'] ?? '');
            // Place Priority & Experience Quality
            $experience_score = !empty($_POST['experience_score']) ? intval($_POST['experience_score']) : null;
            $priority_level = !empty($_POST['priority_level']) ? intval($_POST['priority_level']) : 3;
            $status = trim($_POST['status'] ?? 'active');
            $is_verified = trim($_POST['is_verified'] ?? 'no');
            
            if ($id > 0 && !empty($name) && $city_id > 0) {
                $stmt = $conn->prepare("UPDATE places SET city_id = ?, name = ?, description = ?, image = ?, security_info = ?, night_travel_safety = ?, safety_level = ?, religion_restrictions = ?, best_time_of_day = ?, avg_visit_time_hours = ?, crowd_level = ?, avg_waiting_time_minutes = ?, weather_dependency = ?, monsoon_risk = ?, sudden_closure_risk = ?, child_friendly = ?, senior_friendly = ?, physical_effort = ?, nearest_hospital_distance_km = ?, police_response_level = ?, digital_amenities = ?, mobile_network = ?, internet_availability = ?, experience_score = ?, priority_level = ?, status = ?, is_verified = ? WHERE id = ?");
                $stmt->bind_param("issssssssdissdsssssssssssiissi", $city_id, $name, $description, $image, $security_info, $night_travel_safety, $safety_level, $religion_restrictions, $best_time_of_day, $avg_visit_time_hours, $crowd_level, $avg_waiting_time_minutes, $weather_dependency, $monsoon_risk, $sudden_closure_risk, $child_friendly, $senior_friendly, $physical_effort, $nearest_hospital_distance_km, $police_response_level, $digital_amenities, $mobile_network, $internet_availability, $experience_score, $priority_level, $status, $is_verified, $id);
                if ($stmt->execute()) {
                    // Delete existing hotel types
                    $conn->query("DELETE FROM place_hotel_types WHERE place_id = $id");
                    // Add new hotel types
                    if (isset($_POST['hotel_types']) && is_array($_POST['hotel_types'])) {
                        foreach ($_POST['hotel_types'] as $hotel_type_id) {
                            $hotel_type_id = intval($hotel_type_id);
                            if ($hotel_type_id > 0) {
                                $stmt2 = $conn->prepare("INSERT INTO place_hotel_types (place_id, hotel_type_id) VALUES (?, ?)");
                                $stmt2->bind_param("ii", $id, $hotel_type_id);
                                $stmt2->execute();
                                $stmt2->close();
                            }
                        }
                    }
                    
                    // Delete existing transport types
                    $conn->query("DELETE FROM place_transport_types WHERE place_id = $id");
                    // Add new transport types
                    if (isset($_POST['transport_types']) && is_array($_POST['transport_types'])) {
                        foreach ($_POST['transport_types'] as $transport_type_id) {
                            $transport_type_id = intval($transport_type_id);
                            if ($transport_type_id > 0) {
                                $stmt2 = $conn->prepare("INSERT INTO place_transport_types (place_id, transport_type_id) VALUES (?, ?)");
                                $stmt2->bind_param("ii", $id, $transport_type_id);
                                $stmt2->execute();
                                $stmt2->close();
                            }
                        }
                    }
                    
                    $message = "Place updated successfully!";
                    $message_type = "success";
                    logActivity("Update Place", "Updated place: $name");
                } else {
                    $message = "Error updating place: " . $conn->error;
                    $message_type = "error";
                }
                $stmt->close();
            }
        } elseif ($_POST['action'] === 'delete') {
            $id = intval($_POST['id'] ?? 0);
            if ($id > 0) {
                $stmt = $conn->prepare("DELETE FROM places WHERE id = ?");
                $stmt->bind_param("i", $id);
                if ($stmt->execute()) {
                    $message = "Place deleted successfully!";
                    $message_type = "success";
                    logActivity("Delete Place", "Deleted place ID: $id");
                } else {
                    $message = "Error deleting place: " . $conn->error;
                    $message_type = "error";
                }
                $stmt->close();
            }
        } elseif ($_POST['action'] === 'add_activity') {
            $place_id = intval($_POST['place_id'] ?? 0);
            $activity_type_id = intval($_POST['activity_type_id'] ?? 0);
            $safety_info = trim($_POST['safety_info'] ?? '');
            $safety_level = trim($_POST['safety_level'] ?? 'Medium');
            $age_restriction = trim($_POST['age_restriction'] ?? '');
            $gender_restriction = trim($_POST['gender_restriction'] ?? '');
            $medical_restrictions = trim($_POST['medical_restrictions'] ?? '');
            
            if ($place_id > 0 && $activity_type_id > 0) {
                $stmt = $conn->prepare("INSERT INTO place_activities (place_id, activity_type_id, safety_info, safety_level, age_restriction, gender_restriction, medical_restrictions) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iisssss", $place_id, $activity_type_id, $safety_info, $safety_level, $age_restriction, $gender_restriction, $medical_restrictions);
                if ($stmt->execute()) {
                    $message = "Activity added to place successfully!";
                    $message_type = "success";
                    // Redirect back to same page - user can reopen activities modal
                    $place_name = $conn->query("SELECT name FROM places WHERE id = $place_id")->fetch_assoc()['name'] ?? 'Place';
                    header("Location: places.php?open_activities=1&place_id=$place_id&place_name=" . urlencode($place_name));
                    exit;
                } else {
                    $message = "Error adding activity: " . $conn->error;
                    $message_type = "error";
                }
                $stmt->close();
            }
        } elseif ($_POST['action'] === 'update_activity') {
            $id = intval($_POST['activity_id'] ?? 0);
            $safety_info = trim($_POST['safety_info'] ?? '');
            $safety_level = trim($_POST['safety_level'] ?? 'Medium');
            $age_restriction = trim($_POST['age_restriction'] ?? '');
            $gender_restriction = trim($_POST['gender_restriction'] ?? '');
            $medical_restrictions = trim($_POST['medical_restrictions'] ?? '');
            
            if ($id > 0) {
                $stmt = $conn->prepare("UPDATE place_activities SET safety_info = ?, safety_level = ?, age_restriction = ?, gender_restriction = ?, medical_restrictions = ? WHERE id = ?");
                $stmt->bind_param("sssssi", $safety_info, $safety_level, $age_restriction, $gender_restriction, $medical_restrictions, $id);
                if ($stmt->execute()) {
                    $message = "Activity updated successfully!";
                    $message_type = "success";
                    // Get place_id from activity
                    $place_id_result = $conn->query("SELECT place_id FROM place_activities WHERE id = $id");
                    if ($place_id_result && $place_id_result->num_rows > 0) {
                        $place_id = $place_id_result->fetch_assoc()['place_id'];
                        $place_name = $conn->query("SELECT name FROM places WHERE id = $place_id")->fetch_assoc()['name'] ?? 'Place';
                        header("Location: places.php?open_activities=1&place_id=$place_id&place_name=" . urlencode($place_name));
                        exit;
                    }
                } else {
                    $message = "Error updating activity: " . $conn->error;
                    $message_type = "error";
                }
                $stmt->close();
            }
        } elseif ($_POST['action'] === 'delete_activity') {
            $id = intval($_POST['activity_id'] ?? 0);
            if ($id > 0) {
                $stmt = $conn->prepare("DELETE FROM place_activities WHERE id = ?");
                $stmt->bind_param("i", $id);
                if ($stmt->execute()) {
                    $message = "Activity removed from place successfully!";
                    $message_type = "success";
                    // Get place_id
                    $place_id = intval($_POST['redirect_place_id'] ?? 0);
                    if ($place_id > 0) {
                        $place_name = $conn->query("SELECT name FROM places WHERE id = $place_id")->fetch_assoc()['name'] ?? 'Place';
                        header("Location: places.php?open_activities=1&place_id=$place_id&place_name=" . urlencode($place_name));
                        exit;
                    }
                } else {
                    $message = "Error deleting activity: " . $conn->error;
                    $message_type = "error";
                }
                $stmt->close();
            }
        }
    }
}

// Get all cities for dropdown
$cities = [];
$result = $conn->query("SELECT d.*, s.name as state_name, c.name as country_name FROM districts d JOIN states s ON d.state_id = s.id JOIN countries c ON s.country_id = c.id ORDER BY c.name, s.name, d.name");
if ($result) {
    $cities = $result->fetch_all(MYSQLI_ASSOC);
}

// Get all hotel types
$hotel_types = [];
$result = $conn->query("SELECT * FROM hotel_types ORDER BY name");
if ($result) {
    $hotel_types = $result->fetch_all(MYSQLI_ASSOC);
}

// Get all transport types
$transport_types = [];
$result = $conn->query("SELECT * FROM transport_types ORDER BY name");
if ($result) {
    $transport_types = $result->fetch_all(MYSQLI_ASSOC);
}

// Get all activity types
$activity_types = [];
$result = $conn->query("SELECT * FROM activity_types ORDER BY name");
if ($result) {
    $activity_types = $result->fetch_all(MYSQLI_ASSOC);
}

// Get all places with city, state, and country info
$places = [];
$result = $conn->query("SELECT p.*, d.name as city_name, s.name as state_name, c.name as country_name FROM places p JOIN districts d ON p.city_id = d.id JOIN states s ON d.state_id = s.id JOIN countries c ON s.country_id = c.id ORDER BY c.name, s.name, d.name, p.name");
if ($result) {
    $places = $result->fetch_all(MYSQLI_ASSOC);
}

// Get place for editing
$edit_place = null;
$edit_place_hotels = [];
$edit_place_transports = [];
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $result = $conn->query("SELECT * FROM places WHERE id = $edit_id");
    if ($result && $result->num_rows > 0) {
        $edit_place = $result->fetch_assoc();
        
        // Get hotel types for this place
        $result2 = $conn->query("SELECT hotel_type_id FROM place_hotel_types WHERE place_id = $edit_id");
        if ($result2) {
            while ($row = $result2->fetch_assoc()) {
                $edit_place_hotels[] = $row['hotel_type_id'];
            }
        }
        
        // Get transport types for this place
        $result3 = $conn->query("SELECT transport_type_id FROM place_transport_types WHERE place_id = $edit_id");
        if ($result3) {
            while ($row = $result3->fetch_assoc()) {
                $edit_place_transports[] = $row['transport_type_id'];
            }
        }
    }
}

$admin = getAdminInfo();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Places | Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="admin.css">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include 'includes/header.php'; ?>
        
        <div class="content-area">
            <div class="page-header d-flex justify-content-between align-items-center">
                <div>
                    <h1><i class="bi bi-geo-alt-fill"></i> Manage Places</h1>
                    <p>Add, edit, or delete places and manage their activities</p>
                </div>
                <div>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#placeModal" onclick="openAddModal()">
                        <i class="bi bi-plus-circle"></i> Add New Place
                    </button>
                </div>
            </div>

            <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <!-- Places List -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2><i class="bi bi-list-ul"></i> All Places (<?php echo count($places); ?>)</h2>
                    <div class="search-box">
                        <input type="text" id="searchTable" class="form-control form-control-sm" placeholder="Search places..." style="width: 250px;">
                    </div>
                </div>
                <div class="card-body">
                    <?php if (count($places) > 0): ?>
                    <div class="table-responsive">
                        <table class="data-table" id="dataTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Image</th>
                                    <th>Place Name</th>
                                    <th>City</th>
                                    <th>State</th>
                                    <th>Country</th>
                                    <th>Description</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($places as $place): ?>
                                <tr>
                                    <td><?php echo $place['id']; ?></td>
                                    <td>
                                        <?php if ($place['image']): ?>
                                        <img src="<?php echo htmlspecialchars($place['image']); ?>" alt="<?php echo htmlspecialchars($place['name']); ?>" class="table-image">
                                        <?php else: ?>
                                        <span class="text-gray-400">No image</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?php echo htmlspecialchars($place['name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($place['city_name']); ?></td>
                                    <td><?php echo htmlspecialchars($place['state_name']); ?></td>
                                    <td><?php echo htmlspecialchars($place['country_name']); ?></td>
                                    <td><?php echo htmlspecialchars(substr($place['description'] ?? '', 0, 80)); ?><?php echo strlen($place['description'] ?? '') > 80 ? '...' : ''; ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button type="button" class="btn-icon btn-edit" title="Edit" onclick="openEditModal(<?php echo $place['id']; ?>, <?php echo $place['city_id']; ?>, '<?php echo htmlspecialchars(addslashes($place['name'])); ?>', '<?php echo htmlspecialchars(addslashes($place['description'] ?? '')); ?>', '<?php echo htmlspecialchars(addslashes($place['image'] ?? '')); ?>', '<?php echo htmlspecialchars(addslashes($place['security_info'] ?? '')); ?>', '<?php echo htmlspecialchars(addslashes($place['night_travel_safety'] ?? '')); ?>', '<?php echo htmlspecialchars(addslashes($place['safety_level'] ?? 'Medium')); ?>', '<?php echo htmlspecialchars(addslashes($place['religion_restrictions'] ?? '')); ?>', '<?php echo htmlspecialchars(addslashes($place['best_time_of_day'] ?? '')); ?>', '<?php echo $place['avg_visit_time_hours'] ?? ''; ?>', '<?php echo htmlspecialchars(addslashes($place['crowd_level'] ?? '')); ?>', '<?php echo $place['avg_waiting_time_minutes'] ?? ''; ?>', '<?php echo htmlspecialchars(addslashes($place['weather_dependency'] ?? '')); ?>', '<?php echo htmlspecialchars(addslashes($place['monsoon_risk'] ?? '')); ?>', '<?php echo htmlspecialchars(addslashes($place['sudden_closure_risk'] ?? '')); ?>', '<?php echo htmlspecialchars(addslashes($place['child_friendly'] ?? '')); ?>', '<?php echo htmlspecialchars(addslashes($place['senior_friendly'] ?? '')); ?>', '<?php echo htmlspecialchars(addslashes($place['physical_effort'] ?? '')); ?>', '<?php echo $place['nearest_hospital_distance_km'] ?? ''; ?>', '<?php echo htmlspecialchars(addslashes($place['police_response_level'] ?? '')); ?>', '<?php echo htmlspecialchars(addslashes($place['digital_amenities'] ?? '')); ?>', '<?php echo htmlspecialchars(addslashes($place['mobile_network'] ?? '')); ?>', '<?php echo htmlspecialchars(addslashes($place['internet_availability'] ?? '')); ?>', '<?php echo $place['experience_score'] ?? ''; ?>', '<?php echo $place['priority_level'] ?? '3'; ?>', '<?php echo htmlspecialchars(addslashes($place['status'] ?? 'active')); ?>', '<?php echo htmlspecialchars(addslashes($place['is_verified'] ?? 'no')); ?>')">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <a href="place-activities.php?place_id=<?php echo $place['id']; ?>" class="btn-icon btn-info" title="Manage Activities">
                                                <i class="bi bi-activity"></i>
                                            </a>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this place?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $place['id']; ?>">
                                                <button type="submit" class="btn-icon btn-delete" title="Delete">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="empty-state">
                        <i class="bi bi-inbox"></i>
                        <p>No places found. Add your first place above.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Place Modal -->
    <div class="modal fade" id="placeModal" tabindex="-1" aria-labelledby="placeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="placeModalLabel"><i class="bi bi-geo-alt-fill"></i> <span id="modalTitle">Add Place</span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="placeForm">
                    <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                        <input type="hidden" name="action" id="formAction" value="add">
                        <input type="hidden" name="id" id="formId" value="">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="placeCity" class="form-label">City <span class="text-danger">*</span></label>
                                <select class="form-select select2" id="placeCity" name="city_id" required>
                                    <option value="">Select City</option>
                                    <?php foreach ($cities as $city): ?>
                                    <option value="<?php echo $city['id']; ?>"><?php echo htmlspecialchars($city['name'] . ', ' . $city['state_name'] . ', ' . $city['country_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="placeName" class="form-label">Place Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="placeName" name="name" required>
                            </div>
                        
                        
                            <div class="col-md-6 mb-3">
                                <label for="placeImage" class="form-label">Image URL</label>
                                <input type="url" class="form-control" id="placeImage" name="image" placeholder="https://example.com/image.jpg">
                            </div>
                            
                            <div class=" col-md-6 mb-3">
                                <label for="placeSafetyLevel" class="form-label"><i class="bi bi-shield-check"></i> Safety Level <span class="text-danger">*</span></label>
                                <select class="form-select" id="placeSafetyLevel" name="safety_level" required>
                                    <option value="High">High</option>
                                    <option value="Medium" selected>Medium</option>
                                    <option value="Low">Low</option>
                                    <option value="Worst">Worst</option>
                                </select>
                                <small class="form-text text-muted">Select the overall safety level of this place</small>
                            </div>

                        </div>
                        
                        
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="placeSecurityInfo" class="form-label">Security Information</label>
                                <textarea class="form-control" id="placeSecurityInfo" name="security_info" rows="2" placeholder="General security information..."></textarea>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="placeNightTravelSafety" class="form-label">Night Travel Safety</label>
                                <textarea class="form-control" id="placeNightTravelSafety" name="night_travel_safety" rows="2" placeholder="Night travel safety information..."></textarea>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="placeDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="placeDescription" name="description" rows="2" placeholder="Brief description of the place..."></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label"><i class="bi bi-heart"></i> Religion Restrictions</label>
                                <div class="border rounded p-3" style="max-height: 200px; overflow-y: auto;">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="selectAllReligions" onchange="toggleAll('religion')">
                                        <label class="form-check-label fw-bold" for="selectAllReligions">
                                            Select All
                                        </label>
                                    </div>
                                    <hr class="my-2">

                                    <div class="form-check">
                                        <input class="form-check-input religion-checkbox" type="checkbox" name="religion_restrictions[]" value="No Restriction" id="religion_none" checked>
                                        <label class="form-check-label" for="religion_none">No Restriction</label>
                                    </div>

                                    <hr class="my-2">
                                    <div class="form-check">
                                        <input class="form-check-input religion-checkbox" type="checkbox" name="religion_restrictions[]" value="Hinduism" id="religion_hinduism">
                                        <label class="form-check-label" for="religion_hinduism">Hinduism</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input religion-checkbox" type="checkbox" name="religion_restrictions[]" value="Islam" id="religion_islam">
                                        <label class="form-check-label" for="religion_islam">Islam</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input religion-checkbox" type="checkbox" name="religion_restrictions[]" value="Christianity" id="religion_christianity">
                                        <label class="form-check-label" for="religion_christianity">Christianity</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input religion-checkbox" type="checkbox" name="religion_restrictions[]" value="Sikhism" id="religion_sikhism">
                                        <label class="form-check-label" for="religion_sikhism">Sikhism</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input religion-checkbox" type="checkbox" name="religion_restrictions[]" value="Buddhism" id="religion_buddhism">
                                        <label class="form-check-label" for="religion_buddhism">Buddhism</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input religion-checkbox" type="checkbox" name="religion_restrictions[]" value="Jainism" id="religion_jainism">
                                        <label class="form-check-label" for="religion_jainism">Jainism</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input religion-checkbox" type="checkbox" name="religion_restrictions[]" value="Judaism" id="religion_judaism">
                                        <label class="form-check-label" for="religion_judaism">Judaism</label>
                                    </div>
                                    
                                </div>
                                <small class="form-text text-muted">Select applicable religion restrictions for this place</small>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label"><i class="bi bi-building"></i> Available Hotel Types</label>
                                <div class="border rounded p-3" style="max-height: 200px; overflow-y: auto;">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="selectAllHotels" onchange="toggleAll('hotel')">
                                        <label class="form-check-label fw-bold" for="selectAllHotels">
                                            Select All
                                        </label>
                                    </div>
                                    <hr class="my-2">
                                    <?php foreach ($hotel_types as $hotel): ?>
                                    <div class="form-check">
                                        <input class="form-check-input hotel-checkbox" type="checkbox" name="hotel_types[]" value="<?php echo $hotel['id']; ?>" id="hotel_<?php echo $hotel['id']; ?>">
                                        <label class="form-check-label" for="hotel_<?php echo $hotel['id']; ?>">
                                            <?php echo htmlspecialchars($hotel['name']); ?>
                                        </label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label"><i class="bi bi-truck"></i> Available Transport Types</label>
                                <div class="border rounded p-3" style="max-height: 200px; overflow-y: auto;">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="selectAllTransports" onchange="toggleAll('transport')">
                                        <label class="form-check-label fw-bold" for="selectAllTransports">
                                            Select All
                                        </label>
                                    </div>
                                    <hr class="my-2">
                                    <?php foreach ($transport_types as $transport): ?>
                                    <div class="form-check">
                                        <input class="form-check-input transport-checkbox" type="checkbox" name="transport_types[]" value="<?php echo $transport['id']; ?>" id="transport_<?php echo $transport['id']; ?>">
                                        <label class="form-check-label" for="transport_<?php echo $transport['id']; ?>">
                                            <?php echo htmlspecialchars($transport['name']); ?>
                                        </label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Visit Timing & Crowd Data -->
                        <hr class="my-3">
                        <h6 class="mb-3"><i class="bi bi-clock-history"></i> Visit Timing & Crowd Data</h6>
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label for="placeBestTimeOfDay" class="form-label">Best Time of Day</label>
                                <select class="form-select" id="placeBestTimeOfDay" name="best_time_of_day">
                                    <option value="">Select</option>
                                    <option value="morning">Morning</option>
                                    <option value="afternoon">Afternoon</option>
                                    <option value="evening">Evening</option>
                                    <option value="anytime">Anytime</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="placeAvgVisitTime" class="form-label">Avg Visit Time (Hours)</label>
                                <input type="number" step="0.1" class="form-control" id="placeAvgVisitTime" name="avg_visit_time_hours" placeholder="e.g., 2.5">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="placeCrowdLevel" class="form-label">Crowd Level</label>
                                <select class="form-select" id="placeCrowdLevel" name="crowd_level">
                                    <option value="">Select</option>
                                    <option value="low">Low</option>
                                    <option value="medium">Medium</option>
                                    <option value="high">High</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="placeWaitingTime" class="form-label">Avg Waiting Time (Minutes)</label>
                                <input type="number" class="form-control" id="placeWaitingTime" name="avg_waiting_time_minutes" placeholder="e.g., 30">
                            </div>
                        </div>
                        
                        <!-- Seasonal / Weather Risk -->
                        <hr class="my-3">
                        <h6 class="mb-3"><i class="bi bi-cloud-rain"></i> Seasonal / Weather Risk</h6>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="placeWeatherDependency" class="form-label">Weather Dependency</label>
                                <select class="form-select" id="placeWeatherDependency" name="weather_dependency">
                                    <option value="">Select</option>
                                    <option value="low">Low</option>
                                    <option value="medium">Medium</option>
                                    <option value="high">High</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="placeMonsoonRisk" class="form-label">Monsoon Risk</label>
                                <select class="form-select" id="placeMonsoonRisk" name="monsoon_risk">
                                    <option value="">Select</option>
                                    <option value="safe">Safe</option>
                                    <option value="risky">Risky</option>
                                    <option value="closed">Closed</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="placeSuddenClosureRisk" class="form-label">Sudden Closure Risk</label>
                                <select class="form-select" id="placeSuddenClosureRisk" name="sudden_closure_risk">
                                    <option value="">Select</option>
                                    <option value="rare">Rare</option>
                                    <option value="occasional">Occasional</option>
                                    <option value="frequent">Frequent</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Family / Health Usability -->
                        <hr class="my-3">
                        <h6 class="mb-3"><i class="bi bi-people"></i> Family / Health Usability</h6>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="placeChildFriendly" class="form-label">Child Friendly</label>
                                <select class="form-select" id="placeChildFriendly" name="child_friendly">
                                    <option value="">Select</option>
                                    <option value="yes">Yes</option>
                                    <option value="no">No</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="placeSeniorFriendly" class="form-label">Senior Friendly</label>
                                <select class="form-select" id="placeSeniorFriendly" name="senior_friendly">
                                    <option value="">Select</option>
                                    <option value="yes">Yes</option>
                                    <option value="no">No</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="placePhysicalEffort" class="form-label">Physical Effort</label>
                                <select class="form-select" id="placePhysicalEffort" name="physical_effort">
                                    <option value="">Select</option>
                                    <option value="low">Low</option>
                                    <option value="medium">Medium</option>
                                    <option value="high">High</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Emergency Accessibility -->
                        <hr class="my-3">
                        <h6 class="mb-3"><i class="bi bi-hospital"></i> Emergency Accessibility</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="placeHospitalDistance" class="form-label">Nearest Hospital Distance (km)</label>
                                <input type="number" step="0.01" class="form-control" id="placeHospitalDistance" name="nearest_hospital_distance_km" placeholder="e.g., 5.5">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="placePoliceResponse" class="form-label">Police Response Level</label>
                                <select class="form-select" id="placePoliceResponse" name="police_response_level">
                                    <option value="">Select</option>
                                    <option value="good">Good</option>
                                    <option value="medium">Medium</option>
                                    <option value="poor">Poor</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Network & Power Availability -->
                        <hr class="my-3">
                        <h6 class="mb-3"><i class="bi bi-wifi"></i> Network & Power Availability</h6>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="placeMobileNetwork" class="form-label">Mobile Network</label>
                                <select class="form-select" id="placeMobileNetwork" name="mobile_network">
                                    <option value="">Select</option>
                                    <option value="good">Good</option>
                                    <option value="weak">Weak</option>
                                    <option value="none">None</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="placeInternetAvailability" class="form-label">Internet Availability</label>
                                <select class="form-select" id="placeInternetAvailability" name="internet_availability">
                                    <option value="">Select</option>
                                    <option value="yes">Yes</option>
                                    <option value="limited">Limited</option>
                                    <option value="no">No</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="placeDigitalAmenities" class="form-label">Digital Amenities</label>
                                <input type="text" class="form-control" id="placeDigitalAmenities" name="digital_amenities" placeholder="WiFi, charging points, etc.">
                            </div>
                        </div>
                        
                        <!-- Place Priority & Experience Quality -->
                        <hr class="my-3">
                        <h6 class="mb-3"><i class="bi bi-star"></i> Place Priority & Experience Quality</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="placeExperienceScore" class="form-label">Experience Score (1-5)</label>
                                <input type="number" min="1" max="5" class="form-control" id="placeExperienceScore" name="experience_score" placeholder="1 to 5">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="placePriorityLevel" class="form-label">Priority Level</label>
                                <input type="number" class="form-control" id="placePriorityLevel" name="priority_level" value="3" placeholder="Default: 3">
                                <small class="form-text text-muted">Higher number = higher priority in recommendations</small>
                            </div>
                        </div>
                        <div class="row g-2">
                            <div class="col-md-4 mb-3">
                                <label for="placeStatus" class="form-label">Status</label>
                                <select class="form-select" id="placeStatus" name="status">
                                    <option value="active" selected>Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="placeIsVerified" class="form-label">Is Verified</label>
                                <select class="form-select" id="placeIsVerified" name="is_verified">
                                    <option value="no" selected>No</option>
                                    <option value="yes">Yes</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> <span id="submitText">Add Place</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Activities Management Modal -->
    <div class="modal fade" id="activitiesModal" tabindex="-1" aria-labelledby="activitiesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="activitiesModalLabel"><i class="bi bi-activity"></i> Manage Activities for: <span id="placeNameTitle"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="currentPlaceId" value="">
                    
                    <!-- Add Activity Form -->
                    <div class="card mb-3 border-primary">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0"><i class="bi bi-plus-circle"></i> Add New Activity</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST" id="activityForm">
                                <input type="hidden" name="action" value="add_activity">
                                <input type="hidden" name="place_id" id="activityPlaceId" value="">
                                
                                <div class="row g-2">
                                    <div class="col-md-3 mb-2">
                                        <label for="activityType" class="form-label small fw-bold">Activity Type <span class="text-danger">*</span></label>
                                        <select class="form-select form-select-sm select2" id="activityType" name="activity_type_id" required>
                                            <option value="">Select Activity</option>
                                            <?php foreach ($activity_types as $activity): ?>
                                            <option value="<?php echo $activity['id']; ?>"><?php echo htmlspecialchars($activity['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2 mb-2">
                                        <label for="activitySafetyLevel" class="form-label small fw-bold"><i class="bi bi-shield-check"></i> Safety Level <span class="text-danger">*</span></label>
                                        <select class="form-select form-select-sm" id="activitySafetyLevel" name="safety_level" required>
                                            <option value="High">High</option>
                                            <option value="Medium" selected>Medium</option>
                                            <option value="Low">Low</option>
                                            <option value="Worst">Worst</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2 mb-2">
                                        <label for="activityAge" class="form-label small fw-bold">Age</label>
                                        <input type="text" class="form-control form-control-sm" id="activityAge" name="age_restriction" placeholder="e.g., 18+, All">
                                    </div>
                                    <div class="col-md-2 mb-2">
                                        <label for="activityGender" class="form-label small fw-bold">Gender</label>
                                        <select class="form-select form-select-sm" id="activityGender" name="gender_restriction">
                                            <option value="">No restriction</option>
                                            <option value="Male only">Male only</option>
                                            <option value="Female only">Female only</option>
                                            <option value="Mixed">Mixed</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <label for="activityMedicalRestrictions" class="form-label small fw-bold"><i class="bi bi-heart-pulse"></i> Medical Restrictions</label>
                                        <input type="text" class="form-control form-control-sm" id="activityMedicalRestrictions" name="medical_restrictions" placeholder="Diseases/conditions">
                                    </div>
                                </div>
                                <div class="row g-2">
                                    <div class="col-md-10 mb-2">
                                        <label for="activitySafetyInfo" class="form-label small fw-bold">Safety Information</label>
                                        <textarea class="form-control form-control-sm" id="activitySafetyInfo" name="safety_info" rows="2" placeholder="Safety guidelines and precautions..."></textarea>
                                    </div>
                                    <div class="col-md-2 mb-2 d-flex align-items-end">
                                        <button type="submit" class="btn btn-primary btn-sm w-100">
                                            <i class="bi bi-plus-circle"></i> Add
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Activities Table -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="bi bi-list-ul"></i> Activities List</h6>
                        </div>
                        <div class="card-body">
                            <div id="activitiesTableContainer">
                                <!-- Activities will be loaded here via AJAX -->
                                <div class="text-center py-3">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Activity Modal -->
    <div class="modal fade" id="editActivityModal" tabindex="-1" aria-labelledby="editActivityModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editActivityModalLabel"><i class="bi bi-pencil"></i> Edit Activity</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="editActivityForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_activity">
                        <input type="hidden" name="activity_id" id="editActivityId" value="">
                        <div class="mb-3">
                            <label class="form-label">Activity Type</label>
                            <input type="text" class="form-control" id="editActivityTypeName" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="editActivitySafetyLevel" class="form-label"><i class="bi bi-shield-check"></i> Safety Level <span class="text-danger">*</span></label>
                            <select class="form-select" id="editActivitySafetyLevel" name="safety_level" required>
                                <option value="High">High</option>
                                <option value="Medium" selected>Medium</option>
                                <option value="Low">Low</option>
                                <option value="Worst">Worst</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="editActivityAge" class="form-label">Age Restriction</label>
                            <input type="text" class="form-control" id="editActivityAge" name="age_restriction">
                        </div>
                        <div class="mb-3">
                            <label for="editActivityGender" class="form-label">Gender Restriction</label>
                            <select class="form-select" id="editActivityGender" name="gender_restriction">
                                <option value="">No restriction</option>
                                <option value="Male only">Male only</option>
                                <option value="Female only">Female only</option>
                                <option value="Mixed">Mixed</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="editActivityMedicalRestrictions" class="form-label"><i class="bi bi-heart-pulse"></i> Medical Restrictions</label>
                            <input type="text" class="form-control" id="editActivityMedicalRestrictions" name="medical_restrictions" placeholder="Diseases/conditions that prevent this activity">
                            <small class="form-text text-muted">List diseases or health conditions that would prevent someone from doing this activity</small>
                        </div>
                        <div class="mb-3">
                            <label for="editActivitySafetyInfo" class="form-label">Safety Information</label>
                            <textarea class="form-control" id="editActivitySafetyInfo" name="safety_info" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Activity</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        // Remove any existing event listeners to prevent duplicates
        $('#placeModal').off('shown.bs.modal hidden.bs.modal show.bs.modal');
        $('#activitiesModal').off('shown.bs.modal hidden.bs.modal');
        $('#placeCity, #placeHotelTypes, #placeTransportTypes, #activityType').off('select2:open');
        
        // Initialize Select2 when place modal opens
        $('#placeModal').on('shown.bs.modal', function() {
            // Destroy existing Select2 instance if any (only for city)
            if ($('#placeCity').hasClass('select2-hidden-accessible')) {
                $('#placeCity').select2('destroy');
            }
            
            // Initialize City dropdown only
            $('#placeCity').select2({
                theme: 'bootstrap-5',
                width: '100%',
                dropdownParent: $('#placeModal'),
                allowClear: true
            });
            
            // Reset values if it's an add action
            var isAddAction = document.getElementById('formAction') && document.getElementById('formAction').value === 'add';
            if (isAddAction) {
                setTimeout(function() {
                    if ($('#placeCity').hasClass('select2-hidden-accessible')) {
                        $('#placeCity').val(null).trigger('change');
                    }
                    // Uncheck all checkboxes except "No Restriction" which should be checked by default
                    $('.hotel-checkbox, .transport-checkbox').prop('checked', false);
                    $('.religion-checkbox').prop('checked', false);
                    $('#religion_none').prop('checked', true); // Default: No Restriction
                    $('#selectAllHotels, #selectAllTransports, #selectAllReligions').prop('checked', false);
                }, 100);
            }
            
            // Auto-focus search when dropdown opens
            $('#placeCity').off('select2:open').on('select2:open', function() {
                setTimeout(function() {
                    $('.select2-search__field').focus();
                }, 50);
            });
        });
        
        // Re-initialize Select2 when modal is hidden to prevent conflicts
        $('#placeModal').on('hidden.bs.modal', function() {
            if ($('#placeCity').hasClass('select2-hidden-accessible')) {
                $('#placeCity').select2('destroy');
            }
        });

        // Initialize Select2 when activities modal opens
        $('#activitiesModal').on('shown.bs.modal', function() {
            // Destroy existing Select2 instance if any
            if ($('#activityType').hasClass('select2-hidden-accessible')) {
                $('#activityType').select2('destroy');
            }
            
            // Initialize Activity Type dropdown
            $('#activityType').select2({
                theme: 'bootstrap-5',
                width: '100%',
                dropdownParent: $('#activitiesModal'),
                allowClear: true
            });
            
            // Auto-focus search when dropdown opens (remove old listeners first)
            $('#activityType').off('select2:open').on('select2:open', function() {
                setTimeout(function() {
                    $('.select2-search__field').focus();
                }, 50);
            });
        });
        
        // Re-initialize Select2 when activities modal is hidden
        $('#activitiesModal').on('hidden.bs.modal', function() {
            if ($('#activityType').hasClass('select2-hidden-accessible')) {
                $('#activityType').select2('destroy');
            }
        });

        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Add Place';
            document.getElementById('submitText').textContent = 'Add Place';
            document.getElementById('formAction').value = 'add';
            document.getElementById('formId').value = '';
            document.getElementById('placeForm').reset();
            
            // Uncheck all checkboxes except "No Restriction" which should be checked by default
            $('.hotel-checkbox, .transport-checkbox').prop('checked', false);
            $('.religion-checkbox').prop('checked', false);
            $('#religion_none').prop('checked', true); // Default: No Restriction
            $('#selectAllHotels, #selectAllTransports, #selectAllReligions').prop('checked', false);
        }
        

        function openEditModal(id, cityId, name, description, image, securityInfo, nightTravelSafety, safetyLevel, religionRestrictions, bestTimeOfDay, avgVisitTimeHours, crowdLevel, avgWaitingTimeMinutes, weatherDependency, monsoonRisk, suddenClosureRisk, childFriendly, seniorFriendly, physicalEffort, nearestHospitalDistanceKm, policeResponseLevel, digitalAmenities, mobileNetwork, internetAvailability, experienceScore, priorityLevel, status, isVerified) {
            document.getElementById('modalTitle').textContent = 'Edit Place';
            document.getElementById('submitText').textContent = 'Update Place';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('formId').value = id;
            document.getElementById('placeName').value = name;
            document.getElementById('placeDescription').value = description;
            document.getElementById('placeImage').value = image;
            document.getElementById('placeSecurityInfo').value = securityInfo;
            document.getElementById('placeNightTravelSafety').value = nightTravelSafety;
            document.getElementById('placeSafetyLevel').value = safetyLevel || 'Medium';
            
            // New fields
            document.getElementById('placeBestTimeOfDay').value = bestTimeOfDay || '';
            document.getElementById('placeAvgVisitTime').value = avgVisitTimeHours || '';
            document.getElementById('placeCrowdLevel').value = crowdLevel || '';
            document.getElementById('placeWaitingTime').value = avgWaitingTimeMinutes || '';
            document.getElementById('placeWeatherDependency').value = weatherDependency || '';
            document.getElementById('placeMonsoonRisk').value = monsoonRisk || '';
            document.getElementById('placeSuddenClosureRisk').value = suddenClosureRisk || '';
            document.getElementById('placeChildFriendly').value = childFriendly || '';
            document.getElementById('placeSeniorFriendly').value = seniorFriendly || '';
            document.getElementById('placePhysicalEffort').value = physicalEffort || '';
            document.getElementById('placeHospitalDistance').value = nearestHospitalDistanceKm || '';
            document.getElementById('placePoliceResponse').value = policeResponseLevel || '';
            document.getElementById('placeDigitalAmenities').value = digitalAmenities || '';
            document.getElementById('placeMobileNetwork').value = mobileNetwork || '';
            document.getElementById('placeInternetAvailability').value = internetAvailability || '';
            document.getElementById('placeExperienceScore').value = experienceScore || '';
            document.getElementById('placePriorityLevel').value = priorityLevel || '3';
            
            // Uncheck all checkboxes first
            $('.hotel-checkbox, .transport-checkbox, .religion-checkbox').prop('checked', false);
            $('#selectAllHotels, #selectAllTransports, #selectAllReligions').prop('checked', false);
            
            // Set religion restrictions if provided
            if (religionRestrictions && religionRestrictions.trim() !== '') {
                var religions = religionRestrictions.split(',');
                religions.forEach(function(religion) {
                    religion = religion.trim();
                    if (religion) {
                        // Map religion names to checkbox IDs
                        var religionMap = {
                            'Hinduism': 'religion_hinduism',
                            'Islam': 'religion_islam',
                            'Christianity': 'religion_christianity',
                            'Sikhism': 'religion_sikhism',
                            'Buddhism': 'religion_buddhism',
                            'Jainism': 'religion_jainism',
                            'Judaism': 'religion_judaism',
                            'No Restriction': 'religion_none'
                        };
                        if (religionMap[religion]) {
                            $(religionMap[religion]).prop('checked', true);
                        }
                    }
                });
                // Check if all are selected
                var totalReligions = $('.religion-checkbox').length;
                var checkedReligions = $('.religion-checkbox:checked').length;
                if (checkedReligions === totalReligions && totalReligions > 0) {
                    $('#selectAllReligions').prop('checked', true);
                }
            } else {
                // Default to "No Restriction" if no restrictions set
                $('#religion_none').prop('checked', true);
            }
            
            var modal = new bootstrap.Modal(document.getElementById('placeModal'));
            modal.show();
            
            // Wait for modal to be shown and Select2 to be initialized
            setTimeout(function() {
                if ($('#placeCity').hasClass('select2-hidden-accessible')) {
                    $('#placeCity').val(cityId).trigger('change');
                }
                
                // Load hotel types and transport types for this place
                $.ajax({
                    url: 'places.php',
                    method: 'GET',
                    data: { get_place_data: id },
                    dataType: 'json',
                    success: function(response) {
                        // Set hotel type checkboxes
                        if (response.hotel_types && response.hotel_types.length > 0) {
                            response.hotel_types.forEach(function(hotelId) {
                                $('#hotel_' + hotelId).prop('checked', true);
                            });
                            // Check if all are selected
                            var totalHotels = $('.hotel-checkbox').length;
                            if (response.hotel_types.length === totalHotels) {
                                $('#selectAllHotels').prop('checked', true);
                            }
                        }
                        
                        // Set transport type checkboxes
                        if (response.transport_types && response.transport_types.length > 0) {
                            response.transport_types.forEach(function(transportId) {
                                $('#transport_' + transportId).prop('checked', true);
                            });
                            // Check if all are selected
                            var totalTransports = $('.transport-checkbox').length;
                            if (response.transport_types.length === totalTransports) {
                                $('#selectAllTransports').prop('checked', true);
                            }
                        }
                    }
                });
            }, 500);
        }
        
        // Toggle all checkboxes function
        function toggleAll(type) {
            if (type === 'hotel') {
                var isChecked = $('#selectAllHotels').prop('checked');
                $('.hotel-checkbox').prop('checked', isChecked);
            } else if (type === 'transport') {
                var isChecked = $('#selectAllTransports').prop('checked');
                $('.transport-checkbox').prop('checked', isChecked);
            } else if (type === 'religion') {
                var isChecked = $('#selectAllReligions').prop('checked');
                $('.religion-checkbox').prop('checked', isChecked);
            }
        }
        
        // Update select all checkbox when individual checkboxes change (using event delegation)
        $(document).on('change', '.hotel-checkbox', function() {
            var total = $('.hotel-checkbox').length;
            var checked = $('.hotel-checkbox:checked').length;
            $('#selectAllHotels').prop('checked', total === checked && total > 0);
        });
        
        $(document).on('change', '.transport-checkbox', function() {
            var total = $('.transport-checkbox').length;
            var checked = $('.transport-checkbox:checked').length;
            $('#selectAllTransports').prop('checked', total === checked && total > 0);
        });
        
        $(document).on('change', '.religion-checkbox', function() {
            var total = $('.religion-checkbox').length;
            var checked = $('.religion-checkbox:checked').length;
            $('#selectAllReligions').prop('checked', total === checked && total > 0);
        });

        function openActivitiesModal(placeId, placeName) {
            document.getElementById('placeNameTitle').textContent = placeName;
            document.getElementById('currentPlaceId').value = placeId;
            document.getElementById('activityPlaceId').value = placeId;
            document.getElementById('activityForm').reset();
            $('#activityType').val(null).trigger('change');
            
            // Clear any existing alerts
            $('#activitiesModal .alert').remove();
            
            loadActivitiesTable(placeId);
            
            var modal = new bootstrap.Modal(document.getElementById('activitiesModal'));
            modal.show();
        }
        
        // Auto-open activities modal if we have a success message and place_id in URL
        <?php if (isset($_GET['open_activities']) && isset($_GET['place_id'])): ?>
        $(document).ready(function() {
            var placeId = <?php echo intval($_GET['place_id']); ?>;
            var placeName = '<?php echo htmlspecialchars(addslashes($_GET['place_name'] ?? 'Place')); ?>';
            openActivitiesModal(placeId, placeName);
        });
        <?php endif; ?>

        function loadActivitiesTable(placeId) {
            $.ajax({
                url: 'places.php',
                method: 'GET',
                data: { get_activities: placeId },
                success: function(response) {
                    $('#activitiesTableContainer').html(response);
                },
                error: function() {
                    $('#activitiesTableContainer').html('<div class="alert alert-danger">Error loading activities</div>');
                }
            });
        }

        function openEditActivityModal(activityId, activityName, safetyInfo, safetyLevel, ageRestriction, genderRestriction, medicalRestrictions) {
            document.getElementById('editActivityId').value = activityId;
            document.getElementById('editActivityTypeName').value = activityName;
            document.getElementById('editActivitySafetyInfo').value = safetyInfo || '';
            document.getElementById('editActivitySafetyLevel').value = safetyLevel || 'Medium';
            document.getElementById('editActivityAge').value = ageRestriction || '';
            document.getElementById('editActivityGender').value = genderRestriction || '';
            document.getElementById('editActivityMedicalRestrictions').value = medicalRestrictions || '';
            
            var modal = new bootstrap.Modal(document.getElementById('editActivityModal'));
            modal.show();
        }

        function deleteActivity(activityId, placeId) {
            if (confirm('Are you sure you want to remove this activity from the place?')) {
                var form = document.createElement('form');
                form.method = 'POST';
                form.action = 'places.php';
                form.innerHTML = '<input type="hidden" name="action" value="delete_activity">' +
                                '<input type="hidden" name="activity_id" value="' + activityId + '">' +
                                '<input type="hidden" name="redirect_place_id" value="' + placeId + '">';
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Handle activity form submission - submit normally to show success message
        $('#activityForm').on('submit', function(e) {
            // Let form submit normally - it will reload the page with success message
            // The page will reload and user can reopen activities modal if needed
        });

        // Table search functionality
        document.getElementById('searchTable').addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const table = document.getElementById('dataTable');
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
            
            for (let i = 0; i < rows.length; i++) {
                const row = rows[i];
                const text = row.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            }
        });
    </script>
</body>
</html>

