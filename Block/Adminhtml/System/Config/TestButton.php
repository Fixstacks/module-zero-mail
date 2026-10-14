<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\Mail\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Widget\Button;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Send Test Email: to the admin user signed in, with the settings saved.
 */
class TestButton extends Field
{
    /**
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $button = $this->getLayout()->createBlock(Button::class)->setData([
            'id' => 'zero_mail_test',
            'label' => __('Send Test Email'),
            'onclick' => "setLocation('" . $this->escapeJs($this->getUrl('zero_mail/test/send')) . "')",
        ]);

        return $button->toHtml() . '<p class="note"><span>'
            . $this->escapeHtml(__('To your admin account\'s email, with the settings as saved.'))
            . '</span></p>';
    }

    /**
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();

        return parent::render($element);
    }
}
