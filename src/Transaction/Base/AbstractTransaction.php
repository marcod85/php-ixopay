<?php

namespace Ixopay\Client\Transaction\Base;

use Ixopay\Client\Data\Customer;
use Ixopay\Client\Data\Request;

/**
 * Class AbstractTransaction
 * @package Ixopay\Client\Transaction
 */
class AbstractTransaction {

    /**
     * @var string
     */
    protected $transactionToken;

    /**
     * @var string
     */
    protected $transactionId;

    /**
     * @var string
     */
    protected $additionalId1;

    /**
     * @var string
     */
    protected $additionalId2;

    /**
     * @var Customer
     */
    protected $customer;

    /**
     * @var array
     */
    protected $extraData = array();

    /**
     * @var Request
     */
    protected $request;

    /**
     * @return string
     */
    public function getTransactionToken()
    {
        return $this->transactionToken;
    }

    /**
     * set a required transaction token (if described by the documentation of a payment method)
     *
     * @param string $transactionToken
     */
    public function setTransactionToken($transactionToken)
    {
        $this->transactionToken = $transactionToken;
    }

    /**
     * @return string
     */
    public function getTransactionId()
    {
        return $this->transactionId;
    }

    /**
     * this is your own transaction id
     * NOTE: your transaction ids MUST be unique
     *
     * @param string $transactionId
     */
    public function setTransactionId($transactionId)
    {
        $this->transactionId = $transactionId;
        return $this;
    }

    /**
     * @return string
     */
    public function getAdditionalId1()
    {
        return $this->additionalId1;
    }

    /**
     * any additional id if required by the payment method
     *
     * @param string $additionalId1
     */
    public function setAdditionalId1($additionalId1)
    {
        $this->additionalId1 = $additionalId1;
        return $this;
    }

    /**
     * @return string
     */
    public function getAdditionalId2()
    {
        return $this->additionalId2;
    }

    /**
     * any additional id if required by the payment method
     *
     * @param string $additionalId2
     */
    public function setAdditionalId2($additionalId2)
    {
        $this->additionalId2 = $additionalId2;
        return $this;
    }

    /**
     * @return \IxopayV2\Data\Customer
     */
    public function getCustomer()
    {
        return $this->customer;
    }

    /**
     * customer information
     *
     * @param \IxopayV2\Data\Customer $customer
     */
    public function setCustomer($customer)
    {
        $this->customer = $customer;
    }

    /**
     * @return array
     */
    public function getExtraData()
    {
        return $this->extraData;
    }

    /**
     * any additional data if required by the payment method
     *
     * @param array $extraData
     */
    public function setExtraData($extraData)
    {
        $this->extraData = $extraData;
        return $this;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function addExtraData($key, $value) {
        $this->extraData[$key] = $value;
        return $this;
    }

    /**
     * @return Request
     */
    public function getRequest() {
        return $this->request;
    }

    /**
     * provider request information here (if required by the payment method)
     *
     * @param Request $request
     */
    public function setRequest($request) {
        $this->request = $request;
    }

    /**
     * get data from extra data
     *
     * @param string $key
     * @return mixed|null
     */
    public function __get($key) {
        if (array_key_exists($key, $this->extraData)) {
            return $this->extraData[$key];
        }
        return null;
    }
}