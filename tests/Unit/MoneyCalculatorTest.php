<?php

namespace Tests\Unit;

use App\Core\Helpers\MoneyCalculator;
use PHPUnit\Framework\TestCase;

class MoneyCalculatorTest extends TestCase
{
    public function test_format_returns_amount_without_inline_vat_suffix(): void
    {
        $this->assertSame('1.234,56 ₺', MoneyCalculator::format(1234.56));
    }

    public function test_format_vat_amount_matches_standard_format(): void
    {
        $this->assertSame('246,91 ₺', MoneyCalculator::formatVatAmount(246.91));
    }

    public function test_format_including_vat_labels_gross_amount(): void
    {
        $this->assertSame('1.481,47 ₺ (KDV dahil)', MoneyCalculator::formatIncludingVat(1481.47));
    }
}
