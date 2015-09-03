<?php

namespace Ixopay\Client\Xml;

use Ixopay\Client\Data\CreditCardCustomer;
use Ixopay\Client\Data\Customer;
use Ixopay\Client\Data\IbanCustomer;
use Ixopay\Client\Data\Request;
use Ixopay\Client\Exception\TypeException;
use Ixopay\Client\Transaction\Base\AbstractTransaction;
use Ixopay\Client\Transaction\Base\AbstractTransactionWithReference;
use Ixopay\Client\Transaction\Base\AmountableInterface;
use Ixopay\Client\Transaction\Base\ItemsInterface;
use Ixopay\Client\Transaction\Base\OffsiteInterface;
use Ixopay\Client\Transaction\Capture;
use Ixopay\Client\Transaction\Debit;
use Ixopay\Client\Transaction\Deregister;
use Ixopay\Client\Transaction\Preauthorize;
use Ixopay\Client\Transaction\Refund;
use Ixopay\Client\Transaction\Register;
use Ixopay\Client\Transaction\Void;

/**
 * Class Generator
 *
 * @package Ixopay\Client\Xml
 */
class Generator {

    /**
     * @var \DOMDocument
     */
    protected $document;

    /**
     * @param string $method
     * @param AbstractTransaction $transaction
     * @param string $username
     * @param string $password
     * @param string $language
     * @param bool $testMode
     * @return \DOMDocument
     */
    public function generateTransaction($method, AbstractTransaction $transaction, $username, $password, $language=null, $testMode=false) {
        $this->document = new \DOMDocument('1.0', 'utf-8');
        $this->document->formatOutput = true;
        $root = $this->document->createElementNS('http://gateway.ixopay.com/Schema/V2/Transaction', 'transaction');

        $this->_appendTextNode($root, 'username', $username);
        $this->_appendTextNode($root, 'password', $password);

        if ($language) {
            $this->verifyLanguageType($language, 'language');
        }
        $this->_appendTextNode($root, 'language', $language);
        $this->_appendTextNode($root, 'testMode', $testMode ? 'true' : 'false');

        if (strpos($method, 'complete') === 0) {
            //complete call requires only transactionId
            $node = $this->document->createElement($method);
            $this->appendAbstractTransactionNodes($node, $transaction);
        } else {

            switch (true) {
                case $transaction instanceof Debit:
                    $node = $this->generateDebitNode($transaction, $method);
                    break;
                case $transaction instanceof Register:
                    $node = $this->generateRegisterNode($transaction, $method);
                    break;
                case $transaction instanceof Deregister:
                    $node = $this->generateDeregisterNode($transaction, $method);
                    break;
                case $transaction instanceof Preauthorize:
                    $node = $this->generatePreauthorizeNode($transaction, $method);
                    break;
                case $transaction instanceof Capture:
                    $node = $this->generateCaptureNode($transaction, $method);
                    break;
                case $transaction instanceof Void:
                    $node = $this->generateVoidNode($transaction, $method);
                    break;
                case $transaction instanceof Refund:
                    $node = $this->generateRefundNode($transaction, $method);
                    break;
                default:
                    return null;
                    break;
            }
        }

        $root->appendChild($node);
        $this->document->appendChild($root);

        return $this->document;
    }

    /**
     * @param string $username
     * @param string $password
     * @param string $identifier
     * @param array $parameters
     * @return \DOMDocument
     */
    public function generateOptions($username, $password, $identifier, $parameters=array()) {
        $this->document = new \DOMDocument('1.0', 'utf-8');
        $this->document->formatOutput = true;
        $root = $this->document->createElementNS('http://gateway.ixopay.com/Schema/V2/Options', 'options');
        $this->document->appendChild($root);

        $this->_appendTextNode($root, 'username', $username);
        $this->_appendTextNode($root, 'password', $password);

        $requestNode = $this->document->createElement('request');
        $root->appendChild($requestNode);

        $this->_appendTextNode($requestNode, 'identifier', $identifier);

        if ($parameters) {
            foreach ($parameters as $k=>$v) {
                if (is_array($v)) {
                    $v = json_encode($v);
                } elseif (is_object($v)) {
                    if ($v instanceof \JsonSerializable) {
                        $v = json_encode($v);
                    } else {
                        $v = null;
                    }
                }
                $parNode = $this->_appendTextNode($requestNode, 'parameter', $v);
                $parNode->setAttribute('name', $k);
            }
        }

        return $this->document;

    }

