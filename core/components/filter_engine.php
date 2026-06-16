<?php
// File: core/components/filter_engine.php

function render_filter_engine(array $schema) {
    $theme = $schema['theme'] ?? [];
    $btn_primary = ($theme['btn_primary_bg'] ?? '') . ' ' . ($theme['btn_primary_text'] ?? '');
    $label_class = $theme['label_text'] ?? '';
    
    $action_url = htmlspecialchars($schema['action'] ?? '');
    $form_id = htmlspecialchars($schema['id'] ?? 'filterForm');
    
    $html = '<div class="mb-4 bg-white border border-slate-200 rounded-md p-3 shadow-[0_1px_2px_0_rgba(0,0,0,0.05)] shrink-0">';
    $html .= '<form method="GET" action="' . $action_url . '" id="' . $form_id . '" class="flex flex-wrap items-center gap-4">';

    foreach ($schema['fields'] as $field) {
        $html .= '<div class="flex items-center space-x-2">';
        $html .= '<label class="' . htmlspecialchars($label_class) . '">' . htmlspecialchars($field['label']) . ':</label>';
        
        $name = htmlspecialchars($field['name']);
        $value = htmlspecialchars($field['value'] ?? '');
        $placeholder = htmlspecialchars($field['placeholder'] ?? '');
        $width = $field['width'] ?? 'w-40';

 // Render DOM based on filter type
        switch ($field['type']) {
            case 'text':
                $html .= '<input type="text" name="'.$name.'" value="'.$value.'" placeholder="'.$placeholder.'" class="border border-slate-200 rounded px-3 py-1.5 text-xs font-semibold focus:outline-none focus:border-[#004aad] '.$width.' bg-white">';
                break;

            case 'select':
                $auto_submit = !empty($field['auto_submit']) ? 'onchange="this.form.submit()"' : '';
                $html .= '<select name="'.$name.'" '.$auto_submit.' class="border border-slate-200 rounded px-3 py-1.5 text-xs font-bold text-slate-700 focus:outline-none focus:border-[#004aad] cursor-pointer bg-white '.$width.'">';
                if (!empty($field['placeholder'])) {
                    $html .= '<option value="">' . $placeholder . '</option>';
                }
                foreach ($field['options'] as $opt_val => $opt_label) {
                    $selected = ((string)$value === (string)$opt_val) ? 'selected' : '';
                    $html .= '<option value="'.htmlspecialchars($opt_val).'" '.$selected.'>'.htmlspecialchars($opt_label).'</option>';
                }
                $html .= '</select>';
                break;

            case 'datalist':
                $list_id = 'list_' . $name;
                $html .= '<input list="'.$list_id.'" name="'.$name.'" value="'.$value.'" oninput="this.value = this.value.toUpperCase()" placeholder="'.$placeholder.'" class="border border-slate-200 rounded px-3 py-1.5 text-xs font-bold text-[#004aad] focus:outline-none focus:border-[#004aad] '.$width.' bg-white">';
                $html .= '<datalist id="'.$list_id.'">';
                foreach ($field['options'] as $opt_val) {
                    $html .= '<option value="' . htmlspecialchars($opt_val) . '"></option>';
                }
                $html .= '</datalist>';
                break;
        }
        $html .= '</div>';
    }

 // Render action buttons (Action Button Matrix)
    $html .= '<div class="flex space-x-2 ml-auto">';
    if (!empty($schema['show_submit_btn'])) {
        $html .= '<button type="submit" class="px-4 py-1.5 rounded transition shadow-sm ' . htmlspecialchars($btn_primary) . '">Filter</button>';
    }
    $html .= '<a href="' . $action_url . '" class="px-3 py-1.5 bg-transparent text-slate-500 text-xs font-bold rounded hover:bg-slate-100 transition border border-transparent flex items-center">Reset</a>';
    $html .= '</div>';

    $html .= '</form></div>';
    return $html;
}
?>