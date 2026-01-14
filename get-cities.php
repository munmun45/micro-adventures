<?php
require_once 'config.php';
header('Content-Type: application/json');

$conn = getDBConnection();
$state_id = isset($_GET['state_id']) ? (int)$_GET['state_id'] : 0;

if ($state_id > 0) {
    $query = "SELECT id, name, latitude, longitude FROM districts WHERE state_id = ? AND status = 'active' ORDER BY name";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $state_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $cities = [];
    while ($row = $result->fetch_assoc()) {
        $cities[] = $row;
    }
    
    echo json_encode($cities);
    $stmt->close();
} else {
    echo json_encode([]);
}

$conn->close();
?>