    /**
     * @param \DOMNode $parentNode
     * @param AbstractTransaction $transaction
     */
    protected function appendAbstractTransactionNodes(\DOMNode $parentNode, AbstractTransaction $transaction) {
        $this->_appendTextNode($parentNode, 'transactionToken', $transaction->getTransactionToken());
        $this->_appendTextNode($parentNode, 'transactionId', $transaction->getTransactionId());
        $this->_appendTextNode($parentNode, 'additionalId1', $transaction->getAdditionalId1());
        $this->_appendTextNode($parentNode, 'additionalId2', $transaction->getAdditionalId2());
        if ($transaction->getCustomer()) {
            $this->appendCustomerNode($parentNode, $transaction->getCustomer());
        }
        if ($transaction->getExtraData()) {
            $this->appendExtraDataNodes($parentNode, 'extraData', $transaction->getExtraData());
        }

        if ($transaction->getRequest()) {
            $this->appendRequestNode($parentNode, 'request', $transaction->getRequest());
        }
    }

    /**
     * @param \DOMNode $parentNode
     * @param AbstractTransactionWithReference $transaction
     */
    protected function appendAbstractTransactionWithReferenceNodes(\DOMNode $parentNode, AbstractTransactionWithReference $transaction) {
        $this->appendAbstractTransactionNodes($parentNode, $transaction);
        $this->_appendTextNode($parentNode, 'referenceTransactionId', $transaction->getReferenceTransactionId());
        $this->_appendTextNode($parentNode, 'referenceCustomerId', $transaction->getReferenceCustomerId());
        $this->_appendTextNode($parentNode, 'referenceId2', $transaction->getReferenceId2());
        $this->_appendTextNode($parentNode, 'referenceId3', $transaction->getReferenceId3());
        $this->_appendTextNode($parentNode, 'referenceId4', $transaction->getReferenceId4());
    }

    /**
     * @param \DOMNode $parentNode
     * @param OffsiteInterface $transaction
     * @throws TypeException
     */
    protected function appendOffsiteNodes(\DOMNode $parentNode, OffsiteInterface $transaction) {
        if ($transaction instanceof AbstractTransactionWithReference && $transaction->getReferenceTransactionId()) {
            if ($transaction->getSuccessUrl()) {
                $this->verifyUrl($transaction->getSuccessUrl(), 'successUrl');
            }
        } else {
            $this->verifyUrl($transaction->getSuccessUrl(), 'successUrl');

        }

        $this->verifyUrl($transaction->getCallbackUrl(), 'callbackUrl');
        if ($transaction->getCancelUrl()) {
            $this->verifyUrl($transaction->getCancelUrl(), 'cancelUrl');
        }
        if ($transaction->getErrorUrl()) {
            $this->verifyUrl($transaction->getErrorUrl(), 'errorUrl');
        }

        $this->_appendTextNode($parentNode, 'description', $transaction->getDescription());
        $this->_appendTextNode($parentNode, 'successUrl', $transaction->getSuccessUrl());
        $this->_appendTextNode($parentNode, 'cancelUrl', $transaction->getCancelUrl());
        $this->_appendTextNode($parentNode, 'errorUrl', $transaction->getErrorUrl());
        $this->_appendTextNode($parentNode, 'callbackUrl', $transaction->getCallbackUrl());
    }

