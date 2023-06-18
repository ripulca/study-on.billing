<?php

namespace App\DTO;

use App\Entity\Course;
use App\Enum\CourseEnum;
use JMS\Serializer\Annotation as Serializer;

class CourseResponseDTO
{
    #[Serializer\Type("string")]
    public string $code;

    #[Serializer\Type("float")]
    public ?float $price=0.0;

    #[Serializer\Type("string")]
    public string $type;

    public static function getCourseResponseDTO(Course $course)
    {
        return (new self)
            ->setCode($course->getCode())
            ->setPrice($course->getPrice())
            ->setType(CourseEnum::NAMES[$course->getType()]);
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function setPrice($price): self
    {
        $this->price = $price;

        return $this;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }
}