<?php
// File: core/components/DataGridBuilder.php

namespace Core\Components;

class DataGridBuilder {
    private array $schema;
    private string $process_action_url;
    private string $entity_name;
    private array $action_buttons = [];

    public function __construct(string $primary_key, string $process_action_url, string $entity_name = 'record') {
        $this->schema['primary_key'] = $primary_key;
        $this->schema['enable_checkbox'] = true;
        $this->schema['checkbox_name'] = 'selected_vids';
        $this->schema['columns'] = [];
        $this->process_action_url = $process_action_url;
        $this->entity_name = $entity_name;
    }

    public function addColumn(string $key, string $label, string $type, array $options = []): self {
        $this->schema['columns'][] = array_merge(['key' => $key, 'label' => $label, 'type' => $type], $options);
        return $this;
    }

    public function setCreateAction(string $url, string $label = 'Create'): self {
        $this->action_buttons['create'] = ['url' => $url, 'label' => $label];
        return $this;
    }

    public function setRowActionUrl(string $url_format): self {
        $this->schema['row_action_url'] = $url_format;
        return $this;
    }

    public function render($result): string {
        // ∴ 嚴格引入底層渲染引擎以防 Fatal Error，並呼叫全局函式 \render_datagrid
        require_once __DIR__ . '/datagrid.php';

        $create_url = $this->action_buttons['create']['url'] ?? '#';
        $create_label = $this->action_buttons['create']['label'] ?? 'Create';
        $ent_name = htmlspecialchars($this->entity_name);

        $html = '
        <div class="mb-4 bg-white p-3 rounded-md border border-slate-200 shadow-[0_1px_2px_0_rgba(0,0,0,0.05)] shrink-0 flex justify-between items-center">
            <div class="text-xs font-bold text-slate-500 pl-2">
                <span id="cb-counter">0</span> selected
            </div>
            <div class="flex space-x-2">
                <button onclick="window.location.href=\''.htmlspecialchars($create_url).'\'" class="px-4 py-2 text-xs font-semibold text-white bg-[#004aad] hover:bg-[#003882] rounded-md shadow-sm transition border border-[#004aad]">
                    <i data-lucide="plus" class="w-3.5 h-3.5 inline mr-1"></i> '.htmlspecialchars($create_label).'
                </button>
                <button id="btn-edit" disabled onclick="executeAction(\'edit\')" class="px-4 py-2 text-xs font-semibold text-slate-400 bg-slate-100 rounded-md transition cursor-not-allowed border border-slate-200">
                    <i data-lucide="edit-3" class="w-3.5 h-3.5 inline mr-1"></i> Edit
                </button>
                <button id="btn-delete" disabled onclick="executeAction(\'delete\')" class="px-4 py-2 text-xs font-semibold text-slate-400 bg-slate-100 rounded-md transition cursor-not-allowed border border-slate-200">
                    <i data-lucide="trash-2" class="w-3.5 h-3.5 inline mr-1"></i> Delete
                </button>
            </div>
        </div>

        <form id="bulkActionForm" action="'.htmlspecialchars($this->process_action_url).'" method="POST" class="flex-1 overflow-hidden flex flex-col custom-table-container">
            <input type="hidden" name="action" id="bulk_action_type" value="">
            <div class="flex-1 overflow-y-auto">
                ' . \render_datagrid($this->schema, $result) . '
            </div>
        </form>

        <div id="custom-delete-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 backdrop-blur-sm opacity-0 transition-opacity duration-200">
            <div id="custom-modal-panel" class="bg-white rounded-md shadow-lg w-full max-w-sm p-6 transform scale-95 transition-transform duration-200 border border-slate-200">
                <h3 class="text-base font-bold text-slate-900 mb-2">Delete Asset</h3>
                <p class="text-sm text-slate-600 mb-6" id="custom-modal-msg">Are you sure you want to delete the selected '.$ent_name.'(s)?</p>
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeCustomModal()" class="px-4 py-2 text-xs font-semibold text-slate-600 bg-transparent hover:bg-slate-100 rounded-md transition-colors">Cancel</button>
                    <button type="button" id="custom-modal-confirm-btn" class="px-4 py-2 text-xs font-semibold text-white bg-red-600 hover:bg-red-700 rounded-md shadow-sm transition-colors border border-red-600">Delete</button>
                </div>
            </div>
        </div>
        ';
        return $html;
    }
}
?>