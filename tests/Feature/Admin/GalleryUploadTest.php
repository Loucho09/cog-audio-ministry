<?php

use App\Filament\Resources\GalleryImages\GalleryImageResource;
use App\Filament\Resources\GalleryImages\Pages\CreateGalleryImage;
use App\Filament\Resources\GalleryImages\Pages\EditGalleryImage;
use App\Models\Branch;
use App\Models\GalleryImage;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Filament::setCurrentPanel('admin');
    Storage::fake('public');
});

it('uploads jpg, png and webp images', function (string $name) {
    $this->actingAs(User::factory()->superAdmin()->create());

    Livewire::test(CreateGalleryImage::class)
        ->fillForm(['path' => UploadedFile::fake()->image($name, 1200, 800)->size(3000), 'alt_text' => 'Up '.$name])
        ->call('create')
        ->assertHasNoFormErrors();

    $image = GalleryImage::where('alt_text', 'Up '.$name)->firstOrFail();
    expect($image->path)->toStartWith('gallery/');
    Storage::disk('public')->assertExists($image->path);
})->with(['a.jpg', 'a.png', 'a.webp']);

it('uploads as a branch-admin into their own branch, unpublished', function () {
    $branch = Branch::factory()->create();
    $this->actingAs(User::factory()->branchAdmin($branch)->create());

    Livewire::test(CreateGalleryImage::class)
        ->fillForm(['path' => UploadedFile::fake()->image('a.jpg', 1200, 800)->size(3000), 'alt_text' => 'Head upload'])
        ->call('create')
        ->assertHasNoFormErrors();

    $image = GalleryImage::where('alt_text', 'Head upload')->firstOrFail();
    expect($image->branch_id)->toBe($branch->id)->and($image->is_published)->toBeFalse();
    Storage::disk('public')->assertExists($image->path);
});

it('rejects an image over 4 MB and a wrong file type with a form error', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    Livewire::test(CreateGalleryImage::class)
        ->fillForm(['path' => UploadedFile::fake()->image('big.jpg')->size(5000), 'alt_text' => 'Big'])
        ->call('create')
        ->assertHasFormErrors(['path']);

    Livewire::test(CreateGalleryImage::class)
        ->fillForm(['path' => UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'), 'alt_text' => 'Pdf'])
        ->call('create')
        ->assertHasFormErrors(['path']);

    expect(GalleryImage::whereIn('alt_text', ['Big', 'Pdf'])->count())->toBe(0);
});

it('replaces an image on edit and removes the old file', function () {
    $this->actingAs(User::factory()->superAdmin()->create());
    Storage::disk('public')->put('gallery/old.jpg', 'x');
    $image = GalleryImage::factory()->create(['path' => 'gallery/old.jpg', 'branch_id' => null]);

    Livewire::test(EditGalleryImage::class, ['record' => $image->getKey()])
        ->fillForm(['path' => [UploadedFile::fake()->image('new.png', 800, 600)]])
        ->call('save')
        ->assertHasNoFormErrors();

    $image->refresh();
    expect($image->path)->not->toBe('gallery/old.jpg');
    Storage::disk('public')->assertExists($image->path);
    Storage::disk('public')->assertMissing('gallery/old.jpg');
});

it('removes the file when the record is deleted', function () {
    Storage::disk('public')->put('gallery/gone.jpg', 'x');
    $image = GalleryImage::factory()->create(['path' => 'gallery/gone.jpg']);

    $image->delete();

    Storage::disk('public')->assertMissing('gallery/gone.jpg');
});

it('redirects to the plain gallery list by default', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    Livewire::test(CreateGalleryImage::class)
        ->fillForm(['path' => UploadedFile::fake()->image('a.jpg'), 'alt_text' => 'Redir'])
        ->call('create')
        ->assertRedirect(GalleryImageResource::getUrl('index'));
});

it('returns to the list with page and tab preserved after create and save', function () {
    $this->actingAs(User::factory()->superAdmin()->create());
    $list = GalleryImageResource::getUrl('index').'?page=2&tab=pending';
    session()->setPreviousUrl($list);

    Livewire::test(CreateGalleryImage::class)
        ->fillForm(['path' => UploadedFile::fake()->image('a.jpg'), 'alt_text' => 'Keep'])
        ->call('create')
        ->assertRedirect($list);

    $image = GalleryImage::factory()->create(['branch_id' => null]);
    session()->setPreviousUrl($list);

    Livewire::test(EditGalleryImage::class, ['record' => $image->getKey()])
        ->call('save')
        ->assertRedirect($list);
});

