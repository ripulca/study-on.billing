<?php

namespace App\DTO;

use App\Enum\CourseEnum;
use JMS\Serializer\Annotation as Serializer;

class PaymentResponseDTO
{
    #[Serializer\Type("bool")]
    public string $success;

    #[Serializer\Type("string")]
    public string $type;

    #[Serializer\Type("DateTime"), Serializer\SkipWhenEmpty]
    public ?\DateTime $expires;

    public static function getPaymentResponseDTO($status, $type, $expires)
    {
        return (new self)
            ->setStatus($status)
            ->setType(CourseEnum::NAMES[$type])
            ->setExpires($expires);
    }

    public function setStatus(string $status): self
    {
        $this->success = $status;

        return $this;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function setExpires($expires): self
    {
        $this->expires = $expires;

        return $this;
    }
}