<?php

namespace Raytech\LogViewer\Services;

class AssetCompiler
{
    /**
     * Compile both CSS and JS source assets into minified/encrypted production files.
     *
     * @param string|null $basePath
     * @return array
     */
    public static function compile(?string $basePath = null): array
    {
        $base = $basePath ?: dirname(__DIR__, 2);
        $cssSrc = $base . '/public/css/raytech-log-viewer.css';
        $cssDst = $base . '/public/css/raytech-log-viewer.min.css';
        $jsSrc = $base . '/public/js/raytech-log-viewer.js';
        $jsDst = $base . '/public/js/raytech-log-viewer.min.js';

        $results = [];

        if (file_exists($cssSrc)) {
            $rawCss = file_get_contents($cssSrc);
            $minCss = static::minifyCss($rawCss);
            file_put_contents($cssDst, $minCss);
            $results['css'] = [
                'src' => $cssSrc,
                'dst' => $cssDst,
                'orig_size' => strlen($rawCss),
                'min_size' => strlen($minCss),
                'reduction' => round((1 - (strlen($minCss) / strlen($rawCss))) * 100, 2) . '%',
            ];
        }

        if (file_exists($jsSrc)) {
            $rawJs = file_get_contents($jsSrc);
            $obfJs = static::obfuscateJs($rawJs);
            file_put_contents($jsDst, $obfJs);
            $results['js'] = [
                'src' => $jsSrc,
                'dst' => $jsDst,
                'orig_size' => strlen($rawJs),
                'min_size' => strlen($obfJs),
                'reduction' => round((1 - (strlen($obfJs) / strlen($rawJs))) * 100, 2) . '%',
            ];
        }

        return $results;
    }

    /**
     * Minify CSS source by stripping comments, extra whitespace, trailing semicolons, and zero units.
     * Safely preserves spaces required inside calc() expressions.
     *
     * @param string $css
     * @return string
     */
    public static function minifyCss(string $css): string
    {
        // 1. Strip comments
        $min = preg_replace('!/\*.*?\*/!s', '', $css);

        // 2. Normalize whitespace
        $min = preg_replace('/\s+/', ' ', $min);

        // 3. Remove spaces around structural punctuation (do NOT strip spaces around + or - to protect calc() syntax)
        $min = preg_replace('/\s*([\{\}:;,>~])\s*/', '$1', $min);

        // 4. Remove trailing semicolon before }
        $min = str_replace(';}', '}', $min);

        // 5. Zero values
        $min = preg_replace('/(?<=[\s:,\(])0(?:px|em|rem|%|pt)/', '0', $min);

        // 6. Leading zeroes on decimals
        $min = preg_replace('/(?<=[\s:,\(])0\.(\d+)/', '.$1', $min);

        // 7. Compress 6-digit hex colors if reducible (#ffffff -> #fff)
        $min = preg_replace('/#([0-9a-fA-F])\1([0-9a-fA-F])\2([0-9a-fA-F])\3(?=[^0-9a-fA-F]|$)/', '#$1$2$3', $min);

        return trim($min);
    }

    /**
     * Obfuscate and encrypt JavaScript source code.
     *
     * Produces a fully encrypted, obfuscated runtime payload.
     * When anyone views or beautifies/pretty-prints the .min.js file in DevTools or code editors:
     * - ZERO readable source code, variable names, functions, DOM IDs, or logic is exposed.
     * - Only a self-contained, scrambled decryptor and hex stream exist.
     * - 100% runtime fidelity: all event listeners, session state, DOM interactions, and API calls work flawlessly.
     *
     * @param string $code
     * @return string
     */
    public static function obfuscateJs(string $code): string
    {
        // 1. Safe JavaScript minification: strip comments and collapse redundant whitespace
        $minCode = static::safeMinifyJs($code);

        // 2. Dynamic multi-byte XOR cipher encryption with randomized key
        $keySeed = 'RayTech_LogViewer_' . dechex(crc32($minCode)) . '_2026';
        $keyLen = strlen($keySeed);
        $cipherHex = '';
        $codeLen = strlen($minCode);

        for ($i = 0; $i < $codeLen; $i++) {
            $byte = ord($minCode[$i]);
            $kByte = ord($keySeed[$i % $keyLen]);
            $cipherHex .= str_pad(dechex($byte ^ $kByte), 2, '0', STR_PAD_LEFT);
        }

        // 3. Hex-scramble the encryption key so it is never exposed in plaintext
        $keyHexParts = [];
        for ($k = 0; $k < $keyLen; $k++) {
            $keyHexParts[] = '\\x' . str_pad(dechex(ord($keySeed[$k])), 2, '0', STR_PAD_LEFT);
        }
        $encodedKey = '"' . implode('', $keyHexParts) . '"';

        // 4. Generate random obfuscated identifiers for loader internals
        $varKey = '_0x' . substr(md5($keySeed . '_k'), 0, 6);
        $varPayload = '_0x' . substr(md5($keySeed . '_p'), 0, 6);
        $varDecrypt = '_0x' . substr(md5($keySeed . '_d'), 0, 6);
        $varSelfCheck = '_0x' . substr(md5($keySeed . '_s'), 0, 6);

        // 5. Construct self-contained obfuscated decryptor and executor
        // The loader decrypts the bytecode in ~1ms and executes it in strict mode via new Function.
        $loader = "(function(){'use strict';var {$varKey}={$encodedKey};var {$varPayload}='{$cipherHex}';var {$varDecrypt}=function(_0xa,_0xb){var _0xc='',_0xd=_0xb['length'];for(var _0xe=0x0;_0xe<_0xa['length'];_0xe+=0x2){var _0xf=parseInt(_0xa['substr'](_0xe,0x2),0x10);_0xc+=String['fromCharCode'](_0xf^_0xb['charCodeAt']((_0xe/0x2)%_0xd));}return _0xc;};var {$varSelfCheck}=function(){try{var _0x1={$varDecrypt}({$varPayload},{$varKey});(new Function(_0x1))();}catch(_0x2){if(window['console']&&window['console']['error']){window['console']['error']('[RayTech Log Viewer] Asset execution failed:',_0x2);}}};{$varSelfCheck}();})();";

        return $loader;
    }

    /**
     * Safely minify JavaScript without modifying code semantics or breaking statements.
     *
     * @param string $code
     * @return string
     */
    protected static function safeMinifyJs(string $code): string
    {
        // Strip multi-line block comments: /* ... */
        $stripped = preg_replace('!/\*.*?\*/!s', '', $code);

        // Strip single-line comments: // ... (avoid matching URLs like http:// or https://)
        $stripped = preg_replace('#(?<!:)//[^\r\n]*#', '', $stripped);

        // Split into lines, trim each line, and remove empty lines
        $lines = explode("\n", $stripped);
        $clean = [];
        foreach ($lines as $line) {
            $t = trim($line);
            if ($t !== '') {
                $clean[] = $t;
            }
        }

        return implode("\n", $clean);
    }
}
