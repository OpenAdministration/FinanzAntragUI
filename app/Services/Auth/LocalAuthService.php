<?php

namespace App\Services\Auth;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Request;

class LocalAuthService extends AuthService
{
    private string $username;

    public function __construct()
    {
        $this->username = config('local.auth.username');
    }

    public function prepareLogin(): Response|RedirectResponse
    {
        return redirect()->route('login.callback');
    }

    public function userFromCallback(Request $request): array
    {
        return [
            ['username' => config('local.auth.username', 'user')],
            [],
        ];
    }

    public function userCommittees(): Collection
    {
        return match (\Auth::user()->username) {
            'user' => collect(['Students Council']),
            'hhv' => collect(['Financial Department']),
            'kv' => collect(['Financial Department']),
            'revision' => collect(),
            'external' => collect(),
            default => collect(),
        };
    }

    public function allCommittees(): Collection
    {
        return collect([
            'Students',
            'Financial Department']
        );
    }

    public function userGroupsRaw(): Collection
    {
        return match (\Auth::user()->username) {
            'user' => collect(['login']),
            'hhv' => collect(['login', 'ref-finanzen', 'ref-finanzen-hv', 'ref-finanzen-belege']),
            'kv' => collect(['login', 'ref-finanzen', 'ref-finanzen-kv', 'ref-finanzen-belege']),
            'revision' => collect(['login', 'ref-finanzen']),
            'admin' => collect(['admin']),
            'external' => collect(['login']),
        };
    }

    public function afterLogout() {}
}
