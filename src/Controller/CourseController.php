<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Course;
use App\Enum\CourseEnum;
use App\DTO\CourseRequestDTO;
use App\DTO\CourseResponseDTO;
use JMS\Serializer\Serializer;
use OpenApi\Annotations as OA;
use App\DTO\PaymentResponseDTO;
use App\Service\PaymentService;
use App\Exception\NoMoneyExceptions;
use App\Repository\CourseRepository;
use JMS\Serializer\SerializerBuilder;
use Doctrine\Persistence\ObjectManager;
use JMS\Serializer\SerializerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

#[Route('/api/v1/courses')]
class CourseController extends AbstractController
{
    private ObjectManager $entityManager;
    private Serializer $serializer;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->serializer = SerializerBuilder::create()->build();
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
    public function courses(CourseRepository $courseRepository)
    {
        $courses = $courseRepository->findAll();
        $response = [];
        foreach ($courses as $course) {
            $response[] = CourseResponseDTO::getCourseResponseDTO($course);
        }
        return new JsonResponse($response, Response::HTTP_OK);
    }

    /**
     * @Route("/new", name="api_new_course", methods={"POST"})
     * @OA\Post(
     *     description="New course",
     *     tags={"course"},
     *     @OA\Response(
     *          response=200,
     *          description="Succeded pay info",
     *          @OA\JsonContent(
     *              schema="PayInfo",
     *              type="object",
     *              @OA\Property(property="success", type="boolean"),
     *          )
     *     ),
     *     @OA\Response(
     *          response=401,
     *          description="UNAUTHORIZED",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="error", type="string")
     *          )
     *     ),
     *     @OA\Response(
     *          response=400,
     *          description="Name cannot be empty",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="error", type="string")
     *          )
     *     ),
     *     @OA\Response(
     *          response=403,
     *          description="Course must have the price",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="error", type="string")
     *          )
     *     ),
     *     @OA\Response(
     *          response=409,
     *          description="Course with this code is alredy exist",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="error", type="string")
     *          )
     *     ),
     * )
     * @Security(name="Bearer")
     */
    #[Security(name: 'Bearer')]
    #[Route('/new', name: 'api_new_course', methods: ['POST'])]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function new(Request $request, JWTTokenManagerInterface $jwtManager, TokenStorageInterface $tokenStorageInterface, CourseRepository $courseRepository)
    {
        if (!$tokenStorageInterface->getToken()) {
            return new JsonResponse(['errors' => 'Нет токена'], Response::HTTP_UNAUTHORIZED);
        }
        if (!$this->getUser() || !in_array('ROLE_SUPER_ADMIN', $this->getUser()->getRoles(), true)) {
            return new JsonResponse(['errors' => 'Пользователь не авторизован'], Response::HTTP_UNAUTHORIZED);
        }
        $course = $this->serializer->deserialize($request->getContent(), CourseRequestDTO::class, 'json');
        if ($course->getName() == null) {
            return new JsonResponse(['errors' => 'Название не может быть пустым'], Response::HTTP_BAD_REQUEST);
        }
        if ($course->getType() == CourseEnum::FREE) {
            $course->setPrice(null);
        } else {
            if ($course->getPrice() == null) {
                return new JsonResponse(['errors' => 'Курс платный, укажите цену'], Response::HTTP_FORBIDDEN);
            }
        }
        if ($courseRepository->count(['code' => $course->getCode()]) > 0) {
            return new JsonResponse(['errors' => 'Курс с таким кодом уже существует'], Response::HTTP_CONFLICT);
        }
        $courseRepository->save(Course::fromDTO($course), true);
        return new JsonResponse(['success' => true], Response::HTTP_CREATED);
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
    public function course(string $code, CourseRepository $courseRepository)
    {
        $course = $courseRepository->findOneBy(['code' => $code]);
        if (!$course) {
            return new JsonResponse(['errors' => "Курс $code не найден"], Response::HTTP_NOT_FOUND);
        }
        $course = CourseResponseDTO::getCourseResponseDTO($course);
        return new JsonResponse($course, Response::HTTP_OK);
    }

    /**
     * @Route("/{code}/edit", name="api_edit_course", methods={"POST"})
     * @OA\Post(
     *     description="New course",
     *     tags={"course"},
     *     @OA\Response(
     *          response=200,
     *          description="Succeded pay info",
     *          @OA\JsonContent(
     *              schema="PayInfo",
     *              type="object",
     *              @OA\Property(property="success", type="boolean"),
     *          )
     *     ),
     *     @OA\Response(
     *          response=401,
     *          description="UNAUTHORIZED",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="error", type="string")
     *          )
     *     ),
     *     @OA\Response(
     *          response=404,
     *          description="Course with that code not found",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="error", type="string")
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
    #[Security(name: 'Bearer')]
    #[Route('/{code}/edit', name: 'api_edit_course', methods: ['POST'])]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function edit(string $code, Request $request, JWTTokenManagerInterface $jwtManager, TokenStorageInterface $tokenStorageInterface, CourseRepository $courseRepository)
    {
        if (!$tokenStorageInterface->getToken() || !in_array('ROLE_SUPER_ADMIN', $this->getUser()->getRoles(), true)) {
            return new JsonResponse(['errors' => 'Нет токена'], Response::HTTP_UNAUTHORIZED);
        }
        if (!$this->getUser()) {
            return new JsonResponse(['errors' => 'Пользователь не авторизован'], Response::HTTP_UNAUTHORIZED);
        }
        $course = $this->serializer->deserialize($request->getContent(), CourseRequestDTO::class, 'json');
        if ($course->getName() == null) {
            return new JsonResponse(['errors' => 'Название не может быть пустым'], Response::HTTP_BAD_REQUEST);
        }
        if ($course->getType() == CourseEnum::FREE) {
            $course->setPrice(null);
        } else {
            if ($course->getPrice() == null) {
                return new JsonResponse(['errors' => 'Курс платный, укажите цену'], Response::HTTP_FORBIDDEN);
            }
        }
        $edited_course = $courseRepository->findOneBy(['code' => $code]);
        if ($edited_course == null) {
            return new JsonResponse(['errors' => 'Курс с таким кодом не существует'], Response::HTTP_CONFLICT);
        }
        $courseRepository->save($edited_course->fromDTOedit($course), true);
        return new JsonResponse(['success' => true], Response::HTTP_OK);
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
     *          response=401,
     *          description="UNAUTHORIZED",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="error", type="string")
     *          )
     *     ),
     *     @OA\Response(
     *          response=404,
     *          description="Course with that code not found",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="error", type="string")
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
    #[Security(name: 'Bearer')]
    #[Route('/{code}/pay', name: 'api_pay_for_courses', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function payForCourses(string $code, PaymentService $paymentService, CourseRepository $courseRepository)
    {
        $course = $courseRepository->findOneBy(['code' => htmlspecialchars($code)]);
        if (!$course) {
            return new JsonResponse(['success' => false, 'errors' => "Курс $code не найден"], Response::HTTP_NOT_FOUND);
        }
        if ($course->getType() === CourseEnum::FREE) {
            $response = PaymentResponseDTO::getPaymentResponseDTO(true, $course->getType(), null);
            return new JsonResponse($response, Response::HTTP_OK);
        }
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['success' => false, 'errors' => 'Пользователь не авторизован'], Response::HTTP_UNAUTHORIZED);
        }
        try {
            $transaction = $paymentService->payment($user, $course);
            $expires = $transaction->getExpires() ?: null;
            $response = PaymentResponseDTO::getPaymentResponseDTO(true, $course->getType(), $expires);
            return new JsonResponse($response, Response::HTTP_OK);
        } catch (NoMoneyExceptions $exeption) {
            return new JsonResponse(['success' => false, 'errors' => $exeption->getMessage()], Response::HTTP_NOT_ACCEPTABLE);
        } catch (\LogicException $exeption) {
            return new JsonResponse(['success' => false, 'errors' => $exeption->getMessage()], Response::HTTP_CONFLICT);
        }
    }
}