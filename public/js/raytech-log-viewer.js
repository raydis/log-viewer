(function() {
    'use strict';

    // Retrieve global configuration
    const config = window.RaytechLogViewer || {};

    // State variables
    let activeKey = config.activeKey || '';
    let activeName = config.activeName || '';
    let activeLevelFilter = 'all';
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

    // Scroll to bottom on initial load so recent logs are immediately visible
    if (viewport) {
        viewport.scrollTop = viewport.scrollHeight;
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
        const lines = document.querySelectorAll('.log-line');

        if (searchTerm.length > 0) {
            if (searchClearBtn) searchClearBtn.style.display = 'block';
        } else {
            if (searchClearBtn) searchClearBtn.style.display = 'none';
            if (searchCountBadge) searchCountBadge.style.display = 'none';
        }

        let matchCount = 0;
        let visibleCount = 0;

        lines.forEach(line => {
            const level = line.getAttribute('data-level');
            const textSpan = line.querySelector('.line-text');
            if (!textSpan) return;
            const originalText = textSpan.textContent;

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

            // Search match
            let searchMatch = true;
            if (searchTerm.length > 0) {
                const idx = originalText.toLowerCase().indexOf(termLower);
                if (idx !== -1) {
                    searchMatch = true;
                    matchCount++;
                    highlightMatch(textSpan, originalText, searchTerm);
                } else {
                    searchMatch = false;
                    textSpan.textContent = originalText;
                }
            } else {
                textSpan.textContent = originalText;
            }

            if (levelMatch && searchMatch) {
                line.style.display = 'flex';
                visibleCount++;
            } else {
                line.style.display = 'none';
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
        const url = `${indexUrl}${separator}key=${encodeURIComponent(activeKey)}&lines=${encodeURIComponent(lineLimit)}&ajax=1`;

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

        // Build HTML
        let html = '<div id="linesWrapper">';
        data.lines.forEach(l => {
            const escaped = escapeHtml(l.text);
            const tag = ['error', 'warning', 'info', 'debug'].includes(l.level) 
                ? `<span class="level-tag">${l.level}</span>` 
                : '';
            html += `
                <div class="log-line level-${l.level}" data-level="${l.level}" data-num="${l.num}">
                    <div class="line-num">${l.num}</div>
                    <div class="line-content">
                        ${tag}
                        <span class="line-text">${escaped}</span>
                    </div>
                </div>
            `;
        });
        html += '</div>';

        const wasAtBottom = (viewport.scrollHeight - viewport.scrollTop - viewport.clientHeight) < 40;
        viewport.innerHTML = html;

        applyFilters();

        if (wasAtBottom) {
            viewport.scrollTop = viewport.scrollHeight;
        }
    }

    function escapeHtml(text) {
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return text.replace(/[&<>"']/g, m => map[m]);
    }

    // Copy to Clipboard
    const btnCopy = document.getElementById('btnCopyLog');
    if (btnCopy) {
        btnCopy.addEventListener('click', function() {
            const visibleLines = Array.from(document.querySelectorAll('.log-line'))
                .filter(l => l.style.display !== 'none')
                .map(l => {
                    const textEl = l.querySelector('.line-text');
                    return textEl ? textEl.textContent : '';
                })
                .join('\n');

            if (!visibleLines) {
                showToast('No lines to copy', 'error');
                return;
            }

            navigator.clipboard.writeText(visibleLines).then(() => {
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
