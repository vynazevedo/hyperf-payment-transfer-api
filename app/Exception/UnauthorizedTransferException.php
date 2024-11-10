<?php

declare(strict_types=1);

namespace App\Exception;

use Exception;

class UnauthorizedTransferException extends Exception
{
    public function __construct(string $message = 'Transferência não autorizada')
    {
        parent::__construct($message);
    }
}
