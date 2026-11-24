(function() {
    'use strict';

    // Retrieve global configuration
    const config = window.RaytechLogViewer || {};

    // State variables
    let activeKey = config.activeKey || '';
    let activeName = config.activeName || '';
    let activeLevels = new Set(['all']);
    let activeOrder = config.order || 'desc';
    const savedOrder = sessionStorage.getItem('log_order');
    if (savedOrder && (savedOrder === 'desc' || savedOrder === 'asc')) {
        activeOrder = savedOrder;
    }
    let autoRefreshInterval = null;
    let cachedEntries = Array.isArray(config.initialLines) ? config.initialLines : [];
    let entriesMap = new Map();

    // Real-time error alerts & tab title flashing
    let lastErrorCount = (config.stats && typeof config.stats.error === 'number') ? config.stats.error : null;
    const originalDocumentTitle = document.title;
    let titleFlashTimer = null;

    function flashTabTitle(message) {
        if (titleFlashTimer) clearInterval(titleFlashTimer);
        let isAlt = false;
        let count = 0;
        titleFlashTimer = setInterval(() => {
            document.title = isAlt ? message : originalDocumentTitle;
            isAlt = !isAlt;
            count++;
            if (count > 6) {
                clearInterval(titleFlashTimer);
                document.title = originalDocumentTitle;
                titleFlashTimer = null;
            }
        }, 800);
    }

    function syncEntriesMap() {
        entriesMap.clear();
        (cachedEntries || []).forEach(e => {
            const id = e.id || ('log-entry-' + e.num);
            entriesMap.set(id, e);
        });
    }
    syncEntriesMap();

    function buildDetailsHtml(entry) {
        let exceptionHtml = '';
        if (entry.exception) {
            exceptionHtml = `
                <div class="stack-exception-header">
                    <div class="exception-type-title"><i class="fa fa-bug"></i> ${escapeHtml(entry.exception.class)}</div>
                    <div class="exception-message-text">${escapeHtml(entry.exception.message)}</div>
                    <div class="exception-file-line">
                        in <strong>${escapeHtml(entry.exception.file)}</strong> on line <strong>${entry.exception.line}</strong>
                    </div>
                </div>
            `;
        }

        const hasFrames = Array.isArray(entry.stack_frames) && entry.stack_frames.length > 0;
        const hasContext = Boolean(entry.context_json);

        const tabsHeaderHtml = `
            <div class="details-tabs-header">
                ${hasFrames ? `
                    <button type="button" class="tab-link active" data-tab="trace">
                        <i class="fa fa-stream"></i> Stack Trace <span class="tab-count">(${entry.stack_frames.length})</span>
                    </button>
                ` : ''}
                ${hasContext ? `
                    <button type="button" class="tab-link ${!hasFrames ? 'active' : ''}" data-tab="context">
                        <i class="fa fa-database"></i> Context
                    </button>
                ` : ''}
                <button type="button" class="tab-link ${!hasFrames && !hasContext ? 'active' : ''}" data-tab="raw">
                    <i class="fa fa-code"></i> Raw Log
                </button>
                <div class="tab-actions-right">
                    <button type="button" class="btn-compact btn-copy-full" data-copy-text="${encodeURIComponent(entry.body_content || entry.full_text || entry.text || '')}" title="Copy all body content">
                        <i class="far fa-copy"></i> Copy Body Content
                    </button>
                </div>
            </div>
        `;

        let framesHtml = '';
        if (hasFrames) {
            framesHtml = '<div class="stack-trace-frames">';
            entry.stack_frames.forEach(frame => {
                framesHtml += `
                    <div class="stack-frame-row">
                        <span class="frame-number">#${frame.number}</span>
                        <div class="frame-detail">
                            ${frame.file ? `
                                <div class="frame-location">
                                    <span class="frame-filepath">${escapeHtml(frame.file)}</span>${frame.line ? `<span class="frame-colon">:</span><span class="frame-linenum">${frame.line}</span>` : ''}
                                </div>
                            ` : ''}
                            <div class="frame-callcode"><code>${escapeHtml(frame.call)}</code></div>
                        </div>
                    </div>
                `;
            });
            framesHtml += '</div>';
        }

        const contextHtml = hasContext
            ? `<pre class="context-json-code"><code>${escapeHtml(entry.context_json)}</code></pre>`
            : '';

        const escapedFull = escapeHtml(entry.body_content || entry.full_text || entry.text || '');
        const rawHtml = `<pre class="full-log-code"><code>${escapedFull}</code></pre>`;

        const tabsPanesHtml = `
            <div class="details-tab-panes">
                ${hasFrames ? `<div class="tab-pane active" data-pane="trace">${framesHtml}</div>` : ''}
                ${hasContext ? `<div class="tab-pane ${!hasFrames ? 'active' : ''}" data-pane="context">${contextHtml}</div>` : ''}
                <div class="tab-pane ${!hasFrames && !hasContext ? 'active' : ''}" data-pane="raw">${rawHtml}</div>
            </div>
        `;

        return exceptionHtml + tabsHeaderHtml + tabsPanesHtml;
    }

    function ensureDetailsRendered(entryEl) {
        if (!entryEl) return;
        const details = entryEl.querySelector('.entry-details');
        if (!details || details.getAttribute('data-rendered') === '1') {
            return;
        }
        const id = entryEl.id;
        const entry = entriesMap.get(id);
        if (entry) {
            details.innerHTML = buildDetailsHtml(entry);
            details.setAttribute('data-rendered', '1');
        }
    }

    const indexUrl = config.indexUrl || '';
    const clearUrl = config.clearUrl || '';
    const defaultTheme = config.defaultTheme || 'light';

    // CSRF Token resolution
    let csrfToken = config.csrfToken;
    if (!csrfToken) {
        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';
    }

    // DOM Elements
    const viewport = document.getElementById('logViewport');
    const searchInput = document.getElementById('logSearchInput');
    const searchClearBtn = document.getElementById('searchClearBtn');
    const searchCountBadge = document.getElementById('searchCountBadge');
    const loadingOverlay = document.getElementById('loadingOverlay');
    const sidebar = document.getElementById('sidebarFiles');

    // On initial load, keep scroll at top where newest logs appear
    if (viewport) {
        viewport.scrollTop = 0;
    }

    // Theme Management
    const savedTheme = localStorage.getItem('log_theme') || localStorage.getItem('raytech_log_theme') || defaultTheme;
    document.documentElement.setAttribute('data-theme', savedTheme);
    updateThemeIcon(savedTheme);

    const btnToggleTheme = document.getElementById('btnToggleTheme');
    if (btnToggleTheme) {
        btnToggleTheme.addEventListener('click', function() {
            const current = document.documentElement.getAttribute('data-theme');
            const next = current === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', next);
            localStorage.setItem('log_theme', next);
            localStorage.setItem('raytech_log_theme', next);
            updateThemeIcon(next);
        });
    }

    function updateThemeIcon(theme) {
        const icon = document.getElementById('themeIcon');
        if (icon) {
            icon.className = theme === 'dark' ? 'fa fa-sun' : 'fa fa-moon';
        }
    }

    // Toggle Sidebar
    const btnToggleSidebar = document.getElementById('btnToggleSidebar');
    if (btnToggleSidebar && sidebar) {
        btnToggleSidebar.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
        });
    }

    // Power-User Keyboard Navigation (j / k / Enter / Space / c / / / Esc)
    let selectedEntryIndex = -1;

    function getVisibleEntries() {
        return Array.from(document.querySelectorAll('.log-entry')).filter(el => el.style.display !== 'none');
    }

    function selectKeyboardEntry(index) {
        const visible = getVisibleEntries();
        if (visible.length === 0) return;

        visible.forEach(el => el.classList.remove('keyboard-selected'));

        if (index < 0) index = 0;
        if (index >= visible.length) index = visible.length - 1;

        selectedEntryIndex = index;
        const target = visible[selectedEntryIndex];
        if (target) {
            target.classList.add('keyboard-selected');
            target.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }

    // Keyboard Shortcuts & Power-User Controls
    window.addEventListener('keydown', function(e) {
        const tag = (document.activeElement && document.activeElement.tagName) ? document.activeElement.tagName.toLowerCase() : '';
        const isEditing = (tag === 'input' || tag === 'textarea' || tag === 'select');

        // Toggle Sidebar with Ctrl+B
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'b') {
            e.preventDefault();
            if (sidebar) sidebar.classList.toggle('collapsed');
            return;
        }

        // Search Focus with Ctrl+F
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'f') {
            e.preventDefault();
            if (searchInput) {
                searchInput.focus();
                searchInput.select();
            }
            return;
        }

        // Quick Search with '/' when not editing text
        if (e.key === '/' && !isEditing) {
            e.preventDefault();
            if (searchInput) {
                searchInput.focus();
                searchInput.select();
            }
            return;
        }

        // Escape handling
        if (e.key === 'Escape') {
            if (document.activeElement === searchInput) {
                clearSearch();
            } else {
                const visible = getVisibleEntries();
                visible.forEach(el => el.classList.remove('keyboard-selected'));
                selectedEntryIndex = -1;
            }
            return;
        }

        // Vim & Arrow navigation when outside input fields
        if (!isEditing && !e.ctrlKey && !e.metaKey && !e.altKey) {
            if (e.key === 'j' || e.key === 'ArrowDown') {
                e.preventDefault();
                selectKeyboardEntry(selectedEntryIndex + 1);
            } else if (e.key === 'k' || e.key === 'ArrowUp') {
                e.preventDefault();
                selectKeyboardEntry(selectedEntryIndex - 1);
            } else if (e.key === 'Enter' || e.key === ' ') {
                if (selectedEntryIndex >= 0) {
                    const visible = getVisibleEntries();
                    const current = visible[selectedEntryIndex];
                    if (current) {
                        e.preventDefault();
                        const summary = current.querySelector('.entry-summary');
                        if (summary) summary.click();
                    }
                }
            } else if (e.key.toLowerCase() === 'c') {
                if (selectedEntryIndex >= 0) {
                    const visible = getVisibleEntries();
                    const current = visible[selectedEntryIndex];
                    if (current) {
                        e.preventDefault();
                        const copyBtn = current.querySelector('.btn-entry-copy');
                        if (copyBtn) copyBtn.click();
                    }
                }
            }
        }
    });

    // Toggle Word Wrap with active session persistence
    const btnToggleWrap = document.getElementById('btnToggleWrap');
    if (btnToggleWrap && viewport) {
        const savedWrap = sessionStorage.getItem('log_word_wrap');
        if (savedWrap === '1') {
            viewport.classList.add('nowrap');
            btnToggleWrap.classList.add('active');
        }
        btnToggleWrap.addEventListener('click', function() {
            viewport.classList.toggle('nowrap');
            this.classList.toggle('active');
            sessionStorage.setItem('log_word_wrap', viewport.classList.contains('nowrap') ? '1' : '0');
        });
    }

    // Fullscreen Toggle
    const btnToggleFullscreen = document.getElementById('btnToggleFullscreen');
    if (btnToggleFullscreen) {
        btnToggleFullscreen.addEventListener('click', function() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().catch(() => {});
            } else {
                document.exitFullscreen().catch(() => {});
            }
        });
    }

    // Scroll Top / Bottom buttons
    const btnScrollTop = document.getElementById('btnScrollTop');
    if (btnScrollTop && viewport) {
        btnScrollTop.addEventListener('click', function() {
            viewport.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }
    const btnScrollBottom = document.getElementById('btnScrollBottom');
    if (btnScrollBottom && viewport) {
        btnScrollBottom.addEventListener('click', function() {
            viewport.scrollTo({ top: viewport.scrollHeight, behavior: 'smooth' });
        });
    }

    // Order Toggle Button (Newest First / Oldest First with active session persistence)
    const btnToggleOrder = document.getElementById('btnToggleOrder');
    if (btnToggleOrder) {
        updateOrderButton();
        btnToggleOrder.addEventListener('click', function() {
            activeOrder = activeOrder === 'desc' ? 'asc' : 'desc';
            sessionStorage.setItem('log_order', activeOrder);
            updateOrderButton();
            reloadLogs(false);
        });
    }

    function updateOrderButton() {
        const icon = document.getElementById('iconOrder');
        const text = document.getElementById('lblOrder');
        if (activeOrder === 'desc') {
            if (icon) icon.className = 'fa fa-sort-amount-down';
            if (text) text.textContent = 'Newest First';
        } else {
            if (icon) icon.className = 'fa fa-sort-amount-up';
            if (text) text.textContent = 'Oldest First';
        }
    }

    // Expand All / Collapse All Toggle Button
    const btnToggleExpandAll = document.getElementById('btnToggleExpandAll');
    if (btnToggleExpandAll) {
        btnToggleExpandAll.addEventListener('click', function() {
            const allEntries = document.querySelectorAll('.log-entry.has-details');
            const anyCollapsed = Array.from(allEntries).some(e => !e.classList.contains('expanded'));

            allEntries.forEach(entry => {
                const details = entry.querySelector('.entry-details');
                if (!details) return;

                if (anyCollapsed) {
                    ensureDetailsRendered(entry);
                    entry.classList.add('expanded');
                    details.style.display = 'block';
                } else {
                    entry.classList.remove('expanded');
                    details.style.display = 'none';
                }
            });

            updateExpandAllLabel();
        });
    }

    function updateExpandAllLabel() {
        const icon = document.getElementById('iconExpandAll');
        const text = document.getElementById('lblExpandAll');
        const allEntries = document.querySelectorAll('.log-entry.has-details');
        const allExpanded = allEntries.length > 0 && Array.from(allEntries).every(e => e.classList.contains('expanded'));

        if (allExpanded) {
            if (icon) icon.className = 'fa fa-compress-alt';
            if (text) text.textContent = 'Collapse All';
        } else {
            if (icon) icon.className = 'fa fa-expand-alt';
            if (text) text.textContent = 'Expand All';
        }
    }

    // Delegated click handler on viewport for log entries (Expand/Collapse, Tabs & Copy)
    if (viewport) {
        viewport.addEventListener('click', function(e) {
            // 1. Copy button click (summary or details)
            const copyBtn = e.target.closest('.btn-entry-copy, .btn-copy-full');
            if (copyBtn) {
                e.stopPropagation();
                const rawText = copyBtn.getAttribute('data-copy-text');
                const textToCopy = rawText ? decodeURIComponent(rawText) : '';
                if (textToCopy) {
                    navigator.clipboard.writeText(textToCopy).then(() => {
                        showToast('Log copied to clipboard', 'success');
                    }).catch(() => {
                        showToast('Failed to copy', 'error');
                    });
                }
                return;
            }

            // 2. Tab switching inside expanded details
            const tabBtn = e.target.closest('.tab-link');
            if (tabBtn) {
                e.stopPropagation();
                const targetTab = tabBtn.getAttribute('data-tab');
                const details = tabBtn.closest('.entry-details');
                if (!details) return;

                details.querySelectorAll('.tab-link').forEach(btn => btn.classList.remove('active'));
                tabBtn.classList.add('active');

                details.querySelectorAll('.tab-pane').forEach(pane => {
                    if (pane.getAttribute('data-pane') === targetTab) {
                        pane.classList.add('active');
                    } else {
                        pane.classList.remove('active');
                    }
                });
                return;
            }

            // 3. Click on summary row -> toggle details expansion
            const summary = e.target.closest('.entry-summary');
            if (summary) {
                const entry = summary.closest('.log-entry');
                if (!entry) return;

                const details = entry.querySelector('.entry-details');
                if (details) {
                    const isExpanded = entry.classList.contains('expanded');
                    if (isExpanded) {
                        entry.classList.remove('expanded');
                        details.style.display = 'none';
                    } else {
                        ensureDetailsRendered(entry);
                        entry.classList.add('expanded');
                        details.style.display = 'block';
                    }
                    updateExpandAllLabel();
                }
            }
        });
    }

    // Filter Sidebar Files
    const filterFilesInput = document.getElementById('filterFilesInput');
    if (filterFilesInput) {
        filterFilesInput.addEventListener('input', function() {
            applySidebarFilter();
        });
    }

    // Sidebar Date-Picker Jump for Daily Logs
    const datePickerJump = document.getElementById('datePickerJump');
    const btnClearDateJump = document.getElementById('btnClearDateJump');

    if (datePickerJump) {
        // If active log file contains a date (YYYY-MM-DD), pre-fill date picker
        const activeDateMatch = (activeName || '').match(/\d{4}-\d{2}-\d{2}/);
        if (activeDateMatch) {
            datePickerJump.value = activeDateMatch[0];
            if (btnClearDateJump) btnClearDateJump.style.display = 'flex';
        }

        datePickerJump.addEventListener('change', function() {
            const selectedDate = this.value; // 'YYYY-MM-DD'
            if (!selectedDate) {
                if (btnClearDateJump) btnClearDateJump.style.display = 'none';
                applySidebarFilter();
                return;
            }

            if (btnClearDateJump) btnClearDateJump.style.display = 'flex';

            const items = document.querySelectorAll('.sidebar .file-item');
            let matchedItem = null;
            let matchCount = 0;

            items.forEach(item => {
                const name = item.getAttribute('data-name') || '';
                if (name.includes(selectedDate)) {
                    item.style.display = 'flex';
                    matchCount++;
                    if (!matchedItem) {
                        matchedItem = item;
                    }
                } else {
                    item.style.display = 'none';
                }
            });

            if (matchedItem) {
                const matchedKey = matchedItem.getAttribute('data-key');
                if (matchedKey && matchedKey !== activeKey) {
                    matchedItem.click();
                } else {
                    showToast(`Active log matches date: ${selectedDate}`, 'success');
                }
            } else {
                showToast(`No log file found for date ${selectedDate}`, 'error');
            }
        });
    }

    if (btnClearDateJump) {
        btnClearDateJump.addEventListener('click', function() {
            if (datePickerJump) {
                datePickerJump.value = '';
            }
            this.style.display = 'none';
            applySidebarFilter();
        });
    }

    function applySidebarFilter() {
        const textTerm = filterFilesInput ? filterFilesInput.value.toLowerCase().trim() : '';
        const dateTerm = datePickerJump ? datePickerJump.value.trim() : '';
        const items = document.querySelectorAll('.sidebar .file-item');

        items.forEach(item => {
            const name = item.getAttribute('data-name') || '';
            const textMatch = !textTerm || name.includes(textTerm);
            const dateMatch = !dateTerm || name.includes(dateTerm);

            if (textMatch && dateMatch) {
                item.style.display = 'flex';
            } else {
                item.style.display = 'none';
            }
        });
    }

    // Multi-Level Filtering with active session persistence
    const pillBadges = document.querySelectorAll('.pill-badge[data-filter]');
    const savedLevels = sessionStorage.getItem('log_active_levels');
    if (savedLevels) {
        try {
            const parsed = JSON.parse(savedLevels);
            if (Array.isArray(parsed) && parsed.length > 0) {
                activeLevels = new Set(parsed);
                pillBadges.forEach(b => {
                    const bFilter = b.getAttribute('data-filter');
                    if (activeLevels.has(bFilter)) {
                        b.classList.add('active');
                    } else {
                        b.classList.remove('active');
                    }
                });
            }
        } catch (e) {}
    }

    pillBadges.forEach(badge => {
        badge.addEventListener('click', function() {
            const filter = this.getAttribute('data-filter');

            if (filter === 'all') {
                // Clicking 'all' resets everything to only 'all'
                activeLevels.clear();
                activeLevels.add('all');
            } else {
                // If 'all' was active, deactivate it
                if (activeLevels.has('all')) {
                    activeLevels.delete('all');
                }

                // Toggle the clicked level
                if (activeLevels.has(filter)) {
                    activeLevels.delete(filter);
                } else {
                    activeLevels.add(filter);
                }

                // If all levels were deselected, fallback to 'all'
                if (activeLevels.size === 0) {
                    activeLevels.add('all');
                }
            }

            // Update UI active styles on pills
            pillBadges.forEach(b => {
                const bFilter = b.getAttribute('data-filter');
                if (activeLevels.has(bFilter)) {
                    b.classList.add('active');
                } else {
                    b.classList.remove('active');
                }
            });

            sessionStorage.setItem('log_active_levels', JSON.stringify([...activeLevels]));
            applyFilters();
        });
    });

    // Live Search in Log Lines (Synchronized with 60 FPS requestAnimationFrame)
    let searchDebounce = null;
    let searchRaf = null;

    // URL search query parameter resolution (?search=... or ?q=... or ?query=...)
    try {
        const urlParams = new URLSearchParams(window.location.search);
        const queryTerm = urlParams.get('search') || urlParams.get('q') || urlParams.get('query');
        if (queryTerm && searchInput) {
            searchInput.value = queryTerm;
            if (searchClearBtn) searchClearBtn.style.display = 'block';
        }
    } catch (e) {}

    function scheduleFilter() {
        if (searchRaf) {
            cancelAnimationFrame(searchRaf);
        }
        clearTimeout(searchDebounce);
        searchDebounce = setTimeout(() => {
            searchRaf = requestAnimationFrame(applyFilters);
        }, 80);
    }

    if (searchInput) {
        searchInput.addEventListener('input', scheduleFilter);
    }

    if (searchClearBtn) {
        searchClearBtn.addEventListener('click', clearSearch);
    }

    function clearSearch() {
        if (!searchInput) return;
        searchInput.value = '';
        if (searchClearBtn) searchClearBtn.style.display = 'none';
        if (searchCountBadge) searchCountBadge.style.display = 'none';
        if (searchRaf) cancelAnimationFrame(searchRaf);
        searchRaf = requestAnimationFrame(applyFilters);
        searchInput.focus();
    }

    function applyFilters() {
        if (!searchInput) return;
        const searchTerm = searchInput.value.trim();
        const termLower = searchTerm.toLowerCase();

        if (searchTerm.length > 0) {
            if (searchClearBtn) searchClearBtn.style.display = 'block';
        } else {
            if (searchClearBtn) searchClearBtn.style.display = 'none';
            if (searchCountBadge) searchCountBadge.style.display = 'none';
        }

        let matchCount = 0;
        let visibleCount = 0;

        if (cachedEntries && cachedEntries.length > 0) {
            // High-performance in-memory search (evaluates thousands of lines in < 2ms)
            cachedEntries.forEach(entry => {
                const el = document.getElementById(entry.id || ('log-entry-' + entry.num));
                if (!el) return;

                const level = entry.level;
                const shortText = entry.header_text || entry.message || entry.short_text || '';
                const fullText = entry.body_content || entry.full_text || shortText;

                // Level match
                let levelMatch = false;
                if (activeLevels.has('all')) {
                    levelMatch = true;
                } else {
                    if (activeLevels.has('error') && (level === 'error' || level === 'critical' || level === 'alert' || level === 'emergency')) {
                        levelMatch = true;
                    }
                    if (activeLevels.has('warning') && (level === 'warning' || level === 'notice')) {
                        levelMatch = true;
                    }
                    if (activeLevels.has('info') && level === 'info') {
                        levelMatch = true;
                    }
                    if (activeLevels.has('debug') && level === 'debug') {
                        levelMatch = true;
                    }
                    if (activeLevels.has(level)) {
                        levelMatch = true;
                    }
                }

                // Search match
                let searchMatch = true;
                if (searchTerm.length > 0) {
                    const inShort = shortText.toLowerCase().includes(termLower);
                    const inFull = inShort || fullText.toLowerCase().includes(termLower);

                    if (inFull) {
                        searchMatch = true;
                        matchCount++;

                        const shortTextEl = el.querySelector('.entry-short-text') || el.querySelector('.line-text');
                        if (shortTextEl) {
                            highlightMatch(shortTextEl, shortText, searchTerm);
                        }

                        if (!inShort) {
                            ensureDetailsRendered(el);
                            const details = el.querySelector('.entry-details');
                            if (details) {
                                el.classList.add('expanded');
                                details.style.display = 'block';
                            }
                        }
                    } else {
                        searchMatch = false;
                        const shortTextEl = el.querySelector('.entry-short-text') || el.querySelector('.line-text');
                        if (shortTextEl) shortTextEl.textContent = shortText;
                    }
                } else {
                    const shortTextEl = el.querySelector('.entry-short-text') || el.querySelector('.line-text');
                    if (shortTextEl) shortTextEl.textContent = shortText;
                }

                const isVisible = levelMatch && searchMatch;
                if (isVisible) {
                    if (el.style.display !== '') el.style.display = '';
                    visibleCount++;
                } else {
                    if (el.style.display !== 'none') el.style.display = 'none';
                }
            });
        } else {
            // DOM traversal fallback
            const entries = document.querySelectorAll('.log-entry, .log-line');
            entries.forEach(entry => {
                const level = entry.getAttribute('data-level');
                const shortTextEl = entry.querySelector('.entry-short-text') || entry.querySelector('.line-text');
                const codeEl = entry.querySelector('.full-log-code code');

                const shortText = shortTextEl ? (shortTextEl.getAttribute('data-original') || shortTextEl.textContent) : '';
                if (shortTextEl && !shortTextEl.getAttribute('data-original')) {
                    shortTextEl.setAttribute('data-original', shortText);
                }

                const fullText = codeEl ? codeEl.textContent : shortText;

                let levelMatch = false;
                if (activeLevels.has('all')) {
                    levelMatch = true;
                } else {
                    if (activeLevels.has('error') && (level === 'error' || level === 'critical' || level === 'alert' || level === 'emergency')) {
                        levelMatch = true;
                    }
                    if (activeLevels.has('warning') && (level === 'warning' || level === 'notice')) {
                        levelMatch = true;
                    }
                    if (activeLevels.has('info') && level === 'info') {
                        levelMatch = true;
                    }
                    if (activeLevels.has('debug') && level === 'debug') {
                        levelMatch = true;
                    }
                    if (activeLevels.has(level)) {
                        levelMatch = true;
                    }
                }

                let searchMatch = true;
                if (searchTerm.length > 0) {
                    const inShort = shortText.toLowerCase().includes(termLower);
                    const inFull = fullText.toLowerCase().includes(termLower);

                    if (inShort || inFull) {
                        searchMatch = true;
                        matchCount++;

                        if (shortTextEl) {
                            highlightMatch(shortTextEl, shortText, searchTerm);
                        }

                        if (inFull && !inShort) {
                            ensureDetailsRendered(entry);
                            const details = entry.querySelector('.entry-details');
                            if (details) {
                                entry.classList.add('expanded');
                                details.style.display = 'block';
                            }
                        }
                    } else {
                        searchMatch = false;
                        if (shortTextEl) shortTextEl.textContent = shortText;
                    }
                } else {
                    if (shortTextEl) shortTextEl.textContent = shortText;
                }

                if (levelMatch && searchMatch) {
                    if (entry.style.display !== '') entry.style.display = '';
                    visibleCount++;
                } else {
                    if (entry.style.display !== 'none') entry.style.display = 'none';
                }
            });
        }

        if (searchCountBadge && searchTerm.length > 0) {
            searchCountBadge.textContent = matchCount + ' ' + (matchCount === 1 ? 'match' : 'matches');
            searchCountBadge.style.display = 'inline-flex';
        }
    }

    function highlightMatch(element, text, term) {
        element.textContent = '';
        if (!term) {
            element.textContent = text;
            return;
        }

        const regex = new RegExp(escapeRegExp(term), 'gi');
        let lastIndex = 0;
        let match;
        const fragment = document.createDocumentFragment();

        while ((match = regex.exec(text)) !== null) {
            if (match.index > lastIndex) {
                fragment.appendChild(document.createTextNode(text.substring(lastIndex, match.index)));
            }
            const mark = document.createElement('mark');
            mark.className = 'search-match';
            mark.textContent = match[0];
            fragment.appendChild(mark);
            lastIndex = regex.lastIndex;
        }

        if (lastIndex < text.length) {
            fragment.appendChild(document.createTextNode(text.substring(lastIndex)));
        }

        element.appendChild(fragment);
    }

    function escapeRegExp(string) {
        return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    // Helper to keep sidebar links synchronized with the active session line limit
    function updateSidebarLinksLineLimit(limit) {
        if (!limit) return;
        document.querySelectorAll('.sidebar .file-item').forEach(link => {
            try {
                const href = link.getAttribute('href');
                if (href) {
                    const url = new URL(href, window.location.origin);
                    url.searchParams.set('lines', limit);
                    link.setAttribute('href', url.pathname + url.search);
                }
            } catch (e) {}
        });
    }

    // Lines Limit change with active session persistence
    const selLineLimit = document.getElementById('selLineLimit');
    if (selLineLimit) {
        function initLineLimitState() {
            const savedLines = sessionStorage.getItem('log_line_limit');
            if (savedLines !== null && Array.from(selLineLimit.options).some(o => o.value === savedLines)) {
                if (selLineLimit.value !== savedLines) {
                    selLineLimit.value = savedLines;
                    updateSidebarLinksLineLimit(savedLines);
                    reloadLogs(false);
                    return;
                }
            }
            updateSidebarLinksLineLimit(selLineLimit.value);
        }
        initLineLimitState();

        selLineLimit.addEventListener('change', function() {
            sessionStorage.setItem('log_line_limit', this.value);
            updateSidebarLinksLineLimit(this.value);
            reloadLogs(false);
        });
    }

    // Manual Refresh
    const btnManualRefresh = document.getElementById('btnManualRefresh');
    if (btnManualRefresh) {
        btnManualRefresh.addEventListener('click', function() {
            reloadLogs(false);
        });
    }

    // Auto Refresh with active session persistence
    const selAutoRefresh = document.getElementById('selAutoRefresh');
    if (selAutoRefresh) {
        selAutoRefresh.addEventListener('change', function() {
            sessionStorage.setItem('log_auto_refresh', this.value);
            setupAutoRefresh();

            // Notify backend session in background
            if (indexUrl) {
                const separator = indexUrl.includes('?') ? '&' : '?';
                fetch(`${indexUrl}${separator}auto_refresh=${encodeURIComponent(this.value)}&ajax=1`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                }).catch(() => {});
            }
        });
    }

    function setupAutoRefresh() {
        if (autoRefreshInterval) {
            clearInterval(autoRefreshInterval);
            autoRefreshInterval = null;
        }
        if (!selAutoRefresh) return;
        const val = parseInt(selAutoRefresh.value, 10);
        const spinIcon = document.getElementById('refreshSpinIcon');
        if (val > 0) {
            if (spinIcon) spinIcon.classList.add('fa-spin');
            autoRefreshInterval = setInterval(() => {
                reloadLogs(true);
            }, val);
        } else {
            if (spinIcon) spinIcon.classList.remove('fa-spin');
        }
    }

    function initAutoRefreshState() {
        if (!selAutoRefresh) return;
        const savedAuto = sessionStorage.getItem('log_auto_refresh');
        if (savedAuto !== null && Array.from(selAutoRefresh.options).some(o => o.value === savedAuto)) {
            selAutoRefresh.value = savedAuto;
        }
        setupAutoRefresh();
    }
    initAutoRefreshState();

    // Reload Logs via AJAX without full page flicker
    function reloadLogs(silent = false) {
        if (!activeKey || !indexUrl) return;
        const lineLimit = selLineLimit ? selLineLimit.value : 500;
        const autoRefreshVal = selAutoRefresh ? selAutoRefresh.value : '';
        if (!silent && loadingOverlay) {
            loadingOverlay.classList.add('active');
        }

        const separator = indexUrl.includes('?') ? '&' : '?';
        const url = `${indexUrl}${separator}key=${encodeURIComponent(activeKey)}&lines=${encodeURIComponent(lineLimit)}&order=${encodeURIComponent(activeOrder)}&auto_refresh=${encodeURIComponent(autoRefreshVal)}&ajax=1`;

        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data && data.status) {
                renderLogs(data, silent);
            }
        })
        .catch(err => {
            console.error('Failed to refresh log', err);
        })
        .finally(() => {
            if (loadingOverlay) loadingOverlay.classList.remove('active');
        });
    }

    function renderLogs(data, isAutoRefresh = false) {
        if (!viewport) return;
        cachedEntries = Array.isArray(data.lines) ? data.lines : [];

        // Real-time incoming error notification during auto-refresh
        if (data.stats && typeof data.stats.error === 'number') {
            const currentErrors = data.stats.error;
            if (isAutoRefresh && lastErrorCount !== null && currentErrors > lastErrorCount) {
                const diff = currentErrors - lastErrorCount;
                showToast(`⚠️ ${diff} new error(s) detected!`, 'error');
                flashTabTitle(`🔴 (${diff}) New Error!`);
            }
            lastErrorCount = currentErrors;
        }

        // Update stats
        if (data.stats) {
            const statAll = document.getElementById('statAll');
            const statError = document.getElementById('statError');
            const statWarn = document.getElementById('statWarn');
            const statInfo = document.getElementById('statInfo');
            if (statAll) statAll.textContent = `(${data.stats.total || 0})`;
            if (statError) statError.textContent = `(${data.stats.error || 0})`;
            if (statWarn) statWarn.textContent = `(${data.stats.warning || 0})`;
            if (statInfo) statInfo.textContent = `(${data.stats.info || 0})`;
        }

        // Update Active File Header
        if (data.file) {
            const sizeEl = document.getElementById('activeFileSize');
            if (sizeEl) {
                sizeEl.textContent = data.file.size_formatted;
            }
            const modEl = document.getElementById('activeFileModified');
            if (modEl) {
                modEl.innerHTML = `<i class="far fa-clock"></i> ${data.file.modified}`;
            }
        }

        // If empty lines
        if (!data.lines || data.lines.length === 0) {
            viewport.innerHTML = `
                <div class="empty-log-state">
                    <i class="far fa-check-circle" style="color: #2ea043; opacity: 0.7;"></i>
                    <h4 style="font-weight: 600;">Log file is empty</h4>
                    <p style="font-size: 11.5px; max-width: 400px;">
                        <code>${data.file ? data.file.name : ''}</code> contains 0 bytes. No logs or error records found.
                    </p>
                </div>
            `;
            return;
        }

        // Build HTML for structured entries (Lazy Details)
        function buildEntryHtml(entry) {
            const escapedShort = escapeHtml(entry.header_text || entry.message || entry.short_text || entry.text || '');

            let levelIcon = '<i class="fa fa-terminal log-level-icon icon-default" title="Log"></i>';
            if (entry.level === 'error') {
                levelIcon = '<i class="fa fa-times-circle log-level-icon icon-error" title="Error"></i>';
            } else if (entry.level === 'warning') {
                levelIcon = '<i class="fa fa-exclamation-triangle log-level-icon icon-warning" title="Warning"></i>';
            } else if (entry.level === 'info') {
                levelIcon = '<i class="fa fa-info-circle log-level-icon icon-info" title="Info"></i>';
            } else if (entry.level === 'debug') {
                levelIcon = '<i class="fa fa-bug log-level-icon icon-debug" title="Debug"></i>';
            }

            const chevron = '<i class="fa fa-chevron-right expand-chevron"></i>';

            let countBadge = '';
            if (entry.frames_count && entry.frames_count > 0) {
                countBadge = `<span class="badge-lines" title="${entry.frames_count} stack frames"><i class="fa fa-layer-group"></i> ${entry.frames_count} frames</span>`;
            } else if (entry.lines_count && entry.lines_count > 1) {
                countBadge = `<span class="badge-lines" title="${entry.lines_count} lines"><i class="fa fa-layer-group"></i> +${entry.lines_count - 1}</span>`;
            }

            const entryId = entry.id || ('log-entry-' + entry.num);

            return `
                <div class="log-entry level-${entry.level} has-details" 
                     id="${entryId}" 
                     data-level="${entry.level}" 
                     data-num="${entry.num}"
                     data-has-details="1">
                    
                    <div class="header-group entry-summary" title="Click to view full content">
                        <div class="col-expand">
                            ${chevron}
                            ${levelIcon}
                        </div>
                        <div class="col-type">
                            <span class="log-type-tag tag-${entry.level}">${escapeHtml(entry.log_type || entry.level.toUpperCase())}</span>
                        </div>
                        <div class="col-date">
                            <span class="log-date-text">${escapeHtml(entry.log_create_date || entry.timestamp || '')}</span>
                        </div>
                        <div class="col-message">
                            <span class="entry-short-text header-text">${escapedShort}</span>
                        </div>
                        <div class="col-actions">
                            ${countBadge}
                            <button type="button" class="btn-entry-copy" title="Copy body content" data-copy-text="${encodeURIComponent(entry.body_content || entry.full_text || entry.text || '')}">
                                <i class="far fa-copy"></i>
                            </button>
                        </div>
                    </div>

                    <div class="entry-details body-content-panel" style="display: none;" data-rendered="0"></div>
                </div>
            `;
        }

        let html = '<div id="linesWrapper">';
        html += `
            <div class="log-table-header">
                <div class="th-expand"></div>
                <div class="th-type">Log Type</div>
                <div class="th-date">Log Create Date</div>
                <div class="th-message">Header Text</div>
                <div class="th-actions">Actions</div>
            </div>
        `;

        if (Array.isArray(data.lines)) {
            data.lines.forEach(entry => {
                html += buildEntryHtml(entry);
            });
        }
        html += '</div>';

        viewport.innerHTML = html;

        // When order is newest first ('desc'), stay at top; otherwise stay at bottom
        if (activeOrder === 'desc') {
            viewport.scrollTop = 0;
        }

        syncEntriesMap();
        applyFilters();
        updateExpandAllLabel();
    }

    function escapeHtml(text) {
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return String(text).replace(/[&<>"']/g, m => map[m]);
    }

    // Copy to Clipboard (all visible lines)
    const btnCopy = document.getElementById('btnCopyLog');
    if (btnCopy) {
        btnCopy.addEventListener('click', function() {
            const visibleEntries = Array.from(document.querySelectorAll('.log-entry, .log-line'))
                .filter(el => el.style.display !== 'none')
                .map(el => {
                    const code = el.querySelector('.full-log-code code');
                    if (code) return code.textContent;
                    const textEl = el.querySelector('.entry-short-text') || el.querySelector('.line-text');
                    return textEl ? textEl.textContent : '';
                })
                .join('\n');

            if (!visibleEntries) {
                showToast('No lines to copy', 'error');
                return;
            }

            navigator.clipboard.writeText(visibleEntries).then(() => {
                showToast('Copied visible logs to clipboard', 'success');
            }).catch(() => {
                showToast('Clipboard access denied', 'error');
            });
        });
    }

    // =========================================================================
    // Export Filtered Logs (JSON & CSV)
    // =========================================================================
    const btnExportToggle = document.getElementById('btnExportToggle');
    const exportDropdownMenu = document.getElementById('exportDropdownMenu');
    const exportDropdownWrap = document.getElementById('exportDropdownWrap');

    if (btnExportToggle && exportDropdownMenu) {
        btnExportToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            const isShown = exportDropdownMenu.style.display === 'block';
            exportDropdownMenu.style.display = isShown ? 'none' : 'block';
        });

        document.addEventListener('click', function(e) {
            if (exportDropdownWrap && !exportDropdownWrap.contains(e.target)) {
                exportDropdownMenu.style.display = 'none';
            }
        });
    }

    const btnExportJson = document.getElementById('btnExportJson');
    if (btnExportJson) {
        btnExportJson.addEventListener('click', function(e) {
            e.preventDefault();
            if (exportDropdownMenu) exportDropdownMenu.style.display = 'none';
            exportFilteredLogs('json');
        });
    }

    const btnExportCsv = document.getElementById('btnExportCsv');
    if (btnExportCsv) {
        btnExportCsv.addEventListener('click', function(e) {
            e.preventDefault();
            if (exportDropdownMenu) exportDropdownMenu.style.display = 'none';
            exportFilteredLogs('csv');
        });
    }

    function exportFilteredLogs(format) {
        const visibleEntries = Array.from(document.querySelectorAll('.log-entry, .log-line'))
            .filter(el => el.style.display !== 'none');

        if (visibleEntries.length === 0) {
            showToast('No filtered logs to export', 'error');
            return;
        }

        const dateStr = new Date().toISOString().replace(/[:.]/g, '-').slice(0, 19);
        const baseName = (activeName || 'laravel.log').replace(/\.[^/.]+$/, '');
        const filename = `${baseName}_filtered_${dateStr}.${format}`;

        if (format === 'json') {
            const exportData = visibleEntries.map((el, index) => {
                const level = el.getAttribute('data-level') || 'info';
                const typeEl = el.querySelector('.col-type .log-type-tag');
                const dateEl = el.querySelector('.col-date .log-date-text');
                const shortTextEl = el.querySelector('.entry-short-text') || el.querySelector('.line-text');
                const codeEl = el.querySelector('.full-log-code code');
                const excTitleEl = el.querySelector('.exception-type-title');
                const excMsgEl = el.querySelector('.exception-message-text');
                const excFileLineEl = el.querySelector('.exception-file-line');
                const contextEl = el.querySelector('.context-json-code code');

                let exception = null;
                if (excTitleEl || excMsgEl) {
                    exception = {
                        class: excTitleEl ? excTitleEl.textContent.trim() : '',
                        message: excMsgEl ? excMsgEl.textContent.trim() : '',
                        file_line: excFileLineEl ? excFileLineEl.textContent.trim() : ''
                    };
                }

                let context = null;
                if (contextEl && contextEl.textContent.trim()) {
                    try {
                        context = JSON.parse(contextEl.textContent.trim());
                    } catch (e) {
                        context = contextEl.textContent.trim();
                    }
                }

                return {
                    number: index + 1,
                    level: level,
                    type: typeEl ? typeEl.textContent.trim() : level.toUpperCase(),
                    timestamp: dateEl ? dateEl.textContent.trim() : '',
                    message: shortTextEl ? (shortTextEl.getAttribute('data-original') || shortTextEl.textContent.trim()) : '',
                    exception: exception,
                    context: context,
                    full_log: codeEl ? codeEl.textContent : (shortTextEl ? shortTextEl.textContent : '')
                };
            });

            const blob = new Blob([JSON.stringify(exportData, null, 2)], { type: 'application/json;charset=utf-8;' });
            downloadBlob(blob, filename);
            showToast(`Exported ${exportData.length} records as JSON`, 'success');
        } else if (format === 'csv') {
            const headers = ['#', 'Level', 'Type', 'Timestamp', 'Message', 'Exception Class', 'Exception Message', 'File / Line', 'Full Log'];
            const rows = [headers.map(escapeCsv).join(',')];

            visibleEntries.forEach((el, index) => {
                const level = el.getAttribute('data-level') || 'info';
                const typeEl = el.querySelector('.col-type .log-type-tag');
                const dateEl = el.querySelector('.col-date .log-date-text');
                const shortTextEl = el.querySelector('.entry-short-text') || el.querySelector('.line-text');
                const codeEl = el.querySelector('.full-log-code code');
                const excTitleEl = el.querySelector('.exception-type-title');
                const excMsgEl = el.querySelector('.exception-message-text');
                const excFileLineEl = el.querySelector('.exception-file-line');

                const row = [
                    index + 1,
                    level.toUpperCase(),
                    typeEl ? typeEl.textContent.trim() : level.toUpperCase(),
                    dateEl ? dateEl.textContent.trim() : '',
                    shortTextEl ? (shortTextEl.getAttribute('data-original') || shortTextEl.textContent.trim()) : '',
                    excTitleEl ? excTitleEl.textContent.trim() : '',
                    excMsgEl ? excMsgEl.textContent.trim() : '',
                    excFileLineEl ? excFileLineEl.textContent.trim() : '',
                    codeEl ? codeEl.textContent : (shortTextEl ? shortTextEl.textContent : '')
                ];

                rows.push(row.map(escapeCsv).join(','));
            });

            // Include UTF-8 BOM so Excel opens with proper encoding
            const blob = new Blob(['\uFEFF' + rows.join('\r\n')], { type: 'text/csv;charset=utf-8;' });
            downloadBlob(blob, filename);
            showToast(`Exported ${visibleEntries.length} records as CSV`, 'success');
        }
    }

    function escapeCsv(val) {
        if (val === null || val === undefined) return '""';
        const str = String(val).replace(/"/g, '""');
        return `"${str}"`;
    }

    function downloadBlob(blob, filename) {
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        setTimeout(() => URL.revokeObjectURL(url), 1000);
    }

    // CLEAR LOG ACTION
    const btnClear = document.getElementById('btnClearLog');
    if (btnClear) {
        btnClear.addEventListener('click', function() {
            if (!activeKey) return;

            // If SweetAlert2 is available, use rich dialog
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Clear Log File?',
                    html: `Are you sure you want to clear <strong>${activeName}</strong>?<br><span style="font-size:12px; color:#cf222e;">This will empty the file and cannot be undone.</span>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#30363d',
                    confirmButtonText: '<i class="fa fa-trash-alt"></i> Yes, Clear Log',
                    cancelButtonText: 'Cancel',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        executeClearLog();
                    }
                });
            } else {
                // Native modal fallback
                const modalFileName = document.getElementById('modalFileName');
                if (modalFileName) modalFileName.textContent = activeName;
                const clearModal = document.getElementById('clearModal');
                if (clearModal) clearModal.style.display = 'flex';
            }
        });
    }

    const btnCancelClearModal = document.getElementById('btnCancelClearModal');
    if (btnCancelClearModal) {
        btnCancelClearModal.addEventListener('click', function() {
            const clearModal = document.getElementById('clearModal');
            if (clearModal) clearModal.style.display = 'none';
        });
    }

    const btnConfirmClearModal = document.getElementById('btnConfirmClearModal');
    if (btnConfirmClearModal) {
        btnConfirmClearModal.addEventListener('click', function() {
            const clearModal = document.getElementById('clearModal');
            if (clearModal) clearModal.style.display = 'none';
            executeClearLog();
        });
    }

    function executeClearLog() {
        if (!clearUrl || !activeKey) return;
        if (loadingOverlay) loadingOverlay.classList.add('active');

        fetch(clearUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ key: activeKey })
        })
        .then(res => res.json())
        .then(data => {
            if (data && data.status) {
                showToast(data.message, 'success');

                // Reset viewport to empty state
                if (viewport) {
                    viewport.innerHTML = `
                        <div class="empty-log-state">
                            <i class="far fa-check-circle" style="color: #2ea043; opacity: 0.7;"></i>
                            <h4 style="font-weight: 600;">Log file cleared</h4>
                            <p style="font-size: 11.5px; max-width: 400px;">
                                <code>${activeName}</code> has been emptied. File size is now 0 B.
                            </p>
                        </div>
                    `;
                }

                // Update size badges in header & sidebar
                const sizeEl = document.getElementById('activeFileSize');
                if (sizeEl) {
                    sizeEl.textContent = '0 B';
                    sizeEl.className = 'file-size-tag empty';
                }
                const sidebarItem = document.querySelector(`.sidebar .file-item[data-key="${activeKey}"] .file-size-tag`);
                if (sidebarItem) {
                    sidebarItem.textContent = '0 B';
                    sidebarItem.className = 'file-size-tag empty';
                }

                // Reset stats counters
                const statAll = document.getElementById('statAll');
                const statError = document.getElementById('statError');
                const statWarn = document.getElementById('statWarn');
                const statInfo = document.getElementById('statInfo');
                if (statAll) statAll.textContent = '(0)';
                if (statError) statError.textContent = '(0)';
                if (statWarn) statWarn.textContent = '(0)';
                if (statInfo) statInfo.textContent = '(0)';
            } else {
                showToast((data && data.message) || 'Failed to clear log file.', 'error');
            }
        })
        .catch(err => {
            showToast('Network error while clearing log file.', 'error');
        })
        .finally(() => {
            if (loadingOverlay) loadingOverlay.classList.remove('active');
        });
    }

    // Toast Message Utility
    function showToast(message, type = 'success') {
        const toast = document.getElementById('toastNotify');
        const toastMsg = document.getElementById('toastMessage');
        const toastIcon = document.getElementById('toastIcon');

        if (!toast) return;

        if (toastMsg) toastMsg.textContent = message;
        toast.className = 'toast-notify ' + (type === 'success' ? 'toast-success' : 'toast-error');
        if (toastIcon) {
            toastIcon.className = type === 'success' ? 'fa fa-check-circle' : 'fa fa-exclamation-triangle';
        }

        toast.style.display = 'flex';
        setTimeout(() => {
            toast.style.display = 'none';
        }, 3500);
    }

    // =========================================================================
    // Emergency Session Destroy & Close All Log Viewer Tabs (Ctrl + Shift + D)
    // =========================================================================
    const syncChannel = window.BroadcastChannel ? new BroadcastChannel('log_viewer_sync') : null;

    function handleEmergencyClose() {
        try {
            window.close();
        } catch (e) {}
        // Fallback: If browser blocks window.close(), redirect to home
        window.location.href = config.homeUrl || '/';
    }

    if (syncChannel) {
        syncChannel.onmessage = function(ev) {
            if (ev.data && ev.data.action === 'destroy_and_close') {
                handleEmergencyClose();
            }
        };
    }

    window.addEventListener('storage', function(e) {
        if (e.key === 'log_viewer_session_destroyed' || e.key === 'raytech_log_viewer_session_destroyed') {
            handleEmergencyClose();
        }
    });

    function destroySessionAndCloseAll() {
        showToast('Destroying session & closing...', 'error');

        // Broadcast to all other open tabs
        if (syncChannel) {
            try {
                syncChannel.postMessage({ action: 'destroy_and_close' });
            } catch (e) {}
        }
        try {
            localStorage.setItem('log_viewer_session_destroyed', Date.now().toString());
            localStorage.setItem('raytech_log_viewer_session_destroyed', Date.now().toString());
        } catch (e) {}

        // Send backend logout
        if (config.logoutUrl) {
            fetch(config.logoutUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }).catch(function() {});
        }

        setTimeout(handleEmergencyClose, 150);
    }

    // Emergency Hotkey Listener (Ctrl + Shift + D)
    const destroyKey = (config.destroyShortcutKey || 'd').toLowerCase();
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key && e.key.toLowerCase() === destroyKey) {
            e.preventDefault();
            destroySessionAndCloseAll();
        }
    });

    // Toolbar Lock Button
    const btnLock = document.getElementById('btnLockSession');
    if (btnLock) {
        btnLock.addEventListener('click', function(e) {
            e.preventDefault();
            destroySessionAndCloseAll();
        });
    }

})();
