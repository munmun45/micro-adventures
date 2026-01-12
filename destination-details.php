<?php
require_once 'config.php';
$conn = getDBConnection();

$place_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($place_id <= 0) {
    header('Location: index.php');
    exit;
}

// Get place details
$sql = "SELECT p.*, p.ticket_price_min, p.ticket_price_max, d.name as city_name, d.id as city_id, s.name as state_name, s.id as state_id, c.name as country_name
        FROM places p
        JOIN districts d ON p.city_id = d.id
        JOIN states s ON d.state_id = s.id
        JOIN countries c ON s.country_id = c.id
        WHERE p.id = ? AND p.status = 'active'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $place_id);
$stmt->execute();
$result = $stmt->get_result();
$place = $result->fetch_assoc();
$stmt->close();

if (!$place) {
    header('Location: index.php');
    exit;
}

// Get activities for this place
$activities_sql = "SELECT pa.*, at.name as activity_name, at.image as activity_image, at.description as activity_description
                   FROM place_activities pa
                   JOIN activity_types at ON pa.activity_type_id = at.id
                   WHERE pa.place_id = ? AND pa.status = 'active' AND at.status = 'active'
                   ORDER BY pa.priority_level DESC";
$activities_stmt = $conn->prepare($activities_sql);
$activities_stmt->bind_param("i", $place_id);
$activities_stmt->execute();
$activities_result = $activities_stmt->get_result();
$activities = [];
while ($row = $activities_result->fetch_assoc()) {
    $activities[] = $row;
}
$activities_stmt->close();

$pageTitle = $place['name'] . ' - TravelApp';
$pageCSS = 'destination-details.css';
include 'includes/head.php';
include 'includes/navbar.php';
include 'includes/overlay.php';
include 'includes/sidebar.php';
?>

<!-- Place Header -->
<div class="pt-14 sm:pt-16">
    <div class="relative h-48 sm:h-64 md:h-80 overflow-hidden">
        <img src="<?php echo htmlspecialchars($place['image'] ?: 'https://images.unsplash.com/photo-1501785888041-af3ef285b470'); ?>" 
             alt="<?php echo htmlspecialchars($place['name']); ?>"
             class="w-full h-full object-cover">
        <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
        <div class="absolute bottom-0 left-0 right-0 p-4 sm:p-6 text-white">
            <a href="search.php?city=<?php echo $place['city_id']; ?>" class="text-sm text-white/80 hover:text-white mb-2 inline-flex items-center gap-1">
                <i class="bi bi-arrow-left"></i> Back to <?php echo htmlspecialchars($place['city_name']); ?>
            </a>
            <h1 class="text-2xl sm:text-3xl font-bold mb-2"><?php echo htmlspecialchars($place['name']); ?></h1>
            <p class="text-sm sm:text-base text-white/90">
                <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($place['city_name']); ?>, <?php echo htmlspecialchars($place['state_name']); ?>
            </p>
        </div>
    </div>
</div>

