<?php
require_once 'config.php';
requireLogin();

$conn = getDBConnection();
$message = '';
$message_type = '';

// Get place_id from URL
$place_id = isset($_GET['place_id']) ? intval($_GET['place_id']) : 0;

if ($place_id <= 0) {
    header('Location: places.php');
    exit;
}

// Get place information
$place = null;
$result = $conn->query("SELECT p.*, d.name as city_name, s.name as state_name, c.name as country_name FROM places p JOIN districts d ON p.city_id = d.id JOIN states s ON d.state_id = s.id JOIN countries c ON s.country_id = c.id WHERE p.id = $place_id");
if ($result && $result->num_rows > 0) {
    $place = $result->fetch_assoc();
} else {
    header('Location: places.php');
    exit;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add_activity') {
            $activity_type_id = intval($_POST['activity_type_id'] ?? 0);
            $safety_info = trim($_POST['safety_info'] ?? '');
            $safety_level = trim($_POST['safety_level'] ?? 'Medium');
            $age_restriction = trim($_POST['age_restriction'] ?? '');
            $gender_restriction = trim($_POST['gender_restriction'] ?? '');
            $medical_restrictions = trim($_POST['medical_restrictions'] ?? '');
            $open_time = trim($_POST['open_time'] ?? '');
            $close_time = trim($_POST['close_time'] ?? '');
            $available_months = isset($_POST['available_months']) ? implode(',', $_POST['available_months']) : '';
            
            // Handle ticket price
            $ticket_price = trim($_POST['ticket_price'] ?? '');
            if (empty($ticket_price) || $ticket_price === '0' || strtolower($ticket_price) === 'free') {
                $ticket_price = 'Free';
            }
            
            // Activity Difficulty & Physical Load
            $difficulty_level = trim($_POST['difficulty_level'] ?? '');
            $physical_effort = trim($_POST['physical_effort'] ?? '');
            // Weather & Season Risk
            $weather_dependency = trim($_POST['weather_dependency'] ?? '');
            $monsoon_risk = trim($_POST['monsoon_risk'] ?? '');
            $cancellation_chance = trim($_POST['cancellation_chance'] ?? '');
            // Safety Gear & Requirements
            $safety_equipment_required = trim($_POST['safety_equipment_required'] ?? '');
            $equipment_provided = trim($_POST['equipment_provided'] ?? '');
            // Supervision & Guide Quality
            $guide_mandatory = trim($_POST['guide_mandatory'] ?? '');
            $operator_type = trim($_POST['operator_type'] ?? '');
            // Time Flexibility & Buffer
            $buffer_time_minutes = !empty($_POST['buffer_time_minutes']) ? intval($_POST['buffer_time_minutes']) : null;
            $reporting_time = trim($_POST['reporting_time'] ?? '');
            // Crowd & Waiting Impact
            $crowd_level = trim($_POST['crowd_level'] ?? '');
            $avg_waiting_time_minutes = !empty($_POST['avg_waiting_time_minutes']) ? intval($_POST['avg_waiting_time_minutes']) : null;
            // Age & Body Limitation
            $min_age = !empty($_POST['min_age']) ? intval($_POST['min_age']) : null;
            $max_age = !empty($_POST['max_age']) ? intval($_POST['max_age']) : null;
            // Medical Emergency Access
            $nearest_medical_distance_km = !empty($_POST['nearest_medical_distance_km']) ? floatval($_POST['nearest_medical_distance_km']) : null;
            $emergency_support_level = trim($_POST['emergency_support_level'] ?? '');
            // Activity Stop Conditions
            $stop_conditions = trim($_POST['stop_conditions'] ?? '');
            // Family & Solo Travel Suitability
            $family_friendly = trim($_POST['family_friendly'] ?? '');
            $solo_traveler_safe = trim($_POST['solo_traveler_safe'] ?? '');
            // Status, Verification & Priority
            $status = trim($_POST['status'] ?? 'active');
            $is_verified = trim($_POST['is_verified'] ?? 'no');
            $priority_level = !empty($_POST['priority_level']) ? intval($_POST['priority_level']) : 3;
            
            if ($activity_type_id > 0) {
                $stmt = $conn->prepare("INSERT INTO place_activities (place_id, activity_type_id, safety_info, safety_level, age_restriction, gender_restriction, medical_restrictions, open_time, close_time, available_months, ticket_price, difficulty_level, physical_effort, weather_dependency, monsoon_risk, cancellation_chance, safety_equipment_required, equipment_provided, guide_mandatory, operator_type, buffer_time_minutes, reporting_time, crowd_level, avg_waiting_time_minutes, min_age, max_age, nearest_medical_distance_km, emergency_support_level, stop_conditions, family_friendly, solo_traveler_safe, status, is_verified, priority_level) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iisssssssssssssssssssisissidssssssi", $place_id, $activity_type_id, $safety_info, $safety_level, $age_restriction, $gender_restriction, $medical_restrictions, $open_time, $close_time, $available_months, $ticket_price, $difficulty_level, $physical_effort, $weather_dependency, $monsoon_risk, $cancellation_chance, $safety_equipment_required, $equipment_provided, $guide_mandatory, $operator_type, $buffer_time_minutes, $reporting_time, $crowd_level, $avg_waiting_time_minutes, $min_age, $max_age, $nearest_medical_distance_km, $emergency_support_level, $stop_conditions, $family_friendly, $solo_traveler_safe, $status, $is_verified, $priority_level);
                if ($stmt->execute()) {
                    $message = "Activity added successfully!";
                    $message_type = "success";
                    logActivity("Add Place Activity", "Added activity to place: " . $place['name']);
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
            $open_time = trim($_POST['open_time'] ?? '');
            $close_time = trim($_POST['close_time'] ?? '');
            $available_months = isset($_POST['available_months']) ? implode(',', $_POST['available_months']) : '';
            
            // Handle ticket price
            $ticket_price = trim($_POST['ticket_price'] ?? '');
            if (empty($ticket_price) || $ticket_price === '0' || strtolower($ticket_price) === 'free') {
                $ticket_price = 'Free';
            }
            
            // Activity Difficulty & Physical Load
            $difficulty_level = trim($_POST['difficulty_level'] ?? '');
            $physical_effort = trim($_POST['physical_effort'] ?? '');
            // Weather & Season Risk
            $weather_dependency = trim($_POST['weather_dependency'] ?? '');
            $monsoon_risk = trim($_POST['monsoon_risk'] ?? '');
            $cancellation_chance = trim($_POST['cancellation_chance'] ?? '');
            // Safety Gear & Requirements
            $safety_equipment_required = trim($_POST['safety_equipment_required'] ?? '');
            $equipment_provided = trim($_POST['equipment_provided'] ?? '');
            // Supervision & Guide Quality
            $guide_mandatory = trim($_POST['guide_mandatory'] ?? '');
            $operator_type = trim($_POST['operator_type'] ?? '');
            // Time Flexibility & Buffer
            $buffer_time_minutes = !empty($_POST['buffer_time_minutes']) ? intval($_POST['buffer_time_minutes']) : null;
            $reporting_time = trim($_POST['reporting_time'] ?? '');
            // Crowd & Waiting Impact
            $crowd_level = trim($_POST['crowd_level'] ?? '');
            $avg_waiting_time_minutes = !empty($_POST['avg_waiting_time_minutes']) ? intval($_POST['avg_waiting_time_minutes']) : null;
            // Age & Body Limitation
            $min_age = !empty($_POST['min_age']) ? intval($_POST['min_age']) : null;
            $max_age = !empty($_POST['max_age']) ? intval($_POST['max_age']) : null;
            // Medical Emergency Access
            $nearest_medical_distance_km = !empty($_POST['nearest_medical_distance_km']) ? floatval($_POST['nearest_medical_distance_km']) : null;
            $emergency_support_level = trim($_POST['emergency_support_level'] ?? '');
            // Activity Stop Conditions
            $stop_conditions = trim($_POST['stop_conditions'] ?? '');
            // Family & Solo Travel Suitability
            $family_friendly = trim($_POST['family_friendly'] ?? '');
            $solo_traveler_safe = trim($_POST['solo_traveler_safe'] ?? '');
            // Status, Verification & Priority
            $status = trim($_POST['status'] ?? 'active');
            $is_verified = trim($_POST['is_verified'] ?? 'no');
            $priority_level = !empty($_POST['priority_level']) ? intval($_POST['priority_level']) : 3;
            
            if ($id > 0) {
                $stmt = $conn->prepare("UPDATE place_activities SET safety_info = ?, safety_level = ?, age_restriction = ?, gender_restriction = ?, medical_restrictions = ?, open_time = ?, close_time = ?, available_months = ?, ticket_price = ?, difficulty_level = ?, physical_effort = ?, weather_dependency = ?, monsoon_risk = ?, cancellation_chance = ?, safety_equipment_required = ?, equipment_provided = ?, guide_mandatory = ?, operator_type = ?, buffer_time_minutes = ?, reporting_time = ?, crowd_level = ?, avg_waiting_time_minutes = ?, min_age = ?, max_age = ?, nearest_medical_distance_km = ?, emergency_support_level = ?, stop_conditions = ?, family_friendly = ?, solo_traveler_safe = ?, status = ?, is_verified = ?, priority_level = ? WHERE id = ?");
                $stmt->bind_param("ssssssssssssssssssssisissidssssssii", $safety_info, $safety_level, $age_restriction, $gender_restriction, $medical_restrictions, $open_time, $close_time, $available_months, $ticket_price, $difficulty_level, $physical_effort, $weather_dependency, $monsoon_risk, $cancellation_chance, $safety_equipment_required, $equipment_provided, $guide_mandatory, $operator_type, $buffer_time_minutes, $reporting_time, $crowd_level, $avg_waiting_time_minutes, $min_age, $max_age, $nearest_medical_distance_km, $emergency_support_level, $stop_conditions, $family_friendly, $solo_traveler_safe, $status, $is_verified, $priority_level, $id);
                if ($stmt->execute()) {
                    $message = "Activity updated successfully!";
                    $message_type = "success";
                    logActivity("Update Place Activity", "Updated activity for place: " . $place['name']);
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
                    $message = "Activity deleted successfully!";
                    $message_type = "success";
                    logActivity("Delete Place Activity", "Deleted activity from place: " . $place['name']);
                } else {
                    $message = "Error deleting activity: " . $conn->error;
                    $message_type = "error";
                }
                $stmt->close();
            }
        }
    }
}

