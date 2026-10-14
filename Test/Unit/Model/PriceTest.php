<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\LayeredNavigation\Test\Unit\Model;

use Magento\Framework\App\RequestInterface;
use PHPUnit\Framework\TestCase;
use Zero\LayeredNavigation\Model\Price;

class PriceTest extends TestCase
{
    private function request(array $params): RequestInterface
    {
        $request = $this->createStub(RequestInterface::class);
        $request->method('getParam')->willReturnCallback(static fn ($name) => $params[$name] ?? null);

        return $request;
    }

    public function testTheFormsNumbersComeFirst(): void
    {
        $this->assertSame([500.0, 1200.0], Price::range($this->request(['price_from' => '1 200', 'price_to' => '500', 'price' => '1-2'])));
        $this->assertSame([null, 99.5], Price::range($this->request(['price_to' => '99,5'])));
    }

    public function testMagentosOwnRange(): void
    {
        $this->assertSame([100.0, 500.0], Price::range($this->request(['price' => '100-500'])));
        $this->assertSame([100.0, null], Price::range($this->request(['price' => '100-'])));
        $this->assertNull(Price::range($this->request(['price' => 'abc'])));
        $this->assertNull(Price::range($this->request([])));
    }
}
