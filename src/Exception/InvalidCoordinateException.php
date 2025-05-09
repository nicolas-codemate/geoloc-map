<?php

declare(strict_types=1);


namespace App\Exception;

class InvalidCoordinateException extends \Exception
{
    public function __construct(string $message = '')
    {
        parent::__construct(sprintf('Invalid coordinate: %s', $message));
    }
}
