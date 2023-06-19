<?php

namespace App\DTO;

use App\Entity\Course;
use App\Enum\CourseEnum;
use JMS\Serializer\Annotation as Serializer;

class CourseRequestDTO
{
    #[Serializer\Type("string")]
    public string $name;

    #[Serializer\Type("string")]
    public string $code;

    #[Serializer\Type("float")]
    public ?float $price = null;

    #[Serializer\Type("int")]
    public int $type;

    public static function getCourseRequestDTO($name, $code, $price, $type){
        return (new self)
            ->setCode($code)
            ->setName($name)
            ->setPrice($price)
            ->setType($type);
    }

    public function setName($name){
        $this->name=$name;
        return $this;
    }

    public function getName(){
        return $this->name;
    }

    public function setCode($code){
        $this->code=$code;
        return $this;
    }

    public function getCode(){
        return $this->code;
    }

    public function setType($type){
        $this->type=$type;
        return $this;
    }

    public function getType(){
        return $this->type;
    }

    public function setPrice($price){
        $this->price=$price;
        return $this;
    }

    public function getPrice(){
        return $this->price;
    }
}