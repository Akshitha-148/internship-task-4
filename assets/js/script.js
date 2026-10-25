
document.addEventListener("DOMContentLoaded", function () {
    // Smooth scrolling for links to sections on this page
    document.querySelectorAll('a[href^="#"]').forEach(function (link) {
        link.addEventListener("click", function (event) {
            const targetId = this.getAttribute("href");

            if (targetId && targetId.length > 1) {
                const target = document.querySelector(targetId);

                if (target) {
                    event.preventDefault();
                    target.scrollIntoView({
                        behavior: "smooth",
                        block: "start"
                    });
                }
            }
        });
    });

    // Close the mobile navigation after selecting a section
    document.querySelectorAll(".bookstore-nav .nav-link").forEach(function (link) {
        link.addEventListener("click", function () {
            const nav = document.getElementById("mainNav");

            if (nav && nav.classList.contains("show") &&
                window.bootstrap) {
                window.bootstrap.Collapse.getOrCreateInstance(nav).hide();
            }
        });
    });

    // Reveal cards as the page loads
    document.querySelectorAll(".benefit-card, .book-card").forEach(function (card) {
        card.style.opacity = "0";

        requestAnimationFrame(function () {
            card.style.transition = "opacity 0.5s ease";
            card.style.opacity = "1";
        });
    });

    console.log("BookNest website loaded successfully.");
});