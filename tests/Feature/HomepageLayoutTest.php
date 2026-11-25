<?php

use App\Models\User;

/**
 * Load config/ministry.php with the given env values, the way it loads at boot.
 * Restores the previous $_ENV values afterwards.
 */
function loadMinistryConfigWithEnv(array $env): array
{
    $previous = [];

    foreach ($env as $key => $value) {
        $previous[$key] = [getenv($key), $_ENV[$key] ?? null, $_SERVER[$key] ?? null];
        putenv($key.'='.$value);
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }

    try {
        return require config_path('ministry.php');
    } finally {
        foreach ($previous as $key => [$fromPutenv, $fromEnv, $fromServer]) {
            if ($fromPutenv === false) {
                putenv($key);
            } else {
                putenv($key.'='.$fromPutenv);
            }

            if ($fromEnv === null) {
                unset($_ENV[$key]);
            } else {
                $_ENV[$key] = $fromEnv;
            }

            if ($fromServer === null) {
                unset($_SERVER[$key]);
            } else {
                $_SERVER[$key] = $fromServer;
            }
        }
    }
}

beforeEach(fn () => $this->withoutVite());

it('renders the homepage with the ministry name and subheader', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('COG Audio Ministry')
        ->assertSee('Dasmariñas');
});

it('shows a Member Login link to /login for guests', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('href="'.route('login').'"', false)
        ->assertSee('Member Login');
});

it('shows Dashboard instead of Member Login for signed-in users', function () {
    $this->actingAs(User::factory()->create())
        ->get('/')
        ->assertOk()
        ->assertSee('Dashboard')
        ->assertDontSee('Member Login');
});

it('hides the Meet the Team link when show_team is off', function () {
    config(['ministry.show_team' => false]);

    $this->get('/')
        ->assertOk()
        ->assertDontSee('href="'.route('home').'#team"', false);
});

it('shows the Meet the Team link when show_team is on', function () {
    config(['ministry.show_team' => true]);

    $this->get('/')
        ->assertOk()
        ->assertSee('href="'.route('home').'#team"', false);
});

it('links nav items to homepage anchors so they work from any page', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('href="'.route('home').'#about"', false)
        ->assertSee('href="'.route('home').'#gallery"', false)
        ->assertSee('href="'.route('home').'#contact"', false);
});

it('renders a skip link to the main landmark', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('href="#main"', false)
        ->assertSee('Skip to content')
        ->assertSee('id="main"', false);
});

it('wires the hamburger to the mobile menu with aria state', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('aria-controls="mobile-menu"', false)
        ->assertSee('id="mobile-menu"', false)
        ->assertSee(':aria-expanded="open.toString()"', false);
});

it('hides the Messenger link when messenger_url is null', function () {
    config(['ministry.messenger_url' => null]);

    $this->get('/')
        ->assertOk()
        ->assertDontSee('>Messenger<', false);
});

it('shows the Messenger link when messenger_url is an https URL', function () {
    config(['ministry.messenger_url' => 'https://example.com/messenger']);

    $this->get('/')
        ->assertOk()
        ->assertSee('href="https://example.com/messenger"', false)
        ->assertSee('>Messenger<', false);
});

it('drops a javascript: messenger URL when the config file loads', function () {
    $config = loadMinistryConfigWithEnv(['MINISTRY_MESSENGER_URL' => 'javascript:alert(1)']);

    expect($config['messenger_url'])->toBeNull();
});

it('keeps an https messenger URL when the config file loads', function () {
    $config = loadMinistryConfigWithEnv(['MINISTRY_MESSENGER_URL' => 'https://example.com/messenger']);

    expect($config['messenger_url'])->toBe('https://example.com/messenger');
});

it('drops an invalid ministry email when the config file loads', function () {
    $config = loadMinistryConfigWithEnv(['MINISTRY_EMAIL' => 'not-an-email']);

    expect($config['email'])->toBeNull();
});

it('renders a mailto link for the ministry email', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('href="mailto:'.config('ministry.email').'"', false);
});

it('shows the text logo placeholder when no logo file exists', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('>COG</span>', false)
        ->assertDontSee('images/logo.png', false);
})->skip(fn () => file_exists(public_path('images/logo.png')), 'public/images/logo.png exists, so the placeholder is not rendered.');

it('shows the logo image with empty alt when the logo file exists', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('src="'.asset('images/logo.png').'"', false)
        ->assertSee('alt=""', false)
        ->assertDontSee('>COG</span>', false);
})->skip(fn () => ! file_exists(public_path('images/logo.png')), 'public/images/logo.png does not exist yet.');

it('no longer has the retired join form URL config key', function () {
    expect(config('ministry'))->not->toHaveKey('join_form_url');
});
