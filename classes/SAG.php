<?php

namespace YaleREDCap\SecurityAccessGroups;

class SAG
{

    private SecurityAccessGroups $module;
    private string $sagId;
    private string $sagName;

    private array $sagData;

    public function __construct(SecurityAccessGroups $module, $sagId = null, $sagName = null)
    {
        $this->module  = $module;
        $this->sagId   = $sagId;
        $this->sagName = $sagName ?? $this->getSagNameFromSagId($sagId);
    }
}