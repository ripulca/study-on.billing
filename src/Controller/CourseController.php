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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/api/v1')]
class CourseController extends AbstractController
{
    private ObjectManager $entityManager;
    private CourseRepository $courseRepository;

    public function __construct(EntityManagerInterface $entityManager){
        $this->entityManager=$entityManager;
    }

    #[Route('/courses', name: 'api_courses', methods: ['GET'])]
    public function courses(){
        $courses=$this->courseRepository->findAll();
        $response=[];
        foreach ($courses as $course) {
            $response[] = new CourseResponseDTO($course);
        }
        return new JsonResponse($response, Response::HTTP_OK);
    }

    #[Route('/courses/{code}', name: 'api_courses', methods: ['GET'])]
    public function course(string $code){
        $course=$this->courseRepository->findOneBy(['code' => $code]);
        if(!$course){
            return new JsonResponse(['errors' =>"Курс $code не найден"], Response::HTTP_NOT_FOUND);
        }
        $course=new CourseResponseDTO($course);
        return new JsonResponse($course, Response::HTTP_OK);
    }

    #[Route('/courses/{code}/pay', name: 'api_pay_for_courses', methods: ['POST'])]
    public function payForCourses(string $code, PaymentService $paymentService){
        $course=$this->courseRepository->findOneBy(['code' => $code]);
        if(!$course){
            return new JsonResponse(['errors' =>"Курс $code не найден"], Response::HTTP_NOT_FOUND);
        }
        $response_body=[
                'success'=>true,
                'course_type'=>CourseEnum::NAMES[$course->getType()],
        ];
        if($course->getType()===CourseEnum::FREE){
            return new JsonResponse($response_body, Response::HTTP_OK);
        }
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['errors' => 'Пользователь не авторизован'], Response::HTTP_UNAUTHORIZED);
        }
        try{
            $transaction = $paymentService->payment($user, $course);
            $expires = $transaction->getExpires()?: null;
            $response= new PaymentResponseDTO(true, $course->getType(), $expires);
            return new JsonResponse($response, Response::HTTP_OK);
        }
        catch(\Exception $exeption){
            return new JsonResponse(['errors'=>$exeption->getMessage()], Response::HTTP_NOT_ACCEPTABLE);
        }
    }
}