<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\Mail\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Zero\Mail\Model\MailLog;

class Status implements OptionSourceInterface
{
    /**
     * @return array<int, array{value: string, label: \Magento\Framework\Phrase}>
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => MailLog::SENT, 'label' => __('Sent')],
            ['value' => MailLog::FAILED, 'label' => __('Failed')],
            ['value' => MailLog::SKIPPED, 'label' => __('Test address, not sent')],
            ['value' => MailLog::LOGGED, 'label' => __('Logged only')],
            ['value' => MailLog::DISABLED, 'label' => __('Email communications off')],
            ['value' => MailLog::QUEUED, 'label' => __('Queued')],
            ['value' => MailLog::SENDING, 'label' => __('Sending')],
        ];
    }
}
