/* =====================================================================
   EdenAir — Núcleo 3D del hero
   - Modelo flotando libre, con rotación lenta automática.
   - Hover ⇒ encendido sutil (luces + partículas + tarjetas alrededor).
   - Drag ⇒ rotación libre con mouse/touch; al soltar vuelve suavemente.
   - Sin click-to-activate: no se ensucia el estado al arrastrar.
   - Datos simulados en el HTML; preparado para enriquecer desde /api/sensores.
   ===================================================================== */

import * as THREE from 'three';
import { GLTFLoader } from 'three/addons/loaders/GLTFLoader.js';

const root = document.querySelector('[data-eden-core]');
if (root) {
    initEdenCore(root).catch((err) => {
        console.error('[EdenCore] inicialización falló:', err);
        showStaticFallback(root, 'No se pudo cargar el núcleo 3D.');
    });
}

async function initEdenCore(root) {
    const stage    = root.querySelector('[data-eden-core-stage]');
    const canvas   = root.querySelector('[data-eden-core-canvas]');
    const fallback = root.querySelector('[data-eden-core-fallback]');
    const cards    = root.querySelector('[data-eden-core-cards]');
    const modelUrl = root.dataset.edenCoreSrc;
    const endpoint = root.dataset.edenCoreEndpoint;

    if (!stage || !canvas || !modelUrl) {
        throw new Error('Estructura del núcleo incompleta.');
    }

    if (!hasWebGL()) {
        showStaticFallback(root, 'Tu navegador no soporta WebGL.');
        return;
    }

    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const isMobile      = window.matchMedia('(max-width: 720px)').matches;
    const isTouch       = window.matchMedia('(hover: none)').matches;

    // ---- Renderer ----------------------------------------------------------
    const renderer = new THREE.WebGLRenderer({
        canvas,
        antialias: !isMobile,
        alpha: true,
        powerPreference: 'high-performance',
    });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, isMobile ? 1.4 : 1.8));
    renderer.outputColorSpace = THREE.SRGBColorSpace;
    renderer.toneMapping = THREE.ACESFilmicToneMapping;
    renderer.toneMappingExposure = 1.05;

    const scene  = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(36, 1, 0.1, 100);
    camera.position.set(0, 0.2, 4.6);

    // ---- Luces -------------------------------------------------------------
    const ambient = new THREE.AmbientLight(0xeaf2e2, 0.55);
    scene.add(ambient);

    const keyLight = new THREE.DirectionalLight(0xffffff, 1.1);
    keyLight.position.set(2.6, 3.2, 2.8);
    scene.add(keyLight);

    const rimLight = new THREE.DirectionalLight(0x4a7a55, 0.5);
    rimLight.position.set(-3, 1.5, -2);
    scene.add(rimLight);

    // Luz teal SIEMPRE encendida, "metida" dentro del núcleo para que el
    // ecuador/aro brille permanentemente. Una al frente y otra atrás para que
    // el aro se ilumine sin importar cómo rota el modelo.
    const tealCoreFront = new THREE.PointLight(0x86dcd2, 1.8, 6, 2);
    tealCoreFront.position.set(0, 0, 0.6);
    scene.add(tealCoreFront);

    const tealCoreBack = new THREE.PointLight(0x86dcd2, 1.4, 6, 2);
    tealCoreBack.position.set(0, 0, -0.6);
    scene.add(tealCoreBack);

    // Luces de hover: refuerzan el encendido cuando el usuario interactúa.
    const tealHoverBoost = new THREE.PointLight(0xb8d5d0, 0, 8, 2);
    tealHoverBoost.position.set(0, 0.2, 1.4);
    scene.add(tealHoverBoost);

    const citrusPoint = new THREE.PointLight(0xc9d870, 0, 6, 2);
    citrusPoint.position.set(-1.4, 1.0, 1.0);
    scene.add(citrusPoint);

    // ---- Pivot + partículas ------------------------------------------------
    const pivot = new THREE.Group();
    scene.add(pivot);

    // Partículas SIEMPRE visibles (sutiles en reposo, se intensifican en hover).
    const particleSystem = createParticles(isMobile ? 70 : 140);
    particleSystem.visible = true;
    scene.add(particleSystem);

    // ---- Resize (sólo window.resize, sin ResizeObserver) ------------------
    let pendingResize = false;
    const resize = () => {
        const rect = stage.getBoundingClientRect();
        const w = Math.max(1, Math.floor(rect.width));
        const h = Math.max(1, Math.floor(rect.height));
        renderer.setSize(w, h, false);
        camera.aspect = w / h;
        camera.updateProjectionMatrix();
    };
    const scheduleResize = () => {
        if (pendingResize) return;
        pendingResize = true;
        requestAnimationFrame(() => { pendingResize = false; resize(); });
    };
    resize();
    window.addEventListener('resize', scheduleResize, { passive: true });

    // ---- Estado de interacción --------------------------------------------
    // Rotación inicial fija a la que se vuelve después de cada drag.
    const initialRotation = { x: 0, y: 0, z: 0 };

    // Composición de rotación:
    //   pivot.rotation.x = idleX + userRotX
    //   pivot.rotation.y = autoRotY + userRotY
    //   pivot.rotation.z = idleZ
    let autoRotY     = 0;
    let userRotX     = 0;
    let userRotY     = 0;
    let glowTarget   = 0;
    let glowCurrent  = 0;
    let rotateSpeed  = reducedMotion ? 0 : 0.08; // muy lenta, contemplativa

    // Drag
    let isDragging  = false;
    let isReturning = false; // post-drag: lerp full rotation a initialRotation
    let dragPointer = null;
    let dragStartX  = 0;
    let dragStartY  = 0;
    let dragBaseX   = 0;
    let dragBaseY   = 0;

    // Hover (mantenido también durante drag)
    let hovered = false;

    const setActive = (on) => {
        if (on) root.classList.add('is-active');
        else if (!isDragging) root.classList.remove('is-active');

        glowTarget = on ? 1 : 0;
        if (cards) cards.setAttribute('aria-hidden', on ? 'false' : 'true');
    };

    // En touch/sin hover real: dejamos las cards apenas visibles y la
    // primera interacción activa el estado.
    if (isTouch) {
        root.classList.add('is-touch');
    }

    // ---- Hover -------------------------------------------------------------
    stage.addEventListener('pointerenter', (e) => {
        if (e.pointerType === 'touch') return; // touch maneja activation por drag
        hovered = true;
        setActive(true);
    });
    stage.addEventListener('pointerleave', (e) => {
        if (e.pointerType === 'touch') return;
        hovered = false;
        if (!isDragging) setActive(false);
    });

    // ---- Drag (mouse + touch + pen) ---------------------------------------
    const onPointerDown = (e) => {
        if (isDragging) return;
        isDragging  = true;
        isReturning = false; // cancela cualquier vuelta en curso
        dragPointer = e.pointerId;
        dragStartX  = e.clientX;
        dragStartY  = e.clientY;
        dragBaseX   = userRotX;
        dragBaseY   = userRotY;
        try { stage.setPointerCapture(e.pointerId); } catch (_) {}
        root.classList.add('is-dragging');
        // Mientras se arrastra, NO se cambian datos ni se activan efectos
        // nuevos. Si ya estaba en hover, mantiene el estado; si no, no entra.
    };

    const onPointerMove = (e) => {
        if (!isDragging || e.pointerId !== dragPointer) return;
        const dx = e.clientX - dragStartX;
        const dy = e.clientY - dragStartY;
        // Sensibilidad: ~360° al cruzar el stage horizontalmente
        const rect = stage.getBoundingClientRect();
        const sens = Math.PI * 2 / Math.max(160, rect.width);
        userRotY = dragBaseY + dx * sens;
        userRotX = clamp(dragBaseX + dy * sens, -1.2, 1.2);
    };

    const endDrag = (e) => {
        if (!isDragging || (e && e.pointerId !== dragPointer)) return;
        isDragging  = false;
        isReturning = true; // arranca la vuelta suave a la pose inicial
        dragPointer = null;
        try { stage.releasePointerCapture(e.pointerId); } catch (_) {}
        root.classList.remove('is-dragging');

        // En desktop, si ya no está hovered, apagamos el estado activo.
        // En touch, dejamos las cards un instante para que el usuario las lea.
        if (e && e.pointerType === 'touch') {
            setTimeout(() => { if (!isDragging) setActive(false); }, 1600);
        } else if (!hovered) {
            setActive(false);
        }
    };

    stage.addEventListener('pointerdown', onPointerDown);
    stage.addEventListener('pointermove', onPointerMove);
    stage.addEventListener('pointerup', endDrag);
    stage.addEventListener('pointercancel', endDrag);

    // ---- Visibilidad / loop -----------------------------------------------
    let visible = true;
    if ('IntersectionObserver' in window) {
        new IntersectionObserver(
            ([entry]) => { visible = entry.isIntersecting; },
            { threshold: 0.05 }
        ).observe(root);
    }

    let model = null;
    const clock = new THREE.Clock();
    const tealColor = new THREE.Color(0xb8d5d0);

    const animate = () => {
        requestAnimationFrame(animate);
        if (!visible) return;

        const dt = Math.min(0.05, clock.getDelta());
        const t  = clock.elapsedTime;

        if (isReturning) {
            // Vuelta suave: lerp userRot + autoRot a initialRotation (0,0,0).
            // Exponential decay frame-rate independent: 98% completado en ~1s.
            const k = 1 - Math.exp(-dt * 4.5);
            userRotX += (initialRotation.x - userRotX) * k;
            userRotY += (initialRotation.y - userRotY) * k;
            autoRotY += (initialRotation.y - autoRotY) * k;

            // Cuando todo está suficientemente cerca, terminamos la vuelta
            // y volvemos a rotación automática lenta.
            if (
                Math.abs(userRotX - initialRotation.x) < 0.002 &&
                Math.abs(userRotY - initialRotation.y) < 0.002 &&
                Math.abs(autoRotY - initialRotation.y) < 0.002
            ) {
                userRotX = initialRotation.x;
                userRotY = initialRotation.y;
                autoRotY = initialRotation.y;
                isReturning = false;
            }
        } else if (!isDragging) {
            // Rotación contemplativa muy lenta cuando no estamos arrastrando.
            autoRotY += rotateSpeed * dt;
        }

        // Flotación / rocking suave (siempre activa)
        const idleY = Math.sin(t * 0.85) * 0.10;
        const idleX = Math.sin(t * 0.55) * 0.04;
        const idleZ = Math.cos(t * 0.45) * 0.025;

        pivot.position.y = idleY;
        pivot.rotation.x = idleX + userRotX;
        pivot.rotation.y = autoRotY + userRotY;
        pivot.rotation.z = idleZ;

        // Glow / luces — el aro teal está SIEMPRE encendido (luces fijas),
        // el hover añade un boost adicional.
        glowCurrent += (glowTarget - glowCurrent) * Math.min(1, dt * 4);

        // Pulso interno permanente (muy sutil) sobre las luces del core
        const pulse = 1 + Math.sin(t * 1.8) * 0.06;
        tealCoreFront.intensity = 1.8 * pulse;
        tealCoreBack.intensity  = 1.4 * pulse;

        // Hover: activación sutil (~10-15% de aumento global). El aro
        // ya está encendido permanente; el hover sólo refuerza, no enciende.
        tealHoverBoost.intensity = 0.35 * glowCurrent;
        citrusPoint.intensity    = 0.20 * glowCurrent;
        ambient.intensity        = 0.55 + 0.05 * glowCurrent;
        keyLight.intensity       = 1.10 + 0.06 * glowCurrent;
        renderer.toneMappingExposure = 1.08 + 0.04 * glowCurrent;

        if (model) updateEmissive(model, glowCurrent, tealColor);

        // Partículas siempre activas — opacidad base + boost en hover.
        updateParticles(particleSystem, dt, glowCurrent);

        renderer.render(scene, camera);
    };
    animate();

    // ---- Carga del GLB en paralelo ----------------------------------------
    loadModel(modelUrl, fallback)
        .then((gltf) => {
            model = gltf.scene;

            const box    = new THREE.Box3().setFromObject(model);
            const size   = new THREE.Vector3();
            const center = new THREE.Vector3();
            box.getSize(size);
            box.getCenter(center);
            model.position.sub(center);

            const targetSize = isMobile ? 1.9 : 2.3;
            const maxDim     = Math.max(size.x, size.y, size.z) || 1;
            model.scale.setScalar(targetSize / maxDim);

            const tealEmissive = new THREE.Color(0x8ce6dc);
            model.traverse((obj) => {
                if (!obj.isMesh || !obj.material) return;
                obj.castShadow = false;
                obj.receiveShadow = false;
                const mats = Array.isArray(obj.material) ? obj.material : [obj.material];
                mats.forEach((mat) => {
                    if ('envMapIntensity' in mat) mat.envMapIntensity = 0.9;
                    if (!('emissive' in mat)) return;

                    // Detección de aro/LED teal: materiales con dominante cyan.
                    const c = mat.color || new THREE.Color(1, 1, 1);
                    const isTealish =
                        c.b > 0.45 && c.g > 0.45 && c.r < 0.65 &&
                        (c.b + c.g) * 0.5 > c.r + 0.05;
                    // O materiales que el GLB ya marcó como emisivos.
                    const wasEmissive =
                        (mat.emissive.r + mat.emissive.g + mat.emissive.b) > 0.05;
                    const looksLikeRing =
                        /led|ring|aro|glow|emit|core|halo/i.test(mat.name || '') ||
                        /led|ring|aro|glow|emit|core|halo/i.test(obj.name || '');

                    if (isTealish || wasEmissive || looksLikeRing) {
                        // Encendido permanente del aro/elemento teal.
                        mat.emissive.copy(tealEmissive);
                        mat.emissiveIntensity = 1.6;
                        mat.userData.__baseEmissive = tealEmissive.clone();
                        mat.userData.__baseEmissiveIntensity = 1.6;
                        mat.userData.__isCore = true;
                    } else {
                        mat.userData.__baseEmissive = mat.emissive.clone();
                        mat.userData.__baseEmissiveIntensity = mat.emissiveIntensity ?? 1;
                        mat.userData.__isCore = false;
                    }
                    mat.needsUpdate = true;
                });
            });

            pivot.add(model);
            root.classList.add('is-ready');
        })
        .catch((err) => {
            console.error('[EdenCore] error cargando GLB:', err);
            const msg = fallback && fallback.querySelector('.ea-hero-core-fallback-msg');
            if (msg) msg.textContent = 'No se pudo cargar el modelo.';
        });

    // ---- Hook futuro: refrescar datos desde /api/sensores -----------------
    // Por ahora los valores están en el HTML. Cuando quieras conectar:
    //   await refreshSensores(endpoint, root);
    // Lo dejamos expuesto en window para invocarlo desde consola/tests.
    window.edenCoreRefreshSensores = () => refreshSensores(endpoint, root);
}

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function clamp(v, min, max) { return v < min ? min : v > max ? max : v; }

