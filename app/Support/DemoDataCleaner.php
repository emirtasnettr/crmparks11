<?php

namespace App\Support;

use App\Models\Contract;
use App\Models\Document;
use App\Models\EarningLine;
use App\Modules\Agency\Models\Agency;
use App\Modules\Agency\Models\AgencyContact;
use App\Modules\Business\Models\Business;
use App\Modules\Business\Models\BusinessContact;
use App\Modules\Business\Models\BusinessCourierAssignment;
use App\Modules\Courier\Models\Courier;
use App\Modules\Courier\Models\CourierBankAccount;
use App\Modules\Courier\Models\CourierVehicle;
use App\Modules\Finance\Models\CurrentAccount;
use App\Modules\Finance\Models\CurrentAccountMovement;
use App\Modules\Finance\Models\FinanceCollection;
use App\Modules\Finance\Models\FinanceCollectionPayment;
use App\Modules\Finance\Models\FinanceExpense;
use App\Modules\Finance\Models\FinanceInvoice;
use App\Modules\Finance\Models\FinancePayment;
use App\Modules\Finance\Models\FinancePaymentLine;
use App\Modules\Finance\Models\FinanceRevenue;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Support\Facades\DB;

/**
 * DEMO_SEED işaretli örnek veriyi ve bağlı kayıtları siler.
 * Yalnızca local/testing ortamında çalışır.
 */
