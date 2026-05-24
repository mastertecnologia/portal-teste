<?php
declare(strict_types=1);

namespace App\Utility;

use Cake\Core\Configure;

/**
 * Mapeamento pg-* (mock) ↔ rotas legado / protótipo Cake.
 */
class PortalUiScreenMap {

    /** @return array<string,array<string,mixed>> */
    public static function screens(): array {
        $map = Configure::read('PortalUiScreens.screens');
        if (!is_array($map)) {
            return [];
        }

        return $map;
    }

    /** @return array<string,array<string,mixed>> */
    public static function switchoverModules(): array {
        $map = Configure::read('PortalUiScreens.switchover');
        if (!is_array($map)) {
            return [];
        }

        return $map;
    }

    public static function screen(string $pgId): ?array {
        $pgId = strtolower(trim($pgId));
        $screens = self::screens();

        return isset($screens[$pgId]) && is_array($screens[$pgId]) ? $screens[$pgId] : null;
    }

    /**
     * IDs pg-* presentes no HTML de referência (quando o ficheiro existe no repo).
     *
     * @return list<string>
     */
    public static function referencePgIds(): array {
        $path = self::referenceHtmlPath();
        if ($path === null) {
            return [];
        }
        $html = file_get_contents($path);
        if ($html === false) {
            return [];
        }
        if (!preg_match_all('/\bid=["\']?(pg-[a-z0-9-]+)["\']?/i', $html, $m)) {
            return [];
        }
        $ids = array_values(array_unique($m[1] ?? []));
        sort($ids);

        return $ids;
    }

    public static function referenceHtmlPath(): ?string {
        $primary = (string)Configure::read('PortalUi.reference_html.primary', '');
        $fallback = (string)Configure::read('PortalUi.reference_html.fallback', '');
        $root = dirname(__DIR__, 2);
        foreach ([$primary, $fallback, 'docs/referencias/pgm_erp_completo_2.html'] as $rel) {
            if ($rel === '') {
                continue;
            }
            $path = strpos($rel, '/') === 0 ? $rel : $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
            if (is_file($path) && is_readable($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Estatísticas para auditoria / relatório.
     *
     * @return array{reference_total:int,mapped_total:int,with_prototype:int,partial:int,legacy_only:int,unmapped:int}
     */
    public static function coverageStats(): array {
        $ref = self::referencePgIds();
        $screens = self::screens();
        $mapped = 0;
        $withProto = 0;
        $partial = 0;
        $legacyOnly = 0;
        foreach ($ref as $id) {
            if (!isset($screens[$id])) {
                continue;
            }
            $mapped++;
            $row = $screens[$id];
            if (!empty($row['prototype'])) {
                $withProto++;
            }
            $parity = (string)($row['parity'] ?? '');
            if ($parity === 'partial') {
                $partial++;
            } elseif ($parity === 'legacy_only' || empty($row['prototype'])) {
                $legacyOnly++;
            }
        }

        return [
            'reference_total' => count($ref),
            'mapped_total' => count($screens),
            'with_prototype' => $withProto,
            'partial' => $partial,
            'legacy_only' => $legacyOnly,
            'unmapped' => max(0, count($ref) - $mapped),
        ];
    }
}
