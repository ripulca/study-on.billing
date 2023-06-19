<?php

namespace App\Tests;

use App\Entity\User;
use App\Entity\Course;
use App\Enum\CourseEnum;
use App\Entity\Transaction;
use App\Tests\AbstractTest;
use App\DTO\CourseRequestDTO;
use App\Service\PaymentService;
use App\DataFixtures\AppFixtures;
use Symfony\Component\HttpFoundation\Response;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Gesdinet\JWTRefreshTokenBundle\Generator\RefreshTokenGeneratorInterface;

class CourseControllerTest extends AbstractTest
{
    private string $authURL = '/api/v1/auth';
    private string $fixture_email = 'user_admin@studyon.com';
    private string $fixture_email_with_no_money = 'user_no_money@studyon.com';
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
    public function testGetCourses(): void
    {
        $client = static::getClient();

        $client->request('GET', '/api/v1/courses/');
        $courses = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertResponseOk();

        $this->assertCount(5, $courses);
    }

    public function testGetCourse(): void
    {
        $client = static::getClient();

        $entityManager = $this->getEntityManager();
        $courseRepository = $entityManager->getRepository(Course::class);
        $free_course = $courseRepository->findOneBy(['type' => CourseEnum::FREE]);
        $client->request('GET', '/api/v1/courses/' . $free_course->getCode());
        $this->assertResponseOk();

        $course = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertEquals($free_course->getCode(), $course['code']);
        $this->assertEquals(CourseEnum::FREE, CourseEnum::VALUES[$course['type']]);
    }

