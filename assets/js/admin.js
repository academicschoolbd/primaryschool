/* =================================================================
   EduSaaS — Admin AJAX Framework
   Provides: Toast, debounce, fetch helpers, live search, AJAX delete,
   cascading selects, photo preview, live theme preview.
   ================================================================= */
(function () {
    'use strict';

    const APP = window.APP || { base: '', admin: '', api: '/api' };
    const $  = (s, c) => (c || document).querySelector(s);
    const $$ = (s, c) => Array.from((c || document).querySelectorAll(s));

    // ---------- Toast ----------
    const Toast = {
        host() {
            let h = document.getElementById('toastHost');
            if (!h) { h = document.createElement('div'); h.className = 'toast-host'; h.id = 'toastHost'; document.body.appendChild(h); }
            return h;
        },
        show(type, msg, timeout = 3500) {
            const icons = { success: 'bi-check-circle-fill', error: 'bi-exclamation-circle-fill', info: 'bi-info-circle-fill' };
            const t = document.createElement('div');
            t.className = 'toast ' + (type || 'info');
            t.innerHTML = `<i class="bi ${icons[type] || icons.info}"></i><span>${escapeHtml(msg)}</span>`;
            this.host().appendChild(t);
            setTimeout(() => {
                t.classList.add('fade-out');
                setTimeout(() => t.remove(), 250);
            }, timeout);
        },
        success(m, t) { this.show('success', m, t); },
        error(m, t)   { this.show('error',   m, t); },
        info(m, t)    { this.show('info',    m, t); }
    };
    window.Toast = Toast;

    // ---------- Utilities ----------
    function escapeHtml(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }
    function debounce(fn, delay = 250) {
        let t = null;
        return function (...args) {
            clearTimeout(t);
            t = setTimeout(() => fn.apply(this, args), delay);
        };
    }
    window.debounce = debounce;
    window.escapeHtml = escapeHtml;

    // ---------- Fetch wrapper ----------
    async function api(url, opts = {}) {
        opts.headers = Object.assign({ 'X-Requested-With': 'XMLHttpRequest' }, opts.headers || {});
        if (opts.body && !(opts.body instanceof FormData) && typeof opts.body === 'object') {
            opts.body = new URLSearchParams(opts.body).toString();
            opts.headers['Content-Type'] = 'application/x-www-form-urlencoded';
        }
        const res = await fetch(url, opts);
        let data = null;
        try { data = await res.json(); } catch { /* not JSON */ }
        if (!res.ok || !data || data.ok === false) {
            const msg = (data && data.msg) || ('Request failed (' + res.status + ')');
            throw new Error(msg);
        }
        return data;
    }
    window.api = api;

    // ---------- Live search ----------
    // <input data-live-search="endpoint.php" data-target="#tbody" data-extra-fields=".form select">
    function bindLiveSearch(input) {
        const endpoint = input.dataset.liveSearch;
        const target   = $(input.dataset.target);
        if (!endpoint || !target) return;
        const extraSelector = input.dataset.extraFields;
        const renderer = window[input.dataset.renderer || 'renderRows'];
        if (typeof renderer !== 'function') return;

        async function run() {
            const params = new URLSearchParams();
            params.set('q', input.value || '');
            if (extraSelector) {
                $$(extraSelector).forEach(el => {
                    if (el.name && el.value !== '') params.set(el.name, el.value);
                });
            }
            target.classList.add('is-loading');
            try {
                const r = await api(endpoint + '?' + params.toString());
                target.innerHTML = renderer(r.data || [], r) || '';
            } catch (e) {
                Toast.error(e.message);
            } finally {
                target.classList.remove('is-loading');
            }
        }
        const debounced = debounce(run, 250);
        input.addEventListener('input', debounced);
        // also trigger on change of any extra-field selects
        if (extraSelector) {
            $$(extraSelector).forEach(el => el.addEventListener('change', run));
        }
    }

    // ---------- AJAX delete ----------
    // <a data-ajax-delete="endpoint.php?id=1" data-confirm="Delete?" data-row="tr">
    document.addEventListener('click', async function (ev) {
        const a = ev.target.closest('[data-ajax-delete]');
        if (!a) return;
        ev.preventDefault();
        const url = a.dataset.ajaxDelete;
        const msg = a.dataset.confirm || 'Delete this item?';
        if (!confirm(msg)) return;
        const rowSel = a.dataset.row || 'tr';
        const row = a.closest(rowSel);
        if (row) row.classList.add('removing');
        try {
            const r = await api(url, { method: 'POST' });
            Toast.success(r.msg || 'Deleted.');
            if (row) setTimeout(() => row.remove(), 250);
        } catch (e) {
            Toast.error(e.message);
            if (row) row.classList.remove('removing');
        }
    });

    // ---------- Cascading class → section selects ----------
    // <select data-cascade-source data-target="#sectionSelect" data-endpoint="...">
    // The target select will be filled with sections for the chosen class.
    async function loadSections(classVal, target, preselectId) {
        target.disabled = true;
        target.innerHTML = '<option value="">Loading…</option>';
        if (!classVal) {
            target.innerHTML = '<option value="">— Select class first —</option>';
            target.disabled = false;
            return;
        }
        try {
            const r = await api(APP.api + '/sections.php?class=' + encodeURIComponent(classVal));
            const opts = ['<option value="">— Select section —</option>'];
            (r.data || []).forEach(s => {
                const sel = String(preselectId || '') === String(s.id) ? ' selected' : '';
                const lbl = s.section ? s.section : '(no section)';
                opts.push(`<option value="${s.id}"${sel}>${escapeHtml(lbl)}</option>`);
            });
            target.innerHTML = opts.join('');
        } catch (e) {
            Toast.error(e.message);
            target.innerHTML = '<option value="">— Failed —</option>';
        } finally {
            target.disabled = false;
        }
    }
    function bindCascade(src) {
        const target = $(src.dataset.target);
        if (!target) return;
        const preselect = target.dataset.preselect;
        // Initial load if a class is already chosen (e.g. on edit form)
        if (src.value) loadSections(src.value, target, preselect);
        src.addEventListener('change', () => loadSections(src.value, target));
    }

    // ---------- Photo file preview ----------
    // <input type="file" data-photo-preview="#previewImg">
    function bindPhotoPreview(input) {
        const target = $(input.dataset.photoPreview);
        if (!target) return;
        input.addEventListener('change', () => {
            const f = input.files && input.files[0];
            if (!f) return;
            const reader = new FileReader();
            reader.onload = e => {
                if (target.tagName === 'IMG') target.src = e.target.result;
                else target.style.backgroundImage = 'url(' + e.target.result + ')';
                target.classList.add('has-image');
                const empty = target.querySelector('.ph-empty');
                if (empty) empty.style.display = 'none';
            };
            reader.readAsDataURL(f);
        });
    }

    // ---------- Live theme preview + AJAX save ----------
    function applyTheme(primary, accent) {
        function adjust(hex, amt) {
            hex = (hex || '').replace('#', '');
            if (hex.length !== 6) return '#' + hex;
            let r = parseInt(hex.slice(0,2), 16);
            let g = parseInt(hex.slice(2,4), 16);
            let b = parseInt(hex.slice(4,6), 16);
            const d = Math.round(255 * amt / 100);
            r = Math.max(0, Math.min(255, r + d));
            g = Math.max(0, Math.min(255, g + d));
            b = Math.max(0, Math.min(255, b + d));
            return '#' + [r,g,b].map(n => n.toString(16).padStart(2,'0')).join('');
        }
        function rgbStr(hex) {
            hex = (hex || '').replace('#', '');
            if (hex.length !== 6) return '0,0,0';
            return [hex.slice(0,2), hex.slice(2,4), hex.slice(4,6)].map(s => parseInt(s, 16)).join(',');
        }
        const r = document.documentElement.style;
        r.setProperty('--primary',       primary);
        r.setProperty('--primary-light', adjust(primary, +12));
        r.setProperty('--primary-dark',  adjust(primary, -10));
        r.setProperty('--primary-rgb',   rgbStr(primary));
        r.setProperty('--accent',        accent);
        r.setProperty('--accent-light',  adjust(accent, +10));
        r.setProperty('--accent-dark',   adjust(accent, -15));
        r.setProperty('--accent-rgb',    rgbStr(accent));
        // Update visible swatch chips on the settings page
        const chipP = document.getElementById('chipPrimary');
        const chipA = document.getElementById('chipAccent');
        if (chipP) chipP.style.background = primary;
        if (chipA) chipA.style.background = accent;
    }
    window.applyTheme = applyTheme;

    function bindThemeForm() {
        const form = document.getElementById('themeForm');
        if (!form) return;
        const primary = form.querySelector('[name=theme_primary]');
        const accent  = form.querySelector('[name=theme_accent]');
        const liveUpdate = () => applyTheme(primary.value, accent.value);
        primary.addEventListener('input', liveUpdate);
        accent.addEventListener('input',  liveUpdate);

        // Preset cards (data-primary, data-accent)
        $$('.preset').forEach(p => {
            p.addEventListener('click', () => {
                primary.value = p.dataset.primary;
                accent.value  = p.dataset.accent;
                $$('.preset').forEach(x => x.classList.remove('active'));
                p.classList.add('active');
                liveUpdate();
            });
        });

        form.addEventListener('submit', async ev => {
            ev.preventDefault();
            const btn = form.querySelector('button[type=submit]');
            btn.classList.add('is-loading');
            btn.innerHTML = '<span class="spinner"></span> Saving…';
            try {
                const r = await api(APP.api + '/settings_save.php', {
                    method: 'POST',
                    body: { theme_primary: primary.value, theme_accent: accent.value }
                });
                Toast.success(r.msg || 'Theme saved.');
            } catch (e) {
                Toast.error(e.message);
            } finally {
                btn.classList.remove('is-loading');
                btn.innerHTML = '<i class="bi bi-check-lg"></i> Save Theme';
            }
        });
    }

    // ---------- Auto-bindings on DOMContentLoaded ----------
    document.addEventListener('DOMContentLoaded', function () {
        $$('[data-live-search]').forEach(bindLiveSearch);
        $$('[data-cascade-source]').forEach(bindCascade);
        $$('[data-photo-preview]').forEach(bindPhotoPreview);
        bindThemeForm();
    });

    // ---------- Renderers (used by data-renderer="…") ----------
    window.renderTeachersRows = function (rows) {
        if (!rows.length) {
            return '<tr><td colspan="9" style="text-align:center;color:var(--muted);padding:30px;">No teachers found.</td></tr>';
        }
        return rows.map(t => {
            const photo = t.photo_url
                ? `<img src="${escapeHtml(t.photo_url)}" alt="">`
                : escapeHtml((t.name || '?').charAt(0).toUpperCase());
            const status = t.status === 'active'
                ? '<span class="badge badge-success">Active</span>'
                : '<span class="badge badge-muted">Inactive</span>';
            const joined = t.joined_on_label || '—';
            return `
                <tr data-id="${t.id}">
                    <td>${t.id}</td>
                    <td><span class="avatar-sm">${photo}</span>${escapeHtml(t.name)}</td>
                    <td>${escapeHtml(t.designation || '—')}</td>
                    <td>${escapeHtml(t.subject || '—')}</td>
                    <td>${escapeHtml(t.email || '—')}</td>
                    <td>${escapeHtml(t.phone || '—')}</td>
                    <td>${joined}</td>
                    <td>${status}</td>
                    <td style="white-space:nowrap;">
                        <a class="icon-link" href="teachers.php?action=edit&id=${t.id}" title="Edit"><i class="bi bi-pencil"></i></a>
                        <a class="icon-link danger" href="#" data-ajax-delete="${APP.api}/teachers_delete.php?id=${t.id}" data-confirm="Delete this teacher?" title="Delete"><i class="bi bi-trash"></i></a>
                    </td>
                </tr>`;
        }).join('');
    };

    window.renderStudentsRows = function (rows) {
        if (!rows.length) {
            return '<tr><td colspan="8" style="text-align:center;color:var(--muted);padding:30px;">No students found.</td></tr>';
        }
        return rows.map(s => {
            const initial = escapeHtml((s.name || '?').charAt(0).toUpperCase());
            const status = s.status === 'active'
                ? '<span class="badge badge-success">Active</span>'
                : '<span class="badge badge-muted">Inactive</span>';
            return `
                <tr data-id="${s.id}">
                    <td>${escapeHtml(s.roll_no || '')}</td>
                    <td><span class="avatar-sm">${initial}</span>${escapeHtml(s.name)}</td>
                    <td>${escapeHtml(s.class_label || '—')}</td>
                    <td>${escapeHtml((s.gender || '').charAt(0).toUpperCase() + (s.gender || '').slice(1))}</td>
                    <td>${escapeHtml(s.parent_name || '—')}</td>
                    <td>${escapeHtml(s.phone || '—')}</td>
                    <td>${status}</td>
                    <td style="white-space:nowrap;">
                        <a class="icon-link" href="marksheet.php?student_id=${s.id}" title="Marksheet"><i class="bi bi-file-earmark-text"></i></a>
                        <a class="icon-link" href="students.php?action=edit&id=${s.id}" title="Edit"><i class="bi bi-pencil"></i></a>
                        <a class="icon-link danger" href="#" data-ajax-delete="${APP.api}/students_delete.php?id=${s.id}" data-confirm="Delete this student?" title="Delete"><i class="bi bi-trash"></i></a>
                    </td>
                </tr>`;
        }).join('');
    };
})();
