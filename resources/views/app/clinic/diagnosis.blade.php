@extends('layouts.app', ['page' => 'clinic'])
@section('title', 'Employee Diagnosis')
@section('styles')
@endsection
@section('content')
    <div class="container-xl">
        <div class="page-header d-print-none">
            <h2 class="page-title">
                {{ __('Patient File') }}
            </h2>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            <div class="card">
                <div class="card-header p-2">
                    <div class="col-auto">
                        @livewire('search-employee')
                    </div>
                </div>

                @isset($employee)
                    @livewire('clinic-employee-info', ['employee' => $employee])
                @endisset
            </div>
        </div>
    </div>

    @push('scripts')
        @livewireScripts
        @vite('resources/js/editor.js')
        <script>
            document.addEventListener("DOMContentLoaded", function() {

                plugins = ["advlist", "anchor", "lists"];
                tools = "formatselect | bold italic backcolor | alignleft aligncenter " +
                    "alignright alignjustify | bullist numlist outdent indent | removeformat";
                const diagnosisEditor = hugeRTE.init({
                    selector: "#diagnosis",
                    height: 300,
                    menubar: false,
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

                document.addEventListener('setDiagnosis', (event) => {
                    const content = event.detail[0].content;
                    console.log("New Diagnosis Content:", content);
                    hugeRTE.get('diagnosis').setContent(content);
                });
                document.addEventListener('setPrescription', (event) => {
                    const content = event.detail[0].content;
                    console.log("New prescription Content:", content);
                    hugeRTE.get('prescription').setContent(content);
                });
            });
        </script>
    @endpush
@endsection
