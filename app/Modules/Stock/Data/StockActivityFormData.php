<?php

namespace App\Modules\Stock\Data;

final class StockActivityFormData
{
    /**
     * @return array<string, string>
     */
    public static function actionTypes(): array
    {
        return [
            'stock_product_created' => 'Ürün Oluşturuldu',
            'stock_product_updated' => 'Ürün Güncellendi',
            'stock_quantity_increased' => 'Stok Artırıldı',
            'stock_quantity_decreased' => 'Stok Düşürüldü',
            'stock_assigned' => 'Zimmet Verildi',
            'stock_returned' => 'Zimmet İade Alındı',
        ];
    }

    /**
     * @return list<string>
     */
    public static function actionKeys(): array
    {
        return array_keys(self::actionTypes());
    }

    /**
     * @return array<string, string>
     */
    public static function dateRanges(): array
    {
        return [
            'last_7_days' => 'Son 7 Gün',
            'last_30_days' => 'Son 30 Gün',
            'this_month' => 'Bu Ay',
            'last_3_months' => 'Son 3 Ay',
            'this_year' => 'Bu Yıl',
        ];
    }
}
