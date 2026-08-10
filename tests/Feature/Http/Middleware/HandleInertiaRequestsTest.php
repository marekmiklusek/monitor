<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\App;
use Inertia\Testing\AssertableInertia as Assert;

it('shares the czech translations when the locale is cs', function (): void {
    App::setLocale('cs');

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page): Assert => $page
            ->where('locale', 'cs')
            ->where('translations.Issues', 'Problémy')
            ->where('translations.Resolve', 'Vyřešit')
            ->where('translations.Dashboard', 'Nástěnka')
            ->where('translations.Page :current of :last', 'Strana :current z :last'),
        );
});

it('ships a czech translation for every english key', function (): void {
    /** @var array<string, string> $english */
    $english = json_decode((string) file_get_contents(lang_path('en.json')), true);

    /** @var array<string, string> $czech */
    $czech = json_decode((string) file_get_contents(lang_path('cs.json')), true);

    expect(array_diff_key($english, $czech))->toBeEmpty()
        ->and(array_diff_key($czech, $english))->toBeEmpty();
});
