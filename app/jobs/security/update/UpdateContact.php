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

class UpdateContact extends BaseSecurity {

    public static function Update($keyID) {

        // Get the characters for this key
        $characters = BaseApi::findKeyCharacters($keyID);
        // Check if this key has any characters associated with it
        if (!$characters)
            return;

        // loop through the characters associated with the key
        foreach ($characters as $characterID) {

            // loop through all the contacts that are not corps/alliances
            foreach (\EveCharacterContactList::where('characterID', '=', $characterID)->where('contactID','>',5000000)->get() as $contactID ){

                //check is the character's contact is in the contact blacklist
                $match = \SecurityKeywords::where('security_keywords.type','=','cnct')
                    ->where('keyword','=',$contactID->contactName)
                    ->first();

                // if the contact is in the blacklist add it to the security_events table
                if ($match){
                    $hash = md5("$characterID$contactID->contactName");
                    $alertID = 4;
                    $description = "$contactID->contactName";
                    BaseSecurity::WriteEvent($hash,$characterID,$alertID,$description);
                    return $hash;
                }
            }
        }
        return;
    }
}