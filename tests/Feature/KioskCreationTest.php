<?php

namespace Tests\Feature;

use App\Models\Kiosk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KioskCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_kiosk_without_manual_identifier(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $staff = User::factory()->create(['role' => 'staff']);

        $response = $this->actingAs($admin)->post(route('admin.kiosks.store'), [
            'name' => 'Gate 1 Scanner',
            'assigned_user_id' => $staff->id,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->assertDatabaseHas('kiosks', [
            'name' => 'Gate 1 Scanner',
            'assigned_staff_id' => $staff->id,
        ]);

        $kiosk = Kiosk::where('name', 'Gate 1 Scanner')->first();
        $this->assertNotNull($kiosk);
        $this->assertNotNull($kiosk->identifier);
        $this->assertStringStartsWith('KIOSK-', $kiosk->identifier);
        $this->assertTrue($kiosk->is_active);
    }
}
