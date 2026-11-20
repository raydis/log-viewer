<!DOCTYPE html>
<html lang="en" data-theme="{{ $themeConfig['default_mode'] ?? 'light' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $selectedFile ? $selectedFile['name'] . ' | ' : '' }}{{ $themeConfig['title'] ?? 'RayTech Log Viewer' }}</title>

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

    <style>
        :root, [data-theme="light"] {
            --primary-brand: {{ $themeConfig['primary_color'] ?? '#1f6feb' }};
            --primary-accent: #388bfd;
            --bg-body: #ffffff;
            --bg-sidebar: #f8fafc;
            --bg-panel: #ffffff;
            --bg-header: #ffffff;
            --bg-subbar: #f1f5f9;
            --bg-hover: #f1f5f9;
            --bg-active: #e2e8f0;
            --border-color: #cbd5e1;
            --border-subtle: #e2e8f0;
            --text-main: #000000;
            --text-muted: #475569;
            --text-dim: #64748b;
            --gutter-bg: #f8fafc;
            --gutter-num: #94a3b8;
            --line-hover: #f1f5f9;
            --err-bg: #fef2f2;
            --err-border: #ef4444;
            --err-text: #b91c1c;
            --warn-bg: #fffbeb;
            --warn-border: #f59e0b;
            --warn-text: #b45309;
            --info-bg: #eff6ff;
            --info-border: #3b82f6;
            --info-text: #1d4ed8;
            --debug-bg: #faf5ff;
            --debug-border: #a855f7;
            --debug-text: #7e22ce;
            --trace-text: #0e1116;
            --highlight-bg: #fef08a;
            --highlight-text: #000;
        }

        [data-theme="dark"] {
            --primary-brand: {{ $themeConfig['primary_color'] ?? '#1f6feb' }};
            --primary-accent: #58a6ff;
            --bg-body: #0d1117;
            --bg-sidebar: #161b22;
            --bg-panel: #0d1117;
            --bg-header: #161b22;
            --bg-subbar: #1c2128;
            --bg-hover: #21262d;
            --bg-active: #1f2937;
            --border-color: #30363d;
            --border-subtle: #21262d;
            --text-main: #e6edf3;
            --text-muted: #8b949e;
            --text-dim: #6e7681;
            --gutter-bg: #161b22;
            --gutter-num: #484f58;
            --line-hover: rgba(255, 255, 255, 0.04);
            --err-bg: rgba(248, 81, 73, 0.15);
            --err-border: #f85149;
            --err-text: #ff7b72;
            --warn-bg: rgba(210, 153, 34, 0.15);
            --warn-border: #d29922;
            --warn-text: #e3b341;
            --info-bg: rgba(56, 139, 253, 0.15);
            --info-border: #388bfd;
            --info-text: #58a6ff;
            --debug-bg: rgba(163, 113, 247, 0.15);
            --debug-border: #a371f7;
            --debug-text: #bc8cff;
            --trace-text: #8b949e;
            --highlight-bg: #e3b341;
            --highlight-text: #000;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: var(--bg-body);
            color: var(--text-main);
            overflow: hidden;
            height: 100vh;
            display: flex;
            flex-direction: column;
            font-size: 13px;
        }

        /* Top Bar - Ultra Compact */
        .topbar {
            height: 42px;
            min-height: 42px;
            background-color: var(--bg-header);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 10px;
            user-select: none;
            z-index: 100;
        }

        .topbar-left, .topbar-center, .topbar-right {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .brand-title {
            font-size: 13px;
            font-weight: 700;
            color: var(--text-main);
            display: flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
        }

        .pulse-dot {
            width: 8px;
            height: 8px;
            background-color: #2ea043;
            border-radius: 50%;
            display: inline-block;
            box-shadow: 0 0 6px #2ea043;
        }

        .file-badge-main {
            display: flex;
            align-items: center;
            gap: 6px;
            background: var(--bg-hover);
            border: 1px solid var(--border-color);
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        }

        /* Buttons & Toolbars */
        .btn-compact {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 8px;
            font-size: 11.5px;
            font-weight: 500;
            border-radius: 4px;
            border: 1px solid var(--border-color);
            background: var(--bg-sidebar);
            color: var(--text-main);
            cursor: pointer;
            transition: all 0.15s ease;
            text-decoration: none;
            white-space: nowrap;
        }
        .btn-compact:hover {
            background: var(--bg-hover);
            border-color: var(--text-muted);
            color: var(--text-main);
        }
        .btn-compact:active {
            transform: translateY(1px);
        }
        .btn-danger-compact {
            background: rgba(248, 81, 73, 0.1);
            border-color: rgba(248, 81, 73, 0.4);
            color: #ff7b72;
        }
        .btn-danger-compact:hover {
            background: #cf222e;
            border-color: #cf222e;
            color: #fff;
        }
        .btn-primary-compact {
            background: var(--primary-brand);
            border-color: var(--primary-accent);
            color: #fff;
        }
        .btn-primary-compact:hover {
            opacity: 0.9;
            color: #fff;
        }

        /* Sub-Toolbar (Filters & Actions) */
        .subbar {
            height: 38px;
            min-height: 38px;
            background-color: var(--bg-subbar);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 8px;
            gap: 8px;
            z-index: 90;
        }

        .filter-group {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .search-box-wrap {
            position: relative;
            display: flex;
            align-items: center;
        }

        .search-input {
            width: 220px;
            height: 26px;
            padding: 2px 24px 2px 24px;
            font-size: 12px;
            background: var(--bg-sidebar);
            border: 1px solid var(--border-color);
            border-radius: 4px;
            color: var(--text-main);
            outline: none;
            transition: all 0.2s ease;
        }
        .search-input:focus {
            width: 280px;
            border-color: var(--primary-accent);
            box-shadow: 0 0 0 2px rgba(88, 166, 255, 0.2);
        }
        .search-icon {
            position: absolute;
            left: 7px;
            font-size: 11px;
            color: var(--text-dim);
            pointer-events: none;
        }
        .search-clear {
            position: absolute;
            right: 6px;
            font-size: 11px;
            color: var(--text-dim);
            cursor: pointer;
            display: none;
        }
        .search-clear:hover {
            color: var(--text-main);
        }

        .pill-badge {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            font-size: 11px;
            padding: 1px 6px;
            border-radius: 10px;
            border: 1px solid var(--border-color);
            background: var(--bg-sidebar);
            color: var(--text-muted);
            cursor: pointer;
            user-select: none;
            transition: all 0.15s ease;
        }
        .pill-badge:hover {
            background: var(--bg-hover);
        }
        .pill-badge.active {
            background: var(--bg-hover);
            border-color: var(--text-muted);
            color: var(--text-main);
            font-weight: 600;
        }
        .pill-badge.pill-error.active {
            background: var(--err-bg);
            border-color: var(--err-border);
            color: var(--err-text);
        }
        .pill-badge.pill-warn.active {
            background: var(--warn-bg);
            border-color: var(--warn-border);
            color: var(--warn-text);
        }
        .pill-badge.pill-info.active {
            background: var(--info-bg);
            border-color: var(--info-border);
            color: var(--info-text);
        }

        .select-compact {
            height: 26px;
            padding: 2px 6px;
            font-size: 11.5px;
            background: var(--bg-sidebar);
            border: 1px solid var(--border-color);
            border-radius: 4px;
            color: var(--text-main);
            outline: none;
            cursor: pointer;
        }

        /* App Workspace Layout (Sidebar + Log Pane) */
        .workspace {
            display: flex;
            flex: 1;
            overflow: hidden;
            position: relative;
        }

        /* Sidebar - File Explorer */
        .sidebar {
            width: 270px;
            min-width: 270px;
            max-width: 270px;
            background-color: var(--bg-sidebar);
            border-right: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            transition: margin-left 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            user-select: none;
            z-index: 50;
        }
        .sidebar.collapsed {
            margin-left: -270px;
        }

        .sidebar-search {
            padding: 6px;
            border-bottom: 1px solid var(--border-color);
        }
        .sidebar-search input {
            width: 100%;
            height: 26px;
            padding: 2px 6px;
            font-size: 11.5px;
            background: var(--bg-panel);
            border: 1px solid var(--border-color);
            border-radius: 4px;
            color: var(--text-main);
            outline: none;
        }
        .sidebar-search input:focus {
            border-color: var(--primary-accent);
        }

        .sidebar-scroll {
            flex: 1;
            overflow-y: auto;
            padding: 4px 0;
        }
        .sidebar-scroll::-webkit-scrollbar {
            width: 4px;
        }
        .sidebar-scroll::-webkit-scrollbar-thumb {
            background: var(--border-color);
            border-radius: 2px;
        }

        .sidebar-group-title {
            font-size: 10.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-dim);
            padding: 6px 10px 3px 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .file-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 4px 10px;
            margin: 1px 4px;
            border-radius: 4px;
            color: var(--text-main);
            text-decoration: none;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.1s ease;
        }
        .file-item:hover {
            background: var(--bg-hover);
        }
        .file-item.active {
            background: #e0f2fe;
            color: #0369a1;
            font-weight: 600;
            border: 1px solid #bae6fd;
        }
        [data-theme="dark"] .file-item.active {
            background: rgba(31, 111, 235, 0.15);
            color: #58a6ff;
            border: 1px solid rgba(56, 139, 253, 0.35);
        }
        .file-item-left {
            display: flex;
            align-items: center;
            gap: 6px;
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
        }
        .file-item-left i {
            font-size: 11px;
            opacity: 0.7;
            width: 14px;
            text-align: center;
        }
        .file-item.active .file-item-left i {
            opacity: 1;
            color: var(--primary-accent);
        }
        .file-name {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .file-size-tag {
            font-size: 10.5px;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            padding: 0 4px;
            border-radius: 3px;
            background: var(--bg-subbar);
            color: var(--text-muted);
            border: 1px solid var(--border-subtle);
            flex-shrink: 0;
        }
        .file-size-tag.empty {
            color: var(--text-dim);
            opacity: 0.7;
        }
        .file-size-tag.large {
            color: #e3b341;
            border-color: rgba(210, 153, 34, 0.4);
        }
        .file-size-tag.huge {
            color: #ff7b72;
            border-color: rgba(248, 81, 73, 0.4);
        }

        .sidebar-footer {
            padding: 6px 10px;
            border-top: 1px solid var(--border-color);
            background: var(--bg-subbar);
            font-size: 11px;
            color: var(--text-dim);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* Log Content Area - High Density */
        .log-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            background-color: var(--bg-panel);
            position: relative;
        }

        .log-viewport {
            flex: 1;
            overflow-y: auto;
            overflow-x: auto;
            padding: 0;
            margin: 0;
            font-family: ui-monospace, SFMono-Regular, "SF Mono", Menlo, Consolas, "Liberation Mono", monospace;
            font-size: 12px;
            line-height: 1.35;
            background: var(--bg-panel);
            scroll-behavior: smooth;
        }
        .log-viewport::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        .log-viewport::-webkit-scrollbar-thumb {
            background: var(--border-color);
            border-radius: 4px;
        }
        .log-viewport::-webkit-scrollbar-thumb:hover {
            background: var(--text-dim);
        }

        /* Dense Line Rows */
        .log-line {
            display: flex;
            align-items: flex-start;
            padding: 1px 8px 1px 0;
            border-left: 3px solid transparent;
            min-height: 19px;
            word-break: break-all;
            white-space: pre-wrap;
        }
        .log-viewport.nowrap .log-line {
            white-space: pre;
            word-break: normal;
        }
        .log-line:hover {
            background: var(--line-hover);
        }

        .line-num {
            width: 46px;
            min-width: 46px;
            text-align: right;
            padding-right: 8px;
            color: var(--gutter-num);
            user-select: none;
            font-size: 11px;
            line-height: 1.4;
            background: var(--gutter-bg);
            border-right: 1px solid var(--border-subtle);
            margin-right: 6px;
            flex-shrink: 0;
        }

        .line-content {
            flex: 1;
            display: flex;
            align-items: flex-start;
            gap: 6px;
        }

        .level-tag {
            display: inline-block;
            font-size: 9.5px;
            font-weight: 700;
            padding: 0 4px;
            border-radius: 2px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            line-height: 15px;
            height: 15px;
            flex-shrink: 0;
            margin-top: 1px;
        }

        .line-text {
            flex: 1;
            color: var(--text-main);
        }

        /* Level Specific Colors */
        .log-line.level-error {
            background: var(--err-bg);
            border-left-color: var(--err-border);
        }
        .log-line.level-error .level-tag {
            background: var(--err-border);
            color: #fff;
        }
        .log-line.level-error .line-text {
            color: var(--err-text);
            font-weight: 600;
        }

        .log-line.level-warning {
            background: var(--warn-bg);
            border-left-color: var(--warn-border);
        }
        .log-line.level-warning .level-tag {
            background: var(--warn-border);
            color: #000;
        }
        .log-line.level-warning .line-text {
            color: var(--warn-text);
        }

        .log-line.level-info .level-tag {
            background: var(--info-border);
            color: #fff;
        }
        .log-line.level-info .line-text {
            color: var(--info-text);
        }

        .log-line.level-debug .level-tag {
            background: var(--debug-border);
            color: #fff;
        }
        .log-line.level-debug .line-text {
            color: var(--debug-text);
        }

        .log-line.level-trace .line-text {
            color: var(--trace-text);
            opacity: 0.85;
        }

        /* Highlight Search Matches */
        .search-match {
            background: var(--highlight-bg) !important;
            color: var(--highlight-text) !important;
            padding: 0 2px;
            border-radius: 2px;
            font-weight: bold;
        }

        /* Empty state */
        .empty-log-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: var(--text-dim);
            gap: 10px;
            text-align: center;
            padding: 20px;
        }
        .empty-log-state i {
            font-size: 38px;
            opacity: 0.4;
        }

        /* Quick Navigation Floating Buttons */
        .float-nav-btn-group {
            position: absolute;
            bottom: 12px;
            right: 16px;
            display: flex;
            flex-direction: column;
            gap: 4px;
            z-index: 80;
        }
        .float-nav-btn {
            width: 28px;
            height: 28px;
            border-radius: 4px;
            background: var(--bg-sidebar);
            border: 1px solid var(--border-color);
            color: var(--text-main);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 6px rgba(0,0,0,0.3);
            opacity: 0.75;
            transition: all 0.15s ease;
        }
        .float-nav-btn:hover {
            opacity: 1;
            background: var(--bg-hover);
            transform: scale(1.05);
        }

        /* Loading Overlay */
        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.4);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 200;
        }
        .loading-overlay.active {
            display: flex;
        }
        .spinner {
            width: 32px;
            height: 32px;
            border: 3px solid rgba(255,255,255,0.2);
            border-top-color: var(--primary-accent);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Flash Toast Notification */
        .toast-notify {
            position: fixed;
            top: 48px;
            right: 16px;
            padding: 8px 14px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            display: none;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            z-index: 9999;
            transition: all 0.3s ease;
        }
        .toast-notify.toast-success {
            background: #238636;
            color: #fff;
            border: 1px solid #2ea043;
        }
        .toast-notify.toast-error {
            background: #da3633;
            color: #fff;
            border: 1px solid #f85149;
        }

        /* Modal styling for Confirmation fallback */
        .modal-backdrop-custom {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.6);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 10000;
        }
        .modal-box-custom {
            background: var(--bg-sidebar);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            width: 400px;
            max-width: 90vw;
            padding: 16px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.5);
        }
        .modal-header-custom {
            font-weight: 700;
            font-size: 14px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
            color: #f85149;
        }
        .modal-body-custom {
            font-size: 12.5px;
            color: var(--text-muted);
            margin-bottom: 16px;
            line-height: 1.4;
        }
        .modal-footer-custom {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
        }

        /* Responsive layout tweaks */
        @media (max-width: 768px) {
            .sidebar {
                position: absolute;
                top: 0;
                bottom: 0;
                left: 0;
                box-shadow: 4px 0 16px rgba(0,0,0,0.5);
            }
            .sidebar.collapsed {
                margin-left: -270px;
            }
            .subbar {
                flex-wrap: wrap;
                height: auto;
                min-height: 38px;
                padding: 4px 8px;
            }
            .search-input {
                width: 140px;
            }
            .search-input:focus {
                width: 180px;
            }
        }
    </style>