    /**
     * @param \DOMNode $parentNode
     * @param AmountableInterface $transaction
     * @throws TypeException
     */
    protected function appendAmountableNodes(\DOMNode $parentNode, AmountableInterface $transaction) {
        $this->verifyAmountType($transaction->getAmount(), 'amount');
        $this->verifyCurrencyType($transaction->getCurrency(), 'currency');

        $this->_appendTextNode($parentNode, 'amount', $transaction->getAmount());
        $this->_appendTextNode($parentNode, 'currency', $transaction->getCurrency());
    }

    /**
     * @param \DOMNode $parentNode
     * @param Customer $customer
     * @throws TypeException
     */
    protected function appendCustomerNode(\DOMNode $parentNode, Customer $customer) {
        if ($customer instanceof IbanCustomer) {
            $node = $this->document->createElement('ibanCustomer');
        } elseif ($customer instanceof CreditCardCustomer) {
            $node = $this->document->createElement('creditCardCustomer');
        } else {
            $node = $this->document->createElement('customer');
        }

        if ($customer->getBillingCountry()) {
            $this->verifyCountryType($customer->getBillingCountry(), 'customer:billingCountry');
        }
        if ($customer->getShippingCountry()) {
            $this->verifyCountryType($customer->getShippingCountry(), 'customer:shippingCountry');
        }

        $this->_appendTextNode($node, 'identification', $customer->getIdentification());
        $this->_appendTextNode($node, 'firstName', $customer->getFirstName());
        $this->_appendTextNode($node, 'lastName', $customer->getLastName());
        $this->_appendTextNode($node, 'birthDate', $customer->getBirthDate() ? $customer->getBirthDate()->format('Y-m-d') : null);
        $this->_appendTextNode($node, 'billingAddress1', $customer->getBillingAddress1());
        $this->_appendTextNode($node, 'billingAddress2', $customer->getBillingAddress2());
        $this->_appendTextNode($node, 'billingCity', $customer->getBillingCity());
        $this->_appendTextNode($node, 'billingPostcode', $customer->getBillingPostcode());
        $this->_appendTextNode($node, 'billingState', $customer->getBillingState());
        $this->_appendTextNode($node, 'billingCountry', $customer->getBillingCountry());
        $this->_appendTextNode($node, 'billingPhone', $customer->getBillingPhone());
        $this->_appendTextNode($node, 'shippingAddress1', $customer->getShippingAddress1());
        $this->_appendTextNode($node, 'shippingAddress2', $customer->getShippingAddress2());
        $this->_appendTextNode($node, 'shippingCity', $customer->getShippingCity());
        $this->_appendTextNode($node, 'shippingPostcode', $customer->getShippingPostcode());
        $this->_appendTextNode($node, 'shippingState', $customer->getShippingState());
        $this->_appendTextNode($node, 'shippingCountry', $customer->getShippingCountry());
        $this->_appendTextNode($node, 'shippingPhone', $customer->getShippingPhone());
        $this->_appendTextNode($node, 'company', $customer->getCompany());
        $this->_appendTextNode($node, 'email', $customer->getEmail());
        $this->_appendTextNode($node, 'ipAddress', $customer->getIpAddress());
        $this->_appendTextNode($node, 'nationalId', $customer->getNationalId());

        if ($customer instanceof IbanCustomer) {
            $this->_appendTextNode($node, 'iban', $customer->getIban());
            $this->_appendTextNode($node, 'bic', $customer->getBic());
        } elseif ($customer instanceof CreditCardCustomer) {
            $this->_appendTextNode($node, 'number', $customer->getNumber());
            $this->_appendTextNode($node, 'expiryMonth', $customer->getExpiryMonth());
            $this->_appendTextNode($node, 'expiryYear', $customer->getExpiryYear());
            $this->_appendTextNode($node, 'startMonth', $customer->getStartMonth());
            $this->_appendTextNode($node, 'startYear', $customer->getStartYear());
            $this->_appendTextNode($node, 'cvv', $customer->getCvv());
            $this->_appendTextNode($node, 'issueNumber', $customer->getIssueNumber());
            $this->_appendTextNode($node, 'type', $customer->getType());
        }


        $parentNode->appendChild($node);
    }

