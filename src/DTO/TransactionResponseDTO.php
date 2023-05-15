<?php

namespace App\DTO;

use App\Entity\Transaction;
use DateTimeImmutable;
use JMS\Serializer\Annotation as Serializer;

class TransactionResponseDTO
{
    #[Serializer\Type("int")]
    public ?int $id;

    #[Serializer\Type("string"), Serializer\SkipWhenEmpty]
    public ?string $course_code;

    #[Serializer\Type("string")]
    public ?string $type;

    #[Serializer\Type("float")]
    public ?float $amount;

    #[Serializer\Type("DateTimeImmutable")]
    public ?DateTimeImmutable $created;

    #[Serializer\Type("DateTimeImmutable"), Serializer\SkipWhenEmpty]
    public ?DateTimeImmutable $expires;

    public function __construct(Transaction $transaction)
    {
        $this->id = $transaction->getId();
        $this->course_code = $transaction->getCourse()?->getCode();
        $this->type = $transaction->getType();
        $this->amount = $transaction->getAmount();
        $this->created = $transaction->getCreated();
        $this->expires = $transaction->getExpires();
    }
}