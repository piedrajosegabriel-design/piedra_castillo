<?php
/**
 * Asistente "Conectá tu Eden Air" — alta de dispositivo por código de activación.
 *
 * Pasos: 1) Código  → 2) Datos  → 3) Ambiente  → 4) Confirmación.
 *
 * Variables esperadas:
 *   $tipos               array  tipo => descripción
 *   $espacios            array  key  => ['label', 'preset']
 *   $ambientesExistentes array  [['id','label','tipo'], ...]
 *
 * Posteo: POST panel/dispositivos.
 * Validación en vivo del código: GET panel/dispositivos/validar?code=...
 */
$userName = (string) (session()->get('user_name') ?? 'Usuario');
$initial  = strtoupper(mb_substr(trim($userName), 0, 1) ?: 'U');
$tipos    = $tipos ?? [];
$espacios = $espacios ?? [];
$ambientesExistentes = $ambientesExistentes ?? [];
$errors   = session()->getFlashdata('errors') ?? [];

$oldCode      = old('code', '');
$oldName      = old('name', '');
$oldType      = old('device_type', array_key_first($tipos) ?? 'Eden Air Core');
$oldSpace     = old('space', '');
$oldSpaceMode = old('space_mode', $ambientesExistentes === [] ? 'new' : 'existing');
$oldSpaceId   = (int) old('space_id', 0);
$oldNotes     = old('notes', '');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?= view('partials/head', [
        'title'     => 'Eden Air · Conectá tu dispositivo',
        'extraCss'  => ['CSS/dashboard.css'],
        'extraHead' => '<meta name="robots" content="noindex, nofollow"><meta name="color-scheme" content="light dark">',
    ]) ?>
