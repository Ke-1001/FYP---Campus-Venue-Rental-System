<?php
// This section provides the shared datagrid component.
function render_datagrid(array $schema, $dataset)
{
    $html = '<table class="custom-table w-full">';


    $html .= '<thead class="sticky top-0 z-10 bg-white"><tr>';
    if (!empty($schema['enable_checkbox']))
    {
        $html .= '<th class="w-12 text-center"><input type="checkbox" id="selectAll" onclick="toggleAll(this)" class="w-4 h-4 rounded border-slate-300 text-[#004aad] focus:ring-[#004aad]"></th>';
    }
    foreach ($schema['columns'] as $col)
    {
        $width = isset($col['width']) ? 'class="' . $col['width'] . '"' : '';
        $html .= "<th {$width}>" . htmlspecialchars($col['label']) . "</th>";
    }
    $html .= '</tr></thead><tbody>';


    $has_data = false;
    if ($dataset instanceof mysqli_result)
    {
        $has_data = $dataset->num_rows > 0;
        $rows = $dataset;
    } elseif (is_array($dataset))
    {
        $has_data = count($dataset) > 0;
        $rows = $dataset;
    }

    if ($has_data)
    {
        foreach ($rows as $row)
        {
            $html .= '<tr>';
            $row_attr = '';
    if (!empty($schema['row_action_url']))
    {
        $url = str_replace('%s', $row[$schema['primary_key']], $schema['row_action_url']);
        $row_attr = 'ondblclick="window.location.href=\''.htmlspecialchars($url).'\'" class="hover:bg-slate-50 transition-colors cursor-pointer"';
    }
    $html .= '<tr '.$row_attr.'>';


            if (!empty($schema['enable_checkbox']))
            {
                $primary_key = $schema['primary_key'] ?? 'id';
                $html .= '<td class="text-center"><input type="checkbox" name="'.$schema['checkbox_name'].'[]" value="'.$row[$primary_key].'" onclick="updateButtonStates()" class="row-cb w-4 h-4 rounded border-slate-300 text-[#004aad] focus:ring-[#004aad]"></td>';
            }


            foreach ($schema['columns'] as $col)
            {
                $html .= '<td>';


                $col_key = $col['key'] ?? null;
                $val = ($col_key !== null && isset($row[$col_key])) ? $row[$col_key] : '';

                switch ($col['type'])
                {
                    case 'text':
                        $html .= '<span class="font-semibold text-slate-700 text-sm">' . htmlspecialchars($val) . '</span>';
                        break;
                    case 'text_bold':
                        $html .= '<span class="font-semibold text-slate-800 text-sm block">' . htmlspecialchars($val) . '</span>';
                        break;
                    case 'link':
                        $url = str_replace('%s', urlencode($val), $col['url_format']);
                        $html .= '<a href="' . htmlspecialchars($url) . '" class="td-text-mono">' . htmlspecialchars($val) . '</a>';
                        break;
                    case 'badge':
                        $html .= '<span class="px-2 py-0.5 bg-[#f1f5f9] text-slate-600 text-[10px] font-bold uppercase tracking-wider rounded-sm border border-[#e2e8f0] inline-block">' . htmlspecialchars($val) . '</span>';
                        break;
                    case 'time_range':

                        $start = isset($row[$col['start_key']]) ? date('H:i', strtotime($row[$col['start_key']])) : 'N/A';
                        $end = isset($row[$col['end_key']]) ? date('H:i', strtotime($row[$col['end_key']])) : 'N/A';

                        $html .= '<div class="td-text-mono flex flex-col space-y-1 text-xs text-slate-700 font-semibold">';

                        $html .= '  <div class="flex items-center">';
                        $html .= '    <span class="text-slate-400 font-normal uppercase tracking-wider text-[10px] w-12 block shrink-0">Start</span>';
                        $html .= '    <span class="font-mono text-slate-800">' . $start . '</span>';
                        $html .= '  </div>';
                        $html .= '  <div class="flex items-center">';
                        $html .= '    <span class="text-slate-400 font-normal uppercase tracking-wider text-[10px] w-12 block shrink-0">End</span>';
                        $html .= '    <span class="font-mono text-slate-800">' . $end . '</span>';
                        $html .= '  </div>';
                        $html .= '</div>';
                        break;
                    case 'boolean_badge':
                        $is_true = (bool)$val;
                        $label = $is_true ? $col['true_label'] : $col['false_label'];
                        $css = $is_true ? $col['true_class'] : $col['false_class'];
                        $html .= '<span class="inline-flex items-center px-2.5 py-0.5 rounded-sm text-[10px] font-bold uppercase ' . $css . '">' . htmlspecialchars($label) . '</span>';
                        break;
                    case 'text_muted_mono':
                        $prefix = $col['prefix'] ?? '';
                        $html .= '<span class="td-text-mono text-slate-400">' . htmlspecialchars($prefix . $val) . '</span>';
                        break;
                    case 'text_mono':
                        $html .= '<span class="td-text-mono font-mono text-slate-600">' . htmlspecialchars($val) . '</span>';
                        break;
                    case 'map_badge':
                        $map = $col['map'][$val] ?? $col['default_map'] ?? ['label' => 'UNKNOWN', 'class' => 'bg-slate-50 text-slate-500'];
                        $html .= '<span class="px-2.5 py-1 rounded text-[10px] font-black uppercase tracking-widest border ' . $map['class'] . '">' . htmlspecialchars($map['label']) . '</span>';
                        break;
                    case 'suffix_text':
                        $suffix = $col['suffix'] ?? '';
                        $html .= '<span class="td-text-mono font-mono text-slate-700 font-bold">' . htmlspecialchars($val) . ' <span class="text-[9px] uppercase tracking-widest text-slate-400 ml-1">' . htmlspecialchars($suffix) . '</span></span>';
                        break;
                    case 'currency':
                        $prefix = $col['prefix'] ?? '';
                        $formatted = number_format((float)$val, 2);
                        $html .= '<span class="td-text-mono font-mono text-emerald-600 font-bold">' . htmlspecialchars($prefix . $formatted) . '</span>';
                        break;
                }
                $html .= '</td>';
            }
            $html .= '</tr>';
        }
    } else
    {
        $colspan = count($schema['columns']) + (!empty($schema['enable_checkbox']) ? 1 : 0);
        $html .= '<tr><td colspan="'.$colspan.'" class="py-16 text-center text-slate-500 border-none hover:bg-transparent cursor-default"><span class="text-sm font-medium tracking-wide">No records found.</span></td></tr>';
    }

    $html .= '</tbody></table>';
    return $html;
}
?>
