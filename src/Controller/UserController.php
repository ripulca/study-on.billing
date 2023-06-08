<?php

namespace App\Controller;

use App\DTO\UserDTO;
use App\Entity\User;
use JMS\Serializer\Serializer;
use OpenApi\Annotations as OA;
use JMS\Serializer\SerializerBuilder;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ManagerRegistry;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Gesdinet\JWTRefreshTokenBundle\Generator\RefreshTokenGeneratorInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

#[Route('/api/v1')]
class UserController extends AbstractController
{
    private Serializer $serializer;
    private ValidatorInterface $validator;
    private UserPasswordHasherInterface $passwordHasher;
    private ObjectManager $entityManager;
    private TokenStorageInterface $tokenStorageInterface;
    private JWTTokenManagerInterface $jwtManager;

    public function __construct(ManagerRegistry $doctrine, ValidatorInterface $validator, UserPasswordHasherInterface $passwordHasher, TokenStorageInterface $tokenStorageInterface, JWTTokenManagerInterface $jwtManager)
    {
        $this->entityManager = $doctrine->getManager();
        $this->serializer = SerializerBuilder::create()->build();
        $this->validator = $validator;
        $this->passwordHasher = $passwordHasher;
        $this->tokenStorageInterface = $tokenStorageInterface;
        $this->jwtManager = $jwtManager;
    }

    /**
     * @Route("/auth", name="api_auth", methods={"POST"})
     * @OA\Post(
     *     description="Get JWT and new refresh token by credentials",
     *     tags={"auth"},
     *     @OA\RequestBody(
     *          @Model(type=UserDto::class)
     *     ),
     *     @OA\Response(
     *          response=200,
     *          description="The JWT token",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="token", type="string"),
     *              @OA\Property(property="refresh_token", type="string")
     *          )
     *     )
     * )
     * Managed by lexik/jwt-authentication-bundle. Used for only OA doc
     * @throws \Exception
     */
    #[Route('/auth', name: 'api_auth', methods: ['POST'])]
    public function auth(): JsonResponse
    {
        throw new \RuntimeException();
    }

    /**
     * @Route("/token/refresh", name="api_refresh_token", methods={"POST"})
     * @OA\Post(
     *     description="Get new valid JWT token renewing valid datetime of presented refresh token",
     *     tags={"auth"},
     *     @OA\RequestBody(
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="refresh_token", type="string")
     *          )
     *     ),
     *     @OA\Response(
     *          response=200,
     *          description="The JWT token",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="token", type="string"),
     *              @OA\Property(property="refresh_token", type="string")
     *          )
     *     )
     * )make
     * Managed by gesdinet/jwt-refresh-token-bundle. Used for only OA doc
     * @throws \Exception
     */
    #[Route('/token/refresh', name: 'api_refresh_token', methods: ['POST'])]
    public function refreshToken()
    {
        throw new \RuntimeException();
    }

    /**
     * @Route("/register", name="api_register", methods={"POST"})
     * @OA\Post(
     *     description="Register new user. Get new JWT and new refresh token",
     *     tags={"auth"},
     *     @OA\RequestBody(
     *          @Model(type=UserDto::class)
     *     ),
     *     @OA\Response(
     *          response=200,
     *          description="Returns the JWT token",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="token", type="string"),
     *              @OA\Property(property="refresh_token", type="string")
     *          )
     *     ),
     *     @OA\Response(
     *          response=409,
     *          description="User already exists",
     *          @OA\JsonContent(
     *              schema="Error",
     *              type="object",
     *              @OA\Property(property="error", type="string")
     *          )
     *     )
     * )
     */
    #[Route('/register', name: 'api_register', methods: ['POST'])]
    public function register(Request $request, RefreshTokenGeneratorInterface $refreshTokenGenerator, RefreshTokenManagerInterface $refreshTokenManager, ): JsonResponse
    {
        $DTO_user = $this->serializer->deserialize($request->getContent(), UserDTO::class, 'json');
        $errors = $this->validator->validate($DTO_user);
        if (count($errors) > 0) {
            $json_errors = [];
            foreach ($errors as $error) {
                $json_errors[] = $error->getMessage();
            }
            return new JsonResponse(['errors' => $json_errors], Response::HTTP_BAD_REQUEST);
        }
        if ($this->entityManager->getRepository(User::class)->findOneByEmail($DTO_user->getUsername())) {
            return new JsonResponse(['errors' => ['Email уже существует']], Response::HTTP_CONFLICT);
        }
        $user = User::getFromDTO($DTO_user);
        $user->setPassword(
            $this->passwordHasher->hashPassword($user, $user->getPassword())
        );

        $this->entityManager->getRepository(User::class)->save($user, true);
        $refreshToken = $refreshTokenGenerator->createForUserWithTtl($user, (new \DateTime())->modify('+1 month')->getTimestamp());
        $refreshTokenManager->save($refreshToken);
        return new JsonResponse([
            'token' => $this->jwtManager->create($user),
            'refresh_token' => $refreshToken->getRefreshToken(),
            'roles' => $user->getRoles(),
        ], Response::HTTP_CREATED);
    }

    /**
     * @Route("/users/current", name="api_get_current_user", methods={"GET"})
     * @OA\Get(
     *     description="Get user data by JWT",
     *     tags={"user"},
     *     @OA\Response(
     *          response=200,
     *          description="The user data",
     *          @OA\JsonContent(
     *              schema="CurrentUser",
     *              type="object",
     *              @OA\Property(property="username", type="string"),
     *              @OA\Property(
     *                  property="roles",
     *                  type="array",
     *                  @OA\Items(type="string")
     *              ),
     *              @OA\Property(property="balance", type="float")
     *          )
     *     )
     * )
     * @Security(name="Bearer")
     * @throws JWTDecodeFailureException
     */
    #[Security(name: 'Bearer')]
    #[Route('/users/current', name: 'api_get_current_user', methods: ['GET'])]
    public function getCurrentUser(): JsonResponse
    {
        if (!$this->tokenStorageInterface->getToken()) {
            return new JsonResponse(['errors' => 'Пользователь не найден'], Response::HTTP_UNAUTHORIZED);
        }
        $decodedJwtToken = $this->jwtManager->decode($this->tokenStorageInterface->getToken());
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['errors' => 'Пользователь не найден'], Response::HTTP_UNAUTHORIZED);
        }

        return new JsonResponse([
            'username' => $decodedJwtToken['email'],
            'roles' => $decodedJwtToken['roles'],
            'balance' => $user->getBalance(),
        ], Response::HTTP_OK);
    }
}