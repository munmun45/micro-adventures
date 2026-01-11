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
            $min_price_per_night = !empty($_POST['min_price_per_night']) ? floatval($_POST['min_price_per_night']) : null;
            $max_price_per_night = !empty($_POST['max_price_per_night']) ? floatval($_POST['max_price_per_night']) : null;
            
            if (!empty($name)) {
                $stmt = $conn->prepare("INSERT INTO hotel_types (name, description, image, min_price_per_night, max_price_per_night) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssdd", $name, $description, $image, $min_price_per_night, $max_price_per_night);
                if ($stmt->execute()) {
                    $message = "Hotel type added successfully!";
                    $message_type = "success";
                    logActivity("Add Hotel Type", "Added hotel type: $name");
                } else {
                    $message = "Error adding hotel type: " . $conn->error;
                    $message_type = "error";
                }
                $stmt->close();
            } else {
                $message = "Hotel type name is required!";
                $message_type = "error";
            }
        } elseif ($_POST['action'] === 'edit') {
            $id = intval($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $image = trim($_POST['image'] ?? '');
            $min_price_per_night = !empty($_POST['min_price_per_night']) ? floatval($_POST['min_price_per_night']) : null;
            $max_price_per_night = !empty($_POST['max_price_per_night']) ? floatval($_POST['max_price_per_night']) : null;
            
            if ($id > 0 && !empty($name)) {
                $stmt = $conn->prepare("UPDATE hotel_types SET name = ?, description = ?, image = ?, min_price_per_night = ?, max_price_per_night = ? WHERE id = ?");
                $stmt->bind_param("sssddi", $name, $description, $image, $min_price_per_night, $max_price_per_night, $id);
                if ($stmt->execute()) {
                    $message = "Hotel type updated successfully!";
                    $message_type = "success";
                    logActivity("Update Hotel Type", "Updated hotel type: $name");
                } else {
                    $message = "Error updating hotel type: " . $conn->error;
                    $message_type = "error";
                }
                $stmt->close();
            }
        } elseif ($_POST['action'] === 'delete') {
            $id = intval($_POST['id'] ?? 0);
            if ($id > 0) {
                $stmt = $conn->prepare("DELETE FROM hotel_types WHERE id = ?");
                $stmt->bind_param("i", $id);
                if ($stmt->execute()) {
                    $message = "Hotel type deleted successfully!";
                    $message_type = "success";
                    logActivity("Delete Hotel Type", "Deleted hotel type ID: $id");
                } else {
                    $message = "Error deleting hotel type: " . $conn->error;
                    $message_type = "error";
                }
                $stmt->close();
            }
        }
    }
}

// Get all hotel types
$hotels = [];
$result = $conn->query("SELECT * FROM hotel_types ORDER BY name");
if ($result) {
    $hotels = $result->fetch_all(MYSQLI_ASSOC);
}

// Get hotel for editing
$edit_hotel = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $result = $conn->query("SELECT * FROM hotel_types WHERE id = $edit_id");
    if ($result && $result->num_rows > 0) {
        $edit_hotel = $result->fetch_assoc();
    }
}

