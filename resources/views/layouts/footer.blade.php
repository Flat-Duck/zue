{{--
    The footer was Tabler's own: links to their documentation, a sponsor button, a
    "Copyright © 2023 Tabler" line, and `./license.html` and `./changelog.html`,
    neither of which exists here. None of it belonged in a private HR system.
--}}
<footer class="footer footer-transparent d-print-none">
    <div class="container-xl">
        <div class="row text-center align-items-center flex-row-reverse">
            <div class="col-12 col-lg-auto mt-3 mt-lg-0">
                <ul class="list-inline list-inline-dots mb-0">
                    <li class="list-inline-item">
                        &copy; {{ now()->year }} {{ config('app.name') }}. @lang('nav.all_rights_reserved')
                    </li>
                </ul>
            </div>
        </div>
    </div>
</footer>
