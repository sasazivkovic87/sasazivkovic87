<?php

namespace App\Messenger\MessagePublish;

class EcsdMessage
{
    public function __construct($invoiceId)
    {
        $this->invoiceId = $invoiceId;
    }

    public function getInvoiceId()
    {
        return $this->invoiceId;
    }

    public function setInvoiceId($invoiceId): SendToEcsdMessage
    {
        $this->invoiceId = $invoiceId;
        return $this;
    }
}
