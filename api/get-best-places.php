<?php
require_once '../config.php';
header('Content-Type: application/json');

$conn = getDBConnection();
$current_month = date('F');

// Get places that are best for current month (check place_activities for available_months)
$sql = "SELECT DISTINCT p.*, d.name as city_name, d.id as city_id, s.name as state_name, s.id as state_id
        FROM places p
        JOIN districts d ON p.city_id = d.id
        JOIN states s ON d.state_id = s.id
        LEFT JOIN place_activities pa ON p.id = pa.place_id AND pa.status = 'active'
        WHERE p.status = 'active' 
        AND (pa.available_months LIKE ? OR pa.available_months IS NULL OR pa.available_months = '')
        ORDER BY p.priority_level DESC, p.experience_score DESC
        LIMIT 6";

$month_param = "%{$current_month}%";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $month_param);
$stmt->execute();
$result = $stmt->get_result();

$places = [];
while ($row = $result->fetch_assoc()) {
    $places[] = $row;
}

$stmt->close();

echo json_encode(['success' => true, 'places' => $places]);
?>

