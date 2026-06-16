<?php
// This section provides the shared datagrid controller component.
$edit_url_base = $controller_config['edit_url_base'] ?? '';
$delete_entity_name = $controller_config['delete_entity_name'] ?? 'record';
?>
<div id="custom-delete-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 backdrop-blur-sm opacity-0 transition-opacity duration-200">
    <div id="custom-modal-panel" class="bg-white rounded-md shadow-lg w-full max-w-sm p-6 transform scale-95 transition-transform duration-200 border border-slate-200">
        <h3 class="text-base font-bold text-slate-900 mb-2">System Confirmation</h3>
        <p class="text-sm text-slate-600 mb-6" id="custom-modal-msg">Are you sure you want to proceed?</p>
        <div class="flex justify-end space-x-3">
            <button type="button" onclick="closeCustomModal()" class="px-4 py-2 text-xs font-semibold text-slate-600 bg-transparent hover:bg-slate-100 rounded-md transition-colors">Cancel</button>
            <button type="button" id="custom-modal-confirm-btn" class="px-4 py-2 text-xs font-semibold text-white bg-red-600 hover:bg-red-700 rounded-md shadow-sm transition-colors border border-red-600">Proceed</button>
        </div>
    </div>
</div>
<script>
    const DATAGRID_CONFIG =
    {
        editUrlBase: "<?php echo addslashes($edit_url_base); ?>",
        entityName: "<?php echo addslashes($delete_entity_name); ?>"
    }
;
    const toggleAll = (source) =>
    {
        document.querySelectorAll('.row-cb').forEach(cb =>
        {
            cb.checked = source.checked;
        }
);
        updateButtonStates();
    }
;
    const updateButtonStates = () =>
    {
        const selectedCount = document.querySelectorAll('.row-cb:checked').length;
        const btnEdit = document.getElementById('btn-edit');
        const btnDelete = document.getElementById('btn-delete');
        const cbCounter = document.getElementById('cb-counter');
        if (cbCounter) cbCounter.innerText = selectedCount;  const isSingle = selectedCount === 1; const isMultiple = selectedCount > 0;  if (btnEdit)
        {
            btnEdit.disabled = !isSingle;
            btnEdit.className = isSingle
                ? "px-4 py-2 text-xs font-semibold text-slate-700 bg-white hover:bg-slate-50 border border-slate-300 rounded-md shadow-sm transition cursor-pointer"
                : "px-4 py-2 text-xs font-semibold text-slate-400 bg-slate-100 rounded-md transition cursor-not-allowed border border-slate-200";
        }
        if (btnDelete)
        {
            btnDelete.disabled = !isMultiple;
            btnDelete.className = isMultiple
                ? "px-4 py-2 text-xs font-semibold text-[#dc2626] bg-white hover:bg-red-50 border border-red-200 rounded-md shadow-sm transition cursor-pointer"
                : "px-4 py-2 text-xs font-semibold text-slate-400 bg-slate-100 rounded-md transition cursor-not-allowed border border-slate-200";
        }
    }
;
    const executeAction = (type) =>
    {
        const selected = document.querySelectorAll('.row-cb:checked');
        if (selected.length === 0) return;  if (type === 'edit' && selected.length === 1)
        {
            window.location.href = DATAGRID_CONFIG.editUrlBase + encodeURIComponent(selected[0].value);
        }
        else if (type === 'delete')
        {
            document.getElementById('custom-modal-msg').innerText = `Are you sure you want to permanently delete ${selected.length} ${DATAGRID_CONFIG.entityName}(s)? This action cannot be undone.`;
            openCustomModal();
        }
    }
;
    const openCustomModal = () =>
    {
        const modal = document.getElementById('custom-delete-modal');
        const panel = document.getElementById('custom-modal-panel');
        modal.classList.remove('hidden');
        void modal.offsetWidth;
        modal.classList.remove('opacity-0');
        panel.classList.remove('scale-95');
    }
;
    const closeCustomModal = () =>
    {
        const modal = document.getElementById('custom-delete-modal');
        const panel = document.getElementById('custom-modal-panel');
        modal.classList.add('opacity-0');
        panel.classList.add('scale-95');
        setTimeout(() =>
        {
            modal.classList.add('hidden');
        }
, 200);
    }
;
    const confirmBtn = document.getElementById('custom-modal-confirm-btn');
    if (confirmBtn)
    {
        confirmBtn.addEventListener('click', function()
        {
            const form = document.getElementById('bulkActionForm');
            const actionType = document.getElementById('bulk_action_type');
            if (form && actionType)
            {
                actionType.value = 'bulk_delete';
                this.innerHTML = 'Executing...';
                this.disabled = true;
                form.submit();
            }
        }
);
    }
</script>
