(function() {
    'use strict';

    // Retrieve global configuration
    const config = window.RaytechLogViewer || {};

    // State variables
    let activeKey = config.activeKey || '';
    let activeName = config.activeName || '';
    let activeLevelFilter = 'all';
    let activeOrder = config.order || 'desc';
    let autoRefreshInterval = null;

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
    const savedTheme = localStorage.getItem('raytech_log_theme') || defaultTheme;
    document.documentElement.setAttribute('data-theme', savedTheme);
    updateThemeIcon(savedTheme);

    const btnToggleTheme = document.getElementById('btnToggleTheme');
    if (btnToggleTheme) {
        btnToggleTheme.addEventListener('click', function() {
            const current = document.documentElement.getAttribute('data-theme');
            const next = current === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', next);
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

    // Keyboard Shortcuts
    window.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'b') {
            e.preventDefault();
            if (sidebar) sidebar.classList.toggle('collapsed');
        } else if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'f') {
            e.preventDefault();
            if (searchInput) {
                searchInput.focus();
                searchInput.select();
            }
        } else if (e.key === 'Escape' && document.activeElement === searchInput) {
            clearSearch();
        }
    });

    // Toggle Word Wrap
    const btnToggleWrap = document.getElementById('btnToggleWrap');
    if (btnToggleWrap && viewport) {
        btnToggleWrap.addEventListener('click', function() {
            viewport.classList.toggle('nowrap');
            this.classList.toggle('active');
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

    // Order Toggle Button (Newest First / Oldest First)
    const btnToggleOrder = document.getElementById('btnToggleOrder');
    if (btnToggleOrder) {
        btnToggleOrder.addEventListener('click', function() {
            activeOrder = activeOrder === 'desc' ? 'asc' : 'desc';
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
            const term = this.value.toLowerCase().trim();
            const items = document.querySelectorAll('.sidebar .file-item');
            items.forEach(item => {
                const name = item.getAttribute('data-name');
                if (!term || (name && name.includes(term))) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    }

    // Level Filtering
    const pillBadges = document.querySelectorAll('.pill-badge[data-filter]');
    pillBadges.forEach(badge => {
        badge.addEventListener('click', function() {
            pillBadges.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            activeLevelFilter = this.getAttribute('data-filter');
            applyFilters();
        });
    });

    // Live Search in Log Lines
    let searchDebounce = null;
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchDebounce);
            searchDebounce = setTimeout(applyFilters, 120);
        });
    }

    if (searchClearBtn) {
        searchClearBtn.addEventListener('click', clearSearch);
    }

    function clearSearch() {
        if (!searchInput) return;
        searchInput.value = '';
        if (searchClearBtn) searchClearBtn.style.display = 'none';
        if (searchCountBadge) searchCountBadge.style.display = 'none';
        applyFilters();
        searchInput.focus();
    }

    function applyFilters() {
        if (!searchInput) return;
        const searchTerm = searchInput.value.trim();
        const termLower = searchTerm.toLowerCase();
        const entries = document.querySelectorAll('.log-entry, .log-line');

        if (searchTerm.length > 0) {
            if (searchClearBtn) searchClearBtn.style.display = 'block';
        } else {
            if (searchClearBtn) searchClearBtn.style.display = 'none';
            if (searchCountBadge) searchCountBadge.style.display = 'none';
        }

        let matchCount = 0;
        let visibleCount = 0;

        entries.forEach(entry => {
            const level = entry.getAttribute('data-level');
            const shortTextEl = entry.querySelector('.entry-short-text') || entry.querySelector('.line-text');
            const codeEl = entry.querySelector('.full-log-code code');

            const shortText = shortTextEl ? (shortTextEl.getAttribute('data-original') || shortTextEl.textContent) : '';
            if (shortTextEl && !shortTextEl.getAttribute('data-original')) {
                shortTextEl.setAttribute('data-original', shortText);
            }

            const fullText = codeEl ? codeEl.textContent : shortText;

            // Level match
            let levelMatch = false;
            if (activeLevelFilter === 'all') {
                levelMatch = true;
            } else if (activeLevelFilter === 'error' && (level === 'error' || level === 'critical')) {
                levelMatch = true;
            } else if (activeLevelFilter === 'warning' && (level === 'warning' || level === 'notice')) {
                levelMatch = true;
            } else if (activeLevelFilter === 'info' && level === 'info') {
                levelMatch = true;
            } else if (level === activeLevelFilter) {
                levelMatch = true;
            }

            // Search match across short text or full content
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

                    // If match is inside details, auto-expand so user can inspect it
                    if (inFull && !inShort) {
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
                entry.style.display = '';
                visibleCount++;
            } else {
                entry.style.display = 'none';
            }
        });

        if (searchCountBadge && searchTerm.length > 0) {
            searchCountBadge.textContent = matchCount + ' ' + (matchCount === 1 ? 'match' : 'matches');
            searchCountBadge.style.display = 'inline-flex';
        }
    }

    function highlightMatch(element, text, term) {
        const regex = new RegExp('(' + escapeRegExp(term) + ')', 'gi');
        const escaped = text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        element.innerHTML = escaped.replace(regex, '<mark class="search-match">$1</mark>');
    }

    function escapeRegExp(string) {
        return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    // Lines Limit change
    const selLineLimit = document.getElementById('selLineLimit');
    if (selLineLimit) {
        selLineLimit.addEventListener('change', function() {
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

    // Auto Refresh Change
    const selAutoRefresh = document.getElementById('selAutoRefresh');
    if (selAutoRefresh) {
        selAutoRefresh.addEventListener('change', function() {
            setupAutoRefresh();
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
    setupAutoRefresh();

    // Reload Logs via AJAX without full page flicker
    function reloadLogs(silent = false) {
        if (!activeKey || !indexUrl) return;
        const lineLimit = selLineLimit ? selLineLimit.value : 500;
        if (!silent && loadingOverlay) {
            loadingOverlay.classList.add('active');
        }

        const separator = indexUrl.includes('?') ? '&' : '?';
        const url = `${indexUrl}${separator}key=${encodeURIComponent(activeKey)}&lines=${encodeURIComponent(lineLimit)}&order=${encodeURIComponent(activeOrder)}&ajax=1`;

        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data && data.status) {
                renderLogs(data);
            }
        })
        .catch(err => {
            console.error('Failed to refresh log', err);
        })
        .finally(() => {
            if (loadingOverlay) loadingOverlay.classList.remove('active');
        });
    }

    function renderLogs(data) {
        if (!viewport) return;

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

        // Build HTML for structured entries
        function buildEntryHtml(entry) {
            const escapedShort = escapeHtml(entry.message || entry.short_text || entry.text || '');
            const escapedFull = escapeHtml(entry.full_text || entry.text || '');
            const hasDetails = true;

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

            const levelTag = ['error', 'warning', 'info', 'debug'].includes(entry.level)
                ? `<span class="level-tag tag-${entry.level}">${entry.level.toUpperCase()}</span>`
                : '';
            const errorTypeTag = entry.error_type
                ? `<span class="error-type-tag" title="${entry.exception ? escapeHtml(entry.exception.class) : ''}">${escapeHtml(entry.error_type)}</span>`
                : '';
            const envTag = entry.environment
                ? `<span class="env-tag">${escapeHtml(entry.environment)}</span>`
                : '';
            const timeTag = (entry.time || entry.timestamp)
                ? `<span class="entry-time" title="${escapeHtml(entry.timestamp || '')}"><i class="far fa-clock"></i> ${escapeHtml(entry.time || entry.timestamp)}</span>`
                : '';
            const chevron = '<i class="fa fa-chevron-right expand-chevron"></i>';

            let countBadge = '';
            if (entry.frames_count && entry.frames_count > 0) {
                countBadge = `<span class="badge-lines" title="${entry.frames_count} stack frames"><i class="fa fa-layer-group"></i> ${entry.frames_count} frames</span>`;
            } else if (entry.lines_count && entry.lines_count > 1) {
                countBadge = `<span class="badge-lines" title="${entry.lines_count} lines"><i class="fa fa-layer-group"></i> +${entry.lines_count - 1}</span>`;
            }

            // Build exception card if present
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

            // Build tabs header and panes
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
                        <button type="button" class="btn-compact btn-copy-full" data-copy-text="${encodeURIComponent(entry.body_content || entry.full_text || entry.text || '')}" title="Copy full content">
                            <i class="far fa-copy"></i> Copy Body Content
                        </button>
                    </div>
                </div>
            `;

            // Frames pane
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

            // Context pane
            const contextHtml = hasContext
                ? `<pre class="context-json-code"><code>${escapeHtml(entry.context_json)}</code></pre>`
                : '';

            // Raw pane
            const rawHtml = `<pre class="full-log-code"><code>${escapedFull}</code></pre>`;

            const tabsPanesHtml = `
                <div class="details-tab-panes">
                    ${hasFrames ? `<div class="tab-pane active" data-pane="trace">${framesHtml}</div>` : ''}
                    ${hasContext ? `<div class="tab-pane ${!hasFrames ? 'active' : ''}" data-pane="context">${contextHtml}</div>` : ''}
                    <div class="tab-pane ${!hasFrames && !hasContext ? 'active' : ''}" data-pane="raw">${rawHtml}</div>
                </div>
            `;

            return `
                <div class="log-entry level-${entry.level} has-details" 
                     id="${entry.id || ('log-entry-' + entry.num)}" 
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

                    <div class="entry-details body-content-panel" style="display: none;">
                        ${exceptionHtml}
                        ${tabsHeaderHtml}
                        ${tabsPanesHtml}
                    </div>
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

})();
