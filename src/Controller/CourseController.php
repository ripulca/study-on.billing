<?php

namespace App\Controller;

use App\Entity\User;
use App\Enum\CourseEnum;
use App\DTO\CourseResponseDTO;
use OpenApi\Annotations as OA;
use App\DTO\PaymentResponseDTO;
use App\Service\PaymentService;
use App\Repository\CourseRepository;
use Doctrine\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/api/v1/courses')]
class CourseController extends AbstractController
{
    private ObjectManager $entityManager;

    public function __construct(EntityManagerInterface $entityManager){
        $this->entityManager=$entityManager;
    }

    /**
     * @Route("/", name="api_courses", methods={"GET"})
     * @OA\Get(
     *     description="Get courses data",
     *     tags={"course"},
     *     @OA\Response(
     *          response=200,
     *          description="The courses data",
     *          @OA\JsonContent(
     *              schema="CoursesInfo",
     *              type="array",
     *              @OA\Items(ref=@Model(type=CourseResponseDTO::class, groups={"info"}))
     *          )
     *     )
     * )
     */
    #[Route('/', name: 'api_courses', methods: ['GET'])]
    public function courses(CourseRepository $courseRepository){
        $courses=$courseRepository->findAll();
        $response=[];
        foreach ($courses as $course) {
            $response[] = new CourseResponseDTO($course);
        }
        return new JsonResponse($response, Response::HTTP_OK);
    }

    /**
     * @Route("/{code}", name="api_course", methods={"GET"})
     * @OA\Get(
     *     description="Get course data",
     *     tags={"course"},
     *     @OA\Response(
     *          response=200,
     *          description="The course data",
     *          @OA\JsonContent(
     *              ref=@Model(type=CourseResponseDTO::class, groups={"info"})
     *          )
     *     ),
     *     @OA\Response(
     *          response=404,
     *          description="Not found",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="error", type="string")
     *          )
     *     )
     * )
     */
    #[Route('/{code}', name: 'api_course', methods: ['GET'])]
    public function course(string $code, CourseRepository $courseRepository){
        $course=$courseRepository->findOneBy(['code' => $code]);
        if(!$course){
            return new JsonResponse(['errors' =>"Курс $code не найден"], Response::HTTP_NOT_FOUND);
        }
        $course=new CourseResponseDTO($course);
        return new JsonResponse($course, Response::HTTP_OK);
    }

    /**
     * @Route("/{code}/pay", name="api_pay_for_courses", methods={"POST"})
     * @OA\Post(
     *     description="Pay for the course",
     *     tags={"course"},
     *     @OA\Response(
     *          response=200,
     *          description="Succeded pay info",
     *          @OA\JsonContent(
     *              schema="PayInfo",
     *              type="object",
     *              @OA\Property(property="success", type="boolean"),
     *              @OA\Property(property="course_type", type="string"),
     *              @OA\Property(property="expires_at", type="datetime", format="Y-m-d\\TH:i:sP")
     *          )
     *     ),
     *     @OA\Response(
     *          response=406,
     *          description="Not enough funds",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="error", type="string")
     *          )
     *     ),
     *     @OA\Response(
     *          response=409,
     *          description="User has already paid for this course",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="error", type="string")
     *          )
     *     )
     * )
     * @Security(name="Bearer")
     */
    #[Route('/{code}/pay', name: 'api_pay_for_courses', methods: ['POST'])]
    public function payForCourses(string $code, PaymentService $paymentService, CourseRepository $courseRepository){
        $course=$courseRepository->findOneBy(['code' => htmlspecialchars($code)]);
        if(!$course){
            return new JsonResponse(['success'=>false,'errors' =>"Курс $code не найден"], Response::HTTP_NOT_FOUND);
        }
        if($course->getType()===CourseEnum::FREE){
            $response= new PaymentResponseDTO(true, $course->getType(), null);
            return new JsonResponse($response, Response::HTTP_OK);
        }
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['success'=>false,'errors' => 'Пользователь не авторизован'], Response::HTTP_UNAUTHORIZED);
        }
        try{
            $transaction = $paymentService->payment($user, $course);
            $expires = $transaction->getExpires()?: null;
            $response= new PaymentResponseDTO(true, $course->getType(), $expires);
            return new JsonResponse($response, Response::HTTP_OK);
        }
        catch(\RuntimeException $exeption){
            return new JsonResponse(['success'=>false,'errors'=>$exeption->getMessage()], Response::HTTP_NOT_ACCEPTABLE);
        }
        catch(\LogicException $exeption){
            return new JsonResponse(['success'=>false,'errors'=>$exeption->getMessage()], Response::HTTP_CONFLICT);
        }
    }
}