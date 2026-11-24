<?php

namespace Raytech\LogViewer\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Raytech\LogViewer\RaytechLogViewer;
use Symfony\Component\HttpFoundation\Response;

class InjectLogViewerShortcutMiddleware
{
    /**
     * Handle an incoming request and optionally inject the log viewer hotkey script into HTML responses.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // 0. Check if log viewer plugin is globally enabled
        if (!config('raytech-log-viewer.enabled', true)) {
            return $response;
        }

        // 1. Check if shortcut feature is enabled in config
        if (!config('raytech-log-viewer.shortcut.enabled', false)) {
            return $response;
        }

        // 2. Only inject if auto_inject is true
        if (!config('raytech-log-viewer.shortcut.auto_inject', true)) {
            return $response;
        }

        // 3. Do not inject for AJAX, JSON, XMLHttpRequests, or API calls
        if ($request->ajax() || $request->wantsJson()) {
            return $response;
        }

        // 4. Do not inject if already inside the log-viewer interface
        $routePrefix = trim((string) config('raytech-log-viewer.route.prefix', 'log-viewer'), '/');
        if ($request->is($routePrefix) || $request->is($routePrefix . '/*')) {
            return $response;
        }

        // 5. Must be a valid Symfony / Laravel Response instance
        if (!$response instanceof Response) {
            return $response;
        }

        // 6. Must be an HTML response
        $contentType = $response->headers->get('Content-Type');
        if ($contentType && !str_contains($contentType, 'text/html')) {
            return $response;
        }

        // 7. Response must have content and contain a </body> tag
        $content = $response->getContent();
        if (!is_string($content) || stripos($content, '</body>') === false) {
            return $response;
        }

        // 8. Inject the script right before </body>
        $script = RaytechLogViewer::renderShortcutScript();
        if (empty($script)) {
            return $response;
        }

        $pos = strripos($content, '</body>');
        if ($pos !== false) {
            $content = substr($content, 0, $pos) . $script . "\n" . substr($content, $pos);
            $response->setContent($content);
        }

        return $response;
    }
}
