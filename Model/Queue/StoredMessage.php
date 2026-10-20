<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */
declare(strict_types=1);

namespace Zero\Mail\Model\Queue;

use Magento\Framework\Mail\Address;
use Magento\Framework\Mail\EmailMessage;
use Symfony\Component\Mime\Address as MimeAddress;
use Symfony\Component\Mime\Header\MailboxHeader;
use Symfony\Component\Mime\Header\MailboxListHeader;
use Symfony\Component\Mime\Message as SymfonyMessage;

/**
 * A queued email, back from the Email Log, as Magento's transport takes it:
 * the Symfony message it was built as, complete, so the parent constructor
 * (which builds one from parts) is not run. Plugin\Transport lets it through
 * unlogged: it was logged when queued.
 */
class StoredMessage extends EmailMessage
{
    /**
     * @SuppressWarnings(PHPMD.MissingParentConstructor)
     */
    public function __construct(SymfonyMessage $message)
    {
        $this->symfonyMessage = $message;
        $this->charset = 'utf-8';
    }

    public function getFrom(): ?array
    {
        return $this->addresses('From');
    }

    public function getTo(): array
    {
        return $this->addresses('To') ?? [];
    }

    public function getCc(): ?array
    {
        return $this->addresses('Cc');
    }

    public function getBcc(): ?array
    {
        return $this->addresses('Bcc');
    }

    public function getReplyTo(): ?array
    {
        return $this->addresses('Reply-To');
    }

    public function getSender(): ?Address
    {
        $header = $this->symfonyMessage->getHeaders()->get('Sender');
        $address = $header instanceof MailboxHeader ? $header->getAddress() : null;
        return $address ? new Address($address->getAddress(), $address->getName() ?: null) : null;
    }

    /**
     * @return Address[]|null
     */
    private function addresses(string $name): ?array
    {
        $header = $this->symfonyMessage->getHeaders()->get($name);
        if (!$header instanceof MailboxListHeader) {
            return null;
        }
        return array_map(
            static fn (MimeAddress $a) => new Address($a->getAddress(), $a->getName() ?: null),
            $header->getAddresses()
        );
    }
}
