<?php

namespace App\Modules\Finance\Exports;

use App\Core\Exports\ListExport;
use App\Modules\Finance\Data\FinanceActivityLogDummyData;
use App\Modules\Finance\Data\FinanceCollectionDummyData;
use App\Modules\Finance\Data\FinanceCurrentAccountDummyData;
use App\Modules\Finance\Data\FinanceExpenseDummyData;
use App\Modules\Finance\Data\FinanceInvoiceDummyData;
use App\Modules\Finance\Data\FinancePaymentDummyData;
use App\Modules\Finance\Data\FinanceProfitabilityDummyData;
use App\Modules\Finance\Data\FinanceRevenueDummyData;

final class FinanceListExportSheets
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array{headings: array<int, string>, rows: array<int, array<int, mixed>>}
     */
    public static function currentAccounts(array $filters): array
    {
        return ListExport::sheet(
            FinanceCurrentAccountDummyData::filter($filters),
            ['Cari Kodu', 'Cari Ünvanı', 'Cari Tipi', 'Telefon', 'Borç', 'Alacak', 'Bakiye', 'Son Hareket', 'Son Hareket Türü', 'Durum'],
            [
                fn (array $row) => $row['code'],
                fn (array $row) => $row['title'],
                fn (array $row) => $row['type_label'],
                fn (array $row) => $row['phone'],
                fn (array $row) => $row['total_debit_formatted'],
                fn (array $row) => $row['total_credit_formatted'],
                fn (array $row) => $row['balance_formatted'],
                fn (array $row) => $row['last_movement_formatted'],
                fn (array $row) => $row['last_movement_label'],
                fn (array $row) => $row['status_label'],
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{headings: array<int, string>, rows: array<int, array<int, mixed>>}
     */
    public static function revenues(array $filters): array
    {
        return ListExport::sheet(
            FinanceRevenueDummyData::filter($filters),
            ['Gelir No', 'İşletme', 'Gelir Türü', 'Hakediş Dönemi', 'Fatura No', 'Tutar', 'Tahsil Durumu', 'Tahsil Tarihi', 'Oluşturulma Tarihi'],
            [
                fn (array $row) => $row['reference'],
                fn (array $row) => $row['business_name'],
                fn (array $row) => $row['revenue_type_label'],
                fn (array $row) => $row['period_display'],
                fn (array $row) => $row['invoice_no_display'],
                fn (array $row) => $row['amount_formatted'],
                fn (array $row) => $row['collection_status_label'],
                fn (array $row) => $row['collection_date_formatted'],
                fn (array $row) => $row['created_at_formatted'],
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{headings: array<int, string>, rows: array<int, array<int, mixed>>}
     */
    public static function expenses(array $filters): array
    {
        return ListExport::sheet(
            FinanceExpenseDummyData::filter($filters),
            ['Gider No', 'Gider Türü', 'Kurye / Acente', 'Açıklama', 'Tutar', 'Ödeme Durumu', 'Ödeme Tarihi', 'Oluşturulma Tarihi'],
            [
                fn (array $row) => $row['reference'],
                fn (array $row) => $row['expense_type_label'],
                fn (array $row) => $row['payee_display'],
                fn (array $row) => $row['description'],
                fn (array $row) => $row['amount_formatted'],
                fn (array $row) => $row['payment_status_label'],
                fn (array $row) => $row['payment_date_formatted'],
                fn (array $row) => $row['created_at_formatted'],
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{headings: array<int, string>, rows: array<int, array<int, mixed>>}
     */
    public static function collections(array $filters): array
    {
        return ListExport::sheet(
            FinanceCollectionDummyData::filter($filters),
            ['Tahsilat No', 'İşletme', 'Gelir No', 'Fatura No', 'Vade Tarihi', 'Tahsilat Tarihi', 'Tutar', 'Kalan Tutar', 'Ödeme Yöntemi', 'Durum'],
            [
                fn (array $row) => $row['reference'],
                fn (array $row) => $row['business_name'],
                fn (array $row) => $row['revenue_reference_display'],
                fn (array $row) => $row['invoice_no_display'],
                fn (array $row) => $row['due_date_formatted'],
                fn (array $row) => $row['collection_date_formatted'],
                fn (array $row) => $row['total_amount_formatted'],
                fn (array $row) => $row['remaining_amount_formatted'],
                fn (array $row) => $row['payment_method_label'],
                fn (array $row) => $row['status_label'],
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{headings: array<int, string>, rows: array<int, array<int, mixed>>}
     */
    public static function payments(array $filters): array
    {
        return ListExport::sheet(
            FinancePaymentDummyData::filter($filters),
            ['Ödeme No', 'Alıcı', 'Alıcı Türü', 'Hakediş No', 'Ödeme Tarihi', 'Ödeme Yöntemi', 'Ödenecek Tutar', 'Ödenen Tutar', 'Durum'],
            [
                fn (array $row) => $row['reference'],
                fn (array $row) => $row['recipient_name'],
                fn (array $row) => $row['recipient_type_label'],
                fn (array $row) => $row['earning_reference_display'],
                fn (array $row) => $row['payment_date_formatted'],
                fn (array $row) => $row['payment_method_label'],
                fn (array $row) => $row['total_amount_formatted'],
                fn (array $row) => $row['paid_amount_formatted'],
                fn (array $row) => $row['status_label'],
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{headings: array<int, string>, rows: array<int, array<int, mixed>>}
     */
    public static function invoices(array $filters): array
    {
        return ListExport::sheet(
            FinanceInvoiceDummyData::filter($filters),
            ['Fatura No', 'İşletme', 'Hakediş Dönemi', 'Fatura Tarihi', 'Vade Tarihi', 'Tutar (KDV Hariç)', 'KDV', 'Tahsilat Durumu', 'Fatura Durumu'],
            [
                fn (array $row) => $row['reference'],
                fn (array $row) => $row['business_name'],
                fn (array $row) => $row['earning_period_display'],
                fn (array $row) => $row['invoice_date_formatted'],
                fn (array $row) => $row['due_date_formatted'],
                fn (array $row) => $row['subtotal_formatted'],
                fn (array $row) => $row['vat_amount_formatted'],
                fn (array $row) => $row['collection_status_label'],
                fn (array $row) => $row['invoice_status_label'],
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array{title: string, headings: array<int, string>, rows: array<int, array<int, mixed>>}>
     */
    public static function profitability(array $filters): array
    {
        $analysis = FinanceProfitabilityDummyData::analyze($filters);

        $businessSheet = ListExport::sheet(
            $analysis['business_table'],
            ['İşletme', 'Paket Sayısı', 'Gelir', 'Kurye Maliyeti', 'Acente Maliyeti', 'Diğer Gider', 'Net Kâr', 'Kâr Marjı'],
            [
                fn (array $row) => $row['business_name'],
                fn (array $row) => $row['package_count'],
                fn (array $row) => $row['revenue_formatted'],
                fn (array $row) => $row['courier_cost_formatted'],
                fn (array $row) => $row['agency_cost_formatted'],
                fn (array $row) => $row['other_expenses_formatted'],
                fn (array $row) => $row['net_profit_formatted'],
                fn (array $row) => $row['profit_margin_formatted'],
            ],
        );

        $agencySheet = ListExport::sheet(
            $analysis['agency_table'],
            ['Acente', 'Kurye Sayısı', 'Toplam Paket', 'Toplam Hakediş', 'Toplam Maliyet', 'Net Kâr'],
            [
                fn (array $row) => $row['agency_name'],
                fn (array $row) => $row['courier_count'],
                fn (array $row) => $row['total_packages'],
                fn (array $row) => $row['total_earning_formatted'],
                fn (array $row) => $row['total_cost_formatted'],
                fn (array $row) => $row['net_profit_formatted'],
            ],
        );

        $courierSheet = ListExport::sheet(
            $analysis['courier_table'],
            ['Kurye', 'İşletme', 'Paket Sayısı', 'Hakediş', 'Ek Ödeme', 'Kesinti', 'Toplam Maliyet'],
            [
                fn (array $row) => $row['courier_name'],
                fn (array $row) => $row['business_name'],
                fn (array $row) => $row['package_count'],
                fn (array $row) => $row['earning_formatted'],
                fn (array $row) => $row['extra_payment_formatted'],
                fn (array $row) => $row['deduction_formatted'],
                fn (array $row) => $row['total_cost_formatted'],
            ],
        );

        return [
            array_merge(['title' => 'İşletme Kârlılığı'], $businessSheet),
            array_merge(['title' => 'Acente Kârlılığı'], $agencySheet),
            array_merge(['title' => 'Kurye Maliyetleri'], $courierSheet),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{headings: array<int, string>, rows: array<int, array<int, mixed>>}
     */
    public static function activityLog(array $filters): array
    {
        return ListExport::sheet(
            FinanceActivityLogDummyData::filter($filters),
            ['Tarih', 'Saat', 'Modül', 'İşlem Türü', 'İşlem No', 'Cari', 'İşlemi Yapan', 'IP Adresi', 'Durum'],
            [
                fn (array $row) => $row['date_formatted'],
                fn (array $row) => $row['time_formatted'],
                fn (array $row) => $row['module_label'],
                fn (array $row) => $row['action_type_label'],
                fn (array $row) => $row['reference'],
                fn (array $row) => $row['current_account_name'],
                fn (array $row) => $row['user_name'],
                fn (array $row) => $row['ip_address'],
                fn (array $row) => $row['status_label'],
            ],
        );
    }
}
