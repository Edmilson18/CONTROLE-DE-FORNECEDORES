<?php

namespace Tests\Unit;

use App\Support\BrazilianDocuments;
use App\Support\Money;
use PHPUnit\Framework\TestCase;

class BrazilianDocumentsTest extends TestCase
{
    public function test_documents_and_money(): void
    {
        $this->assertTrue(BrazilianDocuments::validCnpj('11222333000181'));
        $this->assertTrue(BrazilianDocuments::validCnpj('12.ABC.345/01DE-35'));
        $this->assertFalse(BrazilianDocuments::validCnpj('00000000000000'));
        $this->assertFalse(BrazilianDocuments::validCnpj('11222333000182'));
        $this->assertSame('(11) 3333-4444', BrazilianDocuments::phone('1133334444'));
        $this->assertSame('10.01', Money::decimal(Money::cents('10.01')));
        $this->assertSame(999999999999, Money::cents('9999999999.99'));
    }
}
