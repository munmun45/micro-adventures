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
            
            if (!empty($name)) {
                $stmt = $conn->prepare("INSERT INTO transport_types (name, description, image) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $name, $description, $image);
                if ($stmt->execute()) {
                    $message = "Transport type added successfully!";
                    $message_type = "success";
                    logActivity("Add Transport Type", "Added transport: $name");
                } else {
                    $message = "Error adding transport type: " . $conn->error;
                    $message_type = "error";
                }
                $stmt->close();
            } else {
                $message = "Transport name is required!";
                $message_type = "error";
            }
        } elseif ($_POST['action'] === 'edit') {
            $id = intval($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $image = trim($_POST['image'] ?? '');
            
            if ($id > 0 && !empty($name)) {
                $stmt = $conn->prepare("UPDATE transport_types SET name = ?, description = ?, image = ? WHERE id = ?");
                $stmt->bind_param("sssi", $name, $description, $image, $id);
                if ($stmt->execute()) {
                    $message = "Transport type updated successfully!";
                    $message_type = "success";
                    logActivity("Update Transport Type", "Updated transport: $name");
                } else {
                    $message = "Error updating transport type: " . $conn->error;
                    $message_type = "error";
                }
                $stmt->close();
            }
        } elseif ($_POST['action'] === 'delete') {
            $id = intval($_POST['id'] ?? 0);
            if ($id > 0) {
                $stmt = $conn->prepare("DELETE FROM transport_types WHERE id = ?");
                $stmt->bind_param("i", $id);
                if ($stmt->execute()) {
                    $message = "Transport type deleted successfully!";
                    $message_type = "success";
                    logActivity("Delete Transport Type", "Deleted transport ID: $id");
                } else {
                    $message = "Error deleting transport type: " . $conn->error;
                    $message_type = "error";
                }
                $stmt->close();
            }
        } elseif ($_POST['action'] === 'add_price_rule') {
            $transport_type_id = intval($_POST['transport_type_id'] ?? 0);
            $sub_type = trim($_POST['sub_type'] ?? '');
            $min_cost_per_km = floatval($_POST['min_cost_per_km'] ?? 0);
            $max_cost_per_km = floatval($_POST['max_cost_per_km'] ?? 0);
            $notes = trim($_POST['notes'] ?? '');
            $is_active = trim($_POST['is_active'] ?? 'yes');
            
            if ($transport_type_id > 0 && !empty($sub_type) && $min_cost_per_km > 0 && $max_cost_per_km > 0) {
                $stmt = $conn->prepare("INSERT INTO transport_price_rules (transport_type_id, sub_type, min_cost_per_km, max_cost_per_km, notes, is_active) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isddss", $transport_type_id, $sub_type, $min_cost_per_km, $max_cost_per_km, $notes, $is_active);
                if ($stmt->execute()) {
                    $message = "Price rule added successfully!";
                    $message_type = "success";
                    logActivity("Add Transport Price Rule", "Added price rule: $sub_type");
                } else {
                    $message = "Error adding price rule: " . $conn->error;
                    $message_type = "error";
                }
                $stmt->close();
            } else {
                $message = "Please fill all required fields!";
                $message_type = "error";
            }
        } elseif ($_POST['action'] === 'edit_price_rule') {
            $id = intval($_POST['price_rule_id'] ?? 0);
            $transport_type_id = intval($_POST['transport_type_id'] ?? 0);
            $sub_type = trim($_POST['sub_type'] ?? '');
            $min_cost_per_km = floatval($_POST['min_cost_per_km'] ?? 0);
            $max_cost_per_km = floatval($_POST['max_cost_per_km'] ?? 0);
            $notes = trim($_POST['notes'] ?? '');
            $is_active = trim($_POST['is_active'] ?? 'yes');
            
            if ($id > 0 && $transport_type_id > 0 && !empty($sub_type) && $min_cost_per_km > 0 && $max_cost_per_km > 0) {
                $stmt = $conn->prepare("UPDATE transport_price_rules SET transport_type_id = ?, sub_type = ?, min_cost_per_km = ?, max_cost_per_km = ?, notes = ?, is_active = ? WHERE id = ?");
                $stmt->bind_param("isddssi", $transport_type_id, $sub_type, $min_cost_per_km, $max_cost_per_km, $notes, $is_active, $id);
                if ($stmt->execute()) {
                    $message = "Price rule updated successfully!";
                    $message_type = "success";
                    logActivity("Update Transport Price Rule", "Updated price rule: $sub_type");
                } else {
                    $message = "Error updating price rule: " . $conn->error;
                    $message_type = "error";
                }
                $stmt->close();
            }
        } elseif ($_POST['action'] === 'delete_price_rule') {
            $id = intval($_POST['price_rule_id'] ?? 0);
            if ($id > 0) {
                $stmt = $conn->prepare("DELETE FROM transport_price_rules WHERE id = ?");
                $stmt->bind_param("i", $id);
                if ($stmt->execute()) {
                    $message = "Price rule deleted successfully!";
                    $message_type = "success";
                    logActivity("Delete Transport Price Rule", "Deleted price rule ID: $id");
                } else {
                    $message = "Error deleting price rule: " . $conn->error;
                    $message_type = "error";
                }
                $stmt->close();
            }
        }
    }
}

