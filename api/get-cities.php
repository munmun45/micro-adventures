<?php
require_once '../config.php';
header('Content-Type: application/json');

$state_id = isset($_GET['state_id']) ? (int)$_GET['state_id'] : 0;

if ($state_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid state ID']);
    exit;
}

$conn = getDBConnection();
$sql = "SELECT id, name FROM districts WHERE state_id = ? AND status = 'active' ORDER BY name";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $state_id);
$stmt->execute();
$result = $stmt->get_result();

$cities = [];
while ($row = $result->fetch_assoc()) {
    $cities[] = $row;
}

$stmt->close();

echo json_encode(['success' => true, 'cities' => $cities]);
?>

