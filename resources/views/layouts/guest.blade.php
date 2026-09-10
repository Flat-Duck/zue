@php
    use App\Http\Middleware\RememberTablerPreferences;

    $locale = app()->getLocale();
    $direction = config("locales.supported.{$locale}.dir", 'ltr');
    $appearance = RememberTablerPreferences::attributes();
@endphp
<!doctype html>
<html lang="{{ str_replace('_', '-', $locale) }}" dir="{{ $direction }}"
    @foreach ($appearance as $attribute => $value) {{ $attribute }}="{{ $value }}" @endforeach>
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta http-equiv="X-UA-Compatible" content="ie=edge"/>
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @yield('meta_tags')
    
    <title>zue</title>

    {{-- Styles first: the guest layout previously loaded only the script, so
         every page using it rendered as unstyled HTML. --}}
    @vite([$direction === 'rtl' ? 'resources/sass/app-rtl.scss' : 'resources/sass/app.scss', 'resources/js/app.js'])
    @yield('styles')

    {{-- @livewireStyles --}}
  </head>
  <body class="d-flex flex-column">
    <div class="page page-center">
      <div class="container container-tight py-4">
        <div class="text-center mb-4">
          <a href="." class="navbar-brand navbar-brand-autodark">
            <img src="{{ asset('img/zue-logo.png') }}" height="32" alt="zue" class="navbar-brand-image">
          </a>
        </div>
        @yield('content')
      </div>
    </div>
    @stack('modals')

    {{-- @livewireScripts --}}
    @stack('scripts')

    @if (session()->has('success'))
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                new window.Notyf({ dismissible: true })
                    .success(@json(session('success')));
            });
        </script>
    @endif
  </body>
</html>
