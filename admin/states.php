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
            $country_id = intval($_POST['country_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $image = trim($_POST['image'] ?? '');
            $best_months = isset($_POST['best_months']) ? implode(',', $_POST['best_months']) : '';
            
            if (!empty($name) && $country_id > 0) {
                $stmt = $conn->prepare("INSERT INTO states (country_id, name, description, image, best_months) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("issss", $country_id, $name, $description, $image, $best_months);
                if ($stmt->execute()) {
                    $message = "State added successfully!";
                    $message_type = "success";
                    logActivity("Add State", "Added state: $name");
                } else {
                    $message = "Error adding state: " . $conn->error;
                    $message_type = "error";
                }
                $stmt->close();
            } else {
                $message = "State name and country are required!";
                $message_type = "error";
            }
        } elseif ($_POST['action'] === 'edit') {
            $id = intval($_POST['id'] ?? 0);
            $country_id = intval($_POST['country_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $image = trim($_POST['image'] ?? '');
            $best_months = isset($_POST['best_months']) ? implode(',', $_POST['best_months']) : '';
            
            if ($id > 0 && !empty($name) && $country_id > 0) {
                $stmt = $conn->prepare("UPDATE states SET country_id = ?, name = ?, description = ?, image = ?, best_months = ? WHERE id = ?");
                $stmt->bind_param("issssi", $country_id, $name, $description, $image, $best_months, $id);
                if ($stmt->execute()) {
                    $message = "State updated successfully!";
                    $message_type = "success";
                    logActivity("Update State", "Updated state: $name");
                } else {
                    $message = "Error updating state: " . $conn->error;
                    $message_type = "error";
                }
                $stmt->close();
            }
        } elseif ($_POST['action'] === 'delete') {
            $id = intval($_POST['id'] ?? 0);
            if ($id > 0) {
                $stmt = $conn->prepare("DELETE FROM states WHERE id = ?");
                $stmt->bind_param("i", $id);
                if ($stmt->execute()) {
                    $message = "State deleted successfully!";
                    $message_type = "success";
                    logActivity("Delete State", "Deleted state ID: $id");
                } else {
                    $message = "Error deleting state: " . $conn->error;
                    $message_type = "error";
                }
                $stmt->close();
            }
        }
    }
}

// Get all countries for dropdown
$countries = [];
$result = $conn->query("SELECT * FROM countries ORDER BY name");
if ($result) {
    $countries = $result->fetch_all(MYSQLI_ASSOC);
}

// Get all states with country name
$states = [];
$result = $conn->query("SELECT s.*, c.name as country_name FROM states s JOIN countries c ON s.country_id = c.id ORDER BY c.name, s.name");
if ($result) {
    $states = $result->fetch_all(MYSQLI_ASSOC);
}

// Get state for editing
$edit_state = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $result = $conn->query("SELECT * FROM states WHERE id = $edit_id");
    if ($result && $result->num_rows > 0) {
        $edit_state = $result->fetch_assoc();
    }
}

$admin = getAdminInfo();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage States | Admin Panel</title>
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
                    <h1><i class="bi bi-geo-alt"></i> Manage States</h1>
                    <p>Add, edit, or delete states</p>
                </div>
                <div>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#stateModal" onclick="openAddModal()">
                        <i class="bi bi-plus-circle"></i> Add New State
                    </button>
                </div>
            </div>

            <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <!-- States List -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2><i class="bi bi-list-ul"></i> All States (<?php echo count($states); ?>)</h2>
                    <div class="search-box">
                        <input type="text" id="searchTable" class="form-control form-control-sm" placeholder="Search states..." style="width: 250px;">
                    </div>
                </div>
                <div class="card-body">
                    <?php if (count($states) > 0): ?>
                    <div class="table-responsive">
                        <table class="data-table" id="dataTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Image</th>
                                    <th>Name</th>
                                    <th>Country</th>
                                    <th>Best Months</th>
                                    <th>Description</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($states as $state): ?>
                                <tr>
                                    <td><?php echo $state['id']; ?></td>
                                    <td>
                                        <?php if ($state['image']): ?>
                                        <img src="<?php echo htmlspecialchars($state['image']); ?>" alt="<?php echo htmlspecialchars($state['name']); ?>" class="table-image">
                                        <?php else: ?>
                                        <span class="text-gray-400">No image</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?php echo htmlspecialchars($state['name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($state['country_name']); ?></td>
                                    <td>
                                        <?php 
                                        if (!empty($state['best_months'])) {
                                            $months = explode(',', $state['best_months']);
                                            $monthBadges = array_map(function($month) {
                                                return '<span class="badge bg-info text-dark me-1 mb-1">' . htmlspecialchars(trim($month)) . '</span>';
                                            }, $months);
                                            echo implode('', $monthBadges);
                                        } else {
                                            echo '<span class="text-muted">Not set</span>';
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars(substr($state['description'] ?? '', 0, 100)); ?><?php echo strlen($state['description'] ?? '') > 100 ? '...' : ''; ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button type="button" class="btn-icon btn-edit" title="Edit" onclick="openEditModal(<?php echo $state['id']; ?>, <?php echo $state['country_id']; ?>, '<?php echo htmlspecialchars(addslashes($state['name'])); ?>', '<?php echo htmlspecialchars(addslashes($state['description'] ?? '')); ?>', '<?php echo htmlspecialchars(addslashes($state['image'] ?? '')); ?>', '<?php echo htmlspecialchars(addslashes($state['best_months'] ?? '')); ?>')">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this state?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $state['id']; ?>">
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
                        <p>No states found. Add your first state above.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- State Modal -->
    <div class="modal fade" id="stateModal" tabindex="-1" aria-labelledby="stateModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="stateModalLabel"><i class="bi bi-geo-alt"></i> <span id="modalTitle">Add State</span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="stateForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" id="formAction" value="add">
                        <input type="hidden" name="id" id="formId" value="">
                        
                        <div class="mb-3">
                            <label for="stateCountry" class="form-label">Country <span class="text-danger">*</span></label>
                            <select class="form-select select2" id="stateCountry" name="country_id" required>
                                <option value="">Select Country</option>
                                <?php foreach ($countries as $country): ?>
                                <option value="<?php echo $country['id']; ?>"><?php echo htmlspecialchars($country['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="stateName" class="form-label">State Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="stateName" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="stateDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="stateDescription" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="stateImage" class="form-label">Image URL</label>
                            <input type="url" class="form-control" id="stateImage" name="image" placeholder="https://example.com/image.jpg">
                        </div>
                        
                        <div class="mb-3">
                            <label for="stateBestMonths" class="form-label">Best Months to Visit</label>
                            <select class="form-select select2-multiple" id="stateBestMonths" name="best_months[]" multiple>
                                <option value="January">January</option>
                                <option value="February">February</option>
                                <option value="March">March</option>
                                <option value="April">April</option>
                                <option value="May">May</option>
                                <option value="June">June</option>
                                <option value="July">July</option>
                                <option value="August">August</option>
                                <option value="September">September</option>
                                <option value="October">October</option>
                                <option value="November">November</option>
                                <option value="December">December</option>
                            </select>
                            <small class="form-text text-muted">Select multiple months when this state is best to visit</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> <span id="submitText">Add State</span>
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
        $('#stateModal').on('shown.bs.modal', function() {
            // Initialize Country dropdown
            if (!$('#stateCountry').hasClass('select2-hidden-accessible')) {
                $('#stateCountry').select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    dropdownParent: $('#stateModal')
                });
            }
            
            // Initialize Best Months multi-select
            if (!$('#stateBestMonths').hasClass('select2-hidden-accessible')) {
                $('#stateBestMonths').select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    dropdownParent: $('#stateModal'),
                    placeholder: 'Select months...',
                    allowClear: true
                });
            }
            
            // Auto-focus search when dropdown opens
            $('#stateCountry, #stateBestMonths').on('select2:open', function() {
                setTimeout(function() {
                    $('.select2-search__field').focus();
                }, 100);
            });
        });

        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Add State';
            document.getElementById('submitText').textContent = 'Add State';
            document.getElementById('formAction').value = 'add';
            document.getElementById('formId').value = '';
            document.getElementById('stateForm').reset();
            
            // Initialize Select2 after modal is shown
            setTimeout(function() {
                if (!$('#stateCountry').hasClass('select2-hidden-accessible')) {
                    $('#stateCountry').select2({
                        theme: 'bootstrap-5',
                        width: '100%',
                        dropdownParent: $('#stateModal')
                    });
                } else {
                    $('#stateCountry').val(null).trigger('change');
                }
                
                if (!$('#stateBestMonths').hasClass('select2-hidden-accessible')) {
                    $('#stateBestMonths').select2({
                        theme: 'bootstrap-5',
                        width: '100%',
                        dropdownParent: $('#stateModal'),
                        placeholder: 'Select months...',
                        allowClear: true
                    });
                } else {
                    $('#stateBestMonths').val(null).trigger('change');
                }
                
                // Auto-focus search when dropdown opens
                $('#stateCountry, #stateBestMonths').on('select2:open', function() {
                    setTimeout(function() {
                        $('.select2-search__field').focus();
                    }, 100);
                });
            }, 100);
        }

        function openEditModal(id, countryId, name, description, image, bestMonths) {
            document.getElementById('modalTitle').textContent = 'Edit State';
            document.getElementById('submitText').textContent = 'Update State';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('formId').value = id;
            document.getElementById('stateName').value = name;
            document.getElementById('stateDescription').value = description;
            document.getElementById('stateImage').value = image;
            
            var modal = new bootstrap.Modal(document.getElementById('stateModal'));
            modal.show();
            
            // Initialize Select2 after modal is shown
            setTimeout(function() {
                if (!$('#stateCountry').hasClass('select2-hidden-accessible')) {
                    $('#stateCountry').select2({
                        theme: 'bootstrap-5',
                        width: '100%',
                        dropdownParent: $('#stateModal')
                    });
                }
                $('#stateCountry').val(countryId).trigger('change');
                
                if (!$('#stateBestMonths').hasClass('select2-hidden-accessible')) {
                    $('#stateBestMonths').select2({
                        theme: 'bootstrap-5',
                        width: '100%',
                        dropdownParent: $('#stateModal'),
                        placeholder: 'Select months...',
                        allowClear: true
                    });
                }
                
                // Set selected months
                if (bestMonths && bestMonths.trim() !== '') {
                    var monthsArray = bestMonths.split(',');
                    var trimmedMonths = monthsArray.map(function(m) { return m.trim(); });
                    $('#stateBestMonths').val(trimmedMonths).trigger('change');
                } else {
                    $('#stateBestMonths').val(null).trigger('change');
                }
                
                // Auto-focus search when dropdown opens
                $('#stateCountry, #stateBestMonths').on('select2:open', function() {
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

