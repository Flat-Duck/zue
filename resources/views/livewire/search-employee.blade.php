<div>
    <div class="input-group">
        <input type="text" wire:model.live="number" wire:keydown.enter="searchEmployees" class="form-control" placeholder="@lang('ui.employee_number')">
        <button class="btn" wire:click="searchEmployees" type="button">@lang('ui.search')</button>
    </div>
</div>