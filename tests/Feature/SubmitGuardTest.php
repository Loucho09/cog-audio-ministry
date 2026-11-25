<?php

use App\Models\Branch;
use App\Models\User;
use Illuminate\Support\Facades\Blade;

// Every <form> in the rendered HTML must opt in to the submit guard (resources/js/submit-guard.js).
function assertAllFormsAreGuarded(string $html): void
{
    preg_match_all('/<form\b[^>]*>/s', $html, $matches);

    expect($matches[0])->not->toBeEmpty();

    foreach ($matches[0] as $formTag) {
        expect($formTag)->toContain('data-submit-guard');
    }
}

it('guards every form on the login page and shows the signing in label', function () {
    $response = $this->get(route('login'))->assertOk();

    assertAllFormsAreGuarded($response->getContent());

    $response
        ->assertSee('Signing in…')
        ->assertSee('group-aria-busy:hidden', false);
});

it('guards every form on the forgot password page', function () {
    $response = $this->get(route('password.request'))->assertOk();

    assertAllFormsAreGuarded($response->getContent());

    $response->assertSee('Sending…');
});

it('guards every form on the profile page and shows the saving, logout and delete labels', function () {
    $branch = Branch::factory()->create();

    $response = $this->actingAs(User::factory()->member($branch)->create())
        ->get(route('profile.edit'))
        ->assertOk();

    assertAllFormsAreGuarded($response->getContent());

    $response
        ->assertSee('Saving…')
        ->assertSee('Logging out…')
        ->assertSee('Deleting…');
});

it('guards the verify email form and its logout form', function () {
    $branch = Branch::factory()->create();

    $response = $this->actingAs(User::factory()->member($branch)->create(['email_verified_at' => null]))
        ->get(route('verification.notice'))
        ->assertOk();

    assertAllFormsAreGuarded($response->getContent());

    $response->assertSee('Sending…');
});

it('renders the loading label only when a loading text is passed to the primary button', function () {
    $plain = Blade::render('<x-primary-button>Save</x-primary-button>');
    $loading = Blade::render('<x-primary-button loading-text="Saving…">Save</x-primary-button>');

    expect($plain)->toContain('type="submit"')
        ->and($plain)->not->toContain('group-aria-busy:hidden')
        ->and($plain)->not->toContain('Saving…');

    expect($loading)->toContain('Save')
        ->and($loading)->toContain('Saving…')
        ->and($loading)->toContain('group-aria-busy:hidden')
        ->and($loading)->toContain('animate-spin')
        ->and($loading)->toMatch('/class="[^"]*\bgroup\b/');
});

it('renders the loading label on the danger button', function () {
    $html = Blade::render('<x-danger-button loading-text="Deleting…">Delete Account</x-danger-button>');

    expect($html)->toContain('type="submit"')
        ->and($html)->toContain('Deleting…')
        ->and($html)->toContain('group-aria-busy:inline-flex');
});
