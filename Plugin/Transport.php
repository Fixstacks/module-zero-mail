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
use Zero\Mail\Model\Queue\StoredMessage;

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
        // Magento 2.4.7 builds mail on Laminas, with no Symfony message to log or hand to SES:
        // there the email goes the way Magento sends it, outside the Email Log.
        if (!$message instanceof EmailMessage || !method_exists($message, 'getSymfonyMessage')) {
            $proceed();
            return;
        }
        // A queued email on its way out through Magento's transport (Queue\Consumer): logged already.
        if ($message instanceof StoredMessage) {
            $proceed();
            return;
        }
        $this->mailer->send($message, $proceed);
    }
}
