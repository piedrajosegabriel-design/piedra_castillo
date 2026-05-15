document.addEventListener("DOMContentLoaded", function () {
    var body = document.body;
    var app = document.querySelector("[data-dashboard-app]");
    var loader = document.querySelector("[data-dashboard-loader]");
    var toggle = document.querySelector("[data-sidebar-toggle]");
    var backdrop = document.querySelector("[data-sidebar-backdrop]");
    var links = document.querySelectorAll(".sidebar-link");
    var preserveForms = document.querySelectorAll("[data-preserve-scroll]");
    var mobileQuery = window.matchMedia("(max-width: 920px)");
    var reducedMotionQuery = window.matchMedia("(prefers-reduced-motion: reduce)");
    var scrollKey = "dashboard-scroll-y";
    var loaderReady = false;
    var loaderDelayReached = loader === null;
    var pageReady = document.readyState === "complete";
    var loaderDelay = reducedMotionQuery.matches ? 220 : 2500;

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
            }, 820);
        }
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
    }

    if (!app || !toggle) {
        return;
    }

    function restoreScrollPosition() {
        var savedScroll = sessionStorage.getItem(scrollKey);
        var scrollTop;

        if (savedScroll === null) {
            return;
        }

        sessionStorage.removeItem(scrollKey);
        scrollTop = parseInt(savedScroll, 10);

        if (Number.isNaN(scrollTop)) {
            return;
        }

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
        if (!mobileQuery.matches) {
            return;
        }

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
