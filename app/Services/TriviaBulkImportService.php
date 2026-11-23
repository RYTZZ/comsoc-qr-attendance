<?php

namespace App\Services;

use App\Models\TriviaQuestion;
use App\Support\TriviaSchema;
use Illuminate\Support\Facades\DB;

class TriviaBulkImportService
{
    /**
     * Parse raw pasted text into structured trivia question items with validation and duplicate checks.
     *
     * @return array{items: array<int, array<string, mixed>>, summary: array<string, int>}
     */
    public function parse(string $rawText): array
    {
        TriviaSchema::ensureTables();

        $blocks = $this->splitIntoBlocks($rawText);
        $existingNormalized = TriviaQuestion::query()
            ->pluck('question')
            ->map(fn ($q) => $this->normalizeText((string) $q))
            ->filter()
            ->values()
            ->all();

        $seenInBatch = [];
        $items = [];
        $validCount = 0;
        $dupCount = 0;
        $invalidCount = 0;

        foreach ($blocks as $idx => $block) {
            $parsed = $this->parseBlock($block, $idx + 1);
            $errors = $this->validateItem($parsed);

            $norm = $this->normalizeText($parsed['question']);
            $isDuplicate = false;

            if ($norm !== '') {
                if (in_array($norm, $seenInBatch, true)) {
                    $isDuplicate = true;
                    $errors[] = 'Duplicate question within pasted batch.';
                } elseif ($this->matchesExisting($norm, $existingNormalized)) {
                    $isDuplicate = true;
                    $errors[] = 'Question already exists in the database.';
                }
                $seenInBatch[] = $norm;
            }

            $status = $isDuplicate ? 'duplicate' : (empty($errors) ? 'valid' : 'invalid');
            if ($status === 'valid') {
                $validCount++;
            } elseif ($status === 'duplicate') {
                $dupCount++;
            } else {
                $invalidCount++;
            }

            $items[] = array_merge($parsed, [
                'index' => $idx + 1,
                'status' => $status,
                'is_duplicate' => $isDuplicate,
                'selected' => $status === 'valid',
                'errors' => $errors,
            ]);
        }

        return [
            'items' => $items,
            'summary' => [
                'detected' => count($items),
                'valid' => $validCount,
                'duplicates' => $dupCount,
                'invalid' => $invalidCount,
            ],
        ];
    }

