<?php
$pageTitle = 'TravelApp - Premium Travel Experience';
$pageCSS = 'index.css';
include 'includes/head.php';
include 'includes/navbar.php';
include 'includes/overlay.php';
include 'includes/sidebar.php';
?>

<section class="relative w-full max-w-7xl mx-auto mt-20 overflow-hidden slider-container">

    <!-- Slider Wrapper -->
    <div id="slider" class="relative h-[220px] md:h-[360px]">

        <!-- Slide 1 -->
        <div class="slide slide-fade absolute inset-0 opacity-100">
            <div class="slider-image w-full h-full">
                <img src="https://images.unsplash.com/photo-1501785888041-af3ef285b470"
                    class="w-full h-full object-cover" alt="">
            </div>
        </div>

        <!-- Slide 2 -->
        <div class="slide slide-fade absolute inset-0 opacity-0">
            <div class="slider-image w-full h-full">
                <img src="https://images.unsplash.com/photo-1500530855697-b586d89ba3ee"
                    class="w-full h-full object-cover" alt="">
            </div>
        </div>

        <!-- Slide 3 -->
        <div class="slide slide-fade absolute inset-0 opacity-0">
            <div class="slider-image w-full h-full">
                <img src="https://images.unsplash.com/photo-1507525428034-b723cf961d3e"
                    class="w-full h-full object-cover" alt="">
            </div>
        </div>

    </div>

    <!-- DOTS -->
    <div class="absolute bottom-6 left-1/2 -translate-x-1/2 flex gap-3 z-10">
        <span class="dot slider-dot active w-2.5 h-2.5 bg-white rounded-full cursor-pointer"></span>
        <span class="dot slider-dot w-2.5 h-2.5 bg-white/50 rounded-full cursor-pointer"></span>
        <span class="dot slider-dot w-2.5 h-2.5 bg-white/50 rounded-full cursor-pointer"></span>
    </div>

</section>


<!-- SEARCH SECTION -->
<section class="relative -mt-12 z-20">
    <div class="max-w-5xl mx-auto px-6">

        <div class="bg-white rounded-3xl search-container border border-gray-100 p-6 md:p-8">
            <form class="grid grid-cols-1 md:grid-cols-4 gap-5" action="search.php" method="GET">

                <!-- Destination -->
                <div class="md:col-span-3">
                    <label class="text-sm font-medium text-gray-700 mb-2 block">Destination</label>
                    <input type="text" name="destination" placeholder="Search places, states, cities"
                        class="search-input w-full px-5 py-4 rounded-2xl border-2 border-gray-200 focus:border-black focus:ring-0 focus:outline-none transition-all duration-200 text-gray-900 placeholder-gray-400" />
                </div>

                <!-- Search Button -->
                <div class="flex items-end">
                    <button type="submit"
                        class="premium-button w-full py-4 rounded-2xl bg-black text-white font-semibold hover:bg-gray-900 transition-all duration-200 shadow-lg hover:shadow-xl relative overflow-hidden">
                        Search
                    </button>
                </div>

            </form>
        </div>

    </div>
</section>



