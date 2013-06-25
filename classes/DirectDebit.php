<?php

/**
 * Class for generating SEPA files for DirectDebit jobs (pain.008.001.02)
 *
 * This version has been tested against:
 *      ING Pain008 Direct Debit RB4 (http://certification.softshare.com/Certification/welcome.do?go=ING)
 *
 *
 * @license GNU Lesser General Public License v3.0
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Lesser Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author      Damien Overeem ( SoHosted B.V. - www.sohosted.com )
 * @package     Sepa PHP XML tools
 * @version     0.1-alpha
 *
 */
class Sepa_DirectDebit extends Sepa_Base {

    /**
     * Unique identifier for the directdebit job ( Tag: <MsgId> )
     * @var string
     */
    private $_messageIdentification;

    /**
     * Creditor Scheme Identification ( Tag: <CdtrSchmeId> -> <Id> -> <PrvtId> -> <Othr> -> <Id> )
     * @var string
     */
    private $_creditorId;

    /**
     * Initiating party (Creditor name) ( Tag: <InitgPty> )
     * @var string
     */
    private $_initiatingPartyName;

    /**
     * Creditor's iban number (where the money should go..) ( Tag: <CdtrAcct> -> <Id> -> <IBAN> )
     * @var string
     */
    private $_creditorIban;

    /**
     * Creditor's BIC ( Tag: <CdtrAcct> -> <Id> -> <BIC> )
     * @var string
     */
    private $_creditorBic;

    /**
     * Unique identification, as assigned by a sending party, to unambiguously identify the payment information
     * group within the message ( Tag: <PmtInfId> )
     * @var string
     */
    private $_paymentInfoId;

    /**
     * Requested Execution Date in yyyy-mm-dd format ( Tag: <ReqdColltnDt> )
     * @var string
     */
    private $_requestedExecutionDate;

    /**
     * Add transactions
     * @var Sepa_DirectDebit_Transaction[]
     */
    private $_transactions;

    /**
     * Constructor
     */
    public function __construct() {

    }

    /**
     * Generate the XML structure.
     */
    protected function _generateXml() {

        $DateTime = new DateTime();
        $creationDateTime = $DateTime->format('Y-m-d\TH:i:s');

        $nrOfTransactions = count($this->_transactions);

        /**
         * Document base
         */
        $this->_xml = simplexml_load_string('<?xml version="1.0" encoding="utf-8"?><Document xmlns="urn:iso:std:iso:20022:tech:xsd:pain.008.001.02" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"></Document>');
        $this->_xml->addChild('CstmrDrctDbtInitn');

        /**
         * Group Header
         */
        $GroupHeader = $this->_xml->CstmrDrctDbtInitn->addChild('GrpHdr'); /* ISO: 1.0 */
        $GroupHeader->addChild('MsgId', $this->_messageIdentification); /* ISO: 1.1 */
        $GroupHeader->addChild('CreDtTm', $creationDateTime); /* ISO: 1.2 */
        $GroupHeader->addChild('NbOfTxs', $nrOfTransactions); /* ISO: 1.6 */
        $GroupHeader->addChild('InitgPty')->addChild('Nm', $this->alphanumeric($this->_initiatingPartyName, 70)); /* ISO: 1.8 */

        /**
         *  Payment Information
         */
        $PaymentInformation = $this->_xml->CstmrDrctDbtInitn->addChild('PmtInf'); /* ISO: 2.0 */
        $PaymentInformation->addChild('PmtInfId', $this->_paymentInfoId); /* ISO: 2.1 */
        $PaymentInformation->addChild('PmtMtd', "DD"); /* ISO: 2.2 */

        $PaymentInformation->addChild('PmtTpInf')->addChild('SvcLvl')->addChild('Cd', 'SEPA'); /* ISO: 2.9 */
        $PaymentInformation->PmtTpInf->addChild('LclInstrm')->addChild('Cd', 'CORE'); /* ISO: 2.11, 2.12 */
        $PaymentInformation->PmtTpInf->addChild('SeqTp', 'OOFF'); /* ISO: 2.14 */

        $PaymentInformation->addChild('ReqdColltnDt', $this->_requestedExecutionDate);  /* ISO: 2.18 */
        $PaymentInformation->addChild('Cdtr')->addChild('Nm', $this->alphanumeric($this->_initiatingPartyName, 70)); /* ISO: 2.19 */
        $PaymentInformation->addChild('CdtrAcct')->addChild('Id')->addChild('IBAN', $this->_creditorIban); /* ISO: 2.20 */
        $PaymentInformation->addChild('CdtrAgt')->addChild('FinInstnId')->addChild('BIC', $this->_creditorBic); /* ISO: 2.21 */
        /* ISO: 2.27 */
        $PaymentInformation->addChild('CdtrSchmeId')->addChild('Id')->addChild('PrvtId')->addChild('Othr')->addChild('Id', $this->_creditorId);
        $PaymentInformation->CdtrSchmeId->Id->PrvtId->Othr->addChild('SchmeNm')->addChild('Prtry', 'SEPA');

        /**
         * Transactions
         */
        foreach ($this->_transactions as $Transaction)
            $Transaction->appendToXml($PaymentInformation);
    }

