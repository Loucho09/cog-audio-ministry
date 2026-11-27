<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->withoutVite());

it('shows the Facebook link and hides Messenger when its URL is empty', function () {
    config(['ministry.facebook_url' => 'https://www.facebook.com/example', 'ministry.messenger_url' => null]);

    $response = $this->get('/')
        ->assertOk()
        ->assertSee('id="contact"', false)
        ->assertSee('Follow us for service updates and ministry news')
        ->assertSee('Follow us on Facebook')
        ->assertSee('https://www.facebook.com/example', false)
        ->assertDontSee('Message us on Messenger');

    expect(strtolower($response->getContent()))->not->toContain('satellite');
});

it('shows the Messenger button when its URL is set', function () {
    config(['ministry.facebook_url' => 'https://www.facebook.com/example', 'ministry.messenger_url' => 'https://m.me/example']);

    $this->get('/')
        ->assertOk()
        ->assertSee('Message us on Messenger')
        ->assertSee('https://m.me/example', false);
});

it('hides the links but keeps the contact section when both URLs are empty', function () {
    config(['ministry.facebook_url' => null, 'ministry.messenger_url' => null]);

    $this->get('/')
        ->assertOk()
        ->assertSee('id="contact"', false)
        ->assertSee('Facebook page to be added')
        ->assertSee('Chat link to be added')
        ->assertDontSee('Follow us on Facebook')
        ->assertDontSee('Message us on Messenger');
});
