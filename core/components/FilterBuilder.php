<?php
// This section provides the shared FilterBuilder component.
namespace Core\Components;
use mysqli;
class FilterBuilder
{
    private array $fields = [];
    private string $action_url;
    private string $form_id;
    private bool $show_submit_btn;
    private array $layout_config = [
        'container_class' => 'mb-6 bg-white border border-slate-200 rounded-lg p-4 shadow-sm shrink-0',
        'form_class'      => 'grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 items-end',
        'label_class'     => 'block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5',
        'input_class'     => 'w-full border border-slate-200 rounded-md px-3 py-2 text-xs font-semibold focus:outline-none focus:border-[#004aad] transition-colors bg-slate-50/50 focus:bg-white',
        'select_class'    => 'w-full border border-slate-200 rounded-md px-3 py-2 text-xs font-bold text-slate-700 focus:outline-none focus:border-[#004aad] cursor-pointer bg-slate-50/50 transition-colors',
        'btn_container'   => 'col-span-full flex space-x-2 justify-end mt-2',
        'btn_submit'      => 'px-5 py-2 bg-slate-900 text-white text-xs font-bold rounded-md hover:bg-slate-800 transition shadow-sm h-[36px]',
        'btn_reset'       => 'px-4 py-2 bg-transparent text-slate-500 text-xs font-bold rounded-md hover:bg-slate-100 transition border border-transparent flex items-center h-[36px]'
    ];
    public function __construct(string $action_url, bool $show_submit_btn = true, string $form_id = 'filterForm')
    {
        $this->action_url = $action_url;
        $this->show_submit_btn = $show_submit_btn;
        $this->form_id = $form_id;
    }
    public function addField(string $type, string $name, string $label, array $options = [], string $placeholder = '', string $db_column = '', string $operator = '=', bool $auto_submit = false): self
    {
        $value = isset($_GET[$name]) ? trim($_GET[$name]) : '';
        $this->fields[] = [
            'type' => $type, 'name' => $name, 'label' => $label,
            'options' => $options, 'placeholder' => $placeholder,
            'db_column' => $db_column, 'operator' => $operator,
            'value' => $value, 'auto_submit' => $auto_submit
        ];
        return $this;
    }
    public function buildSqlWhere(mysqli $conn): string
    {
        $sql = "";
        foreach ($this->fields as $field)
        {
            if ($field['value'] !== '' && !empty($field['db_column']))
            {
                $sanitized = $conn->real_escape_string($field['value']);
                $col = $field['db_column'];
                switch (strtoupper($field['operator']))
                {
                    case 'LIKE': $sql .= " AND {$col} LIKE '%{$sanitized}%'"; break;
                    case 'LIKE_UPPER': $sql .= " AND UPPER({$col}) LIKE UPPER('%{$sanitized}%')"; break;
                    case '=': default: $sql .= " AND {$col} = '{$sanitized}'"; break;
                }
            }
        }
        return $sql;
    }
    public function render(): string
    {
        $html = '<div class="' . $this->layout_config['container_class'] . '">';
        $html .= '<form method="GET" action="' . htmlspecialchars($this->action_url) . '" id="' . htmlspecialchars($this->form_id) . '" class="' . $this->layout_config['form_class'] . '">';
        foreach ($this->fields as $field)
        {
            $name = htmlspecialchars($field['name']);
            $value = htmlspecialchars($field['value']);
            $placeholder = htmlspecialchars($field['placeholder']);
            $html .= '<div class="flex flex-col">';
            $html .= '<label class="' . $this->layout_config['label_class'] . '">' . htmlspecialchars($field['label']) . '</label>';
            switch ($field['type'])
            {
                case 'text':
 case 'date':
 case 'number':
                    $html .= '<input type="'.$field['type'].'" name="'.$name.'" value="'.$value.'" placeholder="'.$placeholder.'" class="'.$this->layout_config['input_class'].'">';
                    break;
                case 'select':
                    $auto_attr = $field['auto_submit'] ? 'onchange="this.form.submit()"' : '';
                    $html .= '<select name="'.$name.'" '.$auto_attr.' class="'.$this->layout_config['select_class'].'">';
                    if (!empty($field['placeholder']))
                    {
                        $html .= '<option value="">' . $placeholder . '</option>';
                    }
                    foreach ($field['options'] as $opt_val => $opt_label)
                    {
                        $selected = ((string)$value === (string)$opt_val) ? 'selected' : '';
                        $html .= '<option value="'.htmlspecialchars($opt_val).'" '.$selected.'>'.htmlspecialchars($opt_label).'</option>';
                    }
                    $html .= '</select>';
                    break;
                case 'datalist':
                    $list_id = 'list_' . $name;
                    $html .= '<input list="'.$list_id.'" name="'.$name.'" value="'.$value.'" oninput="this.value = this.value.toUpperCase()" placeholder="'.$placeholder.'" class="'.$this->layout_config['input_class'].'">';
                    $html .= '<datalist id="'.$list_id.'">';
                    foreach ($field['options'] as $opt_val)
                    {
                        $html .= '<option value="' . htmlspecialchars($opt_val) . '"></option>';
                    }
                    $html .= '</datalist>';
                    break;
            }
            $html .= '</div>';
        }
        $html .= '<div class="' . $this->layout_config['btn_container'] . '">';
        if ($this->show_submit_btn)
        {
            $html .= '<button type="submit" class="' . $this->layout_config['btn_submit'] . '">Filter</button>';
        }
        $html .= '<a href="' . htmlspecialchars($this->action_url) . '" class="' . $this->layout_config['btn_reset'] . '">Reset</a>';
        $html .= '</div>';
        $html .= '</form></div>';
        return $html;
    }
}
?>
