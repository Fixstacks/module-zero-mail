<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\Mail\Model;

use Magento\Framework\Mail\Address;
use Magento\Framework\Mail\EmailMessage;
use Symfony\Component\Mime\Address as MimeAddress;
use Symfony\Component\Mime\Part\AbstractMultipartPart;
use Symfony\Component\Mime\Part\TextPart;

/**
 * Every email on its way out (Plugin\Transport): the sender address set,
 * logged (without its one-time links and gift card codes: Secrets), then sent
 * through the transport chosen, or not: test addresses and "log only" stay in
 * the log.
 */
class Mailer
{
    private ?int $resending = null;
    private ?int $lastLogId = null;

    public function __construct(
        private readonly Config $config,
        private readonly MailLog $log,
        private readonly Ses $ses
    ) {
    }

    /**
     * @param callable(): void $magento Magento's own transport
     * @throws \Throwable what the transport threw, logged first
     */
    public function send(EmailMessage $message, callable $magento): void
    {
        $this->setSender($message);
        $recipients = [
            'to' => $this->emails($message->getTo()),
            'cc' => $this->emails($message->getCc() ?? []),
            'bcc' => $this->emails($message->getBcc() ?? []),
        ];
        [$body, $type] = $this->body($message);
        $from = $message->getFrom()[0] ?? null;
        $transport = $this->config->transport();
        $id = $this->log->add([
            'transport' => $transport,
            'subject' => mb_substr((string) $message->getSubject(), 0, 255),
            'sender' => $from ? mb_substr($this->format($from), 0, 255) : null,
            'recipient' => mb_substr(implode(', ', array_map([$this, 'format'], $message->getTo())), 0, 512),
            'recipients' => json_encode([
                'to' => $this->pairs($message->getTo()),
                'cc' => $this->pairs($message->getCc() ?? []),
                'bcc' => $this->pairs($message->getBcc() ?? []),
            ], JSON_UNESCAPED_UNICODE),
            'body' => Secrets::redact($body),
            'body_type' => $type,
            'resent_from' => $this->resending,
        ]);
        $this->lastLogId = $id;
        if ($this->config->isDisabled()) {
            $this->log->finish($id, MailLog::DISABLED);
            return;
        }
        $all = array_merge(...array_values($recipients));
        if ($all && count(array_filter($all, [$this->config, 'isTestAddress'])) === count($all)) {
            $this->log->finish($id, MailLog::SKIPPED);
            return;
        }
        try {
            $messageId = null;
            if ($transport === Config::TRANSPORT_SES) {
                $messageId = $this->ses->send($this->raw($message), (string) $from?->getEmail(), $recipients);
            } elseif ($transport === Config::TRANSPORT_MAGENTO) {
                $magento();
            }
        } catch (\Throwable $e) {
            $this->log->finish($id, MailLog::FAILED, $e->getMessage());
            throw $e;
        }
        $status = $transport === Config::TRANSPORT_LOG ? MailLog::LOGGED : MailLog::SENT;
        $this->log->finish($id, $status, null, $messageId);
    }

    /**
     * The Email Log entry of the last email sent in this request.
     */
    public function lastLogId(): ?int
    {
        return $this->lastLogId;
    }

    /**
     * Marks the emails sent while $send runs as resent from log entry $id.
     */
    public function resending(int $id, callable $send): void
    {
        $this->resending = $id;
        try {
            $send();
        } finally {
            $this->resending = null;
        }
    }

    private function setSender(EmailMessage $message): void
    {
        $email = $this->config->senderEmail();
        if ($email === '') {
            return;
        }
        $from = $message->getFrom()[0] ?? null;
        $headers = $message->getSymfonyMessage()->getHeaders();
        $headers->remove('From');
        $headers->addMailboxListHeader('From', [new MimeAddress($email, (string) $from?->getName())]);
    }

    /**
     * The message as SES takes it: MIME, without the Bcc header.
     */
    private function raw(EmailMessage $message): string
    {
        $copy = clone $message->getSymfonyMessage();
        $copy->getHeaders()->remove('Bcc');

        return $copy->toString();
    }

    /**
     * @return array{0: string, 1: string} the HTML (or plain text) part, and which
     */
    private function body(EmailMessage $message): array
    {
        $part = $message->getSymfonyMessage()->getBody();
        $parts = $part instanceof AbstractMultipartPart ? $part->getParts() : [$part];
        $text = null;
        foreach ($parts as $candidate) {
            if ($candidate instanceof TextPart && $candidate->getMediaSubtype() === 'html') {
                return [$candidate->getBody(), 'html'];
            }
            if ($candidate instanceof TextPart && $text === null) {
                $text = $candidate->getBody();
            }
        }

        return [(string) $text, 'plain'];
    }

    /**
     * @param Address[] $addresses
     * @return string[]
     */
    private function emails(array $addresses): array
    {
        return array_values(array_filter(array_map(static fn (Address $a) => (string) $a->getEmail(), $addresses)));
    }

    /**
     * @param Address[] $addresses
     * @return array<int, array{email: string, name: string}>
     */
    private function pairs(array $addresses): array
    {
        return array_map(
            static fn (Address $a) => ['email' => (string) $a->getEmail(), 'name' => (string) $a->getName()],
            $addresses
        );
    }

    private function format(Address $address): string
    {
        return $address->getName()
            ? $address->getName() . ' <' . $address->getEmail() . '>'
            : (string) $address->getEmail();
    }
}
