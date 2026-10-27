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
use App\Http\Controllers\Admin\WebsiteManagementController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicRegistrationController;
use Illuminate\Support\Facades\Route;

Route::get('/privacy', function () {
    return view('privacy');
})->name('privacy');
Route::get('/privacy-notice', fn() => redirect()->route('privacy'));

Route::get('/', function () {
    if (!auth()->check()) {
        return redirect()->route('login');
    }
    return redirect()->route('dashboard');
})->name('home');
Route::get('/terms-of-use', fn() => redirect()->route('privacy'))->name('public.terms');
Route::get('/terms', fn() => redirect()->route('privacy'));
Route::get('/about', fn() => redirect()->route('home'));
Route::get('/officers', fn() => redirect()->route('home'));
Route::get('/activities', fn() => redirect()->route('home'));
Route::get('/activities/{slug}', fn() => redirect()->route('home'));
Route::get('/achievements', fn() => redirect()->route('home'));
Route::get('/gallery', fn() => redirect()->route('home'));
Route::get('/partners', fn() => redirect()->route('home'));

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
    if ($user->role === 'student') {
        return app(\App\Http\Controllers\MemberDashboardController::class)->index(request());
    }
    return redirect()->route('admin.dashboard');
})->middleware(['auth'])->name('dashboard');

Route::get('/register', [PublicRegistrationController::class, 'index'])->name('public.register');
Route::name('register')->get('/register-alias', fn() => redirect()->route('public.register'));
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

Route::middleware(['auth'])->group(function () {
    Route::get('/onboarding/adventurer', [\App\Http\Controllers\Onboarding\AdventurerSetupController::class, 'show'])->name('onboarding.adventurer');
    Route::post('/onboarding/adventurer', [\App\Http\Controllers\Onboarding\AdventurerSetupController::class, 'update'])->name('onboarding.adventurer.update');
    Route::get('/onboarding/adventurer/check-username', [\App\Http\Controllers\Onboarding\AdventurerSetupController::class, 'checkUsername'])
        ->middleware('throttle:30,1')
        ->name('onboarding.adventurer.check');
});

Route::get('/api/check-username', [\App\Http\Controllers\Onboarding\AdventurerSetupController::class, 'checkUsername'])
    ->middleware('throttle:60,1')
    ->name('api.check-username');

require __DIR__ . '/auth.php';

