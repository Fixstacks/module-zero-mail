<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\Mail\Test\Unit\Model;

use PHPUnit\Framework\TestCase;
use Zero\Mail\Model\Secrets;

/**
 * The log keeps no password reset or confirmation link, no abandoned cart,
 * stock alert or quote request link, and no gift card code.
 */
class SecretsTest extends TestCase
{
    public function testOneTimeLinksLoseTheirSecret(): void
    {
        $body = '<a href="https://shop.test/customer/account/createPassword/?token=AbC123xyz&amp;id=5">Reset</a>'
            . ' <a href="https://shop.test/customer/account/confirm/?id=5&key=K9y8">Confirm</a>'
            . ' https://shop.test/abandoned/cart/restore?token=t0k3n plain text'
            . ' <a href="https://shop.test/quotes/view?token=q1&utm_source=mail">Quote</a>';

        $redacted = Secrets::redact($body);

        $this->assertStringNotContainsString('AbC123xyz', $redacted);
        $this->assertStringNotContainsString('K9y8', $redacted);
        $this->assertStringNotContainsString('t0k3n', $redacted);
        $this->assertStringNotContainsString('token=q1', $redacted);
        $this->assertStringContainsString('&amp;id=5', $redacted);
        $this->assertStringContainsString('utm_source=mail', $redacted);
        $this->assertStringContainsString('?id=5&key=' . Secrets::MARK, $redacted);
        $this->assertTrue(Secrets::wasRedacted($redacted));
    }

    public function testGiftCardCodesAreGone(): void
    {
        $redacted = Secrets::redact('<p>Your code</p><p>ABCD-EFGH-JKMN-PQRS</p> order #100000123');

        $this->assertStringNotContainsString('ABCD-EFGH-JKMN-PQRS', $redacted);
        $this->assertStringContainsString('order #100000123', $redacted);
    }

    public function testAnOrdinaryEmailIsUntouched(): void
    {
        $body = '<p>Thank you for your order #100000123.</p><a href="https://shop.test/sales/order/view/order_id/12/">Order</a>';

        $this->assertSame($body, Secrets::redact($body));
        $this->assertFalse(Secrets::wasRedacted($body));
    }
}
