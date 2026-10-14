<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\Mail\Model;

/**
 * What the Email Log must not keep: one-time links (password reset and
 * account confirmation, abandoned cart, stock alert, quote request: their
 * `token`, `key` or `rp_token` query value) and gift card codes. An admin who
 * reads the log then can't use them. A redacted email can't be resent either:
 * the customer asks for a new one instead.
 */
class Secrets
{
    public const MARK = '[zero-mail:redacted]';

    private const QUERY_PARAMS = 'token|key|rp_token|resetPasswordToken|code';
    private const GIFT_CARD_CODE = '/\b[A-HJ-NP-Z2-9]{4}(?:-[A-HJ-NP-Z2-9]{4}){3}\b/';

    public static function redact(string $body): string
    {
        $body = (string) preg_replace(
            '/([?&;](?:' . self::QUERY_PARAMS . ')=)[^&"\'<>\s]+/i',
            '$1' . self::MARK,
            $body
        );

        return (string) preg_replace(self::GIFT_CARD_CODE, self::MARK, $body);
    }

    public static function wasRedacted(string $body): bool
    {
        return str_contains($body, self::MARK);
    }
}
