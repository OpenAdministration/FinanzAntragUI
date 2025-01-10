<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

uses(Tests\TestCase::class)->in('Pest');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

use App\Models\User;

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * @return User returns a default user without special permissions
 */
function user(): User
{
    return User::where(['username' => 'user'])->first();
}

/**
 * @return User returns a budget manager user, with budget manager permissions
 */
function budgetManager(): User
{
    return User::where(['username' => 'hhv'])->first();
}

/**
 * @return User returns a cash management user, with cash manager permissions
 */
function cashManager(): User
{
    return User::where(['username' => 'kv'])->first();
}

/**
 * @return \Illuminate\Http\Testing\File the by livewire expected filetype
 */
function testFile(string $storage_path, ?string $fileName = null): \Illuminate\Http\Testing\File
{
    if (empty($fileName)) {
        $fileName = str($storage_path)->explode('/')->last();
    }
    $content = Storage::disk('tests')->get($storage_path);

    return \Illuminate\Http\Testing\File::createWithContent($fileName, $content);
}
