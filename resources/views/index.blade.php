<!DOCTYPE html>
<html lang="en" data-theme="{{ $themeConfig['default_mode'] ?? 'light' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $selectedFile ? $selectedFile['name'] . ' | ' : '' }}{{ $themeConfig['title'] ?? 'RayTech Log Viewer' }}</title>

    <!-- Browser Tab Favicon / Logo -->
    @if(!empty($logoUrl))
        <link rel="icon" type="image/jpeg" href="{{ $logoUrl }}">
        <link rel="shortcut icon" href="{{ $logoUrl }}">
        <link rel="apple-touch-icon" href="{{ $logoUrl }}">
    @endif

    <!-- FontAwesome & SweetAlert2 (Configurable CDN or Local) -->
    @if(!empty($assets['fontawesome_css']))
        <link rel="stylesheet" href="{{ $assets['fontawesome_css'] }}">
    @endif
    @if(!empty($assets['sweetalert2_css']))
        <link rel="stylesheet" href="{{ $assets['sweetalert2_css'] }}">
    @endif
    @if(!empty($assets['sweetalert2_js']))
        <script src="{{ $assets['sweetalert2_js'] }}"></script>
    @endif

    @php
        $routeNamePrefix = config('raytech-log-viewer.route.name', 'log-viewer.');
        $cssUrl = (function_exists('public_path') && file_exists(public_path('vendor/raytech-log-viewer/css/raytech-log-viewer.css')))
            ? asset('vendor/raytech-log-viewer/css/raytech-log-viewer.css')
            : route($routeNamePrefix . 'asset', ['file' => 'css/raytech-log-viewer.css']);

        $jsUrl = (function_exists('public_path') && file_exists(public_path('vendor/raytech-log-viewer/js/raytech-log-viewer.js')))
            ? asset('vendor/raytech-log-viewer/js/raytech-log-viewer.js')
            : route($routeNamePrefix . 'asset', ['file' => 'js/raytech-log-viewer.js']);
    @endphp

    <!-- RayTech Log Viewer Stylesheet -->
    <link rel="stylesheet" href="{{ $cssUrl }}?v={{ $version ?? '1.0.0' }}">

    @if(!empty($themeConfig['primary_color']) && $themeConfig['primary_color'] !== '#1f6feb')
        <style>
            :root, [data-theme="light"], [data-theme="dark"] {
                --primary-brand: {{ $themeConfig['primary_color'] }};
            }
        </style>
    @endif
