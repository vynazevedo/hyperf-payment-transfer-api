<?php

namespace App\Exception;

use Exception;

class InsufficientBalanceException extends Exception
{
    protected $message = 'Saldo insuficiente para realizar a transferência';
}
