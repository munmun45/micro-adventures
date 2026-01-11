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
        }
    }
}

// Get all transport types
$transports = [];
$result = $conn->query("SELECT * FROM transport_types ORDER BY name");
if ($result) {
    $transports = $result->fetch_all(MYSQLI_ASSOC);
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
    </script>
</body>
</html>