function loadModel(url, fallback) {
    const loader = new GLTFLoader();
    const msgEl = fallback ? fallback.querySelector('.ea-hero-core-fallback-msg') : null;
    if (msgEl) msgEl.textContent = 'Cargando núcleo 3D…';

    return new Promise((resolve, reject) => {
        loader.load(
            url,
            resolve,
            (evt) => {
                if (msgEl && evt && evt.lengthComputable) {
                    const pct = Math.min(100, Math.round((evt.loaded / evt.total) * 100));
                    msgEl.textContent = `Cargando núcleo 3D… ${pct}%`;
                }
            },
            reject
        );
    });
}

function hasWebGL() {
    try {
        const c = document.createElement('canvas');
        return !!(window.WebGLRenderingContext && (c.getContext('webgl2') || c.getContext('webgl')));
    } catch (_) {
        return false;
    }
}

function showStaticFallback(root, msg) {
    const fallback = root.querySelector('[data-eden-core-fallback]');
    const canvas   = root.querySelector('[data-eden-core-canvas]');
    if (canvas) canvas.style.display = 'none';
    if (fallback) {
        const m = fallback.querySelector('.ea-hero-core-fallback-msg');
        if (m && msg) m.textContent = msg;
    }
    root.classList.add('is-fallback');
}

