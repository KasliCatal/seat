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

namespace Seat\Jobs\Security;

class BaseSecurity {

    /*
    |--------------------------------------------------------------------------
    | WriteEvent()
    |--------------------------------------------------------------------------
    |
    | Verifies if the event already exists in the security_events table
    | if it does not exist creates a new entry.
    |
    */
    public static function WriteEvent($hash,$characterID,$alert_id,$description){
    	$checkhash = \SecurityEvents::where('hash','=',$hash)->first();

    	if(!$checkhash){
    		$date = new \DateTime;
            $event = new \SecurityEvents;

            $event->hash = $hash;
	        $event->result = 0;
	        $event->characterID = $characterID;
	        $event->alertID = $alert_id;
	        $event->description = $description;
	        $event->created_at = $date;
	        $event->updated_at = $date;
            $event->save();
    		return $event;
    	}
    }
}