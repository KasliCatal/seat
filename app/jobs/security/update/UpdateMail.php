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
use Pheal\Pheal;

class UpdateMail extends BaseSecurity {

    public static function Update() {
        $keywords=[];
        $pheal = new Pheal();

        foreach (\SecurityKeywords::where('security_keywords.type','alnc')->get() as $alliance_keywords ){
            $member_corporations = \DB::table('eve_alliancelist_membercorporations')
                ->join('eve_alliancelist','eve_alliancelist_membercorporations.allianceid','=','eve_alliancelist.allianceid')
                ->where('eve_alliancelist.name',$alliance_keywords->keyword)
                ->select('eve_alliancelist_membercorporations.corporationID')
                ->get();
            foreach($member_corporations as $corporation){
                if (!\Cache::has('nameid_' . $corporation->corporationID)) {
                    try {
                        $names = $pheal->eveScope->CharacterName(array('ids' => $corporation->corporationID));
                    } catch (Exception $e) {
                        throw $e;
                    }
                    foreach ($names->characters as $lookup_result) {
                        \Cache::forever('nameid_' . $lookup_result->characterID, $lookup_result->name);
                        array_push($keywords,$lookup_result->name);
                    }
                } else {
                    array_push($keywords,\Cache::get('nameid_' . $corporation->corporationID));
                }
            }
        }


        foreach (\SecurityKeywords::where('security_keywords.type','corp')->get() as $corp_keywords){
            if (!\Cache::has('nameid_' . $corp_keywords->keyword)) {
                try {
                    $names = $pheal->eveScope->CharacterName(array('ids' => $corp_keywords->keyword));
                } catch (Exception $e) {
                    throw $e;
                }
                foreach ($names->characters as $lookup_result) {
                    \Cache::forever('nameid_' . $lookup_result->characterID, $lookup_result->name);
                    array_push($keywords,$lookup_result->name);
                }
            } else {
                array_push($keywords,\Cache::get('nameid_' . $corp_keywords->keyword));
            }
        }

        foreach ($keywords as $mail_keyword) {
            // check the message bodies for finding any that have the banned keyword
            $match=\DB::table('character_mailbodies')
                ->join('character_mailmessages','character_mailmessages.messageID','=','character_mailbodies.messageID')
                ->where('character_mailbodies.body','LIKE','%'. $mail_keyword .'%')
                ->whereNull('character_mailmessages.toCorpOrAllianceID')
                ->whereNull('character_mailmessages.toListID')
                ->select('character_mailbodies.messageID', 'character_mailmessages.characterID', 'character_mailmessages.toCharacterIDs')
                ->get();
            // create an entry in the security_keywords table if a keyword is found
            foreach ($match as $mailmatch){

                //exclude messages where the sender and recipient are in the same people group
                $to_character_ids = preg_split("/,/", $mailmatch->toCharacterIDs);
                foreach ($to_character_ids as $to_character_id) {
                    if ( BaseSecurity::characterPeopleGroup($to_character_id) <> BaseSecurity::characterPeopleGroup($mailmatch->characterID) && BaseSecurity::characterPeopleGroup($mailmatch->characterID) <> 95259724) {
                        $hash = md5("$mailmatch->characterID$mailmatch->messageID");
                        $alert_id = 5;
                        $item_id = "$mailmatch->messageID";
                        $details = "$mail_keyword";
                        BaseSecurity::WriteEvent($hash,$mailmatch->characterID,$alert_id,$item_id,$details);
                    }
                }
            }
        }

        //check mail of specific length

        $length_match=\DB::table('character_mailbodies')
                ->join('character_mailmessages','character_mailmessages.messageID','=','character_mailbodies.messageID')
                ->where(DB::raw('CHAR_LENGTH(character_mailbodies.body) =< 540'))
                ->whereNull('character_mailmessages.toCorpOrAllianceID')
                ->whereNull('character_mailmessages.toListID')
                ->select('character_mailbodies.messageID', 'character_mailmessages.characterID', 'character_mailmessages.toCharacterIDs')
                ->get();
        foreach ($match as $mailmatch){
                //exclude messages where the sender and recipient are in the same people group
                $to_character_ids = preg_split("/,/", $mailmatch->toCharacterIDs);
                foreach ($to_character_ids as $to_character_id) {
                    if ( BaseSecurity::characterPeopleGroup($to_character_id) <> BaseSecurity::characterPeopleGroup($mailmatch->characterID) && BaseSecurity::characterPeopleGroup($mailmatch->characterID) <> 95259724) {
                        $hash = md5("$mailmatch->characterID$mailmatch->messageID");
                        $alert_id = 5;
                        $item_id = "$mailmatch->messageID";
                        $details = "Short Message";
                        BaseSecurity::WriteEvent($hash,$mailmatch->characterID,$alert_id,$item_id,$details);
                    }
                }
        }
        return;
    }
}
