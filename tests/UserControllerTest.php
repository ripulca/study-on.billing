<?php

namespace App\Tests;

use App\Entity\User;
use App\Tests\AbstractTest;
use App\DataFixtures\AppFixtures;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserControllerTest extends AbstractTest
{
    private string $authURL = '/api/v1/auth';
    private string $registerURL = '/api/v1/register';
    private string $getCurrentUserURL = '/api/v1/users/current';
    private string $fixture_email = 'user_admin@studyon.com';
    private string $fixture_password = 'password';
    private float $fixture_balance = 1000;

    protected function getFixtures(): array
    {
        return [
            new AppFixtures(
                $this->getContainer()->get(UserPasswordHasherInterface::class),
            )
        ];
    }

    public function testAuthSuccess()
    {
        $client = static::getClient();
        $client->jsonRequest('POST', $this->authURL, [
            "username" => $this->fixture_email,
            "password" => $this->fixture_password
        ]);
        $this->assertResponseCode(Response::HTTP_OK);
        $response = $client->getResponse();
        $responseData = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertTrue(isset($responseData['token']));
    }
    public function testAuthFailed()
    {
        $client = static::getClient();
        // Нет username
        $client->jsonRequest('POST', $this->authURL, [
            "password" => "235346545"
        ]);
        $this->assertResponseCode(Response::HTTP_BAD_REQUEST);
        // Нет password
        $client->jsonRequest('POST', $this->authURL, [
            "username" => "example@example.com",
        ]);
        $this->assertResponseCode(Response::HTTP_BAD_REQUEST);
        // Неверный username
        $client->jsonRequest('POST', $this->authURL, [
            "username" => "example@example.com",
            "password" => $this->fixture_password
        ]);
        $this->assertResponseCode(Response::HTTP_UNAUTHORIZED);
        // Неверный password
        $client->jsonRequest('POST', $this->authURL, [
            "username" => $this->fixture_email,
            "password" => "3456378465926"
        ]);
        $this->assertResponseCode(Response::HTTP_UNAUTHORIZED);

    }
    public function testRegisterSuccess()
    {
        $client = static::getClient();
        $email = 'example@example.com';
        $password = 'password';

        $client->jsonRequest('POST', $this->registerURL, [
            "username" => $email,
            "password" => $password
        ]);
        $this->assertResponseCode(Response::HTTP_CREATED);

        $response = $client->getResponse();
        $responseData = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertTrue(isset($responseData['token']));
        $this->assertSame(1, $this->getEntityManager()->getRepository(User::class)->count(['email' => $email]));

        $client = static::getClient();
        $client->jsonRequest('POST', $this->authURL, [
            "username" => $email,
            "password" => $password
        ]);
        $this->assertResponseCode(Response::HTTP_OK);
        $response = $client->getResponse();
        $responseData = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertTrue(isset($responseData['token']));
    }
    public function testRegisterFailed()
    {
        $client = static::getClient();
        $email = 'example@example.com';
        $password = 'password';

        // Нет username
        $client->jsonRequest('POST', $this->registerURL, [
            "password" => $password,
        ]);
        $this->assertResponseCode(Response::HTTP_BAD_REQUEST);
        // Нет password
        $client->jsonRequest('POST', $this->registerURL, [
            "username" => $email,
        ]);
        $this->assertResponseCode(Response::HTTP_BAD_REQUEST);
        // Пароль меньше 6 символов
        $client->jsonRequest('POST', $this->registerURL, [
            "username" => $email,
            "password" => "12345"
        ]);
        $this->assertResponseCode(Response::HTTP_BAD_REQUEST);
        // Неверный username
        $client->jsonRequest('POST', $this->registerURL, [
            "username" => "@example.com",
            "password" => $password
        ]);
        $this->assertResponseCode(Response::HTTP_BAD_REQUEST);
        // Такой username уже используют
        $client->jsonRequest('POST', $this->registerURL, [
            "username" => $this->fixture_email,
            "password" => $password
        ]);
        $this->assertResponseCode(Response::HTTP_CONFLICT);

    }
    public function testGetCurrentUserSuccess()
    {
        $client = static::getClient();
        $client->jsonRequest('POST', $this->authURL, [
            "username" => $this->fixture_email,
            "password" => $this->fixture_password
        ]);
        $this->assertResponseCode(Response::HTTP_OK);
        $response = $client->getResponse();
        $responseData = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $token = $responseData['token'];
        $this->assertTrue(isset($token));
        $client->request('GET', $this->getCurrentUserURL, [], [], ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]);
        $this->assertResponseCode(Response::HTTP_OK);
        $response = $client->getResponse();
        $responseData = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals($this->fixture_email, $responseData['username']);
        $this->assertTrue(in_array('ROLE_SUPER_ADMIN', $responseData['roles'], true));
        $this->assertEquals($this->fixture_balance, $responseData['balance']);
    }
    public function testGetCurrentUserFailed()
    {
        $client = static::getClient();
        $client->jsonRequest('POST', $this->authURL, [
            "username" => $this->fixture_email,
            "password" => $this->fixture_password
        ]);
        $this->assertResponseCode(Response::HTTP_OK);
        $response = $client->getResponse();
        $responseData = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $token = $responseData['token'];
        $this->assertTrue(isset($token));
        //без токена
        $client->request('GET', $this->getCurrentUserURL, [], [], []);
        $this->assertResponseCode(Response::HTTP_UNAUTHORIZED);
    }
}