<!-- Place Details -->
<section class="max-w-6xl mx-auto px-4 py-6 space-y-6 pb-20">
    <!-- Quick Info Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="bg-white rounded-xl p-3 sm:p-4 border border-gray-100 shadow-sm">
            <div class="text-xs text-gray-500 mb-1">Safety Level</div>
            <div class="text-sm sm:text-base font-bold text-gray-900"><?php echo htmlspecialchars($place['safety_level'] ?: 'Medium'); ?></div>
        </div>
        <div class="bg-white rounded-xl p-3 sm:p-4 border border-gray-100 shadow-sm">
            <div class="text-xs text-gray-500 mb-1">Visit Time</div>
            <div class="text-sm sm:text-base font-bold text-gray-900"><?php echo $place['avg_visit_time_hours'] ?: '2-3'; ?> hrs</div>
        </div>
        <div class="bg-white rounded-xl p-3 sm:p-4 border border-gray-100 shadow-sm">
            <div class="text-xs text-gray-500 mb-1">Best Time</div>
            <div class="text-sm sm:text-base font-bold text-gray-900"><?php echo ucfirst($place['best_time_of_day'] ?: 'Anytime'); ?></div>
        </div>
        <div class="bg-white rounded-xl p-3 sm:p-4 border border-gray-100 shadow-sm">
            <div class="text-xs text-gray-500 mb-1">Ticket Price</div>
            <div class="text-sm sm:text-base font-bold text-gray-900">
                <?php 
                $price_min = $place['ticket_price_min'] ?? 0;
                $price_max = $place['ticket_price_max'] ?? 0;
                if ($price_min == 0 && $price_max == 0) {
                    echo '<span class="text-green-600"><i class="bi bi-check-circle"></i> FREE</span>';
                } elseif ($price_min == $price_max) {
                    echo '<i class="bi bi-ticket-perforated text-purple-600"></i> ₹' . number_format($price_min, 0);
                } else {
                    echo '<i class="bi bi-ticket-perforated text-purple-600"></i> ₹' . number_format($price_min, 0) . ' - ₹' . number_format($price_max, 0);
                }
                ?>
            </div>
        </div>
    </div>

    <!-- Description -->
    <?php if (!empty($place['description'])): ?>
    <div class="bg-white rounded-xl p-4 sm:p-6 border border-gray-100 shadow-sm">
        <h2 class="text-lg sm:text-xl font-bold text-gray-900 mb-3">About</h2>
        <p class="text-sm sm:text-base text-gray-700 leading-relaxed"><?php echo nl2br(htmlspecialchars($place['description'])); ?></p>
    </div>
    <?php endif; ?>

    <!-- Safety Information -->
    <?php if (!empty($place['security_info'])): ?>
    <div class="bg-white rounded-xl p-4 sm:p-6 border border-gray-100 shadow-sm">
        <h2 class="text-lg sm:text-xl font-bold text-gray-900 mb-3 flex items-center gap-2">
            <i class="bi bi-shield-check text-[#009688]"></i> Safety Information
        </h2>
        <p class="text-sm sm:text-base text-gray-700 leading-relaxed"><?php echo nl2br(htmlspecialchars($place['security_info'])); ?></p>
        <?php if (!empty($place['night_travel_safety'])): ?>
            <div class="mt-3 p-3 bg-yellow-50 rounded-lg border border-yellow-200">
                <p class="text-xs sm:text-sm font-semibold text-yellow-800 mb-1">Night Travel:</p>
                <p class="text-xs sm:text-sm text-yellow-700"><?php echo htmlspecialchars($place['night_travel_safety']); ?></p>
            </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Activities -->
    <?php if (!empty($activities)): ?>
    <div class="bg-white rounded-xl p-4 sm:p-6 border border-gray-100 shadow-sm">
        <h2 class="text-lg sm:text-xl font-bold text-gray-900 mb-4">Available Activities</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <?php foreach ($activities as $activity): ?>
            <div class="border border-gray-200 rounded-xl p-4 hover:shadow-md transition-all">
                <div class="flex items-start gap-3">
                    <img src="<?php echo htmlspecialchars($activity['activity_image']); ?>" 
                         alt="<?php echo htmlspecialchars($activity['activity_name']); ?>"
                         class="w-16 h-16 rounded-lg object-cover">
                    <div class="flex-1">
                        <h3 class="font-bold text-gray-900 mb-1 text-sm sm:text-base"><?php echo htmlspecialchars($activity['activity_name']); ?></h3>
                        <?php if ($activity['open_time'] && $activity['close_time']): ?>
                            <p class="text-xs text-gray-600 mb-1">
                                <i class="bi bi-clock"></i> <?php echo date('g:i A', strtotime($activity['open_time'])); ?> - <?php echo date('g:i A', strtotime($activity['close_time'])); ?>
                            </p>
                        <?php endif; ?>
                        <?php if ($activity['ticket_price']): ?>
                            <p class="text-xs font-semibold text-[#009688] mb-1">Price: <?php echo htmlspecialchars($activity['ticket_price']); ?></p>
                        <?php endif; ?>
                        <?php if ($activity['safety_info']): ?>
                            <p class="text-xs text-gray-500 mt-2"><?php echo htmlspecialchars(substr($activity['safety_info'], 0, 100)); ?>...</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Additional Info -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <?php if ($place['child_friendly']): ?>
        <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm">
            <div class="flex items-center gap-2 mb-2">
                <i class="bi bi-<?php echo $place['child_friendly'] == 'yes' ? 'check-circle-fill text-green-500' : 'x-circle-fill text-red-500'; ?>"></i>
                <span class="font-semibold text-gray-900 text-sm">Child Friendly</span>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($place['senior_friendly']): ?>
        <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm">
            <div class="flex items-center gap-2 mb-2">
                <i class="bi bi-<?php echo $place['senior_friendly'] == 'yes' ? 'check-circle-fill text-green-500' : 'x-circle-fill text-red-500'; ?>"></i>
                <span class="font-semibold text-gray-900 text-sm">Senior Friendly</span>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>

