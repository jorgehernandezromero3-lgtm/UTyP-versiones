<?php
declare(strict_types=1);

namespace Utypcb\Asistente\Support;

use RuntimeException;

final class HttpException extends RuntimeException
{
    private int $status;
    private string $publicMessage;

    public function __construct(int $status, string $publicMessage)
    {
        parent::__construct($publicMessage);
        $this->status = $status;
        $this->publicMessage = $publicMessage;
    }

    public function status(): int
    {
        return $this->status;
    }

    public function publicMessage(): string
    {
        return $this->publicMessage;
    }
}
