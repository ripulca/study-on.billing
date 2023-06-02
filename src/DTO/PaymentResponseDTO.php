<?php

namespace App\DTO;

use DateTime;
use App\Enum\CourseEnum;
use JMS\Serializer\Annotation as Serializer;

class PaymentResponseDTO
{
    #[Serializer\Type("bool")]
    public string $success;

    #[Serializer\Type("string")]
    public string $type;

    #[Serializer\Type("DateTime"), Serializer\SkipWhenEmpty]
    public ?DateTime $expires;

    public function __construct($status, $type, $expires)
    {
        $this->success=$status;
        $this->type=CourseEnum::NAMES[$type];
        $this->expires=$expires;
    }
}