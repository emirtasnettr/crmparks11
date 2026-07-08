<?php

use App\Core\Helpers\MoneyCalculator;

if (! function_exists('money_excl_vat')) {
    function money_excl_vat(float|int|null $amount, int $decimals = 2): string
    {
        return MoneyCalculator::format((float) ($amount ?? 0), $decimals);
    }
}
