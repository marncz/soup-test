function toggleNavigation() {

    var navbar_overlay = document.getElementById("overlay");

    if (navbar_overlay.style.width == "100%") {
        document.body.classList.remove('lock-overlay-view');

        document.getElementById('menu-open').style.display = "block";
        document.getElementById('menu-close').style.display = "none";

        document.getElementById('overlay__content').style.display = "none";
        document.getElementById('overlay__content').style.opacity = 0;
        navbar_overlay.style.width = "0%";

    } else {
        document.body.classList.add('lock-overlay-view');

        document.getElementById('menu-open').style.display = "none";
        document.getElementById('menu-close').style.display = "block";

        document.getElementById('overlay__content').style.display = "block";
        document.getElementById('overlay__content').style.opacity = 1;
        navbar_overlay.style.width = "100%";
    }

}
