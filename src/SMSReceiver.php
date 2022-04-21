<?php

namespace CozyFex\DirectSend;

class SMSReceiver
{
    public string $name = '';
    public string $mobile = '';

    public function __construct(string $name, string $mobile)
    {
        $this->name   = $name;
        $this->mobile = $mobile;
    }
}