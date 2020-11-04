<?php

namespace Techiz\Newsletter\Block;

use Magento\Framework\View\Element\Template;

class Subscribe extends \Magento\Framework\View\Element\Template
{
    /**
     * Subscribe constructor.
     * @param Template\Context $context
     * @param array $data
     */
    public function __construct(Template\Context $context, array $data = [])
    {
        parent::__construct($context, $data);
    }

    /**
     * Get Form Action
     * @return string
     */
    public function getFormAction()
    {
        return $this->getUrl('techiz_newsletter/subscribe/new/', ['_secure' => true]);
    }
}
