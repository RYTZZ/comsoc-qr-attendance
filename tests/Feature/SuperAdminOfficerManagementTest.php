<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SuperAdminOfficerManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $officer;
    protected User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->create([
            'name' => 'Super Administrator',
            'email' => 'superadmin@comsoc.local',
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->officer = User::factory()->create([
            'name' => 'Treasurer Officer',
            'email' => 'treasurer@comsoc.local',
            'role' => 'treasurer',
            'officer_position' => 'Treasurer',
            'is_active' => true,
        ]);

        $this->student = User::factory()->create([
            'name' => 'John Member',
            'email' => 'member@student.local',
            'role' => 'student',
            'is_active' => true,
        ]);
    }

    public function test_super_admin_can_access_officer_management_page(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('admin.users.index'));
        $response->assertOk();
        $response->assertSee('System Account Management');
        $response->assertSee('Treasurer Officer');
    }

    public function test_non_super_admin_cannot_access_officer_management_page(): void
    {
        // Officer cannot access user management
        $response = $this->actingAs($this->officer)->get(route('admin.users.index'));
        $response->assertForbidden();

        // Student cannot access user management
        $response = $this->actingAs($this->student)->get(route('admin.users.index'));
        $response->assertForbidden();
    }

    public function test_super_admin_can_create_officer_account_with_position_and_first_login_flag(): void
    {
        $response = $this->actingAs($this->superAdmin)->post(route('admin.users.store'), [
            'account_type' => 'admin',
            'name' => 'Jane Secretary',
            'email' => 'secretary@comsoc.local',
            'student_number' => '2024-55443',
            'officer_position' => 'Executive Secretary',
            'role' => 'executive_secretary',
            'password' => 'TempPass@123',
            'password_confirmation' => 'TempPass@123',
            'is_active' => '1',
            'must_change_password' => '1',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'name' => 'Jane Secretary',
            'email' => 'secretary@comsoc.local',
            'student_number' => '2024-55443',
            'officer_position' => 'Executive Secretary',
            'role' => 'executive_secretary',
            'is_active' => true,
            'must_change_password' => true,
        ]);
    }

    public function test_account_creation_rejects_duplicate_email_and_student_number(): void
    {
        // Duplicate email
        $response = $this->actingAs($this->superAdmin)->post(route('admin.users.store'), [
            'account_type' => 'admin',
            'name' => 'Duplicate Email',
            'email' => 'treasurer@comsoc.local', // Already taken
            'role' => 'auditor',
            'password' => 'TempPass@123',
            'password_confirmation' => 'TempPass@123',
        ]);
        $response->assertSessionHasErrors(['email']);

        // Duplicate student number
        $this->officer->update(['student_number' => '2024-99999']);
        $response = $this->actingAs($this->superAdmin)->post(route('admin.users.store'), [
            'account_type' => 'admin',
            'name' => 'Duplicate ID',
            'email' => 'newauditor@comsoc.local',
            'student_number' => '2024-99999', // Already taken
            'role' => 'auditor',
            'password' => 'TempPass@123',
            'password_confirmation' => 'TempPass@123',
        ]);
        $response->assertSessionHasErrors(['student_number']);
    }

    public function test_super_admin_can_edit_officer_account_details_and_position(): void
    {
        $response = $this->actingAs($this->superAdmin)->put(route('admin.users.update', $this->officer), [
            'name' => 'Treasurer Officer Updated',
            'email' => 'treasurer.updated@comsoc.local',
            'student_number' => '2024-11223',
            'officer_position' => 'Auditor',
            'role' => 'auditor',
            'is_active' => '1',
            'must_change_password' => '1',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('admin.users.index'));

        $this->officer->refresh();
        $this->assertEquals('Treasurer Officer Updated', $this->officer->name);
        $this->assertEquals('treasurer.updated@comsoc.local', $this->officer->email);
        $this->assertEquals('2024-11223', $this->officer->student_number);
        $this->assertEquals('Auditor', $this->officer->officer_position);
        $this->assertEquals('auditor', $this->officer->role);
        $this->assertTrue($this->officer->must_change_password);
    }

    public function test_super_admin_can_deactivate_and_activate_officer(): void
    {
        $this->assertTrue($this->officer->is_active);

        // Deactivate
        $response = $this->actingAs($this->superAdmin)->post(route('admin.users.toggle-active', $this->officer));
        $response->assertRedirect();
        $this->officer->refresh();
        $this->assertFalse($this->officer->is_active);

        // Reactivate
        $response = $this->actingAs($this->superAdmin)->post(route('admin.users.toggle-active', $this->officer));
        $response->assertRedirect();
        $this->officer->refresh();
        $this->assertTrue($this->officer->is_active);
    }

    public function test_cannot_deactivate_last_active_super_admin(): void
    {
        // Try deactivating own or sole super admin account
        $response = $this->actingAs($this->superAdmin)->post(route('admin.users.toggle-active', $this->superAdmin));
        $response->assertSessionHas('error');
        $this->superAdmin->refresh();
        $this->assertTrue($this->superAdmin->is_active);
    }

    public function test_deactivated_officer_cannot_sign_in_or_access_admin(): void
    {
        $this->officer->update([
            'password' => Hash::make('SecretPass@123'),
            'is_active' => false,
        ]);

        $response = $this->post(route('login'), [
            'email' => $this->officer->email,
            'password' => 'SecretPass@123',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
    }

    public function test_super_admin_can_reset_officer_password_with_require_change_flag(): void
    {
        $this->officer->update(['must_change_password' => false]);

        $response = $this->actingAs($this->superAdmin)->post(route('admin.users.reset-password', $this->officer), [
            'password' => 'NewResetPass@123',
            'password_confirmation' => 'NewResetPass@123',
            'require_change' => '1',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->officer->refresh();
        $this->assertTrue(Hash::check('NewResetPass@123', $this->officer->password));
        $this->assertTrue($this->officer->must_change_password);
    }

    public function test_first_login_password_change_enforcement(): void
    {
        $this->officer->update([
            'password' => Hash::make('Temporary@123'),
            'must_change_password' => true,
        ]);

        // Attempting to visit dashboard redirects to profile to change password
        $response = $this->actingAs($this->officer)->get(route('admin.dashboard'));
        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHas('error');

        // Updating password clears the must_change_password flag
        $updateResponse = $this->actingAs($this->officer)->put(route('password.update'), [
            'current_password' => 'Temporary@123',
            'password' => 'BrandNewPassword@2026',
            'password_confirmation' => 'BrandNewPassword@2026',
        ]);

        $updateResponse->assertSessionHasNoErrors();
        $this->officer->refresh();
        $this->assertFalse($this->officer->must_change_password);

        // Now officer can access dashboard
        $allowedResponse = $this->actingAs($this->officer)->get(route('admin.dashboard'));
        $allowedResponse->assertOk();
    }

    public function test_user_cannot_delete_accounts_preserves_historical_records(): void
    {
        $response = $this->actingAs($this->superAdmin)->delete(route('admin.users.destroy', $this->officer));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $this->officer->id]);
    }
}
