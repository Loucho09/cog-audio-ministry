<?php

use App\Models\Branch;
use Database\Seeders\BranchSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->withoutVite());

it('shows our churches section with the logo and no satellite wording', function () {
    (new BranchSeeder)->run();

    $response = $this->get('/')
        ->assertOk()
        ->assertSee('One sound, four churches')
        ->assertSee('aria-label="Church of God logo: We Exalt You Jesus"', false)
        ->assertSee('images/cog-church-logo.png', false)
        ->assertDontSee('cog-church-logo-256.png', false);

    foreach (['COG Dasma', 'COG Trece', 'COG Silang', 'COG Jabez'] as $name) {
        $response->assertSee($name);
    }

    // Marilag is COG Dasma's subdivision, not a separate church.
    $response->assertDontSee('COG Marilag');

    expect(strtolower($response->getContent()))->not->toContain('satellite');
});

it('renders per-church Facebook links safely and skips churches without one', function () {
    (new BranchSeeder)->run();

    $response = $this->get('/')->assertOk();

    foreach ([
        'COG Trece' => 'https://www.facebook.com/COGTreceMartires',
        'COG Silang' => 'https://www.facebook.com/cogsilang',
        'COG Jabez' => 'https://www.facebook.com/churchofgodjabez',
    ] as $name => $url) {
        $response->assertSee('href="'.$url.'"', false)
            ->assertSee('aria-label="'.$name.' on Facebook (opens in a new tab)"', false);
    }

    $response->assertSee('rel="noopener noreferrer"', false);
});

it('seeds Marilag Subdivision as the COG Dasma address', function () {
    (new BranchSeeder)->run();

    expect(Branch::where('slug', 'marilag')->exists())->toBeFalse()
        ->and(Branch::mainBranch()->address)->toBe('Marilag Subdivision, Dasmariñas');
});

it('lists the main branch first and reads names from the database', function () {
    Branch::factory()->create(['name' => 'Aaa Branch', 'slug' => 'aaa']);
    Branch::factory()->create(['name' => 'Zzz Main', 'slug' => 'zzz', 'is_main' => true]);

    $this->get('/')->assertOk()->assertSeeInOrder(['Zzz Main', 'Main church', 'Aaa Branch']);
});

it('does not overwrite an admin-edited facebook_url when reseeding', function () {
    (new BranchSeeder)->run();
    Branch::where('slug', 'trece')->update(['facebook_url' => 'https://www.facebook.com/edited']);

    (new BranchSeeder)->run();

    expect(Branch::where('slug', 'trece')->value('facebook_url'))->toBe('https://www.facebook.com/edited');
});

it('does not render a stored non-https facebook_url as a link', function () {
    (new BranchSeeder)->run();
    Branch::where('slug', 'trece')->update(['facebook_url' => 'javascript:alert(1)']);

    $this->get('/')->assertOk()->assertDontSee('javascript:alert(1)', false);
});