</head>
<body>

    @php
        $indexRoute = $routeNamePrefix . 'index';
        $clearRoute = $routeNamePrefix . 'clear';
        $downloadRoute = $routeNamePrefix . 'download';
        $rawRoute = $routeNamePrefix . 'raw';
    @endphp

    <!-- TOPBAR -->
    <header class="topbar">
        <div class="topbar-left">
            <button type="button" class="btn-compact" id="btnToggleSidebar" title="Toggle File Explorer (Ctrl+B)">
                <i class="fa fa-bars"></i>
            </button>
            <a href="{{ route($indexRoute) }}" class="brand-title" title="{{ $themeConfig['brand_name'] ?? 'RayTech Log Viewer' }}">
                @if(!empty($logoUrl))
                    <img src="{{ $logoUrl }}" alt="{{ $themeConfig['brand_name'] ?? 'RayTech' }}" class="brand-logo-img">
                @else
                    <span class="pulse-dot"></span>
                @endif
                <span>{{ $themeConfig['brand_name'] ?? 'RayTech' }} Log Viewer</span>
            </a>
            @if($themeConfig['show_back_button'] ?? true)
                <a href="{{ url($themeConfig['back_url'] ?? '/') }}" class="btn-compact" style="font-size: 11px;" title="Return to Main Application">
                    <i class="fa fa-arrow-left"></i> {{ $themeConfig['back_url_name'] ?? 'Back To Home' }}
                </a>
            @endif
        </div>

        <div class="topbar-center">
            @if($selectedFile)
                <div class="file-badge-main" title="{{ $selectedFile['path'] }}">
                    <i class="fa {{ $selectedFile['type'] === 'laravel' ? 'fa-bolt text-warning' : 'fa-server text-info' }}"></i>
                    <strong id="activeFileName">{{ $selectedFile['name'] }}</strong>
                    <span style="opacity: 0.5;">|</span>
                    <span id="activeFileSize" class="file-size-tag {{ $selectedFile['is_empty'] ? 'empty' : ($selectedFile['size'] > 10485760 ? 'huge' : ($selectedFile['size'] > 1048576 ? 'large' : '')) }}">
                        {{ $selectedFile['size_formatted'] }}
                    </span>
                    <span style="opacity: 0.5;">|</span>
                    <span id="activeFileModified" style="font-size: 10.5px; color: var(--text-dim);" title="Last Modified Time">
                        <i class="far fa-clock"></i> {{ $selectedFile['modified'] }}
                    </span>
                </div>
            @endif
        </div>

        <div class="topbar-right">
            <!-- Auto Refresh -->
            <label style="font-size: 11.5px; color: var(--text-muted); display: flex; align-items: center; gap: 4px; margin: 0; cursor: pointer;">
                <i class="fa fa-sync-alt fa-xs" id="refreshSpinIcon"></i> Auto:
                <select id="selAutoRefresh" class="select-compact" style="height: 24px; padding: 1px 4px;">
                    @php $autoInterval = $features['auto_refresh_default'] ?? 10000; @endphp
                    <option value="0" {{ $autoInterval == 0 ? 'selected' : '' }}>Off</option>
                    <option value="5000" {{ $autoInterval == 5000 ? 'selected' : '' }}>5s</option>
                    <option value="10000" {{ $autoInterval == 10000 ? 'selected' : '' }}>10s</option>
                    <option value="30000" {{ $autoInterval == 30000 ? 'selected' : '' }}>30s</option>
                </select>
            </label>

            <!-- Wrap lines toggle -->
            <button type="button" class="btn-compact" id="btnToggleWrap" title="Toggle Word Wrap">
                <i class="fa fa-align-left"></i> Wrap
            </button>

            <!-- Theme Toggle -->
            <button type="button" class="btn-compact" id="btnToggleTheme" title="Toggle Light / Dark Mode">
                <i class="fa fa-moon" id="themeIcon"></i>
            </button>

            <!-- Fullscreen -->
            <button type="button" class="btn-compact" id="btnToggleFullscreen" title="Toggle Fullscreen">
                <i class="fa fa-expand"></i>
            </button>
        </div>
    </header>

    <!-- SUB-TOOLBAR -->
    <div class="subbar">
        <div class="filter-group">
            <!-- Search in log lines -->
            <div class="search-box-wrap">
                <i class="fa fa-search search-icon"></i>
                <input type="text" id="logSearchInput" class="search-input" placeholder="Search log lines... (Ctrl+F)" autocomplete="off">
                <i class="fa fa-times search-clear" id="searchClearBtn" title="Clear search"></i>
            </div>
            <span id="searchCountBadge" class="pill-badge" style="display:none; font-size:10px;"></span>

            <!-- Level Filters (Supports Multi-Select) -->
            <span class="pill-badge active" data-filter="all" id="pillAll" title="Show all log levels">
                All <span id="statAll">({{ $stats['total'] }})</span>
            </span>
            <span class="pill-badge pill-error" data-filter="error" id="pillError" title="Filter Error logs (click to toggle multi-level)">
                <i class="fa fa-times-circle"></i> Error <span id="statError">({{ $stats['error'] }})</span>
            </span>
            <span class="pill-badge pill-warn" data-filter="warning" id="pillWarn" title="Filter Warning logs (click to toggle multi-level)">
                <i class="fa fa-exclamation-triangle"></i> Warn <span id="statWarn">({{ $stats['warning'] }})</span>
            </span>
            <span class="pill-badge pill-info" data-filter="info" id="pillInfo" title="Filter Info logs (click to toggle multi-level)">
                <i class="fa fa-info-circle"></i> Info <span id="statInfo">({{ $stats['info'] }})</span>
            </span>
        </div>

        <div class="filter-group">
            <!-- Sort Order Toggle (Latest on top) -->
            <button type="button" class="btn-compact" id="btnToggleOrder" title="Toggle Newest First / Oldest First">
                <i class="fa fa-sort-amount-{{ ($order ?? 'desc') === 'desc' ? 'down' : 'up' }}" id="iconOrder"></i> 
                <span id="lblOrder">{{ ($order ?? 'desc') === 'desc' ? 'Newest First' : 'Oldest First' }}</span>
            </button>

            <!-- Expand / Collapse All Details -->
            <button type="button" class="btn-compact" id="btnToggleExpandAll" title="Expand or Collapse all log details">
                <i class="fa fa-expand-alt" id="iconExpandAll"></i> 
                <span id="lblExpandAll">Expand All</span>
            </button>

            <!-- Lines Limit -->
            <label style="font-size: 11.5px; color: var(--text-muted); display: flex; align-items: center; gap: 4px; margin: 0;">
                Lines:
                <select id="selLineLimit" class="select-compact">
                    @foreach($allowedLines as $opt)
                        <option value="{{ $opt }}" {{ $lineLimit == $opt ? 'selected' : '' }}>Last {{ number_format($opt) }}</option>
                    @endforeach
                </select>
            </label>

            <!-- Refresh Button -->
            <button type="button" class="btn-compact" id="btnManualRefresh" title="Refresh Now">
                <i class="fa fa-sync-alt"></i> Refresh
            </button>

            <!-- Copy Log Button -->
            @if(!empty($features['allow_copy']))
                <button type="button" class="btn-compact" id="btnCopyLog" title="Copy visible lines to clipboard">
                    <i class="far fa-copy"></i> Copy
                </button>
            @endif

            <!-- Export Filtered Logs Dropdown -->
            @if($selectedFile)
                <div class="dropdown-wrap" id="exportDropdownWrap" style="position: relative; display: inline-block;">
                    <button type="button" class="btn-compact" id="btnExportToggle" title="Export currently filtered logs">
                        <i class="fa fa-file-export"></i> Export <i class="fa fa-caret-down" style="font-size: 9px; margin-left: 2px;"></i>
                    </button>
                    <div class="dropdown-menu-compact" id="exportDropdownMenu" style="display: none;">
                        <a href="javascript:void(0)" class="dropdown-item-compact" id="btnExportJson">
                            <i class="far fa-file-code text-warning"></i> Export as JSON
                        </a>
                        <a href="javascript:void(0)" class="dropdown-item-compact" id="btnExportCsv">
                            <i class="far fa-file-excel text-success"></i> Export as CSV
                        </a>
                    </div>
                </div>
            @endif

            <!-- Download Button -->
            @if(!empty($features['allow_download']) && $selectedFile)
                <a href="{{ route($downloadRoute, ['key' => $selectedFile['key']]) }}" class="btn-compact" id="btnDownloadLog" title="Download raw file">
                    <i class="fa fa-download"></i> Download
                </a>
            @endif

            <!-- Raw View Button -->
            @if(!empty($features['allow_raw']) && $selectedFile)
                <a href="{{ route($rawRoute, ['key' => $selectedFile['key']]) }}" target="_blank" class="btn-compact" id="btnRawLog" title="Open raw text in new tab">
                    <i class="fa fa-external-link-alt"></i> Raw
                </a>
            @endif

            <!-- Clear Log File Button -->
            @if(!empty($features['allow_clear']) && $selectedFile)
                <button type="button" class="btn-compact btn-danger-compact" id="btnClearLog" title="Clear / truncate this log file">
                    <i class="fa fa-trash-alt"></i> <strong>Clear Log</strong>
                </button>
            @endif

            <!-- Lock / Destroy Session Button -->
            @if(!empty($isSecurityEnabled))
                <button type="button" class="btn-compact btn-danger-compact" id="btnLockSession" title="Lock & Destroy Session (Ctrl+Shift+D)">
                    <i class="fa fa-lock"></i> <strong>Lock</strong>
                </button>
            @endif
        </div>
    </div>

    <!-- MAIN WORKSPACE -->
    <div class="workspace">
        <!-- SIDEBAR: LOG FILES LIST -->
        <aside class="sidebar" id="sidebarFiles">
            <div class="sidebar-search">
                <input type="text" id="filterFilesInput" placeholder="Filter files (e.g. php, laravel)..." autocomplete="off">
            </div>

            <!-- Date-Picker Jump for Daily Logs -->
            <div class="sidebar-date-jump" title="Select a date to jump to matching daily log">
                <div class="date-input-wrap">
                    <i class="far fa-calendar-alt date-jump-icon"></i>
                    <input type="date" id="datePickerJump" class="date-picker-compact" title="Pick date to jump to daily log (YYYY-MM-DD)">
                    <button type="button" id="btnClearDateJump" class="btn-clear-date" title="Clear date selection" style="display:none;">
                        <i class="fa fa-times"></i>
                    </button>
                </div>
            </div>

            <div class="sidebar-scroll">
                <!-- Laravel Logs -->
                <div class="sidebar-group-title">
                    <span><i class="fa fa-bolt text-warning"></i> Laravel Logs</span>
                    <span style="font-weight: normal; opacity: 0.6;">{{ count($laravelLogs) }}</span>
                </div>
                <div class="file-list-group" id="laravelFilesList">
                    @forelse($laravelLogs as $f)
                        <a href="{{ route($indexRoute, ['key' => $f['key'], 'lines' => $lineLimit]) }}" 
                           class="file-item {{ $selectedFile && $selectedFile['key'] === $f['key'] ? 'active' : '' }}" 
                           data-key="{{ $f['key'] }}" 
                           data-name="{{ strtolower($f['name']) }}"
                           title="{{ $f['path'] }}">
                            <div class="file-item-left">
                                <i class="far fa-file-alt"></i>
                                <span class="file-name">{{ $f['name'] }}</span>
                            </div>
                            <span class="file-size-tag {{ $f['is_empty'] ? 'empty' : ($f['size'] > 10485760 ? 'huge' : ($f['size'] > 1048576 ? 'large' : '')) }}">
                                {{ $f['size_formatted'] }}
                            </span>
                        </a>
                    @empty
                        <div style="padding: 6px 12px; font-size: 11px; color: var(--text-dim); font-style: italic;">
                            No Laravel log files found.
                        </div>
                    @endforelse
                </div>

                <!-- Server Logs (WAMP / Apache / PHP / MySQL / Nginx / Linux) -->
                @if(!empty($showServerLogs))
                    <div class="sidebar-group-title" style="margin-top: 10px;">
                        <span><i class="fa fa-server text-info"></i> Server Logs</span>
                        <span style="font-weight: normal; opacity: 0.6;">{{ count($serverLogs) }}</span>
                    </div>
                    <div class="file-list-group" id="serverFilesList">
                        @forelse($serverLogs as $f)
                            <a href="{{ route($indexRoute, ['key' => $f['key'], 'lines' => $lineLimit]) }}" 
                               class="file-item {{ $selectedFile && $selectedFile['key'] === $f['key'] ? 'active' : '' }}" 
                               data-key="{{ $f['key'] }}" 
                               data-name="{{ strtolower($f['name']) }}"
                               title="{{ $f['path'] }}">
                                <div class="file-item-left">
                                    <i class="fa {{ str_contains(strtolower($f['name']), 'error') ? 'fa-exclamation-circle text-danger' : 'fa-file-code' }}"></i>
                                    <span class="file-name">{{ $f['name'] }}</span>
                                </div>
                                <span class="file-size-tag {{ $f['is_empty'] ? 'empty' : ($f['size'] > 10485760 ? 'huge' : ($f['size'] > 1048576 ? 'large' : '')) }}">
                                    {{ $f['size_formatted'] }}
                                </span>
                            </a>
                        @empty
                            <div style="padding: 6px 12px; font-size: 11px; color: var(--text-dim); font-style: italic;">
                                No server log files found.
                            </div>
                        @endforelse
                    </div>
                @endif
            </div>

            <div class="sidebar-footer">
                <span><i class="fa fa-hdd"></i> {{ count($allFiles) }} Log Files</span>
                <span class="version-tag" title="RayTech Log Viewer Version">v{{ $version ?? '1.0.0' }}</span>
            </div>
        </aside>

        <!-- LOG CONTENT AREA -->
        <main class="log-container" id="logContainer">
            <!-- Loading indicator -->
            <div class="loading-overlay" id="loadingOverlay">
                <div class="spinner"></div>
            </div>

            <!-- Viewport -->
            <div class="log-viewport" id="logViewport">
                @if(!$selectedFile)
                    <div class="empty-log-state">
                        <i class="far fa-folder-open"></i>
                        <p>No log file selected. Choose a log file from the left panel.</p>
                    </div>
                @elseif(empty($parsedLines))
                    <div class="empty-log-state">
                        <i class="far fa-check-circle" style="color: #2ea043; opacity: 0.7;"></i>
                        <h4 style="font-weight: 600;">Log file is empty</h4>
                        <p style="font-size: 11.5px; max-width: 400px;">
                            <code>{{ $selectedFile['name'] }}</code> contains 0 bytes. No logs or error records found.
                        </p>
                    </div>
                @else
                    <div id="linesWrapper">
                        <!-- Table Column Header (like ongc-dak / opcodesio) -->
                        <div class="log-table-header">
                            <div class="th-expand"></div>
                            <div class="th-type">Log Type</div>
                            <div class="th-date">Log Create Date</div>
                            <div class="th-message">Header Text</div>
                            <div class="th-actions">Actions</div>
                        </div>

                        @foreach($parsedLines as $entry)
                            <div class="log-entry level-{{ $entry['level'] }} has-details" 
                                 id="{{ $entry['id'] ?? ('log-entry-' . $entry['num']) }}" 
                                 data-level="{{ $entry['level'] }}" 
                                 data-num="{{ $entry['num'] }}"
                                 data-has-details="1">
                                
                                <!-- header_group: log_create_date, log_type, header_text -->
                                <div class="header-group entry-summary" title="Click to view full content">
                                    <div class="col-expand">
                                        <i class="fa fa-chevron-right expand-chevron"></i>
                                        @if($entry['level'] === 'error')
                                            <i class="fa fa-times-circle log-level-icon icon-error" title="Error"></i>
                                        @elseif($entry['level'] === 'warning')
                                            <i class="fa fa-exclamation-triangle log-level-icon icon-warning" title="Warning"></i>
                                        @elseif($entry['level'] === 'info')
                                            <i class="fa fa-info-circle log-level-icon icon-info" title="Info"></i>
                                        @elseif($entry['level'] === 'debug')
                                            <i class="fa fa-bug log-level-icon icon-debug" title="Debug"></i>
                                        @else
                                            <i class="fa fa-terminal log-level-icon icon-default" title="Log"></i>
                                        @endif
                                    </div>

                                    <!-- log_type -->
                                    <div class="col-type">
                                        <span class="log-type-tag tag-{{ $entry['level'] }}">{{ $entry['log_type'] ?? strtoupper($entry['level']) }}</span>
                                    </div>

                                    <!-- log_create_date -->
                                    <div class="col-date">
                                        <span class="log-date-text">{{ $entry['log_create_date'] ?? $entry['timestamp'] ?? '' }}</span>
                                    </div>

                                    <!-- header_text -->
                                    <div class="col-message">
                                        <span class="entry-short-text header-text">{{ $entry['header_text'] ?? $entry['message'] ?? $entry['short_text'] ?? '' }}</span>
                                    </div>

                                    <div class="col-actions">
                                        @if(!empty($entry['frames_count']) && $entry['frames_count'] > 0)
                                            <span class="badge-lines" title="{{ $entry['frames_count'] }} stack frames"><i class="fa fa-layer-group"></i> {{ $entry['frames_count'] }} frames</span>
                                        @elseif(!empty($entry['lines_count']) && $entry['lines_count'] > 1)
                                            <span class="badge-lines" title="{{ $entry['lines_count'] }} total lines"><i class="fa fa-layer-group"></i> +{{ $entry['lines_count'] - 1 }}</span>
                                        @endif
                                        <button type="button" class="btn-entry-copy" title="Copy body content" data-copy-text="{{ urlencode($entry['body_content'] ?? $entry['full_text'] ?? '') }}">
                                            <i class="far fa-copy"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- body_content: shown when clicking on header_group -->
                                <div class="entry-details body-content-panel" style="display: none;">
                                    @if(!empty($entry['exception']))
                                        <div class="stack-exception-header">
                                            <div class="exception-type-title"><i class="fa fa-bug"></i> {{ $entry['exception']['class'] }}</div>
                                            <div class="exception-message-text">{{ $entry['exception']['message'] }}</div>
                                            <div class="exception-file-line">
                                                in <strong>{{ $entry['exception']['file'] }}</strong> on line <strong>{{ $entry['exception']['line'] }}</strong>
                                            </div>
                                        </div>
                                    @endif

                                    <!-- Details Tabs Navigation -->
                                    <div class="details-tabs-header">
                                        @if(!empty($entry['stack_frames']))
                                            <button type="button" class="tab-link active" data-tab="trace">
                                                <i class="fa fa-stream"></i> Stack Trace <span class="tab-count">({{ count($entry['stack_frames']) }})</span>
                                            </button>
                                        @endif
                                        @if(!empty($entry['context_json']))
                                            <button type="button" class="tab-link {{ empty($entry['stack_frames']) ? 'active' : '' }}" data-tab="context">
                                                <i class="fa fa-database"></i> Context
                                            </button>
                                        @endif
                                        <button type="button" class="tab-link {{ empty($entry['stack_frames']) && empty($entry['context_json']) ? 'active' : '' }}" data-tab="raw">
                                            <i class="fa fa-code"></i> Raw Log
                                        </button>
                                        <div class="tab-actions-right">
                                            <button type="button" class="btn-compact btn-copy-full" data-copy-text="{{ urlencode($entry['body_content'] ?? $entry['full_text'] ?? '') }}" title="Copy all body content">
                                                <i class="far fa-copy"></i> Copy Body Content
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Tab Panes -->
                                    <div class="details-tab-panes">
                                        @if(!empty($entry['stack_frames']))
                                            <div class="tab-pane active" data-pane="trace">
                                                <div class="stack-trace-frames">
                                                    @foreach($entry['stack_frames'] as $frame)
                                                        <div class="stack-frame-row">
                                                            <span class="frame-number">#{{ $frame['number'] }}</span>
                                                            <div class="frame-detail">
                                                                @if(!empty($frame['file']))
                                                                    <div class="frame-location">
                                                                        <span class="frame-filepath">{{ $frame['file'] }}</span>@if(!empty($frame['line']))<span class="frame-colon">:</span><span class="frame-linenum">{{ $frame['line'] }}</span>@endif
                                                                    </div>
                                                                @endif
                                                                <div class="frame-callcode"><code>{{ $frame['call'] }}</code></div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif

                                        @if(!empty($entry['context_json']))
                                            <div class="tab-pane {{ empty($entry['stack_frames']) ? 'active' : '' }}" data-pane="context">
                                                <pre class="context-json-code"><code>{!! htmlspecialchars($entry['context_json'], ENT_QUOTES, 'UTF-8') !!}</code></pre>
                                            </div>
                                        @endif

                                        <div class="tab-pane {{ empty($entry['stack_frames']) && empty($entry['context_json']) ? 'active' : '' }}" data-pane="raw">
                                            <pre class="full-log-code"><code>{!! htmlspecialchars($entry['body_content'] ?? $entry['full_text'] ?? '', ENT_QUOTES, 'UTF-8') !!}</code></pre>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Floating scroll buttons -->
            <div class="float-nav-btn-group">
                <button type="button" class="float-nav-btn" id="btnScrollTop" title="Scroll to Top">
                    <i class="fa fa-chevron-up"></i>
                </button>
                <button type="button" class="float-nav-btn" id="btnScrollBottom" title="Scroll to Bottom">
                    <i class="fa fa-chevron-down"></i>
                </button>
            </div>
        </main>
    </div>

    <!-- TOAST NOTIFICATION -->
    <div class="toast-notify" id="toastNotify">
        <i class="fa fa-check-circle" id="toastIcon"></i>
        <span id="toastMessage">Action completed</span>
    </div>

    <!-- CLEAR LOG CONFIRMATION MODAL (Fallback if SweetAlert2 is offline/blocked) -->
    <div class="modal-backdrop-custom" id="clearModal">
        <div class="modal-box-custom">
            <div class="modal-header-custom">
                <i class="fa fa-exclamation-triangle"></i> Clear Log File
            </div>
            <div class="modal-body-custom">
                Are you sure you want to clear <strong><span id="modalFileName">{{ $selectedFile ? $selectedFile['name'] : '' }}</span></strong>?
                <br><br>
                <span style="color:#ff7b72;">This will delete all current log contents and cannot be undone.</span>
            </div>
            <div class="modal-footer-custom">
                <button type="button" class="btn-compact" id="btnCancelClearModal">Cancel</button>
                <button type="button" class="btn-compact btn-danger-compact" id="btnConfirmClearModal">Yes, Clear Log</button>
            </div>
        </div>
    </div>

    <!-- RayTech Log Viewer Configuration & Scripts -->
    <script>
        window.RaytechLogViewer = {
            activeKey: @json($selectedFile ? $selectedFile['key'] : ''),
            activeName: @json($selectedFile ? $selectedFile['name'] : ''),
            defaultTheme: @json($themeConfig['default_mode'] ?? 'light'),
            order: @json($order ?? 'desc'),
            indexUrl: @json(route($indexRoute)),
            clearUrl: @json(route($clearRoute)),
            logoutUrl: @json(!empty($logoutRoute) ? route($logoutRoute) : ''),
            isSecurityEnabled: @json(!empty($isSecurityEnabled)),
            destroyShortcutKey: @json(config('raytech-log-viewer.security.destroy_shortcut.key', 'd')),
            homeUrl: @json(config('raytech-log-viewer.theme.back_url', '/')),
            csrfToken: @json(csrf_token())
        };
    </script>
    <script src="{{ $jsUrl }}?v={{ $version ?? '1.0.0' }}"></script>
</body>
</html>
