<?php
// Quick test to check database and search
require_once 'config.php';

echo "<h2>Database Connection Test</h2>";
try {
    $conn = getDBConnection();
    echo "✅ Database connected successfully!<br><br>";
} catch (Exception $e) {
    die("❌ Database connection failed: " . $e->getMessage());
}

// Test 1: Check if tables exist
echo "<h3>1. Checking Tables</h3>";
$tables = ['places', 'districts', 'states', 'countries', 'place_activities'];
foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result && $result->num_rows > 0) {
        echo "✅ Table '$table' exists<br>";
    } else {
        echo "❌ Table '$table' does NOT exist<br>";
    }
}

// Test 2: Count records
echo "<br><h3>2. Record Counts</h3>";
$counts = [
    'countries' => "SELECT COUNT(*) as count FROM countries",
    'states' => "SELECT COUNT(*) as count FROM states",
    'districts' => "SELECT COUNT(*) as count FROM districts",
    'places' => "SELECT COUNT(*) as count FROM places WHERE status = 'active'",
    'place_activities' => "SELECT COUNT(*) as count FROM place_activities WHERE status = 'active'"
];

foreach ($counts as $name => $sql) {
    $result = $conn->query($sql);
    if ($result) {
        $row = $result->fetch_assoc();
        echo "$name: " . $row['count'] . " records<br>";
    }
}

// Test 3: Search for "Puri"
echo "<br><h3>3. Testing Search for 'Puri'</h3>";
$search_term = "Puri";
$search_param = "%{$search_term}%";
$sql = "SELECT p.*, d.name as city_name, s.name as state_name, c.name as country_name
        FROM places p
        JOIN districts d ON p.city_id = d.id
        JOIN states s ON d.state_id = s.id
        JOIN countries c ON s.country_id = c.id
        WHERE p.status = 'active' 
        AND (LOWER(d.name) LIKE LOWER(?) OR LOWER(s.name) LIKE LOWER(?) OR LOWER(c.name) LIKE LOWER(?) OR LOWER(p.name) LIKE LOWER(?))
        LIMIT 10";

$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("ssss", $search_param, $search_param, $search_param, $search_param);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    echo "❌ SQL Error: " . $conn->error . "<br>";
    $result = false;
}

if ($result) {
    echo "Found " . $result->num_rows . " places matching 'Puri':<br>";
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "- {$row['name']} in {$row['city_name']}, {$row['state_name']}<br>";
        }
    } else {
        echo "❌ No places found!<br>";
        echo "Checking if 'Puri' exists in districts table:<br>";
        $check = $conn->query("SELECT * FROM districts WHERE LOWER(name) LIKE LOWER('%puri%')");
        if ($check && $check->num_rows > 0) {
            echo "✅ Found districts with 'Puri':<br>";
            while ($d = $check->fetch_assoc()) {
                echo "- {$d['name']} (ID: {$d['id']})<br>";
                // Check if there are places in this district
                $places_check = $conn->query("SELECT COUNT(*) as cnt FROM places WHERE city_id = {$d['id']} AND status = 'active'");
                if ($places_check) {
                    $pc = $places_check->fetch_assoc();
                    echo "  → {$pc['cnt']} active places in this district<br>";
                }
            }
        } else {
            echo "❌ No districts found with 'Puri'<br>";
        }
    }
    if ($stmt) $stmt->close();
} else {
    echo "❌ Query failed!<br>";
}

// Test 4: Search for "India"
echo "<br><h3>4. Testing Search for 'India'</h3>";
$search_term = "India";
$search_param = "%{$search_term}%";
$sql = "SELECT p.*, d.name as city_name, s.name as state_name, c.name as country_name
        FROM places p
        JOIN districts d ON p.city_id = d.id
        JOIN states s ON d.state_id = s.id
        JOIN countries c ON s.country_id = c.id
        WHERE p.status = 'active' 
        AND (LOWER(d.name) LIKE LOWER(?) OR LOWER(s.name) LIKE LOWER(?) OR LOWER(c.name) LIKE LOWER(?) OR LOWER(p.name) LIKE LOWER(?))
        LIMIT 10";

$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("ssss", $search_param, $search_param, $search_param, $search_param);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result) {
        echo "Found " . $result->num_rows . " places matching 'India':<br>";
        if ($result->num_rows > 0) {
            $count = 0;
            while ($row = $result->fetch_assoc()) {
                if ($count >= 5) break;
                echo "- {$row['name']} in {$row['city_name']}, {$row['state_name']}<br>";
                $count++;
            }
            if ($result->num_rows > 5) {
                echo "... and " . ($result->num_rows - 5) . " more<br>";
            }
        } else {
            echo "❌ No places found!<br>";
        }
    }
    $stmt->close();
} else {
    echo "❌ SQL Error: " . $conn->error . "<br>";
}

echo "<br><h3>✅ Test Complete</h3>";
echo "<a href='index.php'>Back to Home</a>";
?>

