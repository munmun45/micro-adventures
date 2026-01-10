// Mobile Menu Script - Common for all pages
document.addEventListener('DOMContentLoaded', function() {
    const openMenu = document.getElementById("openMenu");
    const closeMenu = document.getElementById("closeMenu");
    const sideMenu = document.getElementById("sideMenu");
    const overlay = document.getElementById("overlay");

    if (openMenu && closeMenu && sideMenu && overlay) {
        openMenu.addEventListener("click", () => {
            sideMenu.classList.remove("-translate-x-full");
            overlay.classList.remove("opacity-0", "pointer-events-none");
            document.body.classList.add("overflow-hidden");
        });

        function closeSideMenu() {
            sideMenu.classList.add("-translate-x-full");
            overlay.classList.add("opacity-0", "pointer-events-none");
            document.body.classList.remove("overflow-hidden");
        }

        closeMenu.addEventListener("click", closeSideMenu);
        overlay.addEventListener("click", closeSideMenu);
    }
});

