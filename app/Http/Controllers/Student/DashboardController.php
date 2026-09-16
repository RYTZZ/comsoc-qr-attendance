<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\Incident;
use App\Services\QrPngRenderer;
use Illuminate\View\View;
use SimpleSoftwareIO\QrCode\Facades\QrCode as QrGenerator;

class DashboardController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        $student = $user->student;

        if (!$student) {
            return view('student.no-profile');
        }

        $activeYear = \App\Models\AcademicYear::active();
        $membership = $activeYear ? $student->membershipForYear($activeYear->id) : null;
        $activeQr = $membership?->activeQrCode;

        $recentAttendance = AttendanceRecord::where('qr_token', $activeQr?->token)
            ->with(['event', 'attendanceSession'])
            ->orderByDesc('scanned_at')
            ->take(10)
            ->get();

        return view('student.dashboard', compact('student', 'membership', 'activeQr', 'recentAttendance', 'activeYear'));
    }

    public function qr(): View
    {
        $user = auth()->user();
        $student = $user->student;
        $activeYear = \App\Models\AcademicYear::active();
        $membership = $activeYear ? $student->membershipForYear($activeYear->id) : null;
        $activeQr = $membership?->activeQrCode;

        abort_unless($activeQr, 404, 'No active QR code found.');

        $qrSvg = QrGenerator::format('svg')
            ->size(250)
            ->margin(1)
            ->generate($activeQr->token);

        return view('student.qr', compact('student', 'activeQr', 'qrSvg', 'membership'));
    }

    public function downloadQr()
    {
        $user = auth()->user();
        $student = $user->student;
        $activeYear = \App\Models\AcademicYear::active();
        $membership = $activeYear ? $student->membershipForYear($activeYear->id) : null;
        $activeQr = $membership?->activeQrCode;

        abort_unless($activeQr, 404);

        $qrImage = QrPngRenderer::generate($activeQr->token, 300, 1);

        return response($qrImage)
            ->header('Content-Type', 'image/png')
            ->header('Content-Disposition', 'attachment; filename="my_qr_' . $student->student_number . '.png"');
    }

    public function downloadCard(\Illuminate\Http\Request $request)
    {
        $user = auth()->user();
        $student = $user->student;
        $activeYear = \App\Models\AcademicYear::active();
        $membership = $activeYear ? $student->membershipForYear($activeYear->id) : null;

        abort_unless($membership, 404, 'No membership found.');

        $format = strtolower($request->query('format', 'png'));
        $service = app(\App\Services\MemberCardService::class);
        $baseName = sanitize_filename("{$student->student_number}_{$student->last_name}_card");

        if ($format === 'jpg' || $format === 'jpeg') {
            $data = $service->generateJpeg($membership);
            return response($data, 200, [
                'Content-Type' => 'image/jpeg',
                'Content-Disposition' => "attachment; filename=\"{$baseName}.jpg\"",
            ]);
        }

        if ($format === 'pdf') {
            $data = $service->generatePdf($membership);
            return response($data, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => "attachment; filename=\"{$baseName}.pdf\"",
            ]);
        }

        $data = $service->generatePng($membership);
        return response($data, 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => "attachment; filename=\"{$baseName}.png\"",
        ]);
    }

    public function attendance(): View
    {
        $user = auth()->user();
        $student = $user->student;
        $activeYear = \App\Models\AcademicYear::active();
        $membership = $activeYear ? $student->membershipForYear($activeYear->id) : null;
        $activeQr = $membership?->activeQrCode;

        $records = $activeQr
            ? AttendanceRecord::where('qr_token', $activeQr->token)
                ->with(['event', 'attendanceSession'])
                ->orderByDesc('scanned_at')
                ->paginate(20)
            : collect();

        return view('student.attendance', compact('records', 'student'));
    }

    public function incidents(): View
    {
        $user = auth()->user();

        $incidents = Incident::where(function ($q) use ($user) {
            $q->where('reported_by', $user->id)
              ->orWhere(function ($q2) use ($user) {
                  if ($user->student) {
                      $q2->where('subject_type', \App\Models\Student::class)
                         ->where('subject_id', $user->student->id);
                  }
              });
        })->orderByDesc('created_at')->paginate(15);

        return view('student.incidents', compact('incidents'));
    }
}
