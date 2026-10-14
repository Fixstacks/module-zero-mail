<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\Mail\Controller\Adminhtml\Log;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Zero\Mail\Model\MailLog;

/**
 * One email of the log: who, what, how it went, the email itself.
 */
class View extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Zero_Mail::log';

    public function __construct(
        Action\Context $context,
        private readonly MailLog $log
    ) {
        parent::__construct($context);
    }

    public function execute(): ResultInterface
    {
        $entry = $this->log->get((int) $this->getRequest()->getParam('id'));
        if ($entry === null) {
            $this->messageManager->addErrorMessage(__('The email is no longer in the log.'));
            return $this->resultRedirectFactory->create()->setPath('*/*/index');
        }
        /** @var \Magento\Backend\Model\View\Result\Page $page */
        $page = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $page->setActiveMenu(self::ADMIN_RESOURCE);
        $page->getConfig()->getTitle()->prepend(__('Email Log'));
        $page->getConfig()->getTitle()->prepend((string) $entry['subject'] ?: __('(no subject)'));
        $page->getLayout()->getBlock('zero.mail.log.view')?->setData('entry', $entry);

        return $page;
    }
}