function updateEmissive(model, glow, tealTint) {
    // Pulso interno permanente (independiente del hover) — hace que el aro
    // "respire" como un núcleo inteligente activo.
    const now = performance.now() * 0.001;
    const breath = 1 + Math.sin(now * 1.6) * 0.12;

    model.traverse((obj) => {
        if (!obj.isMesh || !obj.material) return;
        const mats = Array.isArray(obj.material) ? obj.material : [obj.material];
        mats.forEach((mat) => {
            if (!('emissive' in mat) || !mat.userData.__baseEmissive) return;
            const baseI = mat.userData.__baseEmissiveIntensity ?? 1;
            if (mat.userData.__isCore) {
                // Aro/LED teal: encendido permanente con pulso. El hover
                // sólo aporta un refuerzo casi imperceptible (~15%).
                mat.emissive.copy(mat.userData.__baseEmissive).lerp(tealTint, 0.35);
                mat.emissiveIntensity = baseI * breath + glow * 0.18;
            } else {
                // Carcasa: tinte teal mínimo y boost emisivo casi nulo.
                mat.emissive.copy(mat.userData.__baseEmissive).lerp(tealTint, 0.06 * glow);
                mat.emissiveIntensity = baseI + glow * 0.06;
            }
        });
    });
}

function createParticles(count) {
    const positions  = new Float32Array(count * 3);
    const velocities = new Float32Array(count * 3);
    const phases     = new Float32Array(count);

    for (let i = 0; i < count; i++) {
        // Más cerca del núcleo (radio 1.2–1.8) — quedan "alrededor" del modelo,
        // no dispersas por toda la escena.
        const r = 1.2 + Math.random() * 0.6;
        const theta = Math.random() * Math.PI * 2;
        const phi   = Math.acos(2 * Math.random() - 1);
        positions[i * 3]     = r * Math.sin(phi) * Math.cos(theta);
        positions[i * 3 + 1] = r * Math.cos(phi);
        positions[i * 3 + 2] = r * Math.sin(phi) * Math.sin(theta);

        velocities[i * 3]     = (Math.random() - 0.5) * 0.035;
        velocities[i * 3 + 1] = 0.025 + Math.random() * 0.035;
        velocities[i * 3 + 2] = (Math.random() - 0.5) * 0.035;
        phases[i] = Math.random() * Math.PI * 2;
    }

    const geometry = new THREE.BufferGeometry();
    geometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));
    geometry.userData = { velocities, phases, basePositions: positions.slice() };

    const material = new THREE.PointsMaterial({
        color: 0xb8d5d0, // teal/verde-agua acorde al branding
        size: 0.04,
        sizeAttenuation: true,
        transparent: true,
        opacity: 0.55,
        depthWrite: false,
        blending: THREE.AdditiveBlending,
    });

    return new THREE.Points(geometry, material);
}

