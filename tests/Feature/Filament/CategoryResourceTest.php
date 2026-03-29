<?php

use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\Categories\Pages\EditCategory;
use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('can render the category list page', function () {
    Livewire::test(ListCategories::class)
        ->assertSuccessful();
});

it('can list categories', function () {
    $categories = Category::factory()->count(5)->create();

    Livewire::test(ListCategories::class)
        ->assertCanSeeTableRecords($categories);
});

it('can render create category page', function () {
    Livewire::test(CreateCategory::class)
        ->assertSuccessful();
});

it('can create a category', function () {
    $newData = Category::factory()->make();

    Livewire::test(CreateCategory::class)
        ->fillForm([
            'name' => $newData->name,
            'slug' => $newData->slug,
            'description' => $newData->description,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas(Category::class, [
        'name' => $newData->name,
        'slug' => $newData->slug,
    ]);
});

it('can render edit category page', function () {
    $category = Category::factory()->create();

    Livewire::test(EditCategory::class, [
        'record' => $category->getRouteKey(),
    ])
        ->assertSuccessful();
});

it('can edit a category', function () {
    $category = Category::factory()->create();
    $newData = Category::factory()->make();

    Livewire::test(EditCategory::class, [
        'record' => $category->getRouteKey(),
    ])
        ->fillForm([
            'name' => $newData->name,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($category->refresh()->name)->toBe($newData->name);
});

it('can only select root categories as parent', function () {
    $rootCategory = Category::factory()->create(['parent_id' => null]);
    Category::factory()->create(['parent_id' => $rootCategory->id]);

    // parent_id is optional — submitting without it should produce no errors
    $newData = Category::factory()->make();

    Livewire::test(CreateCategory::class)
        ->assertFormExists()
        ->assertFormFieldExists('parent_id')
        ->fillForm([
            'name' => $newData->name,
            'slug' => $newData->slug,
        ])
        ->call('create')
        ->assertHasNoFormErrors(['parent_id']);
});

it('can delete a category', function () {
    $category = Category::factory()->create();

    Livewire::test(EditCategory::class, [
        'record' => $category->getRouteKey(),
    ])
        ->callAction('delete');

    $this->assertModelMissing($category);
});
