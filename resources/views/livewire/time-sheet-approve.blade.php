<div>
    @if ($level == 1)
        <a type="submit" class="btn btn-primary" wire:confirm="ok?" wire:click="approveAsTimekeeper">
            <i class="ti ti-check"></i> @lang('crud.time_sheets.time_keeper_approve')
        </a>
    @elseif ($level == 2)
        <a type="submit" class="btn btn-success" wire:confirm="ok?" wire:click="approveAsSupervisor">
            <i class="ti ti-check"></i> @lang('crud.time_sheets.supervisor_approve')
        </a>
    @elseif ($level == 3)
        <a type="submit" class="btn btn-warning"wire:confirm="ok?" wire:click="approveAsSuperintendent">
            <i class="ti ti-check"></i> @lang('crud.time_sheets.superintendent_approve')
        </a>
    @endif
</div>
