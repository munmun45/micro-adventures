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
                $stmt = $conn->prepare("INSERT INTO countries (name, description, image, status) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $name, $description, $image, $status);
                if ($stmt->execute()) {
                    $message = "Country added successfully!";
                    $message_type = "success";
                    logActivity("Add Country", "Added country: $name");
                } else {
                    $message = "Error adding country: " . $conn->error;
                    $message_type = "error";
                }
                $stmt->close();
            } else {
                $message = "Country name is required!";
                $message_type = "error";
            }
        } elseif ($_POST['action'] === 'edit') {
            $id = intval($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $image = trim($_POST['image'] ?? '');
            $status = trim($_POST['status'] ?? 'active');
            
            if ($id > 0 && !empty($name)) {
                $stmt = $conn->prepare("UPDATE countries SET name = ?, description = ?, image = ?, status = ? WHERE id = ?");
                $stmt->bind_param("ssssi", $name, $description, $image, $status, $id);
                if ($stmt->execute()) {
                    $message = "Country updated successfully!";
                    $message_type = "success";
                    logActivity("Update Country", "Updated country: $name");
                } else {
                    $message = "Error updating country: " . $conn->error;
                    $message_type = "error";
                }
                $stmt->close();
            }
        } elseif ($_POST['action'] === 'delete') {
            $id = intval($_POST['id'] ?? 0);
            if ($id > 0) {
                $stmt = $conn->prepare("DELETE FROM countries WHERE id = ?");
                $stmt->bind_param("i", $id);
                if ($stmt->execute()) {
                    $message = "Country deleted successfully!";
                    $message_type = "success";
                    logActivity("Delete Country", "Deleted country ID: $id");
                } else {
                    $message = "Error deleting country: " . $conn->error;
                    $message_type = "error";
                }
                $stmt->close();
            }
        }
    }
}

// Get all countries
$countries = [];
$result = $conn->query("SELECT * FROM countries ORDER BY name");
if ($result) {
    $countries = $result->fetch_all(MYSQLI_ASSOC);
}

// Get country for editing
$edit_country = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $result = $conn->query("SELECT * FROM countries WHERE id = $edit_id");
    if ($result && $result->num_rows > 0) {
        $edit_country = $result->fetch_assoc();
    }
}

$admin = getAdminInfo();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Countries | Admin Panel</title>
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
                    <h1><i class="bi bi-globe"></i> Manage Countries</h1>
                    <p>Add, edit, or delete countries</p>
                </div>
                <div>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#countryModal" onclick="openAddModal()">
                        <i class="bi bi-plus-circle"></i> Add New Country
                    </button>
                </div>
            </div>

            <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <!-- Countries List -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2><i class="bi bi-list-ul"></i> All Countries (<?php echo count($countries); ?>)</h2>
                    <div class="search-box">
                        <input type="text" id="searchTable" class="form-control form-control-sm" placeholder="Search countries..." style="width: 250px;">
                    </div>
                </div>
                <div class="card-body">
                    <?php if (count($countries) > 0): ?>
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
                                <?php foreach ($countries as $country): ?>
                                <tr>
                                    <td><?php echo $country['id']; ?></td>
                                    <td>
                                        <?php if ($country['image']): ?>
                                        <img src="<?php echo htmlspecialchars($country['image']); ?>" alt="<?php echo htmlspecialchars($country['name']); ?>" class="table-image">
                                        <?php else: ?>
                                        <span class="text-gray-400">No image</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?php echo htmlspecialchars($country['name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars(substr($country['description'] ?? '', 0, 100)); ?><?php echo strlen($country['description'] ?? '') > 100 ? '...' : ''; ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button type="button" class="btn-icon btn-edit" title="Edit" onclick="openEditModal(<?php echo $country['id']; ?>, '<?php echo htmlspecialchars(addslashes($country['name'])); ?>', '<?php echo htmlspecialchars(addslashes($country['description'] ?? '')); ?>', '<?php echo htmlspecialchars(addslashes($country['image'] ?? '')); ?>', '<?php echo htmlspecialchars(addslashes($country['status'] ?? 'active')); ?>')">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this country?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $country['id']; ?>">
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
                        <p>No countries found. Add your first country above.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Country Modal -->
    <div class="modal fade" id="countryModal" tabindex="-1" aria-labelledby="countryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="countryModalLabel"><i class="bi bi-globe"></i> <span id="modalTitle">Add Country</span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="countryForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" id="formAction" value="add">
                        <input type="hidden" name="id" id="formId" value="">
                        
                        <div class="mb-3">
                            <label for="countryName" class="form-label">Country Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="countryName" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="countryDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="countryDescription" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="countryImage" class="form-label">Image URL</label>
                            <input type="url" class="form-control" id="countryImage" name="image" placeholder="https://example.com/image.jpg">
                        </div>
                        
                        <div class="mb-3">
                            <label for="countryStatus" class="form-label">Status</label>
                            <select class="form-select" id="countryStatus" name="status">
                                <option value="active" selected>Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> <span id="submitText">Add Country</span>
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
            document.getElementById('modalTitle').textContent = 'Add Country';
            document.getElementById('submitText').textContent = 'Add Country';
            document.getElementById('formAction').value = 'add';
            document.getElementById('formId').value = '';
            document.getElementById('countryForm').reset();
        }

        function openEditModal(id, name, description, image, status) {
            document.getElementById('modalTitle').textContent = 'Edit Country';
            document.getElementById('submitText').textContent = 'Update Country';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('formId').value = id;
            document.getElementById('countryName').value = name;
            document.getElementById('countryDescription').value = description;
            document.getElementById('countryImage').value = image;
            document.getElementById('countryStatus').value = status || 'active';
            
            var modal = new bootstrap.Modal(document.getElementById('countryModal'));
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

