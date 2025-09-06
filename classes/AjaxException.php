<?php

namespace YaleREDCap\SecurityAccessGroups;

class AjaxException extends \Exception
{
    public function __construct(array $options)
    {
        http_response_code($options['code'] ?? 0);
        parent::__construct($options['message'] ?? '', $options['code'] ?? 0, $options['previous'] ?? null);
    }
}