    public function testAddCourse(): void
    {
        $client = static::getClient();
        $client->jsonRequest('POST', $this->authURL, [
            "username" => $this->fixture_email,
            "password" => $this->fixture_password
        ]);
        $userInfo = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $client->jsonRequest(
            'POST',
            '/api/v1/courses/new',
            [
                'name' => 'test name',
                'code' => 'test code',
                'price' => 10.0,
                'type' => 2
            ],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $userInfo['token'],
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json'
            ],
        );
        $this->assertResponseCode(Response::HTTP_CREATED);
        $this->assertTrue(json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR)['success']);
    }

    public function testAddCourseWithNoPrice(): void
    {
        $client = static::getClient();
        $client->jsonRequest('POST', $this->authURL, [
            "username" => $this->fixture_email,
            "password" => $this->fixture_password
        ]);
        $userInfo = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $client->jsonRequest(
            'POST',
            '/api/v1/courses/new',
            [
                'name' => 'test name',
                'code' => 'test code',
                'price' => 0,
                'type' => 2
            ],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $userInfo['token'],
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json'
            ],
        );
        $this->assertResponseCode(Response::HTTP_FORBIDDEN);
    }

    public function testAddCourseWithEmptyName(): void
    {
        $client = static::getClient();
        $client->jsonRequest('POST', $this->authURL, [
            "username" => $this->fixture_email,
            "password" => $this->fixture_password
        ]);
        $userInfo = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $client->jsonRequest(
            'POST',
            '/api/v1/courses/new',
            [
                'name' => '',
                'code' => 'test code',
                'price' => 10.0,
                'type' => 2
            ],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $userInfo['token'],
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json'
            ],
        );
        $this->assertResponseCode(Response::HTTP_BAD_REQUEST);
    }

    public function testAddCourseWithNotUniqueCode(): void
    {
        $client = static::getClient();
        $client->jsonRequest('POST', $this->authURL, [
            "username" => $this->fixture_email,
            "password" => $this->fixture_password
        ]);
        $userInfo = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $client->jsonRequest(
            'POST',
            '/api/v1/courses/new',
            [
                'name' => 'test name',
                'code' => 'test_buy',
                'price' => 10.0,
                'type' => 2
            ],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $userInfo['token'],
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json'
            ],
        );
        $this->assertResponseCode(Response::HTTP_CONFLICT);
    }

    public function testAddCourseWithoutToken(): void
    {
        $client = static::getClient();
        $client->jsonRequest('POST', $this->authURL, [
            "username" => $this->fixture_email,
            "password" => $this->fixture_password
        ]);
        $userInfo = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $client->jsonRequest(
            'POST',
            '/api/v1/courses/new',
            [
                'name' => 'test name',
                'code' => 'test code',
                'price' => 10.0,
                'type' => 2
            ],
        );
        $this->assertResponseCode(Response::HTTP_UNAUTHORIZED);
    }

    public function testEditCourse(): void
    {
        $client = static::getClient();
        $client->jsonRequest('POST', $this->authURL, [
            "username" => $this->fixture_email,
            "password" => $this->fixture_password
        ]);
        $userInfo = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $client->jsonRequest(
            'POST',
            '/api/v1/courses/test_buy/edit',
            [
                'name' => 'test name',
                'code' => 'test code',
                'price' => 10.0,
                'type' => 2
            ],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $userInfo['token'],
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json'
            ],
        );
        $this->assertResponseCode(Response::HTTP_OK);
        $this->assertTrue(json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR)['success']);
    }

    public function testEditCourseNoPrice(): void
    {
        $client = static::getClient();
        $client->jsonRequest('POST', $this->authURL, [
            "username" => $this->fixture_email,
            "password" => $this->fixture_password
        ]);
        $userInfo = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $client->jsonRequest(
            'POST',
            '/api/v1/courses/test_buy/edit',
            [
                'name' => 'test name',
                'code' => 'test code',
                'price' => 0.0,
                'type' => 2
            ],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $userInfo['token'],
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json'
            ],
        );
        $this->assertResponseCode(Response::HTTP_FORBIDDEN);
    }

    public function testEditCourseNoToken(): void
    {
        $client = static::getClient();
        $client->jsonRequest('POST', $this->authURL, [
            "username" => $this->fixture_email,
            "password" => $this->fixture_password
        ]);
        $userInfo = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $client->jsonRequest(
            'POST',
            '/api/v1/courses/test_buy/edit',
            [
                'name' => 'test name',
                'code' => 'test code',
                'price' => 10.0,
                'type' => 2
            ],
        );
        $this->assertResponseCode(Response::HTTP_UNAUTHORIZED);
    }

    public function testEditCourseWithEmptyName(): void
    {
        $client = static::getClient();
        $client->jsonRequest('POST', $this->authURL, [
            "username" => $this->fixture_email,
            "password" => $this->fixture_password
        ]);
        $userInfo = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $client->jsonRequest(
            'POST',
            '/api/v1/courses/test_buy/edit',
            [
                'name' => '',
                'code' => 'test code',
                'price' => 10.0,
                'type' => 2
            ],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $userInfo['token'],
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json'
            ],
        );
        $this->assertResponseCode(Response::HTTP_BAD_REQUEST);
    }

    public function testEditCourseNotFound(): void
    {
        $client = static::getClient();
        $client->jsonRequest('POST', $this->authURL, [
            "username" => $this->fixture_email,
            "password" => $this->fixture_password
        ]);
        $userInfo = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $client->jsonRequest(
            'POST',
            '/api/v1/courses/test buy/edit',
            [
                'name' => 'test name',
                'code' => 'test code',
                'price' => 10.0,
                'type' => 2
            ],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $userInfo['token'],
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json'
            ],
        );
        $this->assertResponseCode(Response::HTTP_CONFLICT);
    }

    public function testPayCourse(): void
    {
        $client = static::getClient();

        $entityManager = $this->getEntityManager();
        $courseRepository = $entityManager->getRepository(Course::class);
        $pay_course = $courseRepository->findOneBy(['type' => CourseEnum::RENT]);

        // Пользователь, у которого есть средства
        $client->jsonRequest('POST', $this->authURL, [
            "username" => $this->fixture_email,
            "password" => $this->fixture_password
        ]);
        $userInfo = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        // Успешная оплата курса
        $client->jsonRequest('POST', '/api/v1/courses/' . $pay_course->getCode() . '/pay', [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $userInfo['token'],
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json'
        ]);
        $this->assertResponseCode(Response::HTTP_OK);
        $payInfo = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals(1, $payInfo['success']);
    }

    public function testPayCourseNoMoney(): void
    {
        $client = static::getClient();

        $entityManager = $this->getEntityManager();
        $courseRepository = $entityManager->getRepository(Course::class);
        $pay_course = $courseRepository->findOneBy(['type' => CourseEnum::RENT]);

        $client->jsonRequest('POST', $this->authURL, [
            "username" => $this->fixture_email_with_no_money,
            "password" => $this->fixture_password
        ]);
        $userInfo = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        // Успешная оплата курса
        $client->jsonRequest('POST', '/api/v1/courses/' . $pay_course->getCode() . '/pay', [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $userInfo['token'],
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json'
        ]);
        $this->assertResponseCode(Response::HTTP_NOT_ACCEPTABLE);
    }

    public function testPayCourseNotFound(): void
    {
        $client = static::getClient();

        // Пользователь, у которого есть средства
        $client->jsonRequest('POST', $this->authURL, [
            "username" => $this->fixture_email,
            "password" => $this->fixture_password
        ]);
        $userInfo = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        // Успешная оплата курса
        $client->jsonRequest('POST', '/api/v1/courses/utyuewtr/pay', [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $userInfo['token'],
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json'
        ]);
        $this->assertResponseCode(Response::HTTP_NOT_FOUND);
    }

    public function testPayCourseNoToken(): void
    {
        $client = static::getClient();

        $entityManager = $this->getEntityManager();
        $courseRepository = $entityManager->getRepository(Course::class);
        $pay_course = $courseRepository->findOneBy(['type' => CourseEnum::RENT]);

        // Пользователь, у которого есть средства
        $client->jsonRequest('POST', $this->authURL, [
            "username" => $this->fixture_email,
            "password" => $this->fixture_password
        ]);

        // Успешная оплата курса
        $client->jsonRequest('POST', '/api/v1/courses/' . $pay_course->getCode() . '/pay', [], );
        $this->assertResponseCode(Response::HTTP_UNAUTHORIZED);
    }
}