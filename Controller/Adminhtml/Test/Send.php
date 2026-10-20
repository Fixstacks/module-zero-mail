<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\Mail\Controller\Adminhtml\Test;

use Magento\Backend\App\Action;
use Magento\Backend\Model\Auth\Session;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Area;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Store\Model\StoreManagerInterface;
use Zero\Mail\Model\Config;
use Zero\Mail\Model\MailLog;
use Zero\Mail\Model\Mailer;

/**
 * Send Test Email (Stores > Configuration > Zero > Mail), then what became of it.
 */
class Send extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Zero_Mail::config';

    public function __construct(
        Action\Context $context,
        private readonly Session $authSession,
        private readonly TransportBuilder $transportBuilder,
        private readonly Mailer $mailer,
        private readonly MailLog $log,
        private readonly Config $config,
        private readonly StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
    }

    public function execute(): Redirect
    {
        $redirect = $this->resultRedirectFactory->create()
            ->setPath('adminhtml/system_config/edit', ['section' => 'zero_mail']);
        $email = (string) $this->authSession->getUser()?->getEmail();
        try {
            $this->transportBuilder
                ->setTemplateIdentifier('zero_mail_test')
                // The storefront's email design (header, footer, logo) of the default store view.
                ->setTemplateOptions([
                    'area' => Area::AREA_FRONTEND,
                    'store' => (int) $this->storeManager->getDefaultStoreView()?->getId(),
                ])
                ->setTemplateVars(['transport' => $this->config->transport()])
                ->setFromByScope('general')
                ->addTo($email)
                ->getTransport()
                ->sendMessage();
        } catch (\Throwable $e) {
            $this->messageManager->addErrorMessage(__('The test email to %1 failed: %2', $email, $e->getMessage()));
            return $redirect;
        }
        $entry = $this->mailer->lastLogId() ? $this->log->get((int) $this->mailer->lastLogId()) : null;
        match ($entry['status'] ?? null) {
            MailLog::SENT => $this->messageManager->addSuccessMessage(__('A test email went to %1.', $email)),
            MailLog::QUEUED => $this->messageManager->addSuccessMessage(
                __('A test email to %1 is queued: the consumer zero.mail.send sends it (cron starts it). See the Email Log.', $email)
            ),
            MailLog::SKIPPED => $this->messageManager->addNoticeMessage(
                __('%1 is a test address (Test Address Domains): the email is in the Email Log, not sent.', $email)
            ),
            MailLog::LOGGED => $this->messageManager->addNoticeMessage(
                __('Send Through is "Log only": the email is in the Email Log, not sent.')
            ),
            MailLog::DISABLED => $this->messageManager->addNoticeMessage(
                __('Email communications are off (Advanced > System > Mail Sending Settings): not sent.')
            ),
            default => $this->messageManager->addNoticeMessage(__('Sent to %1; see the Email Log.', $email)),
        };

        return $redirect;
    }
}
