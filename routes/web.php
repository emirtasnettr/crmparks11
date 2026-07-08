<?php

use App\Modules\Agency\Controllers\AgencyContactController;
use App\Modules\Agency\Controllers\AgencyActivityController;
use App\Modules\Agency\Controllers\AgencyContractController;
use App\Modules\Agency\Controllers\AgencyCourierController;
use App\Modules\Agency\Controllers\AgencyDocumentController;
use App\Modules\Agency\Controllers\AgencyEarningController;
use App\Modules\Agency\Controllers\AgencyController;
use App\Modules\Business\Controllers\BusinessActivityController;
use App\Modules\Business\Controllers\BusinessAssignmentController;
use App\Modules\Business\Controllers\BusinessContactController;
use App\Modules\Business\Controllers\BusinessContractController;
use App\Modules\Business\Controllers\BusinessController;
use App\Modules\Business\Controllers\BusinessDocumentController;
use App\Modules\Business\Controllers\BusinessEarningController;
use App\Modules\Courier\Controllers\CourierController;
use App\Modules\Courier\Controllers\CourierDocumentController;
use App\Modules\Courier\Controllers\CourierEarningController;
use App\Modules\Courier\Controllers\CourierActivityController;
use App\Modules\Courier\Controllers\CourierBankAccountController;
use App\Modules\Courier\Controllers\CourierVehicleController;
use App\Modules\Courier\Controllers\CourierWorkHistoryController;
use App\Modules\Dashboard\Controllers\DashboardController;
use App\Modules\Finance\Controllers\FinanceCollectionController;
use App\Modules\Finance\Controllers\FinanceActivityLogController;
use App\Modules\Finance\Controllers\FinanceCashFlowController;
use App\Modules\Finance\Controllers\FinanceInvoiceController;
use App\Modules\Finance\Controllers\FinanceProfitabilityController;
use App\Modules\Finance\Controllers\FinancePaymentController;
use App\Modules\Finance\Controllers\FinanceExpenseController;
use App\Modules\Finance\Controllers\FinanceCurrentAccountController;
use App\Modules\Finance\Controllers\FinanceDashboardController;
use App\Modules\Finance\Controllers\FinanceRevenueController;
use App\Modules\FormBuilder\Controllers\FormBuilderController;
use App\Modules\FormBuilder\Controllers\FormSubmissionController;
use App\Modules\LandingPage\Controllers\LandingPageBuilderController;
use App\Modules\LandingPage\Controllers\LandingPageController;
use App\Modules\Policy\Controllers\PolicyController;
use App\Modules\Policy\Controllers\PolicySettingsController;
use App\Modules\Setting\Controllers\SettingsController;
use App\Modules\User\Controllers\AuthController;
use App\Modules\User\Controllers\PermissionManagementController;
use App\Modules\User\Controllers\RoleManagementController;
use App\Modules\User\Controllers\UserActivityLogController;
use App\Modules\User\Controllers\UserManagementController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('permission:dashboard.view')
        ->name('dashboard');

    Route::prefix('isletmeler')->name('businesses.')->middleware('permission:business.view')->group(function () {
        Route::get('/', [BusinessController::class, 'index'])->name('index');
        Route::get('/export', [BusinessController::class, 'export'])->name('export');
        Route::get('/yeni', [BusinessController::class, 'create'])->name('create');
        Route::post('/', [BusinessController::class, 'store'])->middleware('permission:business.create')->name('store');
        Route::get('/yetkililer', [BusinessContactController::class, 'index'])->name('contacts.index');
        Route::get('/yetkililer/export', [BusinessContactController::class, 'export'])->name('contacts.export');
        Route::get('/sozlesmeler', [BusinessContractController::class, 'index'])->name('contracts.index');
        Route::get('/sozlesmeler/export', [BusinessContractController::class, 'export'])->name('contracts.export');
        Route::get('/sozlesmeler/{id}', [BusinessContractController::class, 'show'])->name('contracts.show');
        Route::get('/atanan-kuryeler', [BusinessAssignmentController::class, 'index'])->name('assignments.index');
        Route::get('/atanan-kuryeler/export', [BusinessAssignmentController::class, 'export'])->name('assignments.export');
        Route::get('/atanan-kuryeler/{id}', [BusinessAssignmentController::class, 'show'])->name('assignments.show');
        Route::get('/hakedisler', [BusinessEarningController::class, 'index'])->name('earnings.index');
        Route::get('/hakedisler/export', [BusinessEarningController::class, 'export'])->name('earnings.export');
        Route::get('/hakedisler/{id}', [BusinessEarningController::class, 'show'])->name('earnings.show');
        Route::get('/evraklar', [BusinessDocumentController::class, 'index'])->name('documents.index');
        Route::get('/hareket-gecmisi', [BusinessActivityController::class, 'index'])->name('activities.index');
        Route::get('/{id}/duzenle', [BusinessController::class, 'edit'])->name('edit');
        Route::put('/{id}', [BusinessController::class, 'update'])->middleware('permission:business.update')->name('update');
        Route::get('/{id}', [BusinessController::class, 'show'])->name('show');
    });

    Route::prefix('kuryeler')->name('couriers.')->middleware('permission:courier.view')->group(function () {
        Route::get('/', [CourierController::class, 'index'])->name('index');
        Route::get('/export', [CourierController::class, 'export'])->name('export');
        Route::get('/yeni', [CourierController::class, 'create'])->name('create');
        Route::get('/belgeler', [CourierDocumentController::class, 'index'])->name('documents.index');
        Route::get('/belgeler/{id}', [CourierDocumentController::class, 'show'])->name('documents.show');
        Route::get('/hakedisler', [CourierEarningController::class, 'index'])->name('earnings.index');
        Route::get('/hakedisler/export', [CourierEarningController::class, 'export'])->name('earnings.export');
        Route::get('/hakedisler/{id}', [CourierEarningController::class, 'show'])->name('earnings.show');
        Route::get('/calisma-gecmisi', [CourierWorkHistoryController::class, 'index'])->name('work-history.index');
        Route::get('/calisma-gecmisi/{id}', [CourierWorkHistoryController::class, 'show'])->name('work-history.show');
        Route::get('/arac-bilgileri', [CourierVehicleController::class, 'index'])->name('vehicles.index');
        Route::get('/arac-bilgileri/{id}', [CourierVehicleController::class, 'show'])->name('vehicles.show');
        Route::get('/banka-bilgileri', [CourierBankAccountController::class, 'index'])->name('bank-accounts.index');
        Route::get('/banka-bilgileri/{id}', [CourierBankAccountController::class, 'show'])->name('bank-accounts.show');
        Route::get('/hareket-gecmisi', [CourierActivityController::class, 'index'])->name('activities.index');
        Route::get('/{id}/duzenle', [CourierController::class, 'edit'])->name('edit');
        Route::put('/{id}', [CourierController::class, 'update'])->middleware('permission:courier.update')->name('update');
        Route::get('/{id}', [CourierController::class, 'show'])->name('show');
    });

    Route::prefix('finans')->name('finance.')->middleware('permission:dashboard.financial')->group(function () {
        Route::redirect('/', '/finans/dashboard');
        Route::get('/dashboard', [FinanceDashboardController::class, 'index'])->name('dashboard.index');
        Route::get('/cari-hesaplar', [FinanceCurrentAccountController::class, 'index'])->name('current-accounts.index');
        Route::get('/cari-hesaplar/export', [FinanceCurrentAccountController::class, 'export'])->name('current-accounts.export');
        Route::get('/gelirler', [FinanceRevenueController::class, 'index'])->name('revenues.index');
        Route::get('/gelirler/export', [FinanceRevenueController::class, 'export'])->name('revenues.export');
        Route::get('/gelirler/{id}', [FinanceRevenueController::class, 'show'])->name('revenues.show');
        Route::get('/giderler', [FinanceExpenseController::class, 'index'])->name('expenses.index');
        Route::get('/giderler/export', [FinanceExpenseController::class, 'export'])->name('expenses.export');
        Route::get('/giderler/{id}', [FinanceExpenseController::class, 'show'])->name('expenses.show');
        Route::get('/tahsilatlar', [FinanceCollectionController::class, 'index'])->name('collections.index');
        Route::get('/tahsilatlar/export', [FinanceCollectionController::class, 'export'])->name('collections.export');
        Route::get('/tahsilatlar/{id}', [FinanceCollectionController::class, 'show'])->name('collections.show');
        Route::get('/odemeler', [FinancePaymentController::class, 'index'])->name('payments.index');
        Route::get('/odemeler/export', [FinancePaymentController::class, 'export'])->name('payments.export');
        Route::get('/odemeler/{id}', [FinancePaymentController::class, 'show'])->name('payments.show');
        Route::get('/faturalar', [FinanceInvoiceController::class, 'index'])->name('invoices.index');
        Route::get('/faturalar/export', [FinanceInvoiceController::class, 'export'])->name('invoices.export');
        Route::get('/faturalar/{id}', [FinanceInvoiceController::class, 'show'])->name('invoices.show');
        Route::get('/karlilik-analizi', [FinanceProfitabilityController::class, 'index'])->name('profitability.index');
        Route::get('/karlilik-analizi/export', [FinanceProfitabilityController::class, 'export'])->name('profitability.export');
        Route::get('/nakit-akisi', [FinanceCashFlowController::class, 'index'])->name('cash-flow.index');
    });

    Route::prefix('finans')->name('finance.')->middleware('role:super_admin|general_manager')->group(function () {
        Route::get('/hareket-gecmisi', [FinanceActivityLogController::class, 'index'])->name('activity-log.index');
        Route::get('/hareket-gecmisi/export', [FinanceActivityLogController::class, 'export'])->name('activity-log.export');
    });

    Route::prefix('kullanici-yonetimi')->middleware('permission:user.view')->group(function () {
        Route::name('users.')->group(function () {
            Route::get('/kullanicilar', [UserManagementController::class, 'index'])->name('index');
            Route::get('/kullanicilar/export', [UserManagementController::class, 'export'])->name('export');
            Route::get('/kullanicilar/{id}', [UserManagementController::class, 'show'])->name('show');
        });

        Route::name('roles.')->group(function () {
            Route::get('/roller', [RoleManagementController::class, 'index'])->name('index');
            Route::get('/roller/{id}', [RoleManagementController::class, 'show'])->name('show');
        });

        Route::name('permissions.')->group(function () {
            Route::get('/yetkiler', [PermissionManagementController::class, 'index'])->name('index');
        });
    });

    Route::prefix('kullanici-yonetimi')->middleware('role:super_admin|general_manager')->group(function () {
        Route::get('/aktivite-kayitlari', [UserActivityLogController::class, 'index'])->name('users.activity-log.index');
        Route::get('/aktivite-kayitlari/export', [UserActivityLogController::class, 'export'])->name('users.activity-log.export');
    });

    Route::prefix('sistem-ayarlari')->middleware('role:super_admin')->name('settings.')->group(function () {
        Route::get('/', [SettingsController::class, 'index'])->name('index');
        Route::put('/{group}', [SettingsController::class, 'update'])->name('update');
        Route::post('/{group}/reset', [SettingsController::class, 'reset'])->name('reset');
    });

    Route::prefix('form-builder')->middleware('permission:form_builder.view')->name('form-builder.')->group(function () {
        Route::get('/', [FormBuilderController::class, 'index'])->name('index');
        Route::get('/yeni', [FormBuilderController::class, 'create'])->middleware('permission:form_builder.manage')->name('create');
        Route::post('/', [FormBuilderController::class, 'store'])->middleware('permission:form_builder.manage')->name('store');
        Route::get('/{id}/basvurular', [FormSubmissionController::class, 'index'])->name('submissions.index');
        Route::get('/{id}/basvurular/export', [FormSubmissionController::class, 'export'])->name('submissions.export');
        Route::get('/{id}/duzenle', [FormBuilderController::class, 'edit'])->middleware('permission:form_builder.manage')->name('edit');
        Route::put('/{id}', [FormBuilderController::class, 'update'])->middleware('permission:form_builder.manage')->name('update');
        Route::delete('/{id}', [FormBuilderController::class, 'destroy'])->middleware('permission:form_builder.manage')->name('destroy');
    });

    Route::prefix('landing-page-builder')->middleware('permission:landing_page.view')->name('landing-page-builder.')->group(function () {
        Route::get('/', [LandingPageBuilderController::class, 'index'])->name('index');
        Route::get('/yeni', [LandingPageBuilderController::class, 'create'])->middleware('permission:landing_page.manage')->name('create');
        Route::post('/', [LandingPageBuilderController::class, 'store'])->middleware('permission:landing_page.manage')->name('store');
        Route::get('/{id}/duzenle', [LandingPageBuilderController::class, 'edit'])->middleware('permission:landing_page.manage')->name('edit');
        Route::put('/{id}', [LandingPageBuilderController::class, 'update'])->middleware('permission:landing_page.manage')->name('update');
        Route::delete('/{id}', [LandingPageBuilderController::class, 'destroy'])->middleware('permission:landing_page.manage')->name('destroy');
    });

    Route::prefix('politika-ayarlari')->middleware('permission:policy_settings.view')->name('policy-settings.')->group(function () {
        Route::get('/', [PolicySettingsController::class, 'index'])->name('index');
        Route::put('/', [PolicySettingsController::class, 'update'])->middleware('permission:policy_settings.manage')->name('update');
    });

    Route::prefix('acenteler')->name('agencies.')->middleware('permission:agency.view')->group(function () {
        Route::get('/', [AgencyController::class, 'index'])->name('index');
        Route::get('/export', [AgencyController::class, 'export'])->name('export');
        Route::get('/yeni', [AgencyController::class, 'create'])->name('create');
        Route::get('/yetkililer', [AgencyContactController::class, 'index'])->name('contacts.index');
        Route::get('/yetkililer/export', [AgencyContactController::class, 'export'])->name('contacts.export');
        Route::get('/yetkililer/{id}', [AgencyContactController::class, 'show'])->name('contacts.show');
        Route::get('/kuryeler', [AgencyCourierController::class, 'index'])->name('couriers.index');
        Route::get('/kuryeler/export', [AgencyCourierController::class, 'export'])->name('couriers.export');
        Route::get('/sozlesmeler', [AgencyContractController::class, 'index'])->name('contracts.index');
        Route::get('/sozlesmeler/export', [AgencyContractController::class, 'export'])->name('contracts.export');
        Route::get('/sozlesmeler/{id}', [AgencyContractController::class, 'show'])->name('contracts.show');
        Route::get('/hakedisler', [AgencyEarningController::class, 'index'])->name('earnings.index');
        Route::get('/hakedisler/export', [AgencyEarningController::class, 'export'])->name('earnings.export');
        Route::get('/hakedisler/{id}', [AgencyEarningController::class, 'show'])->name('earnings.show');
        Route::get('/evraklar', [AgencyDocumentController::class, 'index'])->name('documents.index');
        Route::get('/evraklar/{id}', [AgencyDocumentController::class, 'show'])->name('documents.show');
        Route::get('/hareket-gecmisi', [AgencyActivityController::class, 'index'])->name('activities.index');
        Route::get('/hareket-gecmisi/export', [AgencyActivityController::class, 'export'])->name('activities.export');
        Route::get('/{id}/duzenle', [AgencyController::class, 'edit'])->name('edit');
        Route::put('/{id}', [AgencyController::class, 'update'])->middleware('permission:agency.update')->name('update');
        Route::get('/{id}', [AgencyController::class, 'show'])->name('show');
    });
});

Route::get('/lp/{slug}', [LandingPageController::class, 'show'])->name('landing.show');
Route::post('/lp/{slug}/gonder', [LandingPageController::class, 'submit'])
    ->middleware('throttle:landing-submit')
    ->name('landing.submit');
Route::get('/politika/{slug}', [PolicyController::class, 'show'])->name('policy.show');

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});
