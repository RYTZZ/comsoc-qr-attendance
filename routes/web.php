<?php

use App\Http\Controllers\Admin\AcademicYearController;
use App\Http\Controllers\Admin\AttendanceController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\CardController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EventController;
use App\Http\Controllers\Admin\EventRegistrationController;
use App\Http\Controllers\Admin\IncidentController;
use App\Http\Controllers\Admin\KioskController;
use App\Http\Controllers\Admin\MasterlistController;
use App\Http\Controllers\Admin\MembershipController;
use App\Http\Controllers\Admin\OrganizationController;
use App\Http\Controllers\Admin\ProgramController;
use App\Http\Controllers\Admin\QrCodeController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Kiosk\AttendanceKioskController;
use App\Http\Controllers\Kiosk\SnackKioskController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicRegistrationController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => redirect()->route('login'));

Route::get('/dashboard', function () {
    $user = auth()->user();
    if (!$user) return redirect()->route('login');
    if (!$user->is_active) {
        auth('web')->logout();
        return redirect()->route('login')->withErrors(['email' => 'Your account has been deactivated.']);
    }
    if ($user->role === 'kiosk') {
        if ($user->kiosk) {
            return redirect()->route('kiosk.attendance', $user->kiosk);
        }
        return redirect()->route('kiosk.hub');
    }
    return redirect()->route('admin.dashboard');
})->middleware(['auth'])->name('dashboard');

Route::get('/register', [PublicRegistrationController::class, 'index'])->name('public.register');
Route::post('/register', [PublicRegistrationController::class, 'store'])
    ->middleware('throttle:15,1')
    ->name('public.register.store');
Route::get('/register/success', [PublicRegistrationController::class, 'success'])->name('public.register.success');

Route::get('/events/{event}/register', [PublicRegistrationController::class, 'show'])->name('public.register.event');
Route::post('/events/{event}/register', [PublicRegistrationController::class, 'store'])
    ->middleware('throttle:15,1')
    ->name('public.register.event.store');
Route::get('/events/{event}/register/success', [PublicRegistrationController::class, 'success'])->name('public.register.event.success');

Route::get('/register-non-student', function () {
    return redirect()->route('public.register');
})->name('public.register.latest');

Route::get('/kiosk/health', [AttendanceKioskController::class, 'healthCheck'])->name('kiosk.health');
Route::get('/kiosk', fn() => redirect()->route('login')->withErrors(['email' => 'No kiosk terminal is assigned to your account. Please contact the administrator.']))->name('kiosk.hub');

