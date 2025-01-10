<?php

namespace App\Services\Auth;

use Http;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Request;
use Laravel\Socialite\Facades\Socialite;

class StumvAuthService extends AuthService
{
    private function api(): PendingRequest
    {
        return Http::baseUrl(config('services.laravelpassport.host'))
            ->withToken(session('stumv.tokens.access_token'))
            ->acceptJson();
    }

    public function prepareLogin(): Response|RedirectResponse
    {
        $driver = Socialite::driver('laravelpassport')
            ->scopes(['profile', 'committees', 'groups']);

        return $driver->redirect();
    }

    public function userFromCallback(Request $request): array
    {
        $driver = Socialite::driver('laravelpassport');
        // if we have a local dev instance of stumv there is no need to verify ssl certs
        if (\App::isLocal()) {
            $driver = $driver->setHttpClient(new \GuzzleHttp\Client(['verify' => false]));
        }
        $user = $driver->user();

        /** Array:
         *  token_type => Bearer,
         *  expires_in => (int),
         *  access_token => (string),
         *  refresh_token => (string),
         */
        session(['stumv.tokens' => $user->accessTokenResponseBody]);

        $attributes = $user->getRaw();
        $identifiers = [
            'provider_uid' => $attributes['id'], // can be username, here: ldap uuid
            'provider' => 'stumv',
        ];
        $userAttributes = [
            'name' => $attributes['name'],
            'username' => $attributes['nickname'],
            'email' => $attributes['email'],
            // 'provider_token' => $user->token,
            // 'provider_token_expiration' => now()->addSeconds($tokenResponse['expires_in']),
            // 'provider_refresh_token' => $user->refreshToken,
            // 'provider_refresh_token_expiration' => now()->addSeconds($tokenResponse['expires_in']),
            'picture_url' => $attributes['avatar'] ?? '',
            'iban' => $attributes['iban'] ?? '',
            'address' => $attributes['address'] ?? '',
        ];

        return [$identifiers, $userAttributes];
    }

    public function userCommittees(): Collection
    {
        return \Session::remember('stumv.comittees', function () {
            return $this->api()->get('/api/my/committees')->collect();
        });
    }

    public function userGroupsRaw(): Collection
    {
        return \Session::remember('stumv.groups', function () {
            return $this->api()->get('/api/my/groups')->collect();
        });
    }

    public function userGroups(): Collection
    {
        $rawGroups = $this->userGroupsRaw();
        $mapping = collect(config('services.laravelpassport.mapping', []));
        if ($mapping->isEmpty()) {
            return $this->userGroupsRaw();
        }

        // permissions to obtain are the keys of the $mapping
        return $mapping->filter(function ($value) use ($rawGroups) {
            // filter permissions, that are not given by provider
            // prevent permission escalation by ignoring empty mappings
            return $rawGroups->contains($value) && ! empty($value);
        })->keys();
    }

    public function afterLogout()
    {
        \Session::flush();

        return redirect(to: config('services.laravelpassport.host').
            config('services.laravelpassport.logout_path')
        );
    }

    public function allCommittees(): Collection
    {
        $community_uid = config('stufis.community_uid');

        return $this->api()->get("/api/committees/$community_uid")->collect();
    }
}