<!-- BEST ACTIVITIES -->
<section class="mt-12">
    <div class="max-w-7xl mx-auto px-6">

        <!-- Section Title -->
        <div class="mb-6">
            <h2 class="section-title text-2xl font-bold text-gray-900 mb-1">Popular Activities</h2>
            <p class="section-subtitle text-sm text-gray-500">Discover amazing experiences</p>
        </div>

        <!-- Horizontal Scroll -->
        <div class="flex overflow-x-auto scroll-smooth pb-4 gap-4 custom-scrollbar" style="padding-bottom: 20px;" >

            <!-- ACTIVITY CARD -->
            <button class="activity-card flex flex-col items-center min-w-[100px] focus:outline-none">
                <div class="w-20 h-20 rounded-2xl overflow-hidden mb-3 shadow-lg ring-2 ring-gray-100">
                    <img src="https://images.unsplash.com/photo-1500530855697-b586d89ba3ee"
                        class="activity-image w-full h-full object-cover">
                </div>
                <span class="text-xs font-semibold text-gray-800 text-center">
                    Trekking
                </span>
            </button>

            <!-- ACTIVITY CARD -->
            <button class="activity-card flex flex-col items-center min-w-[100px]">
                <div class="w-20 h-20 rounded-2xl overflow-hidden mb-3 shadow-lg ring-2 ring-gray-100">
                    <img src="https://images.unsplash.com/photo-1507525428034-b723cf961d3e"
                        class="activity-image w-full h-full object-cover">
                </div>
                <span class="text-xs font-semibold text-gray-800 text-center">
                    Boating
                </span>
            </button>

            <!-- ACTIVITY CARD -->
            <button class="activity-card flex flex-col items-center min-w-[100px]">
                <div class="w-20 h-20 rounded-2xl overflow-hidden mb-3 shadow-lg ring-2 ring-gray-100">
                    <img src="https://images.unsplash.com/photo-1501785888041-af3ef285b470"
                        class="activity-image w-full h-full object-cover">
                </div>
                <span class="text-xs font-semibold text-gray-800 text-center">
                    Camping
                </span>
            </button>

            <!-- ACTIVITY CARD -->
            <button class="activity-card flex flex-col items-center min-w-[100px]">
                <div class="w-20 h-20 rounded-2xl overflow-hidden mb-3 shadow-lg ring-2 ring-gray-100">
                    <img src="https://images.unsplash.com/photo-1500534314209-a25ddb2bd429"
                        class="activity-image w-full h-full object-cover">
                </div>
                <span class="text-xs font-semibold text-gray-800 text-center">
                    Beach
                </span>
            </button>

            <!-- ACTIVITY CARD -->
            <button class="activity-card flex flex-col items-center min-w-[100px]">
                <div class="w-20 h-20 rounded-2xl overflow-hidden mb-3 shadow-lg ring-2 ring-gray-100">
                    <img src="https://images.unsplash.com/photo-1500530855697-b586d89ba3ee"
                        class="activity-image w-full h-full object-cover">
                </div>
                <span class="text-xs font-semibold text-gray-800 text-center">
                    Trekking
                </span>
            </button>

            <!-- ACTIVITY CARD -->
            <button class="activity-card flex flex-col items-center min-w-[100px]">
                <div class="w-20 h-20 rounded-2xl overflow-hidden mb-3 shadow-lg ring-2 ring-gray-100">
                    <img src="https://images.unsplash.com/photo-1507525428034-b723cf961d3e"
                        class="activity-image w-full h-full object-cover">
                </div>
                <span class="text-xs font-semibold text-gray-800 text-center">
                    Boating
                </span>
            </button>

            <!-- ACTIVITY CARD -->
            <button class="activity-card flex flex-col items-center min-w-[100px]">
                <div class="w-20 h-20 rounded-2xl overflow-hidden mb-3 shadow-lg ring-2 ring-gray-100">
                    <img src="https://images.unsplash.com/photo-1501785888041-af3ef285b470"
                        class="activity-image w-full h-full object-cover">
                </div>
                <span class="text-xs font-semibold text-gray-800 text-center">
                    Camping
                </span>
            </button>

            <!-- ACTIVITY CARD -->
            <button class="activity-card flex flex-col items-center min-w-[100px]">
                <div class="w-20 h-20 rounded-2xl overflow-hidden mb-3 shadow-lg ring-2 ring-gray-100">
                    <img src="https://images.unsplash.com/photo-1500534314209-a25ddb2bd429"
                        class="activity-image w-full h-full object-cover">
                </div>
                <span class="text-xs font-semibold text-gray-800 text-center">
                    Beach
                </span>
            </button>

        </div>
    </div>
</section>