Route::middleware(['auth', 'verified', 'require_password_change'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/profile/sessions', [\App\Http\Controllers\UserSessionController::class, 'index'])->name('profile.sessions');
    Route::get('/profile/sessions/poll', [\App\Http\Controllers\UserSessionController::class, 'pollAlerts'])->name('profile.sessions.poll');
    Route::delete('/profile/sessions/{session}', [\App\Http\Controllers\UserSessionController::class, 'destroy'])
        ->middleware('throttle:30,1')
        ->name('profile.sessions.destroy');

    // Dedicated Settings Routes
    Route::get('/settings', [\App\Http\Controllers\SettingsController::class, 'index'])->name('settings.index');
    Route::patch('/settings/profile', [\App\Http\Controllers\SettingsController::class, 'updateProfile'])->name('settings.profile.update');
    Route::patch('/settings/notifications', [\App\Http\Controllers\SettingsController::class, 'updateNotifications'])->name('settings.notifications.update');
    Route::put('/settings/password', [\App\Http\Controllers\SettingsController::class, 'updatePassword'])->name('settings.password.update');

    // Student signature (own account only; no ids accepted from the client)
    Route::prefix('signature')->name('signature.')->group(function () {
        Route::get('/', [\App\Http\Controllers\MemberSignatureController::class, 'show'])->name('show');
        Route::get('/status', [\App\Http\Controllers\MemberSignatureController::class, 'status'])->name('status');
        Route::get('/preview', [\App\Http\Controllers\MemberSignatureController::class, 'preview'])
            ->middleware('throttle:30,1')->name('preview');
        Route::post('/', [\App\Http\Controllers\MemberSignatureController::class, 'store'])
            ->middleware('throttle:10,1')->name('store');
        Route::put('/', [\App\Http\Controllers\MemberSignatureController::class, 'replace'])
            ->middleware('throttle:10,1')->name('replace');
        Route::delete('/', [\App\Http\Controllers\MemberSignatureController::class, 'reset'])
            ->middleware('throttle:10,1')->name('reset');
    });


    // Official attendance sheet export (authorized per event via the export-attendance-sheet gate)
    Route::get('/attendance-sheets/{event}/check', [\App\Http\Controllers\AttendanceSheetController::class, 'check'])
        ->middleware('throttle:30,1')->name('attendance-sheet.check');
    Route::get('/attendance-sheets/{event}/download', [\App\Http\Controllers\AttendanceSheetController::class, 'download'])
        ->middleware('throttle:10,1')->name('attendance-sheet.download');

    // Dynamic QR Attendance Pass
    Route::get('/attendance/my-qr', [\App\Http\Controllers\MemberQrCodeController::class, 'show'])->name('member.dynamic_qr');
    Route::post('/attendance/my-qr/refresh', [\App\Http\Controllers\MemberQrCodeController::class, 'refresh'])
        ->middleware('throttle:60,1')
        ->name('member.dynamic_qr.refresh');

    Route::get('/events', [\App\Http\Controllers\MemberEventController::class, 'index'])->name('events.index');
    Route::get('/events/{event}', [\App\Http\Controllers\MemberEventController::class, 'show'])->name('events.show');
    Route::post('/events/{event}/join', [\App\Http\Controllers\MemberEventController::class, 'register'])->name('events.join');
    Route::get('/events/{event}/evaluation', [\App\Http\Controllers\MemberEvaluationController::class, 'show'])->name('events.evaluation.show');
    Route::post('/events/{event}/evaluation', [\App\Http\Controllers\MemberEvaluationController::class, 'store'])->name('events.evaluation.store');

    Route::get('/tickets', [\App\Http\Controllers\MemberTicketController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/{ticket}', [\App\Http\Controllers\MemberTicketController::class, 'show'])->name('tickets.show');
    Route::post('/tickets/{ticket}/refresh-qr', [\App\Http\Controllers\MemberTicketController::class, 'refreshQr'])
        ->middleware('throttle:60,1')
        ->name('tickets.refresh_qr');
    Route::get('/tickets/{ticket}/qr', [\App\Http\Controllers\MemberTicketController::class, 'downloadQr'])->name('tickets.qr');

    Route::get('/ledger', [\App\Http\Controllers\MemberLedgerController::class, 'index'])->name('ledger.index');

    Route::get('/missions', [\App\Http\Controllers\MemberMissionController::class, 'index'])->name('missions.index');
    Route::get('/missions/{mission}', [\App\Http\Controllers\MemberMissionController::class, 'show'])->name('missions.show');
    Route::post('/missions/{mission}/submit', [\App\Http\Controllers\MemberMissionController::class, 'submit'])
        ->middleware('throttle:20,1')
        ->name('missions.submit');

    Route::get('/shop', [\App\Http\Controllers\MemberShopController::class, 'index'])->name('shop.index');
    Route::get('/shop/{reward}', [\App\Http\Controllers\MemberShopController::class, 'show'])->name('shop.show');
    Route::post('/shop/{reward}/redeem', [\App\Http\Controllers\MemberShopController::class, 'redeem'])
        ->middleware('throttle:20,1')
        ->name('shop.redeem');

    Route::get('/leaderboard', [\App\Http\Controllers\MemberLeaderboardController::class, 'index'])->name('leaderboard.index');

    Route::prefix('directory')->name('directory.')->group(function () {
        Route::get('/', [\App\Http\Controllers\MemberDirectoryController::class, 'index'])->name('index');
        Route::get('/profile/edit', [\App\Http\Controllers\MemberDirectoryController::class, 'editProfile'])->name('profile.edit');
        Route::put('/profile', [\App\Http\Controllers\MemberDirectoryController::class, 'updateProfile'])->name('profile.update');
        Route::get('/{user}', [\App\Http\Controllers\MemberDirectoryController::class, 'show'])->name('show');
    });

    Route::prefix('opportunities')->name('opportunities.')->group(function () {
        Route::get('/', [\App\Http\Controllers\MemberJobOpportunityController::class, 'index'])->name('index');
        Route::get('/{opportunity}', [\App\Http\Controllers\MemberJobOpportunityController::class, 'show'])->name('show');
        Route::post('/{opportunity}/bookmark', [\App\Http\Controllers\MemberJobOpportunityController::class, 'toggleBookmark'])->name('bookmark');
    });

    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [\App\Http\Controllers\NotificationController::class, 'index'])->name('index');
        Route::post('/read-all', [\App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('read-all');
        Route::post('/{notification}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('read');
        Route::delete('/{notification}', [\App\Http\Controllers\NotificationController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('announcements')->name('announcements.')->group(function () {
        Route::get('/', [\App\Http\Controllers\AnnouncementController::class, 'index'])->name('index');
        Route::get('/{announcement}', [\App\Http\Controllers\AnnouncementController::class, 'show'])->name('show');
        Route::post('/{announcement}/acknowledge', [\App\Http\Controllers\AnnouncementController::class, 'acknowledge'])->name('acknowledge');
    });

    // Member Help Desk & Dispute Center
    Route::prefix('helpdesk')->name('helpdesk.')->group(function () {
        Route::get('/', [\App\Http\Controllers\MemberSupportTicketController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\MemberSupportTicketController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\MemberSupportTicketController::class, 'store'])
            ->middleware('throttle:15,1')
            ->name('store');
        Route::get('/{ticket}', [\App\Http\Controllers\MemberSupportTicketController::class, 'show'])->name('show');
        Route::post('/{ticket}/reply', [\App\Http\Controllers\MemberSupportTicketController::class, 'reply'])
            ->middleware('throttle:30,1')
            ->name('reply');
        Route::get('/messages/{message}/attachment', [\App\Http\Controllers\MemberSupportTicketController::class, 'downloadAttachment'])->name('attachment');
    });

    // Project Collaboration & Team Finder
    Route::prefix('projects')->name('projects.')->group(function () {
        Route::get('/', [\App\Http\Controllers\ProjectCollaborationController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\ProjectCollaborationController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\ProjectCollaborationController::class, 'store'])->name('store');
        Route::get('/{project}', [\App\Http\Controllers\ProjectCollaborationController::class, 'show'])->name('show');
        Route::post('/{project}/apply', [\App\Http\Controllers\ProjectCollaborationController::class, 'apply'])
            ->middleware('throttle:20,1')
            ->name('apply');
        Route::post('/applications/{application}/decide', [\App\Http\Controllers\ProjectCollaborationController::class, 'decideApplication'])->name('applications.decide');
        Route::post('/{project}/milestones', [\App\Http\Controllers\ProjectCollaborationController::class, 'addMilestone'])->name('milestones.store');
        Route::delete('/{project}', [\App\Http\Controllers\ProjectCollaborationController::class, 'destroy'])->name('destroy');
    });

    // Volunteer Opportunities Board
    Route::prefix('volunteers')->name('volunteers.')->group(function () {
        Route::get('/', [\App\Http\Controllers\VolunteerOpportunityController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\VolunteerOpportunityController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\VolunteerOpportunityController::class, 'store'])->name('store');
        Route::get('/{opportunity}', [\App\Http\Controllers\VolunteerOpportunityController::class, 'show'])->name('show');
        Route::post('/{opportunity}/apply', [\App\Http\Controllers\VolunteerOpportunityController::class, 'apply'])
            ->middleware('throttle:20,1')
            ->name('apply');
        Route::post('/applications/{application}/decide', [\App\Http\Controllers\VolunteerOpportunityController::class, 'decideApplication'])->name('applications.decide');
        Route::post('/applications/{application}/approve-hours', [\App\Http\Controllers\VolunteerOpportunityController::class, 'approveHours'])->name('applications.approve-hours');
    });

    // Constitutional and By-Laws Module
    Route::prefix('constitution')->name('constitution.')->group(function () {
        Route::get('/', [\App\Http\Controllers\ConstitutionController::class, 'index'])->name('index');
        Route::get('/preview', [\App\Http\Controllers\ConstitutionController::class, 'preview'])
            ->middleware('throttle:60,1')
            ->name('preview');
        Route::get('/download', [\App\Http\Controllers\ConstitutionController::class, 'download'])
            ->middleware('throttle:30,1')
            ->name('download');
    });

    // Event Organizer Workspace & Live Scanner
    Route::middleware(['role:event_organizer,staff,comsoc_admin,admin,super_admin'])->prefix('organizer')->name('organizer.')->group(function () {
        Route::get('/scanner', [\App\Http\Controllers\OrganizerScannerController::class, 'index'])->name('scanner');
        Route::post('/scanner/scan', [\App\Http\Controllers\OrganizerScannerController::class, 'scan'])
            ->middleware('throttle:120,1')
            ->name('scanner.scan');
        Route::post('/attendance/correct-missing-timeout', [\App\Http\Controllers\OrganizerScannerController::class, 'correctMissingTimeOut'])
            ->middleware('throttle:30,1')
            ->name('attendance.correct-timeout');
    });

    Route::middleware(['role:kiosk,staff,treasurer,admin,super_admin'])->group(function () {
        Route::get('/kiosk/{kiosk}', [AttendanceKioskController::class, 'show'])->name('kiosk.attendance');
        Route::post('/kiosk/{kiosk}/scan', [AttendanceKioskController::class, 'scan'])
            ->middleware('throttle:60,1')
            ->name('kiosk.scan');
        Route::post('/kiosk/{kiosk}/lookup', [AttendanceKioskController::class, 'manualLookup'])
            ->middleware('throttle:60,1')
            ->name('kiosk.lookup');
        Route::get('/kiosk/{kiosk}/snack', [SnackKioskController::class, 'show'])->name('kiosk.snack');
        Route::post('/kiosk/{kiosk}/snack/scan', [SnackKioskController::class, 'scan'])
            ->middleware('throttle:60,1')
            ->name('kiosk.snack.scan');

        Route::prefix('staff/incidents')->name('staff.incidents.')->group(function () {
            Route::get('/create', [\App\Http\Controllers\Staff\IncidentController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Staff\IncidentController::class, 'store'])->name('store');
            Route::get('/my', [\App\Http\Controllers\Staff\IncidentController::class, 'myIncidents'])->name('my');
            Route::get('/{incident}', [\App\Http\Controllers\Staff\IncidentController::class, 'show'])->name('show');
        });
    });

    Route::middleware(['role:admin,super_admin,treasurer,president,executive_secretary,auditor'])->prefix('admin')->name('admin.')->group(function () {

        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Raffles Management (Events & Attendance Rewards)
        Route::prefix('raffles')->name('raffles.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\RaffleController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\Admin\RaffleController::class, 'store'])->name('store');
            Route::get('/{raffle}', [\App\Http\Controllers\Admin\RaffleController::class, 'show'])->name('show');
            Route::post('/{raffle}/draw', [\App\Http\Controllers\Admin\RaffleController::class, 'draw'])->name('draw');
            Route::post('/winners/{winner}/claim', [\App\Http\Controllers\Admin\RaffleController::class, 'claim'])->name('claim');
        });

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

            Route::get('students/exp-points', [\App\Http\Controllers\Admin\StudentExpPointsController::class, 'index'])->name('students.exp-points.index');
            Route::post('students/exp-points/adjust', [\App\Http\Controllers\Admin\StudentExpPointsController::class, 'adjust'])->name('students.exp-points.adjust');

            Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
            Route::get('audit-logs/{auditLog}', [AuditLogController::class, 'show'])->name('audit-logs.show');

            // Website Management
            Route::prefix('website')->name('website.')->group(function () {
                Route::get('/', [WebsiteManagementController::class, 'settings'])->name('settings');
                Route::post('/', [WebsiteManagementController::class, 'updateSettings'])->name('settings.update');

                Route::get('/profile', [WebsiteManagementController::class, 'profile'])->name('profile');
                Route::post('/profile', [WebsiteManagementController::class, 'updateProfile'])->name('profile.update');

                Route::get('/officers', [WebsiteManagementController::class, 'officers'])->name('officers');
                Route::post('/officers', [WebsiteManagementController::class, 'storeOfficer'])->name('officers.store');
                Route::put('/officers/{officer}', [WebsiteManagementController::class, 'updateOfficer'])->name('officers.update');
                Route::delete('/officers/{officer}', [WebsiteManagementController::class, 'destroyOfficer'])->name('officers.destroy');

                Route::get('/activities', [WebsiteManagementController::class, 'activities'])->name('activities');
                Route::post('/activities/{event}/toggle', [WebsiteManagementController::class, 'toggleEventPublic'])->name('activities.toggle');

                Route::get('/achievements', [WebsiteManagementController::class, 'achievements'])->name('achievements');
                Route::post('/achievements', [WebsiteManagementController::class, 'storeAchievement'])->name('achievements.store');
                Route::put('/achievements/{achievement}', [WebsiteManagementController::class, 'updateAchievement'])->name('achievements.update');
                Route::delete('/achievements/{achievement}', [WebsiteManagementController::class, 'destroyAchievement'])->name('achievements.destroy');

                Route::get('/gallery', [WebsiteManagementController::class, 'gallery'])->name('gallery');
                Route::post('/gallery', [WebsiteManagementController::class, 'storeGalleryItem'])->name('gallery.store');
                Route::delete('/gallery/{item}', [WebsiteManagementController::class, 'destroyGalleryItem'])->name('gallery.destroy');

                Route::get('/partners', [WebsiteManagementController::class, 'partners'])->name('partners');
                Route::post('/partners', [WebsiteManagementController::class, 'storePartner'])->name('partners.store');
                Route::put('/partners/{partner}', [WebsiteManagementController::class, 'updatePartner'])->name('partners.update');
                Route::delete('/partners/{partner}', [WebsiteManagementController::class, 'destroyPartner'])->name('partners.destroy');

                Route::get('/faqs', [WebsiteManagementController::class, 'faqs'])->name('faqs');
                Route::post('/faqs', [WebsiteManagementController::class, 'storeFaq'])->name('faqs.store');
                Route::put('/faqs/{faq}', [WebsiteManagementController::class, 'updateFaq'])->name('faqs.update');
                Route::delete('/faqs/{faq}', [WebsiteManagementController::class, 'destroyFaq'])->name('faqs.destroy');
            });
        });

        // Event Management & Approval Workflow
        Route::middleware(['role:super_admin,president,executive_secretary,admin'])->group(function () {
            Route::resource('events', EventController::class)->except(['index', 'show']);
            Route::get('events/{event}/configure', [EventController::class, 'configure'])->name('events.configure');
            Route::post('events/{event}/configure', [EventController::class, 'saveConfiguration'])->name('events.configure.save');
            Route::post('events/{event}/approve', [EventController::class, 'approve'])->name('events.approve');
            Route::post('events/{event}/publish', [EventController::class, 'publish'])->name('events.publish');
            Route::post('events/{event}/close-registration', [EventController::class, 'closeRegistration'])->name('events.close-registration');
            Route::post('events/{event}/complete', [EventController::class, 'complete'])->name('events.complete');
            Route::post('events/{event}/archive', [EventController::class, 'archive'])->name('events.archive');
            Route::post('events/{event}/sessions', [EventController::class, 'storeSession'])->name('events.sessions.store');
            Route::post('events/{event}/snack-sessions', [EventController::class, 'storeSnackSession'])->name('events.snack-sessions.store');

            // Evaluation Templates Management
            Route::resource('evaluation-templates', \App\Http\Controllers\Admin\EvaluationTemplateController::class);
            Route::post('evaluation-templates/{evaluation_template}/toggle-active', [\App\Http\Controllers\Admin\EvaluationTemplateController::class, 'toggleActive'])
                ->name('evaluation-templates.toggle-active');

            // Evaluation Results & Analytics
            Route::get('evaluations/results', [\App\Http\Controllers\Admin\EventEvaluationReportController::class, 'index'])->name('evaluations.results.index');
            Route::get('evaluations/results/{evaluation}', [\App\Http\Controllers\Admin\EventEvaluationReportController::class, 'show'])->name('evaluations.results.show');

            // Constitutional and By-Laws Admin Management
            Route::prefix('constitution')->name('constitution.')->group(function () {
                Route::get('/', [\App\Http\Controllers\Admin\AdminConstitutionController::class, 'index'])->name('index');
                Route::post('/upload', [\App\Http\Controllers\Admin\AdminConstitutionController::class, 'upload'])->name('upload');
                Route::patch('/metadata', [\App\Http\Controllers\Admin\AdminConstitutionController::class, 'updateMetadata'])->name('update-metadata');
                Route::post('/toggle-publish', [\App\Http\Controllers\Admin\AdminConstitutionController::class, 'togglePublish'])->name('toggle-publish');
            });
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
        Route::post('event-registrations/batch-approve', [EventRegistrationController::class, 'batchApprove'])->name('event-registrations.batch-approve');
        Route::post('event-registrations/batch-reject', [EventRegistrationController::class, 'batchReject'])->name('event-registrations.batch-reject');
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

        Route::prefix('economy')->name('economy.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\RewardEconomyController::class, 'index'])->name('index');
            Route::post('/rules', [\App\Http\Controllers\Admin\RewardEconomyController::class, 'storeRule'])->name('rules.store');
            Route::put('/rules/{rule}', [\App\Http\Controllers\Admin\RewardEconomyController::class, 'updateRule'])->name('rules.update');
            Route::post('/levels', [\App\Http\Controllers\Admin\RewardEconomyController::class, 'storeLevel'])->name('levels.store');
            Route::put('/levels/{level}', [\App\Http\Controllers\Admin\RewardEconomyController::class, 'updateLevel'])->name('levels.update');
            Route::post('/adjust', [\App\Http\Controllers\Admin\RewardEconomyController::class, 'adjustMemberBalance'])->name('adjust');
        });

        Route::prefix('missions')->name('missions.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdminMissionController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\Admin\AdminMissionController::class, 'store'])->name('store');
            Route::post('/submissions/{submission}/approve', [\App\Http\Controllers\Admin\AdminMissionController::class, 'approveSubmission'])->name('approve');
            Route::post('/submissions/{submission}/reject', [\App\Http\Controllers\Admin\AdminMissionController::class, 'rejectSubmission'])->name('reject');
        });

        Route::prefix('rewards')->name('rewards.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdminRewardShopController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\Admin\AdminRewardShopController::class, 'store'])->name('store');
            Route::put('/{reward}', [\App\Http\Controllers\Admin\AdminRewardShopController::class, 'update'])->name('update');
            Route::delete('/{reward}', [\App\Http\Controllers\Admin\AdminRewardShopController::class, 'destroy'])->name('destroy');
            Route::post('/{reward}/publish', [\App\Http\Controllers\Admin\AdminRewardShopController::class, 'publish'])->name('publish');
            Route::post('/{reward}/toggle-active', [\App\Http\Controllers\Admin\AdminRewardShopController::class, 'toggleActive'])->name('toggle-active');
            Route::post('/{reward}/archive', [\App\Http\Controllers\Admin\AdminRewardShopController::class, 'archive'])->name('archive');
            Route::post('/{redemption}/fulfill', [\App\Http\Controllers\Admin\AdminRewardShopController::class, 'fulfill'])->name('fulfill');
            Route::post('/{redemption}/reject', [\App\Http\Controllers\Admin\AdminRewardShopController::class, 'reject'])->name('reject');
            
            // Collection Desk & Claim Verification
            Route::get('/collection', [\App\Http\Controllers\Admin\RewardCollectionController::class, 'index'])->name('collection');
            Route::post('/collection/verify', [\App\Http\Controllers\Admin\RewardCollectionController::class, 'verify'])->name('collection.verify');
            Route::post('/collection/{redemption}/cancel', [\App\Http\Controllers\Admin\RewardCollectionController::class, 'cancel'])->name('collection.cancel');
        });

        Route::prefix('shop')->name('shop.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdminRewardShopController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\Admin\AdminRewardShopController::class, 'store'])->name('store');
            Route::put('/{reward}', [\App\Http\Controllers\Admin\AdminRewardShopController::class, 'update'])->name('update');
            Route::delete('/{reward}', [\App\Http\Controllers\Admin\AdminRewardShopController::class, 'destroy'])->name('destroy');
            Route::post('/{reward}/publish', [\App\Http\Controllers\Admin\AdminRewardShopController::class, 'publish'])->name('publish');
            Route::post('/{reward}/toggle-active', [\App\Http\Controllers\Admin\AdminRewardShopController::class, 'toggleActive'])->name('toggle-active');
            Route::post('/{reward}/archive', [\App\Http\Controllers\Admin\AdminRewardShopController::class, 'archive'])->name('archive');
            Route::post('/{redemption}/fulfill', [\App\Http\Controllers\Admin\AdminRewardShopController::class, 'fulfill'])->name('fulfill');
            Route::post('/{redemption}/reject', [\App\Http\Controllers\Admin\AdminRewardShopController::class, 'reject'])->name('reject');
        });

        Route::prefix('achievements')->name('achievements.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdminAchievementController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\Admin\AdminAchievementController::class, 'store'])->name('store');
            Route::put('/{achievement}', [\App\Http\Controllers\Admin\AdminAchievementController::class, 'update'])->name('update');
            Route::post('/{achievement}/award', [\App\Http\Controllers\Admin\AdminAchievementController::class, 'awardMember'])->name('award');
        });

        Route::prefix('opportunities')->name('opportunities.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdminJobOpportunityController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\Admin\AdminJobOpportunityController::class, 'store'])->name('store');
            Route::put('/{opportunity}', [\App\Http\Controllers\Admin\AdminJobOpportunityController::class, 'update'])->name('update');
            Route::post('/{opportunity}/toggle-active', [\App\Http\Controllers\Admin\AdminJobOpportunityController::class, 'toggleActive'])->name('toggle-active');
        });

        Route::prefix('announcements')->name('announcements.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdminAnnouncementController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\Admin\AdminAnnouncementController::class, 'store'])->name('store');
            Route::get('/{announcement}', [\App\Http\Controllers\Admin\AdminAnnouncementController::class, 'show'])->name('show');
            Route::put('/{announcement}', [\App\Http\Controllers\Admin\AdminAnnouncementController::class, 'update'])->name('update');
            Route::post('/{announcement}/toggle-active', [\App\Http\Controllers\Admin\AdminAnnouncementController::class, 'toggleActive'])->name('toggle-active');
            Route::delete('/{announcement}', [\App\Http\Controllers\Admin\AdminAnnouncementController::class, 'destroy'])->name('destroy');
        });

        // Admin Help Desk & Dispute Center
        Route::prefix('support')->name('support.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdminSupportTicketController::class, 'index'])->name('index');
            Route::get('/{ticket}', [\App\Http\Controllers\Admin\AdminSupportTicketController::class, 'show'])->name('show');
            Route::post('/{ticket}/reply', [\App\Http\Controllers\Admin\AdminSupportTicketController::class, 'reply'])->name('reply');
            Route::post('/{ticket}/assign', [\App\Http\Controllers\Admin\AdminSupportTicketController::class, 'assign'])->name('assign');
            Route::post('/{ticket}/resolve', [\App\Http\Controllers\Admin\AdminSupportTicketController::class, 'resolve'])->name('resolve');
            Route::post('/{ticket}/escalate', [\App\Http\Controllers\Admin\AdminSupportTicketController::class, 'escalate'])->name('escalate');
        });

        // Module 3: Event Analytics & Impact Reports
        Route::prefix('analytics')->name('analytics.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\EventAnalyticsController::class, 'index'])->name('index');
            Route::get('/export/csv', [\App\Http\Controllers\Admin\EventAnalyticsController::class, 'exportCsv'])->name('export.csv');
        });

        Route::prefix('seasons')->name('seasons.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\SeasonManagementController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\Admin\SeasonManagementController::class, 'store'])->name('store');
            Route::put('/{season}', [\App\Http\Controllers\Admin\SeasonManagementController::class, 'update'])->name('update');
            Route::get('/report', [\App\Http\Controllers\Admin\SeasonManagementController::class, 'semesterReport'])->name('report');
            Route::get('/{season}/preview-reset', [\App\Http\Controllers\Admin\SeasonManagementController::class, 'previewReset'])->name('preview-reset');
            Route::post('/{season}/execute-reset', [\App\Http\Controllers\Admin\SeasonManagementController::class, 'executeReset'])->name('execute-reset');
            Route::get('/{season}/snapshots', [\App\Http\Controllers\Admin\SeasonManagementController::class, 'showSnapshots'])->name('snapshots');
        });
    });
});
