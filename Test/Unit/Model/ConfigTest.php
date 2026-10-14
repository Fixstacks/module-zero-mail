<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\Mail\Test\Unit\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use PHPUnit\Framework\TestCase;
use Zero\Mail\Model\Config;

class ConfigTest extends TestCase
{
    public function testTestAddressesByDomainAndSubdomain(): void
    {
        $scopeConfig = $this->createStub(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturnMap([
            ['zero_mail/general/test_domains', 'default', null, " Example.com\n.test, invalid\n\n"],
        ]);
        $config = new Config($scopeConfig);

        $this->assertSame(['example.com', 'test', 'invalid'], $config->testDomains());
        $this->assertTrue($config->isTestAddress('zero-e2e+ua-1@example.com'));
        $this->assertTrue($config->isTestAddress('a@mail.EXAMPLE.com'));
        $this->assertTrue($config->isTestAddress('a@shop.test'));
        $this->assertFalse($config->isTestAddress('a@notexample.com'));
        $this->assertFalse($config->isTestAddress('a@example.com.ua'));
        $this->assertFalse($config->isTestAddress('a@gmail.com'));
    }

    public function testAnUnknownTransportIsMagentos(): void
    {
        $scopeConfig = $this->createStub(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturn('smtp');

        $this->assertSame(Config::TRANSPORT_MAGENTO, (new Config($scopeConfig))->transport());
    }
}