    /**
     * Add a transaction to the list of transactions
     *
     * @param Sepa_DirectDebit_Transaction $Transaction
     * @return Sepa_DirectDebitFile
     */
    public function addTransaction($Transaction) {
        if (!$Transaction instanceof Sepa_DirectDebit_Transaction)
            throw new Sepa_Exception('Invalid transaction');
        $this->_transactions[] = $Transaction;
        return $this;
    }

    /**
     * Calculate the Creditor Identifier for the rabobank
     * This is according to the implementation guidelines version 2.0 of the
     * NVB, chapter 1.5.2 SEPA B2B DD C2B
     *
     * @param int       $kvk        KVK Number
     * @param string    $location   Location code
     *
     */
    public static function calculateRabobankCreditorId($kvk, $location) {
        return 'NL' . (98 - ($kvk . $location . '232100') % 97) . 'ZZZ' . $kvk . '0000';
    }

    /**
     * Returns the previously set message id
     *
     * @return string
     */
    public function getMessageIdentification() {
        return $this->_messageIdentification;
    }

    /**
     * Sets the Unique identifier for the directdebit job ( Tag: <MsgId> )
     *
     * @param  string $messageIdentification
     * @return Sepa_DirectDebit
     */
    public function setMessageIdentification($messageIdentification) {
        $this->_messageIdentification = $messageIdentification;
    }

    /**
     * Returns the previously set creditor id
     *
     * @return string
     */
    public function getCreditorId() {
        return $this->_creditorId;
    }

    /**
     * Sets the Creditor Scheme Identification ( Tag: <CdtrSchmeId> -> <Id> -> <PrvtId> -> <Othr> -> <Id> )
     *
     * @param  string $creditorId
     * @return Sepa_DirectDebit
     */
    public function setCreditorId($creditorId) {
        $this->_creditorId = $creditorId;
        return $this;
    }

    /**
     * Returns the previously set initiating party name (creditor name)
     *
     * @return string
     */
    public function getInitiatingPartyName() {
        return $this->_initiatingPartyName;
    }

    /**
     * Name of the initiating party ( often the creditor name) ( Tag: <InitgPty> )
     *
     * @param  string $initiatingPartyName
     * @return Sepa_DirectDebit
     */
    public function setInitiatingPartyName($initiatingPartyName) {
        $this->_initiatingPartyName = $initiatingPartyName;
        return $this;
    }

    /**
     * Returns the previously set creditor iban account
     *
     * @return string
     */
    public function getCreditorIban() {
        return $this->_creditorIban;
    }

    /**
     * Sets the Creditor's iban number (where the money should go..) ( Tag: <CdtrAcct> -> <Id> -> <IBAN> )
     *
     * @param  string $creditorIban
     * @return Sepa_DirectDebit
     */
    public function setCreditorIban($creditorIban) {
        if ( !$creditorIban || strlen($creditorIban) > 34 ) throw new Sepa_Exception('Invalid IBAN value. Accepted: min-length: 1, max-length: 32');
        $this->_creditorIban = $creditorIban;
        return $this;
    }

    /**
     * Returns the previously set creditor bic code
     *
     * @return string
     */
    public function getCreditorBic() {
        return $this->_creditorBic;
    }

    /**
     * sets the Creditor's BIC ( Tag: <CdtrAcct> -> <Id> -> <BIC> )
     *
     * @param  string $creditorBic
     * @return Sepa_DirectDebit
     */
    public function setCreditorBic($creditorBic) {
        if ( !preg_match('/^[0-9a-z]{4}[a-z]{2}[0-9a-z]{2}([0-9a-z]{3})?\z/i', $creditorBic ) ) throw new Sepa_Exception('Invalid BIC. Accepted: regex: /^[0-9a-z]{4}[a-z]{2}[0-9a-z]{2}([0-9a-z]{3})?\z/i');
        $this->_creditorBic = $creditorBic;
        return $this;
    }

    /**
     * Returns the previously set payment info id
     *
     * @return string
     */
    public function getPaymentInfoId() {
        return $this->_paymentInfoId;
    }

    /**
     * Sets the Unique identification, as assigned by a sending party, to unambiguously identify the payment information
     * group within the message ( Tag: <PmtInfId> )
     *
     * @param  string $paymentInfoId
     * @return Sepa_DirectDebit
     */
    public function setPaymentInfoId($paymentInfoId) {
        if ( !$paymentInfoId || strlen($paymentInfoId) > 35 ) throw new Sepa_Exception('Invalid value for PaymentInfoId. Accepted: min-length: 1, max-length: 32');
        $this->_paymentInfoId = $paymentInfoId;
        return $this;
    }

    /**
     * Returns the previously set requested execution date
     *
     * @return string
     */
    public function getRequestedExecutionDate() {
        return $this->_requestedExecutionDate;
    }

    /**
     * Sets the Requested Execution Date in yyyy-mm-dd format ( Tag: <ReqdColltnDt> )
     *
     * @param  string $requestedExecutionDate
     * @return Sepa_DirectDebit
     */
    public function setRequestedExecutionDate($requestedExecutionDate) {
        $dateArr = explode('-', $requestedExecutionDate);
        if ( !checkdate($dateArr[1], $dateArr[2], $dateArr[0]) ) throw new Sepa_Exception('Invalid date for RequestedExecutionDate. Accepted format: yyyy-mm-dd');
        $this->_requestedExecutionDate = $requestedExecutionDate;
        return $this;
    }

}