<?php

namespace Raytech\LogViewer\Services;

class AssetCompiler
{
    /**
     * Compile both CSS and JS source assets into minified/obfuscated production files.
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

        // 3. Remove spaces around operators & punctuation
        $min = preg_replace('/\s*([\{\}:;,>+~])\s*/', '$1', $min);

        // 4. Remove trailing semicolon before }
        $min = str_replace(';}', '}', $min);

        // 5. Zero values
        $min = preg_replace('/(?<=[\s:,\(])0(?:px|em|rem|%|pt)/', '0', $min);

        // 6. Leading zeroes on decimals
        $min = preg_replace('/(?<=[\s:,\(])0\.(\d+)/', '.$1', $min);

        // 7. Compress 6-digit hex colors if reducible (#ffffff -> #fff)
        $min = preg_replace('/#([0-9a-fA-F])\1([0-9a-fA-F])\2([0-9a-fA-F])\3(?=[^0-9a-fA-F]|$)/', '#$1$2$3', $min);

        // 8. Empty rules
        $min = preg_replace('/[^{}]+\{\}/', '', $min);

        return trim($min);
    }

    /**
     * Obfuscate and minify JavaScript source code.
     * Extracts string literals into an encrypted/rotated table and mangles internal private variables.
     * When pretty-printed / beautified in DevTools, the actual original code is not revealed.
     *
     * @param string $code
     * @return string
     */
    public static function obfuscateJs(string $code): string
    {
        $tokens = static::tokenizeJs($code);

        $privateIdentifiers = [
            'activeKey', 'activeName', 'activeLevels', 'activeOrder', 'savedOrder',
            'autoRefreshInterval', 'cachedEntries', 'entriesMap', 'lastErrorCount',
            'originalDocumentTitle', 'titleFlashTimer', 'flashTabTitle', 'syncEntriesMap',
            'buildDetailsHtml', 'ensureDetailsRendered', 'indexUrl', 'clearUrl', 'defaultTheme',
            'csrfToken', 'viewport', 'searchInput', 'searchClearBtn', 'searchCountBadge',
            'loadingOverlay', 'sidebar', 'savedTheme', 'btnToggleTheme', 'updateThemeIcon',
            'btnToggleSidebar', 'selectedEntryIndex', 'getVisibleEntries', 'selectKeyboardEntry',
            'btnToggleWrap', 'savedWrap', 'btnToggleFullscreen', 'btnScrollTop', 'btnScrollBottom',
            'btnToggleOrder', 'updateOrderButton', 'btnToggleExpandAll', 'updateExpandAllLabel',
            'filterFilesInput', 'datePickerJump', 'btnClearDateJump', 'applySidebarFilter',
            'pillBadges', 'savedLevels', 'updateSidebarLinksLineLimit', 'selLineLimit',
            'initLineLimitState', 'btnManualRefresh', 'selAutoRefresh', 'setupAutoRefresh',
            'initAutoRefreshState', 'reloadLogs', 'renderLogs', 'escapeHtml', 'btnCopy',
            'btnExportToggle', 'exportDropdownMenu', 'exportDropdownWrap', 'btnExportJson',
            'btnExportCsv', 'exportFilteredLogs', 'escapeCsv', 'downloadBlob', 'btnSearchFiles',
            'initSearchFromUrl', 'clearSearch', 'filterVisibleEntries', 'highlightMatch',
            'escapeRegExp', 'showToast', 'syncChannel', 'handleEmergencyClose',
            'destroySessionAndCloseAll', 'destroyKey', 'btnLock', 'isAutoRefresh',
            'currentErrors', 'visibleCount', 'matchCount', 'escapedShort', 'levelIcon',
            'chevron', 'countBadge', 'entryId', 'framesHtml', 'contextHtml', 'rawHtml',
            'tabsHeaderHtml', 'tabsPanesHtml', 'exceptionHtml', 'hasFrames', 'hasContext',
            'isAlt', 'count', 'target', 'isEditing', 'summary', 'details', 'isExpanded',
            'anyCollapsed', 'allEntries', 'targetTab', 'copyBtn', 'tabBtn', 'dateTerm',
            'textTerm', 'selectedDate', 'matchedItem', 'matchedKey', 'bFilter', 'val',
            'spinIcon', 'lineLimit', 'autoRefreshVal', 'separator', 'url', 'statAll',
            'statError', 'statWarn', 'statInfo', 'sizeEl', 'modEl', 'buildEntryHtml',
            'html', 'visibleEntries', 'isShown', 'dateStr', 'baseName', 'filename',
            'exportData', 'level', 'typeEl', 'dateEl', 'shortTextEl', 'codeEl',
            'excTitleEl', 'excMsgEl', 'excFileLineEl', 'contextEl', 'headers', 'rows',
            'row', 'searchTerm', 'levelMatch', 'searchMatch', 'shortText', 'bodyText',
            'toast', 'toastMsg', 'toastIcon'
        ];

        $idMap = [];
        $counter = 0x1100;
        foreach ($privateIdentifiers as $name) {
            $idMap[$name] = '_0x' . dechex($counter++);
        }

        $stringTable = [];
        $stringIndices = [];

        foreach ($tokens as $token) {
            if ($token['type'] === 'string_single' || $token['type'] === 'string_double') {
                $raw = $token['value'];
                $val = substr($raw, 1, -1);
                if ($val === 'use strict') {
                    continue;
                }
                if (!isset($stringIndices[$raw])) {
                    $stringIndices[$raw] = count($stringTable);
                    $stringTable[] = $val;
                }
            }
        }

        $shiftCount = 0x15f;
        $totalStrings = count($stringTable);

        // Cancel out runtime rotation: PHP shifts elements from back to front
        $phpTable = $stringTable;
        for ($i = 0; $i < ($shiftCount % $totalStrings); $i++) {
            array_unshift($phpTable, array_pop($phpTable));
        }

        $tableVarName = '_0x' . dechex(0x4a1b);
        $decoderFuncName = '_0x' . dechex(0x3e8a);

        $hexEncodedItems = [];
        foreach ($phpTable as $str) {
            $encoded = '';
            for ($c = 0; $c < strlen($str); $c++) {
                $ord = ord($str[$c]);
                $encoded .= '\\x' . str_pad(dechex($ord), 2, '0', STR_PAD_LEFT);
            }
            $hexEncodedItems[] = '"' . $encoded . '"';
        }

        $header = "var {$tableVarName}=[" . implode(',', $hexEncodedItems) . "];";
        $header .= "(function(_0x1,_0x2){var _0x3=function(_0x4){while(--_0x4){_0x1['push'](_0x1['shift']());}};_0x3(++_0x2);}({$tableVarName},0x" . dechex($shiftCount - 1) . "));";
        $header .= "var {$decoderFuncName}=function(_0x1,_0x2){_0x1=_0x1-0x0;var _0x3={$tableVarName}[_0x1];return _0x3;};";

        $tokenCount = count($tokens);
        $body = '';
        $prevType = null;
        $prevVal = '';

        for ($i = 0; $i < $tokenCount; $i++) {
            $token = $tokens[$i];
            $type = $token['type'];
            $val = $token['value'];

            if ($type === 'comment_single' || $type === 'comment_multi' || $type === 'whitespace') {
                continue;
            }

            if ($type === 'string_single' || $type === 'string_double') {
                $unquoted = substr($val, 1, -1);
                if ($unquoted === 'use strict') {
                    $out = "'use strict';";
                } else {
                    $idx = $stringIndices[$val];
                    $call = "{$decoderFuncName}(0x" . dechex($idx) . ")";

                    // Check if this string is an object literal key
                    $prevNonWs = null;
                    for ($p = $i - 1; $p >= 0; $p--) {
                        if ($tokens[$p]['type'] !== 'whitespace' && $tokens[$p]['type'] !== 'comment_single' && $tokens[$p]['type'] !== 'comment_multi') {
                            $prevNonWs = $tokens[$p]['value'];
                            break;
                        }
                    }

                    $nextNonWs = null;
                    for ($n = $i + 1; $n < $tokenCount; $n++) {
                        if ($tokens[$n]['type'] !== 'whitespace' && $tokens[$n]['type'] !== 'comment_single' && $tokens[$n]['type'] !== 'comment_multi') {
                            $nextNonWs = $tokens[$n]['value'];
                            break;
                        }
                    }

                    if (($prevNonWs === '{' || $prevNonWs === ',') && $nextNonWs === ':') {
                        $out = "[{$call}]";
                    } else {
                        $out = $call;
                    }
                }
            } elseif ($type === 'word') {
                $out = isset($idMap[$val]) ? $idMap[$val] : $val;
            } elseif ($type === 'string_template') {
                $valMangled = preg_replace_callback('/\$\{([^}]+)\}/', function ($m) use ($idMap) {
                    $expr = $m[1];
                    foreach ($idMap as $orig => $mangled) {
                        $expr = preg_replace('/\b' . $orig . '\b/', $mangled, $expr);
                    }
                    return '${' . $expr . '}';
                }, $val);

                $minTemplate = preg_replace('/>\s+</', '><', $valMangled);
                $minTemplate = preg_replace('/[ \t]+/', ' ', $minTemplate);
                $minTemplate = preg_replace('/\n\s*/', '', $minTemplate);
                $out = $minTemplate;
            } else {
                $out = $val;
            }

            // Add space between tokens only when syntactically required
            if ($prevType === 'word' && ($type === 'word' || isset($stringIndices[$val]))) {
                $body .= ' ';
            } elseif (in_array($prevVal, ['return', 'case', 'throw', 'typeof', 'new', 'delete', 'in', 'instanceof', 'const', 'let', 'var', 'function', 'class', 'extends', 'yield', 'await', 'async'], true)) {
                $body .= ' ';
            } elseif (($prevVal === '+' && ($out[0] === '+' || $out === '++')) || ($prevVal === '-' && ($out[0] === '-' || $out === '--'))) {
                $body .= ' ';
            } elseif (($prevVal === '++' && $out[0] === '+') || ($prevVal === '--' && $out[0] === '-')) {
                $body .= ' ';
            }

            $body .= $out;
            $prevType = $type;
            $prevVal = $out;
        }

        return $header . $body;
    }

    /**
     * Tokenize JavaScript source code into syntax tokens.
     *
     * @param string $code
     * @return array
     */
    protected static function tokenizeJs(string $code): array
    {
        $len = strlen($code);
        $tokens = [];
        $i = 0;

        while ($i < $len) {
            $char = $code[$i];

            // 1. Single-line comment: //
            if ($char === '/' && $i + 1 < $len && $code[$i + 1] === '/') {
                $start = $i;
                $i += 2;
                while ($i < $len && $code[$i] !== "\n" && $code[$i] !== "\r") {
                    $i++;
                }
                $tokens[] = ['type' => 'comment_single', 'value' => substr($code, $start, $i - $start)];
                continue;
            }

            // 2. Multi-line comment: /* ... */
            if ($char === '/' && $i + 1 < $len && $code[$i + 1] === '*') {
                $start = $i;
                $i += 2;
                while ($i + 1 < $len && !($code[$i] === '*' && $code[$i + 1] === '/')) {
                    $i++;
                }
                $i += 2;
                $tokens[] = ['type' => 'comment_multi', 'value' => substr($code, $start, $i - $start)];
                continue;
            }

            // 3. Single-quoted string: '...'
            if ($char === "'") {
                $start = $i;
                $i++;
                while ($i < $len) {
                    if ($code[$i] === '\\') {
                        $i += 2;
                        continue;
                    }
                    if ($code[$i] === "'") {
                        $i++;
                        break;
                    }
                    $i++;
                }
                $tokens[] = ['type' => 'string_single', 'value' => substr($code, $start, $i - $start)];
                continue;
            }

            // 4. Double-quoted string: "..."
            if ($char === '"') {
                $start = $i;
                $i++;
                while ($i < $len) {
                    if ($code[$i] === '\\') {
                        $i += 2;
                        continue;
                    }
                    if ($code[$i] === '"') {
                        $i++;
                        break;
                    }
                    $i++;
                }
                $tokens[] = ['type' => 'string_double', 'value' => substr($code, $start, $i - $start)];
                continue;
            }

            // 5. Template literal: `...` (with full nested interpolation support)
            if ($char === '`') {
                $start = $i;
                $i++;
                $stack = ['TEMPLATE'];

                while ($i < $len && !empty($stack)) {
                    if ($code[$i] === '\\') {
                        $i += 2;
                        continue;
                    }

                    $current = end($stack);

                    if ($current === 'TEMPLATE') {
                        if ($code[$i] === '`') {
                            array_pop($stack);
                            $i++;
                            continue;
                        }
                        if ($code[$i] === '$' && $i + 1 < $len && $code[$i + 1] === '{') {
                            $stack[] = 'EXPR';
                            $i += 2;
                            continue;
                        }
                        $i++;
                        continue;
                    }

                    if ($current === 'EXPR') {
                        if ($code[$i] === '}') {
                            array_pop($stack);
                            $i++;
                            continue;
                        }
                        if ($code[$i] === '{') {
                            $stack[] = 'EXPR';
                            $i++;
                            continue;
                        }
                        if ($code[$i] === '`') {
                            $stack[] = 'TEMPLATE';
                            $i++;
                            continue;
                        }
                        if ($code[$i] === "'" || $code[$i] === '"') {
                            $quote = $code[$i++];
                            while ($i < $len) {
                                if ($code[$i] === '\\') {
                                    $i += 2;
                                    continue;
                                }
                                if ($code[$i] === $quote) {
                                    $i++;
                                    break;
                                }
                                $i++;
                            }
                            continue;
                        }
                        $i++;
                    }
                }

                $tokens[] = ['type' => 'string_template', 'value' => substr($code, $start, $i - $start)];
                continue;
            }

            // 6. Regex literal or division: /.../
            if ($char === '/') {
                $prevNonSpace = null;
                for ($k = count($tokens) - 1; $k >= 0; $k--) {
                    if ($tokens[$k]['type'] !== 'whitespace' && $tokens[$k]['type'] !== 'comment_single' && $tokens[$k]['type'] !== 'comment_multi') {
                        $prevNonSpace = $tokens[$k];
                        break;
                    }
                }

                $isRegex = false;
                if ($prevNonSpace === null) {
                    $isRegex = true;
                } else {
                    $val = $prevNonSpace['value'];
                    if (in_array($val, ['(', '=', ':', '[', '!', '&', '|', '?', ';', ',', 'return', 'case', 'throw', 'typeof'], true)) {
                        $isRegex = true;
                    }
                }

                if ($isRegex) {
                    $start = $i;
                    $i++;
                    while ($i < $len) {
                        if ($code[$i] === '\\') {
                            $i += 2;
                            continue;
                        }
                        if ($code[$i] === '/') {
                            $i++;
                            while ($i < $len && ctype_alpha($code[$i])) {
                                $i++;
                            }
                            break;
                        }
                        $i++;
                    }
                    $tokens[] = ['type' => 'regex', 'value' => substr($code, $start, $i - $start)];
                    continue;
                }
            }

            // 7. Whitespace
            if (ctype_space($char)) {
                $start = $i;
                while ($i < $len && ctype_space($code[$i])) {
                    $i++;
                }
                $tokens[] = ['type' => 'whitespace', 'value' => substr($code, $start, $i - $start)];
                continue;
            }

            // 8. Word / Identifier / Number
            if (ctype_alnum($char) || $char === '_' || $char === '$') {
                $start = $i;
                while ($i < $len && (ctype_alnum($code[$i]) || $code[$i] === '_' || $code[$i] === '$')) {
                    $i++;
                }
                $tokens[] = ['type' => 'word', 'value' => substr($code, $start, $i - $start)];
                continue;
            }

            // 9. Multi-character Operators and Punctuation
            $op3 = ($i + 2 < $len) ? substr($code, $i, 3) : '';
            if (in_array($op3, ['===', '!==', '>>>', '<<=', '>>=', '**=', '&&=', '||=', '??='], true)) {
                $tokens[] = ['type' => 'punct', 'value' => $op3];
                $i += 3;
                continue;
            }

            $op2 = ($i + 1 < $len) ? substr($code, $i, 2) : '';
            if (in_array($op2, ['==', '!=', '<=', '>=', '&&', '||', '++', '--', '+=', '-=', '*=', '/=', '%=', '&=', '|=', '^=', '=>', '??', '?.', '**', '<<', '>>'], true)) {
                $tokens[] = ['type' => 'punct', 'value' => $op2];
                $i += 2;
                continue;
            }

            // 10. Single Punctuation / Operator
            $tokens[] = ['type' => 'punct', 'value' => $char];
            $i++;
        }

        return $tokens;
    }
}
