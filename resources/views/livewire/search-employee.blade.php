<div>
    <div class="input-group">
        <input type="text" wire:model="number" wire:keydown.enter="searchEmployees" class="form-control" placeholder="Employee Number">
        <button class="btn" wire:click="searchEmployees" type="button">Search</button>
    </div>
</div>