<?php

use App\Models\Faq;
use App\Models\TeamMember;

beforeEach(fn () => $this->withoutVite());

it('hides the team section when the flag is off', function () {
    TeamMember::factory()->create(['name' => 'Test Person', 'is_featured' => true]);
    config()->set('ministry.show_team', false);

    $this->get('/')->assertOk()->assertDontSee('id="team"', false)->assertDontSee('Test Person');
});

it('shows featured team members, full-time first, when the flag is on', function () {
    config()->set('ministry.show_team', true);
    TeamMember::factory()->create(['name' => 'Part Timer', 'is_featured' => true, 'is_full_time' => false, 'sort_order' => 1]);
    TeamMember::factory()->create(['name' => 'Full Timer', 'is_featured' => true, 'is_full_time' => true, 'sort_order' => 2]);
    TeamMember::factory()->create(['name' => 'Hidden One', 'is_featured' => false]);

    $this->get('/')->assertOk()
        ->assertSee('id="team"', false)
        ->assertSeeInOrder(['Full Timer', 'Part Timer'])
        ->assertDontSee('Hidden One');
});

it('hides the team section when the flag is on but nobody is featured', function () {
    config()->set('ministry.show_team', true);

    $this->get('/')->assertOk()->assertDontSee('id="team"', false);
});

it('links the join button to #contact', function () {
    $html = $this->get('/')->assertOk()->getContent();

    expect($html)->toMatch('/<section id="join".*?<a href="#contact"[^>]*>\s*Message us to join\s*<\/a>.*?<\/section>/s');
});

it('does not show the FAQ on the homepage even when published FAQs exist', function () {
    Faq::factory()->create(['question' => 'Can I serve?', 'answer' => 'Yes.', 'is_published' => true]);

    $this->get('/')->assertOk()
        ->assertDontSee('id="faq"', false)
        ->assertDontSee('Can I serve?')
        ->assertDontSee('#faq', false);
});

it('shows the map only when an address is set', function () {
    config()->set('ministry.address', null);
    $this->get('/')->assertOk()->assertDontSee('<iframe', false);

    config()->set('ministry.address', '123 Sample St, Dasmariñas');
    $this->get('/')->assertOk()
        ->assertSee('maps.google.com/maps?q='.urlencode('123 Sample St, Dasmariñas'), false)
        ->assertSee('loading="lazy"', false)
        ->assertSee('referrerpolicy="no-referrer-when-downgrade"', false);
});

it('encodes special characters in the map address', function () {
    config()->set('ministry.address', 'Lot 1 & 2 "Sample" St');

    $this->get('/')->assertOk()
        ->assertSee('maps.google.com/maps?q='.urlencode('Lot 1 & 2 "Sample" St').'&amp;output=embed', false)
        ->assertDontSee('q=Lot 1 & 2', false);
});

it('hides the Messenger link when null and shows it when set', function () {
    config()->set('ministry.messenger_url', null);
    $this->get('/')->assertOk()->assertDontSee('>Messenger<', false);

    config()->set('ministry.messenger_url', 'https://m.me/example');
    $this->get('/')->assertOk()->assertSee('href="https://m.me/example"', false)->assertSee('>Messenger<', false);
});

it('shows the email and Facebook links in the contact section', function () {
    $this->get('/')->assertOk()
        ->assertSee('id="contact"', false)
        ->assertSee('mailto:'.config('ministry.email'), false)
        ->assertSee(config('ministry.facebook_url'), false);
});

it('shows Join Us and Contact links in the navbar', function () {
    $this->get('/')->assertOk()
        ->assertSee(route('home').'#join', false)
        ->assertSee(route('home').'#contact', false);
});
