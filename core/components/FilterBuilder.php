<?php
// File: core/components/FilterBuilder.php

namespace Core\Components;

class FilterBuilder {
    private array $schema;

    /**
     * 初始化過濾器配置
     */
    public function __construct(string $action_url, bool $show_submit_btn = true, string $form_id = 'filterForm') {
        $this->schema = [
            'id' => $form_id,
            'action' => $action_url,
            'show_submit_btn' => $show_submit_btn,
            'fields' => []
        ];
    }

    /**
     * 附加文字輸入框
     */
    public function addText(string $name, string $label, string $value = '', string $placeholder = '', string $width = 'w-40'): self {
        $this->schema['fields'][] = [
            'type' => 'text',
            'name' => $name,
            'label' => $label,
            'value' => $value,
            'placeholder' => $placeholder,
            'width' => $width
        ];
        return $this; // 支援鏈式調用 (Method Chaining)
    }

    /**
     * 附加下拉式選單
     */
    public function addSelect(string $name, string $label, array $options, string $value = '', string $placeholder = '', bool $auto_submit = false, string $width = 'w-40'): self {
        $this->schema['fields'][] = [
            'type' => 'select',
            'name' => $name,
            'label' => $label,
            'options' => $options,
            'value' => $value,
            'placeholder' => $placeholder,
            'auto_submit' => $auto_submit,
            'width' => $width
        ];
        return $this;
    }

    /**
     * 附加 Datalist 數據列表
     */
    public function addDatalist(string $name, string $label, array $options, string $value = '', string $placeholder = '', string $width = 'w-40'): self {
        $this->schema['fields'][] = [
            'type' => 'datalist',
            'name' => $name,
            'label' => $label,
            'options' => $options,
            'value' => $value,
            'placeholder' => $placeholder,
            'width' => $width
        ];
        return $this;
    }

    /**
     * 輸出最終編譯之 Schema
     */
    public function build(): array {
        return $this->schema;
    }
}
?>