<?php

namespace App\Tests;

use App\Entity\User;
use App\Tests\AbstractTest;
use App\Service\PaymentService;
use App\DataFixtures\AppFixtures;
use Symfony\Component\HttpFoundation\Response;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Gesdinet\JWTRefreshTokenBundle\Generator\RefreshTokenGeneratorInterface;

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
                $this->getContainer()->get(RefreshTokenGeneratorInterface::class),
                $this->getContainer()->get(RefreshTokenManagerInterface::class),
                $this->getContainer()->get(PaymentService::class),
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
        $this->assertNotNull($responseData['token']);
    }
    public function testAuthWithEmptyUsername()
    {
        $client = static::getClient();
        // Нет username
        $client->jsonRequest('POST', $this->authURL, [
            "password" => "235346545"
        ]);
        $this->assertResponseCode(Response::HTTP_BAD_REQUEST);
    }
    public function testAuthWithEmptyPassword()
    {
        $client = static::getClient();
        // Нет password
        $client->jsonRequest('POST', $this->authURL, [
            "username" => "example@example.com",
        ]);
        $this->assertResponseCode(Response::HTTP_BAD_REQUEST);
    }
    public function testAuthIncorrectUsername()
    {
        $client = static::getClient();
        // Неверный username
        $client->jsonRequest('POST', $this->authURL, [
            "username" => "example@example.com",
            "password" => $this->fixture_password
        ]);
        $this->assertResponseCode(Response::HTTP_UNAUTHORIZED);
        $response = $client->getResponse();
        $responseData = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals("Invalid credentials.",$responseData['message']);
    }
    public function testAuthIncorrectPassword()
    {
        $client = static::getClient();
        // Неверный password
        $client->jsonRequest('POST', $this->authURL, [
            "username" => $this->fixture_email,
            "password" => "3456378465926"
        ]);
        $this->assertResponseCode(Response::HTTP_UNAUTHORIZED);
        $response = $client->getResponse();
        $responseData = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals("Invalid credentials.",$responseData['message']);

    }
    public function testRegisterSuccessWithAuthCheck()
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
        $this->assertNotNull($responseData['token']);
        $this->assertSame(1, $this->getEntityManager()->getRepository(User::class)->count(['email' => $email]));

        $client = static::getClient();
        $client->jsonRequest('POST', $this->authURL, [
            "username" => $email,
            "password" => $password
        ]);
        $this->assertResponseCode(Response::HTTP_OK);
        $response = $client->getResponse();
        $responseData = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertNotNull($responseData['token']);
    }
    public function testRegisterWithEmptyUsername()
    {
        $client = static::getClient();
        $password = 'password';
        // Нет username
        $client->jsonRequest('POST', $this->registerURL, [
            "password" => $password,
        ]);
        $this->assertResponseCode(Response::HTTP_BAD_REQUEST);
        $response = $client->getResponse();
        $responseData = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals("Email не может быть пуст.",$responseData['errors'][0]);
    }
    public function testRegisterWithEmptyPassword()
    {
        $client = static::getClient();
        $email = 'example@example.com';
        // Нет password
        $client->jsonRequest('POST', $this->registerURL, [
            "username" => $email,
        ]);
        $this->assertResponseCode(Response::HTTP_BAD_REQUEST);
        $response = $client->getResponse();
        $responseData = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals("Пароль не может быть пуст.",$responseData['errors'][0]);
    }
    public function testRegisterWithInvalidPassword()
    {
        $client = static::getClient();
        $email = 'example@example.com';
        // Пароль меньше 6 символов
        $client->jsonRequest('POST', $this->registerURL, [
            "username" => $email,
            "password" => "12345"
        ]);
        $this->assertResponseCode(Response::HTTP_BAD_REQUEST);
        $response = $client->getResponse();
        $responseData = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals("Пароль должен содержать минимум 6 символов.",$responseData['errors'][0]);
    }
    public function testRegisterWithInvalidUsername()
    {
        $client = static::getClient();
        $password = 'password';
        // Неверный username
        $client->jsonRequest('POST', $this->registerURL, [
            "username" => "@example.com",
            "password" => $password
        ]);
        $this->assertResponseCode(Response::HTTP_BAD_REQUEST);
        $response = $client->getResponse();
        $responseData = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals("Email не является валидным.",$responseData['errors'][0]);
    }
    public function testRegisterWithBusyUsername()
    {
        $client = static::getClient();
        $email = 'example@example.com';
        $password = 'password';
        // Такой username уже используют
        $client->jsonRequest('POST', $this->registerURL, [
            "username" => $this->fixture_email,
            "password" => $password
        ]);
        $this->assertResponseCode(Response::HTTP_CONFLICT);
        $response = $client->getResponse();
        $responseData = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals("Email уже существует",$responseData['errors'][0]);

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
        $this->assertNotNull($responseData['token']);
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
        $this->assertNotNull($responseData['token']);
        //без токена
        $client->request('GET', $this->getCurrentUserURL, [], [], []);
        $this->assertResponseCode(Response::HTTP_UNAUTHORIZED);
        $response = $client->getResponse();
        $responseData = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals("Пользователь не найден",$responseData['errors']);
    }
}