/* =====================================================================
   EdenAir — Núcleo 3D del hero
   Carga el modelo .glb, lo hace flotar y rotar suavemente, y reacciona al
   click con un estado "encendido": más luz, partículas, glow intenso y
   tarjetas con datos de /api/sensores. El render arranca enseguida con un
   placeholder visual, así el hero nunca se ve "roto" mientras descarga.
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
    const hint     = root.querySelector('[data-eden-core-hint]');
    const cards    = root.querySelector('[data-eden-core-cards]');
    const modelUrl = root.dataset.edenCoreSrc;
    const endpoint = root.dataset.edenCoreEndpoint;

    if (!stage || !canvas || !modelUrl) {
        throw new Error('Estructura del núcleo incompleta.');
    }

    if (!hasWebGL()) {
        showStaticFallback(root, 'Tu navegador no soporta WebGL.');
        setupCardClick(stage, root, endpoint);
        return;
    }

    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const isMobile      = window.matchMedia('(max-width: 720px)').matches;

    // --- Renderer / scene ---------------------------------------------------
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
    const camera = new THREE.PerspectiveCamera(38, 1, 0.1, 100);
    camera.position.set(0, 0.25, 4.6);

    const ambient = new THREE.AmbientLight(0xeaf2e2, 0.55);
    scene.add(ambient);

    const keyLight = new THREE.DirectionalLight(0xffffff, 1.1);
    keyLight.position.set(2.6, 3.2, 2.8);
    scene.add(keyLight);

    const rimLight = new THREE.DirectionalLight(0x4a7a55, 0.45);
    rimLight.position.set(-3, 1.5, -2);
    scene.add(rimLight);

    const corePoint = new THREE.PointLight(0xb8d5d0, 0, 8, 2);
    corePoint.position.set(0, 0.2, 1.2);
    scene.add(corePoint);

    const citrusPoint = new THREE.PointLight(0xc9d870, 0, 6, 2);
    citrusPoint.position.set(-1.4, 1.0, 1.0);
    scene.add(citrusPoint);

    const pivot = new THREE.Group();
    scene.add(pivot);

    const particleSystem = createParticles(isMobile ? 60 : 120);
    particleSystem.visible = false;
    scene.add(particleSystem);

    // --- Resize (solo window.resize, sin ResizeObserver) -------------------
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

    // --- Estado / interacción ----------------------------------------------
    let powered     = false;
    let glowTarget  = 0;
    let glowCurrent = 0;
    let rotateSpeed = reducedMotion ? 0 : 0.22;
    let model       = null;

    const togglePower = () => {
        powered = !powered;
        root.classList.toggle('is-powered', powered);
        if (cards) cards.setAttribute('aria-hidden', powered ? 'false' : 'true');
        if (hint)  hint.classList.toggle('is-hidden', powered);

        if (powered) {
            glowTarget = 1;
            particleSystem.visible = true;
            rotateSpeed = reducedMotion ? 0 : 0.42;
            fetchSensores(endpoint, root);
        } else {
            glowTarget  = 0;
            rotateSpeed = reducedMotion ? 0 : 0.22;
        }
    };

    stage.addEventListener('click', togglePower);
    stage.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            togglePower();
        }
    });
    stage.addEventListener('pointerenter', () => root.classList.add('is-active'));
    stage.addEventListener('pointerleave', () => root.classList.remove('is-active'));

    let visible = true;
    if ('IntersectionObserver' in window) {
        new IntersectionObserver(
            ([entry]) => { visible = entry.isIntersecting; },
            { threshold: 0.05 }
        ).observe(root);
    }

    // --- Render loop (arranca ya, aunque el modelo no esté) ----------------
    const clock = new THREE.Clock();
    const tmpColor = new THREE.Color(0xb8d5d0);

    const animate = () => {
        requestAnimationFrame(animate);
        if (!visible) return;

        const t  = clock.getElapsedTime();
        const dt = Math.min(0.05, clock.getDelta());

        pivot.rotation.y += rotateSpeed * dt;
        pivot.position.y  = Math.sin(t * 0.9) * 0.08;
        pivot.rotation.x  = Math.sin(t * 0.55) * 0.045;
        pivot.rotation.z  = Math.cos(t * 0.45) * 0.025;

        glowCurrent += (glowTarget - glowCurrent) * Math.min(1, dt * 4);
        corePoint.intensity   = 4.5 * glowCurrent;
        citrusPoint.intensity = 2.6 * glowCurrent;
        ambient.intensity     = 0.55 + 0.35 * glowCurrent;
        keyLight.intensity    = 1.1  + 0.45 * glowCurrent;
        renderer.toneMappingExposure = 1.05 + 0.35 * glowCurrent;

        if (model) updateEmissive(model, glowCurrent, tmpColor);

        if (particleSystem.visible) {
            updateParticles(particleSystem, dt, glowCurrent);
            if (!powered && glowCurrent < 0.02) particleSystem.visible = false;
        }

        renderer.render(scene, camera);
    };
    animate();

    // --- Carga del modelo en paralelo --------------------------------------
    loadModel(modelUrl, fallback)
        .then((gltf) => {
            model = gltf.scene;

            const box    = new THREE.Box3().setFromObject(model);
            const size   = new THREE.Vector3();
            const center = new THREE.Vector3();
            box.getSize(size);
            box.getCenter(center);
            model.position.sub(center);

            const targetSize = isMobile ? 1.8 : 2.2;
            const maxDim     = Math.max(size.x, size.y, size.z) || 1;
            model.scale.setScalar(targetSize / maxDim);

            model.traverse((obj) => {
                if (!obj.isMesh || !obj.material) return;
                obj.castShadow = false;
                obj.receiveShadow = false;
                const mats = Array.isArray(obj.material) ? obj.material : [obj.material];
                mats.forEach((mat) => {
                    if ('envMapIntensity' in mat) mat.envMapIntensity = 0.8;
                    if ('emissive' in mat) {
                        mat.userData.__baseEmissive = mat.emissive.clone();
                        mat.userData.__baseEmissiveIntensity = mat.emissiveIntensity ?? 1;
                    }
                });
            });

            pivot.add(model);
            root.classList.add('is-ready');
        })
        .catch((err) => {
            console.error('[EdenCore] error cargando GLB:', err);
            // Si falla, el placeholder del fallback queda visible y todo sigue funcionando.
            const msg = fallback && fallback.querySelector('.ea-hero-core-fallback-msg');
            if (msg) msg.textContent = 'No se pudo cargar el modelo.';
        });

    // --- Click en cards también activa/desactiva ---------------------------
    setupCardClick(stage, root, endpoint);
}

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

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

function updateEmissive(model, glow, tmp) {
    model.traverse((obj) => {
        if (!obj.isMesh || !obj.material) return;
        const mats = Array.isArray(obj.material) ? obj.material : [obj.material];
        mats.forEach((mat) => {
            if (!('emissive' in mat) || !mat.userData.__baseEmissive) return;
            const baseI = mat.userData.__baseEmissiveIntensity ?? 1;
            mat.emissive.copy(mat.userData.__baseEmissive).lerp(tmp, 0.55 * glow);
            mat.emissiveIntensity = baseI + glow * 0.9;
        });
    });
}

function createParticles(count) {
    const positions = new Float32Array(count * 3);
    const velocities = new Float32Array(count * 3);
    const phases     = new Float32Array(count);

    for (let i = 0; i < count; i++) {
        const r = 1.1 + Math.random() * 0.9;
        const theta = Math.random() * Math.PI * 2;
        const phi   = Math.acos(2 * Math.random() - 1);
        positions[i * 3]     = r * Math.sin(phi) * Math.cos(theta);
        positions[i * 3 + 1] = r * Math.cos(phi);
        positions[i * 3 + 2] = r * Math.sin(phi) * Math.sin(theta);

        velocities[i * 3]     = (Math.random() - 0.5) * 0.05;
        velocities[i * 3 + 1] = 0.04 + Math.random() * 0.05;
        velocities[i * 3 + 2] = (Math.random() - 0.5) * 0.05;
        phases[i] = Math.random() * Math.PI * 2;
    }

    const geometry = new THREE.BufferGeometry();
    geometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));
    geometry.userData = { velocities, phases };

    const material = new THREE.PointsMaterial({
        color: 0xc9d870,
        size: 0.045,
        sizeAttenuation: true,
        transparent: true,
        opacity: 0,
        depthWrite: false,
        blending: THREE.AdditiveBlending,
    });

    return new THREE.Points(geometry, material);
}

function updateParticles(points, dt, glow) {
    const pos = points.geometry.attributes.position;
    const { velocities } = points.geometry.userData;
    const arr = pos.array;

    for (let i = 0; i < arr.length; i += 3) {
        arr[i]     += velocities[i]     * dt;
        arr[i + 1] += velocities[i + 1] * dt;
        arr[i + 2] += velocities[i + 2] * dt;
        if (arr[i + 1] > 2.4) {
            const r = 1.1 + Math.random() * 0.4;
            const theta = Math.random() * Math.PI * 2;
            arr[i]     = r * Math.cos(theta);
            arr[i + 1] = -0.6;
            arr[i + 2] = r * Math.sin(theta);
        }
    }

    pos.needsUpdate = true;
    points.material.opacity = 0.75 * glow;
    points.rotation.y += dt * 0.12;
}

function setupCardClick(stage, root, endpoint) {
    // (Reservado para extensiones — por ahora el click se maneja sobre el stage.)
    void stage; void root; void endpoint;
}

async function fetchSensores(endpoint, root) {
    if (!endpoint) return;
    const cards = root.querySelector('[data-eden-core-cards]');
    if (!cards) return;

    try {
        const res = await fetch(endpoint, { headers: { Accept: 'application/json' }, cache: 'no-store' });
        if (!res.ok) throw new Error('HTTP ' + res.status);
        const data = await res.json();
        const sensores = data.sensores || {};
        applyMetric(cards, 'temperatura',  sensores.temperatura);
        applyMetric(cards, 'humedad',      sensores.humedad);
        applyMetric(cards, 'co2',          sensores.co2);
        applyMetric(cards, 'calidad_aire', sensores.calidad_aire);
    } catch (err) {
        console.warn('[EdenCore] no se pudieron leer sensores:', err);
        cards.querySelectorAll('[data-value]').forEach((el) => {
            if (el.textContent === '--') el.textContent = '—';
        });
    }
}

function applyMetric(cards, key, payload) {
    if (!payload) return;
    const card = cards.querySelector(`[data-metric="${key}"]`);
    if (!card) return;
    const valEl  = card.querySelector('[data-value]');
    const unitEl = card.querySelector('[data-unit]');
    if (valEl && payload.valor !== undefined) animateNumber(valEl, payload.valor);
    if (unitEl && payload.unidad) unitEl.textContent = payload.unidad;
}

function animateNumber(el, target) {
    const decimals = String(target).includes('.') ? 1 : 0;
    const from = parseFloat(el.textContent.replace(',', '.'));
    const start = isFinite(from) ? from : 0;
    const dur = 700;
    const t0 = performance.now();
    const tick = (now) => {
        const k = Math.min(1, (now - t0) / dur);
        const eased = 1 - Math.pow(1 - k, 3);
        const value = start + (target - start) * eased;
        el.textContent = value.toFixed(decimals);
        if (k < 1) requestAnimationFrame(tick);
        else el.textContent = Number(target).toFixed(decimals);
    };
    requestAnimationFrame(tick);
}
