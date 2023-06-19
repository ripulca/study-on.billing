<?php

namespace App\Tests\Controller;

use App\Tests\AbstractTest;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\HttpFoundation\Response;

class TransactionControllerTest extends AbstractTest
{
    private string $authURL = '/api/v1/auth';
    private string $fixture_email = 'user_admin@studyon.com';
    private string $fixture_email_with_no_money = 'user_no_money@studyon.com';
    private string $fixture_password = 'password';
    private float $fixture_balance = 1000;
    public function testGetTransactions(): void
    {
        $client = static::getClient();

        $client->jsonRequest('GET', '/api/v1/transactions/', [
            'type' => null,
            'code' => null,
            'skip_expired' => true
        ]);
        $client->getResponse()->getContent();
        $transactionsInfo = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertCount(2, $transactionsInfo);
    }
    public function testGetTransactionsTypeDepositSkipExp(): void
    {
        $client = static::getClient();

        $client->jsonRequest('GET', '/api/v1/transactions/', [
            'type' => 'deposit',
            'code' => null,
            'skip_expired' => true
        ]);
        $client->getResponse()->getContent();
        $transactionsInfo = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertCount(2, $transactionsInfo);
    }
    public function testGetTransactionsTypePaymentSkipExp(): void
    {
        $client = static::getClient();

        $client->jsonRequest('GET', '/api/v1/transactions/', [
            'type' => 'payment',
            'code' => null,
            'skip_expired' => true
        ]);
        $client->getResponse()->getContent();
        $transactionsInfo = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertCount(2, $transactionsInfo);
    }
    public function testGetTransactionsTypeDeposit(): void
    {
        $client = static::getClient();

        $client->jsonRequest('GET', '/api/v1/transactions/', [
            'type' => 'deposit',
            'code' => null,
            'skip_expired' => false
        ]);
        $client->getResponse()->getContent();
        $transactionsInfo = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertCount(2, $transactionsInfo);
    }
    public function testGetTransactionsTypePayment(): void
    {
        $client = static::getClient();

        $client->jsonRequest('GET', '/api/v1/transactions/', [
            'type' => 'payment',
            'code' => null,
            'skip_expired' => false
        ]);
        $client->getResponse()->getContent();
        $transactionsInfo = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertCount(2, $transactionsInfo);
    }
    public function testGetTransactionsUnauthorized(): void
    {
        $client = static::getClient();

        $client->jsonRequest('GET', '/api/v1/transactions/');
        $this->assertResponseCode(Response::HTTP_UNAUTHORIZED);
    }
}