$admin = getAdminInfo();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Hotel Types | Admin Panel</title>
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
                    <h1><i class="bi bi-building"></i> Manage Hotel Types</h1>
                    <p>Add, edit, or delete hotel types</p>
                </div>
                <div>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#hotelModal" onclick="openAddModal()">
                        <i class="bi bi-plus-circle"></i> Add New Hotel Type
                    </button>
                </div>
            </div>

            <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <!-- Hotels List -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2><i class="bi bi-list-ul"></i> All Hotel Types (<?php echo count($hotels); ?>)</h2>
                    <div class="search-box">
                        <input type="text" id="searchTable" class="form-control form-control-sm" placeholder="Search hotels..." style="width: 250px;">
                    </div>
                </div>
                <div class="card-body">
                    <?php if (count($hotels) > 0): ?>
                    <div class="table-responsive">
                        <table class="data-table" id="dataTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Image</th>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Price/Night</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($hotels as $hotel): ?>
                                <tr>
                                    <td><?php echo $hotel['id']; ?></td>
                                    <td>
                                        <?php if ($hotel['image']): ?>
                                        <img src="<?php echo htmlspecialchars($hotel['image']); ?>" alt="<?php echo htmlspecialchars($hotel['name']); ?>" class="table-image">
                                        <?php else: ?>
                                        <span class="text-gray-400">No image</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?php echo htmlspecialchars($hotel['name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars(substr($hotel['description'] ?? '', 0, 100)); ?><?php echo strlen($hotel['description'] ?? '') > 100 ? '...' : ''; ?></td>
                                    <td>
                                        <?php 
                                        $min_price = $hotel['min_price_per_night'] ?? null;
                                        $max_price = $hotel['max_price_per_night'] ?? null;
                                        if ($min_price !== null && $max_price !== null) {
                                            echo '₹' . number_format($min_price, 2) . ' - ₹' . number_format($max_price, 2);
                                        } elseif ($min_price !== null) {
                                            echo '₹' . number_format($min_price, 2) . '+';
                                        } elseif ($max_price !== null) {
                                            echo 'Up to ₹' . number_format($max_price, 2);
                                        } else {
                                            echo '<span class="text-muted">Not set</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button type="button" class="btn-icon btn-edit" title="Edit" onclick="openEditModal(<?php echo $hotel['id']; ?>, '<?php echo htmlspecialchars(addslashes($hotel['name'])); ?>', '<?php echo htmlspecialchars(addslashes($hotel['description'] ?? '')); ?>', '<?php echo htmlspecialchars(addslashes($hotel['image'] ?? '')); ?>', <?php echo $min_price !== null ? $min_price : 'null'; ?>, <?php echo $max_price !== null ? $max_price : 'null'; ?>)">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this hotel type?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $hotel['id']; ?>">
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
                        <p>No hotel types found. Add your first hotel type above.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Hotel Modal -->
    <div class="modal fade" id="hotelModal" tabindex="-1" aria-labelledby="hotelModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="hotelModalLabel"><i class="bi bi-building"></i> <span id="modalTitle">Add Hotel Type</span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="hotelForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" id="formAction" value="add">
                        <input type="hidden" name="id" id="formId" value="">
                        
                        <div class="mb-3">
                            <label for="hotelName" class="form-label">Hotel Type Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="hotelName" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="hotelDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="hotelDescription" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="hotelImage" class="form-label">Image URL</label>
                            <input type="url" class="form-control" id="hotelImage" name="image" placeholder="https://example.com/image.jpg">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="hotelMinPrice" class="form-label">Min Price/Night (₹)</label>
                                <input type="number" step="0.01" class="form-control" id="hotelMinPrice" name="min_price_per_night" placeholder="e.g., 500.00">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="hotelMaxPrice" class="form-label">Max Price/Night (₹)</label>
                                <input type="number" step="0.01" class="form-control" id="hotelMaxPrice" name="max_price_per_night" placeholder="e.g., 5000.00">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> <span id="submitText">Add Hotel Type</span>
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
            document.getElementById('modalTitle').textContent = 'Add Hotel Type';
            document.getElementById('submitText').textContent = 'Add Hotel Type';
            document.getElementById('formAction').value = 'add';
            document.getElementById('formId').value = '';
            document.getElementById('hotelForm').reset();
        }

        function openEditModal(id, name, description, image, minPrice, maxPrice) {
            document.getElementById('modalTitle').textContent = 'Edit Hotel Type';
            document.getElementById('submitText').textContent = 'Update Hotel Type';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('formId').value = id;
            document.getElementById('hotelName').value = name;
            document.getElementById('hotelDescription').value = description;
            document.getElementById('hotelImage').value = image;
            document.getElementById('hotelMinPrice').value = minPrice !== null ? minPrice : '';
            document.getElementById('hotelMaxPrice').value = maxPrice !== null ? maxPrice : '';
            
            var modal = new bootstrap.Modal(document.getElementById('hotelModal'));
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

