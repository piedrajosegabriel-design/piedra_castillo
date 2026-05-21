/* EdenAir — Index: navbar scroll-spy, scroll reveal, carousel, anchor offset */
document.addEventListener("DOMContentLoaded", function () {
    var reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    var navbar = document.querySelector(".ea-navbar");
    var navLinks = Array.prototype.slice.call(document.querySelectorAll(".ea-nav-links a[href^='#']"));

    /* -------- Navbar shrink on scroll -------- */
    function syncNavbarScrolled() {
        if (!navbar) return;
        navbar.classList.toggle("is-scrolled", window.scrollY > 14);
    }
    syncNavbarScrolled();
    window.addEventListener("scroll", syncNavbarScrolled, { passive: true });

    /* -------- Anchor scroll respeta navbar -------- */
    function getNavbarOffset() {
        if (!navbar) return 80;
        return navbar.getBoundingClientRect().height + 12;
    }

    function scrollToTarget(target) {
        if (!target) return;
        var rect = target.getBoundingClientRect();
        var top = window.scrollY + rect.top - getNavbarOffset();
        if (top < 0) top = 0;
        window.scrollTo({ top: top, behavior: reducedMotion ? "auto" : "smooth" });
    }

    document.addEventListener("click", function (event) {
        var link = event.target.closest && event.target.closest('a[href^="#"]');
        if (!link) return;
        var href = link.getAttribute("href");
        if (!href || href.length < 2) return;
        var target = document.getElementById(href.slice(1));
        if (!target) return;
        event.preventDefault();
        scrollToTarget(target);
        if (history.replaceState) history.replaceState(null, "", href);
    });

    /* -------- Scroll spy en navbar -------- */
    var sections = navLinks
        .map(function (l) {
            var id = l.getAttribute("href").slice(1);
            var el = document.getElementById(id);
            return el ? { id: id, el: el, link: l } : null;
        })
        .filter(Boolean);

    function setActiveLink(hash) {
        navLinks.forEach(function (l) {
            l.classList.toggle("is-active", l.getAttribute("href") === hash);
        });
    }

    var spyTicking = false;
    function updateSpy() {
        if (!sections.length) return;
        var offset = getNavbarOffset() + 24;
        var current = sections[0];
        for (var i = 0; i < sections.length; i++) {
            var rect = sections[i].el.getBoundingClientRect();
            if (rect.top - offset <= 0) {
                current = sections[i];
            } else {
                break;
            }
        }
        setActiveLink("#" + current.id);
    }

    window.addEventListener("scroll", function () {
        if (spyTicking) return;
        spyTicking = true;
        window.requestAnimationFrame(function () {
            updateSpy();
            spyTicking = false;
        });
    }, { passive: true });
    updateSpy();

    /* -------- Scroll reveal -------- */
    var reveals = document.querySelectorAll("[data-reveal]");
    if (reveals.length) {
        reveals.forEach(function (section) {
            var children = section.querySelectorAll("[data-reveal-child]");
            children.forEach(function (child, i) {
                child.style.setProperty("--reveal-i", String(i));
            });
        });

        if (reducedMotion || typeof IntersectionObserver !== "function") {
            reveals.forEach(function (el) { el.classList.add("is-visible"); });
        } else {
            var obs = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add("is-visible");
                        obs.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.12, rootMargin: "0px 0px -8% 0px" });
            reveals.forEach(function (el) { obs.observe(el); });
        }
    }

    /* -------- Carrusel -------- */
    var carousel = document.querySelector("[data-ea-carousel]");
    if (carousel) {
        var track = carousel.querySelector("[data-ea-carousel-track]");
        var viewport = carousel.querySelector("[data-ea-carousel-viewport]");
        var prevBtn = carousel.querySelector("[data-ea-carousel-prev]");
        var nextBtn = carousel.querySelector("[data-ea-carousel-next]");
        var dotsContainer = carousel.querySelector("[data-ea-carousel-dots]");
        var slides = track ? Array.prototype.slice.call(track.children) : [];
        var index = 0;
        var autoplayId = null;
        var autoplayDelay = 6000;
        var paused = false;

        function visibleCount() {
            if (!viewport || !slides.length) return 1;
            var vw = viewport.getBoundingClientRect().width;
            var sw = slides[0].getBoundingClientRect().width;
            if (sw <= 0) return 1;
            return Math.max(1, Math.round(vw / sw));
        }

        function maxIndex() { return Math.max(0, slides.length - visibleCount()); }

        function update() {
            if (!slides.length || !track) return;
            index = Math.min(Math.max(index, 0), maxIndex());
            var gap = parseFloat(getComputedStyle(track).gap || "0") || 0;
            var sw = slides[0].getBoundingClientRect().width + gap;
            track.style.transform = "translateX(" + (-index * sw) + "px)";
            if (prevBtn) prevBtn.disabled = index <= 0;
            if (nextBtn) nextBtn.disabled = index >= maxIndex();
            renderDots();
        }

        function renderDots() {
            if (!dotsContainer) return;
            var total = maxIndex() + 1;
            if (dotsContainer.childElementCount !== total) {
                dotsContainer.innerHTML = "";
                for (var i = 0; i < total; i++) {
                    var dot = document.createElement("button");
                    dot.type = "button";
                    dot.className = "ea-carousel-dot";
                    dot.setAttribute("aria-label", "Ir a la diapositiva " + (i + 1));
                    (function (target) {
                        dot.addEventListener("click", function () {
                            index = target;
                            update();
                            resetAutoplay();
                        });
                    })(i);
                    dotsContainer.appendChild(dot);
                }
            }
            for (var k = 0; k < dotsContainer.children.length; k++) {
                dotsContainer.children[k].classList.toggle("is-active", k === index);
            }
        }

        function next() {
            index = index >= maxIndex() ? 0 : index + 1;
            update();
        }
        function prev() {
            index = index <= 0 ? maxIndex() : index - 1;
            update();
        }

        function startAutoplay() {
            if (autoplayId || reducedMotion) return;
            autoplayId = window.setInterval(function () {
                if (!paused && !document.hidden) next();
            }, autoplayDelay);
        }
        function stopAutoplay() {
            if (autoplayId) { window.clearInterval(autoplayId); autoplayId = null; }
        }
        function resetAutoplay() { stopAutoplay(); startAutoplay(); }

        if (prevBtn) prevBtn.addEventListener("click", function () { prev(); resetAutoplay(); });
        if (nextBtn) nextBtn.addEventListener("click", function () { next(); resetAutoplay(); });

        carousel.addEventListener("mouseenter", function () { paused = true; });
        carousel.addEventListener("mouseleave", function () { paused = false; });
        carousel.addEventListener("focusin", function () { paused = true; });
        carousel.addEventListener("focusout", function () { paused = false; });

        carousel.addEventListener("keydown", function (e) {
            if (e.key === "ArrowLeft") { prev(); resetAutoplay(); }
            if (e.key === "ArrowRight") { next(); resetAutoplay(); }
        });

        var touchStartX = null;
        if (viewport) {
            viewport.addEventListener("touchstart", function (e) {
                touchStartX = e.touches[0].clientX;
            }, { passive: true });
            viewport.addEventListener("touchend", function (e) {
                if (touchStartX === null) return;
                var dx = e.changedTouches[0].clientX - touchStartX;
                if (Math.abs(dx) > 40) {
                    if (dx < 0) next(); else prev();
                    resetAutoplay();
                }
                touchStartX = null;
            });
        }

        window.addEventListener("resize", update);
        update();
        startAutoplay();
    }
});
