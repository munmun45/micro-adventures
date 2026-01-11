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
            $state_id = intval($_POST['state_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $image = trim($_POST['image'] ?? '');
            $latitude = !empty($_POST['latitude']) ? floatval($_POST['latitude']) : null;
            $longitude = !empty($_POST['longitude']) ? floatval($_POST['longitude']) : null;
            $road_type = trim($_POST['road_type'] ?? 'plain');
            
            if (!empty($name) && $state_id > 0) {
                $stmt = $conn->prepare("INSERT INTO districts (state_id, name, description, image, latitude, longitude, road_type) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isssddss", $state_id, $name, $description, $image, $latitude, $longitude, $road_type);
                if ($stmt->execute()) {
                    $message = "City added successfully!";
                    $message_type = "success";
                    logActivity("Add City", "Added city: $name");
                } else {
                    $message = "Error adding city: " . $conn->error;
                    $message_type = "error";
                }
                $stmt->close();
            } else {
                $message = "City name and state are required!";
                $message_type = "error";
            }
        } elseif ($_POST['action'] === 'edit') {
            $id = intval($_POST['id'] ?? 0);
            $state_id = intval($_POST['state_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $image = trim($_POST['image'] ?? '');
            $latitude = !empty($_POST['latitude']) ? floatval($_POST['latitude']) : null;
            $longitude = !empty($_POST['longitude']) ? floatval($_POST['longitude']) : null;
            $road_type = trim($_POST['road_type'] ?? 'plain');
            
            if ($id > 0 && !empty($name) && $state_id > 0) {
                $stmt = $conn->prepare("UPDATE districts SET state_id = ?, name = ?, description = ?, image = ?, latitude = ?, longitude = ?, road_type = ? WHERE id = ?");
                $stmt->bind_param("isssddssi", $state_id, $name, $description, $image, $latitude, $longitude, $road_type, $id);
                if ($stmt->execute()) {
                    $message = "City updated successfully!";
                    $message_type = "success";
                    logActivity("Update City", "Updated city: $name");
                } else {
                    $message = "Error updating city: " . $conn->error;
                    $message_type = "error";
                }
                $stmt->close();
            }
        } elseif ($_POST['action'] === 'delete') {
            $id = intval($_POST['id'] ?? 0);
            if ($id > 0) {
                $stmt = $conn->prepare("DELETE FROM districts WHERE id = ?");
                $stmt->bind_param("i", $id);
                if ($stmt->execute()) {
                    $message = "City deleted successfully!";
                    $message_type = "success";
                    logActivity("Delete City", "Deleted city ID: $id");
                } else {
                    $message = "Error deleting city: " . $conn->error;
                    $message_type = "error";
                }
                $stmt->close();
            }
        }
    }
}

// Get all states for dropdown
$states = [];
$result = $conn->query("SELECT s.*, c.name as country_name FROM states s JOIN countries c ON s.country_id = c.id ORDER BY c.name, s.name");
if ($result) {
    $states = $result->fetch_all(MYSQLI_ASSOC);
}

// Get all cities with state and country name
$districts = [];
$result = $conn->query("SELECT d.*, s.name as state_name, c.name as country_name FROM districts d JOIN states s ON d.state_id = s.id JOIN countries c ON s.country_id = c.id ORDER BY c.name, s.name, d.name");
if ($result) {
    $districts = $result->fetch_all(MYSQLI_ASSOC);
}

// Get city for editing
$edit_district = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $result = $conn->query("SELECT * FROM districts WHERE id = $edit_id");
    if ($result && $result->num_rows > 0) {
        $edit_district = $result->fetch_assoc();
    }
}

