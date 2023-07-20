<?php

namespace YaleREDCap\SecurityAccessGroups;

class AjaxException extends \Exception
{
    public function __construct($message = "", $code = 0, \Throwable $previous = null)
    {
        http_response_code($code);
        parent::__construct($message, $code, $previous);
    }
}