function updateParticles(points, dt, glow) {
    const pos = points.geometry.attributes.position;
    const { velocities } = points.geometry.userData;
    const arr = pos.array;

    // En hover: las partículas se mueven apenas ~10% más rápido. Sin
    // multiplicarse, sin explosiones — sólo un guiño de actividad.
    const speedMult = 1 + 0.10 * glow;

    for (let i = 0; i < arr.length; i += 3) {
        arr[i]     += velocities[i]     * dt * speedMult;
        arr[i + 1] += velocities[i + 1] * dt * speedMult;
        arr[i + 2] += velocities[i + 2] * dt * speedMult;
        if (arr[i + 1] > 2.2) {
            // Reciclar partícula cerca del modelo otra vez
            const r = 1.2 + Math.random() * 0.5;
            const theta = Math.random() * Math.PI * 2;
            arr[i]     = r * Math.cos(theta);
            arr[i + 1] = -0.8;
            arr[i + 2] = r * Math.sin(theta);
        }
    }

    pos.needsUpdate = true;
    // Opacidad base sutil + boost casi imperceptible en hover.
    points.material.opacity = 0.30 + 0.07 * glow;
    // Tamaño constante: respirar sería ya demasiado.
    points.material.size = 0.038;
    // Rotación lenta del sistema entero, con un toque extra en hover.
    points.rotation.y += dt * (0.05 + 0.015 * glow);
}

