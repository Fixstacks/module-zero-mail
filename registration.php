<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 *
 * Zero_Mail: how the store's emails leave, and a log of each. Transport:
 * Magento's own, Amazon SES via its API with the server's AWS role, or none.
 * Email Log (Marketing > Communications) with Resend. Test addresses
 * (example.com, ...) are logged, not sent. One sender for all emails, and a
 * Send Test Email button.
 */
use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(ComponentRegistrar::MODULE, 'Zero_Mail', __DIR__);
