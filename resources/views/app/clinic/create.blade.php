@extends('layouts.app', ['page' => 'clinic'])

@section('content')
<form method="POST" action="{{ route('clinic.store') }}" class="card">
    @csrf
    <div class="card-header">
        <a href="{{ route('clinic.index') }}" class="mr-4"
            ><i class="ti ti-arrow-back"></i
        ></a>
        <h3 class="card-title">@lang('crud.clinic.create_title')</h3>
    </div>
    <div class="card-body">
        <div class="col-6">@include('app.clinic.form-inputs')</div>
        <div class="col-12">@include('app.clinic.editor')</div>
    </div>
    <div class="card-footer text-end">
        <div class="d-flex">
            <a
                href="{{ route('clinic.index') }}"
                class="btn btn-outline-secondary"
                >@lang('crud.common.back')</a
            >
            <button type="submit" class="btn btn-primary">
                <i class="ti ti-device-floppy"></i> @lang('crud.common.create')
            </button>
        </div>
    </div>
</form>
@endsection
@push('scripts')
      @vite('resources/js/editor.js')
      <script @cspNonce>
            document.addEventListener("DOMContentLoaded", function() {

                plugins = ["advlist", "anchor", "lists"];
                tools = "formatselect | bold italic backcolor | alignleft aligncenter " +
                    "alignright alignjustify | bullist numlist outdent indent | removeformat";
                const diagnosisEditor = hugeRTE.init({
                    selector: "#diagnosis",
                    height: 300,
                    menubar: false,
                    // The skin and content stylesheet are in the build; without this the editor
                    // asks the current URL for them and gets a 404.
                    skin_url: 'default',
                    content_css: 'default',
                    statusbar: false,
                    plugins: plugins,
                    toolbar: tools,
                    setup: function(editor) {
                        editor.on("change", function() {
                            Livewire.dispatch('updateDiagnosis', {
                                content: editor.getContent()
                            });
                        });
                    }
                });
                const prescriptionEditor = hugeRTE.init({
                    selector: "#prescription",
                    height: 300,
                    menubar: false,
                    // The skin and content stylesheet are in the build; without this the editor
                    // asks the current URL for them and gets a 404.
                    skin_url: 'default',
                    content_css: 'default',
                    statusbar: false,
                    plugins: plugins,
                    toolbar: tools,
                    setup: function(editor) {
                        editor.on("change", function() {
                            Livewire.dispatch('updatePrescription', {
                                content: editor.getContent()
                            });
                        });
                    }
                });

                // document.addEventListener('setDiagnosis', (event) => {
                //     const content = event.detail[0].content;
                //     console.log("New Diagnosis Content:", content);
                //     hugeRTE.get('diagnosis').setContent(content);
                // });
                // document.addEventListener('setPrescription', (event) => {
                //     const content = event.detail[0].content;
                //     console.log("New prescription Content:", content);
                //     hugeRTE.get('prescription').setContent(content);
                // });
            });
        </script>
    
@endpush
