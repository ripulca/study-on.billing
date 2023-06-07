<?php

namespace App\Command;

use App\Entity\Course;
use App\Repository\CourseRepository;
use App\Service\TwigService;
use App\Service\ArrayService;
use Symfony\Component\Mime\Email;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

#[AsCommand(name: 'payment:ending:notification')]
class RentEndingNotifCommand extends Command
{
    private TwigService $twig;
    private MailerInterface $mailer;
    private CourseRepository $courseRepository;

    public function __construct(TwigService $twig, MailerInterface $mailer, CourseRepository $courseRepository)
    {
        $this->twig = $twig;
        $this->courseRepository = $courseRepository;
        $this->mailer = $mailer;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $courses = $this->courseRepository->findExpired('P1D');
        $coursesByEmail = ArrayService::arrayByKey($courses, 'email');

        foreach ($coursesByEmail as $email => $userCourses) {
            $html = $this->twig->render(
                'email/rent_ending_notif_email.html.twig',
                ['courses' => $userCourses]
            );

            $email = (new Email())
                ->to($email)
                ->subject('Окончание аренды курсов')
                ->html($html);

            try {
                $this->mailer->send($email);
            } catch (TransportExceptionInterface $e) {
                return Command::FAILURE;
            }
        }

        return Command::SUCCESS;
    }
}