<?php

use App\Models\AcademicYear;
use App\Models\Event;

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$ay = AcademicYear::firstOrCreate(
    ['label' => 'A.Y. 2024-2025'],
    [
        'year_start' => 2024,
        'year_end' => 2025,
        'is_active' => true,
    ]
);

$admin = App\Models\User::first();

$event = Event::firstOrCreate(
    ['name' => 'ComSoc Tech Summit 2026'],
    [
        'academic_year_id' => $ay->id,
        'created_by' => $admin?->id,
        'description' => 'Annual Computing Society Tech Summit with keynote tech talks, hands-on workshops, and open hackathon.',
        'location' => 'Main University Auditorium & Hall A',
        'event_date' => now()->addDays(14)->toDateString(),
        'starts_at' => now()->addDays(14)->setTime(9, 0),
        'ends_at' => now()->addDays(14)->setTime(17, 0),
        'requires_registration' => true,
        'registration_deadline' => now()->addDays(13)->setTime(23, 59),
        'allow_non_students' => true,
        'is_published' => true,
    ]
);

echo "EVENT_ID=" . $event->id . PHP_EOL;
