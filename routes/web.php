<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Auth\StudentAuthController;
use App\Http\Controllers\Admin\AcademicDataController;
use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DocumentTemplateController;
use App\Http\Controllers\Admin\GradeController;
use App\Http\Controllers\Admin\GraduationController;
use App\Http\Controllers\Admin\MajorController;
use App\Http\Controllers\Admin\SmkCertificateSettingController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\SchoolController;
use App\Http\Controllers\Admin\SearchController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\SmkRecordController;
use App\Http\Controllers\Admin\SmkUnitController;
use App\Http\Controllers\Admin\SubjectController;
use App\Http\Controllers\Admin\SystemConfigController;
use App\Http\Controllers\Admin\WhatsAppCenterController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\Student\DashboardController as StudentDashboardController;
use App\Http\Controllers\VerificationController;
use App\Http\Middleware\CheckAnnouncementDate;
use App\Services\AnnouncementService;
use Illuminate\Http\Request;

// --- PRE-RELEASE COUNTDOWN ---
Route::get('/countdown', function (AnnouncementService $announcementService) {
    return view('countdown', [
        'announcementAt' => $announcementService->getAnnouncementAt(),
    ]);
})->name('countdown');

Route::post('/locale', [LocaleController::class, 'update'])->name('locale.update');
Route::get('/verifikasi-dokumen/{token}', [VerificationController::class, 'verifyDocument'])->name('verification.document');

// --- STUDENT AREA (Protected by Countdown Middleware) ---
Route::middleware([CheckAnnouncementDate::class])->group(function () {
    Route::middleware('guest:student')->group(function (): void {
        Route::get('/', [StudentAuthController::class, 'showLoginForm'])->name('login');
        Route::post('/login', [StudentAuthController::class, 'login'])->middleware('throttle:6,1');
    });

    Route::middleware('auth:student')->name('student.')->group(function (): void {
        Route::post('/logout', [StudentAuthController::class, 'logout'])->name('logout');
        Route::get('/dashboard', [StudentDashboardController::class, 'index'])->name('dashboard');
        Route::get('/documents/skl/download', [StudentDashboardController::class, 'downloadSkl'])->name('documents.skl.download');
        Route::get('/documents/skl/preview', [StudentDashboardController::class, 'previewSkl'])->name('documents.skl.preview');
        Route::get('/documents/transcript/download', [StudentDashboardController::class, 'downloadTranscript'])->name('documents.transcript.download');
        Route::get('/documents/transcript/preview', [StudentDashboardController::class, 'previewTranscript'])->name('documents.transcript.preview');
    });
});

