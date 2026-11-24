<?php

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

it('hides the Meet the Team link when show_team is off', function () {
    config(['ministry.show_team' => false]);

    $this->get('/')
        ->assertOk()
        ->assertDontSee('href="#team"', false);
});

it('shows the Meet the Team link when show_team is on', function () {
    config(['ministry.show_team' => true]);

    $this->get('/')
        ->assertOk()
        ->assertSee('href="#team"', false);
});

it('shows the text logo placeholder when no logo file exists', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('>COG</span>', false)
        ->assertDontSee('images/logo.png', false);
})->skip(fn () => file_exists(public_path('images/logo.png')), 'public/images/logo.png exists, so the placeholder is not rendered.');