// ---------------------------------------------------------------------------
// /api/sensores — preparado para conectar más adelante. Mapea los datos del
// endpoint a las tarjetas del DOM. Hoy las cards muestran valores estáticos
// del HTML, así que esta función queda lista pero no se invoca por defecto.
// ---------------------------------------------------------------------------
async function refreshSensores(endpoint, root) {
    if (!endpoint) return;
    const cards = root.querySelector('[data-eden-core-cards]');
    if (!cards) return;

    try {
        const res = await fetch(endpoint, { headers: { Accept: 'application/json' }, cache: 'no-store' });
        if (!res.ok) throw new Error('HTTP ' + res.status);
        const data = await res.json();
        const sensores = data.sensores || {};

        // Métricas numéricas
        applyNumeric(cards, 'temperatura',  sensores.temperatura);
        applyNumeric(cards, 'humedad',      sensores.humedad);

        // Métricas textuales (CO2, calidad de aire, ventilador, humidificación)
        applyText(cards, 'co2',            sensores.co2?.texto);
        applyText(cards, 'calidad_aire',   sensores.calidad_aire?.texto);
        applyText(cards, 'ventilador',     sensores.ventilador?.texto);
        applyText(cards, 'humidificacion', sensores.humidificacion?.texto);
    } catch (err) {
        console.warn('[EdenCore] no se pudieron leer sensores:', err);
    }
}

