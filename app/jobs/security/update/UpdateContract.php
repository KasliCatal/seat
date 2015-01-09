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

class UpdateContract extends BaseSecurity {

    public static function Update($keyID) {

        // Get the characters for this key
        $characters = BaseApi::findKeyCharacters($keyID);
        // Check if this key has any characters associated with it
        if (!$characters)
            return;

        // loop through the characters associated with the key
        foreach ($characters as $character_id) {

            // Get any contracts for this character that are plex
            foreach (\EveCharacterContractsItems::where('characterID', '=', $character_id)->where('typeID',29668)->get() as $plex_contract ){

                // pull all the details about the contract
                $match = \EveCharacterContractsItems::where('contractID',$plex_contract->contractID)
                    ->first();

                // only process if it is an item exchange and not in the same people group
                if (BaseSecurity::characterPeopleGroup($match->issuerID) <> BaseSecurity::characterPeopleGroup($match->acceptorID) && $match->type == 'ItemExchange'){
                    $hash = md5("$character_id$plex_contract->contractID");
                    $alert_id = 10;
                    $item_id = "$plex_contract->contractID";
                    $details = "Plex Contract on $match->dateCompleted";
                    BaseSecurity::WriteEvent($hash,$character_id,$alert_id,$item_id,$details);
                    return $hash;
                }
            }
        }
        return;
    }
}