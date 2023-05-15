<?php

namespace App\Controller;

use App\Enum\TransactionEnum;
use Doctrine\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\TransactionRepository;
use App\DTO\TransactionResponseDTO;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

#[Route('/api/v1')]
class TransactionsController extends AbstractController
{
    private ObjectManager $entityManager;

    public function __construct(EntityManagerInterface $entityManager){
        $this->entityManager=$entityManager;
    }
    #[Route('/transactions', name: 'api_transactions', methods: ['GET'])]
    public function transactions(Request $request, JWTTokenManagerInterface $jwtManager, TokenStorageInterface $tokenStorageInterface): JsonResponse
    {
        $token = $tokenStorageInterface->getToken();
        if (null === $token) {
            return new JsonResponse(['errors' => 'Нет токена'], Response::HTTP_UNAUTHORIZED);
        }
        $decodedJwtToken = $jwtManager->decode($token);
        $filter['type'] = $request->query->get('type') ? TransactionEnum::TYPE_CODES[$request->query->get('type')] : null;
        $filter['course_code']=$request->query->get('course_code');
        $filter['skip_expired']=$request->query->get('skip_expired');
        $transactions=$this->entityManager->getRepository(Transaction::class)->findByFilter($decodedJwtToken['username'], $filter);
        $response=[];
        foreach ($transactions as $transaction) {
            $response[] = new TransactionResponseDTO($transaction);
        }
        return new JsonResponse($response, Response::HTTP_OK);
    }
}
