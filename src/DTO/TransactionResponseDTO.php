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
    public ?string $code;

    #[Serializer\Type("string")]
    public ?string $type;

    #[Serializer\Type("float")]
    public ?float $amount;

    #[Serializer\Type("DateTime")]
    public ?\DateTime $created;

    #[Serializer\Type("DateTime"), Serializer\SkipWhenEmpty]
    public ?\DateTime $expires;

    public static function fromTransaction(Transaction $transaction)
    {
        return (new self)
            ->setId($transaction->getId())
            ->setCode($transaction->getCourse()?->getCode())
            ->setType($transaction->getType())
            ->setAmount($transaction->getAmount())
            ->setCreated($transaction->getCreated())
            ->setExpires($transaction->getExpires());
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function setCode($code): self
    {
        $this->code = $code;

        return $this;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function setAmount(float $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    public function setCreated($created): self
    {
        $this->created = $created;

        return $this;
    }

    public function setExpires($expires): self
    {
        $this->expires = $expires;

        return $this;
    }
}