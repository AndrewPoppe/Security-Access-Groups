<?php

namespace YaleREDCap\SecurityAccessGroups;

class SAGException extends \Exception
{
    public function __construct(string|null $message, int|null $code, \Throwable|null $previous)
    {
        http_response_code($code ?? 0);
        parent::__construct($message ?? '', $code ?? 0, $previous);
    }
}