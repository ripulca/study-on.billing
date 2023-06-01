<?php

namespace App\Controller;

use App\Entity\Course;
use App\Entity\Transaction;
use App\Enum\TransactionEnum;
use OpenApi\Annotations as OA;
use App\DTO\TransactionResponseDTO;
use Doctrine\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\TransactionRepository;
use Doctrine\Common\Collections\Criteria;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

#[Route('/api/v1/transactions')]
class TransactionsController extends AbstractController
{
    private ObjectManager $entityManager;

    public function __construct(EntityManagerInterface $entityManager){
        $this->entityManager=$entityManager;
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
     *     )
     * )
     * @Security(name="Bearer")
     * @throws JWTDecodeFailureException
     */
    #[Route('/', name: 'api_get_transactions', methods: ['GET'])]
    public function transactions(Request $request, JWTTokenManagerInterface $jwtManager, TokenStorageInterface $tokenStorageInterface): JsonResponse
    {
        $token = $tokenStorageInterface->getToken();
        if (null === $token) {
            return new JsonResponse(['errors' => 'Нет токена'], Response::HTTP_UNAUTHORIZED);
        }
        $decodedJwtToken = $jwtManager->decode($token);
        $type = $request->query->get('type') ? TransactionEnum::TYPE_CODES[$request->query->get('type')] : null;
        $course_code=$request->query->get('course_code')? :null;
        $skip_expired =$request->query->get('skip_expired');
        // if($this->getUser()->getUserIdentifier()!=$decodedJwtToken['email']){
        //     return 
        // }
        $course=$this->entityManager->getRepository(Course::class)->findOneBy(['code'=>$course_code]);
        $criteria=Criteria::create()->where(Criteria::expr()->eq('customer', $this->getUser()))->andWhere(Criteria::expr()->eq('type', $type));
        if($course_code){
            $criteria->andWhere(Criteria::expr()->eq('course', $course));
        }
        if($skip_expired){
            $criteria->andWhere(Criteria::expr()->orX(Criteria::expr()->gte('expires',new \DateTime()), Criteria::expr()->isNull('expires')));
        }
        $transactions=$this->entityManager->getRepository(Transaction::class)->matching($criteria);
        $response=[];
        foreach ($transactions as $transaction) {
            $response[] = new TransactionResponseDTO($transaction);
        }
        return new JsonResponse($response, Response::HTTP_OK);
    }
}
