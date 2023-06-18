<?php

namespace App\Controller;

use App\Entity\Transaction;
use App\Enum\TransactionEnum;
use OpenApi\Annotations as OA;
use App\DTO\TransactionResponseDTO;
use Doctrine\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

#[Route('/api/v1/transactions')]
class TransactionsController extends AbstractController
{
    private ObjectManager $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/", name="api_get_transactions", methods={"GET"})
     * @OA\Get(
     *     description="Get user transactions",
     *     tags={"transaction"},
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         required=false,
     *         description="Transaction type filter",
     *         @OA\Property(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="course_code",
     *         in="query",
     *         required=false,
     *         description="Course code filter",
     *         @OA\Property(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="skip_expired",
     *         in="query",
     *         required=false,
     *         description="Skip expired transactions filter (e.g. rent payment)",
     *         @OA\Property(type="boolean")
     *     ),
     *     @OA\Response(
     *          response=200,
     *          description="The transactions data",
     *          @OA\JsonContent(
     *              schema="TransactionsInfo",
     *              type="array",
     *              @OA\Items(ref=@Model(type=TransactionResponseDTO::class, groups={"info"}))
     *          )
     *     ),
     *     @OA\Response(
     *          response=401,
     *          description="UNAUTHORIZED",
     *          @OA\JsonContent(
     *              schema="TransactionsInfo",
     *              type="array",
     *              @OA\Items(ref=@Model(type=TransactionResponseDTO::class, groups={"info"}))
     *          )
     *     )
     * )
     * @Security(name="Bearer")
     */
    #[Security(name: 'Bearer')]
    #[Route('/', name: 'api_get_transactions', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function transactions(Request $request, JWTTokenManagerInterface $jwtManager, TokenStorageInterface $tokenStorageInterface): JsonResponse
    {
        if (!$tokenStorageInterface->getToken()) {
            return new JsonResponse(['errors' => 'Нет токена'], Response::HTTP_UNAUTHORIZED);
        }
        if (!$this->getUser()) {
            return new JsonResponse(['errors' => 'Пользователь не авторизован'], Response::HTTP_UNAUTHORIZED);
        }
        $type = $request->query->get('type') ? TransactionEnum::TYPE_CODES[$request->query->get('type')] : null;
        $code = $request->query->get('code') ?: null;
        $skip_expired = $request->query->get('skip_expired');
        $transactions = $this->entityManager->getRepository(Transaction::class)->findByFilters($this->getUser(), $type, $code, $skip_expired);
        $response = [];
        foreach ($transactions as $transaction) {
            $response[] = TransactionResponseDTO::fromTransaction($transaction);
        }
        return new JsonResponse($response, Response::HTTP_OK);
    }
}