<?php

use App\Filament\Resources\Faqs\Pages\CreateFaq;
use App\Filament\Resources\Faqs\Pages\EditFaq;
use App\Filament\Resources\Faqs\Pages\ListFaqs;
use App\Models\Branch;
use App\Models\Faq;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    Filament::setCurrentPanel('admin');
    $this->branch = Branch::factory()->create();
    $this->faq = Faq::factory()->create();
});

it('lets a super-admin create, edit and delete a FAQ', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    Livewire::test(CreateFaq::class)
        ->fillForm(['question' => 'Why?', 'answer' => 'Because.', 'sort_order' => 3, 'is_published' => true])
        ->call('create')
        ->assertHasNoFormErrors();
    expect(Faq::where('question', 'Why?')->exists())->toBeTrue();

    Livewire::test(EditFaq::class, ['record' => $this->faq->id])
        ->fillForm(['question' => 'Changed?'])
        ->call('save')
        ->assertHasNoFormErrors();
    expect($this->faq->fresh()->question)->toBe('Changed?');

    Livewire::test(ListFaqs::class)->callTableBulkAction('delete', [$this->faq]);
    expect(Faq::find($this->faq->id))->toBeNull();
});

it('lets a branch-head view FAQs but not change them', function () {
    $this->actingAs(User::factory()->branchHead($this->branch)->create());

    Livewire::test(ListFaqs::class)->assertCanSeeTableRecords([$this->faq]);
    $this->get('/admin/faqs/create')->assertForbidden();
    $this->get("/admin/faqs/{$this->faq->id}/edit")->assertForbidden();
});

it('does not let a branch-admin bulk delete FAQs', function () {
    $this->actingAs(User::factory()->branchAdmin($this->branch)->create());

    Livewire::test(ListFaqs::class)->callTableBulkAction('delete', [$this->faq]);
    expect(Faq::find($this->faq->id))->not->toBeNull();
});

it('forbids members from the FAQ page', function () {
    $this->actingAs(User::factory()->member($this->branch)->create());

    $this->get('/admin/faqs')->assertForbidden();
});
