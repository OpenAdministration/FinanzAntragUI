<?php

use Illuminate\Support\Facades\Facade;

return [

    'aliases' => Facade::defaultAliases()->merge([
        'Debugbar' => Barryvdh\Debugbar\Facades\Debugbar::class,
    ])->toArray(),

    'version' => \Composer\InstalledVersions::getPrettyVersion('openadministration/stufis'),

    'about_url' => env('ABOUT_URL', 'https://open-administration.de/index.php/kontakt-und-impressum/'),

    'privacy_url' => env('PRIVACY_URL', 'https://open-administration.de/index.php/datenschutz/'),

    'terms_url' => env('TERMS_URL', 'https://open-administration.de/index.php/nutzungsbedingungen/'),

    'help_contact_mail' => env('HELP_CONTACT_MAIL', 'stufis@open-administration.de'),

    'git-repo' => env('GIT_URL', 'https://github.com/openadministration/stufis/releases'),

    'blog_url' => env('BLOG_URL', 'https://open-administration.de'),
    'docs_url' => env('DOCS_URL', 'https://doku.stufis.de'),

    'realm' => env('AUTH_REALM'),

    'fints' => [
        'registration-number' => env('FINTS_REG_NR'),
    ],

    'chat' => [
        'public_key' => env('CHAT_PUBLIC_KEY'),
        'private_key' => env('CHAT_PRIVATE_KEY'),
    ],

];
