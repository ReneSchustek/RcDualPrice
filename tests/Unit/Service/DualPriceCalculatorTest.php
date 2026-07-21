<?php

declare(strict_types=1);

namespace Ruhrcoder\RcDualPrice\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;
use Ruhrcoder\RcDualPrice\Service\DualPriceCalculator;

class DualPriceCalculatorTest extends TestCase
{
    private DualPriceCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new DualPriceCalculator();
    }

    public function testGrossToNet(): void
    {
        // 119,00 brutto bei 19 % -> 100,00 netto
        self::assertSame(100.0, $this->calculator->convert(119.0, 19.0, DualPriceCalculator::STATE_GROSS));
    }

    public function testNetToGross(): void
    {
        // 100,00 netto bei 19 % -> 119,00 brutto
        self::assertSame(119.0, $this->calculator->convert(100.0, 19.0, DualPriceCalculator::STATE_NET));
    }

    public function testReducedRateGrossToNet(): void
    {
        // 107,00 brutto bei 7 % -> 100,00 netto
        self::assertSame(100.0, $this->calculator->convert(107.0, 7.0, DualPriceCalculator::STATE_GROSS));
    }

    public function testResultIsRoundedToTwoDecimals(): void
    {
        // 9,99 brutto / 1.19 = 8.39495... -> 8,39 (deterministisch gerundet, keine sub-Cent-Drift)
        self::assertSame(8.39, $this->calculator->convert(9.99, 19.0, DualPriceCalculator::STATE_GROSS));
    }

    public function testTaxFreeStateReturnsNull(): void
    {
        self::assertNull($this->calculator->convert(100.0, 19.0, 'tax-free'));
    }

    public function testUnknownStateReturnsNull(): void
    {
        self::assertNull($this->calculator->convert(100.0, 19.0, 'something'));
    }

    public function testNullStateReturnsNull(): void
    {
        self::assertNull($this->calculator->convert(100.0, 19.0, null));
    }

    public function testZeroTaxRateReturnsNull(): void
    {
        self::assertNull($this->calculator->convert(100.0, 0.0, DualPriceCalculator::STATE_GROSS));
    }

    public function testNullTaxRateReturnsNull(): void
    {
        self::assertNull($this->calculator->convert(100.0, null, DualPriceCalculator::STATE_GROSS));
    }

    public function testNullUnitPriceReturnsNull(): void
    {
        self::assertNull($this->calculator->convert(null, 19.0, DualPriceCalculator::STATE_GROSS));
    }
}