</head>
<body>

    @php
        $routeNamePrefix = config('raytech-log-viewer.route.name', 'raytech-log-viewer.');
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
                <span class="pulse-dot"></span>
                <span>{{ $themeConfig['brand_name'] ?? 'RayTech' }} Log Viewer</span>
            </a>
            @if(!empty($themeConfig['show_back_button']))
                <a href="{{ url($themeConfig['back_url'] ?? '/') }}" class="btn-compact" style="font-size: 11px;" title="Return to Main Application">
                    <i class="fa fa-arrow-left"></i> {{ url($themeConfig['back_url'] ?? 'Back To Home') }}
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

            <!-- Level Filters -->
            <span class="pill-badge active" data-filter="all" id="pillAll">
                All <span id="statAll">({{ $stats['total'] }})</span>
            </span>
            <span class="pill-badge pill-error" data-filter="error" id="pillError">
                <i class="fa fa-times-circle"></i> Error <span id="statError">({{ $stats['error'] }})</span>
            </span>
            <span class="pill-badge pill-warn" data-filter="warning" id="pillWarn">
                <i class="fa fa-exclamation-triangle"></i> Warn <span id="statWarn">({{ $stats['warning'] }})</span>
            </span>
            <span class="pill-badge pill-info" data-filter="info" id="pillInfo">
                <i class="fa fa-info-circle"></i> Info <span id="statInfo">({{ $stats['info'] }})</span>
            </span>
        </div>

        <div class="filter-group">
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
        </div>
    </div>

    <!-- MAIN WORKSPACE -->
    <div class="workspace">
        <!-- SIDEBAR: LOG FILES LIST -->
        <aside class="sidebar" id="sidebarFiles">
            <div class="sidebar-search">
                <input type="text" id="filterFilesInput" placeholder="Filter files (e.g. php, laravel)..." autocomplete="off">
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
            </div>

            <div class="sidebar-footer">
                <span><i class="fa fa-hdd"></i> {{ count($allFiles) }} Log Files</span>
                <span title="Auto-scanned from storage/logs, server roots, and custom paths">Auto-detected</span>
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
                        @foreach($parsedLines as $line)
                            <div class="log-line level-{{ $line['level'] }}" data-level="{{ $line['level'] }}" data-num="{{ $line['num'] }}">
                                <div class="line-num">{{ $line['num'] }}</div>
                                <div class="line-content">
                                    @if(in_array($line['level'], ['error', 'warning', 'info', 'debug']))
                                        <span class="level-tag">{{ $line['level'] }}</span>
                                    @endif
                                    <span class="line-text">{!! htmlspecialchars($line['text'], ENT_QUOTES, 'UTF-8') !!}</span>
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

    <script>
        (function() {
            // State
            let activeKey = "{{ $selectedFile ? $selectedFile['key'] : '' }}";
            let activeName = "{{ $selectedFile ? $selectedFile['name'] : '' }}";
            let activeLevelFilter = "all";
            let autoRefreshInterval = null;
            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';

            // DOM Elements
            const viewport = document.getElementById('logViewport');
            const linesWrapper = document.getElementById('linesWrapper');
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
            const defaultTheme = "{{ $themeConfig['default_mode'] ?? 'light' }}";
            const savedTheme = localStorage.getItem('raytech_log_theme') || defaultTheme;
            document.documentElement.setAttribute('data-theme', savedTheme);
            updateThemeIcon(savedTheme);

            document.getElementById('btnToggleTheme').addEventListener('click', function() {
                const current = document.documentElement.getAttribute('data-theme');
                const next = current === 'dark' ? 'light' : 'dark';
                document.documentElement.setAttribute('data-theme', next);
                localStorage.setItem('raytech_log_theme', next);
                updateThemeIcon(next);
            });

            function updateThemeIcon(theme) {
                const icon = document.getElementById('themeIcon');
                if (icon) {
                    icon.className = theme === 'dark' ? 'fa fa-sun' : 'fa fa-moon';
                }
            }

            // Toggle Sidebar
            document.getElementById('btnToggleSidebar').addEventListener('click', function() {
                sidebar.classList.toggle('collapsed');
            });

            // Keyboard Shortcuts
            window.addEventListener('keydown', function(e) {
                if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'b') {
                    e.preventDefault();
                    sidebar.classList.toggle('collapsed');
                } else if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'f') {
                    e.preventDefault();
                    searchInput.focus();
                    searchInput.select();
                } else if (e.key === 'Escape' && document.activeElement === searchInput) {
                    clearSearch();
                }
            });

            // Toggle Word Wrap
            document.getElementById('btnToggleWrap').addEventListener('click', function() {
                viewport.classList.toggle('nowrap');
                this.classList.toggle('active');
            });

            // Fullscreen Toggle
            document.getElementById('btnToggleFullscreen').addEventListener('click', function() {
                if (!document.fullscreenElement) {
                    document.documentElement.requestFullscreen().catch(() => {});
                } else {
                    document.exitFullscreen().catch(() => {});
                }
            });

            // Scroll Top / Bottom buttons
            document.getElementById('btnScrollTop').addEventListener('click', function() {
                viewport.scrollTo({ top: 0, behavior: 'smooth' });
            });
            document.getElementById('btnScrollBottom').addEventListener('click', function() {
                viewport.scrollTo({ top: viewport.scrollHeight, behavior: 'smooth' });
            });

            // Filter Sidebar Files
            document.getElementById('filterFilesInput').addEventListener('input', function() {
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
            searchInput.addEventListener('input', function() {
                clearTimeout(searchDebounce);
                searchDebounce = setTimeout(applyFilters, 120);
            });

            searchClearBtn.addEventListener('click', clearSearch);

            function clearSearch() {
                searchInput.value = '';
                searchClearBtn.style.display = 'none';
                searchCountBadge.style.display = 'none';
                applyFilters();
                searchInput.focus();
            }

            function applyFilters() {
                const searchTerm = searchInput.value.trim();
                const termLower = searchTerm.toLowerCase();
                const lines = document.querySelectorAll('.log-line');

                if (searchTerm.length > 0) {
                    searchClearBtn.style.display = 'block';
                } else {
                    searchClearBtn.style.display = 'none';
                    searchCountBadge.style.display = 'none';
                }

                let matchCount = 0;
                let visibleCount = 0;

                lines.forEach(line => {
                    const level = line.getAttribute('data-level');
                    const textSpan = line.querySelector('.line-text');
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

                if (searchTerm.length > 0) {
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
            document.getElementById('selLineLimit').addEventListener('change', function() {
                reloadLogs(false);
            });

            // Manual Refresh
            document.getElementById('btnManualRefresh').addEventListener('click', function() {
                reloadLogs(false);
            });

            // Auto Refresh Change
            document.getElementById('selAutoRefresh').addEventListener('change', function() {
                setupAutoRefresh();
            });

            function setupAutoRefresh() {
                if (autoRefreshInterval) {
                    clearInterval(autoRefreshInterval);
                    autoRefreshInterval = null;
                }
                const val = parseInt(document.getElementById('selAutoRefresh').value);
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
                if (!activeKey) return;
                const lineLimit = document.getElementById('selLineLimit').value;
                if (!silent) {
                    loadingOverlay.classList.add('active');
                }

                const url = `{{ route($indexRoute) }}?key=${encodeURIComponent(activeKey)}&lines=${lineLimit}&ajax=1`;
                
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
                    loadingOverlay.classList.remove('active');
                });
            }

            function renderLogs(data) {
                // Update stats
                document.getElementById('statAll').textContent = `(${data.stats.total})`;
                document.getElementById('statError').textContent = `(${data.stats.error})`;
                document.getElementById('statWarn').textContent = `(${data.stats.warning})`;
                document.getElementById('statInfo').textContent = `(${data.stats.info})`;

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
                        .map(l => l.querySelector('.line-text').textContent)
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
                        document.getElementById('modalFileName').textContent = activeName;
                        document.getElementById('clearModal').style.display = 'flex';
                    }
                });
            }

            document.getElementById('btnCancelClearModal').addEventListener('click', function() {
                document.getElementById('clearModal').style.display = 'none';
            });

            document.getElementById('btnConfirmClearModal').addEventListener('click', function() {
                document.getElementById('clearModal').style.display = 'none';
                executeClearLog();
            });

            function executeClearLog() {
                loadingOverlay.classList.add('active');

                fetch("{{ route($clearRoute) }}", {
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
                        viewport.innerHTML = `
                            <div class="empty-log-state">
                                <i class="far fa-check-circle" style="color: #2ea043; opacity: 0.7;"></i>
                                <h4 style="font-weight: 600;">Log file cleared</h4>
                                <p style="font-size: 11.5px; max-width: 400px;">
                                    <code>${activeName}</code> has been emptied. File size is now 0 B.
                                </p>
                            </div>
                        `;

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
                        document.getElementById('statAll').textContent = '(0)';
                        document.getElementById('statError').textContent = '(0)';
                        document.getElementById('statWarn').textContent = '(0)';
                        document.getElementById('statInfo').textContent = '(0)';
                    } else {
                        showToast(data.message || 'Failed to clear log file.', 'error');
                    }
                })
                .catch(err => {
                    showToast('Network error while clearing log file.', 'error');
                })
                .finally(() => {
                    loadingOverlay.classList.remove('active');
                });
            }

            // Toast Message Utility
            function showToast(message, type = 'success') {
                const toast = document.getElementById('toastNotify');
                const toastMsg = document.getElementById('toastMessage');
                const toastIcon = document.getElementById('toastIcon');

                if (!toast) return;

                toastMsg.textContent = message;
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
    </script>
</body>
</html>
