<?php

namespace App\Service;
use App\Enum\CourseEnum;

class ArrayService
{
    public static function arrayByKey($array, $key): array
    {
        $result = [];
        foreach ($array as $el) {
            if (isset($result[$el[$key]])) {
                $result[$el[$key]][] = $el;
            } else {
                $result[$el[$key]] = [$el];
            }
        }
        return $result;
    }
    public static function arrayByKeyWithTypeMap($array, $key): array
    {
        $result = [];
        foreach ($array as $el) {
            $el['type'] = CourseEnum::COURSE_TYPE_NAMES[$el['type']];
            if (isset($result[$el[$key]])) {
                $result[$el[$key]][] = $el;
            } else {
                $result[$el[$key]] = [$el];
            }
        }
        return $result;
    }
}