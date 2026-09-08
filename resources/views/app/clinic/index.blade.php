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
                @else
                @livewire('clinic-employee-info', ['employee' => $employee])
                @endisset
            </div>
        </div>
    </div>
    
    @push('scripts')
        @vite('resources/js/editor.js')
            <script>
      document.addEventListener("DOMContentLoaded", function () {
        let options = {
          selector: "#hugerte-mytextarea",
          height: 300,
          menubar: false,
          statusbar: false,
          plugins: [
            "advlist",
            "autolink",
            "lists",
            "link",
            "image",
            "charmap",
            "preview",
            "anchor",
            "searchreplace",
            "visualblocks",
            "code",
            "fullscreen",
            "insertdatetime",
            "media",
            "table",
            "code",
            "help",
            "wordcount",
          ],
          toolbar:
            "undo redo | formatselect | " +
            "bold italic backcolor | alignleft aligncenter " +
            "alignright alignjustify | bullist numlist outdent indent | " +
            "removeformat",
          content_style:
            "body { font-family: -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif; font-size: 14px; -webkit-font-smoothing: antialiased; }",
        };
        if (localStorage.getItem("tablerTheme") === "dark") {
          options.skin = "oxide-dark";
          options.content_css = "dark";
        }
        hugeRTE.init(options);
      });
    </script>
    @endpush
@endsection
