<?php
/**
 * Classy Llama
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email to us at
 * support+paypal@classyllama.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module
 * to newer versions in the future. If you require customizations of this
 * module for your needs, please write us at sales@classyllama.com.
 *
 * To report bugs or issues with this module, please email support+paypal@classyllama.com.
 * 
 * @category   CLS
 * @package    Paypal
 * @copyright  Copyright (c) 2013 Classy Llama Studios, LLC (http://www.classyllama.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class CLS_Paypal_Model_Paypaluk_Api_Nvp extends Mage_PaypalUk_Model_Api_Nvp
{
    /**
     * Paypal methods definition
     */
    const SET_BILLING_AGREEMENT = 'SetCustomerBillingAgreement';
    const CREATE_BILLING_AGREEMENT = 'CreateBillingAgreement';
    const UPDATE_BILLING_AGREEMENT = 'BillAgreementUpdate';
    const DO_REFERENCE_TRANSACTION = 'DoReferenceTransaction';

    /**
     * Actions
     */
    const BA_SET = 'S';
    const BA_CREATE = 'X';
    const BA_UPDATE = 'U';

    const RESPONSE_MSG = 'paypal_response_msg';

    /**
     * Create Billing Agreement request/response map
     * @var array
     */
    protected $_createBillingAgreementResponse = array('BAID');

    /**
     * Update Billing Agreement request/response map
     * @var array
     */
    protected $_updateBillingAgreementRequest = array(
            'BAID', 'TENDER', 'BA_STATUS',
    );
    protected $_updateBillingAgreementResponse = array(
            'BAID', 'PPREF', 'RESPMSG', 'CORRELATIONID',
    );

    /**
     * Do Reference Transaction request/response map
     *
     * @var array
     */
    protected $_doReferenceTransactionRequest = array('BAID', 'TENDER', 'AMT', 'FREIGHTAMT',
            'TAXAMT', 'INVNUM', 'NOTIFYURL'
    );
    protected $_doReferenceTransactionResponse = array('BAID', 'PNREF', 'PPREF', 'PENDINGREASON', 'RESPMSG');

    protected function _construct()
    {
        parent::_construct();

        $this->_globalMap['BILLINGTYPE'] = 'billing_type';
        $this->_globalMap['BAID'] = 'billing_agreement_id';
        $this->_globalMap['RESPMSG'] = 'response_msg';
        $this->_globalMap['BA_STATUS'] = 'billing_agreement_status';
        $this->_globalMap['BUTTONSOURCE'] = 'paypal_info_code';
        
        $this->_debugReplacePrivateDataKeys[] = 'BUTTONSOURCE';

        $this->_setExpressCheckoutRequest[] = 'BILLINGTYPE';

        $this->_customerBillingAgreementRequest[] = 'TENDER';
        $this->_customerBillingAgreementRequest[] = 'AMT';

        $this->_createBillingAgreementRequest[] = 'TENDER';
    }

    /**
     * Get Paypal info code
     *
     * @return string
     */
    public function getPaypalInfoCode()
    {
        return Mage::helper('cls_paypal')->getPaypalInfoCode();
    }
    
    /**
     * Return PaypalUk tender based on config data
     *
     * @return string
     */
    public function getTender()
    {
        switch ($this->_config->getMethodCode()) {
            case CLS_Paypal_Model_Paypal_Config::METHOD_PAYFLOW_BILLING_AGREEMENT:
            case CLS_Paypal_Model_Paypal_Config::METHOD_PAYFLOW_ORDERSTORED_AGREEMENT:
                return Mage_PaypalUk_Model_Api_Nvp::TENDER_PAYPAL;

            default:
                return parent::getTender();
        }
    }

    /**
     * Add method to request array
     *
     * @param string $methodName
     * @param array $request
     * @return array
     */
    protected function _addMethodToRequest($methodName, $request)
    {
        $txnType = $this->_mapPaypalMethodName($methodName);
        if ($txnType) {
            return parent::_addMethodToRequest($methodName, $request);
        } else {
            if (!is_null($this->_getPaypalUkActionName($methodName))) {
                $request['ACTION'] = $this->_getPaypalUkActionName($methodName);
            }
            return $request;
        }
    }

    /**
     * Return Payflow Edition
     *
     * @param string
     * @return string | null
     */
    protected function _getPaypalUkActionName($methodName)
    {
        switch($methodName) {
            case self::DO_REFERENCE_TRANSACTION:
                return Mage_PaypalUk_Model_Api_Nvp::EXPRESS_DO_PAYMENT;

            case self::SET_BILLING_AGREEMENT:
                return self::BA_SET;

            case self::CREATE_BILLING_AGREEMENT:
                return self::BA_CREATE;

            case self::UPDATE_BILLING_AGREEMENT:
                return self::BA_UPDATE;

            default:
                return parent::_getPaypalUkActionName($methodName);
        }
    }

    /**
     * Map paypal method names
     *
     * @param string| $methodName
     * @return string
     */
    protected function _mapPaypalMethodName($methodName)
    {
        switch ($methodName) {
            case self::DO_REFERENCE_TRANSACTION:
                return ($this->_config->payment_action == Mage_Paypal_Model_Config::PAYMENT_ACTION_AUTH)
                ? Mage_PaypalUk_Model_Api_Nvp::TRXTYPE_AUTH_ONLY
                : Mage_PaypalUk_Model_Api_Nvp::TRXTYPE_SALE;

            case self::SET_BILLING_AGREEMENT:
            case self::CREATE_BILLING_AGREEMENT:
                return Mage_PaypalUk_Model_Api_Nvp::TRXTYPE_AUTH_ONLY;
                
            case self::UPDATE_BILLING_AGREEMENT:
                return null;

            default:
                return parent::_mapPaypalMethodName($methodName);
        }
    }

    /**
     * Set Customer Billing Agreement call
     *
     * @link https://cms.paypal.com/us/cgi-bin/?&cmd=_render-content&content_ID=developer/e_howto_api_nvp_r_SetCustomerBillingAgreement
     */
    public function callSetCustomerBillingAgreement()
    {
        $this->setAmount(0);
        parent::callSetCustomerBillingAgreement();
    }
}
