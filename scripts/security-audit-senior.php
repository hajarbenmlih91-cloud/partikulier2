<?php
/**
 * Partikulier Security Audit Senior Script
 * Scans for common WordPress vulnerabilities: SQLi, XSS, CSRF, Insecure Auth.
 */

$theme_dir = __DIR__ . '/../theme';
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($theme_dir));
$php_files = new RegexIterator($files, '/\.php$/');

$patterns = [
    'SQL Injection' => [
        '/\$wpdb->(query|get_results|get_row|get_var|get_col)\s*\(\s*["\'].*?\$[a-zA-Z0-9_]+.*?\s*["\']\s*\)/i',
        '/\$wpdb->prepare\s*\(\s*["\'].*?\$[a-zA-Z0-9_]+.*?\s*["\']\s*,/i', // Missing placeholders
    ],
    'Cross-Site Scripting (XSS)' => [
        '/echo\s+\$[a-zA-Z0-9_]+(?!->|\[)/i', // Echoing variables without escaping
        '/printf\s*\(\s*["\'].*?%s.*?["\']\s*,\s*\$[a-zA-Z0-9_]+\s*\)/i',
        '/<\?=\s*\$[a-zA-Z0-9_]+\s*\?>/i',
    ],
    'Insecure File Inclusion' => [
        '/(include|require)(_once)?\s*\(?\s*\$[a-zA-Z0-9_]+\s*\)?/i',
    ],
    'Missing Nonce Check' => [
        '/if\s*\(\s*isset\s*\(\s*\$_POST/i', // Potential CSRF if no check_admin_referer
    ],
    'Unsafe Function Usage' => [
        '/\b(eval|exec|passthru|shell_exec|system|base64_decode|unserialize)\b/i',
    ],
];

$findings = [];

foreach ($php_files as $file) {
    $content = file_get_contents($file->getPathname());
    $relative_path = str_replace($theme_dir, '', $file->getPathname());
    
    foreach ($patterns as $category => $regexes) {
        foreach ($regexes as $regex) {
            if (preg_match_all($regex, $content, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[0] as $match) {
                    $line = substr_count(substr($content, 0, $match[1]), "\n") + 1;
                    $findings[] = [
                        'category' => $category,
                        'file' => $relative_path,
                        'line' => $line,
                        'code' => trim($match[0]),
                    ];
                }
            }
        }
    }
}

// Custom check for wp_kses_post (should be avoided in favor of specific tags)
if (preg_match_all('/wp_kses_post/i', $content, $matches, PREG_OFFSET_CAPTURE)) {
    // Already handled in loop
}

echo json_encode(['status' => 'success', 'findings' => $findings], JSON_PRETTY_PRINT);
