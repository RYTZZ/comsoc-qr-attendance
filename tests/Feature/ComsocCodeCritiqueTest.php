<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
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
        AcademicYear::create([
            'label' => 'AY 2025–2026',
            'year_start' => 2025,
            'year_end' => 2026,
            'is_active' => true,
        ]);

        $author = User::factory()->create(['role' => 'student']);
        $reviewer = User::factory()->create(['role' => 'student']);
        $headers = ['X-Bypass-Coming-Soon' => 'true'];

        // 1. Submit showcase
        $showcaseRes = $this->actingAs($author)->postJson(route('critique.api.store'), [
            'title' => 'Binary Search Tree Implementation',
            'description' => 'Need advice on AVL balancing rotations and memory footprint in PHP.',
            'language' => 'php',
            'bounty_exp' => 75,
        ], $headers);

        $showcaseRes->assertStatus(201);
        $showcaseId = $showcaseRes->json('showcase_id');
        $showcase = CodeShowcase::find($showcaseId);
        $this->assertNotNull($showcase);
        $this->assertEquals(75, $showcase->bounty_exp);

        // 2. Author updates showcase
        $updateRes = $this->actingAs($author)->putJson(route('critique.api.update', $showcase), [
            'title' => 'Binary Search Tree AVL Balancing',
            'description' => 'Updated: Need advice on AVL balancing rotations in PHP with type hints.',
            'language' => 'php',
            'bounty_exp' => 75,
        ], $headers);
        $updateRes->assertStatus(200);
        $this->assertEquals('Binary Search Tree AVL Balancing', $showcase->fresh()->title);

        // 3. Post peer review
        $critiqueRes = $this->actingAs($reviewer)->postJson(route('critique.api.reviews.store', $showcase), [
            'summary' => 'You should check the node height recalculation after left-right double rotations.',
            'suggested_diff' => '+$this->updateHeight($node);',
        ], $headers);

        $critiqueRes->assertStatus(201);
        $critiqueId = $critiqueRes->json('critique_id');
        $critique = CodeCritique::find($critiqueId);
        $this->assertNotNull($critique);

        // 4. Accept solution and verify bounty EXP credited to reviewer
        $acceptRes = $this->actingAs($author)->postJson(route('critique.api.reviews.accept', $critique), [], $headers);
        $acceptRes->assertStatus(200);

        $reviewer->refresh();
        $this->assertEquals(75, $reviewer->getSpendableExp());

        // 5. Author can delete showcase
        $deleteRes = $this->actingAs($author)->deleteJson(route('critique.api.destroy', $showcase), [], $headers);
        $deleteRes->assertStatus(200);
        $this->assertNull(CodeShowcase::find($showcaseId));
    }
}
