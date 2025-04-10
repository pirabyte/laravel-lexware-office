<?php

namespace Pirabyte\LaravelLexwareOffice\Tests\Feature;

use Pirabyte\LaravelLexwareOffice\Models\FinancialTransaction;
use Pirabyte\LaravelLexwareOffice\Tests\TestCase;

class FinancialTransactionResourceTest extends TestCase
{
    public function test_it_can_create_valid_financial_transaction_json()
    {
        $fixtureFile = __DIR__ . '/../Fixtures/financial-transactions/1_financial_transactions_response.json';
        $fixtureContents = file_get_contents($fixtureFile);
        $fixtureData = json_decode($fixtureContents, true);

        foreach($fixtureData as $fixtureFinancialTransaction) {
            $financialTransaction = new FinancialTransaction();

            $financialTransaction->setValueDate($fixtureFinancialTransaction['valueDate']);
            $financialTransaction->setBookingDate($fixtureFinancialTransaction['bookingDate']);
            $financialTransaction->setTransactionDate($fixtureFinancialTransaction['transactiondate']);
            $financialTransaction->setPurpose($fixtureFinancialTransaction['purpose']);
            $financialTransaction->setAmount($fixtureFinancialTransaction['amount']);
            $financialTransaction->setAdditionalInfo($fixtureFinancialTransaction['additionalInfo']);
            $financialTransaction->setRecipientOrSenderName($fixtureFinancialTransaction['recipientOrSenderName']);
            $financialTransaction->setRecipientOrSenderIban($fixtureFinancialTransaction['recipientOrSenderIban']);
            $financialTransaction->setRecipientOrSenderBic($fixtureFinancialTransaction['recipientOrSenderBic']);
            $financialTransaction->setFinancialAccountId($fixtureFinancialTransaction['financialAccountId']);
            $financialTransaction->setExternalReference($fixtureFinancialTransaction['externalReference']);

            $this->validate_financial_transaction_with_fixture_data($financialTransaction, $fixtureFinancialTransaction);
        }
    }

    public function test_it_can_parse_financial_transaction_response()
    {
        $fixtureFile = __DIR__ . '/../Fixtures/financial-transactions/1_financial_transactions_response.json';
        $fixtureContents = file_get_contents($fixtureFile);
        $fixtureData = json_decode($fixtureContents, true);
        foreach($fixtureData as $fixtureFinancialTransaction) {
            $financialTransaction = FinancialTransaction::fromArray($fixtureFinancialTransaction);
            $this->validate_financial_transaction_with_fixture_data($financialTransaction, $fixtureFinancialTransaction);
        }
    }

    private function validate_financial_transaction_with_fixture_data(FinancialTransaction $transaction, array $fixtureData): void
    {
        $this->assertEquals($transaction->getValueDate(), $fixtureData['valueDate']);
        $this->assertEquals($transaction->getBookingDate(), $fixtureData['bookingDate']);
        $this->assertEquals($transaction->getTransactionDate(), $fixtureData['transactiondate']);
        $this->assertEquals($transaction->getPurpose(), $fixtureData['purpose']);
        $this->assertEquals($transaction->getAmount(), $fixtureData['amount']);
        $this->assertEquals($transaction->getAdditionalInfo(), $fixtureData['additionalInfo']);
        $this->assertEquals($transaction->getRecipientOrSenderName(), $fixtureData['recipientOrSenderName']);
        $this->assertEquals($transaction->getRecipientOrSenderIban(), $fixtureData['recipientOrSenderIban']);
        $this->assertEquals($transaction->getRecipientOrSenderBic(), $fixtureData['recipientOrSenderBic']);
        $this->assertEquals($transaction->getFinancialAccountId(), $fixtureData['financialAccountId']);
        $this->assertEquals($transaction->getExternalReference(), $fixtureData['externalReference']);
    }
}