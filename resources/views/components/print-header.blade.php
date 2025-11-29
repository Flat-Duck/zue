<!-- resources/views/components/header.blade.php -->
<div class="header">
    <div class="row mt-2">
        <div class="col-3">
            <img src="/img/zue-logo.png" style="height: 100px" class="mx-auto d-block">
        </div>
        <div class="col-6">
            <h2 class="h2 text-center">
                ZUEITINA OIL COMPANY
            </h2>
            <h3 class="h3 text-center">
                ACCOUNTING DEPARTMENT 103
            </h3>
            <h4 class="h4 text-center">
                DETAILED FIELD-BREAK BALANCE
            </h4>
        </div>
        <div class="col-3 d-flex align-items-end">
            {{-- <img src="/img/noc-logo.png" style="height: 100px" class="mx-auto d-block"> --}}
            <h4 class="h4 text-right d-flex align-items-end">
                <span class="align-text-bottom">{{ now()->format('d/M/Y') }}</span>
            </h4>
        </div>
    </div>
    <div class="row mt-2">
        <!-- Additional content if needed -->
    </div>
</div>
