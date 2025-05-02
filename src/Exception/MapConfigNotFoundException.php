<?php

declare(strict_types=1);


namespace App\Exception;

class MapConfigNotFoundException extends \Exception
{
    public function __construct(string $mapConfigName)
    {
        parent::__construct(sprintf('Map config "%s" not found.', $mapConfigName));
    }
}
