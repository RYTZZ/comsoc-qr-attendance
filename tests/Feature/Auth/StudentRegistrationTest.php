<?php

namespace Tests\Feature\Auth;

use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_signup_screen_can_be_rendered(): void
    {
        $response = $this->get('/signup');

        $response->assertStatus(200);
        $response->assertSee('Student Registration');
        $response->assertSee('Academic Program');
        $response->assertSee('Bachelor of Science in Information Technology');
        $response->assertDontSee('<select', false);
    }

    public function test_student_signup_screen_has_predictable_responsive_grids(): void
    {
        $response = $this->get('/signup');

        $response->assertStatus(200);
        // Step 1: explicit responsive grid structures
        $response->assertSee('academic-name-grid', false);
        $response->assertSee('academic-cohort-grid', false);
        $response->assertSee('Student ID / Student Number', false);
        $response->assertSee('name="student_id"', false);
        $response->assertSee('name="first_name"', false);
        $response->assertSee('name="middle_name"', false);
        $response->assertSee('name="last_name"', false);
        $response->assertSee("name: 'program'", false);
        $response->assertSee("name: 'year'", false);
        $response->assertSee("name: 'block'", false);

        // Step 2: responsive avatar selector grid and security fields
        $response->assertSee('avatar-grid-responsive', false);
        $response->assertSee('security-passwords-grid', false);
        $response->assertSee('Adventurer Character Avatar options', false);
        $response->assertSee('Felix', false);
        $response->assertSee('Silas', false);
    }

    public function test_student_can_register_with_valid_details(): void
    {
        AcademicYear::create([
            'label' => 'AY 2026-2027',
            'year_start' => 2026,
            'year_end' => 2027,
            'is_active' => true,
        ]);

        $response = $this->post('/signup', [
            'student_id' => '21-00123',
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'middle_name' => 'Santos',
            'email' => 'juan.delacruz@sorsu.edu.ph',
            'program' => 'Bachelor of Science in Information Technology',
            'year' => '3rd',
            'block' => '2',
            'password' => 'SecurePass123',
            'password_confirmation' => 'SecurePass123',
            'terms' => '1',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();

        $this->assertDatabaseHas('students', [
            'student_number' => '21-00123',
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'middle_name' => 'Santos',
            'email' => 'juan.delacruz@sorsu.edu.ph',
            'program' => 'Bachelor of Science in Information Technology',
            'year_level' => '3rd Year',
            'block' => '2',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'juan.delacruz@sorsu.edu.ph',
            'role' => 'student',
            'is_active' => true,
        ]);

        $user = User::where('email', 'juan.delacruz@sorsu.edu.ph')->first();
        $this->assertNotNull($user->student_id);
        $this->assertNotNull($user->profile);
    }

    public function test_duplicate_student_id_is_rejected(): void
    {
        Student::create([
            'student_number' => '21-00999',
            'first_name' => 'Existing',
            'last_name' => 'Student',
            'email' => 'existing@sorsu.edu.ph',
            'program' => 'Bachelor of Science in Information Technology',
            'year_level' => '1st Year',
            'block' => '1',
        ]);

        $response = $this->from('/signup')->post('/signup', [
            'student_id' => '21-00999',
            'first_name' => 'Another',
            'last_name' => 'Student',
            'email' => 'another@sorsu.edu.ph',
            'program' => 'Bachelor of Science in Computer Science',
            'year' => '1st',
            'block' => '1',
            'password' => 'SecurePass123',
            'password_confirmation' => 'SecurePass123',
            'terms' => '1',
        ]);

        $response->assertRedirect('/signup');
        $response->assertSessionHasErrors(['student_id']);
    }

    public function test_invalid_program_or_year_or_block_is_rejected(): void
    {
        $response = $this->from('/signup')->post('/signup', [
            'student_id' => '21-00456',
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@sorsu.edu.ph',
            'program' => 'Bachelor of Fake Arts',
            'year' => '5th',
            'block' => '9',
            'password' => 'SecurePass123',
            'password_confirmation' => 'SecurePass123',
            'terms' => '1',
        ]);

        $response->assertRedirect('/signup');
        $response->assertSessionHasErrors(['program', 'year', 'block']);
    }

    public function test_password_confirmation_mismatch_is_rejected(): void
    {
        $response = $this->from('/signup')->post('/signup', [
            'student_id' => '21-00789',
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'mismatch@sorsu.edu.ph',
            'program' => 'Bachelor of Science in Information Systems',
            'year' => '2nd',
            'block' => '1',
            'password' => 'SecurePass123',
            'password_confirmation' => 'DifferentPassword123',
            'terms' => '1',
        ]);

        $response->assertRedirect('/signup');
        $response->assertSessionHasErrors(['password']);
    }

    public function test_student_id_with_more_than_eight_digits_is_rejected(): void
    {
        $response = $this->from('/signup')->post('/signup', [
            'student_id' => '2024-12345',
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'email' => 'juan.test@sorsu.edu.ph',
            'program' => 'Bachelor of Science in Information Technology',
            'year' => '1st',
            'block' => '1',
            'password' => 'SecurePass123',
            'password_confirmation' => 'SecurePass123',
            'terms' => '1',
        ]);

        $response->assertRedirect('/signup');
        $response->assertSessionHasErrors(['student_id']);
    }

    public function test_student_id_with_invalid_characters_is_rejected(): void
    {
        $response = $this->from('/signup')->post('/signup', [
            'student_id' => '21-0012A',
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'email' => 'juan.letters@sorsu.edu.ph',
            'program' => 'Bachelor of Science in Information Technology',
            'year' => '1st',
            'block' => '1',
            'password' => 'SecurePass123',
            'password_confirmation' => 'SecurePass123',
            'terms' => '1',
        ]);

        $response->assertRedirect('/signup');
        $response->assertSessionHasErrors(['student_id']);
    }

    public function test_student_id_with_eight_digits_and_leading_zeros_is_accepted(): void
    {
        AcademicYear::create([
            'label' => 'AY 2026-2027',
            'year_start' => 2026,
            'year_end' => 2027,
            'is_active' => true,
        ]);

        $response = $this->post('/signup', [
            'student_id' => '0024-0001',
            'first_name' => 'Zero',
            'last_name' => 'Lead',
            'email' => 'zero.lead@sorsu.edu.ph',
            'program' => 'Bachelor of Science in Information Technology',
            'year' => '1st',
            'block' => '1',
            'password' => 'SecurePass123',
            'password_confirmation' => 'SecurePass123',
            'terms' => '1',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertDatabaseHas('students', [
            'student_number' => '0024-0001',
        ]);
    }
}
