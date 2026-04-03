<?php

namespace App\Services;

class ChurchContext
{
    protected ?int $churchId = null;

    public function setId(?int $id): void
    {
        $this->churchId = $id;
    }

    public function getId(): ?int
    {
        return $this->churchId;
    }

    public function has(): bool
    {
        return $this->churchId !== null;
    }
}
