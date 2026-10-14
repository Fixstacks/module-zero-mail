<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\Mail\Plugin;

use Magento\Framework\Mail\EmailMessage;
use Magento\Framework\Mail\TransportInterface;
use Zero\Mail\Model\Mailer;

/**
 * Every email through Zero_Mail's Mailer, around Magento's transport (outside
 * its Disable Email Communications plugin, which the Mailer checks itself).
 */
class Transport
{
    public function __construct(
        private readonly Mailer $mailer
    ) {
    }

    public function aroundSendMessage(TransportInterface $subject, callable $proceed): void
    {
        $message = $subject->getMessage();
        if (!$message instanceof EmailMessage) {
            $proceed();
            return;
        }
        $this->mailer->send($message, $proceed);
    }
}