it('ignores foreign or non-gallery previous URLs', function (string $previous) {
    $this->actingAs(User::factory()->superAdmin()->create());
    session()->setPreviousUrl($previous);

    Livewire::test(CreateGalleryImage::class)
        ->fillForm(['path' => UploadedFile::fake()->image('a.jpg'), 'alt_text' => 'Safe'])
        ->call('create')
        ->assertRedirect(GalleryImageResource::getUrl('index'));
})->with([
    'foreign host' => 'https://evil.example/admin/gallery-images?page=2',
    'other admin page' => 'http://localhost/admin/branches?page=2',
    'userinfo trick' => 'http://localhost@evil.example/admin/gallery-images',
]);

it('still supports create another', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    Livewire::test(CreateGalleryImage::class)
        ->fillForm(['path' => UploadedFile::fake()->image('a.jpg'), 'alt_text' => 'One'])
        ->call('create', true)
        ->assertHasNoFormErrors()
        ->assertNoRedirect();

    expect(GalleryImage::where('alt_text', 'One')->exists())->toBeTrue();
});

// Livewire::test sends no Referer header, so these tests set the session previous URL,
// which is what url()->previous() falls back to.

it('falls back to the index for bypass attempts', function (string $previous) {
    $this->actingAs(User::factory()->superAdmin()->create());
    session()->setPreviousUrl($previous);

    Livewire::test(CreateGalleryImage::class)
        ->fillForm(['path' => UploadedFile::fake()->image('a.jpg'), 'alt_text' => 'Bypass'])
        ->call('create')
        ->assertRedirect(GalleryImageResource::getUrl('index'));
})->with([
    'dot dot' => 'http://localhost/admin/gallery-images/../branches',
    'record edit' => 'http://localhost/admin/gallery-images/5/edit',
    'protocol relative' => '//evil.example/admin/gallery-images',
    'backslash userinfo' => 'http://localhost\@evil.example/admin/gallery-images',
    'encoded slashes' => 'http://localhost/admin/gallery-images%2f..%2fx',
    'other port' => 'http://localhost:8080/admin/gallery-images?page=2',
    'whitespace' => 'http://localhost/admin/gallery-images?x=a b',
    'too long' => 'http://localhost/admin/gallery-images?x='.str_repeat('a', 2100),
]);

it('keeps a trailing slash list url and normalises it', function () {
    $this->actingAs(User::factory()->superAdmin()->create());
    session()->setPreviousUrl(GalleryImageResource::getUrl('index').'/?page=3');

    Livewire::test(CreateGalleryImage::class)
        ->fillForm(['path' => UploadedFile::fake()->image('a.jpg'), 'alt_text' => 'Slash'])
        ->call('create')
        ->assertRedirect(GalleryImageResource::getUrl('index').'?page=3');
});

it('does not let the client tamper with the locked return url', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    expect(fn () => Livewire::test(CreateGalleryImage::class)->set('returnUrl', 'https://evil.example/'))
        ->toThrow(Exception::class);
});

it('keeps the return url after a validation failure and after create another', function () {
    $this->actingAs(User::factory()->superAdmin()->create());
    $list = GalleryImageResource::getUrl('index').'?page=2&tab=pending';
    session()->setPreviousUrl($list);

    Livewire::test(CreateGalleryImage::class)
        ->fillForm(['path' => UploadedFile::fake()->image('a.jpg'), 'alt_text' => ''])
        ->call('create')
        ->assertHasFormErrors(['alt_text'])
        ->fillForm(['path' => UploadedFile::fake()->image('a.jpg'), 'alt_text' => 'First'])
        ->call('create', true)
        ->assertNoRedirect()
        ->fillForm(['path' => UploadedFile::fake()->image('b.jpg'), 'alt_text' => 'Second'])
        ->call('create')
        ->assertRedirect($list);
});

it('ignores a foreign previous url on the edit page', function () {
    $this->actingAs(User::factory()->superAdmin()->create());
    $image = GalleryImage::factory()->create(['branch_id' => null]);
    session()->setPreviousUrl('https://evil.example/admin/gallery-images?page=2');

    Livewire::test(EditGalleryImage::class, ['record' => $image->getKey()])
        ->call('save')
        ->assertRedirect(GalleryImageResource::getUrl('index'));
});

it('returns to the preserved list after deleting from the edit page', function () {
    $this->actingAs(User::factory()->superAdmin()->create());
    $image = GalleryImage::factory()->create(['branch_id' => null]);
    $list = GalleryImageResource::getUrl('index').'?page=2&tab=pending';
    session()->setPreviousUrl($list);

    Livewire::test(EditGalleryImage::class, ['record' => $image->getKey()])
        ->callAction('delete')
        ->assertRedirect($list);

    expect(GalleryImage::find($image->getKey()))->toBeNull();
});
