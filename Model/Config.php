<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\Mail\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * zero_mail/* (default scope: one way out for the whole installation).
 */
class Config
{
    public const TRANSPORT_MAGENTO = 'magento';
    public const TRANSPORT_SES = 'ses';
    public const TRANSPORT_LOG = 'log';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    public function transport(): string
    {
        $transport = (string) $this->scopeConfig->getValue('zero_mail/general/transport');

        return in_array($transport, [self::TRANSPORT_SES, self::TRANSPORT_LOG], true)
            ? $transport
            : self::TRANSPORT_MAGENTO;
    }

    public function senderEmail(): string
    {
        return trim((string) $this->scopeConfig->getValue('zero_mail/general/sender_email'));
    }

    /**
     * @return string[]
     */
    public function testDomains(): array
    {
        $value = strtolower((string) $this->scopeConfig->getValue('zero_mail/general/test_domains'));
        $lines = preg_split('/[\s,]+/', $value);

        return array_values(array_filter(array_map(static fn ($d) => trim($d, " .\t"), (array) $lines)));
    }

    /**
     * An address on a test domain or a subdomain of one.
     */
    public function isTestAddress(string $email): bool
    {
        $domain = strtolower(substr((string) strrchr($email, '@'), 1));
        foreach ($this->testDomains() as $test) {
            if ($domain === $test || str_ends_with($domain, '.' . $test)) {
                return true;
            }
        }

        return false;
    }

    public function logDays(): int
    {
        return max(1, (int) $this->scopeConfig->getValue('zero_mail/general/log_days'));
    }

    public function sesRegion(): string
    {
        return trim((string) $this->scopeConfig->getValue('zero_mail/ses/region')) ?: 'us-east-1';
    }

    public function sesConfigurationSet(): string
    {
        return trim((string) $this->scopeConfig->getValue('zero_mail/ses/configuration_set'));
    }

    /**
     * Advanced > System > Mail Sending Settings > Disable Email Communications.
     */
    public function isDisabled(): bool
    {
        return $this->scopeConfig->isSetFlag('system/smtp/disable');
    }
}