</head>
<body class="dashboard-body ea-body ea-dashboard-body dashboard-ready">
<div class="ea-dashboard" data-dashboard-app>

    <?= view('partials/dashboard_sidebar', ['active' => 'dispositivos']) ?>

    <main class="ea-main">
        <header class="dashboard-header ea-header">
            <button type="button" class="ea-burger" data-sidebar-toggle aria-controls="dashboardSidebar" aria-expanded="true" aria-label="Mostrar u ocultar menú">
                <span></span><span></span><span></span>
            </button>
            <div class="ea-header-titles">
                <h1>Conectá tu Eden Air</h1>
                <p>Vinculá un nuevo dispositivo en 4 pasos simples</p>
            </div>
            <div class="ea-header-tools"><?= view('partials/theme_toggle') ?></div>
            <div class="ea-header-user" title="<?= esc($userName) ?>">
                <span class="ea-header-avatar"><?= esc($initial) ?></span>
                <span class="ea-header-name"><?= esc($userName) ?><small>Cuenta Eden Air</small></span>
            </div>
        </header>

        <div class="ea-content">
            <a href="<?= site_url('panel/dispositivos') ?>" class="ea-back-link">← Volver a Mis dispositivos</a>

            <?php if (session()->getFlashdata('error')): ?>
                <div class="ea-flash ea-flash-danger"><?= esc(session()->getFlashdata('error')) ?></div>
            <?php endif; ?>
            <?php if ($errors): ?>
                <div class="ea-flash ea-flash-danger"><ul><?php foreach ($errors as $e): ?><li><?= esc($e) ?></li><?php endforeach; ?></ul></div>
            <?php endif; ?>

            <div class="ea-wizard">
                <!-- Progreso -->
                <ol class="ea-wizard-steps" data-wizard-progress aria-label="Progreso del asistente">
                    <li class="is-active" data-step-dot="1"><span>1</span> Código</li>
                    <li data-step-dot="2"><span>2</span> Datos</li>
                    <li data-step-dot="3"><span>3</span> Ambiente</li>
                    <li data-step-dot="4"><span>4</span> Confirmar</li>
                </ol>

                <form method="post" action="<?= site_url('panel/dispositivos') ?>" class="ea-wizard-form" data-wizard
                      data-validate-url="<?= site_url('panel/dispositivos/validar') ?>">
                    <?= csrf_field() ?>

                    <!-- ============ PASO 1 · Código ============ -->
                    <section class="ea-step is-active" data-step="1" aria-label="Paso 1: código de activación">
                        <span class="ea-step-eyebrow">Paso 1 de 4</span>
                        <h2 class="ea-step-title">Ingresá tu código de activación</h2>
                        <p class="ea-step-lede">
                            El código de activación es el <strong>identificador único</strong> que viene con tu dispositivo Eden Air. Sirve para asociar ese producto con tu cuenta y evitar que otra persona pueda usarlo.
                        </p>

                        <details class="ea-explainer" open>
                            <summary><span>¿Dónde lo encuentro? ¿Es seguro?</span></summary>
                            <ul class="ea-explainer-list">
                                <li>Lo encontrás en la <strong>caja</strong>, la <strong>etiqueta del dispositivo</strong> o el <strong>QR de activación</strong>.</li>
                                <li>Tiene el formato <code>EDEN-XXXX-XXXX</code> (8 caracteres alfanuméricos).</li>
                                <li>Este código <strong>solo puede usarse una vez</strong>.</li>
                                <li>Si tenés más de un Eden Air, podés repetir este proceso con cada uno.</li>
                            </ul>
                        </details>

                        <label class="ea-field">
                            <span class="ea-field-label">Código de activación</span>
                            <input type="text" name="code" value="<?= esc($oldCode, 'attr') ?>"
                                   class="ea-input ea-input-code" placeholder="EDEN-XXXX-XXXX"
                                   autocomplete="off" autocapitalize="characters" spellcheck="false"
                                   maxlength="20" data-code-input aria-describedby="codeFeedback" required>
                        </label>
                        <p class="ea-code-feedback" id="codeFeedback" data-code-feedback role="status" aria-live="polite"></p>

                        <div class="ea-demo-hint">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01" stroke-linecap="round"/></svg>
                            <span>¿Estás probando la demo? Usá <button type="button" class="ea-demo-fill" data-demo-fill="EDEN-DEMO-2026">EDEN-DEMO-2026</button> (Eden Air Core).</span>
                        </div>

                        <div class="ea-wizard-nav">
                            <a href="<?= site_url('panel/dispositivos') ?>" class="ea-button ea-button-ghost">Cancelar</a>
                            <button type="button" class="ea-button ea-button-primary" data-step-next="2">Continuar →</button>
                        </div>
                    </section>

                    <!-- ============ PASO 2 · Datos ============ -->
                    <section class="ea-step" data-step="2" aria-label="Paso 2: datos del dispositivo">
                        <span class="ea-step-eyebrow">Paso 2 de 4</span>
                        <h2 class="ea-step-title">Personalizá tu dispositivo</h2>
                        <p class="ea-step-lede">Identificá este dispositivo con un nombre claro y elegí su tipo. Vas a poder cambiarlo después desde Mis dispositivos.</p>

                        <label class="ea-field">
                            <span class="ea-field-label">Nombre del dispositivo</span>
                            <input type="text" name="name" value="<?= esc($oldName, 'attr') ?>" class="ea-input"
                                   placeholder="Ej: Eden Air del dormitorio" maxlength="60" data-name-input required>
                        </label>

                        <span class="ea-field-label">Tipo de dispositivo</span>
                        <div class="ea-type-grid" role="radiogroup" aria-label="Tipo de dispositivo">
                            <?php foreach ($tipos as $tipo => $desc): ?>
                                <label class="ea-type-opt">
                                    <input type="radio" name="device_type" value="<?= esc($tipo, 'attr') ?>" <?= $oldType === $tipo ? 'checked' : '' ?> data-type-input>
                                    <span class="ea-type-card">
                                        <strong><?= esc($tipo) ?></strong>
                                        <small><?= esc($desc) ?></small>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>

                        <label class="ea-field">
                            <span class="ea-field-label">Notas <span class="ea-field-opt">(opcional)</span></span>
                            <textarea name="notes" class="ea-input ea-textarea" rows="2" maxlength="255" placeholder="Algún detalle del lugar o del equipo…"><?= esc($oldNotes) ?></textarea>
                        </label>

                        <div class="ea-wizard-nav">
                            <button type="button" class="ea-button ea-button-secondary" data-step-prev="1">← Atrás</button>
                            <button type="button" class="ea-button ea-button-primary" data-step-next="3">Continuar →</button>
                        </div>
                    </section>

                    <!-- ============ PASO 3 · Ambiente ============ -->
                    <section class="ea-step" data-step="3" aria-label="Paso 3: ambiente">
                        <span class="ea-step-eyebrow">Paso 3 de 4</span>
                        <h2 class="ea-step-title">¿En qué ambiente lo vas a instalar?</h2>
                        <p class="ea-step-lede">
                            Un ambiente es el <strong>lugar físico</strong> donde está el dispositivo (dormitorio, aula, oficina…). Cada ambiente tiene su propia configuración de confort y puede tener varios dispositivos.
                        </p>

                        <?php if ($ambientesExistentes !== []): ?>
                            <div class="ea-space-mode" role="tablist" aria-label="Origen del ambiente">
                                <button type="button" class="ea-space-mode-btn <?= $oldSpaceMode === 'existing' ? 'is-active' : '' ?>" data-space-mode="existing" role="tab" aria-selected="<?= $oldSpaceMode === 'existing' ? 'true' : 'false' ?>">Usar un ambiente existente</button>
                                <button type="button" class="ea-space-mode-btn <?= $oldSpaceMode === 'new' ? 'is-active' : '' ?>" data-space-mode="new" role="tab" aria-selected="<?= $oldSpaceMode === 'new' ? 'true' : 'false' ?>">Crear uno nuevo</button>
                            </div>
                        <?php endif; ?>

                        <input type="hidden" name="space_mode" value="<?= esc($oldSpaceMode, 'attr') ?>" data-space-mode-value>

                        <!-- Ambientes existentes -->
                        <?php if ($ambientesExistentes !== []): ?>
                            <div class="ea-space-existing" data-space-panel="existing" <?= $oldSpaceMode !== 'existing' ? 'hidden' : '' ?>>
                                <div class="ea-space-grid" role="radiogroup" aria-label="Ambientes ya creados">
                                    <?php foreach ($ambientesExistentes as $amb): ?>
                                        <label class="ea-space-opt">
                                            <input type="radio" name="space_id" value="<?= esc((string) $amb['id'], 'attr') ?>" <?= $oldSpaceId === (int) $amb['id'] ? 'checked' : '' ?> data-space-existing>
                                            <span class="ea-space-card">
                                                <strong><?= esc($amb['label']) ?></strong>
                                                <small><?= esc($amb['tipo']) ?></small>
                                            </span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Crear nuevo ambiente -->
                        <div class="ea-space-new" data-space-panel="new" <?= ($oldSpaceMode !== 'new' && $ambientesExistentes !== []) ? 'hidden' : '' ?>>
                            <div class="ea-space-grid" role="radiogroup" aria-label="Nuevo ambiente">
                                <?php foreach ($espacios as $key => $meta): ?>
                                    <label class="ea-space-opt">
                                        <input type="radio" name="space" value="<?= esc($key, 'attr') ?>" <?= $oldSpace === $key ? 'checked' : '' ?> data-space-input>
                                        <span class="ea-space-card"><?= esc($meta['label']) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>

                            <label class="ea-field" data-custom-space hidden>
                                <span class="ea-field-label">Nombre del espacio personalizado</span>
                                <input type="text" name="space_custom" value="<?= esc(old('space_custom', ''), 'attr') ?>" class="ea-input" placeholder="Ej: Sala de servidores" maxlength="120">
                            </label>
                        </div>

                        <div class="ea-wizard-nav">
                            <button type="button" class="ea-button ea-button-secondary" data-step-prev="2">← Atrás</button>
                            <button type="button" class="ea-button ea-button-primary" data-step-next="4">Continuar →</button>
                        </div>
                    </section>

                    <!-- ============ PASO 4 · Confirmar ============ -->
                    <section class="ea-step" data-step="4" aria-label="Paso 4: confirmación">
                        <span class="ea-step-eyebrow">Paso 4 de 4</span>
                        <h2 class="ea-step-title">Revisá y finalizá</h2>
                        <p class="ea-step-lede">Confirmá que los datos son correctos para vincular el dispositivo a tu cuenta.</p>

                        <dl class="ea-confirm" data-confirm-summary>
                            <div><dt>Código</dt><dd data-sum="code">—</dd></div>
                            <div><dt>Nombre</dt><dd data-sum="name">—</dd></div>
                            <div><dt>Tipo</dt><dd data-sum="type">—</dd></div>
                            <div><dt>Ambiente</dt><dd data-sum="space">—</dd></div>
                        </dl>

                        <ul class="ea-confirm-notes">
                            <li>El dispositivo quedará asociado a tu cuenta.</li>
                            <li>Una cuenta puede administrar <strong>varios dispositivos</strong> desde el mismo dashboard.</li>
                            <li>Luego vas a poder configurar <strong>automatizaciones por ambiente</strong>.</li>
                        </ul>

                        <div class="ea-wizard-nav">
                            <button type="button" class="ea-button ea-button-secondary" data-step-prev="3">← Atrás</button>
                            <button type="submit" class="ea-button ea-button-primary ea-button-buy">Finalizar vinculación</button>
                        </div>
                    </section>
                </form>
            </div>
        </div>
    </main>

    <div class="ea-sidebar-backdrop" data-sidebar-backdrop></div>
