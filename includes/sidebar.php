<!-- SLIDE MENU -->
<div id="sideMenu" class="side-menu fixed top-0 left-0 h-full w-72 bg-white z-50 transform -translate-x-full transition-transform duration-300 ease-in-out shadow-2xl border-r border-gray-100">
    <div class="flex items-center justify-between px-4 sm:px-6 h-14 sm:h-16 border-b border-gray-100">
        <span class="text-lg font-bold text-gray-900">Menu</span>
        <button id="closeMenu" class="p-2 rounded-xl">
            <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>
    <div class="flex flex-col p-4 sm:p-6 gap-2">
        <a href="index.php" class="menu-item text-gray-700 font-semibold py-3 px-4 rounded-xl transition-colors hover:bg-gray-100">Home</a>
        <a href="#" class="menu-item text-gray-700 font-semibold py-3 px-4 rounded-xl transition-colors hover:bg-gray-100">Explore</a>
    </div>
</div>

<script>
// Mobile Menu Toggle
document.getElementById('openMenu')?.addEventListener('click', function() {
    document.getElementById('sideMenu').classList.remove('-translate-x-full');
    document.getElementById('overlay').classList.remove('opacity-0', 'pointer-events-none');
    document.getElementById('overlay').classList.add('opacity-100', 'pointer-events-auto');
});

document.getElementById('closeMenu')?.addEventListener('click', function() {
    document.getElementById('sideMenu').classList.add('-translate-x-full');
    document.getElementById('overlay').classList.remove('opacity-100', 'pointer-events-auto');
    document.getElementById('overlay').classList.add('opacity-0', 'pointer-events-none');
});

document.getElementById('overlay')?.addEventListener('click', function() {
    document.getElementById('sideMenu').classList.add('-translate-x-full');
    document.getElementById('overlay').classList.remove('opacity-100', 'pointer-events-auto');
    document.getElementById('overlay').classList.add('opacity-0', 'pointer-events-none');
});
</script>

