<?php

namespace AlanzEvo\GooglePubsub\Commands;

use AlanzEvo\GooglePubsub\Supervisor;
use Illuminate\Console\Command;

class PubsubSupervisor extends Command
{
    /**
     * @var string
     */
    protected $signature = 'pubsub-supervisor '
                            . '{--sleep= : Sleep N ms after a message, default: 1000 ms} '
                            . '{--once : Break the process after handling messages.} '
                            . '{--ackBeforeHandling : Ack to google pub/sub before handling}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'The supervisor for AlanzEvo\\GooglePubsub.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $sleep = $this->option('sleep') ?? 1000;
        $once = $this->option('once') ?? false;
        $ackBeforeHandling = $this->option('ackBeforeHandling') ?? false;
        if ($sleep < 0) {
            $sleep = 1000;
        }

        app(Supervisor::class)
            ->setSleepMsPerMessage($sleep)
            ->setAckBeforeHandling($ackBeforeHandling)
            ->setOnce($once)
            ->monitor();
    }
}
