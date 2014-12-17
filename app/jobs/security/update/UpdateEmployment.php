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
        $allianceCorporations=[];
        // Get the characters for this key
        $characters = BaseApi::findKeyCharacters($keyID);
        // Check if this key has any characters associated with it
        if (!$characters)
            return;

        // Build an array with corpIDs for problematic alliances
        foreach (\SecurityKeywords::where('security_keywords.type','alnc')->get() as $allianceKeywords ){
            $memberCorporations = \DB::table('eve_alliancelist_membercorporations')
                ->join('eve_alliancelist','eve_alliancelist_membercorporations.allianceid','=','eve_alliancelist.allianceid')
                ->where('eve_alliancelist.name',$allianceKeywords->keyword)
                ->select('eve_alliancelist_membercorporations.corporationID')
                ->get();
            foreach($memberCorporations as $corporation){
                array_push($allianceCorporations,$corporation->corporationID);
            }
        }

        // loop through the characters associated with the key
        foreach ($characters as $characterID) {
            // loop through the employment history for the characterID
            foreach (\EveEveCharacterInfoEmploymentHistory::where('characterID',$characterID)->get() as $employerID ){
                // look for a match between the corpID from employment history and the corp blacklist
                $match = \SecurityKeywords::where('security_keywords.type','corp')
                    ->where('keyword','=',$employerID->corporationID)
                    ->first();

                /*
                | if a match is found in the previous query, or the employer corpID is found in the alliance to
                | corpID blacklist array then call the WriteEvent function with the associated details
                */
                if ($match || in_array($employerID->corporationID,$allianceCorporations) ){
                    $hash = md5("$characterID$employerID->corporationID");
                    $alertID = 3;
                    $description = "$employerID->corporationID";
                    BaseSecurity::WriteEvent($hash,$characterID,$alertID,$description);
                    return $hash;
                }
            }

        }
        return;
    }
}