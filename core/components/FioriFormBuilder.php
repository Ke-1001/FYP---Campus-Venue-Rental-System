<?php

// This section provides the shared FioriFormBuilder component.
namespace Core\Components;

class FioriFormBuilder
{
    private const LABEL_CSS = 'col-span-1 text-sm text-slate-500 font-bold';
    private const INPUT_CSS = 'fiori-input focus:border-[#004aad] w-full';
    private const READONLY_CSS = 'fiori-input fiori-readonly font-mono uppercase text-[#004aad] font-bold cursor-not-allowed bg-slate-50';

    private static function renderRow(string $label, string $controlHtml): string
    {
        return "
        <div class=\"grid grid-cols-3 gap-4 items-center\">
            <label class=\"" . self::LABEL_CSS . "\">{$label}</label>
            <div class=\"col-span-2 relative\">
{$controlHtml}
            </div>
        </div>";
    }

    public static function input(string $type, string $name, string $label, ?string $value, array $attrs = []): string
    {
        $attrStr = self::buildAttributes($attrs);
        $css = ($attrs['readonly'] ?? false) ? self::READONLY_CSS : self::INPUT_CSS . ' ' . ($attrs['extra_css'] ?? '');
        $val = htmlspecialchars((string)$value);

        $control = "<input type=\"{$type}\" name=\"{$name}\" value=\"{$val}\" class=\"{$css}\" {$attrStr}>";

        if (isset($attrs['prefix']))
        {
            $control = "<span class=\"absolute left-3 top-2.5 text-xs text-slate-400 font-bold\">{$attrs['prefix']}</span>" . str_replace('fiori-input', 'fiori-input pl-10', $control);
        }
        if (isset($attrs['suffix']))
        {
            $control .= "<span class=\"absolute right-3 top-2.5 text-xs text-slate-400 font-bold\">{$attrs['suffix']}</span>";
            $control = str_replace('fiori-input', 'fiori-input pr-12 font-mono', $control);
        }

        return self::renderRow($label, $control);
    }

    public static function select(string $name, string $label, array $options, ?string $selectedValue, array $attrs = []): string
    {
        $attrStr = self::buildAttributes($attrs);
        $css = self::INPUT_CSS . ' appearance-none pr-8 bg-white cursor-pointer transition-colors ' . ($attrs['extra_css'] ?? '');

        $control = "<select name=\"{$name}\" class=\"{$css}\" {$attrStr}>";
        if (!empty($attrs['placeholder']))
        {
            $control .= "<option value=\"\" disabled " . ($selectedValue === null ? 'selected' : '') . ">{$attrs['placeholder']}</option>";
        }

        foreach ($options as $val => $text)
        {
            $sel = ((string)$selectedValue === (string)$val) ? 'selected' : '';
            $control .= "<option value=\"" . htmlspecialchars($val) . "\" {$sel}>" . htmlspecialchars($text) . "</option>";
        }
        $control .= "</select>
        <i data-lucide=\"chevron-down\" class=\"w-4 h-4 text-slate-400 absolute right-2 top-2.5 pointer-events-none\"></i>";

        return self::renderRow($label, $control);
    }

    private static function buildAttributes(array $attrs): string
    {
        $compiled = [];
        $booleanAttrs = ['required', 'readonly', 'disabled'];
        foreach ($attrs as $key => $val)
        {
            if (in_array($key, ['prefix', 'suffix', 'extra_css', 'placeholder'])) continue; if (in_array($key, $booleanAttrs))
            {
                if ($val) $compiled[] = $key; } else
                {
                $compiled[] = "{$key}=\"" . htmlspecialchars((string)$val) . "\"";
            }
        }
        return implode(' ', $compiled);
    }

    public static function textarea(string $name, string $label, ?string $value, array $attrs = []): string
    {
        $attrStr = self::buildAttributes($attrs);
        $css = self::INPUT_CSS . ' resize-none p-3 ' . ($attrs['extra_css'] ?? '');
        $val = htmlspecialchars((string)$value);
        $rows = $attrs['rows'] ?? 3;

        $control = "<textarea name=\"{$name}\" rows=\"{$rows}\" class=\"{$css}\" {$attrStr}>{$val}</textarea>";
        return self::renderRow($label, $control);
    }
}
?>