final class DemoDataCleaner
{
    /**
     * @return array<string, int>
     */
    public static function clear(): array
    {
        DemoDataGuard::assertAllowed();

        $marker = DemoDataSeeder::MARKER;
        $counts = [];

        DB::transaction(function () use ($marker, &$counts): void {
            $businessIds = Business::withTrashed()
                ->where('notes', $marker)
                ->pluck('id');
            $agencyIds = Agency::withTrashed()
                ->where('notes', $marker)
                ->pluck('id');
            $courierIds = Courier::withTrashed()
                ->where('notes', $marker)
                ->pluck('id');
            $assignmentIds = BusinessCourierAssignment::withTrashed()
                ->where('notes', $marker)
                ->pluck('id');
            $earningIds = EarningLine::withTrashed()
                ->where('description', $marker)
                ->pluck('id');
            $revenueIds = FinanceRevenue::query()
                ->where(function ($query) use ($marker, $businessIds): void {
                    $query->where('description', $marker)
                        ->orWhereIn('business_id', $businessIds);
                })
                ->pluck('id');
            $collectionIds = FinanceCollection::query()
                ->where(function ($query) use ($marker, $businessIds, $revenueIds): void {
                    $query->where('description', $marker)
                        ->orWhereIn('business_id', $businessIds)
                        ->orWhereIn('revenue_id', $revenueIds);
                })
                ->pluck('id');
            $paymentIds = FinancePayment::query()
                ->where(function ($query) use ($marker, $courierIds, $agencyIds, $earningIds): void {
                    $query->where('description', $marker)
                        ->orWhereIn('courier_id', $courierIds)
                        ->orWhereIn('agency_id', $agencyIds)
                        ->orWhereIn('earning_line_id', $earningIds);
                })
                ->pluck('id');
            $accountIds = CurrentAccount::withTrashed()
                ->where(function ($query) use ($businessIds, $agencyIds, $courierIds): void {
                    $query->where(function ($q) use ($businessIds): void {
                        $q->where('accountable_type', Business::class)
                            ->whereIn('accountable_id', $businessIds);
                    })->orWhere(function ($q) use ($agencyIds): void {
                        $q->where('accountable_type', Agency::class)
                            ->whereIn('accountable_id', $agencyIds);
                    })->orWhere(function ($q) use ($courierIds): void {
                        $q->where('accountable_type', Courier::class)
                            ->whereIn('accountable_id', $courierIds);
                    });
                })
                ->pluck('id');

            $counts['finance_collection_payments'] = FinanceCollectionPayment::query()
                ->whereIn('collection_id', $collectionIds)
                ->delete();

            $counts['finance_collections'] = FinanceCollection::query()
                ->whereIn('id', $collectionIds)
                ->delete();

            $counts['finance_payment_lines'] = FinancePaymentLine::query()
                ->whereIn('payment_id', $paymentIds)
                ->delete();

            $counts['finance_payments'] = FinancePayment::query()
                ->whereIn('id', $paymentIds)
                ->delete();

            $counts['finance_invoices'] = FinanceInvoice::query()
                ->where(function ($query) use ($marker, $businessIds, $earningIds): void {
                    $query->where('description', $marker)
                        ->orWhereIn('business_id', $businessIds)
                        ->orWhereIn('earning_line_id', $earningIds);
                })
                ->delete();

            $counts['finance_revenues'] = FinanceRevenue::query()
                ->whereIn('id', $revenueIds)
                ->delete();

            $counts['finance_expenses'] = FinanceExpense::query()
                ->where(function ($query) use ($marker, $courierIds, $agencyIds, $earningIds): void {
                    $query->where('description', $marker)
                        ->orWhereIn('courier_id', $courierIds)
                        ->orWhereIn('agency_id', $agencyIds)
                        ->orWhereIn('earning_line_id', $earningIds);
                })
                ->delete();

            $counts['current_account_movements'] = CurrentAccountMovement::query()
                ->where(function ($query) use ($marker, $accountIds): void {
                    $query->where('description', $marker)
                        ->orWhereIn('current_account_id', $accountIds);
                })
                ->delete();

            $counts['current_accounts'] = CurrentAccount::withTrashed()
                ->whereIn('id', $accountIds)
                ->forceDelete();

            $counts['earning_lines'] = EarningLine::withTrashed()
                ->whereIn('id', $earningIds)
                ->forceDelete();

            $counts['assignments'] = BusinessCourierAssignment::withTrashed()
                ->where(function ($query) use ($assignmentIds, $businessIds, $courierIds): void {
                    $query->whereIn('id', $assignmentIds)
                        ->orWhereIn('business_id', $businessIds)
                        ->orWhereIn('courier_id', $courierIds);
                })
                ->forceDelete();

            $counts['business_contacts'] = BusinessContact::query()
                ->whereIn('business_id', $businessIds)
                ->delete();

            $counts['agency_contacts'] = AgencyContact::query()
                ->whereIn('agency_id', $agencyIds)
                ->delete();

            $counts['courier_vehicles'] = CourierVehicle::query()
                ->whereIn('courier_id', $courierIds)
                ->delete();

            $counts['courier_bank_accounts'] = CourierBankAccount::query()
                ->whereIn('courier_id', $courierIds)
                ->delete();

            $counts['documents'] = Document::withTrashed()
                ->where(function ($query) use ($businessIds, $agencyIds, $courierIds): void {
                    $query->where(function ($q) use ($businessIds): void {
                        $q->where('documentable_type', Business::class)
                            ->whereIn('documentable_id', $businessIds);
                    })->orWhere(function ($q) use ($agencyIds): void {
                        $q->where('documentable_type', Agency::class)
                            ->whereIn('documentable_id', $agencyIds);
                    })->orWhere(function ($q) use ($courierIds): void {
                        $q->where('documentable_type', Courier::class)
                            ->whereIn('documentable_id', $courierIds);
                    });
                })
                ->forceDelete();

            $counts['contracts'] = Contract::withTrashed()
                ->where(function ($query) use ($businessIds, $agencyIds, $courierIds): void {
                    $query->where(function ($q) use ($businessIds): void {
                        $q->where('contractable_type', Business::class)
                            ->whereIn('contractable_id', $businessIds);
                    })->orWhere(function ($q) use ($agencyIds): void {
                        $q->where('contractable_type', Agency::class)
                            ->whereIn('contractable_id', $agencyIds);
                    })->orWhere(function ($q) use ($courierIds): void {
                        $q->where('contractable_type', Courier::class)
                            ->whereIn('contractable_id', $courierIds);
                    });
                })
                ->forceDelete();

            $counts['businesses'] = Business::withTrashed()
                ->whereIn('id', $businessIds)
                ->forceDelete();

            $counts['agencies'] = Agency::withTrashed()
                ->whereIn('id', $agencyIds)
                ->forceDelete();

            $counts['couriers'] = Courier::withTrashed()
                ->whereIn('id', $courierIds)
                ->forceDelete();
        });

        return $counts;
    }
}
