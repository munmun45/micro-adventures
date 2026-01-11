<?php
require_once 'config.php';
requireLogin();

$conn = getDBConnection();
$message = '';
$message_type = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $image = trim($_POST['image'] ?? '');
            $status = trim($_POST['status'] ?? 'active');
            
            if (!empty($name)) {
                $stmt = $conn->prepare("INSERT INTO activity_types (name, description, image, status) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $name, $description, $image, $status);
                if ($stmt->execute()) {
                    $message = "Activity type added successfully!";
                    $message_type = "success";
                    logActivity("Add Activity Type", "Added activity: $name");
                } else {
                    $message = "Error adding activity type: " . $conn->error;
                    $message_type = "error";
                }
                $stmt->close();
            } else {
                $message = "Activity name is required!";
                $message_type = "error";
            }
        } elseif ($_POST['action'] === 'edit') {
            $id = intval($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $image = trim($_POST['image'] ?? '');
            $status = trim($_POST['status'] ?? 'active');
            
            if ($id > 0 && !empty($name)) {
                $stmt = $conn->prepare("UPDATE activity_types SET name = ?, description = ?, image = ?, status = ? WHERE id = ?");
                $stmt->bind_param("ssssi", $name, $description, $image, $status, $id);
                if ($stmt->execute()) {
                    $message = "Activity type updated successfully!";
                    $message_type = "success";
                    logActivity("Update Activity Type", "Updated activity: $name");
                } else {
                    $message = "Error updating activity type: " . $conn->error;
                    $message_type = "error";
                }
                $stmt->close();
            }
        } elseif ($_POST['action'] === 'delete') {
            $id = intval($_POST['id'] ?? 0);
            if ($id > 0) {
                $stmt = $conn->prepare("DELETE FROM activity_types WHERE id = ?");
                $stmt->bind_param("i", $id);
                if ($stmt->execute()) {
                    $message = "Activity type deleted successfully!";
                    $message_type = "success";
                    logActivity("Delete Activity Type", "Deleted activity ID: $id");
                } else {
                    $message = "Error deleting activity type: " . $conn->error;
                    $message_type = "error";
                }
                $stmt->close();
            }
        }
    }
}

// Get all activity types
$activities = [];
$result = $conn->query("SELECT * FROM activity_types ORDER BY name");
if ($result) {
    $activities = $result->fetch_all(MYSQLI_ASSOC);
}

// Get activity for editing
$edit_activity = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $result = $conn->query("SELECT * FROM activity_types WHERE id = $edit_id");
    if ($result && $result->num_rows > 0) {
        $edit_activity = $result->fetch_assoc();
    }
}

$admin = getAdminInfo();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Activity Types | Admin Panel</title>
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
                    <h1><i class="bi bi-activity"></i> Manage Activity Types</h1>
                    <p>Add, edit, or delete activity types</p>
                </div>
                <div>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#activityModal" onclick="openAddModal()">
                        <i class="bi bi-plus-circle"></i> Add New Activity Type
                    </button>
                </div>
            </div>

            <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <!-- Activities List -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2><i class="bi bi-list-ul"></i> All Activity Types (<?php echo count($activities); ?>)</h2>
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
                                    <th>Image</th>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($activities as $activity): ?>
                                <tr>
                                    <td><?php echo $activity['id']; ?></td>
                                    <td>
                                        <?php if ($activity['image']): ?>
                                        <img src="<?php echo htmlspecialchars($activity['image']); ?>" alt="<?php echo htmlspecialchars($activity['name']); ?>" class="table-image">
                                        <?php else: ?>
                                        <span class="text-gray-400">No image</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?php echo htmlspecialchars($activity['name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars(substr($activity['description'] ?? '', 0, 100)); ?><?php echo strlen($activity['description'] ?? '') > 100 ? '...' : ''; ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button type="button" class="btn-icon btn-edit" title="Edit" onclick="openEditModal(<?php echo $activity['id']; ?>, '<?php echo htmlspecialchars(addslashes($activity['name'])); ?>', '<?php echo htmlspecialchars(addslashes($activity['description'] ?? '')); ?>', '<?php echo htmlspecialchars(addslashes($activity['image'] ?? '')); ?>', '<?php echo htmlspecialchars(addslashes($activity['status'] ?? 'active')); ?>')">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this activity type?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $activity['id']; ?>">
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
                        <p>No activity types found. Add your first activity type above.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Activity Modal -->
    <div class="modal fade" id="activityModal" tabindex="-1" aria-labelledby="activityModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="activityModalLabel"><i class="bi bi-activity"></i> <span id="modalTitle">Add Activity Type</span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="activityForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" id="formAction" value="add">
                        <input type="hidden" name="id" id="formId" value="">
                        
                        <div class="mb-3">
                            <label for="activityName" class="form-label">Activity Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="activityName" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="activityDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="activityDescription" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="activityImage" class="form-label">Image URL</label>
                            <input type="url" class="form-control" id="activityImage" name="image" placeholder="https://example.com/image.jpg">
                        </div>
                        
                        <div class="mb-3">
                            <label for="activityStatus" class="form-label">Status</label>
                            <select class="form-select" id="activityStatus" name="status">
                                <option value="active" selected>Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> <span id="submitText">Add Activity</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Add Activity Type';
            document.getElementById('submitText').textContent = 'Add Activity';
            document.getElementById('formAction').value = 'add';
            document.getElementById('formId').value = '';
            document.getElementById('activityForm').reset();
        }

        function openEditModal(id, name, description, image, status) {
            document.getElementById('modalTitle').textContent = 'Edit Activity Type';
            document.getElementById('submitText').textContent = 'Update Activity';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('formId').value = id;
            document.getElementById('activityName').value = name;
            document.getElementById('activityDescription').value = description;
            document.getElementById('activityImage').value = image;
            document.getElementById('activityStatus').value = status || 'active';
            
            var modal = new bootstrap.Modal(document.getElementById('activityModal'));
            modal.show();
        }

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

