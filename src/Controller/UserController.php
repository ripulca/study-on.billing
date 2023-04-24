<?php

namespace App\Controller;

use App\DTO\UserDTO;
use App\Entity\User;
use JMS\Serializer\Serializer;
use JMS\Serializer\SerializerBuilder;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
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

    #[Route('/auth', name: 'api_auth', methods: ['POST'])]
    public function auth(): JsonResponse
    {
        return $this->json([]);
    }

    #[Route('/register', name: 'api_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $DTO_user = $this->serializer->deserialize($request->getContent(), UserDTO::class, 'json');
        $errors = $this->validator->validate($DTO_user);
        if ($errors) {
            return new JsonResponse(['errors' => $errors], Response::HTTP_BAD_REQUEST);
        }
        if($this->entityManager->getRepository(User::class)->findOneByEmail($DTO_user->email)) {
            return new JsonResponse(['errors' => ['Email уже существует']], Response::HTTP_BAD_REQUEST);
        }
        $user = User::getFromDTO($DTO_user);
        $user->setPassword(
            $this->passwordHasher->hashPassword($user, $user->getPassword())
        );
        $this->entityManager->getRepository(User::class)->save($user, true);

        return new JsonResponse([
            'token' => $this->jwtManager->create($user),
            'roles'=>$user->getRoles(),
        ], Response::HTTP_CREATED);
    }

    #[Route('/users/current', name: 'api_get_current_user', methods: ['POST'])]
    public function getCurrentUser(): JsonResponse
    {
        $decodedJwtToken = $this->jwtManager->decode($this->tokenStorageInterface->getToken());

        $user=$this->entityManager->getRepository(User::class)->findOneByEmail($decodedJwtToken['username']);

        return new JsonResponse([
            'email' => $decodedJwtToken['username'],
            'roles' => $user->getRoles(),
            'balance' => $user->getBalance(),
        ], Response::HTTP_OK);
    }
}
