<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\ChatBotController;
use App\Http\Controllers\Api\ClaimController;
use App\Http\Controllers\Api\DoctorConsultationController;
use App\Http\Controllers\Api\FeedbackController;
use App\Http\Controllers\Api\HospitalController;
use App\Http\Controllers\Api\HomeDashboardController;
use App\Http\Controllers\Api\ReminderController;
use App\Http\Controllers\Api\RiwayatController;
use App\Http\Controllers\Api\HospitalRegistrationController;
use App\Http\Controllers\Api\InsurancePackageController;
use App\Http\Controllers\Api\InsurancePolicyController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\AnnouncementController;
use App\Http\Controllers\Api\BannerController;
use App\Http\Controllers\Api\PromoCodeController;
use App\Http\Controllers\Api\PromotionController;
use App\Http\Controllers\Api\ReferralController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\MonitorController;
use App\Http\Controllers\Api\ServiceRegistrationController;
use App\Http\Controllers\Api\HealthServiceController;
use App\Http\Controllers\Api\AdminController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [\App\Http\Controllers\Api\AuthController::class, 'register']);
Route::post('/login', [\App\Http\Controllers\Api\AuthController::class, 'login']);

Route::prefix('v1')->group(function (): void {
    Route::apiResource('users', UserController::class);
    Route::apiResource('insurance-policies', InsurancePolicyController::class);
    Route::post('/insurance-policies/{id}/premium-payment', [InsurancePolicyController::class, 'submitPremiumPayment']);
    Route::apiResource('claims', ClaimController::class);
    Route::apiResource('transactions', TransactionController::class);
    Route::apiResource('hospital-registrations', HospitalRegistrationController::class);
    Route::apiResource('service-registrations', ServiceRegistrationController::class);
    Route::get('/health-services', [HealthServiceController::class, 'index']);
    Route::apiResource('doctor-consultations', DoctorConsultationController::class);
    Route::get('/doctor-consultations/{id}/messages', [DoctorConsultationController::class, 'getMessages']);
    Route::post('/doctor-consultations/{id}/messages', [DoctorConsultationController::class, 'sendMessage']);
    Route::post('/doctor-consultations/{id}/upload-proof', [DoctorConsultationController::class, 'uploadProof']);
    Route::get('/feedbacks/featured', [FeedbackController::class, 'featured']);
    Route::apiResource('feedbacks', FeedbackController::class);
    Route::get('/user-insurance', [InsurancePolicyController::class, 'index']);
    Route::get('/claims/status', [ClaimController::class, 'index']);
    Route::get('/transactions/recent', [TransactionController::class, 'index']);
    
    // Notification routes
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/summary', [NotificationController::class, 'summary']);
    
    // ChatBot routes
    Route::post('/chatbot/chat', [ChatBotController::class, 'chat']);
    Route::get('/chatbot/quick-replies', [ChatBotController::class, 'quickReplies']);

    // Home Dashboard route
    Route::get('/home-dashboard', [HomeDashboardController::class, 'index']);
    Route::get('/promotions/active', [PromotionController::class, 'active']);
    Route::get('/announcements/active', [AnnouncementController::class, 'active']);
    Route::get('/banners/active', [BannerController::class, 'active']);
    Route::get('/referrals/me', [ReferralController::class, 'me']);
    Route::post('/referrals/apply', [ReferralController::class, 'apply']);
    Route::get('/promo-codes/features', [PromoCodeController::class, 'features']);
    Route::post('/promo-codes/validate', [PromoCodeController::class, 'validateCode']);
    Route::post('/discount-coupons/validate', [ReferralController::class, 'validateCoupon']);

    // Package data
    Route::get('/insurance-packages', [InsurancePackageController::class, 'index']);

    // My policies
    Route::get('/my-policies', [InsurancePolicyController::class, 'myPolicies']);

    // Monitor - Saldo Polis dan Grafik Penggunaan
    Route::get('/monitor/saldo-summary', [MonitorController::class, 'getPolicySaldoSummary']);
    Route::get('/monitor/claims-history', [MonitorController::class, 'getClaimsHistory']);
    Route::get('/monitor/saldo-chart', [MonitorController::class, 'getSaldoChart']);

    // Riwayat (registrasi RS + transaksi + klaim)
    Route::get('/riwayat', [RiwayatController::class, 'index']);

    // Reminder / Kalender Pengingat
    Route::get('/reminders/today', [ReminderController::class, 'today']);
    Route::get('/reminders/upcoming', [ReminderController::class, 'upcoming']);
    Route::get('/reminders', [ReminderController::class, 'index']);
    Route::post('/reminders', [ReminderController::class, 'store']);
    Route::put('/reminders/{id}', [ReminderController::class, 'update']);
    Route::delete('/reminders/{id}', [ReminderController::class, 'destroy']);

    // Hospitals routes
    Route::get('/hospitals', [HospitalController::class, 'index']);
    Route::get('/hospitals/{id}', [HospitalController::class, 'show']);
    Route::get('/hospitals/{id}/doctors', [HospitalController::class, 'doctors']);
    Route::post('/hospitals', [HospitalController::class, 'store']);
    Route::put('/hospitals/{id}', [HospitalController::class, 'update']);
    Route::delete('/hospitals/{id}', [HospitalController::class, 'destroy']);
    Route::get('/doctors', [HospitalController::class, 'allDoctors']);

    // ─── Admin Routes ─────────────────────────────────────────────────────────
    Route::prefix('admin')->group(function () {
        Route::get('/stats', [AdminController::class, 'stats']);

        // Users
        Route::get('/users', [AdminController::class, 'users']);
        Route::put('/users/{id}', [AdminController::class, 'updateUser']);
        Route::delete('/users/{id}', [AdminController::class, 'deleteUser']);

        Route::get('/admins', [AdminController::class, 'admins']);
        Route::post('/admins', [AdminController::class, 'storeAdmin']);
        Route::put('/admins/{id}', [AdminController::class, 'updateAdmin']);
        Route::delete('/admins/{id}', [AdminController::class, 'deleteAdmin']);

        // Claims
        Route::get('/claims', [AdminController::class, 'claims']);
        Route::put('/claims/{id}', [AdminController::class, 'updateClaim']);

        // Insurance Policies
        Route::get('/policies', [AdminController::class, 'policies']);
        Route::put('/policies/{id}', [AdminController::class, 'updatePolicy']);

        // Transactions
        Route::get('/transactions', [AdminController::class, 'transactions']);

        // Hospitals
        Route::get('/hospitals', [AdminController::class, 'hospitals']);
        Route::post('/hospitals', [AdminController::class, 'storeHospital']);
        Route::put('/hospitals/{id}', [AdminController::class, 'updateHospital']);
        Route::delete('/hospitals/{id}', [AdminController::class, 'deleteHospital']);

        // Doctors
        Route::get('/doctors', [AdminController::class, 'doctors']);
        Route::post('/doctors', [AdminController::class, 'storeDoctor']);
        Route::put('/doctors/{id}', [AdminController::class, 'updateDoctor']);
        Route::delete('/doctors/{id}', [AdminController::class, 'deleteDoctor']);

        // Consultations
        Route::get('/consultations', [AdminController::class, 'consultations']);
        Route::put('/consultations/{id}', [AdminController::class, 'updateConsultation']);
        Route::post('/consultations/{id}/verify-payment', [AdminController::class, 'verifyConsultationPayment']);
        Route::post('/consultations/{id}/messages', [AdminController::class, 'sendConsultationMessage']);

        // Feedbacks
        Route::get('/feedbacks', [AdminController::class, 'feedbacks']);
        Route::put('/feedbacks/{id}/featured', [AdminController::class, 'updateFeedbackFeatured']);

        // Hospital Registrations
        Route::get('/hospital-registrations', [AdminController::class, 'hospitalRegistrations']);

        // Service Registrations
        Route::get('/service-registrations', [AdminController::class, 'serviceRegistrations']);

        // Insurance Packages
        Route::get('/packages', [AdminController::class, 'packages']);
        Route::post('/packages', [AdminController::class, 'storePackage']);
        Route::put('/packages/{id}', [AdminController::class, 'updatePackage']);
        Route::delete('/packages/{id}', [AdminController::class, 'deletePackage']);

        // Promotions
        Route::get('/promotions', [AdminController::class, 'promotions']);
        Route::post('/promotions', [AdminController::class, 'storePromotion']);
        Route::put('/promotions/{id}', [AdminController::class, 'updatePromotion']);
        Route::delete('/promotions/{id}', [AdminController::class, 'deletePromotion']);

        // Announcements / Informasi
        Route::get('/announcements', [AdminController::class, 'announcements']);
        Route::post('/announcements', [AdminController::class, 'storeAnnouncement']);
        Route::put('/announcements/{id}', [AdminController::class, 'updateAnnouncement']);
        Route::delete('/announcements/{id}', [AdminController::class, 'deleteAnnouncement']);

        // Kode Promo (diskon pembayaran)
        Route::get('/promo-codes', [AdminController::class, 'promoCodes']);
        Route::post('/promo-codes', [AdminController::class, 'storePromoCode']);
        Route::put('/promo-codes/{id}', [AdminController::class, 'updatePromoCode']);
        Route::delete('/promo-codes/{id}', [AdminController::class, 'deletePromoCode']);
    });
});