// Get all activity types
$activity_types = [];
$result = $conn->query("SELECT * FROM activity_types ORDER BY name");
if ($result) {
    $activity_types = $result->fetch_all(MYSQLI_ASSOC);
}

// Get all activities for this place
$activities = [];
$result = $conn->query("SELECT pa.*, at.name as activity_name FROM place_activities pa JOIN activity_types at ON pa.activity_type_id = at.id WHERE pa.place_id = $place_id ORDER BY at.name");
if ($result) {
    $activities = $result->fetch_all(MYSQLI_ASSOC);
}

$admin = getAdminInfo();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Activities - <?php echo htmlspecialchars($place['name']); ?> | Admin Panel</title>
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
                    <h1><i class="bi bi-activity"></i> Manage Activities</h1>
                    <p>Place: <strong><?php echo htmlspecialchars($place['name']); ?></strong> - <?php echo htmlspecialchars($place['city_name'] . ', ' . $place['state_name'] . ', ' . $place['country_name']); ?></p>
                </div>
                <div>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addActivityModal">
                        <i class="bi bi-plus-circle"></i> Add New Activity
                    </button>
                    <a href="places.php" class="btn btn-secondary ms-2">
                        <i class="bi bi-arrow-left"></i> Back to Places
                    </a>
                </div>
            </div>

            <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <!-- Activities Table -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-list-ul"></i> Activities List (<?php echo count($activities); ?>)</h5>
                    <div class="search-box">
                        <input type="text" id="searchTable" class="form-control form-control-sm" placeholder="Search activities..." style="width: 250px;">
                    </div>
                </div>
                <div class="card-body">
                    <?php if (count($activities) > 0): ?>
                    <div class="table-responsive">
                        <table class="data-table" id="dataTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Activity</th>
                                    <th>Safety Level</th>
                                    <th>Time</th>
                                    <th>Months</th>
                                    <th>Ticket Price</th>
                                    <th>Age</th>
                                    <th>Gender</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($activities as $activity): ?>
                                <tr>
                                    <td><?php echo $activity['id']; ?></td>
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
                                    <td>
                                        <?php 
                                        $open_time = $activity['open_time'] ?? '';
                                        $close_time = $activity['close_time'] ?? '';
                                        if ($open_time || $close_time) {
                                            echo htmlspecialchars(($open_time ? date('h:i A', strtotime($open_time)) : 'N/A') . ' - ' . ($close_time ? date('h:i A', strtotime($close_time)) : 'N/A'));
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                        $months = $activity['available_months'] ?? '';
                                        if ($months) {
                                            $month_list = explode(',', $months);
                                            echo '<span class="badge bg-secondary">' . count($month_list) . ' months</span>';
                                        } else {
                                            echo '<span class="badge bg-info">All year</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                        $ticket_price = $activity['ticket_price'] ?? 'Free';
                                        if ($ticket_price === 'Free' || empty($ticket_price)) {
                                            echo '<span class="badge bg-success">Free</span>';
                                        } else {
                                            echo '<span class="badge bg-primary">₹' . htmlspecialchars($ticket_price) . '</span>';
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($activity['age_restriction'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($activity['gender_restriction'] ?? '-'); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button type="button" class="btn-icon btn-edit" title="Edit" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($activity)); ?>)">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button type="button" class="btn-icon btn-info" title="View Ticket Details" onclick="openTicketModal(<?php echo $activity['id']; ?>, '<?php echo htmlspecialchars(addslashes($activity['activity_name'])); ?>', '<?php echo htmlspecialchars(addslashes($activity['ticket_price'] ?? 'Free')); ?>', '<?php echo htmlspecialchars(addslashes($activity['open_time'] ?? '')); ?>', '<?php echo htmlspecialchars(addslashes($activity['close_time'] ?? '')); ?>', '<?php echo htmlspecialchars(addslashes($activity['available_months'] ?? '')); ?>', '<?php echo htmlspecialchars(addslashes($activity['age_restriction'] ?? '')); ?>', '<?php echo htmlspecialchars(addslashes($activity['gender_restriction'] ?? '')); ?>', '<?php echo htmlspecialchars(addslashes($activity['safety_info'] ?? '')); ?>', '<?php echo htmlspecialchars(addslashes($activity['medical_restrictions'] ?? '')); ?>')">
                                                <i class="bi bi-ticket"></i>
                                            </button>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this activity?');">
                                                <input type="hidden" name="action" value="delete_activity">
                                                <input type="hidden" name="activity_id" value="<?php echo $activity['id']; ?>">
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
                        <p>No activities found. Add your first activity above.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Activity Modal -->
    <div class="modal fade" id="addActivityModal" tabindex="-1" aria-labelledby="addActivityModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addActivityModalLabel"><i class="bi bi-plus-circle"></i> Add New Activity</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="activityForm">
                    <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                        <input type="hidden" name="action" value="add_activity">
                        <input type="hidden" name="place_id" value="<?php echo $place_id; ?>">
                        
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
                                <label for="activityOpenTime" class="form-label small fw-bold"><i class="bi bi-clock"></i> Open Time</label>
                                <input type="time" class="form-control form-control-sm" id="activityOpenTime" name="open_time">
                            </div>
                            <div class="col-md-2 mb-2">
                                <label for="activityCloseTime" class="form-label small fw-bold"><i class="bi bi-clock-fill"></i> Close Time</label>
                                <input type="time" class="form-control form-control-sm" id="activityCloseTime" name="close_time">
                            </div>
                            <div class="col-md-3 mb-2">
                                <label for="activityAge" class="form-label small fw-bold">Age</label>
                                <input type="text" class="form-control form-control-sm" id="activityAge" name="age_restriction" placeholder="e.g., 18+, All">
                            </div>
                        </div>
                        <div class="row g-2">
                            <div class="col-md-2 mb-2">
                                <label for="activityGender" class="form-label small fw-bold">Gender</label>
                                <select class="form-select form-select-sm" id="activityGender" name="gender_restriction">
                                    <option value="">No restriction</option>
                                    <option value="Male only">Male only</option>
                                    <option value="Female only">Female only</option>
                                    <option value="Mixed">Mixed</option>
                                </select>
                            </div>
                            <div class="col-md-7 mb-2">
                                <label for="activityMedicalRestrictions" class="form-label small fw-bold"><i class="bi bi-heart-pulse"></i> Medical Restrictions</label>
                                <input type="text" class="form-control form-control-sm" id="activityMedicalRestrictions" name="medical_restrictions" placeholder="Diseases/conditions that prevent this activity">
                            </div>
                            <div class="col-md-3 mb-2">
                                <label for="ticketPrice" class="form-label small fw-bold"><i class="bi bi-ticket"></i> Ticket Price</label>
                                <input type="text" class="form-control form-control-sm" id="ticketPrice" name="ticket_price" placeholder="e.g., Free, 100, 100-200" value="Free">
                            </div>
                            <div class="col-12 mb-2">
                                <label class="form-label small fw-bold"><i class="bi bi-calendar-month"></i> Available Months</label>
                                <div class="border rounded p-2">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="selectAllMonths" onchange="toggleAll('month')">
                                        <label class="form-check-label fw-bold small" for="selectAllMonths">Select All</label>
                                    </div>
                                    <hr class="my-2">
                                    <div class="d-flex flex-wrap gap-2">
                                        <?php 
                                        $months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
                                        foreach ($months as $month): 
                                        ?>
                                        <div class="form-check">
                                            <input class="form-check-input month-checkbox" type="checkbox" name="available_months[]" value="<?php echo $month; ?>" id="month_<?php echo strtolower($month); ?>">
                                            <label class="form-check-label small" for="month_<?php echo strtolower($month); ?>"><?php echo $month; ?></label>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
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
                        
                        <!-- Activity Difficulty & Physical Load -->
                        <hr class="my-3">
                        <h6 class="mb-3"><i class="bi bi-speedometer2"></i> Activity Difficulty & Physical Load</h6>
                        <div class="row g-2">
                            <div class="col-md-6 mb-2">
                                <label for="activityDifficultyLevel" class="form-label small fw-bold">Difficulty Level</label>
                                <select class="form-select form-select-sm" id="activityDifficultyLevel" name="difficulty_level">
                                    <option value="">Select</option>
                                    <option value="easy">Easy</option>
                                    <option value="moderate">Moderate</option>
                                    <option value="hard">Hard</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-2">
                                <label for="activityPhysicalEffort" class="form-label small fw-bold">Physical Effort</label>
                                <select class="form-select form-select-sm" id="activityPhysicalEffort" name="physical_effort">
                                    <option value="">Select</option>
                                    <option value="low">Low</option>
                                    <option value="medium">Medium</option>
                                    <option value="high">High</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Weather & Season Risk -->
                        <hr class="my-3">
                        <h6 class="mb-3"><i class="bi bi-cloud-rain"></i> Weather & Season Risk</h6>
                        <div class="row g-2">
                            <div class="col-md-4 mb-2">
                                <label for="activityWeatherDependency" class="form-label small fw-bold">Weather Dependency</label>
                                <select class="form-select form-select-sm" id="activityWeatherDependency" name="weather_dependency">
                                    <option value="">Select</option>
                                    <option value="low">Low</option>
                                    <option value="medium">Medium</option>
                                    <option value="high">High</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-2">
                                <label for="activityMonsoonRisk" class="form-label small fw-bold">Monsoon Risk</label>
                                <select class="form-select form-select-sm" id="activityMonsoonRisk" name="monsoon_risk">
                                    <option value="">Select</option>
                                    <option value="safe">Safe</option>
                                    <option value="risky">Risky</option>
                                    <option value="closed">Closed</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-2">
                                <label for="activityCancellationChance" class="form-label small fw-bold">Cancellation Chance</label>
                                <select class="form-select form-select-sm" id="activityCancellationChance" name="cancellation_chance">
                                    <option value="">Select</option>
                                    <option value="low">Low</option>
                                    <option value="medium">Medium</option>
                                    <option value="high">High</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Safety Gear & Requirements -->
                        <hr class="my-3">
                        <h6 class="mb-3"><i class="bi bi-shield-check"></i> Safety Gear & Requirements</h6>
                        <div class="row g-2">
                            <div class="col-md-6 mb-2">
                                <label for="activitySafetyEquipmentRequired" class="form-label small fw-bold">Safety Equipment Required</label>
                                <select class="form-select form-select-sm" id="activitySafetyEquipmentRequired" name="safety_equipment_required">
                                    <option value="">Select</option>
                                    <option value="yes">Yes</option>
                                    <option value="no">No</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-2">
                                <label for="activityEquipmentProvided" class="form-label small fw-bold">Equipment Provided</label>
                                <select class="form-select form-select-sm" id="activityEquipmentProvided" name="equipment_provided">
                                    <option value="">Select</option>
                                    <option value="yes">Yes</option>
                                    <option value="partial">Partial</option>
                                    <option value="no">No</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Supervision & Guide Quality -->
                        <hr class="my-3">
                        <h6 class="mb-3"><i class="bi bi-person-badge"></i> Supervision & Guide Quality</h6>
                        <div class="row g-2">
                            <div class="col-md-6 mb-2">
                                <label for="activityGuideMandatory" class="form-label small fw-bold">Guide Mandatory</label>
                                <select class="form-select form-select-sm" id="activityGuideMandatory" name="guide_mandatory">
                                    <option value="">Select</option>
                                    <option value="yes">Yes</option>
                                    <option value="no">No</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-2">
                                <label for="activityOperatorType" class="form-label small fw-bold">Operator Type</label>
                                <select class="form-select form-select-sm" id="activityOperatorType" name="operator_type">
                                    <option value="">Select</option>
                                    <option value="government">Government</option>
                                    <option value="private">Private</option>
                                    <option value="local">Local</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Time Flexibility & Buffer -->
                        <hr class="my-3">
                        <h6 class="mb-3"><i class="bi bi-clock-history"></i> Time Flexibility & Buffer</h6>
                        <div class="row g-2">
                            <div class="col-md-6 mb-2">
                                <label for="activityBufferTime" class="form-label small fw-bold">Buffer Time (Minutes)</label>
                                <input type="number" class="form-control form-control-sm" id="activityBufferTime" name="buffer_time_minutes" placeholder="e.g., 15, 30">
                            </div>
                            <div class="col-md-6 mb-2">
                                <label for="activityReportingTime" class="form-label small fw-bold">Reporting Time</label>
                                <input type="time" class="form-control form-control-sm" id="activityReportingTime" name="reporting_time">
                            </div>
                        </div>
                        
                        <!-- Crowd & Waiting Impact -->
                        <hr class="my-3">
                        <h6 class="mb-3"><i class="bi bi-people"></i> Crowd & Waiting Impact</h6>
                        <div class="row g-2">
                            <div class="col-md-6 mb-2">
                                <label for="activityCrowdLevel" class="form-label small fw-bold">Crowd Level</label>
                                <select class="form-select form-select-sm" id="activityCrowdLevel" name="crowd_level">
                                    <option value="">Select</option>
                                    <option value="low">Low</option>
                                    <option value="medium">Medium</option>
                                    <option value="high">High</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-2">
                                <label for="activityWaitingTime" class="form-label small fw-bold">Avg Waiting Time (Minutes)</label>
                                <input type="number" class="form-control form-control-sm" id="activityWaitingTime" name="avg_waiting_time_minutes" placeholder="e.g., 20, 45">
                            </div>
                        </div>
                        
                        <!-- Age & Body Limitation (More Precise) -->
                        <hr class="my-3">
                        <h6 class="mb-3"><i class="bi bi-person"></i> Age & Body Limitation (More Precise)</h6>
                        <div class="row g-2">
                            <div class="col-md-6 mb-2">
                                <label for="activityMinAge" class="form-label small fw-bold">Minimum Age</label>
                                <input type="number" class="form-control form-control-sm" id="activityMinAge" name="min_age" placeholder="e.g., 12, 18">
                            </div>
                            <div class="col-md-6 mb-2">
                                <label for="activityMaxAge" class="form-label small fw-bold">Maximum Age</label>
                                <input type="number" class="form-control form-control-sm" id="activityMaxAge" name="max_age" placeholder="e.g., 60, 80">
                            </div>
                        </div>
                        
                        <!-- Medical Emergency Access -->
                        <hr class="my-3">
                        <h6 class="mb-3"><i class="bi bi-hospital"></i> Medical Emergency Access</h6>
                        <div class="row g-2">
                            <div class="col-md-6 mb-2">
                                <label for="activityMedicalDistance" class="form-label small fw-bold">Nearest Medical Distance (km)</label>
                                <input type="number" step="0.01" class="form-control form-control-sm" id="activityMedicalDistance" name="nearest_medical_distance_km" placeholder="e.g., 5.5, 10.0">
                            </div>
                            <div class="col-md-6 mb-2">
                                <label for="activityEmergencySupport" class="form-label small fw-bold">Emergency Support Level</label>
                                <select class="form-select form-select-sm" id="activityEmergencySupport" name="emergency_support_level">
                                    <option value="">Select</option>
                                    <option value="good">Good</option>
                                    <option value="medium">Medium</option>
                                    <option value="poor">Poor</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Activity Stop Conditions -->
                        <hr class="my-3">
                        <h6 class="mb-3"><i class="bi bi-exclamation-triangle"></i> Activity Stop Conditions</h6>
                        <div class="row g-2">
                            <div class="col-12 mb-2">
                                <label for="activityStopConditions" class="form-label small fw-bold">Stop Conditions</label>
                                <textarea class="form-control form-control-sm" id="activityStopConditions" name="stop_conditions" rows="2" placeholder="e.g., Stop activity during heavy rain or high wind"></textarea>
                                <small class="form-text text-muted">When should users NOT do this activity?</small>
                            </div>
                        </div>
                        
                        <!-- Family & Solo Travel Suitability -->
                        <hr class="my-3">
                        <h6 class="mb-3"><i class="bi bi-people-fill"></i> Family & Solo Travel Suitability</h6>
                        <div class="row g-2">
                            <div class="col-md-6 mb-2">
                                <label for="activityFamilyFriendly" class="form-label small fw-bold">Family Friendly</label>
                                <select class="form-select form-select-sm" id="activityFamilyFriendly" name="family_friendly">
                                    <option value="">Select</option>
                                    <option value="yes">Yes</option>
                                    <option value="no">No</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-2">
                                <label for="activitySoloTravelerSafe" class="form-label small fw-bold">Solo Traveler Safe</label>
                                <select class="form-select form-select-sm" id="activitySoloTravelerSafe" name="solo_traveler_safe">
                                    <option value="">Select</option>
                                    <option value="yes">Yes</option>
                                    <option value="no">No</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Status, Verification & Priority -->
                        <hr class="my-3">
                        <h6 class="mb-3"><i class="bi bi-gear"></i> Status, Verification & Priority</h6>
                        <div class="row g-2">
                            <div class="col-md-4 mb-2">
                                <label for="activityStatus" class="form-label small fw-bold">Status</label>
                                <select class="form-select form-select-sm" id="activityStatus" name="status">
                                    <option value="active" selected>Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-2">
                                <label for="activityIsVerified" class="form-label small fw-bold">Is Verified</label>
                                <select class="form-select form-select-sm" id="activityIsVerified" name="is_verified">
                                    <option value="no" selected>No</option>
                                    <option value="yes">Yes</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-2">
                                <label for="activityPriorityLevel" class="form-label small fw-bold">Priority Level</label>
                                <input type="number" class="form-control form-control-sm" id="activityPriorityLevel" name="priority_level" value="3" placeholder="Default: 3">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Activity Modal -->
    <div class="modal fade" id="editActivityModal" tabindex="-1" aria-labelledby="editActivityModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editActivityModalLabel"><i class="bi bi-pencil"></i> Edit Activity</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="editActivityForm">
                    <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                        <input type="hidden" name="action" value="update_activity">
                        <input type="hidden" name="activity_id" id="editActivityId" value="">
                        <input type="hidden" id="editActivityTypeName" value="">
                        
                        <div class="row g-2">
                            <div class="col-md-2 mb-2">
                                <label for="editActivitySafetyLevel" class="form-label small fw-bold"><i class="bi bi-shield-check"></i> Safety Level <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm" id="editActivitySafetyLevel" name="safety_level" required>
                                    <option value="High">High</option>
                                    <option value="Medium" selected>Medium</option>
                                    <option value="Low">Low</option>
                                    <option value="Worst">Worst</option>
                                </select>
                            </div>
                            <div class="col-md-2 mb-2">
                                <label for="editActivityOpenTime" class="form-label small fw-bold"><i class="bi bi-clock"></i> Open Time</label>
                                <input type="time" class="form-control form-control-sm" id="editActivityOpenTime" name="open_time">
                            </div>
                            <div class="col-md-2 mb-2">
                                <label for="editActivityCloseTime" class="form-label small fw-bold"><i class="bi bi-clock-fill"></i> Close Time</label>
                                <input type="time" class="form-control form-control-sm" id="editActivityCloseTime" name="close_time">
                            </div>
                            <div class="col-md-3 mb-2">
                                <label for="editActivityAge" class="form-label small fw-bold">Age</label>
                                <input type="text" class="form-control form-control-sm" id="editActivityAge" name="age_restriction" placeholder="e.g., 18+, All">
                            </div>
                            <div class="col-md-3 mb-2">
                                <label for="editActivityGender" class="form-label small fw-bold">Gender</label>
                                <select class="form-select form-select-sm" id="editActivityGender" name="gender_restriction">
                                    <option value="">No restriction</option>
                                    <option value="Male only">Male only</option>
                                    <option value="Female only">Female only</option>
                                    <option value="Mixed">Mixed</option>
                                </select>
                            </div>
                        </div>
                        <div class="row g-2">
                            <div class="col-md-7 mb-2">
                                <label for="editActivityMedicalRestrictions" class="form-label small fw-bold"><i class="bi bi-heart-pulse"></i> Medical Restrictions</label>
                                <input type="text" class="form-control form-control-sm" id="editActivityMedicalRestrictions" name="medical_restrictions" placeholder="Diseases/conditions that prevent this activity">
                            </div>
                            <div class="col-md-3 mb-2">
                                <label for="editTicketPrice" class="form-label small fw-bold"><i class="bi bi-ticket"></i> Ticket Price</label>
                                <input type="text" class="form-control form-control-sm" id="editTicketPrice" name="ticket_price" placeholder="e.g., Free, 100, 100-200">
                            </div>
                            <div class="col-12 mb-2">
                                <label class="form-label small fw-bold"><i class="bi bi-calendar-month"></i> Available Months</label>
                                <div class="border rounded p-2">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="editSelectAllMonths" onchange="toggleAll('editMonth')">
                                        <label class="form-check-label fw-bold small" for="editSelectAllMonths">Select All</label>
                                    </div>
                                    <hr class="my-2">
                                    <div class="d-flex flex-wrap gap-2">
                                        <?php 
                                        $months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
                                        foreach ($months as $month): 
                                        ?>
                                        <div class="form-check">
                                            <input class="form-check-input edit-month-checkbox" type="checkbox" name="available_months[]" value="<?php echo $month; ?>" id="edit_month_<?php echo strtolower($month); ?>">
                                            <label class="form-check-label small" for="edit_month_<?php echo strtolower($month); ?>"><?php echo $month; ?></label>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row g-2">
                            <div class="col-md-10 mb-2">
                                <label for="editActivitySafetyInfo" class="form-label small fw-bold">Safety Information</label>
                                <textarea class="form-control form-control-sm" id="editActivitySafetyInfo" name="safety_info" rows="2" placeholder="Safety guidelines and precautions..."></textarea>
                            </div>
                            <div class="col-md-2 mb-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary btn-sm w-100">
                                    <i class="bi bi-check-circle"></i> Update
                                </button>
                            </div>
                        </div>
                        
                        <!-- Activity Difficulty & Physical Load -->
                        <hr class="my-3">
                        <h6 class="mb-3"><i class="bi bi-speedometer2"></i> Activity Difficulty & Physical Load</h6>
                        <div class="row g-2">
                            <div class="col-md-6 mb-2">
                                <label for="editActivityDifficultyLevel" class="form-label small fw-bold">Difficulty Level</label>
                                <select class="form-select form-select-sm" id="editActivityDifficultyLevel" name="difficulty_level">
                                    <option value="">Select</option>
                                    <option value="easy">Easy</option>
                                    <option value="moderate">Moderate</option>
                                    <option value="hard">Hard</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-2">
                                <label for="editActivityPhysicalEffort" class="form-label small fw-bold">Physical Effort</label>
                                <select class="form-select form-select-sm" id="editActivityPhysicalEffort" name="physical_effort">
                                    <option value="">Select</option>
                                    <option value="low">Low</option>
                                    <option value="medium">Medium</option>
                                    <option value="high">High</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Weather & Season Risk -->
                        <hr class="my-3">
                        <h6 class="mb-3"><i class="bi bi-cloud-rain"></i> Weather & Season Risk</h6>
                        <div class="row g-2">
                            <div class="col-md-4 mb-2">
                                <label for="editActivityWeatherDependency" class="form-label small fw-bold">Weather Dependency</label>
                                <select class="form-select form-select-sm" id="editActivityWeatherDependency" name="weather_dependency">
                                    <option value="">Select</option>
                                    <option value="low">Low</option>
                                    <option value="medium">Medium</option>
                                    <option value="high">High</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-2">
                                <label for="editActivityMonsoonRisk" class="form-label small fw-bold">Monsoon Risk</label>
                                <select class="form-select form-select-sm" id="editActivityMonsoonRisk" name="monsoon_risk">
                                    <option value="">Select</option>
                                    <option value="safe">Safe</option>
                                    <option value="risky">Risky</option>
                                    <option value="closed">Closed</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-2">
                                <label for="editActivityCancellationChance" class="form-label small fw-bold">Cancellation Chance</label>
                                <select class="form-select form-select-sm" id="editActivityCancellationChance" name="cancellation_chance">
                                    <option value="">Select</option>
                                    <option value="low">Low</option>
                                    <option value="medium">Medium</option>
                                    <option value="high">High</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Safety Gear & Requirements -->
                        <hr class="my-3">
                        <h6 class="mb-3"><i class="bi bi-shield-check"></i> Safety Gear & Requirements</h6>
                        <div class="row g-2">
                            <div class="col-md-6 mb-2">
                                <label for="editActivitySafetyEquipmentRequired" class="form-label small fw-bold">Safety Equipment Required</label>
                                <select class="form-select form-select-sm" id="editActivitySafetyEquipmentRequired" name="safety_equipment_required">
                                    <option value="">Select</option>
                                    <option value="yes">Yes</option>
                                    <option value="no">No</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-2">
                                <label for="editActivityEquipmentProvided" class="form-label small fw-bold">Equipment Provided</label>
                                <select class="form-select form-select-sm" id="editActivityEquipmentProvided" name="equipment_provided">
                                    <option value="">Select</option>
                                    <option value="yes">Yes</option>
                                    <option value="partial">Partial</option>
                                    <option value="no">No</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Supervision & Guide Quality -->
                        <hr class="my-3">
                        <h6 class="mb-3"><i class="bi bi-person-badge"></i> Supervision & Guide Quality</h6>
                        <div class="row g-2">
                            <div class="col-md-6 mb-2">
                                <label for="editActivityGuideMandatory" class="form-label small fw-bold">Guide Mandatory</label>
                                <select class="form-select form-select-sm" id="editActivityGuideMandatory" name="guide_mandatory">
                                    <option value="">Select</option>
                                    <option value="yes">Yes</option>
                                    <option value="no">No</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-2">
                                <label for="editActivityOperatorType" class="form-label small fw-bold">Operator Type</label>
                                <select class="form-select form-select-sm" id="editActivityOperatorType" name="operator_type">
                                    <option value="">Select</option>
                                    <option value="government">Government</option>
                                    <option value="private">Private</option>
                                    <option value="local">Local</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Time Flexibility & Buffer -->
                        <hr class="my-3">
                        <h6 class="mb-3"><i class="bi bi-clock-history"></i> Time Flexibility & Buffer</h6>
                        <div class="row g-2">
                            <div class="col-md-6 mb-2">
                                <label for="editActivityBufferTime" class="form-label small fw-bold">Buffer Time (Minutes)</label>
                                <input type="number" class="form-control form-control-sm" id="editActivityBufferTime" name="buffer_time_minutes" placeholder="e.g., 15, 30">
                            </div>
                            <div class="col-md-6 mb-2">
                                <label for="editActivityReportingTime" class="form-label small fw-bold">Reporting Time</label>
                                <input type="time" class="form-control form-control-sm" id="editActivityReportingTime" name="reporting_time">
                            </div>
                        </div>
                        
                        <!-- Crowd & Waiting Impact -->
                        <hr class="my-3">
                        <h6 class="mb-3"><i class="bi bi-people"></i> Crowd & Waiting Impact</h6>
                        <div class="row g-2">
                            <div class="col-md-6 mb-2">
                                <label for="editActivityCrowdLevel" class="form-label small fw-bold">Crowd Level</label>
                                <select class="form-select form-select-sm" id="editActivityCrowdLevel" name="crowd_level">
                                    <option value="">Select</option>
                                    <option value="low">Low</option>
                                    <option value="medium">Medium</option>
                                    <option value="high">High</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-2">
                                <label for="editActivityWaitingTime" class="form-label small fw-bold">Avg Waiting Time (Minutes)</label>
                                <input type="number" class="form-control form-control-sm" id="editActivityWaitingTime" name="avg_waiting_time_minutes" placeholder="e.g., 20, 45">
                            </div>
                        </div>
                        
                        <!-- Age & Body Limitation (More Precise) -->
                        <hr class="my-3">
                        <h6 class="mb-3"><i class="bi bi-person"></i> Age & Body Limitation (More Precise)</h6>
                        <div class="row g-2">
                            <div class="col-md-6 mb-2">
                                <label for="editActivityMinAge" class="form-label small fw-bold">Minimum Age</label>
                                <input type="number" class="form-control form-control-sm" id="editActivityMinAge" name="min_age" placeholder="e.g., 12, 18">
                            </div>
                            <div class="col-md-6 mb-2">
                                <label for="editActivityMaxAge" class="form-label small fw-bold">Maximum Age</label>
                                <input type="number" class="form-control form-control-sm" id="editActivityMaxAge" name="max_age" placeholder="e.g., 60, 80">
                            </div>
                        </div>
                        
                        <!-- Medical Emergency Access -->
                        <hr class="my-3">
                        <h6 class="mb-3"><i class="bi bi-hospital"></i> Medical Emergency Access</h6>
                        <div class="row g-2">
                            <div class="col-md-6 mb-2">
                                <label for="editActivityMedicalDistance" class="form-label small fw-bold">Nearest Medical Distance (km)</label>
                                <input type="number" step="0.01" class="form-control form-control-sm" id="editActivityMedicalDistance" name="nearest_medical_distance_km" placeholder="e.g., 5.5, 10.0">
                            </div>
                            <div class="col-md-6 mb-2">
                                <label for="editActivityEmergencySupport" class="form-label small fw-bold">Emergency Support Level</label>
                                <select class="form-select form-select-sm" id="editActivityEmergencySupport" name="emergency_support_level">
                                    <option value="">Select</option>
                                    <option value="good">Good</option>
                                    <option value="medium">Medium</option>
                                    <option value="poor">Poor</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Activity Stop Conditions -->
                        <hr class="my-3">
                        <h6 class="mb-3"><i class="bi bi-exclamation-triangle"></i> Activity Stop Conditions</h6>
                        <div class="row g-2">
                            <div class="col-12 mb-2">
                                <label for="editActivityStopConditions" class="form-label small fw-bold">Stop Conditions</label>
                                <textarea class="form-control form-control-sm" id="editActivityStopConditions" name="stop_conditions" rows="2" placeholder="e.g., Stop activity during heavy rain or high wind"></textarea>
                                <small class="form-text text-muted">When should users NOT do this activity?</small>
                            </div>
                        </div>
                        
                        <!-- Family & Solo Travel Suitability -->
                        <hr class="my-3">
                        <h6 class="mb-3"><i class="bi bi-people-fill"></i> Family & Solo Travel Suitability</h6>
                        <div class="row g-2">
                            <div class="col-md-6 mb-2">
                                <label for="editActivityFamilyFriendly" class="form-label small fw-bold">Family Friendly</label>
                                <select class="form-select form-select-sm" id="editActivityFamilyFriendly" name="family_friendly">
                                    <option value="">Select</option>
                                    <option value="yes">Yes</option>
                                    <option value="no">No</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-2">
                                <label for="editActivitySoloTravelerSafe" class="form-label small fw-bold">Solo Traveler Safe</label>
                                <select class="form-select form-select-sm" id="editActivitySoloTravelerSafe" name="solo_traveler_safe">
                                    <option value="">Select</option>
                                    <option value="yes">Yes</option>
                                    <option value="no">No</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Status, Verification & Priority -->
                        <hr class="my-3">
                        <h6 class="mb-3"><i class="bi bi-gear"></i> Status, Verification & Priority</h6>
                        <div class="row g-2">
                            <div class="col-md-4 mb-2">
                                <label for="editActivityStatus" class="form-label small fw-bold">Status</label>
                                <select class="form-select form-select-sm" id="editActivityStatus" name="status">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-2">
                                <label for="editActivityIsVerified" class="form-label small fw-bold">Is Verified</label>
                                <select class="form-select form-select-sm" id="editActivityIsVerified" name="is_verified">
                                    <option value="no">No</option>
                                    <option value="yes">Yes</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-2">
                                <label for="editActivityPriorityLevel" class="form-label small fw-bold">Priority Level</label>
                                <input type="number" class="form-control form-control-sm" id="editActivityPriorityLevel" name="priority_level" value="3" placeholder="Default: 3">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Ticket Details Modal -->
    <div class="modal fade" id="ticketModal" tabindex="-1" aria-labelledby="ticketModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="ticketModalLabel"><i class="bi bi-ticket-perforated"></i> Ticket Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="card border-0">
                        <div class="card-body">
                            <h5 class="card-title mb-4" id="ticketActivityName"></h5>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <tbody>
                                        <tr>
                                            <th class="bg-light" style="width: 40%;"><i class="bi bi-currency-rupee"></i> Ticket Price</th>
                                            <td id="ticketPrice" class="fw-bold"></td>
                                        </tr>
                                        <tr>
                                            <th class="bg-light"><i class="bi bi-clock"></i> Opening Time</th>
                                            <td id="ticketOpenTime"></td>
                                        </tr>
                                        <tr>
                                            <th class="bg-light"><i class="bi bi-clock-fill"></i> Closing Time</th>
                                            <td id="ticketCloseTime"></td>
                                        </tr>
                                        <tr>
                                            <th class="bg-light"><i class="bi bi-calendar-month"></i> Available Months</th>
                                            <td id="ticketMonths"></td>
                                        </tr>
                                        <tr>
                                            <th class="bg-light"><i class="bi bi-person"></i> Age Restriction</th>
                                            <td id="ticketAge"></td>
                                        </tr>
                                        <tr>
                                            <th class="bg-light"><i class="bi bi-gender-ambiguous"></i> Gender Restriction</th>
                                            <td id="ticketGender"></td>
                                        </tr>
                                        <tr>
                                            <th class="bg-light"><i class="bi bi-shield-check"></i> Safety Information</th>
                                            <td id="ticketSafetyInfo"></td>
                                        </tr>
                                        <tr>
                                            <th class="bg-light"><i class="bi bi-heart-pulse"></i> Medical Restrictions</th>
                                            <td id="ticketMedicalRestrictions"></td>
                                        </tr>
                                    </tbody>
                                </table>
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

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        // Initialize Select2 for Add Modal
        $(document).ready(function() {
            // Initialize Select2 when add modal is shown
            $('#addActivityModal').on('shown.bs.modal', function() {
                if (!$('#activityType').hasClass('select2-hidden-accessible')) {
                    $('#activityType').select2({
                        theme: 'bootstrap-5',
                        width: '100%',
                        allowClear: true,
                        dropdownParent: $('#addActivityModal')
                    });
                    
                    // Auto-focus search when dropdown opens
                    $('#activityType').on('select2:open', function() {
                        setTimeout(function() {
                            $('.select2-search__field').focus();
                        }, 50);
                    });
                }
            });
            
            // Destroy Select2 and reset form when modal is hidden
            $('#addActivityModal').on('hidden.bs.modal', function() {
                if ($('#activityType').hasClass('select2-hidden-accessible')) {
                    $('#activityType').select2('destroy');
                }
                // Reset form
                document.getElementById('activityForm').reset();
                $('#ticketPrice').val('Free');
                $('.month-checkbox').prop('checked', false);
                $('#selectAllMonths').prop('checked', false);
            });
        });

        function openEditModal(activity) {
            // Basic fields
            document.getElementById('editActivityId').value = activity.id || '';
            document.getElementById('editActivityTypeName').value = activity.activity_name || '';
            document.getElementById('editActivitySafetyInfo').value = activity.safety_info || '';
            document.getElementById('editActivitySafetyLevel').value = activity.safety_level || 'Medium';
            document.getElementById('editActivityOpenTime').value = activity.open_time || '';
            document.getElementById('editActivityCloseTime').value = activity.close_time || '';
            document.getElementById('editActivityAge').value = activity.age_restriction || '';
            document.getElementById('editActivityGender').value = activity.gender_restriction || '';
            document.getElementById('editActivityMedicalRestrictions').value = activity.medical_restrictions || '';
            document.getElementById('editTicketPrice').value = activity.ticket_price || 'Free';
            
            // Activity Difficulty & Physical Load
            document.getElementById('editActivityDifficultyLevel').value = activity.difficulty_level || '';
            document.getElementById('editActivityPhysicalEffort').value = activity.physical_effort || '';
            
            // Weather & Season Risk
            document.getElementById('editActivityWeatherDependency').value = activity.weather_dependency || '';
            document.getElementById('editActivityMonsoonRisk').value = activity.monsoon_risk || '';
            document.getElementById('editActivityCancellationChance').value = activity.cancellation_chance || '';
            
            // Safety Gear & Requirements
            document.getElementById('editActivitySafetyEquipmentRequired').value = activity.safety_equipment_required || '';
            document.getElementById('editActivityEquipmentProvided').value = activity.equipment_provided || '';
            
            // Supervision & Guide Quality
            document.getElementById('editActivityGuideMandatory').value = activity.guide_mandatory || '';
            document.getElementById('editActivityOperatorType').value = activity.operator_type || '';
            
            // Time Flexibility & Buffer
            document.getElementById('editActivityBufferTime').value = activity.buffer_time_minutes || '';
            document.getElementById('editActivityReportingTime').value = activity.reporting_time || '';
            
            // Crowd & Waiting Impact
            document.getElementById('editActivityCrowdLevel').value = activity.crowd_level || '';
            document.getElementById('editActivityWaitingTime').value = activity.avg_waiting_time_minutes || '';
            
            // Age & Body Limitation
            document.getElementById('editActivityMinAge').value = activity.min_age || '';
            document.getElementById('editActivityMaxAge').value = activity.max_age || '';
            
            // Medical Emergency Access
            document.getElementById('editActivityMedicalDistance').value = activity.nearest_medical_distance_km || '';
            document.getElementById('editActivityEmergencySupport').value = activity.emergency_support_level || '';
            
            // Activity Stop Conditions
            document.getElementById('editActivityStopConditions').value = activity.stop_conditions || '';
            
            // Family & Solo Travel Suitability
            document.getElementById('editActivityFamilyFriendly').value = activity.family_friendly || '';
            document.getElementById('editActivitySoloTravelerSafe').value = activity.solo_traveler_safe || '';
            
            // Status, Verification & Priority
            document.getElementById('editActivityStatus').value = activity.status || 'active';
            document.getElementById('editActivityIsVerified').value = activity.is_verified || 'no';
            document.getElementById('editActivityPriorityLevel').value = activity.priority_level || '3';
            
            // Set available months
            $('.edit-month-checkbox').prop('checked', false);
            $('#editSelectAllMonths').prop('checked', false);
            if (activity.available_months && activity.available_months.trim() !== '') {
                var months = activity.available_months.split(',');
                months.forEach(function(month) {
                    month = month.trim();
                    if (month) {
                        $('#edit_month_' + month.toLowerCase()).prop('checked', true);
                    }
                });
                // Check if all are selected
                var totalMonths = $('.edit-month-checkbox').length;
                var checkedMonths = $('.edit-month-checkbox:checked').length;
                if (checkedMonths === totalMonths && totalMonths > 0) {
                    $('#editSelectAllMonths').prop('checked', true);
                }
            } else {
                // Select all if no months specified (all year)
                $('.edit-month-checkbox').prop('checked', true);
                $('#editSelectAllMonths').prop('checked', true);
            }
            
            var modal = new bootstrap.Modal(document.getElementById('editActivityModal'));
            modal.show();
        }
        
        function openTicketModal(activityId, activityName, ticketPrice, openTime, closeTime, availableMonths, ageRestriction, genderRestriction, safetyInfo, medicalRestrictions) {
            document.getElementById('ticketActivityName').textContent = activityName;
            
            // Ticket Price
            if (ticketPrice === 'Free' || !ticketPrice || ticketPrice.trim() === '') {
                document.getElementById('ticketPrice').innerHTML = '<span class="badge bg-success fs-6">Free</span>';
            } else {
                document.getElementById('ticketPrice').innerHTML = '<span class="badge bg-primary fs-6">₹' + ticketPrice + '</span>';
            }
            
            // Times
            document.getElementById('ticketOpenTime').textContent = openTime ? new Date('2000-01-01 ' + openTime).toLocaleTimeString('en-US', {hour: '2-digit', minute: '2-digit', hour12: true}) : 'Not specified';
            document.getElementById('ticketCloseTime').textContent = closeTime ? new Date('2000-01-01 ' + closeTime).toLocaleTimeString('en-US', {hour: '2-digit', minute: '2-digit', hour12: true}) : 'Not specified';
            
            // Available Months
            if (availableMonths && availableMonths.trim() !== '') {
                var months = availableMonths.split(',');
                var monthBadges = months.map(function(m) {
                    return '<span class="badge bg-secondary me-1">' + m.trim() + '</span>';
                }).join('');
                document.getElementById('ticketMonths').innerHTML = monthBadges;
            } else {
                document.getElementById('ticketMonths').innerHTML = '<span class="badge bg-info">All year round</span>';
            }
            
            // Other details
            document.getElementById('ticketAge').textContent = ageRestriction || 'No restriction';
            document.getElementById('ticketGender').textContent = genderRestriction || 'No restriction';
            document.getElementById('ticketSafetyInfo').textContent = safetyInfo || 'Not specified';
            document.getElementById('ticketMedicalRestrictions').textContent = medicalRestrictions || 'None';
            
            var modal = new bootstrap.Modal(document.getElementById('ticketModal'));
            modal.show();
        }
        
        // Toggle all months function
        function toggleAll(type) {
            if (type === 'month') {
                var isChecked = $('#selectAllMonths').prop('checked');
                $('.month-checkbox').prop('checked', isChecked);
            } else if (type === 'editMonth') {
                var isChecked = $('#editSelectAllMonths').prop('checked');
                $('.edit-month-checkbox').prop('checked', isChecked);
            }
        }
        
        // Update select all checkbox when individual checkboxes change
        $(document).on('change', '.month-checkbox', function() {
            var total = $('.month-checkbox').length;
            var checked = $('.month-checkbox:checked').length;
            $('#selectAllMonths').prop('checked', total === checked && total > 0);
        });
        
        $(document).on('change', '.edit-month-checkbox', function() {
            var total = $('.edit-month-checkbox').length;
            var checked = $('.edit-month-checkbox:checked').length;
            $('#editSelectAllMonths').prop('checked', total === checked && total > 0);
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

