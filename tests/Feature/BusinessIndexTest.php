<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_business_index_requires_authentication(): void
    {
        $response = $this->get(route('businesses.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_business_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('businesses.index'));

        $response->assertOk();
        $response->assertSee('İşletmeler');
        $response->assertSee('Burger House');
        $response->assertSee('Yeni İşletme');
        $response->assertDontSee('>Logo<', false);
        $response->assertSee('İşletmeden Alınan Ücret');
        $response->assertSee('Kuryeye Verilen Ücret');
        $response->assertSee('45,00 ₺', false);
        $response->assertSee('32,00 ₺', false);
        $response->assertSee('Sözleşme Aşamasında');
    }

    public function test_business_contract_stage_status_reflects_on_show_and_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->put(route('businesses.update', 6), [
            'company_name' => 'Tatlı Diyarı Pastane ve Unlu Mamulleri',
            'brand_name' => 'Tatlı Diyarı',
            'phone' => '0224 666 77 88',
            'city' => 'Bursa',
            'district' => 'Nilüfer',
            'pricing_model' => 'daily',
            'earning_period' => 'weekly',
            'status' => 'contract_stage',
        ]);

        $response->assertRedirect(route('businesses.show', 6));

        $showResponse = $this->actingAs($user)->get(route('businesses.show', 6));
        $showResponse->assertOk();
        $showResponse->assertSee('Sözleşme Aşamasında');

        $indexResponse = $this->actingAs($user)->get(route('businesses.index', ['status' => 'contract_stage']));
        $indexResponse->assertOk();
        $indexResponse->assertSee('Tatlı Diyarı');
        $indexResponse->assertSee('Sözleşme Aşamasında');
    }

    public function test_authenticated_user_can_view_business_create_form(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('businesses.create'));

        $response->assertOk();
        $response->assertSee('Yeni İşletme');
        $response->assertSee('Genel Bilgiler');
        $response->assertSee('Çalışma Modeli');
        $response->assertDontSee('Hakediş Periyodu');
        $response->assertSee('Kaydet');
    }
}
