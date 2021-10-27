<?php

namespace AlanzEvo\GooglePubsub\Tests\Classes;

use AlanzEvo\GooglePubsub\Abstracts\AbstractHandler;

class TestHandler extends AbstractHandler
{
    public function handle()
    {
        return $this->message->data();
    }
}
