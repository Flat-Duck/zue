<?php

namespace Tests\Feature;

use App\Http\Middleware\SetLocale;
use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The interface is offered in English and Arabic.
 *
 * Arabic is not only a different set of strings: the page reverses, and Tabler
 * ships a mirrored stylesheet rather than relying on logical properties, so the
 * direction decides which stylesheet is served. Both halves are asserted here.
 */
class LocaleTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed(PermissionsSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('super-admin');

        return $user->fresh();
    }

    #[Test]
    public function both_languages_are_offered(): void
    {
        $this->assertSame(['en', 'ar'], array_keys(config('locales.supported')));
        $this->assertSame('rtl', config('locales.supported.ar.dir'));
        $this->assertSame('ltr', config('locales.supported.en.dir'));
    }

    #[Test]
    public function every_english_key_has_an_arabic_one(): void
    {
        $flatten = function (array $items, string $prefix = '') use (&$flatten): array {
            $flat = [];

            foreach ($items as $key => $value) {
                $flat += is_array($value)
                    ? $flatten($value, "{$prefix}{$key}.")
                    : ["{$prefix}{$key}" => $value];
            }

            return $flat;
        };

        $english = $flatten(require lang_path('en/crud.php'));
        $arabic = $flatten(require lang_path('ar/crud.php'));

        $this->assertSame([], array_keys(array_diff_key($english, $arabic)), 'Arabic is missing keys.');
        $this->assertSame([], array_keys(array_diff_key($arabic, $english)), 'Arabic has keys English does not.');

        $untranslated = array_keys(array_filter(
            $arabic,
            fn (string $value, string $key): bool => $value === ($english[$key] ?? null),
            ARRAY_FILTER_USE_BOTH
        ));

        $this->assertSame([], $untranslated, 'These are still English in the Arabic file.');
    }

    #[Test]
    public function choosing_arabic_turns_the_page_round(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('locale.switch', 'ar'));

        $response = $this->actingAs($admin)->get(route('employees.index'));

        $response->assertOk()
            ->assertSee('<html lang="ar" dir="rtl"', false)
            // The column headings the index actually renders.
            ->assertSee('الاسم بالإنجليزية', false)
            ->assertSee('الإجراءات', false);
    }

    #[Test]
    public function arabic_is_served_the_mirrored_stylesheet(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('locale.switch', 'ar'));
        $this->actingAs($admin)->get(route('employees.index'))->assertSee('app-rtl', false);

        $this->actingAs($admin)->get(route('locale.switch', 'en'));
        $response = $this->actingAs($admin)->get(route('employees.index'));

        $response->assertSee('<html lang="en" dir="ltr"', false);
        $response->assertDontSee('app-rtl', false);
    }

    #[Test]
    public function the_choice_is_remembered_between_requests(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('locale.switch', 'ar'))->assertRedirect();

        $this->assertSame('ar', session(SetLocale::SESSION_KEY));

        $this->actingAs($admin)->get(route('employees.index'))->assertSee('dir="rtl"', false);
    }

    /**
     * Someone arriving with an Arabic browser should not have to find the switch.
     */
    #[Test]
    public function the_browser_is_asked_when_no_choice_has_been_made(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->withHeaders(['Accept-Language' => 'ar,en;q=0.8'])
            ->get(route('employees.index'))
            ->assertSee('dir="rtl"', false);
    }

    #[Test]
    public function an_unknown_language_is_refused_rather_than_guessed_at(): void
    {
        $this->actingAs($this->admin())
            ->get(route('locale.switch', 'fr'))
            ->assertNotFound();
    }

    #[Test]
    public function the_login_page_is_offered_in_both_languages_too(): void
    {
        $this->get(route('locale.switch', 'ar'));

        $this->get(route('login'))->assertOk()->assertSee('dir="rtl"', false);
    }
}
