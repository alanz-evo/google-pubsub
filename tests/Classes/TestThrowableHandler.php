<?php

namespace AlanzEvo\GooglePubsub\Tests\Classes;

use AlanzEvo\GooglePubsub\Abstracts\AbstractThrowableHandler;

class TestThrowableHandler extends AbstractThrowableHandler
{
    public function handle()
    {
        return $this->throwable->getMessage();
    }
}