function applyNumeric(cards, key, payload) {
    if (!payload) return;
    const card = cards.querySelector(`[data-metric="${key}"]`);
    if (!card) return;
    const valEl  = card.querySelector('[data-value]');
    const unitEl = card.querySelector('[data-unit]');
    if (valEl && payload.valor !== undefined && payload.valor !== null) {
        animateNumber(valEl, payload.valor);
    }
    if (unitEl && payload.unidad) unitEl.textContent = payload.unidad;
}

function applyText(cards, key, text) {
    if (!text) return;
    const card = cards.querySelector(`[data-metric="${key}"]`);
    if (!card) return;
    const valEl = card.querySelector('[data-value]');
    if (valEl) valEl.textContent = text;
}

function animateNumber(el, target) {
    const decimals = String(target).includes('.') ? 1 : 0;
    const from = parseFloat(String(el.textContent).replace(',', '.'));
    const start = isFinite(from) ? from : 0;
    const dur = 700;
    const t0 = performance.now();
    const tick = (now) => {
        const k = Math.min(1, (now - t0) / dur);
        const eased = 1 - Math.pow(1 - k, 3);
        const value = start + (Number(target) - start) * eased;
        el.textContent = value.toFixed(decimals);
        if (k < 1) requestAnimationFrame(tick);
        else el.textContent = Number(target).toFixed(decimals);
    };
    requestAnimationFrame(tick);
}
