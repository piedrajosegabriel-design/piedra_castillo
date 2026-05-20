document.addEventListener("DOMContentLoaded", function () {
    var body = document.body;
    var app = document.querySelector("[data-dashboard-app]");
    var header = document.querySelector(".dashboard-header");
    var loader = document.querySelector("[data-dashboard-loader]");
    var toggle = document.querySelector("[data-sidebar-toggle]");
    var backdrop = document.querySelector("[data-sidebar-backdrop]");
    var links = document.querySelectorAll(".sidebar-link");
    var preserveForms = document.querySelectorAll("[data-preserve-scroll]");
    var mobileQuery = window.matchMedia("(max-width: 960px)");
    var reducedMotionQuery = window.matchMedia("(prefers-reduced-motion: reduce)");
    var scrollKey = "dashboard-scroll-y";
    var loaderReady = false;
    var loaderDelayReached = loader === null;
    var pageReady = document.readyState === "complete";
    var loaderDelay = reducedMotionQuery.matches ? 200 : 1400;

    /* -------------------- Loader -------------------- */
    function finishLoader() {
        if (!body || loaderReady || !pageReady || !loaderDelayReached) {
            return;
        }

        loaderReady = true;
        body.classList.remove("dashboard-loading");
        body.classList.add("dashboard-ready");

        if (loader) {
            loader.setAttribute("aria-hidden", "true");
            window.setTimeout(function () {
                loader.remove();
            }, 800);
        }

        scrollToInitialHash();
    }

    if (loader) {
        loader.setAttribute("aria-hidden", "false");
        window.setTimeout(function () {
            loaderDelayReached = true;
            finishLoader();
        }, loaderDelay);

        if (!pageReady) {
            window.addEventListener("load", function () {
                pageReady = true;
                finishLoader();
            }, { once: true });
        }
    } else if (body) {
        body.classList.remove("dashboard-loading");
        body.classList.add("dashboard-ready");
        loaderReady = true;
        scrollToInitialHash();
    }

    /* -------------------- Offset del header sticky -------------------- */
    function getHeaderOffset() {
        if (!header) return 96;
        var rect = header.getBoundingClientRect();
        // Aire extra para que el título no quede pegado al header.
        return Math.ceil(rect.height + 24);
    }

    function syncScrollOffsetVar() {
        var offset = getHeaderOffset();
        document.documentElement.style.setProperty("--ea-scroll-offset", offset + "px");
    }

    syncScrollOffsetVar();
    window.addEventListener("resize", syncScrollOffsetVar);

    function scrollToTarget(target) {
        if (!target) return;
        var rect = target.getBoundingClientRect();
        var top = window.scrollY + rect.top - getHeaderOffset();
        if (top < 0) top = 0;
        window.scrollTo({
            top: top,
            behavior: reducedMotionQuery.matches ? "auto" : "smooth"
        });
    }

    function isInternalAnchor(link) {
        if (!link) return false;
        var href = link.getAttribute("href") || "";
        if (href.length < 2 || href.charAt(0) !== "#") return false;
        var id = href.slice(1);
        if (id === "") return false;
        return !!document.getElementById(id);
    }

    // Interceptar clicks de cualquier link interno (#) en el documento
    document.addEventListener("click", function (event) {
        var link = event.target.closest && event.target.closest('a[href^="#"]');
        if (!link || !isInternalAnchor(link)) return;
        var href = link.getAttribute("href");
        var target = document.getElementById(href.slice(1));
        if (!target) return;

        event.preventDefault();
        scrollToTarget(target);

        // Actualizar URL sin saltar
        if (history.replaceState) {
            history.replaceState(null, "", href);
        }

        setActiveLink(href);
        closeMobileSidebar();
    });

    function scrollToInitialHash() {
        var hash = window.location.hash;
        if (!hash || hash.length < 2) return;
        var target = document.getElementById(hash.slice(1));
        if (!target) return;
        // Pequeña espera para que el layout haya estabilizado tras el loader
        window.setTimeout(function () { scrollToTarget(target); }, 60);
    }

    /* -------------------- Scroll spy -------------------- */
    var navLinks = Array.prototype.slice.call(document.querySelectorAll('.sidebar-link[href^="#"]'));
    var sections = navLinks
        .map(function (link) {
            var id = link.getAttribute("href").slice(1);
            var el = document.getElementById(id);
            return el ? { id: id, el: el, link: link } : null;
        })
        .filter(Boolean);

    function setActiveLink(hash) {
        navLinks.forEach(function (link) {
            link.classList.toggle("is-active", link.getAttribute("href") === hash);
        });
    }

    var spyTicking = false;
    function updateSpy() {
        if (sections.length === 0) return;
        var offset = getHeaderOffset() + 12;
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

    /* -------------------- Sidebar (toggle, modos, escape) -------------------- */
    if (!app || !toggle) {
        return;
    }

    function restoreScrollPosition() {
        var savedScroll = sessionStorage.getItem(scrollKey);
        if (savedScroll === null) return;

        sessionStorage.removeItem(scrollKey);
        var scrollTop = parseInt(savedScroll, 10);
        if (Number.isNaN(scrollTop)) return;

        window.setTimeout(function () {
            window.scrollTo(0, scrollTop);
        }, 0);
    }

    function rememberScrollPosition() {
        sessionStorage.setItem(scrollKey, String(window.scrollY));
    }

    function syncBodyScroll() {
        document.body.classList.toggle(
            "sidebar-lock",
            mobileQuery.matches && app.classList.contains("sidebar-open")
        );
    }

    function updateAria() {
        var expanded;

        if (mobileQuery.matches) {
            expanded = app.classList.contains("sidebar-open");
        } else {
            expanded = !app.classList.contains("sidebar-closed");
        }

        toggle.setAttribute("aria-expanded", expanded ? "true" : "false");
        syncBodyScroll();
    }

    function closeMobileSidebar() {
        if (!mobileQuery.matches) return;
        app.classList.remove("sidebar-open");
        updateAria();
    }

    function syncSidebarMode() {
        if (mobileQuery.matches) {
            app.classList.remove("sidebar-closed");
        } else {
            app.classList.remove("sidebar-open");
        }
        updateAria();
        syncScrollOffsetVar();
    }

    toggle.addEventListener("click", function () {
        if (mobileQuery.matches) {
            app.classList.toggle("sidebar-open");
        } else {
            app.classList.toggle("sidebar-closed");
        }
        updateAria();
    });

    if (backdrop) {
        backdrop.addEventListener("click", closeMobileSidebar);
    }

    links.forEach(function (link) {
        link.addEventListener("click", closeMobileSidebar);
    });

    preserveForms.forEach(function (form) {
        form.addEventListener("submit", rememberScrollPosition);
    });

    document.addEventListener("keydown", function (event) {
        if (event.key === "Escape") {
            closeMobileSidebar();
        }
    });

    if (typeof mobileQuery.addEventListener === "function") {
        mobileQuery.addEventListener("change", syncSidebarMode);
    } else if (typeof mobileQuery.addListener === "function") {
        mobileQuery.addListener(syncSidebarMode);
    }

    restoreScrollPosition();
    syncSidebarMode();
});
