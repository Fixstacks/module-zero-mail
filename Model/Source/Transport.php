<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\Mail\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Zero\Mail\Model\Config;

class Transport implements OptionSourceInterface
{
    /**
     * @return array<int, array{value: string, label: \Magento\Framework\Phrase}>
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => Config::TRANSPORT_MAGENTO, 'label' => __('Magento (sendmail or SMTP)')],
            ['value' => Config::TRANSPORT_SES, 'label' => __('Amazon SES')],
            ['value' => Config::TRANSPORT_LOG, 'label' => __('Log only, send nothing')],
        ];
    }
}
