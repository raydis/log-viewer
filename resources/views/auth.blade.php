<!DOCTYPE html>
<html lang="en" data-theme="{{ $themeConfig['default_mode'] ?? 'light' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $themeConfig['title'] ?? 'RayTech Log Viewer' }} — Authentication Required</title>
    
    @if(!empty($logoUrl))
        <link rel="icon" type="image/jpeg" href="{{ $logoUrl }}">
        <link rel="shortcut icon" type="image/jpeg" href="{{ $logoUrl }}">
    @endif

    @if(!empty($assets['fontawesome_css']))
        <link rel="stylesheet" href="{{ $assets['fontawesome_css'] }}">
    @endif
    <link rel="stylesheet" href="{{ route($assetRoute, ['file' => 'css/raytech-log-viewer.css']) }}?v={{ $version }}">

    <style>
        .auth-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--bg-body);
            padding: 20px;
        }

        .auth-card {
            width: 100%;
            max-width: 420px;
            background: var(--bg-panel);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
            padding: 32px;
            text-align: center;
            animation: fadeIn 0.25s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .auth-logo {
            width: 72px;
            height: 72px;
            object-fit: cover;
            border-radius: 14px;
            margin: 0 auto 16px auto;
            border: 2px solid var(--border-color);
            display: block;
        }

        .auth-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--text-main);
            margin: 0 0 6px 0;
            letter-spacing: -0.3px;
        }

        .auth-subtitle {
            font-size: 13px;
            color: var(--text-muted);
            margin: 0 0 24px 0;
        }

        .auth-alert {
            padding: 10px 14px;
            border-radius: 6px;
            font-size: 12.5px;
            margin-bottom: 20px;
            text-align: left;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            line-height: 1.4;
        }

        .auth-alert-danger {
            background: var(--err-bg);
            border: 1px solid var(--err-border);
            color: var(--err-text);
        }

        .auth-alert-warning {
            background: var(--warn-bg);
            border: 1px solid var(--warn-border);
            color: var(--warn-text);
        }

        .auth-input-group {
            position: relative;
            margin-bottom: 20px;
            text-align: left;
        }

        .auth-input-group label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: var(--text-main);
            margin-bottom: 6px;
        }

        .auth-input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .auth-input-wrapper i.input-icon {
            position: absolute;
            left: 12px;
            color: var(--text-dim);
            font-size: 14px;
            pointer-events: none;
        }

        .auth-input {
            width: 100%;
            height: 42px;
            padding: 0 40px 0 36px;
            background: var(--bg-body);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 14px;
            color: var(--text-main);
            outline: none;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }

        .auth-input:focus {
            border-color: var(--primary-brand);
            box-shadow: 0 0 0 3px rgba(31, 111, 235, 0.18);
        }

        .btn-toggle-eye {
            position: absolute;
            right: 12px;
            background: none;
            border: none;
            color: var(--text-dim);
            cursor: pointer;
            padding: 4px;
            font-size: 14px;
        }

        .btn-toggle-eye:hover {
            color: var(--text-main);
        }

        .btn-auth-submit {
            width: 100%;
            height: 42px;
            background: var(--primary-brand);
            color: #ffffff;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: background 0.15s ease, transform 0.05s ease;
        }

        .btn-auth-submit:hover {
            opacity: 0.92;
        }

        .btn-auth-submit:active {
            transform: scale(0.99);
        }

        .auth-footer {
            margin-top: 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 12px;
            color: var(--text-dim);
        }

        .auth-footer a {
            color: var(--text-muted);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .auth-footer a:hover {
            color: var(--primary-brand);
        }

        .attempts-badge {
            display: inline-block;
            background: var(--bg-subbar);
            border: 1px solid var(--border-color);
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 11px;
            color: var(--text-dim);
        }
    </style>

    <script>
        (function() {
            try {
                var savedTheme = localStorage.getItem('raytech_log_viewer_theme');
                var savedTheme = localStorage.getItem('log_viewer_theme') || localStorage.getItem('log_theme') || localStorage.getItem('raytech_log_viewer_theme');
                if (savedTheme) {
                    document.documentElement.setAttribute('data-theme', savedTheme);
                }
            } catch (e) {}
        })();
    </script>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            @if(!empty($logoUrl))
                <img src="{{ $logoUrl }}" alt="Logo" class="auth-logo">
            @endif

            <h1 class="auth-title">{{ $themeConfig['brand_name'] ?? 'RayTech' }} Log Viewer</h1>
            <p class="auth-subtitle">Password verification required to access application logs.</p>

            @if(session('error'))
                <div class="auth-alert auth-alert-danger">
                    <i class="fa fa-exclamation-circle" style="font-size: 15px; margin-top: 1px;"></i>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            @if(session('warning'))
                <div class="auth-alert auth-alert-warning">
                    <i class="fa fa-exclamation-triangle" style="font-size: 15px; margin-top: 1px;"></i>
                    <span>{{ session('warning') }}</span>
                </div>
            @endif

            <form action="{{ route($authRoute) }}" method="POST" autocomplete="off">
                @csrf
                <div class="auth-input-group">
                    <label for="passwordInput">Access Password</label>
                    <div class="auth-input-wrapper">
                        <i class="fa fa-lock input-icon"></i>
                        <input type="password" 
                               id="passwordInput" 
                               name="password" 
                               class="auth-input" 
                               placeholder="Enter password..." 
                               required 
                               autofocus>
                        <button type="button" class="btn-toggle-eye" id="btnToggleEye" title="Show / Hide Password">
                            <i class="fa fa-eye" id="iconEye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-auth-submit">
                    <i class="fa fa-key"></i> Unlock Log Viewer
                </button>
            </form>

            <div class="auth-footer">
                <a href="{{ $themeConfig['back_url'] ?? '/' }}">
                    <i class="fa fa-arrow-left"></i> {{ $themeConfig['back_url_name'] ?? 'Back to Home' }}
                </a>

                @if(!empty($attempts) && $attempts > 0)
                    <span class="attempts-badge" title="Failed login attempts">
                        Attempt {{ $attempts }} / {{ $maxAttempts ?? 3 }}
                    </span>
                @else
                    <span class="attempts-badge">
                        Max {{ $maxAttempts ?? 3 }} attempts
                    </span>
                @endif
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var input = document.getElementById('passwordInput');
            var btnEye = document.getElementById('btnToggleEye');
            var iconEye = document.getElementById('iconEye');

            if (btnEye && input && iconEye) {
                btnEye.addEventListener('click', function() {
                    if (input.type === 'password') {
                        input.type = 'text';
                        iconEye.className = 'fa fa-eye-slash';
                    } else {
                        input.type = 'password';
                        iconEye.className = 'fa fa-eye';
                    }
                });
            }
        });
    </script>
</body>
</html>
