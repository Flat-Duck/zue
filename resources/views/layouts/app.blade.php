<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1" viewport-fit=cover">
        <meta http-equiv="X-UA-Compatible" content="ie=edge"/>
        <!-- CSRF Token -->
        <meta name="csrf-token" content="{{ csrf_token() }}">
        @yield('meta_tags')
        
        <title>zue</title>
        
        @vite('resources/js/app.js')
        
        
        
        {{-- @livewireStyles --}}
        @yield('styles')
    </head>
    
    <body>
        <div id="app" class="page">
            <div class="sticky-top">
                @include('layouts.nav')
                @include('layouts.sidebar')
            </div>
            <div class="page-wrapper">
                {{-- <div class="page-header d-print-none">
                    <div class="container-xl">
                        <div class="row g-2 align-items-center">
                            <div class="col">
                                <h2 class="page-title">
                                    @yield('page_title')
                                </h2>
                            </div>
                        </div>
                    </div>
                </div> --}}
                <div class="page-body">
                    <div class="container-xl">
                        @yield('content')
                    </div>
                </div>
                {{-- @include('layouts.footer') --}}
            </div>
        </div>

        
        {{-- @livewireScripts --}}
        
        @stack('scripts')
        @yield('scripts')
        
        <script src="https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.js"></script>
        
        @if (session()->has('success')) 
        <script>
            var notyf = new Notyf({dismissible: true})
            notyf.success('{{ session('success') }}')
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
                            if (! event.target.files.length) return
                            
                            let file = event.target.files[0],
                            reader = new FileReader()
                            
                            reader.readAsDataURL(file)
                            reader.onload = e => callback(e.target.result)
                        },
                    }
                })
            })
            </script>
    @stack('modals')
    </body>
    </html>