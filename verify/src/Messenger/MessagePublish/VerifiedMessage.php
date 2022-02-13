<?php

namespace App\Messenger\MessagePublish;

class VerifiedMessage
{
    public function __construct($ecsdResponseJson)
    {
        $this->ecsdResponseJson = $ecsdResponseJson;
    }

    public function getEcsdResponseJson()
    {
        return $this->ecsdResponseJson;
    }

    public function setEcsdResponseJson($ecsdResponseJson): VerifiedMessage
    {
        $this->ecsdResponseJson = $ecsdResponseJson;
        return $this;
    }
}
