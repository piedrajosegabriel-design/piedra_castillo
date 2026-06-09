/* EdenAir — Index: navbar scroll-spy, scroll reveal, carousel, anchor offset */
document.addEventListener("DOMContentLoaded", function () {
    var reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    var navbar = document.querySelector(".ea-navbar");
    var navLinks = Array.prototype.slice.call(document.querySelectorAll(".ea-nav-links a[href^='#']"));

    /* -------- Navbar shrink on scroll + altura dinámica -------- */
    function syncNavbarScrolled() {
        if (!navbar) return;
        navbar.classList.toggle("is-scrolled", window.scrollY > 14);
    }
    function syncNavHeight() {
        if (!navbar) return;
        var h = Math.round(navbar.getBoundingClientRect().height);
        document.documentElement.style.setProperty("--ea-nav-h", h + "px");
    }
    syncNavbarScrolled();
    syncNavHeight();
    window.addEventListener("scroll", syncNavbarScrolled, { passive: true });
    window.addEventListener("resize", syncNavHeight);

    /* -------- Mobile nav drawer -------- */
    var navToggle = document.querySelector("[data-ea-nav-toggle]");
    var mobileNav = document.querySelector("[data-ea-mobile-nav]");

    function setMobileNav(open) {
        if (!navToggle || !mobileNav) return;
        navToggle.setAttribute("aria-expanded", open ? "true" : "false");
        mobileNav.setAttribute("aria-hidden", open ? "false" : "true");
        mobileNav.classList.toggle("is-open", open);
        document.body.classList.toggle("ea-nav-open", open);
        navToggle.setAttribute("aria-label", open ? "Cerrar menú de navegación" : "Abrir menú de navegación");
    }

    if (navToggle && mobileNav) {
        navToggle.addEventListener("click", function () {
            var isOpen = navToggle.getAttribute("aria-expanded") === "true";
            setMobileNav(!isOpen);
        });
        mobileNav.addEventListener("click", function (e) {
            var link = e.target.closest && e.target.closest("a");
            if (link) setMobileNav(false);
        });
        document.addEventListener("keydown", function (e) {
            if (e.key === "Escape") setMobileNav(false);
        });
        window.addEventListener("resize", function () {
            if (window.innerWidth > 900) setMobileNav(false);
        });
    }

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
            reveals.forEach(function (el) {
                el.classList.add("is-visible");
                el.classList.add("is-revealed");
            });
        } else {
            var obs = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        var target = entry.target;
                        target.classList.add("is-visible");
                        obs.unobserve(target);
                        var childCount = target.querySelectorAll("[data-reveal-child]").length;
                        var totalMs = 700 + childCount * 80;
                        window.setTimeout(function () {
                            target.classList.add("is-revealed");
                        }, totalMs);
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

    /* -------- Hero count-up animation -------- */
    var counters = document.querySelectorAll("[data-counter]");
    if (counters.length) {
        counters.forEach(function (el) {
            var target = parseFloat(el.getAttribute("data-counter-target"));
            var decimals = parseInt(el.getAttribute("data-counter-decimals") || "0", 10);
            if (isNaN(target)) return;
            if (reducedMotion) {
                el.textContent = target.toFixed(decimals);
                return;
            }
            var duration = 1400;
            var start = null;
            function tick(ts) {
                if (start === null) start = ts;
                var elapsed = ts - start;
                var t = Math.min(1, elapsed / duration);
                // easeOutCubic
                var eased = 1 - Math.pow(1 - t, 3);
                var value = target * eased;
                el.textContent = value.toFixed(decimals);
                if (t < 1) window.requestAnimationFrame(tick);
                else el.textContent = target.toFixed(decimals);
            }
            // pequeño delay para sincronizar con el fade del hero
            window.setTimeout(function () { window.requestAnimationFrame(tick); }, 420);
        });
    }

    /* -------- Eden Air Experience: video fullscreen scroll-driven -------- */
    var expSection = document.querySelector("[data-ea-experience]");
    if (expSection) {
        var EA_LOG = "[EdenAir Experience]";
        var expVideo    = expSection.querySelector("[data-ea-experience-video]");
        var expFallback = expSection.querySelector("[data-ea-experience-fallback]");
        var expTexts    = Array.prototype.slice.call(expSection.querySelectorAll(".ea-experience-text"));
        var isMobile    = window.matchMedia("(max-width: 640px)").matches;
        var targetTime  = 0;
        var currentTime = 0;
        var rafId       = null;
        var lastProgress = -1;
        var ready        = false;

        // Geometría cacheada: evita getBoundingClientRect() en cada frame de scroll
        var expSectionTop = 0;
        var expSectionH   = 0;
        function cacheExpGeometry() {
            expSectionTop = expSection.getBoundingClientRect().top + window.scrollY;
            expSectionH   = expSection.offsetHeight;
        }
        cacheExpGeometry();

        // Debug útil para diagnosticar
        if (window.console && console.info) {
            console.info(EA_LOG, "Section detectada");
            if (expVideo) {
                var initialSrc = expVideo.getAttribute("data-ea-experience-src") || expVideo.currentSrc || expVideo.src;
                console.info(EA_LOG, "src final:", initialSrc);
            } else {
                console.warn(EA_LOG, "No se encontró el elemento <video>");
            }
        }

        // Ventanas de visibilidad por texto: [inicio, fin] en progreso 0..1
        // Cada texto ocupa una ventana de ~22% con fade-in/out de 6% en bordes
        var WINDOWS = [
            { in: 0.02, out: 0.22 },  // text 0 (TL)
            { in: 0.20, out: 0.42 },  // text 1 (MR)
            { in: 0.38, out: 0.60 },  // text 2 (BL)
            { in: 0.56, out: 0.78 },  // text 3 (CR)
            { in: 0.74, out: 0.98 }   // text 4 (BR)
        ];

        function hideFallback() {
            if (expFallback && !expFallback.classList.contains("is-hidden")) {
                expFallback.classList.add("is-hidden");
            }
        }
        function showFallbackError(msg) {
            if (!expFallback) return;
            expFallback.classList.remove("is-hidden");
            var p = expFallback.querySelector("p");
            if (p && msg) p.textContent = msg;
        }

        function markReady() {
            if (ready) return;
            if (!expVideo || !isFinite(expVideo.duration) || expVideo.duration <= 0) return;
            ready = true;
            hideFallback();
            if (window.console && console.info) {
                console.info(EA_LOG, "READY ✓ duration =", expVideo.duration.toFixed(2), "s · networkState =", expVideo.networkState, "· readyState =", expVideo.readyState, "· currentTime inicial =", expVideo.currentTime);
            }
            syncExperience();
        }

        if (expVideo) {
            expVideo.removeAttribute("controls");
            expVideo.muted = true;
            expVideo.defaultMuted = true;
            expVideo.playsInline = true;

            // Truco iOS Safari + asegurar inicio de carga
            var unlockOnce = function () {
                var p = expVideo.play();
                if (p && typeof p.then === "function") {
                    p.then(function () { try { expVideo.pause(); } catch (e) {} })
                     .catch(function () {});
                } else {
                    try { expVideo.pause(); } catch (e) {}
                }
                window.removeEventListener("touchstart", unlockOnce);
                window.removeEventListener("scroll",     unlockOnce);
                window.removeEventListener("click",      unlockOnce);
            };
            window.addEventListener("touchstart", unlockOnce, { passive: true, once: true });
            window.addEventListener("scroll",     unlockOnce, { passive: true, once: true });
            window.addEventListener("click",      unlockOnce, { passive: true, once: true });

            expVideo.addEventListener("loadstart", function () {
                if (window.console && console.info) console.info(EA_LOG, "loadstart · currentSrc =", expVideo.currentSrc);
            });
            expVideo.addEventListener("loadedmetadata", function () {
                if (window.console && console.info) console.info(EA_LOG, "loadedmetadata · duration =", expVideo.duration);
                markReady();
            });
            expVideo.addEventListener("loadeddata", function () {
                if (window.console && console.info) console.info(EA_LOG, "loadeddata · readyState =", expVideo.readyState);
                markReady();
            });
            expVideo.addEventListener("canplay", function () {
                if (window.console && console.info) console.info(EA_LOG, "canplay · readyState =", expVideo.readyState);
                markReady();
            });
            expVideo.addEventListener("progress", function () {
                // re-check al recibir buffer chunks
                if (!ready) markReady();
            });
            expVideo.addEventListener("stalled", function () {
                if (window.console && console.warn) console.warn(EA_LOG, "stalled");
            });

            // Manejo de error a nivel <video> Y a nivel <source>
            function handleVideoError(label, srcUrl, errObj) {
                ready = false;
                var msg = "El video no pudo cargarse";
                if (errObj) {
                    if (errObj.code === 2) msg = "Error de red al cargar el video";
                    else if (errObj.code === 3) msg = "El video no se puede decodificar";
                    else if (errObj.code === 4) msg = "Formato de video no soportado";
                }
                showFallbackError(msg);
                if (window.console && console.warn) {
                    console.warn(EA_LOG, "ERROR (" + label + "):", srcUrl, errObj);
                }
            }
            expVideo.addEventListener("error", function () {
                handleVideoError("video", expVideo.currentSrc || expVideo.src, expVideo.error);
            });
            var sources = expVideo.querySelectorAll("source");
            for (var s = 0; s < sources.length; s++) {
                sources[s].addEventListener("error", (function (src) {
                    return function () {
                        if (window.console && console.warn) {
                            console.warn(EA_LOG, "ERROR <source>:", src);
                        }
                    };
                })(sources[s].src));
            }

            // FORZAR INICIO DE CARGA. Algunos navegadores deprioritzan el load
            // si el video está fuera del viewport o si tiene aria-hidden.
            try { expVideo.load(); } catch (e) {}

            // Cuando la sección se acerca al viewport, asegurar carga
            if (typeof IntersectionObserver === "function") {
                var ioVideo = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        if (entry.isIntersecting) {
                            // Nuevo intento de load si todavía no está ready
                            if (!ready && expVideo.readyState < 1) {
                                try { expVideo.load(); } catch (e) {}
                            }
                            ioVideo.unobserve(entry.target);
                        }
                    });
                }, { rootMargin: "200% 0px 200% 0px" });
                ioVideo.observe(expSection);
            }

            // Si tarda más de 15s en estar listo, mensaje discreto pero no roto
            window.setTimeout(function () {
                if (!ready && expFallback && !expFallback.classList.contains("is-hidden")) {
                    var p = expFallback.querySelector("p");
                    if (p) p.textContent = "El video tarda más de lo esperado…";
                    if (window.console && console.warn) {
                        console.warn(EA_LOG, "Timeout 15s sin ready · readyState =", expVideo.readyState, "· networkState =", expVideo.networkState, "· currentSrc =", expVideo.currentSrc, "· error =", expVideo.error);
                    }
                }
            }, 15000);
        } else {
            showFallbackError("Video no disponible");
        }

        function smoothSeek() {
            rafId = null;
            if (!expVideo || !ready) return;
            var diff = targetTime - currentTime;
            var absDiff = Math.abs(diff);
            if (absDiff < 0.016) {
                // Ya llegamos
                currentTime = targetTime;
            } else if (absDiff > 0.4) {
                // Salto grande (> 0.4s): snap directo, evita cola interminable
                currentTime = targetTime;
            } else {
                // Lerp rápido: 40% por frame ≈ llega en ~5 frames a 60fps
                currentTime += diff * 0.4;
            }
            try { expVideo.currentTime = currentTime; } catch (e) {}

            if (Math.abs(targetTime - currentTime) > 0.01) {
                rafId = window.requestAnimationFrame(smoothSeek);
            }
        }

        // Cada texto aparece/desaparece en su ventana de progreso
        function updateTextsVisibility(progress) {
            for (var i = 0; i < expTexts.length; i++) {
                var w = WINDOWS[i];
                if (!w) continue;
                var visible = (progress >= w.in && progress <= w.out);
                if (visible !== expTexts[i].classList.contains("is-visible")) {
                    expTexts[i].classList.toggle("is-visible", visible);
                }
            }
        }

        function syncExperience() {
            // Con ScrollSmoother activo, el pin + scrub del video lo maneja
            // inicio-gsap.js (ScrollTrigger). Acá bailamos para no pelear por
            // currentTime ni por la visibilidad de los textos.
            if (window.__eaSmoother) return;
            var vh = window.innerHeight || document.documentElement.clientHeight;
            var scrollable = expSectionH - vh;
            if (scrollable <= 0) return;

            // window.scrollY no fuerza layout; usa geometría cacheada
            var raw = (window.scrollY - expSectionTop) / scrollable;
            var progress = Math.max(0, Math.min(1, raw));
            if (progress === lastProgress) return;
            lastProgress = progress;

            updateTextsVisibility(progress);

            // currentTime ↔ scroll (solo si video listo)
            if (ready && expVideo && isFinite(expVideo.duration)) {
                var eased = 0.02 + progress * 0.96; // padding en bordes
                targetTime = eased * expVideo.duration;

                if (reducedMotion || isMobile) {
                    try { expVideo.currentTime = targetTime; } catch (e) {}
                    currentTime = targetTime;
                } else if (rafId === null) {
                    rafId = window.requestAnimationFrame(smoothSeek);
                }
            }
        }

        var expTicking = false;
        function onExpScroll() {
            if (expTicking) return;
            expTicking = true;
            window.requestAnimationFrame(function () {
                syncExperience();
                expTicking = false;
            });
        }

        window.addEventListener("scroll", onExpScroll, { passive: true });
        window.addEventListener("resize", function () {
            isMobile = window.matchMedia("(max-width: 640px)").matches;
            lastProgress = -1;
            cacheExpGeometry();   // recalcular tras cambio de tamaño
            syncExperience();
        });

        // estado inicial
        updateTextsVisibility(0);
        syncExperience();
    }
});
