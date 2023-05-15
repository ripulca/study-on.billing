<?php

namespace App\DTO;

use App\Enum\CourseEnum;
use JMS\Serializer\Annotation as Serializer;

class PaymentResponseDTO
{
    #[Serializer\Type("bool")]
    public string $success;

    #[Serializer\Type("string")]
    public float $course_type;

    #[Serializer\Type("DateTimeImmutable"), Serializer\SkipWhenEmpty]
    public string $expires_at;

    public function __construct($status, $type, $expires)
    {
        $this->success=$status;
        $this->course_type=CourseEnum::NAMES[$type];
        $this->expires_at=$expires;
    }
}