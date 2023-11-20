<div class="mb-3">
    <div class="input-group mb-2">
        <input type="text" wire:model="number" wire:keydown.enter="searchEmployees" class="form-control" placeholder="Employee Number">
        <button class="btn" wire:click="searchEmployees" type="button">Go!</button>
    </div>
</div>