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

class UpdateWallet extends BaseSecurity {
    public static function Update($keyID)
    {
        // Get the characters for this key
        $characters = BaseApi::findKeyCharacters($keyID);
        // Check if this key has any characters associated with it
        if (!$characters)
            return;
        // loop through the characters associated with the key
        foreach ($characters as $character_id) {

            // loop through the wallet journal for the char looking for flagable issues
            foreach (\EveCharacterWalletJournal::where('characterID',$character_id)->where('amount','>','500000000')
            ->where('ownerName1','<>','Secure Commerce Commission')
            ->where('ownerName2','<>','Secure Commerce Commission')
            ->where(function($query)
            {
                $query->where('refTypeID','10')
                      ->orWhereBetween('refTypeID',array(63,84));
            })
            ->get() as $wallet_journal){

                if ( BaseSecurity::characterPeopleGroup($wallet_journal->ownerID1) <> BaseSecurity::characterPeopleGroup($wallet_journal->ownerID2)) {
                    $hash = md5("$character_id$wallet_journal->refID");
                    $alert_id = 7;
                    $item_id = "$wallet_journal->refID";
                    $details = "Between $wallet_journal->ownerName1 and $wallet_journal->ownerName2";
                    BaseSecurity::WriteEvent($hash,$character_id,$alert_id,$item_id,$details);
                }
            }
        }
        return;
    }
}