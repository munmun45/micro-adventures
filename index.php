<?php
require_once 'config.php';
$pageTitle = 'TravelApp - Discover Amazing Places';
$pageCSS = 'index.css';
include 'includes/head.php';
include 'includes/navbar.php';
include 'includes/overlay.php';
include 'includes/sidebar.php';

// Get popular activities for display
$conn = getDBConnection();
$activities_query = "SELECT * FROM activity_types WHERE status = 'active' ORDER BY id LIMIT 8";
$activities_result = $conn->query($activities_query);
$activities = [];
if ($activities_result) {
    while ($row = $activities_result->fetch_assoc()) {
        $activities[] = $row;
    }
}
?>

<!-- Hero Section with Search -->
<section class="relative w-full mt-14 sm:mt-16">
    <div class="relative h-[200px] sm:h-[280px] md:h-[360px] overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-br from-[#009688] to-[#00796b]">
            <img src="https://images.unsplash.com/photo-1501785888041-af3ef285b470" 
                 class="w-full h-full object-cover opacity-30" alt="Travel">
        </div>
        <div class="absolute inset-0 flex items-center justify-center px-4">
            <div class="text-center text-white">
                <h1 class="text-2xl sm:text-3xl md:text-4xl font-bold mb-2">Discover Amazing Places</h1>
                <p class="text-sm sm:text-base text-white/90">Explore India's beautiful destinations</p>
            </div>
        </div>
    </div>
</section>

<!-- Advanced Search Section -->
<section class="relative -mt-8 sm:-mt-12 z-20 px-4">
    <div class="max-w-5xl mx-auto">
        <div class="bg-white rounded-2xl shadow-xl border border-gray-100 p-4 sm:p-6 md:p-8">
            <form action="search.php" method="GET">
                <!-- Main Search Input -->
                <div class="relative">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="bi bi-search mr-1"></i> Search Places, Cities, States, or Activities
                    </label>
                    <div class="flex gap-2">
                        <input type="text" 
                               name="q" 
                               id="searchInput"
                               placeholder="e.g., 'Boating in Puri', 'Goa', 'Trekking in Munnar', 'Odisha'"
                               value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>"
                               class="flex-1 px-4 py-3 sm:py-3.5 rounded-xl border-2 border-gray-200 focus:border-[#009688] focus:ring-2 focus:ring-[#009688]/20 focus:outline-none transition-all text-gray-900 placeholder-gray-400 text-sm sm:text-base"
                               autocomplete="off">
                        <button type="submit" 
                                class="px-6 sm:px-8 py-3 sm:py-3.5 rounded-xl bg-gradient-to-r from-[#009688] to-[#00796b] text-white font-semibold shadow-lg hover:shadow-xl transition-all duration-200 flex items-center justify-center gap-2 whitespace-nowrap">
                            <i class="bi bi-search"></i>
                            <span class="hidden sm:inline">Search</span>
                        </button>
                    </div>
                    <div id="searchSuggestions" class="absolute z-30 w-full mt-1 bg-white rounded-xl shadow-lg border border-gray-200 hidden max-h-60 overflow-y-auto"></div>
                </div>
            </form>
        </div>
    </div>
</section>

<!-- Popular Activities -->
<?php if (!empty($activities)): ?>
<section class="mt-8 sm:mt-12 px-4">
    <div class="max-w-7xl mx-auto">
        <div class="mb-4 sm:mb-6">
            <h2 class="text-xl sm:text-2xl font-bold text-gray-900 mb-1">Popular Activities</h2>
            <p class="text-xs sm:text-sm text-gray-500">Discover amazing experiences</p>
        </div>
        <div class="flex overflow-x-auto gap-3 sm:gap-4 pb-4 scrollbar-hide" style="scrollbar-width: none; -ms-overflow-style: none;">
            <?php foreach ($activities as $activity): ?>
            <a href="search.php?activity=<?php echo $activity['id']; ?>" 
               class="flex flex-col items-center min-w-[90px] sm:min-w-[110px] p-3 sm:p-4 bg-white rounded-xl shadow-sm hover:shadow-md transition-all border border-gray-100 group">
                <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-xl overflow-hidden mb-2 sm:mb-3 shadow-md ring-2 ring-gray-100 group-hover:ring-[#009688] transition-all">
                    <img src="<?php echo htmlspecialchars($activity['image']); ?>" 
                         alt="<?php echo htmlspecialchars($activity['name']); ?>"
                         class="w-full h-full object-cover">
                </div>
                <span class="text-xs sm:text-sm font-semibold text-gray-800 text-center"><?php echo htmlspecialchars($activity['name']); ?></span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Best Places This Month -->
<section class="mt-8 sm:mt-12 px-4 pb-12">
    <div class="max-w-7xl mx-auto">
        <div class="mb-4 sm:mb-6">
            <h2 class="text-xl sm:text-2xl font-bold text-gray-900 mb-1">Best Places This Month</h2>
            <p class="text-xs sm:text-sm text-gray-500">Perfect time to visit these destinations</p>
        </div>
        <div id="bestPlacesContainer" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <!-- Places will be loaded via AJAX -->
        </div>
    </div>
</section>

<script>
// Load best places for current month
document.addEventListener('DOMContentLoaded', function() {
    fetch('api/get-best-places.php')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.places) {
                const container = document.getElementById('bestPlacesContainer');
                if (data.places.length === 0) {
                    container.innerHTML = '<p class="text-gray-500 text-sm col-span-full text-center py-8">No places found for this month</p>';
                } else {
                    container.innerHTML = data.places.map(place => `
                        <a href="search.php?city=${place.city_id}&place=${place.id}" 
                           class="bg-white rounded-xl shadow-sm hover:shadow-md transition-all overflow-hidden border border-gray-100 group">
                            <div class="relative h-40 sm:h-48 overflow-hidden">
                                <img src="${place.image}" alt="${place.name}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                <div class="absolute top-2 right-2 bg-[#009688] text-white text-xs font-semibold px-2 py-1 rounded-lg">
                                    Best Now
                                </div>
                            </div>
                            <div class="p-4">
                                <h3 class="font-bold text-gray-900 mb-1 text-sm sm:text-base">${place.name}</h3>
                                <p class="text-xs text-gray-600 mb-2">${place.city_name}, ${place.state_name}</p>
                                <div class="flex items-center gap-2 text-xs text-gray-500">
                                    <span><i class="bi bi-clock"></i> ${place.avg_visit_time_hours || '2-3'} hrs</span>
                                    <span><i class="bi bi-shield-check"></i> ${place.safety_level || 'High'}</span>
                                </div>
                            </div>
                        </a>
                    `).join('');
                }
            }
        })
        .catch(error => {
            console.error('Error loading best places:', error);
            document.getElementById('bestPlacesContainer').innerHTML = '<p class="text-gray-500 text-sm col-span-full text-center py-8">Unable to load places</p>';
        });
});
</script>

<?php include 'includes/footer.php'; ?>

