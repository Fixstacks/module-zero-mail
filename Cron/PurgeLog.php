<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\Mail\Cron;

use Zero\Mail\Model\Config;
use Zero\Mail\Model\MailLog;

class PurgeLog
{
    public function __construct(
        private readonly MailLog $log,
        private readonly Config $config
    ) {
    }

    public function execute(): void
    {
        $this->log->purge($this->config->logDays());
    }
}
