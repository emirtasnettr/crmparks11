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
use App\Modules\FormBuilder\Controllers\FormApplicationController;
use App\Modules\FormBuilder\Controllers\FormBuilderController;
use App\Modules\FormBuilder\Controllers\FormSubmissionController;
use App\Modules\FormBuilder\Controllers\FormSubmissionStatusController;
use App\Modules\LandingPage\Controllers\LandingPageBuilderController;
use App\Modules\LandingPage\Controllers\LandingPageController;
use App\Modules\Policy\Controllers\PolicyController;
use App\Modules\Policy\Controllers\PolicySettingsController;
use App\Modules\Setting\Controllers\SettingsController;
use App\Modules\User\Controllers\AuthController;
use App\Modules\User\Controllers\PasswordResetController;
use App\Modules\User\Controllers\PermissionManagementController;
use App\Modules\User\Controllers\RoleManagementController;
use App\Modules\User\Controllers\UserActivityLogController;
use App\Modules\User\Controllers\UserManagementController;
use App\Modules\Notification\Controllers\NotificationController;
use App\Modules\Report\Controllers\ReportController;
use App\Modules\Search\Controllers\SearchController;
use App\Modules\ShiftPlanning\Controllers\ShiftPlanningController;
use App\Modules\Stock\Controllers\StockProductController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/sifremi-unuttum', [PasswordResetController::class, 'showForgotForm'])->name('password.request');
    Route::post('/sifremi-unuttum', [PasswordResetController::class, 'sendResetLink'])->name('password.email');
    Route::get('/sifre-sifirla/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
    Route::post('/sifre-sifirla', [PasswordResetController::class, 'reset'])->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('permission:dashboard.view')
        ->name('dashboard');

    Route::get('/arama', SearchController::class)->name('search');

    Route::prefix('form-basvurulari')->middleware('permission:form_application.view')->name('form-applications.')->group(function () {
        Route::get('/', [FormApplicationController::class, 'index'])->name('index');
        Route::get('/{formId}', [FormApplicationController::class, 'submissions'])->name('submissions');
        Route::get('/{formId}/{submissionId}', [FormApplicationController::class, 'show'])->name('show');
        Route::put('/{formId}/{submissionId}/statu', [FormApplicationController::class, 'updateStatus'])->name('status.update');
        Route::post('/{formId}/{submissionId}/notlar', [FormApplicationController::class, 'storeNote'])->name('notes.store');
    });

    Route::prefix('vardiya-planlama')->name('shift-planning.')->middleware('permission:shift_planning.view')->group(function () {
        Route::get('/', [ShiftPlanningController::class, 'index'])->name('index');
        Route::post('/', [ShiftPlanningController::class, 'store'])->middleware('permission:shift_planning.create')->name('store');
        Route::put('/{id}', [ShiftPlanningController::class, 'update'])->middleware('permission:shift_planning.update')->name('update');
        Route::put('/{id}/kuryeler', [ShiftPlanningController::class, 'assignCouriers'])->middleware('permission:shift_planning.update')->name('assign-couriers');
        Route::post('/{id}/joker', [ShiftPlanningController::class, 'storeJoker'])->middleware('permission:shift_planning.update')->name('jokers.store');
        Route::delete('/joker/{jokerId}', [ShiftPlanningController::class, 'destroyJoker'])->middleware('permission:shift_planning.update')->name('jokers.destroy');
        Route::delete('/{id}', [ShiftPlanningController::class, 'destroy'])->middleware('permission:shift_planning.delete')->name('destroy');
    });

    Route::prefix('stok-yonetimi')->name('stock.')->middleware('permission:stock.view')->group(function () {
        Route::get('/', [StockProductController::class, 'dashboard'])->name('dashboard');
        Route::get('/urunler', [StockProductController::class, 'index'])->name('products.index');
        Route::get('/urunler/yeni', [StockProductController::class, 'create'])->middleware('permission:stock.create')->name('products.create');
        Route::post('/urunler', [StockProductController::class, 'store'])->middleware('permission:stock.create')->name('products.store');
        Route::get('/zimmetler', [StockProductController::class, 'assignmentsIndex'])->name('assignments.index');
        Route::post('/zimmetler/{assignmentId}/iade', [StockProductController::class, 'returnAssignment'])
            ->middleware('permission:stock.update')
            ->name('assignments.return');
        Route::get('/kayit-gecmisi', [StockProductController::class, 'activityIndex'])->name('activity.index');
        Route::get('/urunler/{id}/duzenle', [StockProductController::class, 'edit'])->middleware('permission:stock.update')->name('products.edit');
        Route::put('/urunler/{id}', [StockProductController::class, 'update'])->middleware('permission:stock.update')->name('products.update');
        Route::post('/urunler/{id}/zimmetle', [StockProductController::class, 'assign'])->middleware('permission:stock.update')->name('products.assign');
        Route::get('/urunler/{id}', [StockProductController::class, 'show'])->name('products.show');
    });

    Route::prefix('raporlar')->middleware('permission:report.view')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/isletme-pipeline', [ReportController::class, 'businessPipeline'])->name('business-pipeline');
        Route::get('/acilis-asamasi', [ReportController::class, 'openingStage'])->name('opening-stage');
        Route::get('/sozlesme-vadeleri', [ReportController::class, 'contractExpiry'])->name('contract-expiry');
        Route::get('/hakedis-ozeti', [ReportController::class, 'earnings'])->name('earnings');
        Route::get('/hakedis-ozeti/export', [ReportController::class, 'earningsExport'])
            ->middleware('permission:report.export')
            ->name('earnings.export');
        Route::get('/tahsilat-yaslandirma', [ReportController::class, 'collections'])->name('collections');
        Route::get('/tahsilat-yaslandirma/export', [ReportController::class, 'collectionsExport'])
            ->middleware('permission:report.export')
            ->name('collections.export');
        Route::get('/operasyon-ozeti', [ReportController::class, 'operations'])->name('operations');
        Route::get('/kurye-performansi', [ReportController::class, 'courierPerformance'])->name('courier-performance');
        Route::get('/kurye-performansi/export', [ReportController::class, 'courierPerformanceExport'])
            ->middleware('permission:report.export')
            ->name('courier-performance.export');
        Route::get('/acente-payi', [ReportController::class, 'agencyShare'])->name('agency-share');
        Route::get('/acente-payi/export', [ReportController::class, 'agencyShareExport'])
            ->middleware('permission:report.export')
            ->name('agency-share.export');
    });

    Route::prefix('isletmeler')->name('businesses.')->group(function () {
        Route::middleware('permission:assignment.view')->group(function () {
            Route::get('/atanan-kuryeler', [BusinessAssignmentController::class, 'index'])->name('assignments.index');
            Route::get('/atanan-kuryeler/export', [BusinessAssignmentController::class, 'export'])->name('assignments.export');
            Route::post('/atanan-kuryeler', [BusinessAssignmentController::class, 'store'])->middleware('permission:assignment.create')->name('assignments.store');
            Route::put('/atanan-kuryeler/{id}', [BusinessAssignmentController::class, 'update'])->middleware('permission:assignment.update')->name('assignments.update');
            Route::post('/atanan-kuryeler/{id}/sonlandir', [BusinessAssignmentController::class, 'terminate'])
                ->middleware('permission:assignment.update')
                ->name('assignments.terminate');
            Route::get('/atanan-kuryeler/{id}', [BusinessAssignmentController::class, 'show'])->name('assignments.show');
        });

        Route::middleware('permission:business.view')->group(function () {
            Route::get('/', [BusinessController::class, 'index'])->name('index');
            Route::get('/export', [BusinessController::class, 'export'])->name('export');
            Route::get('/yeni', [BusinessController::class, 'create'])->name('create');
            Route::post('/', [BusinessController::class, 'store'])->middleware('permission:business.create')->name('store');
            Route::get('/yetkililer', [BusinessContactController::class, 'index'])->name('contacts.index');
            Route::get('/yetkililer/export', [BusinessContactController::class, 'export'])->name('contacts.export');
            Route::post('/yetkililer', [BusinessContactController::class, 'store'])->middleware('permission:business.update')->name('contacts.store');
            Route::put('/yetkililer/{id}', [BusinessContactController::class, 'update'])->middleware('permission:business.update')->name('contacts.update');
            Route::post('/yetkililer/{id}/pasife-al', [BusinessContactController::class, 'deactivate'])->middleware('permission:business.update')->name('contacts.deactivate');
            Route::get('/sozlesmeler', [BusinessContractController::class, 'index'])->name('contracts.index');
            Route::get('/sozlesmeler/export', [BusinessContractController::class, 'export'])->name('contracts.export');
            Route::post('/sozlesmeler', [BusinessContractController::class, 'store'])->middleware('permission:business.update')->name('contracts.store');
            Route::put('/sozlesmeler/{id}', [BusinessContractController::class, 'update'])->middleware('permission:business.update')->name('contracts.update');
            Route::get('/sozlesmeler/{id}/indir', [BusinessContractController::class, 'download'])->name('contracts.download');
            Route::get('/sozlesmeler/{id}', [BusinessContractController::class, 'show'])->name('contracts.show');
            Route::delete('/sozlesmeler/{id}', [BusinessContractController::class, 'destroy'])->name('contracts.destroy');
            Route::post('/sozlesmeler/{id}/pasife-al', [BusinessContractController::class, 'deactivate'])->middleware('permission:business.update')->name('contracts.deactivate');
            Route::get('/hakedisler', [BusinessEarningController::class, 'index'])->name('earnings.index');
            Route::get('/hakedisler/export', [BusinessEarningController::class, 'export'])->name('earnings.export');
            Route::get('/hakedisler/sablon', [BusinessEarningController::class, 'template'])
                ->middleware('permission:earning.create')
                ->name('earnings.template');
            Route::post('/hakedisler/ice-aktar', [BusinessEarningController::class, 'import'])
                ->middleware('permission:earning.create')
                ->name('earnings.import');
            Route::post('/hakedisler', [BusinessEarningController::class, 'store'])->middleware('permission:earning.create')->name('earnings.store');
            Route::put('/hakedisler/{id}', [BusinessEarningController::class, 'update'])->middleware('permission:earning.update')->name('earnings.update');
            Route::post('/hakedisler/{id}/onayla', [BusinessEarningController::class, 'approve'])->middleware('permission:earning.approve')->name('earnings.approve');
            Route::delete('/hakedisler/{id}', [BusinessEarningController::class, 'destroy'])->middleware('permission:earning.delete')->name('earnings.destroy');
            Route::get('/hakedisler/{id}', [BusinessEarningController::class, 'show'])->name('earnings.show');
            Route::get('/evraklar', [BusinessDocumentController::class, 'index'])->name('documents.index');
            Route::post('/evraklar', [BusinessDocumentController::class, 'store'])->middleware('permission:business.update')->name('documents.store');
            Route::get('/evraklar/{id}/indir', [BusinessDocumentController::class, 'download'])->name('documents.download');
            Route::delete('/evraklar/{id}', [BusinessDocumentController::class, 'destroy'])->middleware('permission:business.update')->name('documents.destroy');
            Route::get('/hareket-gecmisi', [BusinessActivityController::class, 'index'])->name('activities.index');
            Route::get('/{id}/duzenle', [BusinessController::class, 'edit'])->name('edit');
            Route::put('/{id}', [BusinessController::class, 'update'])->middleware('permission:business.update')->name('update');
            Route::post('/{id}/pasife-al', [BusinessController::class, 'deactivate'])->middleware('permission:business.update')->name('deactivate');
            Route::get('/{id}', [BusinessController::class, 'show'])->name('show');
        });
    });

    Route::prefix('kuryeler')->name('couriers.')->middleware('permission:courier.view')->group(function () {
        Route::get('/', [CourierController::class, 'index'])->name('index');
        Route::get('/export', [CourierController::class, 'export'])->name('export');
        Route::get('/yeni', [CourierController::class, 'create'])->name('create');
        Route::post('/', [CourierController::class, 'store'])->middleware('permission:courier.create')->name('store');
        Route::get('/belgeler', [CourierDocumentController::class, 'index'])->name('documents.index');
        Route::post('/belgeler', [CourierDocumentController::class, 'store'])->middleware('permission:courier.update')->name('documents.store');
        Route::get('/belgeler/{id}/indir', [CourierDocumentController::class, 'download'])->name('documents.download');
        Route::delete('/belgeler/{id}', [CourierDocumentController::class, 'destroy'])->middleware('permission:courier.update')->name('documents.destroy');
        Route::get('/belgeler/{id}', [CourierDocumentController::class, 'show'])->name('documents.show');
        Route::get('/hakedisler', [CourierEarningController::class, 'index'])->name('earnings.index');
        Route::get('/hakedisler/export', [CourierEarningController::class, 'export'])->name('earnings.export');
        Route::get('/hakedisler/sablon', [CourierEarningController::class, 'template'])
            ->middleware('permission:earning.create')
            ->name('earnings.template');
        Route::post('/hakedisler/ice-aktar', [CourierEarningController::class, 'import'])
            ->middleware('permission:earning.create')
            ->name('earnings.import');
        Route::get('/hakedisler/{id}/pdf', [CourierEarningController::class, 'pdf'])->name('earnings.pdf');
        Route::get('/hakedisler/{id}', [CourierEarningController::class, 'show'])->name('earnings.show');
        Route::get('/calisma-gecmisi', [CourierWorkHistoryController::class, 'index'])->name('work-history.index');
        Route::post('/calisma-gecmisi/{id}/sonlandir', [CourierWorkHistoryController::class, 'terminate'])
            ->middleware('permission:courier.update')
            ->name('work-history.terminate');
        Route::get('/calisma-gecmisi/{id}', [CourierWorkHistoryController::class, 'show'])->name('work-history.show');
        Route::get('/arac-bilgileri', [CourierVehicleController::class, 'index'])->name('vehicles.index');
        Route::post('/arac-bilgileri', [CourierVehicleController::class, 'store'])->middleware('permission:courier.update')->name('vehicles.store');
        Route::post('/arac-bilgileri/{id}/pasife-al', [CourierVehicleController::class, 'deactivate'])
            ->middleware('permission:courier.update')
            ->name('vehicles.deactivate');
        Route::get('/arac-bilgileri/{id}', [CourierVehicleController::class, 'show'])->name('vehicles.show');
        Route::get('/banka-bilgileri', [CourierBankAccountController::class, 'index'])->name('bank-accounts.index');
        Route::post('/banka-bilgileri', [CourierBankAccountController::class, 'store'])->middleware('permission:courier.update')->name('bank-accounts.store');
        Route::post('/banka-bilgileri/{id}/varsayilan', [CourierBankAccountController::class, 'makeDefault'])
            ->middleware('permission:courier.update')
            ->name('bank-accounts.make-default');
        Route::post('/banka-bilgileri/{id}/pasife-al', [CourierBankAccountController::class, 'deactivate'])
            ->middleware('permission:courier.update')
            ->name('bank-accounts.deactivate');
        Route::get('/banka-bilgileri/{id}', [CourierBankAccountController::class, 'show'])->name('bank-accounts.show');
        Route::get('/hareket-gecmisi', [CourierActivityController::class, 'index'])->name('activities.index');
        Route::get('/{id}/duzenle', [CourierController::class, 'edit'])->name('edit');
        Route::put('/{id}', [CourierController::class, 'update'])->middleware('permission:courier.update')->name('update');
        Route::post('/{id}/pasife-al', [CourierController::class, 'deactivate'])->middleware('permission:courier.update')->name('deactivate');
        Route::delete('/{id}', [CourierController::class, 'destroy'])->name('destroy');
        Route::get('/{id}', [CourierController::class, 'show'])->name('show');
    });

    Route::prefix('finans')->name('finance.')->middleware('permission:dashboard.financial')->group(function () {
        Route::redirect('/', '/finans/dashboard');
        Route::get('/dashboard', [FinanceDashboardController::class, 'index'])->name('dashboard.index');
        Route::get('/cari-hesaplar', [FinanceCurrentAccountController::class, 'index'])->name('current-accounts.index');
        Route::post('/cari-hesaplar', [FinanceCurrentAccountController::class, 'store'])->name('current-accounts.store');
        Route::put('/cari-hesaplar/{id}', [FinanceCurrentAccountController::class, 'update'])->name('current-accounts.update');
        Route::post('/cari-hesaplar/{id}/pasife-al', [FinanceCurrentAccountController::class, 'deactivate'])->name('current-accounts.deactivate');
        Route::post('/cari-hesaplar/hareketler', [FinanceCurrentAccountController::class, 'storeMovement'])->name('current-accounts.movements.store');
        Route::get('/cari-hesaplar/export', [FinanceCurrentAccountController::class, 'export'])->name('current-accounts.export');
        Route::get('/cari-hesaplar/{id}/pdf', [FinanceCurrentAccountController::class, 'statementPdf'])->name('current-accounts.pdf');
        Route::get('/gelirler', [FinanceRevenueController::class, 'index'])->name('revenues.index');
        Route::post('/gelirler', [FinanceRevenueController::class, 'store'])->name('revenues.store');
        Route::put('/gelirler/{id}', [FinanceRevenueController::class, 'update'])->name('revenues.update');
        Route::get('/gelirler/export', [FinanceRevenueController::class, 'export'])->name('revenues.export');
        Route::get('/gelirler/pdf', [FinanceRevenueController::class, 'exportPdf'])->name('revenues.export-pdf');
        Route::get('/gelirler/{id}/pdf', [FinanceRevenueController::class, 'pdf'])->name('revenues.pdf');
        Route::get('/gelirler/{id}', [FinanceRevenueController::class, 'show'])->name('revenues.show');
        Route::get('/giderler', [FinanceExpenseController::class, 'index'])->name('expenses.index');
        Route::post('/giderler', [FinanceExpenseController::class, 'store'])->name('expenses.store');
        Route::put('/giderler/{id}', [FinanceExpenseController::class, 'update'])->name('expenses.update');
        Route::delete('/giderler/{id}', [FinanceExpenseController::class, 'destroy'])->name('expenses.destroy');
        Route::get('/giderler/export', [FinanceExpenseController::class, 'export'])->name('expenses.export');
        Route::get('/giderler/pdf', [FinanceExpenseController::class, 'exportPdf'])->name('expenses.export-pdf');
        Route::get('/giderler/{id}/pdf', [FinanceExpenseController::class, 'pdf'])->name('expenses.pdf');
        Route::get('/giderler/{id}', [FinanceExpenseController::class, 'show'])->name('expenses.show');
        Route::get('/tahsilatlar', [FinanceCollectionController::class, 'index'])->name('collections.index');
        Route::post('/tahsilatlar', [FinanceCollectionController::class, 'store'])->name('collections.store');
        Route::post('/tahsilatlar/toplu', [FinanceCollectionController::class, 'bulk'])->name('collections.bulk');
        Route::put('/tahsilatlar/{id}', [FinanceCollectionController::class, 'update'])->name('collections.update');
        Route::post('/tahsilatlar/{id}/dekont', [FinanceCollectionController::class, 'storeReceipt'])->name('collections.receipts.store');
        Route::get('/tahsilatlar/{id}/dekont', [FinanceCollectionController::class, 'downloadReceipt'])->name('collections.receipts.download');
        Route::get('/tahsilatlar/export', [FinanceCollectionController::class, 'export'])->name('collections.export');
        Route::get('/tahsilatlar/{id}/pdf', [FinanceCollectionController::class, 'pdf'])->name('collections.pdf');
        Route::get('/tahsilatlar/{id}', [FinanceCollectionController::class, 'show'])->name('collections.show');
        Route::get('/odemeler', [FinancePaymentController::class, 'index'])->name('payments.index');
        Route::post('/odemeler', [FinancePaymentController::class, 'store'])->name('payments.store');
        Route::post('/odemeler/toplu', [FinancePaymentController::class, 'bulk'])->name('payments.bulk');
        Route::put('/odemeler/{id}', [FinancePaymentController::class, 'update'])->name('payments.update');
        Route::get('/odemeler/export', [FinancePaymentController::class, 'export'])->name('payments.export');
        Route::get('/odemeler/{id}/pdf', [FinancePaymentController::class, 'pdf'])->name('payments.pdf');
        Route::get('/odemeler/{id}', [FinancePaymentController::class, 'show'])->name('payments.show');
        Route::get('/faturalar', [FinanceInvoiceController::class, 'index'])->name('invoices.index');
        Route::post('/faturalar', [FinanceInvoiceController::class, 'store'])->name('invoices.store');
        Route::post('/faturalar/toplu', [FinanceInvoiceController::class, 'bulk'])->name('invoices.bulk');
        Route::put('/faturalar/{id}', [FinanceInvoiceController::class, 'update'])->name('invoices.update');
        Route::post('/faturalar/{id}/iptal', [FinanceInvoiceController::class, 'cancel'])->name('invoices.cancel');
        Route::get('/faturalar/export', [FinanceInvoiceController::class, 'export'])->name('invoices.export');
        Route::get('/faturalar/pdf', [FinanceInvoiceController::class, 'exportPdf'])->name('invoices.export-pdf');
        Route::get('/faturalar/{id}/pdf', [FinanceInvoiceController::class, 'pdf'])->name('invoices.pdf');
        Route::get('/faturalar/{id}', [FinanceInvoiceController::class, 'show'])->name('invoices.show');
        Route::get('/karlilik-analizi', [FinanceProfitabilityController::class, 'index'])->name('profitability.index');
        Route::get('/karlilik-analizi/export', [FinanceProfitabilityController::class, 'export'])->name('profitability.export');
        Route::get('/karlilik-analizi/pdf', [FinanceProfitabilityController::class, 'exportPdf'])->name('profitability.export-pdf');
        Route::get('/nakit-akisi', [FinanceCashFlowController::class, 'index'])->name('cash-flow.index');
    });

    Route::prefix('finans')->name('finance.')->middleware('role:super_admin|general_manager')->group(function () {
        Route::get('/hareket-gecmisi', [FinanceActivityLogController::class, 'index'])->name('activity-log.index');
        Route::get('/hareket-gecmisi/export', [FinanceActivityLogController::class, 'export'])->name('activity-log.export');
        Route::get('/hareket-gecmisi/pdf', [FinanceActivityLogController::class, 'exportPdf'])->name('activity-log.export-pdf');
    });

    Route::prefix('kullanici-yonetimi')->middleware('permission:user.view')->group(function () {
        Route::name('users.')->group(function () {
            Route::get('/kullanicilar', [UserManagementController::class, 'index'])->name('index');
            Route::post('/kullanicilar', [UserManagementController::class, 'store'])
                ->middleware('permission:user.create')
                ->name('store');
            Route::get('/kullanicilar/export', [UserManagementController::class, 'export'])->name('export');
            Route::get('/kullanicilar/{id}', [UserManagementController::class, 'show'])->name('show');
            Route::put('/kullanicilar/{id}', [UserManagementController::class, 'update'])
                ->middleware('permission:user.update')
                ->name('update');
            Route::post('/kullanicilar/{id}/sifre-sifirla', [UserManagementController::class, 'resetPassword'])
                ->middleware('permission:user.update')
                ->name('reset-password');
            Route::post('/kullanicilar/{id}/askiya-al', [UserManagementController::class, 'suspend'])
                ->middleware('permission:user.update')
                ->name('suspend');
            Route::post('/kullanicilar/{id}/pasife-al', [UserManagementController::class, 'deactivate'])
                ->middleware('permission:user.delete')
                ->name('deactivate');
            Route::delete('/kullanicilar/{id}/kalici-sil', [UserManagementController::class, 'forceDestroy'])
                ->middleware('role:super_admin')
                ->name('force-destroy');
            Route::delete('/kullanicilar/{id}', [UserManagementController::class, 'destroy'])
                ->middleware('permission:user.delete')
                ->name('destroy');
        });

        Route::name('roles.')->group(function () {
            Route::get('/roller', [RoleManagementController::class, 'index'])->name('index');
            Route::post('/roller', [RoleManagementController::class, 'store'])
                ->middleware('permission:user.create')
                ->name('store');
            Route::get('/roller/{id}', [RoleManagementController::class, 'show'])->name('show');
            Route::put('/roller/{id}', [RoleManagementController::class, 'update'])
                ->middleware('permission:user.update')
                ->name('update');
            Route::delete('/roller/{id}', [RoleManagementController::class, 'destroy'])
                ->middleware('permission:user.delete')
                ->name('destroy');
        });

        Route::name('permissions.')->group(function () {
            Route::get('/yetkiler', [PermissionManagementController::class, 'index'])->name('index');
            Route::put('/yetkiler', [PermissionManagementController::class, 'update'])
                ->middleware('permission:user.update')
                ->name('update');
        });
    });

    Route::prefix('kullanici-yonetimi')->name('notifications.')->middleware('permission:notification.view')->group(function () {
        Route::get('/bildirimler', [NotificationController::class, 'index'])->name('index');
        Route::get('/bildirimler/{id}/ac', [NotificationController::class, 'open'])->name('open');
        Route::post('/bildirimler/tumunu-oku', [NotificationController::class, 'markAllRead'])
            ->middleware('permission:notification.update')
            ->name('mark-all-read');
        Route::patch('/bildirimler/{id}/oku', [NotificationController::class, 'markRead'])
            ->middleware('permission:notification.update')
            ->name('mark-read');
        Route::delete('/bildirimler/{id}', [NotificationController::class, 'destroy'])
            ->middleware('permission:notification.delete')
            ->name('destroy');
    });

    Route::prefix('kullanici-yonetimi')->middleware('role:super_admin|general_manager')->group(function () {
        Route::get('/aktivite-kayitlari', [UserActivityLogController::class, 'index'])->name('users.activity-log.index');
        Route::get('/aktivite-kayitlari/export', [UserActivityLogController::class, 'export'])->name('users.activity-log.export');
        Route::get('/aktivite-kayitlari/pdf', [UserActivityLogController::class, 'exportPdf'])->name('users.activity-log.export-pdf');
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
        Route::post('/basvuru-statuleri', [FormSubmissionStatusController::class, 'store'])->middleware('permission:form_builder.manage')->name('statuses.store');
        Route::put('/basvuru-statuleri/{statusId}', [FormSubmissionStatusController::class, 'update'])->middleware('permission:form_builder.manage')->name('statuses.update');
        Route::delete('/basvuru-statuleri/{statusId}', [FormSubmissionStatusController::class, 'destroy'])->middleware('permission:form_builder.manage')->name('statuses.destroy');
        Route::put('/basvuru-statuleri/{statusId}/varsayilan', [FormSubmissionStatusController::class, 'setDefault'])->middleware('permission:form_builder.manage')->name('statuses.set-default');
        Route::get('/{id}/basvurular', [FormSubmissionController::class, 'index'])->name('submissions.index');
        Route::get('/{id}/basvurular/export', [FormSubmissionController::class, 'export'])->name('submissions.export');
        Route::get('/{id}/basvurular/{submissionId}', [FormSubmissionController::class, 'show'])->name('submissions.show');
        Route::put('/{id}/basvurular/{submissionId}/statu', [FormSubmissionController::class, 'updateStatus'])->name('submissions.status.update');
        Route::post('/{id}/basvurular/{submissionId}/notlar', [FormSubmissionController::class, 'storeNote'])->name('submissions.notes.store');
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
        Route::post('/', [AgencyController::class, 'store'])->middleware('permission:agency.create')->name('store');
        Route::get('/yetkililer', [AgencyContactController::class, 'index'])->name('contacts.index');
        Route::get('/yetkililer/export', [AgencyContactController::class, 'export'])->name('contacts.export');
        Route::post('/yetkililer', [AgencyContactController::class, 'store'])->middleware('permission:agency.update')->name('contacts.store');
        Route::get('/yetkililer/{id}', [AgencyContactController::class, 'show'])->name('contacts.show');
        Route::post('/yetkililer/{id}/pasife-al', [AgencyContactController::class, 'deactivate'])->middleware('permission:agency.update')->name('contacts.deactivate');
        Route::get('/kuryeler', [AgencyCourierController::class, 'index'])->name('couriers.index');
        Route::get('/kuryeler/export', [AgencyCourierController::class, 'export'])->name('couriers.export');
        Route::post('/kuryeler', [AgencyCourierController::class, 'store'])->middleware('permission:agency.update')->name('couriers.store');
        Route::post('/kuryeler/{id}/ayril', [AgencyCourierController::class, 'detach'])
            ->middleware('permission:agency.update')
            ->name('couriers.detach');
        Route::get('/sozlesmeler', [AgencyContractController::class, 'index'])->name('contracts.index');
        Route::get('/sozlesmeler/export', [AgencyContractController::class, 'export'])->name('contracts.export');
        Route::post('/sozlesmeler', [AgencyContractController::class, 'store'])->middleware('permission:agency.update')->name('contracts.store');
        Route::get('/sozlesmeler/{id}', [AgencyContractController::class, 'show'])->name('contracts.show');
        Route::post('/sozlesmeler/{id}/pasife-al', [AgencyContractController::class, 'deactivate'])->middleware('permission:agency.update')->name('contracts.deactivate');
        Route::get('/hakedisler', [AgencyEarningController::class, 'index'])->name('earnings.index');
        Route::get('/hakedisler/export', [AgencyEarningController::class, 'export'])->name('earnings.export');
        Route::get('/hakedisler/sablon', [AgencyEarningController::class, 'template'])
            ->middleware('permission:earning.create')
            ->name('earnings.template');
        Route::post('/hakedisler/ice-aktar', [AgencyEarningController::class, 'import'])
            ->middleware('permission:earning.create')
            ->name('earnings.import');
        Route::get('/hakedisler/{id}/pdf', [AgencyEarningController::class, 'pdf'])->name('earnings.pdf');
        Route::get('/hakedisler/{id}', [AgencyEarningController::class, 'show'])->name('earnings.show');
        Route::get('/evraklar', [AgencyDocumentController::class, 'index'])->name('documents.index');
        Route::post('/evraklar', [AgencyDocumentController::class, 'store'])->middleware('permission:agency.update')->name('documents.store');
        Route::get('/evraklar/{id}/indir', [AgencyDocumentController::class, 'download'])->name('documents.download');
        Route::delete('/evraklar/{id}', [AgencyDocumentController::class, 'destroy'])->middleware('permission:agency.update')->name('documents.destroy');
        Route::get('/evraklar/{id}', [AgencyDocumentController::class, 'show'])->name('documents.show');
        Route::get('/hareket-gecmisi', [AgencyActivityController::class, 'index'])->name('activities.index');
        Route::get('/hareket-gecmisi/export', [AgencyActivityController::class, 'export'])->name('activities.export');
        Route::get('/{id}/duzenle', [AgencyController::class, 'edit'])->name('edit');
        Route::put('/{id}', [AgencyController::class, 'update'])->middleware('permission:agency.update')->name('update');
        Route::post('/{id}/pasife-al', [AgencyController::class, 'deactivate'])->middleware('permission:agency.update')->name('deactivate');
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