    /**
     * @param \DOMNode $parentNode
     * @param string $nodeName
     * @param array $extraData
     */
    protected function appendExtraDataNodes(\DOMNode $parentNode, $nodeName, $extraData) {
        if (is_array($extraData)) {
            foreach ($extraData as $k=>$v) {
                $node = $this->_appendTextNode($parentNode, $nodeName, $v, false);
                $node->setAttribute('key', $k);
            }
        }
    }

    /**
     * @param \DOMNode $parentNode
     * @param $nodeName
     * @param Request $request
     */
    protected function appendRequestNode(\DOMNode $parentNode, $nodeName, Request $request) {
        $node = $this->document->createElement($nodeName);

        if ($request->getGet()) {
            $this->appendExtraDataNodes($node, 'getParam', $request->getGet());
        }
        if ($request->getPost()) {
            $this->appendExtraDataNodes($node, 'postParam', $request->getPost());
        }
        if ($request->getHeaders()) {
            $this->appendExtraDataNodes($node, 'requestHeader', $request->getHeaders());
        }
        $this->_appendTextNode($node, 'requestBody', $request->getBody());

        $parentNode->appendChild($node);
    }

    /**
     * @param \DOMNode $parentNode
     * @param ItemsInterface $transaction
     */
    protected function appendItemsNode(\DOMNode $parentNode, ItemsInterface $transaction) {
        if ($transaction->getItems()) {
            $node = $this->document->createElement('items');

            foreach ($transaction->getItems() as $item) {
                if ($item->getPrice()) {
                    $this->verifyAmountType($item->getPrice(), 'item.amount');
                }
                if ($item->getQuantity()) {
                    $this->verifyAmountType($item->getQuantity(), 'item.quantity');
                }
                if ($item->getCurrency()) {
                    $this->verifyCurrencyType($item->getCurrency(), 'item.currency');
                }
                $itemNode = $this->document->createElement('item');
                $this->_appendTextNode($itemNode, 'identification', $item->getIdentification());
                $this->_appendTextNode($itemNode, 'name', $item->getName());
                $this->_appendTextNode($itemNode, 'description', $item->getDescription());
                $this->_appendTextNode($itemNode, 'quantity', $item->getQuantity());
                $this->_appendTextNode($itemNode, 'price', $item->getPrice());
                $this->_appendTextNode($itemNode, 'currency', $item->getCurrency());
                $this->appendExtraDataNodes($itemNode, 'extraData', $item->getExtraData());
                $node->appendChild($itemNode);
            }
            $parentNode->appendChild($node);
        }
    }

    /**
     * @param Debit $transaction
     * @param string $method
     *
     * @return \DOMElement
     */
    protected function generateDebitNode(Debit $transaction, $method) {
        $node = $this->document->createElement($method);
        $this->appendAbstractTransactionWithReferenceNodes($node, $transaction);
        $this->appendAmountableNodes($node, $transaction);
        $this->appendOffsiteNodes($node, $transaction);
        $this->appendItemsNode($node, $transaction);

        $this->_appendTextNode($node, 'withRegister', $transaction->isWithRegister() ? 'true' : 'false');

        return $node;
    }

    /**
     * @param Register $transaction
     * @param $method
     * @return \DOMElement
     */
    protected function generateRegisterNode(Register $transaction, $method) {
        $node = $this->document->createElement($method);
        $this->appendAbstractTransactionNodes($node, $transaction);

        return $node;
    }

    /**
     * @param Deregister $transaction
     * @param $method
     * @return \DOMElement
     */
    protected function generateDeregisterNode(Deregister $transaction, $method) {
        $node = $this->document->createElement($method);
        $this->appendAbstractTransactionWithReferenceNodes($node, $transaction);

        return $node;
    }

