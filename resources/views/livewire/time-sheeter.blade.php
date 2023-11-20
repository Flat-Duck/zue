@props(['options' => "{dateFormat:'Y-m-d', altFormat:'F j, Y', altInput:true, }"])
<div>
    <div class="row g-5">
        <div class="col-6 mb-3">
            <div class="row row-cards">
                <div class="col-2 mt-3">
                    <div class="mb-3">
                        <label class="form-label">ZOC No :</label>
                        <input value="{{ $employee->number}}" type="text" class="form-control" disabled >
                    </div>
                </div>
                <div class="col-6 mt-3">
                    <div class="mb-3">
                        <label class="form-label">Full Name :</label>
                        <input value="{{ $employee->english_name}}" type="text" class="form-control" disabled >
                    </div>
                </div>
                <div class="col-4 mt-3">
                    <div class="mb-3">
                        <label class="form-label">Start Date :</label>
                        <input value="{{ $employee->id_card_issue_date}}" type="text" class="form-control" disabled >
                    </div>
                </div>
                <div class="col-3 mt-3">
                    <div class="mb-3">
                        <label class="form-label">Administration :</label>
                        <input value="{{ $employee->name }}" type="text" class="form-control" disabled >
                    </div>
                </div>
                <div class="col-3 mt-3">
                    <div class="mb-3">
                        <label class="form-label">Department :</label>
                        <input value="{{ $employee->name }}" type="text" class="form-control" disabled >
                    </div>
                </div>
                <div class="col-3 mt-3">
                    <div class="mb-3">
                        <label class="form-label">Cost Center :</label>
                        <input value="{{ $employee->name }}" type="text" class="form-control" disabled >
                    </div>
                </div>
                <div class="col-3 mt-3">
                    <div class="mb-3">
                        <label class="form-label">Location :</label>
                        <input value="{{ $employee->name }}" type="text" class="form-control" disabled >
                    </div>
                </div>
                <div class="col-4 mt-3">
                    <div class="mb-3">
                        <label class="form-label">Schedule :</label>
                        <input value="{{ $employee->schedule }}" type="text" class="form-control" disabled >
                    </div>
                </div>
                <div class="col-4 mt-3">
                    <div class="mb-3">
                        <label class="form-label">Total Balance : </label>
                        <input value="{{ $employee->balance }}" type="text" class="form-control bg-danger" disabled >
                    </div>
                </div>
                <div class="col-4 mt-3">
                    <div class="mb-3">
                        <label class="form-label">To Date :</label>
                        <input value="{{ $employee->to_date }}" type="text" class="form-control" disabled >
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 mb-3">
            <div class="row g-5">
                <div class="col-7">
                    <div class="flatpickr" wire:ignore>
                        <input id="time" wire:model="range" x-data x-init="flatpickr($refs.input, {{ $options }} );" x-ref="input" type="hidden" data-input style="display: none;" />
                    </div>
                </div>
                <div class="col-5">
                    <label class="form-label">Selectgroup with icons and text {{$val}}</label>
                    <div class="form-selectgroup">
                        @foreach(range('A','Z') as $V) 
                            <label class="form-selectgroup-item">
                                <input type="radio" wire:confirm="ok?" wire:click="save" wire:model="val" value="{{$V}}" class="form-selectgroup-input">
                                <span class="form-selectgroup-label">{{$V}}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script >
    window.onload = function () {
        flatpickr("#time", {
            inline: true,
            mode: "range"
        });
    
        console.log("DOM fully loaded and parsed last thing");
    }; 

</script>
@endsection