<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AttendanceRecord;
use App\Models\Event;
use App\Models\Membership;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class StudentActivationTest extends TestCase
{
    use RefreshDatabase;

    public function test_activation_step1_page_can_be_rendered(): void
    {
        $response = $this->get(route('student.activate'));
        $response->assertStatus(200);
        $response->assertSee('Enter Your Student Number');
    }

    public function test_step1_invalid_student_number_is_rejected(): void
    {
        $response = $this->post(route('student.activate.student.submit'), [
            'student_number' => '99-9999',
        ]);

        $response->assertSessionHasErrors(['student_number']);
        $this->assertNull(session('activation_student_id'));
    }

    public function test_step1_valid_student_proceeds_to_step2_email_form(): void
    {
        $student = Student::create([
            'student_number' => '23-0001',
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'program' => 'BSIT',
            'year_level' => '1st Year',
        ]);

        $response = $this->post(route('student.activate.student.submit'), [
            'student_number' => '23-0001',
        ]);

        $response->assertRedirect(route('student.activate.email'));
        $this->assertEquals($student->id, session('activation_student_id'));

        $emailPage = $this->get(route('student.activate.email'));
        $emailPage->assertStatus(200);
        $emailPage->assertSee('Provide Your Email');
        $emailPage->assertSee('Juan Dela Cruz');
    }

    public function test_step2_cannot_be_accessed_without_verifying_student_number(): void
    {
        $response = $this->get(route('student.activate.email'));
        $response->assertRedirect(route('student.activate'));
        $response->assertSessionHasErrors(['student_number']);
    }

    public function test_step2_submits_email_and_sends_otp(): void
    {
        Mail::fake();

        $student = Student::create([
            'student_number' => '23-0002',
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'program' => 'BSCS',
            'year_level' => '2nd Year',
        ]);

        $this->withSession([
            'activation_student_id' => $student->id,
            'activation_student_number' => $student->student_number,
        ]);

        $response = $this->post(route('student.activate.email.submit'), [
            'email' => 'maria.santos@student.com',
        ]);

        $response->assertRedirect(route('student.activate.verify'));
        $response->assertSessionHas('success');

        $student->refresh();
        $this->assertEquals('maria.santos@student.com', $student->email);

        $user = User::where('student_id', $student->id)->first();
        $this->assertNotNull($user);
        $this->assertEquals('maria.santos@student.com', $user->email);
        $this->assertFalse((bool) $user->is_activated);
        $this->assertNotNull($user->activation_otp);
        $this->assertNotNull($user->activation_otp_expires_at);

        Mail::assertSent(\Illuminate\Mail\Mailable::class, 0);
    }

    public function test_step3_and_step4_verify_otp_and_set_password_activates_account(): void
    {
        $student = Student::create([
            'student_number' => '23-0003',
            'first_name' => 'Pedro',
            'last_name' => 'Penduko',
            'email' => 'pedro@example.com',
            'program' => 'BSIT',
            'year_level' => '3rd Year',
        ]);

        $user = User::create([
            'name' => $student->display_name,
            'username' => $student->student_number,
            'email' => $student->email,
            'password' => Hash::make(Str::random(32)),
            'role' => 'student',
            'student_id' => $student->id,
            'is_active' => true,
            'is_activated' => false,
            'activation_otp' => Hash::make('123456'),
            'activation_otp_expires_at' => now()->addMinutes(10),
        ]);

        $this->withSession([
            'activation_user_id' => $user->id,
            'activation_student_id' => $student->id,
            'activation_email' => $student->email,
        ]);

        $badOtpResponse = $this->post(route('student.activate.verify.submit'), [
            'otp' => '999999',
        ]);
        $badOtpResponse->assertSessionHasErrors(['otp']);

        $goodOtpResponse = $this->post(route('student.activate.verify.submit'), [
            'otp' => '123456',
        ]);
        $goodOtpResponse->assertRedirect(route('student.activate.password'));

        $user->refresh();
        $this->assertNotNull($user->activation_token);
        $this->assertNull($user->activation_otp);

        $setPasswordResponse = $this->post(route('student.activate.complete'), [
            'password' => 'NewSecurePassword123!',
            'password_confirmation' => 'NewSecurePassword123!',
        ]);

        $setPasswordResponse->assertRedirect(route('login'));
        $setPasswordResponse->assertSessionHas('status');

        $user->refresh();
        $this->assertTrue((bool) $user->is_activated);
        $this->assertTrue(Hash::check('NewSecurePassword123!', $user->password));

        $loginResponse = $this->post(route('login'), [
            'email' => '23-0003',
            'password' => 'NewSecurePassword123!',
        ]);

        $loginResponse->assertRedirect(route('student.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_unactivated_student_cannot_login(): void
    {
        $student = Student::create([
            'student_number' => '23-0004',
            'first_name' => 'Clara',
            'last_name' => 'Reyes',
            'email' => 'clara@example.com',
            'program' => 'BSIT',
            'year_level' => '1st Year',
        ]);

        $user = User::create([
            'name' => $student->display_name,
            'username' => $student->student_number,
            'email' => $student->email,
            'password' => Hash::make('ValidPassword123!'),
            'role' => 'student',
            'student_id' => $student->id,
            'is_active' => true,
            'is_activated' => false,
        ]);

        $response = $this->post(route('login'), [
            'email' => '23-0004',
            'password' => 'ValidPassword123!',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
    }

    public function test_student_dashboard_only_shows_own_data(): void
    {
        $studentA = Student::create([
            'student_number' => '23-1111',
            'first_name' => 'Student',
            'last_name' => 'A',
            'email' => 'studenta@example.com',
            'program' => 'BSIT',
            'year_level' => '1st Year',
        ]);

        $userA = User::create([
            'name' => $studentA->display_name,
            'username' => $studentA->student_number,
            'email' => $studentA->email,
            'password' => Hash::make('password123'),
            'role' => 'student',
            'student_id' => $studentA->id,
            'is_active' => true,
            'is_activated' => true,
        ]);

        $studentB = Student::create([
            'student_number' => '23-2222',
            'first_name' => 'Student',
            'last_name' => 'B',
            'email' => 'studentb@example.com',
            'program' => 'BSIT',
            'year_level' => '2nd Year',
        ]);

        $response = $this->actingAs($userA)->get(route('student.dashboard'));
        $response->assertStatus(200);
        $response->assertSee('23-1111');
        $response->assertDontSee('23-2222');
    }

    public function test_admin_can_update_email_and_resend_activation(): void
    {
        Mail::fake();

        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'is_activated' => true,
        ]);

        $student = Student::create([
            'student_number' => '23-3333',
            'first_name' => 'Test',
            'last_name' => 'Student',
            'program' => 'BSIT',
            'year_level' => '1st Year',
        ]);

        $response = $this->actingAs($admin)->patch(route('admin.students.update-email', $student), [
            'email' => 'new.email@example.com',
        ]);

        $response->assertRedirect();
        $this->assertEquals('new.email@example.com', $student->fresh()->email);

        $resendResponse = $this->actingAs($admin)->post(route('admin.students.resend-activation', $student));
        $resendResponse->assertRedirect();
        $resendResponse->assertSessionHas('success');

        $user = User::where('student_id', $student->id)->first();
        $this->assertNotNull($user);
        $this->assertFalse((bool) $user->is_activated);
        $this->assertNotNull($user->activation_otp);
    }
}