<!-- BEST PLACES THIS MONTH -->
<section class="mt-16 mb-12">
    <div class="max-w-7xl mx-auto px-6">

        <!-- Section Title -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="section-title text-2xl font-bold text-gray-900 mb-1">
                    Visit This Month
                </h2>
                <p class="section-subtitle text-sm text-gray-500">Top destinations trending now</p>
            </div>
            <a href="#" class="premium-link text-sm font-semibold text-gray-700 hover:text-black transition-colors duration-200">
                View all →
            </a>
        </div>

        <!-- Horizontal Scroll -->
        <div id="monthScroll" class="flex gap-4 overflow-x-auto scroll-smooth snap-x snap-mandatory pb-4 custom-scrollbar">

            <!-- CARD TEMPLATE -->
            <div class="place-card card-hover-effect min-w-[260px] snap-start bg-white rounded-3xl shadow-lg border border-gray-100 overflow-hidden">
                <div class="relative h-40 overflow-hidden">
                    <img src="https://images.unsplash.com/photo-1548013146-72479768bada"
                        class="place-image absolute inset-0 w-full h-full object-cover" />
                    <button class="heart-button absolute top-3 right-3 bg-white/90 p-2 rounded-xl shadow-md">
                        <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                        </svg>
                    </button>
                </div>

                <div class="p-4">
                    <h3 class="text-base font-bold text-gray-900 mb-1">Shillong</h3>
                    <p class="text-xs text-gray-500 mb-3 font-medium">Meghalaya</p>

                    <div class="flex flex-wrap gap-2">
                        <span class="tag-badge px-3 py-1 text-xs font-medium bg-gray-100 text-gray-700 rounded-full">Nature</span>
                        <span class="tag-badge px-3 py-1 text-xs font-medium bg-gray-100 text-gray-700 rounded-full">Hill</span>
                    </div>
                </div>
            </div>

            <!-- CARD -->
            <div class="place-card card-hover-effect min-w-[260px] snap-start bg-white rounded-3xl shadow-lg border border-gray-100 overflow-hidden">
                <div class="relative h-40 overflow-hidden">
                    <img src="https://images.unsplash.com/photo-1589182373726-e4f658ab50f0"
                        class="place-image absolute inset-0 w-full h-full object-cover" />
                    <button class="heart-button absolute top-3 right-3 bg-white/90 p-2 rounded-xl shadow-md">
                        <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                        </svg>
                    </button>
                </div>
                <div class="p-4">
                    <h3 class="text-base font-bold text-gray-900 mb-1">Jaipur</h3>
                    <p class="text-xs text-gray-500 mb-3 font-medium">Rajasthan</p>
                    <div class="flex gap-2">
                        <span class="tag-badge px-3 py-1 text-xs font-medium bg-gray-100 text-gray-700 rounded-full">Heritage</span>
                        <span class="tag-badge px-3 py-1 text-xs font-medium bg-gray-100 text-gray-700 rounded-full">Forts</span>
                    </div>
                </div>
            </div>

            <!-- CARD -->
            <div class="place-card card-hover-effect min-w-[260px] snap-start bg-white rounded-3xl shadow-lg border border-gray-100 overflow-hidden">
                <div class="relative h-40 overflow-hidden">
                    <img src="https://images.unsplash.com/photo-1593693397690-362cb9666fc2"
                        class="place-image absolute inset-0 w-full h-full object-cover" />
                    <button class="heart-button absolute top-3 right-3 bg-white/90 p-2 rounded-xl shadow-md">
                        <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                        </svg>
                    </button>
                </div>
                <div class="p-4">
                    <h3 class="text-base font-bold text-gray-900 mb-1">Kaziranga</h3>
                    <p class="text-xs text-gray-500 mb-3 font-medium">Assam</p>
                    <div class="flex gap-2">
                        <span class="tag-badge px-3 py-1 text-xs font-medium bg-gray-100 text-gray-700 rounded-full">Wildlife</span>
                        <span class="tag-badge px-3 py-1 text-xs font-medium bg-gray-100 text-gray-700 rounded-full">Safari</span>
                    </div>
                </div>
            </div>
            <!-- CARD TEMPLATE -->
            <div class="place-card card-hover-effect min-w-[260px] snap-start bg-white rounded-3xl shadow-lg border border-gray-100 overflow-hidden">
                <div class="relative h-40 overflow-hidden">
                    <img src="https://images.unsplash.com/photo-1548013146-72479768bada"
                        class="place-image absolute inset-0 w-full h-full object-cover" />
                    <button class="heart-button absolute top-3 right-3 bg-white/90 p-2 rounded-xl shadow-md">
                        <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                        </svg>
                    </button>
                </div>

                <div class="p-4">
                    <h3 class="text-base font-bold text-gray-900 mb-1">Shillong</h3>
                    <p class="text-xs text-gray-500 mb-3 font-medium">Meghalaya</p>

                    <div class="flex flex-wrap gap-2">
                        <span class="tag-badge px-3 py-1 text-xs font-medium bg-gray-100 text-gray-700 rounded-full">Nature</span>
                        <span class="tag-badge px-3 py-1 text-xs font-medium bg-gray-100 text-gray-700 rounded-full">Hill</span>
                    </div>
                </div>
            </div>

            <!-- CARD -->
            <div class="place-card card-hover-effect min-w-[260px] snap-start bg-white rounded-3xl shadow-lg border border-gray-100 overflow-hidden">
                <div class="relative h-40 overflow-hidden">
                    <img src="https://images.unsplash.com/photo-1589182373726-e4f658ab50f0"
                        class="place-image absolute inset-0 w-full h-full object-cover" />
                    <button class="heart-button absolute top-3 right-3 bg-white/90 p-2 rounded-xl shadow-md">
                        <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                        </svg>
                    </button>
                </div>
                <div class="p-4">
                    <h3 class="text-base font-bold text-gray-900 mb-1">Jaipur</h3>
                    <p class="text-xs text-gray-500 mb-3 font-medium">Rajasthan</p>
                    <div class="flex gap-2">
                        <span class="tag-badge px-3 py-1 text-xs font-medium bg-gray-100 text-gray-700 rounded-full">Heritage</span>
                        <span class="tag-badge px-3 py-1 text-xs font-medium bg-gray-100 text-gray-700 rounded-full">Forts</span>
                    </div>
                </div>
            </div>

            <!-- CARD -->
            <div class="place-card card-hover-effect min-w-[260px] snap-start bg-white rounded-3xl shadow-lg border border-gray-100 overflow-hidden">
                <div class="relative h-40 overflow-hidden">
                    <img src="https://images.unsplash.com/photo-1593693397690-362cb9666fc2"
                        class="place-image absolute inset-0 w-full h-full object-cover" />
                    <button class="heart-button absolute top-3 right-3 bg-white/90 p-2 rounded-xl shadow-md">
                        <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                        </svg>
                    </button>
                </div>
                <div class="p-4">
                    <h3 class="text-base font-bold text-gray-900 mb-1">Kaziranga</h3>
                    <p class="text-xs text-gray-500 mb-3 font-medium">Assam</p>
                    <div class="flex gap-2">
                        <span class="tag-badge px-3 py-1 text-xs font-medium bg-gray-100 text-gray-700 rounded-full">Wildlife</span>
                        <span class="tag-badge px-3 py-1 text-xs font-medium bg-gray-100 text-gray-700 rounded-full">Safari</span>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<script>
    const slider = document.getElementById("slider");
    const slides = document.querySelectorAll(".slide");
    const dots = document.querySelectorAll(".dot");
    let index = 0;
    const totalSlides = slides.length;

    function showSlide(i) {
        // Hide all slides
        slides.forEach((slide, idx) => {
            if (idx === i) {
                slide.style.opacity = "1";
            } else {
                slide.style.opacity = "0";
            }
        });

        // Update dots
        dots.forEach((dot, idx) => {
            if (idx === i) {
                dot.classList.remove("bg-white/50");
                dot.classList.add("bg-white", "active");
                dot.style.transform = "scale(1.3)";
            } else {
                dot.classList.remove("bg-white", "active");
                dot.classList.add("bg-white/50");
                dot.style.transform = "scale(1)";
            }
        });
    }

    function autoSlide() {
        index = (index + 1) % totalSlides;
        showSlide(index);
    }

    dots.forEach((dot, i) => {
        dot.addEventListener("click", () => {
            index = i;
            showSlide(index);
        });
    });

    // Initialize first slide
    showSlide(0);
    setInterval(autoSlide, 4000);
</script>

<script src="includes/mobile-menu.js"></script>

</body>

</html>

