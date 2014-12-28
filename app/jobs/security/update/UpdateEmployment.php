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

class UpdateEmployment extends BaseSecurity {

    public static function Update($keyID) {
        $alliance_corporations=[];
        // Get the characters for this key
        $characters = BaseApi::findKeyCharacters($keyID);
        // Check if this key has any characters associated with it
        if (!$characters)
            return;

        // Build an array with corpIDs for problematic alliances
        foreach (\SecurityKeywords::where('security_keywords.type','alnc')->get() as $alliance_keywords ){
            $member_corporations = \DB::table('eve_alliancelist_membercorporations')
                ->join('eve_alliancelist','eve_alliancelist_membercorporations.allianceid','=','eve_alliancelist.allianceid')
                ->where('eve_alliancelist.name',$alliance_keywords->keyword)
                ->select('eve_alliancelist_membercorporations.corporationID')
                ->get();
            foreach($member_corporations as $corporation){
                array_push($alliance_corporations,$corporation->corporationID);
            }
        }

        // loop through the characters associated with the key
        foreach ($characters as $character_id) {
            // loop through the employment history for the characterID
            foreach (\EveEveCharacterInfoEmploymentHistory::where('characterID',$character_id)->get() as $employer_id ){
                // look for a match between the corpID from employment history and the corp blacklist
                $match = \SecurityKeywords::where('security_keywords.type','corp')
                    ->where('keyword','=',$employer_id->corporationID)
                    ->first();

                /*
                | if a match is found in the previous query, or the employer corpID is found in the alliance to
                | corpID blacklist array then call the WriteEvent function with the associated details
                */
                if ($match || in_array($employer_id->corporationID,$alliance_corporations) ){
                    $hash = md5("$character_id$employer_id->corporationID");
                    $alert_id = 3;
                    $item_id = "$employer_id->corporationID";
                    $details = "$employer_id->corporationID";
                    BaseSecurity::WriteEvent($hash,$character_id,$alert_id,$item_id,$details);
                    return $hash;
                }
            }

        }
        return;
    }
}