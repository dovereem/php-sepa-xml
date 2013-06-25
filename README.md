[![githalytics.com alpha](https://cruel-carlota.pagodabox.com/77a16b2a3b6a54e7e303f64e2337b4b6 "githalytics.com")](http://githalytics.com/dovereem/php-sepa-xml)

php-sepa-xml
============

Classes for generating Sepa XML files

usage
============
```php
<?php

// This is normally handled by my autoloader, but for
// example sake lets include here..
require_once('classes/Sepa/Exeption.php');
require_once('classes/Sepa/Base.php');
require_once('classes/Sepa/DirectDebit.php');
require_once('classes/Sepa/DirectDebit/Transaction.php');

$SepaFile = new Sepa_DirectDebit();

// Unique identifier for this job
$SepaFile->setMessageIdentification('SEPA_000000001');

// Name of the party sending the job. Usually the creditor
$SepaFile->setInitiatingPartyName('Initiating party name');

// Your own unique identifier for this batch
$SepaFile->setPaymentInfoId(1);

// Account on which payment should be recieved
$SepaFile->setCreditorIban('NL44RABO0123456789');
$SepaFile->setCreditorBic('RABONL2U');

// Creditor Scheme Identification. This might differ per bank. Example is for Rabobank
$SepaFile->setCreditorId( Sepa_DirectDebit::calculateRabobankCreditorId('12345678','0000') );

// Date on which the job should be executed
$SepaFile->setRequestedExecutionDate('2013-07-10');

// Add a transaction to the batch
$SepaFile->addTransaction(
    Sepa_DirectDebit_Transaction::factory()
        ->setEndToEndId('endtoend1') // Unique identifier
        ->setAmount(13.50)
        ->setTransactionIdentifier('123456789')
        ->setSignatureDate('2013-03-01')
        ->setDebtorName('Damien Overeem')
        ->setDebtorIban('NL44RABO0123456789')
        ->SetDebtorBic('RABONL2U')
        ->setTransactionDescription('Text about debit')
);

// Add another transaction to the batch
$SepaFile->addTransaction(
    Sepa_DirectDebit_Transaction::factory()
        ->setEndToEndId('endtoend2') // Unique identifier
        ->setAmount(1.99)
        ->setTransactionIdentifier('123456789')
        ->setSignatureDate('2013-03-01')
        ->setDebtorName('Henk de Vries')
        ->setDebtorIban('NL44RABO0123456789')
        ->SetDebtorBic('RABONL2U')
        ->setTransactionDescription('Text about debit')
);


/**
 * Generate the file and return the XML string in pretty format (using dom to format)
 * To get the raw non-pretty version, just echo $SepaFile or $SepaFile->asXml()
 */
$SimpleXml = new SimpleXmlElement($SepaFile);
$dom = dom_import_simplexml($SimpleXml)->ownerDocument;
$dom->formatOutput = true;
echo $dom->saveXML();
```
