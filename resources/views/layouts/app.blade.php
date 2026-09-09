<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1" viewport-fit=cover">
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- @yield('meta_tags') --}}

    <title>zue</title>

    {{--
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet"> --}}
    {{-- @vite('resources/js/app.js') --}}
    @vite('resources/sass/app.scss')

    @livewireStyles
    @yield('styles')

</head>

<body>
    <div id="app" class="page">
        <div class="sticky-top d-print-none">
            @include('layouts.nav')
            @include('layouts.sidebar')
            </div>
        <div class="page-wrapper">
            <div class="page-body">
                <div class="container-xl">
                    @yield('content')
                </div>
            </div>
            {{-- @include('layouts.footer') --}}
        </div>
    </div>


    @livewireScripts

    @vite('resources/js/app.js')

    @if (session()->has('success'))
        <script>
            // app.js is a module and therefore deferred, so Notyf does not
            // exist while this inline script is parsed. Wait for the document.
            document.addEventListener('DOMContentLoaded', () => {
                new window.Notyf({ dismissible: true })
                    .success(@json(session('success')));
            });
        </script>
    @endif

    <script>
        /* Simple Alpine Image Viewer */
        document.addEventListener('alpine:init', () => {
            Alpine.data('imageViewer', (src = '') => {
                return {
                    imageUrl: src,

                    refreshUrl() {
                        this.imageUrl = this.$el.getAttribute("image-url")
                    },

                    fileChosen(event) {
                        this.fileToDataUrl(event, src => this.imageUrl = src)
                    },

                    fileToDataUrl(event, callback) {
                        if (!event.target.files.length) return

                        let file = event.target.files[0],
                            reader = new FileReader()

                        reader.readAsDataURL(file)
                        reader.onload = e => callback(e.target.result)
                    },
                }
            })
        })
    </script>
    @stack('scripts')
    @yield('scripts')
    @stack('modals')
</body>

</html>
