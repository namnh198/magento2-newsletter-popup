<?php

namespace Techiz\Newsletter\Controller\Subscribe;

use Magento\Customer\Api\AccountManagementInterface as CustomerAccountManagement;
use Magento\Customer\Model\Session;
use Magento\Customer\Model\Url as CustomerUrl;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Newsletter\Model\SubscriberFactory;
use Magento\Store\Model\StoreManagerInterface;

/**
 * NewAction
 * @package Techiz\Newsletter\Controller\Subscribe
 */
class NewAction extends \Magento\Framework\App\Action\Action
{
    /**
     * Customer account management
     * @var CustomerAccountManagement $_customerAccountManagement
     */
    protected $_customerAccountManagement;

    /**
     * Customer Session
     * @var Session $_customerSession
     */
    protected $_customerSession;

    /**
     * Subscriber Factory
     * @var SubscriberFactory $_subscriberFactory
     */
    protected $_subscriberFactory;

    /**
     * Store Manager
     * @var StoreManagerInterface $_storeManager
     */
    protected $_storeManager;

    /**
     * Customer Url
     * @var CustomerUrl $_customerUrl
     */
    protected $_customerUrl;

    /**
     * Result Json Factory
     * @var JsonFactory $_resultJsonFactory
     */
    protected $_resultJsonFactory;

    /**
     * Newsletter NewAction constructor.
     * @param Context $context
     * @param SubscriberFactory $subscriberFactory
     * @param Session $customerSession
     * @param StoreManagerInterface $storeManager
     * @param CustomerUrl $customerUrl
     * @param CustomerAccountManagement $customerAccountManagement
     * @param JsonFactory $resultJsonFactory
     */
    public function __construct(
        Context $context,
        SubscriberFactory $subscriberFactory,
        Session $customerSession,
        StoreManagerInterface $storeManager,
        CustomerUrl $customerUrl,
        CustomerAccountManagement $customerAccountManagement,
        JsonFactory $resultJsonFactory
    ) {
        $this->_customerAccountManagement = $customerAccountManagement;
        $this->_storeManager = $storeManager;
        $this->_subscriberFactory = $subscriberFactory;
        $this->_customerSession = $customerSession;
        $this->_customerUrl = $customerUrl;
        $this->_resultJsonFactory = $resultJsonFactory;

        parent::__construct($context);
    }

    /**
     * Validate Email if Email Available
     * @param $email
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function _validateEmailAvailable($email)
    {
        $websiteId = $this->_storeManager->getStore()->getWebsiteId();

        if ($this->_customerSession->getCustomerDataObject()->getEmail() !== $email
        && !$this->_customerAccountManagement->isEmailAvailable($email, $websiteId)) {
            throw new LocalizedException(
                __('This email address is already assigned to another user.')
            );
        }
    }

    /**
     * Don't allow register newsletter when not logged
     * @throws LocalizedException
     */
    protected function _validateGuestSubscription()
    {
        if ($this->_objectManager
            ->get('Magento\Framework\App\Config\ScopeConfigInterface')
            ->getValue(
                \Magento\Newsletter\Model\Subscriber::XML_PATH_ALLOW_GUEST_SUBSCRIBE_FLAG,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            ) && ! $this->_customerSession->isLoggedIn()) {
            throw new LocalizedException(
                __(
                    'Sorry, but the adminstrator denied subscription for guests, Please <a href="%1">register</a>',
                    $this->_customerUrl->getRegisterUrl()
                )
            );
        }
    }

    /**
     * Email Address is valid
     * @param $email
     * @throws LocalizedException
     * @throws \Zend_Validate_Exception
     */
    protected function _validateEmailFormat($email)
    {
        if (!\Zend_Validate::is($email, 'EmailAddress')) {
            throw new LocalizedException(
                __('Please enter a valid email address')
            );
        }
    }

    /**
     * Execute
     * @return \Magento\Framework\App\ResponseInterface | \Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $data['error'] = true;
        $data['message'] = __('Please enter your email address.');

        if ($this->getRequest()->isAjax()
            && $this->getRequest()->getMethod() == 'POST'
            && $this->getRequest()->isXmlHttpRequest()
            && $this->getRequest()->getPost('email')
        ) {
            $email= (string) $this->getRequest()->getPost('email');

            try {
                $this->_validateEmailFormat($email);
                $this->_validateGuestSubscription();
                $this->_validateEmailAvailable($email);

                $status = $this->_subscriberFactory->create()->subscribe($email);

                $data['error'] = false;

                if ($status == \Magento\Newsletter\Model\Subscriber::STATUS_NOT_ACTIVE) {
                    $data['message'] = __('The confirmation request has been sent');
                } else {
                    $data['message'] = __('Thank you for your subscription');
                }


            } catch (LocalizedException $e) {
                $data['message'] = __('There was a problem with the subscription: %1', $e->getMessage());
            } catch (\Exception $e) {
                $data['message'] = $e->getMessage();
            }

            $result = $this->_resultJsonFactory->create();
            return $result->setData($data);
        }
    }
}
