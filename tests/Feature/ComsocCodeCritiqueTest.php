<?php

namespace Tests\Feature;

use App\Models\CodeCritique;
use App\Models\CodeShowcase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComsocCodeCritiqueTest extends TestCase
{
    use RefreshDatabase;

    public function test_students_can_submit_code_for_peer_review_and_accept_solutions(): void
    {
        $author = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $reviewer = User::factory()->create(['role' => 'student', 'is_active' => true]);

        // 1. Author submits code showcase
        $res = $this->actingAs($author)
            ->postJson(route('critique.api.store'), [
                'title' => 'Secure Password Reset OTP Token Generator',
                'description' => 'Please review this cryptographic token generation logic for potential collisions.',
                'language' => 'php',
                'bounty_exp' => 50,
                'code_snippet' => 'public function generateOtp() { return str_pad(random_int(0, 999999), 6, "0", STR_PAD_LEFT); }',
            ])
            ->assertCreated();

        $showcaseId = $res->json('showcase_id');
        $this->assertNotNull($showcaseId);
        $showcase = CodeShowcase::findOrFail($showcaseId);
        $this->assertSame('open', $showcase->status);

        // 2. Reviewer posts critique
        $resCritique = $this->actingAs($reviewer)
            ->postJson(route('critique.api.reviews.store', $showcase), [
                'summary' => 'Logic is secure because random_int uses CSPRNG under the hood. Consider adding expiry validation check.',
                'suggested_diff' => '+ $tokenExpiry = now()->addMinutes(15);',
                'line_number' => 1,
            ])
            ->assertCreated();

        $critiqueId = $resCritique->json('critique.id');
        $critique = CodeCritique::findOrFail($critiqueId);
        $this->assertSame($reviewer->id, $critique->reviewer_id);
        $this->assertSame('in_review', $showcase->fresh()->status);

        // 3. Author accepts critique
        $this->actingAs($author)
            ->postJson(route('critique.api.reviews.accept', $critique))
            ->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $this->assertTrue($critique->fresh()->is_accepted);
        $this->assertSame('solved', $showcase->fresh()->status);
    }
}