// Get all transport types
$transports = [];
$result = $conn->query("SELECT * FROM transport_types ORDER BY name");
if ($result) {
    $transports = $result->fetch_all(MYSQLI_ASSOC);
}

// Handle AJAX request for price rules
if (isset($_GET['get_price_rules'])) {
    $transport_id = intval($_GET['get_price_rules']);
    $result = $conn->query("SELECT * FROM transport_price_rules WHERE transport_type_id = $transport_id ORDER BY sub_type");
    $price_rules = [];
    if ($result) {
        $price_rules = $result->fetch_all(MYSQLI_ASSOC);
    }
    header('Content-Type: application/json');
    echo json_encode($price_rules);
    exit;
}

// Get transport for editing
$edit_transport = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $result = $conn->query("SELECT * FROM transport_types WHERE id = $edit_id");
    if ($result && $result->num_rows > 0) {
        $edit_transport = $result->fetch_assoc();
    }
}

$admin = getAdminInfo();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Transport Types | Admin Panel</title>
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
                    <h1><i class="bi bi-truck"></i> Manage Transport Types</h1>
                    <p>Add, edit, or delete transport and vehicle types</p>
                </div>
                <div>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#transportModal" onclick="openAddModal()">
                        <i class="bi bi-plus-circle"></i> Add New Transport Type
                    </button>
                </div>
            </div>

            <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <!-- Transport Types List -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2><i class="bi bi-list-ul"></i> All Transport Types (<?php echo count($transports); ?>)</h2>
                    <div class="search-box">
                        <input type="text" id="searchTable" class="form-control form-control-sm" placeholder="Search transport types..." style="width: 250px;">
                    </div>
                </div>
                <div class="card-body">
                    <?php if (count($transports) > 0): ?>
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
                                <?php foreach ($transports as $transport): ?>
                                <tr>
                                    <td><?php echo $transport['id']; ?></td>
                                    <td>
                                        <?php if ($transport['image']): ?>
                                        <img src="<?php echo htmlspecialchars($transport['image']); ?>" alt="<?php echo htmlspecialchars($transport['name']); ?>" class="table-image">
                                        <?php else: ?>
                                        <span class="text-gray-400">No image</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?php echo htmlspecialchars($transport['name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars(substr($transport['description'] ?? '', 0, 100)); ?><?php echo strlen($transport['description'] ?? '') > 100 ? '...' : ''; ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button type="button" class="btn-icon btn-edit" title="Edit" onclick="openEditModal(<?php echo $transport['id']; ?>, '<?php echo htmlspecialchars(addslashes($transport['name'])); ?>', '<?php echo htmlspecialchars(addslashes($transport['description'] ?? '')); ?>', '<?php echo htmlspecialchars(addslashes($transport['image'] ?? '')); ?>')">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button type="button" class="btn-icon btn-info" title="Manage Price Rules" onclick="openPriceRulesModal(<?php echo $transport['id']; ?>, '<?php echo htmlspecialchars(addslashes($transport['name'])); ?>')">
                                                <i class="bi bi-currency-rupee"></i>
                                            </button>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this transport type?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $transport['id']; ?>">
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
                        <p>No transport types found. Add your first transport type above.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>

    <!-- Transport Modal -->
    <div class="modal fade" id="transportModal" tabindex="-1" aria-labelledby="transportModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="transportModalLabel"><i class="bi bi-truck"></i> <span id="modalTitle">Add Transport Type</span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="transportForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" id="formAction" value="add">
                        <input type="hidden" name="id" id="formId" value="">
                        
                        <div class="mb-3">
                            <label for="transportName" class="form-label">Transport Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="transportName" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="transportDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="transportDescription" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="transportImage" class="form-label">Image URL</label>
                            <input type="url" class="form-control" id="transportImage" name="image" placeholder="https://example.com/image.jpg">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> <span id="submitText">Add Transport</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Price Rules Management Modal -->
    <div class="modal fade" id="priceRulesModal" tabindex="-1" aria-labelledby="priceRulesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="priceRulesModalLabel"><i class="bi bi-currency-rupee"></i> Manage Price Rules - <span id="priceRulesTransportName"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                    <input type="hidden" id="currentTransportTypeId" value="">
                    
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0"><i class="bi bi-list-ul"></i> Price Rules</h6>
                        <button type="button" class="btn btn-primary btn-sm" onclick="openAddPriceRuleModal()">
                            <i class="bi bi-plus-circle"></i> Add New Price Rule
                        </button>
                    </div>
                    
                    <!-- Existing Price Rules List -->
                    <div id="priceRulesList">
                        <div class="text-center py-3">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
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

    <!-- Add Price Rule Modal -->
    <div class="modal fade" id="addPriceRuleModal" tabindex="-1" aria-labelledby="addPriceRuleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addPriceRuleModalLabel"><i class="bi bi-plus-circle"></i> Add New Price Rule</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="priceRuleForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_price_rule">
                        <input type="hidden" name="transport_type_id" id="addPriceRuleTransportTypeId" value="">
                        
                        <div class="mb-3">
                            <label for="addPriceRuleSubType" class="form-label">Sub Type <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="addPriceRuleSubType" name="sub_type" placeholder="e.g., Ordinary Bus, AC Bus, Sleeper, 3AC" required>
                            <small class="form-text text-muted">Examples: Ordinary, AC, Sleeper, 3AC, Petrol Bike, Diesel Car</small>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="addPriceRuleMinCost" class="form-label">Min Cost/km (₹) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" class="form-control" id="addPriceRuleMinCost" name="min_cost_per_km" placeholder="1.20" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="addPriceRuleMaxCost" class="form-label">Max Cost/km (₹) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" class="form-control" id="addPriceRuleMaxCost" name="max_cost_per_km" placeholder="1.60" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="addPriceRuleNotes" class="form-label">Notes</label>
                            <textarea class="form-control" id="addPriceRuleNotes" name="notes" rows="2" placeholder="Additional information about this price rule"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="addPriceRuleIsActive" class="form-label">Status</label>
                            <select class="form-select" id="addPriceRuleIsActive" name="is_active">
                                <option value="yes" selected>Active</option>
                                <option value="no">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Add Price Rule
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Price Rule Modal -->
    <div class="modal fade" id="editPriceRuleModal" tabindex="-1" aria-labelledby="editPriceRuleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editPriceRuleModalLabel"><i class="bi bi-pencil"></i> Edit Price Rule</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="editPriceRuleForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit_price_rule">
                        <input type="hidden" name="price_rule_id" id="editPriceRuleId" value="">
                        <input type="hidden" name="transport_type_id" id="editPriceRuleTransportTypeId" value="">
                        
                        <div class="mb-3">
                            <label for="editPriceRuleSubType" class="form-label">Sub Type <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="editPriceRuleSubType" name="sub_type" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="editPriceRuleMinCost" class="form-label">Min Cost/km (₹) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" class="form-control" id="editPriceRuleMinCost" name="min_cost_per_km" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editPriceRuleMaxCost" class="form-label">Max Cost/km (₹) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" class="form-control" id="editPriceRuleMaxCost" name="max_cost_per_km" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editPriceRuleNotes" class="form-label">Notes</label>
                            <textarea class="form-control" id="editPriceRuleNotes" name="notes" rows="2"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editPriceRuleIsActive" class="form-label">Status</label>
                            <select class="form-select" id="editPriceRuleIsActive" name="is_active">
                                <option value="yes">Active</option>
                                <option value="no">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Update
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
            document.getElementById('modalTitle').textContent = 'Add Transport Type';
            document.getElementById('submitText').textContent = 'Add Transport';
            document.getElementById('formAction').value = 'add';
            document.getElementById('formId').value = '';
            document.getElementById('transportForm').reset();
        }

        function openEditModal(id, name, description, image) {
            document.getElementById('modalTitle').textContent = 'Edit Transport Type';
            document.getElementById('submitText').textContent = 'Update Transport';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('formId').value = id;
            document.getElementById('transportName').value = name;
            document.getElementById('transportDescription').value = description;
            document.getElementById('transportImage').value = image;
            
            var modal = new bootstrap.Modal(document.getElementById('transportModal'));
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

        function openPriceRulesModal(transportId, transportName) {
            document.getElementById('priceRulesTransportName').textContent = transportName;
            document.getElementById('currentTransportTypeId').value = transportId;
            
            // Load existing price rules for this transport type
            loadPriceRules(transportId);
            
            var modal = new bootstrap.Modal(document.getElementById('priceRulesModal'));
            modal.show();
        }

        function openAddPriceRuleModal() {
            const transportId = document.getElementById('currentTransportTypeId').value;
            document.getElementById('addPriceRuleTransportTypeId').value = transportId;
            document.getElementById('priceRuleForm').reset();
            document.getElementById('addPriceRuleTransportTypeId').value = transportId;
            
            var modal = new bootstrap.Modal(document.getElementById('addPriceRuleModal'));
            modal.show();
        }

        function loadPriceRules(transportId) {
            const listDiv = document.getElementById('priceRulesList');
            listDiv.innerHTML = '<div class="text-center py-3"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';
            
            // Fetch price rules via AJAX
            fetch('transport.php?get_price_rules=' + transportId)
                .then(response => response.json())
                .then(data => {
                    if (data.length === 0) {
                        listDiv.innerHTML = '<div class="empty-state"><i class="bi bi-inbox"></i><p>No price rules found. Add your first price rule above.</p></div>';
                        return;
                    }
                    
                    let html = '<div class="table-responsive"><table class="table table-sm table-bordered">';
                    html += '<thead><tr><th>Sub Type</th><th>Min Cost/km</th><th>Max Cost/km</th><th>Notes</th><th>Status</th><th>Actions</th></tr></thead>';
                    html += '<tbody>';
                    
                    data.forEach(rule => {
                        html += '<tr>';
                        html += '<td><strong>' + escapeHtml(rule.sub_type) + '</strong></td>';
                        html += '<td>₹' + parseFloat(rule.min_cost_per_km).toFixed(2) + '</td>';
                        html += '<td>₹' + parseFloat(rule.max_cost_per_km).toFixed(2) + '</td>';
                        html += '<td>' + escapeHtml(rule.notes || '-') + '</td>';
                        html += '<td>' + (rule.is_active === 'yes' ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>') + '</td>';
                        html += '<td>';
                        html += '<button type="button" class="btn btn-sm btn-outline-primary me-1" onclick="openEditPriceRuleModal(' + escapeHtml(JSON.stringify(rule)) + ')" title="Edit"><i class="bi bi-pencil"></i></button>';
                        html += '<form method="POST" style="display:inline;" onsubmit="return confirm(\'Are you sure you want to delete this price rule?\');">';
                        html += '<input type="hidden" name="action" value="delete_price_rule">';
                        html += '<input type="hidden" name="price_rule_id" value="' + rule.id + '">';
                        html += '<button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>';
                        html += '</form>';
                        html += '</td>';
                        html += '</tr>';
                    });
                    
                    html += '</tbody></table></div>';
                    listDiv.innerHTML = html;
                })
                .catch(error => {
                    listDiv.innerHTML = '<div class="alert alert-danger">Error loading price rules. Please refresh the page.</div>';
                    console.error('Error:', error);
                });
        }

        function openEditPriceRuleModal(rule) {
            document.getElementById('editPriceRuleId').value = rule.id;
            document.getElementById('editPriceRuleTransportTypeId').value = rule.transport_type_id;
            document.getElementById('editPriceRuleSubType').value = rule.sub_type || '';
            document.getElementById('editPriceRuleMinCost').value = rule.min_cost_per_km || '';
            document.getElementById('editPriceRuleMaxCost').value = rule.max_cost_per_km || '';
            document.getElementById('editPriceRuleNotes').value = rule.notes || '';
            document.getElementById('editPriceRuleIsActive').value = rule.is_active || 'yes';
            
            var modal = new bootstrap.Modal(document.getElementById('editPriceRuleModal'));
            modal.show();
        }

        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return String(text).replace(/[&<>"']/g, m => map[m]);
        }

        // Handle form submission for add price rule
        document.getElementById('priceRuleForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('transport.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                // Close add modal
                const addModal = bootstrap.Modal.getInstance(document.getElementById('addPriceRuleModal'));
                addModal.hide();
                
                // Reload price rules list
                const transportId = document.getElementById('currentTransportTypeId').value;
                loadPriceRules(transportId);
                
                // Show success message
                setTimeout(() => {
                    window.location.reload();
                }, 500);
            })
            .catch(error => {
                alert('Error adding price rule. Please try again.');
                console.error('Error:', error);
            });
        });

        // Handle form submission for edit price rule
        document.getElementById('editPriceRuleForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('transport.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                // Close edit modal and reload price rules
                const editModal = bootstrap.Modal.getInstance(document.getElementById('editPriceRuleModal'));
                editModal.hide();
                
                const transportId = document.getElementById('currentTransportTypeId').value;
                loadPriceRules(transportId);
                
                // Show success message
                setTimeout(() => {
                    window.location.reload();
                }, 500);
            })
            .catch(error => {
                alert('Error updating price rule. Please try again.');
                console.error('Error:', error);
            });
        });
    </script>
</body>
</html>

