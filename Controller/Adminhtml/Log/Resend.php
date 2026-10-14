<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\Mail\Controller\Adminhtml\Log;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Zero\Mail\Model\Mailer;
use Zero\Mail\Model\MailLog;
use Zero\Mail\Model\Resender;

class Resend extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Zero_Mail::log';

    public function __construct(
        Action\Context $context,
        private readonly Resender $resender,
        private readonly Mailer $mailer,
        private readonly MailLog $log
    ) {
        parent::__construct($context);
    }

    public function execute(): Redirect
    {
        $id = (int) $this->getRequest()->getParam('id');
        $redirect = $this->resultRedirectFactory->create()->setPath('*/*/view', ['id' => $id]);
        try {
            $this->resender->resend($id);
        } catch (\Throwable $e) {
            $this->messageManager->addErrorMessage(__('Not resent: %1', $e->getMessage()));
            return $redirect;
        }
        $new = $this->mailer->lastLogId();
        $status = $new ? ($this->log->get($new)['status'] ?? '') : '';
        $this->messageManager->addSuccessMessage(__('Resent: entry #%1, %2.', $new, $status));

        return $new ? $redirect->setPath('*/*/view', ['id' => $new]) : $redirect;
    }
}