    /**
     * Import validated, selected questions into the database question bank.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @param  array<string, mixed>  $batchConfig
     * @return array{detected: int, imported: int, duplicates_skipped: int, invalid_skipped: int}
     */
    public function import(array $items, array $batchConfig, ?string $createdBy = null): array
    {
        TriviaSchema::ensureTables();

        $detected = count($items);
        $imported = 0;
        $duplicatesSkipped = 0;
        $invalidSkipped = 0;

        $existingNormalized = TriviaQuestion::query()
            ->pluck('question')
            ->map(fn ($q) => $this->normalizeText((string) $q))
            ->filter()
            ->values()
            ->all();

        $seenInBatch = [];
        $points = max(1, min(100, (int) ($batchConfig['points'] ?? 15)));
        $feedbackMode = in_array($batchConfig['feedback_mode'] ?? '', array_keys(TriviaQuestion::FEEDBACK_MODES), true)
            ? $batchConfig['feedback_mode']
            : 'immediate';
        $status = in_array($batchConfig['status'] ?? '', ['draft', 'published'], true)
            ? $batchConfig['status']
            : 'published';

        DB::transaction(function () use (
            $items,
            $points,
            $feedbackMode,
            $status,
            $createdBy,
            &$existingNormalized,
            &$seenInBatch,
            &$imported,
            &$duplicatesSkipped,
            &$invalidSkipped
        ) {
            foreach ($items as $raw) {
                if (empty($raw['selected'])) {
                    continue;
                }

                $item = [
                    'question' => trim((string) ($raw['question'] ?? '')),
                    'options' => array_values(array_map(fn ($o) => trim((string) $o), (array) ($raw['options'] ?? []))),
                    'correct_index' => isset($raw['correct_index']) ? (int) $raw['correct_index'] : -1,
                    'category' => $this->resolveCategory((string) ($raw['category'] ?? 'general_tech')),
                    'difficulty' => $this->resolveDifficulty((string) ($raw['difficulty'] ?? 'medium')),
                    'explanation' => trim((string) ($raw['explanation'] ?? '')),
                ];

                $errors = $this->validateItem($item);
                if (! empty($errors)) {
                    $invalidSkipped++;
                    continue;
                }

                $norm = $this->normalizeText($item['question']);
                if (in_array($norm, $seenInBatch, true) || $this->matchesExisting($norm, $existingNormalized)) {
                    $duplicatesSkipped++;
                    continue;
                }

                TriviaQuestion::create([
                    'question' => $item['question'],
                    'category' => $item['category'],
                    'difficulty' => $item['difficulty'],
                    'options' => $item['options'],
                    'correct_index' => $item['correct_index'],
                    'explanation' => $item['explanation'] !== '' ? $item['explanation'] : null,
                    'points' => $points,
                    'usage_count' => 0,
                    'is_ai_generated' => false,
                    'is_active' => true,
                    'feedback_mode' => $feedbackMode,
                    'status' => $status,
                    'created_by' => $createdBy,
                ]);

                $seenInBatch[] = $norm;
                $existingNormalized[] = $norm;
                $imported++;
            }
        });

        return [
            'detected' => $detected,
            'imported' => $imported,
            'duplicates_skipped' => $duplicatesSkipped,
            'invalid_skipped' => $invalidSkipped,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function splitIntoBlocks(string $rawText): array
    {
        $normalized = str_replace(["\r\n", "\r"], "\n", trim($rawText));
        if ($normalized === '') {
            return [];
        }

        // Split before lines starting with Q1., Question 1:, or 1.
        $parts = preg_split('/(?=^(?:Q(?:uestion)?\s*\d+|\d+)\s*[\.:]\s+)/im', $normalized, -1, PREG_SPLIT_NO_EMPTY);

        return array_values(array_filter(array_map('trim', $parts ?: [])));
    }

    /**
     * @return array<string, mixed>
     */
    private function parseBlock(string $block, int $fallbackNum): array
    {
        $lines = explode("\n", $block);
        $questionLines = [];
        $options = ['', '', '', ''];
        $correctIndex = -1;
        $category = 'general_tech';
        $difficulty = 'medium';
        $explanation = '';
        $inOptionsOrMeta = false;

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '') {
                continue;
            }

            if (preg_match('/^([A-D])\s*[\.\)]\s*(.+)$/i', $trimmed, $m)) {
                $inOptionsOrMeta = true;
                $idx = ord(strtoupper($m[1])) - ord('A');
                $options[$idx] = trim($m[2]);
                continue;
            }

            if (preg_match('/^(?:Correct\s+Answer|Answer|Correct)\s*:\s*([A-D])\b/i', $trimmed, $m)) {
                $inOptionsOrMeta = true;
                $correctIndex = ord(strtoupper($m[1])) - ord('A');
                continue;
            }

            if (preg_match('/^Category\s*:\s*(.+)$/i', $trimmed, $m)) {
                $inOptionsOrMeta = true;
                $category = $this->resolveCategory($m[1]);
                continue;
            }

            if (preg_match('/^Difficulty\s*:\s*(.+)$/i', $trimmed, $m)) {
                $inOptionsOrMeta = true;
                $difficulty = $this->resolveDifficulty($m[1]);
                continue;
            }

            if (preg_match('/^Explanation\s*:\s*(.+)$/i', $trimmed, $m)) {
                $inOptionsOrMeta = true;
                $explanation = trim($m[1]);
                continue;
            }

            if (! $inOptionsOrMeta) {
                $cleaned = preg_replace('/^(?:Q(?:uestion)?\s*\d+|\d+)\s*[\.:]\s*/i', '', $trimmed) ?? $trimmed;
                $questionLines[] = trim($cleaned);
            }
        }

        return [
            'question' => trim(implode(' ', $questionLines)),
            'options' => $options,
            'correct_index' => $correctIndex,
            'category' => $category,
            'difficulty' => $difficulty,
            'explanation' => $explanation,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function validateItem(array $item): array
    {
        $errors = [];
        if (mb_strlen($item['question'] ?? '') < 10) {
            $errors[] = 'Question text must be at least 10 characters.';
        }

        $opts = $item['options'] ?? [];
        $nonEmpty = array_filter($opts, fn ($o) => trim((string) $o) !== '');
        if (count($nonEmpty) !== 4) {
            $errors[] = 'All four answer choices (A, B, C, D) are required.';
        } elseif (count(array_unique(array_map('mb_strtolower', $nonEmpty))) !== 4) {
            $errors[] = 'All four answer choices must be distinct.';
        }

        $ci = (int) ($item['correct_index'] ?? -1);
        if ($ci < 0 || $ci > 3) {
            $errors[] = 'Correct answer must be A, B, C, or D.';
        }

        if (! array_key_exists($item['category'] ?? '', TriviaQuestion::CATEGORIES)) {
            $errors[] = 'Invalid category.';
        }

        if (! in_array($item['difficulty'] ?? '', TriviaQuestion::DIFFICULTIES, true)) {
            $errors[] = 'Difficulty must be Easy, Medium, or Hard.';
        }

        return $errors;
    }

    public function resolveCategory(string $raw): string
    {
        $clean = strtolower(trim($raw));
        if (array_key_exists($clean, TriviaQuestion::CATEGORIES)) {
            return $clean;
        }

        foreach (TriviaQuestion::CATEGORIES as $key => $label) {
            if (strtolower($label) === $clean) {
                return $key;
            }
        }

        return match (true) {
            str_contains($clean, 'web'), str_contains($clean, 'software') => 'software_development',
            str_contains($clean, 'program'), str_contains($clean, 'coding') => 'programming',
            str_contains($clean, 'cyber'), str_contains($clean, 'security') => 'cybersecurity',
            str_contains($clean, 'ai'), str_contains($clean, 'artificial'), str_contains($clean, 'machine') => 'artificial_intelligence',
            str_contains($clean, 'network') => 'networking',
            str_contains($clean, 'computer science'), $clean === 'cs' => 'computer_science',
            str_contains($clean, 'information tech'), $clean === 'it' => 'information_technology',
            str_contains($clean, 'digital'), str_contains($clean, 'innovation') => 'digital_innovation',
            str_contains($clean, 'knowledge') => 'general_knowledge',
            default => 'general_tech',
        };
    }

    public function resolveDifficulty(string $raw): string
    {
        $clean = strtolower(trim($raw));

        return in_array($clean, TriviaQuestion::DIFFICULTIES, true) ? $clean : 'medium';
    }

    private function normalizeText(string $text): string
    {
        $clean = preg_replace('/[^a-z0-9\s]/', '', mb_strtolower($text)) ?? '';

        return trim(preg_replace('/\s+/', ' ', $clean) ?? '');
    }

    /**
     * @param  array<int, string>  $existingNormalized
     */
    private function matchesExisting(string $norm, array $existingNormalized): bool
    {
        if (in_array($norm, $existingNormalized, true)) {
            return true;
        }

        foreach ($existingNormalized as $existing) {
            similar_text($norm, $existing, $percent);
            if ($percent >= 88.0) {
                return true;
            }
        }

        return false;
    }
}
