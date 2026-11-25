<?php

use App\Models\Branch;
use App\Models\JoinApplication;
use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;

const GENERIC_DUPLICATE = 'We could not accept an application with this email. If you have already applied or have an account, please wait for our reply or contact us.';

function joinPayload(array $overrides = []): array
{
    return array_merge([
        'full_name' => 'Juan Dela Cruz',
        'age' => 22,
        'contact_number' => '09171234567',
        'email' => 'juan@example.com',
        'facebook_url' => 'https://facebook.com/juan',
        'branch_id' => Branch::factory()->create()->id,
        'consent' => '1',
        'website' => '',
    ], $overrides);
}

beforeEach(fn () => RateLimiter::clear(md5('join-form'.'join:127.0.0.1')));

it('renders the form and thank-you pages', function () {
    Branch::factory()->create(['name' => 'Branch Alpha']);

    $this->get(route('join.create'))->assertOk()->assertSee('Branch Alpha')->assertSee('<title>Join the Team', false)
        ->assertSee('If you are approved, you will receive an email to set up your account.')
        ->assertDontSee('will get back to you');
    $this->get(route('join.thanks'))->assertOk()
        ->assertSee('Thank you for applying')
        ->assertSee('If you are approved, you will receive an email');
});

it('saves a pending application with server-side consent', function () {
    $this->post(route('join.store'), joinPayload(['status' => 'approved', 'user_id' => 1, 'reviewed_by' => 1, 'consented_at' => '2000-01-01']))
        ->assertRedirect(route('join.thanks'));

    $app = JoinApplication::firstOrFail();
    expect($app->status)->toBe(JoinApplication::STATUS_PENDING)
        ->and($app->user_id)->toBeNull()
        ->and($app->reviewed_by)->toBeNull()
        ->and($app->consented_at->isToday())->toBeTrue();
});

it('rejects invalid input', function (array $override, string $field) {
    $this->post(route('join.store'), joinPayload($override))->assertSessionHasErrors($field);
    expect(JoinApplication::count())->toBe(0);
})->with([
    'no name' => [['full_name' => ''], 'full_name'],
    'bad age' => [['age' => 'abc'], 'age'],
    'bad email' => [['email' => 'nope'], 'email'],
    'non-https facebook' => [['facebook_url' => 'javascript:alert(1)'], 'facebook_url'],
    'unknown branch' => [['branch_id' => 99999], 'branch_id'],
    'age zero' => [['age' => 0], 'age'],
    'no consent' => [['consent' => null], 'consent'],
]);

it('silently drops honeypot submissions', function () {
    $this->post(route('join.store'), joinPayload(['website' => 'http://spam.test']))
        ->assertRedirect(route('join.thanks'));

    expect(JoinApplication::count())->toBe(0);
});

it('honeypot short-circuits before validation and lookups', function () {
    User::factory()->create(['email' => 'juan@example.com']);

    $this->post(route('join.store'), joinPayload(['website' => 'spam', 'full_name' => '', 'age' => 'x', 'branch_id' => 999999]))
        ->assertRedirect(route('join.thanks'))
        ->assertSessionHasNoErrors();

    expect(JoinApplication::count())->toBe(0);
});

it('accepts a young age since there is no minimum', function () {
    $this->post(route('join.store'), joinPayload(['age' => 1]))->assertRedirect(route('join.thanks'));
});

it('blocks an email that belongs to a user with a generic message', function () {
    User::factory()->create(['email' => 'juan@example.com']);

    $this->post(route('join.store'), joinPayload(['email' => '  JUAN@Example.com ']))
        ->assertSessionHasErrors(['email' => GENERIC_DUPLICATE]);
    expect(JoinApplication::count())->toBe(0);
});

it('blocks an email with a pending application using the same message', function () {
    JoinApplication::factory()->pending()->create(['email' => 'juan@example.com']);

    $this->post(route('join.store'), joinPayload(['email' => 'JUAN@example.com']))
        ->assertSessionHasErrors(['email' => GENERIC_DUPLICATE]);
    expect(JoinApplication::count())->toBe(1);
});

it('lets a rejected applicant apply again', function () {
    JoinApplication::factory()->rejected()->create(['email' => 'juan@example.com']);

    $this->post(route('join.store'), joinPayload())->assertRedirect(route('join.thanks'));
    expect(JoinApplication::count())->toBe(2);
});

it('rate limits submissions with 429', function () {
    foreach (range(1, 5) as $_) {
        $this->post(route('join.store'), joinPayload(['website' => 'bot']))->assertRedirect();
    }

    $this->post(route('join.store'), joinPayload())->assertStatus(429);
});

it('renders field errors accessibly after a failed submit', function () {
    $this->followingRedirects()->from(route('join.create'))
        ->post(route('join.store'), joinPayload(['email' => 'not-an-email']))
        ->assertOk()
        ->assertSee('id="email-error"', false)
        ->assertSee('aria-invalid="true"', false);
});
