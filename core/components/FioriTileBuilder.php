<?php
// File: core/components/FioriTileBuilder.php

namespace Core\Components;

class FioriTileBuilder {
    
    /**
 * Render one Launchpad section (Render Launchpad Section)
 * @param string $title section title
 * @param string $description section description
 * @param array $tiles tile config array
     */
    public static function renderSection(string $title, string $description, array $tiles): string {
        $html = '
        <div class="mt-8 mb-6 border-b border-slate-200 pb-4">
            <h1 class="text-2xl font-extrabold text-slate-800 tracking-tight">' . htmlspecialchars($title) . '</h1>
            <p class="text-sm text-slate-500 mt-1">' . htmlspecialchars($description) . '</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-10">';

        foreach ($tiles as $tile) {
            $html .= self::renderTile($tile);
        }

        $html .= '</div>';
        return $html;
    }

    /**
 * Render one tile (Render Discrete Tile)
     */
    private static function renderTile(array $t): string {
        $url = htmlspecialchars($t['url'] ?? '#');
        $title = htmlspecialchars($t['title']);
        $icon = htmlspecialchars($t['icon']);
        $desc = htmlspecialchars($t['desc']);
 $kpi = $t['kpi'] ?? ''; // KPI can be a number or icon (HTML)
        $action = htmlspecialchars($t['action'] ?? 'View Records');

        return "
        <a href=\"{$url}\" class=\"fiori-tile group\">
            <div class=\"fiori-tile-header\">
                <h3 class=\"fiori-tile-title\">{$title}</h3>
                <i data-lucide=\"{$icon}\" class=\"w-5 h-5 fiori-tile-icon transition-colors group-hover:text-white\"></i>
            </div>
            <p class=\"fiori-tile-desc\">{$desc}</p>
            <div class=\"fiori-tile-kpi\">
                {$kpi}
            </div>
            <div class=\"fiori-tile-footer\">
                {$action} <i data-lucide=\"arrow-right\" class=\"w-3 h-3 ml-2\"></i>
            </div>
        </a>";
    }
}
?>