require __DIR__ . '/auth.php';

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::middleware(['role:kiosk,staff,treasurer,admin,super_admin'])->group(function () {
        Route::get('/kiosk/{kiosk}', [AttendanceKioskController::class, 'show'])->name('kiosk.attendance');
        Route::post('/kiosk/{kiosk}/scan', [AttendanceKioskController::class, 'scan'])->name('kiosk.scan');
        Route::get('/kiosk/{kiosk}/snack', [SnackKioskController::class, 'show'])->name('kiosk.snack');
        Route::post('/kiosk/{kiosk}/snack/scan', [SnackKioskController::class, 'scan'])->name('kiosk.snack.scan');

        Route::prefix('staff/incidents')->name('staff.incidents.')->group(function () {
            Route::get('/create', [\App\Http\Controllers\Staff\IncidentController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Staff\IncidentController::class, 'store'])->name('store');
            Route::get('/my', [\App\Http\Controllers\Staff\IncidentController::class, 'myIncidents'])->name('my');
            Route::get('/{incident}', [\App\Http\Controllers\Staff\IncidentController::class, 'show'])->name('show');
        });
    });

    Route::middleware(['role:admin,super_admin,treasurer'])->prefix('admin')->name('admin.')->group(function () {

        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::middleware(['role:super_admin'])->group(function () {
            Route::resource('academic-years', AcademicYearController::class)->except(['show']);
            Route::post('academic-years/{academicYear}/activate', [AcademicYearController::class, 'activate'])->name('academic-years.activate');

            Route::get('masterlist/upload', [MasterlistController::class, 'create'])->name('masterlist.upload');
            Route::post('masterlist/preview', [MasterlistController::class, 'preview'])->name('masterlist.preview');
            Route::post('masterlist/import', [MasterlistController::class, 'import'])->name('masterlist.import');

            Route::post('qr-codes/generate', [QrCodeController::class, 'generate'])->name('qr-codes.generate');
            Route::post('qr-codes/{qrCode}/revoke', [QrCodeController::class, 'revoke'])->name('qr-codes.revoke');
            Route::post('qr-codes/{qrCode}/reissue', [QrCodeController::class, 'reissue'])->name('qr-codes.reissue');
            Route::get('qr-codes/download', [QrCodeController::class, 'download'])->name('qr-codes.download');

            Route::patch('attendance/{record}/correct', [AttendanceController::class, 'correct'])->name('attendance.correct');
            Route::delete('attendance/{record}', [AttendanceController::class, 'destroy'])->name('attendance.destroy');

            Route::post('incidents/{incident}/resolve', [IncidentController::class, 'resolve'])->name('incidents.resolve');

            Route::resource('users', UserController::class)->except(['show']);
            Route::post('users/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('users.toggle-active');
            Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');

            Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
            Route::get('audit-logs/{auditLog}', [AuditLogController::class, 'show'])->name('audit-logs.show');
            Route::resource('events', EventController::class)->except(['index', 'show']);
            Route::get('events/{event}/configure', [EventController::class, 'configure'])->name('events.configure');
            Route::post('events/{event}/configure', [EventController::class, 'saveConfiguration'])->name('events.configure.save');
            Route::post('events/{event}/publish', [EventController::class, 'publish'])->name('events.publish');
            Route::post('events/{event}/close-registration', [EventController::class, 'closeRegistration'])->name('events.close-registration');
            Route::post('events/{event}/complete', [EventController::class, 'complete'])->name('events.complete');
            Route::post('events/{event}/archive', [EventController::class, 'archive'])->name('events.archive');
            Route::post('events/{event}/sessions', [EventController::class, 'storeSession'])->name('events.sessions.store');
            Route::post('events/{event}/snack-sessions', [EventController::class, 'storeSnackSession'])->name('events.snack-sessions.store');
        });

        Route::resource('students', StudentController::class)->only(['index', 'show', 'update']);
        Route::patch('students/{student}/email', [StudentController::class, 'updateEmail'])->name('students.update-email');

        Route::get('memberships', [MembershipController::class, 'index'])->name('memberships.index');
        Route::post('memberships/bulk-activate', [MembershipController::class, 'bulkActivate'])->name('memberships.bulk-activate');
        Route::post('memberships/generate-missing-qrs', [MembershipController::class, 'generateMissingQrs'])->name('memberships.generate-missing-qrs');
        Route::post('memberships/{membership}/activate', [MembershipController::class, 'activate'])->name('memberships.activate');
        Route::post('memberships/{membership}/deactivate', [MembershipController::class, 'deactivate'])->name('memberships.deactivate');
        Route::post('memberships/{membership}/generate-qr', [MembershipController::class, 'generateQr'])->name('memberships.generate-qr');

        Route::get('qr-codes', [QrCodeController::class, 'index'])->name('qr-codes.index');

        Route::middleware(['role:admin,super_admin,treasurer'])->group(function () {
            Route::get('cards', [CardController::class, 'index'])->name('cards.index');
            Route::get('cards/{card}/download', [CardController::class, 'download'])->name('cards.download');
            Route::post('cards/{card}/claim', [CardController::class, 'markClaimed'])->name('cards.claim');
            Route::post('cards/{card}/lost', [CardController::class, 'markLost'])->name('cards.lost');
            Route::post('cards/{card}/reissue', [CardController::class, 'reissue'])->name('cards.reissue');
        });

        Route::get('events', [EventController::class, 'index'])->name('events.index');
        Route::get('events/{event}', [EventController::class, 'show'])->name('events.show');

        Route::get('registrations', [EventRegistrationController::class, 'allRegistrations'])->name('event-registrations.all');
        Route::get('events/{event}/registrations', [EventRegistrationController::class, 'index'])->name('event-registrations.index');
        Route::get('event-registrations/{registration}', [EventRegistrationController::class, 'show'])->name('event-registrations.show');
        Route::post('event-registrations/{registration}/approve', [EventRegistrationController::class, 'approve'])->name('event-registrations.approve');
        Route::post('event-registrations/{registration}/reject', [EventRegistrationController::class, 'reject'])->name('event-registrations.reject');
        Route::get('event-registrations/{registration}/qr', [EventRegistrationController::class, 'downloadQr'])->name('event-registrations.qr');

        Route::resource('organizations', OrganizationController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::post('organizations/{organization}/toggle-active', [OrganizationController::class, 'toggleActive'])->name('organizations.toggle-active');

        Route::resource('programs', ProgramController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::post('programs/{program}/toggle-active', [ProgramController::class, 'toggleActive'])->name('programs.toggle-active');

        Route::get('attendance', [AttendanceController::class, 'index'])->name('attendance.index');

        Route::get('incidents', [IncidentController::class, 'index'])->name('incidents.index');
        Route::get('incidents/{incident}', [IncidentController::class, 'show'])->name('incidents.show');

        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/membership', [ReportController::class, 'membership'])->name('reports.membership');
        Route::get('reports/attendance', [ReportController::class, 'attendance'])->name('reports.attendance');
        Route::get('reports/qr-card', [ReportController::class, 'qrCard'])->name('reports.qr-card');
        Route::get('reports/snacks', [ReportController::class, 'snacks'])->name('reports.snacks');
        Route::get('reports/incidents', [ReportController::class, 'incidents'])->name('reports.incidents');

        Route::resource('kiosks', KioskController::class)->only(['index', 'store', 'update', 'destroy']);
    });
});
