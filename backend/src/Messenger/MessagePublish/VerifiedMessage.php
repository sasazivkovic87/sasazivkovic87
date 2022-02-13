<?php

namespace App\Messenger\MessagePublish;

class VerifiedMessage
{
    public function __construct($ecsdResponseJson)
    {
        $this->ecsdResponseJson = $ecsdResponseJson;
    }

    public function getecsdResponseJson()
    {
        return $this->ecsdResponseJson;
    }

    public function setecsdResponseJson($ecsdResponseJson): VerifiedMessage
    {
        $this->ecsdResponseJson = $ecsdResponseJson;
        return $this;
    }
}