    /**
     * @param Preauthorize $transaction
     * @param $method
     * @return \DOMElement
     */
    protected function generatePreauthorizeNode(Preauthorize $transaction, $method) {
        $node = $this->document->createElement($method);
        // @todo $transaction parameter is expected to be an AbstractTransactionWithReference, Preauthorize extends the AbstractTransaction. The fact that it has referenced transaction should be checked.
        $this->appendAbstractTransactionWithReferenceNodes($node, $transaction);
        $this->appendAmountableNodes($node, $transaction);
        $this->appendOffsiteNodes($node, $transaction);
        $this->appendItemsNode($node, $transaction);

        $this->_appendTextNode($node, 'withRegister', $transaction->isWithRegister() ? 'true' : 'false');

        return $node;
    }

    /**
     * @param Capture $transaction
     * @param $method
     * @return \DOMElement
     */
    protected function generateCaptureNode(Capture $transaction, $method) {
        $node = $this->document->createElement($method);
        $this->appendAbstractTransactionWithReferenceNodes($node, $transaction);
        $this->appendItemsNode($node, $transaction);

        return $node;
    }

    /**
     * @param \Ixopay\Client\Transaction\Void $transaction
     * @param $method
     *
     * @return \DOMElement
     */
    protected function generateVoidNode(Void $transaction, $method) {
        $node = $this->document->createElement($method);
        $this->appendAbstractTransactionWithReferenceNodes($node, $transaction);

        return $node;
    }

    /**
     * @param Refund $transaction
     * @param $method
     * @return \DOMElement
     */
    protected function generateRefundNode(Refund $transaction, $method) {
        $node = $this->document->createElement($method);
        $this->appendAbstractTransactionWithReferenceNodes($node, $transaction);
        $this->appendAmountableNodes($node, $transaction);
        $this->appendItemsNode($node, $transaction);

        return $node;
    }

    /**
     * @param string $value
     * @param string $elementName
     * @throws TypeException
     */
    private function verifyCurrencyType($value, $elementName) {
        if ($value == null || !is_string($value) || strlen($value) != 3) {
            throw new TypeException('Value of '.$elementName.' must by of type string and exactly 3 characters long');
        }
    }

    /**
     * @param string $value
     * @param string $elementName
     * @throws TypeException
     */
    private function verifyCountryType($value, $elementName) {
        if ($value == null || !is_string($value) || strlen($value) != 2) {
            throw new TypeException('Value of '.$elementName.' must by of type string and exactly 2 characters long');
        }
    }

    /**
     * @param string $value
     * @param string $elementName
     * @throws TypeException
     */
    private function verifyLanguageType($value, $elementName) {
        if ($value == null || !is_string($value) || strlen($value) != 2) {
            throw new TypeException('Value of '.$elementName.' must by of type string and exactly 2 characters long');
        }
    }

    /**
     * @param float|int $value
     * @param string $elementName
     * @throws TypeException
     */
    private function verifyAmountType($value, $elementName) {
        if ($value == null || !is_numeric($value)) {
            throw new TypeException('Value of '.$elementName.' must by of type numeric');
        }
    }

    /**
     * @param string $value
     * @param string $elementName
     * @throws TypeException
     */
    private function verifyUrl($value, $elementName) {
        if ($value == null || !preg_match("/\b(?:(?:https?):\/\/)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i",$value)) {
            throw new TypeException('Value of '.$elementName.' must by a valid url starting with "http" or "https"');
        }
    }

    /**
     * @param \DOMNode $parentNode
     * @param string $nodeName
     * @param string $nodeValue
     * @param bool $skipNullValue
     *
     * @return \DOMElement|null
     *
     */
    private function _appendTextNode(\DOMNode $parentNode, $nodeName, $nodeValue, $skipNullValue=true) {
        if (!$skipNullValue || $nodeValue !== null) {
            $node = $this->document->createElement($nodeName);
            $node->appendChild($this->document->createTextNode($nodeValue));
            $parentNode->appendChild($node);
            return $node;
        }
        return null;
    }
}