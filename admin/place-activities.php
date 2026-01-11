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
            
            if ($activity_type_id > 0) {
                $stmt = $conn->prepare("INSERT INTO place_activities (place_id, activity_type_id, safety_info, safety_level, age_restriction, gender_restriction, medical_restrictions, open_time, close_time, available_months, ticket_price) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iisssssssss", $place_id, $activity_type_id, $safety_info, $safety_level, $age_restriction, $gender_restriction, $medical_restrictions, $open_time, $close_time, $available_months, $ticket_price);
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
            
            if ($id > 0) {
                $stmt = $conn->prepare("UPDATE place_activities SET safety_info = ?, safety_level = ?, age_restriction = ?, gender_restriction = ?, medical_restrictions = ?, open_time = ?, close_time = ?, available_months = ?, ticket_price = ? WHERE id = ?");
                $stmt->bind_param("sssssssssi", $safety_info, $safety_level, $age_restriction, $gender_restriction, $medical_restrictions, $open_time, $close_time, $available_months, $ticket_price, $id);
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
                                            <button type="button" class="btn-icon btn-edit" title="Edit" onclick="openEditModal(<?php echo $activity['id']; ?>, '<?php echo htmlspecialchars(addslashes($activity['activity_name'])); ?>', '<?php echo htmlspecialchars(addslashes($activity['safety_info'] ?? '')); ?>', '<?php echo htmlspecialchars(addslashes($activity['safety_level'] ?? 'Medium')); ?>', '<?php echo htmlspecialchars(addslashes($activity['open_time'] ?? '')); ?>', '<?php echo htmlspecialchars(addslashes($activity['close_time'] ?? '')); ?>', '<?php echo htmlspecialchars(addslashes($activity['available_months'] ?? '')); ?>', '<?php echo htmlspecialchars(addslashes($activity['ticket_price'] ?? 'Free')); ?>', '<?php echo htmlspecialchars(addslashes($activity['age_restriction'] ?? '')); ?>', '<?php echo htmlspecialchars(addslashes($activity['gender_restriction'] ?? '')); ?>', '<?php echo htmlspecialchars(addslashes($activity['medical_restrictions'] ?? '')); ?>')">
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

        function openEditModal(activityId, activityName, safetyInfo, safetyLevel, openTime, closeTime, availableMonths, ticketPrice, ageRestriction, genderRestriction, medicalRestrictions) {
            document.getElementById('editActivityId').value = activityId;
            document.getElementById('editActivityTypeName').value = activityName;
            document.getElementById('editActivitySafetyInfo').value = safetyInfo || '';
            document.getElementById('editActivitySafetyLevel').value = safetyLevel || 'Medium';
            document.getElementById('editActivityOpenTime').value = openTime || '';
            document.getElementById('editActivityCloseTime').value = closeTime || '';
            document.getElementById('editActivityAge').value = ageRestriction || '';
            document.getElementById('editActivityGender').value = genderRestriction || '';
            document.getElementById('editActivityMedicalRestrictions').value = medicalRestrictions || '';
            
            // Set available months
            $('.edit-month-checkbox').prop('checked', false);
            $('#editSelectAllMonths').prop('checked', false);
            if (availableMonths && availableMonths.trim() !== '') {
                var months = availableMonths.split(',');
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
            
            // Set ticket price
            document.getElementById('editTicketPrice').value = ticketPrice || 'Free';
            
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