$admin = getAdminInfo();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Cities | Admin Panel</title>
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
                    <h1><i class="bi bi-geo"></i> Manage Cities</h1>
                    <p>Add, edit, or delete cities</p>
                </div>
                <div>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#districtModal" onclick="openAddModal()">
                        <i class="bi bi-plus-circle"></i> Add New City
                    </button>
                </div>
            </div>

            <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <!-- Cities List -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2><i class="bi bi-list-ul"></i> All Cities (<?php echo count($districts); ?>)</h2>
                    <div class="search-box">
                        <input type="text" id="searchTable" class="form-control form-control-sm" placeholder="Search cities..." style="width: 250px;">
                    </div>
                </div>
                <div class="card-body">
                    <?php if (count($districts) > 0): ?>
                    <div class="table-responsive">
                        <table class="data-table" id="dataTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Image</th>
                                    <th>Name</th>
                                    <th>State</th>
                                    <th>Country</th>
                                    <th>Description</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($districts as $district): ?>
                                <tr>
                                    <td><?php echo $district['id']; ?></td>
                                    <td>
                                        <?php if ($district['image']): ?>
                                        <img src="<?php echo htmlspecialchars($district['image']); ?>" alt="<?php echo htmlspecialchars($district['name']); ?>" class="table-image">
                                        <?php else: ?>
                                        <span class="text-gray-400">No image</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?php echo htmlspecialchars($district['name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($district['state_name']); ?></td>
                                    <td><?php echo htmlspecialchars($district['country_name']); ?></td>
                                    <td><?php echo htmlspecialchars(substr($district['description'] ?? '', 0, 100)); ?><?php echo strlen($district['description'] ?? '') > 100 ? '...' : ''; ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button type="button" class="btn-icon btn-edit" title="Edit" onclick="openEditModal(<?php echo $district['id']; ?>, <?php echo $district['state_id']; ?>, '<?php echo htmlspecialchars(addslashes($district['name'])); ?>', '<?php echo htmlspecialchars(addslashes($district['description'] ?? '')); ?>', '<?php echo htmlspecialchars(addslashes($district['image'] ?? '')); ?>', <?php echo $district['latitude'] ?? 'null'; ?>, <?php echo $district['longitude'] ?? 'null'; ?>, '<?php echo htmlspecialchars(addslashes($district['road_type'] ?? 'plain')); ?>')">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this city?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $district['id']; ?>">
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
                        <p>No cities found. Add your first city above.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- City Modal -->
    <div class="modal fade" id="districtModal" tabindex="-1" aria-labelledby="districtModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="districtModalLabel"><i class="bi bi-geo"></i> <span id="modalTitle">Add City</span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="districtForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" id="formAction" value="add">
                        <input type="hidden" name="id" id="formId" value="">
                        
                        <div class="mb-3">
                            <label for="districtState" class="form-label">State <span class="text-danger">*</span></label>
                            <select class="form-select select2" id="districtState" name="state_id" required>
                                <option value="">Select State</option>
                                <?php foreach ($states as $state): ?>
                                <option value="<?php echo $state['id']; ?>"><?php echo htmlspecialchars($state['country_name'] . ' - ' . $state['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="districtName" class="form-label">City Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="districtName" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="districtDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="districtDescription" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="districtImage" class="form-label">Image URL</label>
                            <input type="url" class="form-control" id="districtImage" name="image" placeholder="https://example.com/image.jpg">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="districtLatitude" class="form-label"><i class="bi bi-geo-alt"></i> Latitude</label>
                                <input type="number" step="any" class="form-control" id="districtLatitude" name="latitude" placeholder="e.g., 28.6139">
                                <small class="form-text text-muted">For distance calculation</small>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="districtLongitude" class="form-label"><i class="bi bi-geo-alt-fill"></i> Longitude</label>
                                <input type="number" step="any" class="form-control" id="districtLongitude" name="longitude" placeholder="e.g., 77.2090">
                                <small class="form-text text-muted">For distance calculation</small>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="districtRoadType" class="form-label"><i class="bi bi-road"></i> Road Type</label>
                                <select class="form-select" id="districtRoadType" name="road_type">
                                    <option value="plain">Plain</option>
                                    <option value="hill">Hill</option>
                                    <option value="mixed">Mixed</option>
                                </select>
                                <small class="form-text text-muted">Affects travel time calculation</small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> <span id="submitText">Add City</span>
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
        // Initialize Select2 when modal opens
        $('#districtModal').on('shown.bs.modal', function() {
            if (!$('#districtState').hasClass('select2-hidden-accessible')) {
                $('#districtState').select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    dropdownParent: $('#districtModal')
                });
            }
            
            // Auto-focus search when dropdown opens
            $('#districtState').on('select2:open', function() {
                setTimeout(function() {
                    $('.select2-search__field').focus();
                }, 100);
            });
        });

        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Add City';
            document.getElementById('submitText').textContent = 'Add City';
            document.getElementById('formAction').value = 'add';
            document.getElementById('formId').value = '';
            document.getElementById('districtForm').reset();
            
            // Initialize Select2 after modal is shown
            setTimeout(function() {
                if (!$('#districtState').hasClass('select2-hidden-accessible')) {
                    $('#districtState').select2({
                        theme: 'bootstrap-5',
                        width: '100%',
                        dropdownParent: $('#districtModal')
                    });
                } else {
                    $('#districtState').val(null).trigger('change');
                }
                
                // Auto-focus search when dropdown opens
                $('#districtState').on('select2:open', function() {
                    setTimeout(function() {
                        $('.select2-search__field').focus();
                    }, 100);
                });
            }, 100);
        }

        function openEditModal(id, stateId, name, description, image, latitude, longitude, roadType) {
            document.getElementById('modalTitle').textContent = 'Edit City';
            document.getElementById('submitText').textContent = 'Update City';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('formId').value = id;
            document.getElementById('districtName').value = name;
            document.getElementById('districtDescription').value = description;
            document.getElementById('districtImage').value = image;
            document.getElementById('districtLatitude').value = latitude !== null ? latitude : '';
            document.getElementById('districtLongitude').value = longitude !== null ? longitude : '';
            document.getElementById('districtRoadType').value = roadType || 'plain';
            
            var modal = new bootstrap.Modal(document.getElementById('districtModal'));
            modal.show();
            
            // Initialize Select2 after modal is shown
            setTimeout(function() {
                if (!$('#districtState').hasClass('select2-hidden-accessible')) {
                    $('#districtState').select2({
                        theme: 'bootstrap-5',
                        width: '100%',
                        dropdownParent: $('#districtModal')
                    });
                }
                $('#districtState').val(stateId).trigger('change');
                
                // Auto-focus search when dropdown opens
                $('#districtState').on('select2:open', function() {
                    setTimeout(function() {
                        $('.select2-search__field').focus();
                    }, 100);
                });
            }, 100);
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

