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
    public float $price;

    #[Serializer\Type("string")]
    public string $type;

    public function __construct(Course $course)
    {
        if ($course) {
            $this->code = $course->getCode();
            $this->type = $course->getType();
            if($this->type!=CourseEnum::FREE){
                $this->price = $course->getPrice();
            }
        }
    }
}