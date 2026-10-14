<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\Mail\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Mail\AddressConverter;
use Magento\Framework\Mail\EmailMessageInterfaceFactory;
use Magento\Framework\Mail\MimeInterface;
use Magento\Framework\Mail\MimeMessageInterfaceFactory;
use Magento\Framework\Mail\MimePartInterfaceFactory;
use Magento\Framework\Mail\TransportInterfaceFactory;

/**
 * An Email Log entry sent again: the message rebuilt from the log, out
 * through the transport (so logged again, as resent from the entry).
 */
class Resender
{
    public function __construct(
        private readonly MailLog $log,
        private readonly Mailer $mailer,
        private readonly AddressConverter $addressConverter,
        private readonly MimePartInterfaceFactory $mimePartFactory,
        private readonly MimeMessageInterfaceFactory $mimeMessageFactory,
        private readonly EmailMessageInterfaceFactory $emailMessageFactory,
        private readonly TransportInterfaceFactory $transportFactory
    ) {
    }

    /**
     * @throws LocalizedException
     */
    public function resend(int $id): void
    {
        $entry = $this->log->get($id);
        if ($entry === null) {
            throw new LocalizedException(__('The email is no longer in the log.'));
        }
        if (Secrets::wasRedacted((string) $entry['body'])) {
            throw new LocalizedException(
                __('This email carried a one-time link or code, which the log does not keep. Ask the customer to request a new one.')
            );
        }
        $recipients = json_decode((string) $entry['recipients'], true) ?: [];
        $addresses = fn (string $kind) => array_map(
            fn (array $a) => $this->addressConverter->convert($a['email'], $a['name'] ?: null),
            $recipients[$kind] ?? []
        );
        $from = $this->parseSender((string) $entry['sender']);
        $part = $this->mimePartFactory->create([
            'content' => (string) $entry['body'],
            'type' => $entry['body_type'] === 'plain' ? MimeInterface::TYPE_TEXT : MimeInterface::TYPE_HTML,
        ]);
        $message = $this->emailMessageFactory->create([
            'body' => $this->mimeMessageFactory->create(['parts' => [$part]]),
            'to' => $addresses('to'),
            'cc' => $addresses('cc') ?: null,
            'bcc' => $addresses('bcc') ?: null,
            'from' => $from ? [$this->addressConverter->convert($from['email'], $from['name'])] : null,
            'subject' => (string) $entry['subject'],
            'encoding' => $part->getCharset(),
        ]);
        $this->mailer->resending($id, fn () => $this->transportFactory->create(['message' => $message])->sendMessage());
    }

    /**
     * "Name <email>" or "email".
     *
     * @return array{email: string, name: string|null}|null
     */
    private function parseSender(string $sender): ?array
    {
        if (preg_match('/^(.*)<([^>]+)>$/', trim($sender), $m)) {
            return ['email' => trim($m[2]), 'name' => trim($m[1]) ?: null];
        }

        return $sender !== '' ? ['email' => trim($sender), 'name' => null] : null;
    }
}
