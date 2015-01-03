<?php
/*
The MIT License (MIT)

Copyright (c) 2014 eve-seat

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
*/

namespace Seat\Commands\Scheduled;

use Indatus\Dispatcher\Scheduling\ScheduledCommand;
use Indatus\Dispatcher\Scheduling\Schedulable;
use Indatus\Dispatcher\Drivers\Cron\Scheduler;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

use Seat\EveApi;
use Seat\EveApi\Account;

class SecurityUpdater extends ScheduledCommand {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'seatscheduled:update-all-security-events';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Schedules Update for all Security Checks';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * When a command should run
     *
     * @param Scheduler $scheduler
     * @return \Indatus\Dispatcher\Schedulable
     */
    public function schedule(Schedulable $scheduler)
    {
        return $scheduler->hourly();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {

        \Log::info('Started command ' . $this->name, array('src' => __CLASS__));

        // Get the keys, and process them
        foreach (\SeatKey::where('isOk', '=', 1)->get() as $key) {

            $access = EveApi\BaseApi::determineAccess($key->keyID);
            if (!isset($access['type'])) {
                //TODO: Log this key's problems and disable it
                continue;
            }

            // Only process Character keys here
            if ($access['type'] == 'Character') {
                // Add the Security checker job to the queue
                \App\Services\Queue\QueueHelper::addToQueue('\Seat\EveQueues\Security\CharacterUpdate', $key->keyID, $key->vCode, 'Security', 'Character');
            }
        }
        // Check mail once per hour, not per char
        \App\Services\Queue\QueueHelper::addToQueue('\Seat\EveQueues\Security\MailUpdate', '0', NULL, 'Mail', 'Security');
    }
}
