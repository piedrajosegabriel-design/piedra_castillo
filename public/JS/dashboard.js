document.addEventListener("DOMContentLoaded", function () {
    var body = document.body;
    var app = document.querySelector("[data-dashboard-app]");
    var header = document.querySelector(".dashboard-header");
    var loader = document.querySelector("[data-dashboard-loader]");
    var toggle = document.querySelector("[data-sidebar-toggle]");
    var backdrop = document.querySelector("[data-sidebar-backdrop]");
    var links = document.querySelectorAll(".sidebar-link");
    var preserveForms = document.querySelectorAll("[data-preserve-scroll]");
    var confirmForms = document.querySelectorAll("[data-confirm-form]");
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
        // Altura real del header → padding-top de .ea-main (el 64px del CSS
        // es solo fallback; el header puede crecer si el título hace wrap).
        if (header) {
            var h = Math.ceil(header.getBoundingClientRect().height);
            document.documentElement.style.setProperty("--ead-header-h", h + "px");
        }
    }

    syncScrollOffsetVar();
    window.addEventListener("resize", syncScrollOffsetVar);

    function scrollToTarget(target) {
        if (!target) return;
        if (window.__eaSmoother) {
            var y = window.__eaSmoother.offset(target, "top top") - getHeaderOffset();
            if (y < 0) y = 0;
            window.__eaSmoother.scrollTo(y, !reducedMotionQuery.matches);
            return;
        }
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

    /* -------------------- Scroll reveal (sutil, accesible) -------------------- */
    if ("IntersectionObserver" in window && !reducedMotionQuery.matches) {
        var reveals = document.querySelectorAll(".ea-reveal");
        if (reveals.length) {
            var revealObs = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add("is-visible");
                        revealObs.unobserve(entry.target);
                    }
                });
            }, { rootMargin: "0px 0px -10% 0px", threshold: 0.05 });
            reveals.forEach(function (el) { revealObs.observe(el); });
        }
    } else {
        document.querySelectorAll(".ea-reveal").forEach(function (el) {
            el.classList.add("is-visible");
        });
    }

    /* -------------------- Lecturas: ver más / ver menos -------------------- */
    var readingsToggle = document.querySelector("[data-readings-toggle]");
    var readingsCard = document.querySelector("[data-readings]");
    if (readingsToggle && readingsCard) {
        var readingsLabel = readingsToggle.querySelector("[data-readings-label]");
        readingsToggle.addEventListener("click", function () {
            var expanded = readingsCard.classList.toggle("is-expanded");
            readingsToggle.setAttribute("aria-expanded", expanded ? "true" : "false");
            var label = expanded ? readingsToggle.getAttribute("data-less") : readingsToggle.getAttribute("data-more");
            if (label) {
                if (readingsLabel) {
                    readingsLabel.textContent = label;
                } else {
                    readingsToggle.textContent = label;
                }
            }
        });
    }

    /* -------------------- Diálogo de confirmación -------------------- */
    function ensureConfirmDialog() {
        var existing = document.querySelector("[data-confirm-dialog]");

        if (existing) {
            return existing;
        }

        var dialog = document.createElement("div");
        dialog.className = "ea-confirm";
        dialog.setAttribute("data-confirm-dialog", "");
        dialog.setAttribute("aria-hidden", "true");
        dialog.innerHTML = '' +
            '<div class="ea-confirm-panel" role="dialog" aria-modal="true" aria-labelledby="eaConfirmTitle">' +
                '<div class="ea-confirm-icon" aria-hidden="true">' +
                    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4"/><path d="M12 17h.01"/><path d="M10.3 4.4 2.7 18a2 2 0 0 0 1.7 3h15.2a2 2 0 0 0 1.7-3L13.7 4.4a2 2 0 0 0-3.4 0z"/></svg>' +
                '</div>' +
                '<div class="ea-confirm-copy">' +
                    '<h2 id="eaConfirmTitle">Confirmar cambios</h2>' +
                    '<p data-confirm-text></p>' +
                '</div>' +
                '<div class="ea-confirm-actions">' +
                    '<button type="button" class="ea-kbtn" data-confirm-cancel>Cancelar</button>' +
                    '<button type="button" class="ea-kbtn ea-kbtn-primary" data-confirm-accept>Confirmar</button>' +
                '</div>' +
            '</div>';

        document.body.appendChild(dialog);
        return dialog;
    }

    function openConfirmDialog(message, onAccept) {
        var dialog = ensureConfirmDialog();
        var text = dialog.querySelector("[data-confirm-text]");
        var cancel = dialog.querySelector("[data-confirm-cancel]");
        var accept = dialog.querySelector("[data-confirm-accept]");
        var close = function () {
            dialog.classList.remove("is-open");
            dialog.setAttribute("aria-hidden", "true");
            document.body.classList.remove("confirm-lock");
        };
        var acceptOnce = function () {
            close();
            onAccept();
        };

        text.textContent = message;
        dialog.classList.add("is-open");
        dialog.setAttribute("aria-hidden", "false");
        document.body.classList.add("confirm-lock");

        cancel.onclick = close;
        accept.onclick = acceptOnce;
        dialog.onclick = function (event) {
            if (event.target === dialog) {
                close();
            }
        };
        document.addEventListener("keydown", function escapeHandler(event) {
            if (event.key !== "Escape" || !dialog.classList.contains("is-open")) {
                return;
            }

            document.removeEventListener("keydown", escapeHandler);
            close();
        });
        accept.focus();
    }

    function buildChangeMessage(form) {
        if (!form.hasAttribute("data-confirm-changes")) {
            return "";
        }

        var changes = [];
        var fields = form.querySelectorAll("[data-confirm-label][data-confirm-current]");

        fields.forEach(function (field) {
            var currentValue = (field.getAttribute("data-confirm-current") || "").trim();
            var nextValue = (field.value || "").trim();
            var label = field.getAttribute("data-confirm-label") || "este dato";

            if (currentValue !== nextValue) {
                changes.push("Cambiar " + label + " a \"" + nextValue + "\"");
            }
        });

        if (changes.length === 0) {
            return "No se detectaron cambios en tus datos.";
        }

        if (changes.length === 1) {
            return "Estas seguro de " + changes[0].charAt(0).toLowerCase() + changes[0].slice(1) + "?";
        }

        return "Estas seguro de aplicar estos cambios?\n\n- " + changes.join("\n- ");
    }

    confirmForms.forEach(function (form) {
        form.addEventListener("submit", function (event) {
            var message = buildChangeMessage(form) || form.getAttribute("data-confirm-message") || "Estas por aplicar cambios importantes en tu cuenta. Confirma para continuar.";

            if (form.dataset.confirmed === "true") {
                delete form.dataset.confirmed;
                return;
            }

            event.preventDefault();
            openConfirmDialog(message, function () {
                form.dataset.confirmed = "true";

                if (typeof form.requestSubmit === "function") {
                    form.requestSubmit();
                } else {
                    form.submit();
                }
            });
        });
    });
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
            if (window.__eaSmoother) {
                window.__eaSmoother.scrollTop(scrollTop);
            } else {
                window.scrollTo(0, scrollTop);
            }
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
