<?php
/**
 * Agreement.php
 *
 * @category    CLS
 * @package     Paypal
 * @copyright   Copyright (c) 2013 Classy Llama Studios
 */

class CLS_Paypal_Model_Paypaluk_Method_Agreement extends Mage_Paypal_Model_Method_Agreement
{

    const STATUS_CANCELED = 'cancel';

    /**
     * Method code
     *
     * @var string
     */
    protected $_code = CLS_Paypal_Model_Paypal_Config::METHOD_PAYFLOW_BILLING_AGREEMENT;

    /**
     * Initialize Mage_Paypal_Model_Pro model
     *
     * @param array $params
     */
    public function __construct($params = array())
    {
        $proInstance = array_shift($params);
        if ($proInstance && ($proInstance instanceof Mage_Paypal_Model_Pro)) {
            $this->_pro = $proInstance;
        } else {
            $this->_pro = Mage::getModel('paypaluk/pro');
        }
        $this->_pro->setMethod($this->_code);
    }

    /**
     * GetBillingAgreementCustomerDetails doesn't exist in PaypalUk
     *
     * @return array
     */
    public function getBillingAgreementTokenInfo(Mage_Payment_Model_Billing_AgreementAbstract $agreement)
    {
        $api = $this->_pro->getApi()
            ->setToken($agreement->getToken());

        $api->callGetExpressCheckoutDetails();

        $responseData = array(
            'token'         => $api->getData('token'),
            'email'         => $api->getData('email'),
            'payer_id'      => $api->getData('payer_id'),
            'payer_status'  => $api->getData('payer_status')
        );
        $agreement->addData($responseData);
        return $responseData;
    }

    /**
     * Update billing agreement status
     *
     * @param Mage_Payment_Model_Billing_AgreementAbstract $agreement
     * @throws Exception|Mage_Core_Exception
     * @return Mage_Paypal_Model_Method_Agreement
     */
    public function updateBillingAgreementStatus(Mage_Payment_Model_Billing_AgreementAbstract $agreement)
    {
        $targetStatus = $agreement->getStatus();

        if (Mage_Sales_Model_Billing_Agreement::STATUS_CANCELED == $targetStatus) {
            $targetStatus = self::STATUS_CANCELED;
        }

        $api = $this->_pro->getApi()
            ->setBillingAgreementId($agreement->getReferenceId())
            ->setBillingAgreementStatus($targetStatus);
        try {
            $api->callUpdateBillingAgreement();
        } catch (Mage_Core_Exception $e) {
            // when BA was already canceled, just pretend that the operation succeeded
            if (!(self::STATUS_CANCELED == $targetStatus && $api->getIsBillingAgreementAlreadyCancelled())) {
                throw $e;
            }
        }
        return $this;
    }

    /**
     * Place an order with authorization or capture action
     *
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param float $amount
     * @return Mage_Paypal_Model_Method_Agreement
     */
    protected function _placeOrder(Mage_Sales_Model_Order_Payment $payment, $amount)
    {
        $order = $payment->getOrder();
        $billingAgreement = Mage::getModel('sales/billing_agreement')->load(
            $payment->getAdditionalInformation(
                Mage_Sales_Model_Payment_Method_Billing_AgreementAbstract::TRANSPORT_BILLING_AGREEMENT_ID
            )
        );

        $api = $this->_pro->getApi()
            ->setBillingAgreementId($billingAgreement->getReferenceId())
            ->setPaymentAction($this->_pro->getConfig()->paymentAction)
            ->setAmount($amount)
            ->setNotifyUrl(Mage::getUrl('paypal/ipn/'))
            ->setPaypalCart(Mage::getModel('paypal/cart', array($order)))
            ->setIsLineItemsEnabled($this->_pro->getConfig()->lineItemsEnabled)
            ->setInvNum($order->getIncrementId())
        ;

        // call api and import transaction and other payment information
        $api->callDoReferenceTransaction();
        $this->_pro->importPaymentInfo($api, $payment);

        $payment
            ->setAdditionalInformation(CLS_Paypal_Model_Paypaluk_Api_Nvp_Common::RESPONSE_MSG, $api->getResponseMsg())
            ->setPreparedMessage(Mage::helper('cls_paypal')->__('Payflow PNREF: #%s.', $api->getTransactionId()))
            ->setTransactionAdditionalInfo(Mage_PaypalUk_Model_Pro::TRANSPORT_PAYFLOW_TXN_ID, $api->getTransactionId());

        $payment->setTransactionId($api->getPaypalTransactionId())
            ->setIsTransactionClosed(0);

        if ($api->getBillingAgreementId()) {
            $order->addRelatedObject($billingAgreement);
            $billingAgreement->setIsObjectChanged(true);
            $billingAgreement->addOrderRelation($order->getId());
        }

        return $this;
    }
}
