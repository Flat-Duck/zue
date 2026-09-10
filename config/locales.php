<?php

return [

    /*
    |--------------------------------------------------------------------------
    | The languages the interface is offered in
    |--------------------------------------------------------------------------
    |
    | `dir` drives the `dir` attribute on the page and which Tabler stylesheet is
    | loaded: Tabler ships a mirrored build rather than relying on logical
    | properties, so right-to-left is a different file.
    |
    | `native` is what the language calls itself, which is what belongs in a
    | language picker — someone looking for Arabic is looking for العربية.
    |
    */

    'supported' => [
        'en' => ['native' => 'English', 'english' => 'English', 'dir' => 'ltr'],
        'ar' => ['native' => 'العربية', 'english' => 'Arabic', 'dir' => 'rtl'],
    ],

];
