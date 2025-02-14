<?php

return [

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'keycloak' => [
        'client_id' => env('KEYCLOAK_CLIENT_ID'),
        'client_secret' => env('KEYCLOAK_CLIENT_SECRET'),
        'redirect' => env('KEYCLOAK_REDIRECT_URI'),
        'base_url' => env('KEYCLOAK_BASE_URL'),   // Specify your keycloak server URL here
        'realms' => env('KEYCLOAK_REALM'),         // Specify your keycloak realm
    ],

    'laravelpassport' => [
        'client_id' => env('STUMV_CLIENT_ID'),
        'client_secret' => env('STUMV_CLIENT_SECRET'),
        'redirect' => rtrim(env('APP_URL', 'http://localhost:8000'), '/').'/auth/callback',
        'host' => env('STUMV_HOST'),
        'logout_path' => env('STUMV_LOGOUT_PATH', 'logout'),
        'mapping' => [
            'login' => env('STUMV_GROUP_LOGIN', 'login'),
            'ref-finanzen' => env('STUMV_GROUP_REVISION'),
            'ref-finanzen-belege' => env('STUMV_GROUP_INVOICE'),
            'ref-finanzen-kv' => env('STUMV_GROUP_KV'),
            'ref-finanzen-hv' => env('STUMV_GROUP_HV'),
            'admin' => env('STUMV_GROUP_ADMIN'),
        ],
    ],

    'oidc' => [
        'client_id' => env('OIDC_CLIENT_ID'),
        'client_secret' => env('OIDC_CLIENT_SECRET'),
        'provider_url' => env('OIDC_PROVIDER_URL'),
        'certificate_path' => env('OIDC_CERT_PATH'),
        'scopes' => explode(' ', env('OIDC_SCOPES', 'openid profile email')),
        'verify_host' => env('OIDC_VERIFY_HOST', true),
        'attribute-mapping' => [
            'uid' => env('OIDC_ATTRIBUTE_UID', 'sub'),
            'username' => env('OIDC_ATTRIBUTE_USERNAME', 'username'),
            'name' => env('OIDC_ATTRIBUTE_NAME', 'name'),
            'email' => env('OIDC_ATTRIBUTE_EMAIL', 'email'),
            'picture_url' => env('OIDC_ATTRIBUTE_PICTURE_URL', 'avatar'),
            'iban' => env('OIDC_ATTRIBUTE_IBAN', 'iban'),
            'address' => env('OIDC_ATTRIBUTE_ADDRESS', 'address'),
            'groups' => env('OIDC_ATTRIBUTE_GROUP', 'groups'),
            'committees' => env('OIDC_ATTRIBUTE_COMMITTEES', 'committees'),
            'all-committees' => env('OIDC_ATTRIBUTE_ALL_COMMITTEES', 'all-committees'),
        ],
        'group-mapping' => [
            'login' => env('OIDC_GROUP_LOGIN', 'login'),
            'ref-finanzen' => env('OIDC_GROUP_REVISION'),
            'ref-finanzen-belege' => env('OIDC_GROUP_INVOICE'),
            'ref-finanzen-kv' => env('OIDC_GROUP_KV'),
            'ref-finanzen-hv' => env('OIDC_GROUP_HV'),
            'admin' => env('OIDC_GROUP_ADMIN'),
        ],
    ],

];
