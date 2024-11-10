<?php

declare(strict_types=1);

namespace App\Constants;

class ErrorCode
{
    public const USER_NOT_FOUND = 1001;
    public const INSUFFICIENT_BALANCE = 1002;
    public const UNAUTHORIZED_TRANSFER = 1003;
    public const INVALID_TRANSACTION = 1004;
    public const EXTERNAL_SERVICE_ERROR = 1005;
}