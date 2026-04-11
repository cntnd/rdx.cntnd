<?php

/**
 * Path security helper for the asset_editor addon.
 *
 * Every user-supplied path (relative, coming from GET/POST) is resolved against
 * one of the configured base paths. The base paths themselves are stored
 * already-realpath'd in the addon config, and this class refuses any relative
 * path that, once normalized, would leave the base directory. Directory
 * traversal via ".." is handled by the normalizer, not by realpath() — so files
 * that do not yet exist can still be validated.
 *
 * Only .css and .js files are considered editable. Everything else is rejected
 * at the type check, regardless of where it lives.
 */
class asset_editor_path
{
    public const ALLOWED_EXTENSIONS = ['css', 'js'];

    /**
     * @return array<int, array{label: string, path: string}>
     */
    public static function getBasePaths(): array
    {
        $raw = rex_addon::get('asset_editor')->getConfig('base_paths', []);
        if (!is_array($raw)) {
            return [];
        }

        $result = [];
        foreach ($raw as $entry) {
            if (!is_array($entry) || !isset($entry['path'])) {
                continue;
            }
            $real = realpath((string) $entry['path']);
            if ($real === false || !is_dir($real)) {
                continue;
            }
            $label = isset($entry['label']) && $entry['label'] !== '' ? (string) $entry['label'] : basename($real);
            $result[] = ['label' => $label, 'path' => $real];
        }
        return $result;
    }

    /**
     * Resolve a (baseIndex, relative) pair to an absolute path that is
     * guaranteed to live inside the chosen base path.
     *
     * Returns null on any violation: unknown base, traversal escape, or a
     * non-absolute result after normalization.
     */
    public static function resolve(int $baseIndex, string $relative): ?string
    {
        $bases = self::getBasePaths();
        if (!isset($bases[$baseIndex])) {
            return null;
        }
        $base = $bases[$baseIndex]['path'];

        $relative = str_replace('\\', '/', $relative);
        $relative = ltrim($relative, '/');

        $combined = $relative === '' ? $base : $base . DIRECTORY_SEPARATOR . $relative;
        $normalized = self::normalize($combined);
        if ($normalized === null) {
            return null;
        }

        if ($normalized !== $base && !str_starts_with($normalized, $base . DIRECTORY_SEPARATOR)) {
            return null;
        }

        return $normalized;
    }

    /**
     * Normalize an absolute path by collapsing "." and ".." segments. Unlike
     * realpath(), the target does not need to exist, so this also works for
     * files the editor is about to create.
     */
    private static function normalize(string $path): ?string
    {
        $path = str_replace('\\', '/', $path);
        $isAbsolute = $path !== '' && ($path[0] === '/' || (bool) preg_match('#^[A-Za-z]:/#', $path));
        if (!$isAbsolute) {
            return null;
        }

        $parts = explode('/', $path);
        $out = [];
        foreach ($parts as $i => $part) {
            if ($part === '' || $part === '.') {
                if ($i === 0 && $part === '') {
                    $out[] = '';
                }
                continue;
            }
            if ($part === '..') {
                if (count($out) > 1) {
                    array_pop($out);
                }
                continue;
            }
            $out[] = $part;
        }

        return str_replace('/', DIRECTORY_SEPARATOR, implode('/', $out));
    }

    public static function isEditableFile(string $absolutePath): bool
    {
        if (!is_file($absolutePath)) {
            return false;
        }
        $ext = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));
        return in_array($ext, self::ALLOWED_EXTENSIONS, true);
    }

    public static function aceModeForFile(string $absolutePath): string
    {
        $ext = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));
        return match ($ext) {
            'css' => 'css',
            'js' => 'javascript',
            default => 'text',
        };
    }

    /**
     * List immediate .css/.js files and subdirectories inside $absoluteDir.
     *
     * @return array{dirs: array<int, string>, files: array<int, string>}
     */
    public static function listDir(string $absoluteDir): array
    {
        $dirs = [];
        $files = [];
        if (!is_dir($absoluteDir)) {
            return ['dirs' => $dirs, 'files' => $files];
        }
        $entries = scandir($absoluteDir);
        if ($entries === false) {
            return ['dirs' => $dirs, 'files' => $files];
        }
        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $full = $absoluteDir . DIRECTORY_SEPARATOR . $entry;
            if (is_dir($full)) {
                $dirs[] = $entry;
            } elseif (is_file($full)) {
                $ext = strtolower(pathinfo($entry, PATHINFO_EXTENSION));
                if (in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
                    $files[] = $entry;
                }
            }
        }
        sort($dirs, SORT_NATURAL | SORT_FLAG_CASE);
        sort($files, SORT_NATURAL | SORT_FLAG_CASE);
        return ['dirs' => $dirs, 'files' => $files];
    }
}
