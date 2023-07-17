<?php

namespace YaleREDCap\SecurityAccessGroups;

class AjaxHandler
{
    private SecurityAccessGroups $module;

    public function __construct(SecurityAccessGroups $module)
    {
        $this->module = $module;
    }
}