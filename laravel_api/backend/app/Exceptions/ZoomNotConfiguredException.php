<?php

namespace App\Exceptions;

use RuntimeException;

class ZoomNotConfiguredException extends RuntimeException
{
    public function __construct(string $message = 'Add a Zoom account under Settings → Zoom Accounts')
    {
        parent::__construct($message);
    }
}
