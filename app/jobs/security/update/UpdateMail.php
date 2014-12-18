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

namespace Seat\Jobs\Security\Update;

use Seat\EveApi\BaseApi;
use Seat\Jobs\Security\BaseSecurity;

class UpdateMail extends BaseSecurity {

    public static function Update() {

        // get all the mail keywords from the security_keywords table
        $keywords = \SecurityKeywords::where('type','mail')->get();
        //loop through each keyword
        foreach ($keywords as $mail_keyword) {
            // check the message bodies for finding any that have the banned keyword
            $match=\DB::table('character_mailbodies')
                ->join('character_mailmessages','character_mailmessages.messageID','=','character_mailbodies.messageID')
                ->where('character_mailbodies.body','LIKE','%'. $mail_keyword->keyword .'%')
                ->select('character_mailbodies.messageID', 'character_mailmessages.characterID')
                ->get();
            // create an entry in the security_keywords table if a keyword is found
            foreach ($match as $mailmatch){
                $hash = md5("$mailmatch->characterID$mailmatch->messageID");
                $alert_id = 5;
                $description = "$mailmatch->messageID";
                BaseSecurity::WriteEvent($hash,$mailmatch->characterID,$alert_id,$description);
            }
        }
    }
}