</div>

<script src="<?= base_url('JS/tema.js') ?>"></script>
<script src="<?= base_url('JS/dashboard.js') ?>"></script>
<script>
/* Asistente "Conectá tu Eden Air" — wizard de 4 pasos, validación en vivo del
   código y selector de ambiente existente / nuevo. Degrada con gracia sin JS. */
(function () {
    var form = document.querySelector("[data-wizard]");
    if (!form) return;

    form.classList.add("wizard-js");
    var steps    = Array.prototype.slice.call(form.querySelectorAll(".ea-step"));
    var dots     = Array.prototype.slice.call(document.querySelectorAll("[data-step-dot]"));
    var codeIn   = form.querySelector("[data-code-input]");
    var nameIn   = form.querySelector("[data-name-input]");
    var feedback = form.querySelector("[data-code-feedback]");
    var customWrap = form.querySelector("[data-custom-space]");
    var modeValue  = form.querySelector("[data-space-mode-value]");
    var validateUrl = form.getAttribute("data-validate-url");

    function showStep(n) {
        steps.forEach(function (s) {
            s.classList.toggle("is-active", s.getAttribute("data-step") === String(n));
        });
        dots.forEach(function (d) {
            var dn = parseInt(d.getAttribute("data-step-dot"), 10);
            d.classList.toggle("is-active", dn === n);
            d.classList.toggle("is-done", dn < n);
        });
        if (n === 4) fillSummary();
        var active = form.querySelector('.ea-step[data-step="' + n + '"]');
        if (active) { var h = active.querySelector("h2"); if (h) h.setAttribute("tabindex", "-1"), h.focus(); }
        try { window.scrollTo({ top: 0, behavior: "smooth" }); } catch (e) {}
    }

    function normalize(v) { return (v || "").toUpperCase().replace(/\s+/g, ""); }

    function setFeedback(state, msg) {
        if (!feedback) return;
        feedback.textContent = msg || "";
        feedback.className = "ea-code-feedback" + (state ? " is-" + state : "");
    }

    function validateCode() {
        var code = normalize(codeIn ? codeIn.value : "");
        if (codeIn) codeIn.value = code;
        if (!code) { setFeedback("error", "Ingresá el código de activación."); return Promise.resolve(false); }
        if (!validateUrl) return Promise.resolve(true);
        setFeedback("checking", "Verificando código…");
        return fetch(validateUrl + "?code=" + encodeURIComponent(code), { headers: { "X-Requested-With": "fetch" } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                setFeedback(data.ok ? "ok" : "error", data.mensaje || (data.ok ? "Código válido" : "Código no válido"));
                if (data.ok) {
                    if (nameIn && !nameIn.value && data.default_name) nameIn.value = data.default_name;
                    if (data.device_type) {
                        var t = form.querySelector('input[name="device_type"][value="' + data.device_type + '"]');
                        if (t) t.checked = true;
                    }
                }
                return data.ok;
            })
            .catch(function () { setFeedback("", ""); return true; });
    }

    function getSpaceSummary() {
        var mode = modeValue ? modeValue.value : "new";
        if (mode === "existing") {
            var sel = form.querySelector('[data-space-existing]:checked');
            if (!sel) return "—";
            var card = sel.parentElement.querySelector(".ea-space-card strong");
            return card ? card.textContent.trim() + " · existente" : "Ambiente existente";
        }
        var rad = form.querySelector('[data-space-input]:checked');
        if (!rad) return "—";
        var label = rad.parentElement.querySelector(".ea-space-card");
        var text  = label ? label.textContent.trim() : rad.value;
        var custom = form.querySelector('[name="space_custom"]');
        if (rad.value === "otro" && custom && custom.value.trim()) text += " · " + custom.value.trim();
        return text + " · nuevo";
    }

    function fillSummary() {
        var typeSel = form.querySelector('input[name="device_type"]:checked');
        var map = {
            code: codeIn ? codeIn.value : "",
            name: nameIn ? nameIn.value : "",
            type: typeSel ? typeSel.value : "",
            space: getSpaceSummary()
        };
        Object.keys(map).forEach(function (k) {
            var el = form.querySelector('[data-sum="' + k + '"]');
            if (el) el.textContent = map[k] || "—";
        });
    }

    // Tabs: existente / nuevo
    function setMode(mode) {
        if (!modeValue) return;
        modeValue.value = mode;
        form.querySelectorAll("[data-space-mode]").forEach(function (b) {
            var on = b.getAttribute("data-space-mode") === mode;
            b.classList.toggle("is-active", on);
            b.setAttribute("aria-selected", on ? "true" : "false");
        });
        form.querySelectorAll("[data-space-panel]").forEach(function (p) {
            p.hidden = p.getAttribute("data-space-panel") !== mode;
        });
        // Limpiar selección del otro panel para que el server reciba uno solo
        if (mode === "existing") {
            form.querySelectorAll("[data-space-input]").forEach(function (r) { r.checked = false; });
        } else {
            form.querySelectorAll("[data-space-existing]").forEach(function (r) { r.checked = false; });
        }
    }

    // Validación al avanzar
    function validateStep(to) {
        if (to === 2) return validateCode();
        if (to === 3) {
            if (nameIn && !nameIn.value.trim()) { nameIn.focus(); return Promise.resolve(false); }
            var t = form.querySelector('input[name="device_type"]:checked');
            if (!t) { var grid = form.querySelector(".ea-type-grid"); if (grid) grid.classList.add("is-error"); return Promise.resolve(false); }
            return Promise.resolve(true);
        }
        if (to === 4) {
            var mode = modeValue ? modeValue.value : "new";
            if (mode === "existing") {
                var sel = form.querySelector('[data-space-existing]:checked');
                if (!sel) { var g1 = form.querySelector('[data-space-panel="existing"] .ea-space-grid'); if (g1) g1.classList.add("is-error"); return Promise.resolve(false); }
            } else {
                var rad = form.querySelector('[data-space-input]:checked');
                if (!rad) { var g2 = form.querySelector('[data-space-panel="new"] .ea-space-grid'); if (g2) g2.classList.add("is-error"); return Promise.resolve(false); }
            }
            return Promise.resolve(true);
        }
        return Promise.resolve(true);
    }

    // Eventos
    form.addEventListener("click", function (e) {
        var next = e.target.closest("[data-step-next]");
        var prev = e.target.closest("[data-step-prev]");
        var modeBtn = e.target.closest("[data-space-mode]");
        if (modeBtn) { setMode(modeBtn.getAttribute("data-space-mode")); return; }
        if (next) {
            var to = parseInt(next.getAttribute("data-step-next"), 10);
            validateStep(to).then(function (ok) { if (ok) showStep(to); });
        }
        if (prev) showStep(parseInt(prev.getAttribute("data-step-prev"), 10));
    });

    if (codeIn) {
        var t;
        codeIn.addEventListener("input", function () {
            window.clearTimeout(t);
            t = window.setTimeout(validateCode, 450);
        });
    }

    var demoBtn = form.querySelector("[data-demo-fill]");
    if (demoBtn && codeIn) {
        demoBtn.addEventListener("click", function () {
            codeIn.value = demoBtn.getAttribute("data-demo-fill");
            validateCode();
        });
    }

    function toggleCustom() {
        if (!customWrap) return;
        var sel = form.querySelector('[data-space-input]:checked');
        customWrap.hidden = !(sel && sel.value === "otro");
    }
    form.addEventListener("change", function (e) {
        if (e.target.matches("[data-space-input]")) {
            var g = form.querySelector('[data-space-panel="new"] .ea-space-grid');
            if (g) g.classList.remove("is-error");
            toggleCustom();
        }
        if (e.target.matches("[data-space-existing]")) {
            var g2 = form.querySelector('[data-space-panel="existing"] .ea-space-grid');
            if (g2) g2.classList.remove("is-error");
        }
        if (e.target.matches('input[name="device_type"]')) {
            var grid = form.querySelector(".ea-type-grid");
            if (grid) grid.classList.remove("is-error");
        }
    });
    toggleCustom();
})();
</script>
</body>
</html>