// --- ADMIN AREA (Isolated & No Countdown) ---
Route::prefix('admin-panel')->group(function (): void {
    Route::any('/{path?}', function (Request $request, ?string $path = null) {
        $target = url('admin/' . ltrim((string) $path, '/'));
        $query = $request->getQueryString();

        if (is_string($query) && $query !== '') {
            $target .= '?' . $query;
        }

        $status = in_array($request->method(), ['GET', 'HEAD'], true) ? 301 : 307;

        return redirect()->to($target, $status);
    })->where('path', '.*');
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/', fn () => redirect()->route('admin.dashboard'));

    Route::middleware('guest:admin')->group(function () {
        Route::get('/login', [AdminAuthController::class, 'showLoginForm'])->name('login');
        Route::post('/login', [AdminAuthController::class, 'login'])->middleware('throttle:6,1')->name('login.attempt');
    });

    Route::middleware('auth:admin')->group(function () {
        Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');
        Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
        Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
        Route::delete('/profile/avatar', [ProfileController::class, 'destroyAvatar'])->name('profile.avatar.destroy');
        Route::get('/search', SearchController::class)->name('search');

        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        
        // Notifications
        Route::prefix('notifications')->name('notifications.')->group(function (): void {
            Route::get('/', [\App\Http\Controllers\Admin\NotificationController::class, 'index'])->name('index');
            Route::post('/{notification}/read', [\App\Http\Controllers\Admin\NotificationController::class, 'markAsRead'])->name('read');
            Route::post('/read-all', [\App\Http\Controllers\Admin\NotificationController::class, 'markAllAsRead'])->name('read-all');
        });
        Route::prefix('school')->name('school.')->group(function (): void {
            Route::get('/profile', [SchoolController::class, 'index'])->name('profile.index');
            Route::put('/profile', [SchoolController::class, 'update'])->name('profile.update');
            Route::post('/profile/release-date', [SchoolController::class, 'updateAnnouncementDate'])->name('profile.release.update');
            Route::post('/profile/assessor/{major}', [SchoolController::class, 'updateAssessor'])->name('profile.assessor.update');
            Route::prefix('majors')->name('majors.')->group(function (): void {
                Route::get('/', [MajorController::class, 'index'])->name('index');
                Route::post('/', [MajorController::class, 'store'])->name('store');
                Route::put('/{major}', [MajorController::class, 'update'])->name('update');
                Route::delete('/{major}', [MajorController::class, 'destroy'])->name('destroy');
            });
            Route::prefix('subjects')->name('subjects.')->group(function (): void {
                Route::get('/', [SubjectController::class, 'index'])->name('index');
                Route::post('/', [SubjectController::class, 'store'])->name('store');
                Route::put('/{subject}', [SubjectController::class, 'update'])->name('update');
                Route::delete('/{subject}', [SubjectController::class, 'destroy'])->name('destroy');
                Route::get('/template', [SubjectController::class, 'downloadTemplate'])->name('template.download');
                Route::post('/import', [SubjectController::class, 'import'])->name('import.store');
                Route::get('/import', [SubjectController::class, 'importIndex'])->name('import.index');
            });
            Route::prefix('smk-units')->name('smk-units.')->group(function (): void {
                Route::get('/', [SmkUnitController::class, 'index'])->name('index');
                Route::post('/', [SmkUnitController::class, 'store'])->name('store');
                Route::put('/{unit}', [SmkUnitController::class, 'update'])->name('update');
                Route::delete('/{unit}', [SmkUnitController::class, 'destroy'])->name('destroy');
                Route::get('/template', [SmkUnitController::class, 'downloadTemplate'])->name('template.download');
                Route::post('/import', [SmkUnitController::class, 'import'])->name('import.store');
            });
            Route::get('/years', [AcademicDataController::class, 'academicYears'])->name('academic-years.index');
            Route::post('/years', [AcademicDataController::class, 'updateAcademicYears'])->name('academic-years.update');
        });

        Route::prefix('students')->name('students.')->group(function (): void {
            Route::get('/', [StudentController::class, 'index'])->name('index');
            Route::post('/', [StudentController::class, 'store'])->name('store');
            Route::put('/{student}', [StudentController::class, 'update'])->name('update');
            Route::delete('/{student}', [StudentController::class, 'destroy'])->name('destroy');
            Route::get('/template', [StudentController::class, 'downloadTemplate'])->name('template.download');
            Route::post('/import', [StudentController::class, 'import'])->name('import.store');
            Route::get('/import', [StudentController::class, 'importIndex'])->name('import.index');
            Route::get('/majors', fn () => redirect()->route('admin.school.majors.index'))->name('majors.index');
        });

        Route::prefix('grades')->name('grades.')->group(function (): void {
            Route::get('/academic', [GradeController::class, 'index'])->name('academic.index');
            Route::get('/students/{student}/edit', [GradeController::class, 'edit'])->name('students.edit');
            Route::put('/students/{student}/subjects/{subject}/semester/{semester}', [GradeController::class, 'updateSemester'])
                ->whereNumber('semester')
                ->name('students.semesters.update');
                
            // SMK Records (Competency)
            Route::get('/competency', [SmkRecordController::class, 'index'])->name('competency.index');
            Route::get('/competency/template', [SmkRecordController::class, 'downloadTemplate'])->name('competency.template');
            Route::post('/competency/import', [SmkRecordController::class, 'import'])->name('competency.import');
            Route::get('/competency/{student}/record', [SmkRecordController::class, 'getRecordData'])->name('competency.record.data');
            Route::put('/competency/{student}/record', [SmkRecordController::class, 'update'])->name('competency.update');
            Route::patch('/competency/{student}/pkl-inline', [SmkRecordController::class, 'updatePklInline'])->name('competency.update-pkl-inline');
            Route::delete('/competency/{student}/record', [SmkRecordController::class, 'destroyRecord'])->name('competency.destroy');
            Route::get('/competency/{student}/print', [SmkRecordController::class, 'printCertificate'])->name('competency.print');
            Route::get('/competency/{student}/print/certificate', [SmkRecordController::class, 'printCertificate'])->name('competency.print.certificate');
            Route::get('/competency/{student}/print/statement', [SmkRecordController::class, 'printStatement'])->name('competency.print.statement');
            
            Route::get('/template', [GradeController::class, 'downloadTemplate'])->name('template.download');
            Route::post('/import', [GradeController::class, 'import'])->name('import.store');
        });

        Route::prefix('wa')->name('whatsapp.')->group(function (): void {
            Route::get('connection', [WhatsAppCenterController::class, 'connection'])->name('connection.index');
            Route::get('connection/status', [WhatsAppCenterController::class, 'status'])->name('connection.status');
            Route::get('connection/qr', [WhatsAppCenterController::class, 'qr'])->name('connection.qr');
            Route::post('connection/test', [WhatsAppCenterController::class, 'testSend'])->name('connection.test');
            Route::post('connection/disconnect', [WhatsAppCenterController::class, 'disconnect'])->name('connection.disconnect');

            Route::get('blast', [WhatsAppCenterController::class, 'blast'])->name('blast.index');
            Route::post('blast', [WhatsAppCenterController::class, 'sendBlast'])->name('blast.send');
            Route::get('blast/{batch}/status', [WhatsAppCenterController::class, 'blastStatus'])->name('blast.status');

            Route::get('history', [WhatsAppCenterController::class, 'history'])->name('history.index');
            Route::post('history/{log}/retry', [WhatsAppCenterController::class, 'retryMessage'])->name('history.retry');

            Route::get('auto-respond', [WhatsAppCenterController::class, 'autoRespond'])->name('auto-respond.index');
            Route::post('auto-respond/toggle', [WhatsAppCenterController::class, 'toggleAutoRespond'])->name('auto-respond.toggle');

            Route::post('templates', [WhatsAppCenterController::class, 'storeTemplate'])->name('templates.store');
            Route::delete('templates/{template}', [WhatsAppCenterController::class, 'deleteTemplate'])->name('templates.delete');

            Route::post('send-individual', [WhatsAppCenterController::class, 'sendIndividual'])->name('send-individual');
        });

        Route::prefix('graduation')->name('graduation.')->group(function (): void {
            Route::get('status', [GraduationController::class, 'status'])->name('status.index');
            Route::get('templates', [DocumentTemplateController::class, 'index'])->name('templates.index');
            Route::put('templates/{documentType}', [DocumentTemplateController::class, 'update'])->name('templates.update');
            Route::get('templates/{documentType}/preview', [DocumentTemplateController::class, 'preview'])->name('templates.preview');
            Route::post('status/bulk-update', [GraduationController::class, 'bulkUpdateStatus'])->name('status.bulk.update');
            Route::post('status/bulk-access', [GraduationController::class, 'bulkUpdateAccess'])->name('status.bulk-access');
            Route::post('status/{student}/update', [GraduationController::class, 'updateStudentStatus'])->name('status.student.update');
            Route::post('status/{student}/access', [GraduationController::class, 'updateStudentAccess'])->name('status.student.access');
            Route::get('documents', [GraduationController::class, 'documents'])->name('documents.index');
            Route::post('documents/bulk-sync', [GraduationController::class, 'bulkSyncDrafts'])->name('documents.bulk-sync');
            Route::post('documents/bulk-cache', [GraduationController::class, 'bulkGenerateCache'])->name('documents.bulk-cache');
            Route::post('documents/bulk-publish', [GraduationController::class, 'bulkPublish'])->name('documents.bulk-publish');
            Route::post('documents/{student}/skl/sync', [GraduationController::class, 'syncSklDraft'])->name('documents.skl.sync');
            Route::get('documents/{student}/skl/preview', [GraduationController::class, 'previewSkl'])->name('documents.skl.preview');
            Route::get('documents/{student}/skl/print', [GraduationController::class, 'printSkl'])->name('documents.skl.print');
            Route::post('documents/{student}/skl/publish', [GraduationController::class, 'publishSkl'])->name('documents.skl.publish');
            Route::post('documents/{student}/skl/revoke', [GraduationController::class, 'revokeSkl'])->name('documents.skl.revoke');
            Route::get('documents/{student}/skl/download', [GraduationController::class, 'downloadSkl'])->name('documents.skl.download');
            Route::post('documents/{student}/transcript/sync', [GraduationController::class, 'syncTranscriptDraft'])->name('documents.transcript.sync');
            Route::get('documents/{student}/transcript/preview', [GraduationController::class, 'previewTranscript'])->name('documents.transcript.preview');
            Route::get('documents/{student}/transcript/print', [GraduationController::class, 'printTranscript'])->name('documents.transcript.print');
            Route::post('documents/{student}/transcript/publish', [GraduationController::class, 'publishTranscript'])->name('documents.transcript.publish');
            Route::post('documents/{student}/transcript/revoke', [GraduationController::class, 'revokeTranscript'])->name('documents.transcript.revoke');
            Route::get('documents/{student}/transcript/download', [GraduationController::class, 'downloadTranscript'])->name('documents.transcript.download');
        });

        Route::prefix('settings')->name('settings.')->group(function (): void {
            Route::get('/branding', [SystemConfigController::class, 'branding'])->name('branding.index');
            Route::post('/branding', [SystemConfigController::class, 'updateBranding'])->name('branding.update');
            Route::get('/backup', [SystemConfigController::class, 'backup'])->name('backup.index');
            Route::post('/backup', [SystemConfigController::class, 'createBackup'])->name('backup.create');
            Route::get('/backup/{filename}', [SystemConfigController::class, 'downloadBackup'])->name('backup.download');
            Route::delete('/backup/{filename}', [SystemConfigController::class, 'deleteBackup'])->name('backup.delete');
            Route::get('/about', [SystemConfigController::class, 'about'])->name('about.index');
            Route::get('/update/check', [SystemConfigController::class, 'checkUpdate'])->name('update.check');
            Route::post('/update/perform', [SystemConfigController::class, 'performUpdate'])->name('update.perform');
            Route::post('/update/fix-storage', [SystemConfigController::class, 'fixStorageLink'])->name('update.fix-storage');
        });

        Route::get('/wa/status', [SchoolController::class, 'waStatus'])->name('school.wa.status');
        Route::get('/wa/qr', [SchoolController::class, 'waQr'])->name('school.wa.qr');
    });
});
