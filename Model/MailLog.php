<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\Mail\Model;

use Magento\Framework\App\ResourceConnection;

/**
 * The Email Log (zero_mail_log): a row per email, written as it goes out.
 */
class MailLog
{
    public const SENDING = 'sending';
    public const SENT = 'sent';
    public const LOGGED = 'logged';
    public const SKIPPED = 'skipped';
    public const DISABLED = 'disabled';
    public const FAILED = 'failed';

    private const TABLE = 'zero_mail_log';

    public function __construct(
        private readonly ResourceConnection $resource
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public function add(array $row): int
    {
        $db = $this->resource->getConnection();
        $db->insert($this->table(), $row + ['status' => self::SENDING, 'created_at' => gmdate('Y-m-d H:i:s')]);

        return (int) $db->lastInsertId($this->table());
    }

    public function finish(int $id, string $status, ?string $error = null, ?string $messageId = null): void
    {
        $this->resource->getConnection()->update(
            $this->table(),
            ['status' => $status, 'error' => $error, 'message_id' => $messageId],
            ['log_id = ?' => $id]
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function get(int $id): ?array
    {
        $db = $this->resource->getConnection();
        $row = $db->fetchRow($db->select()->from($this->table())->where('log_id = ?', $id));

        return $row ?: null;
    }

    /**
     * @return int rows deleted
     */
    public function purge(int $days, ?int $now = null): int
    {
        return $this->resource->getConnection()->delete(
            $this->table(),
            ['created_at < ?' => gmdate('Y-m-d H:i:s', ($now ?? time()) - $days * 86400)]
        );
    }

    private function table(): string
    {
        return $this->resource->getTableName(self::TABLE);
    }
}
