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
                        $name = $pheal->eveScope->CharacterName(array('ids' => $corporation->corporationID));
                    } catch (Exception $e) {
                        throw $e;
                    }
                    echo "adding $name[0]->name to cache\n";
                    \Cache::forever('nameid_' . $corporation->corporationID, $name->name);
                    array_push($keywords,$name->name);
                } else {
                    echo "pulling $corporation->corporationID from cache\n";
                    array_push($keywords,\Cache::get('nameid_' . $corporation->corporationID));
                }
            }
        }


        foreach (\SecurityKeywords::where('security_keywords.type','corp')->get() as $corp_keywords){
            if (!\Cache::has('nameid_' . $corp_keywords->keyword)) {
                try {
                    $name = $pheal->eveScope->CharacterName(array('ids' => $corp_keywords->keyword));
                } catch (Exception $e) {
                    throw $e;
                }
                echo "adding $name[0]->name to cache\n";
                \Cache::forever('nameid_' . $corp_keywords->keyword, $name->name);
                array_push($keywords,$name->name);
            } else {
                echo "pulling $corp_keywords->keyword from cache\n";
                array_push($keywords,\Cache::get('nameid_' . $corp_keywords->keyword));
            }
        }
/*
        foreach ($keywords as $mail_keyword) {
            // check the message bodies for finding any that have the banned keyword
            $match=\DB::table('character_mailbodies')
                ->join('character_mailmessages','character_mailmessages.messageID','=','character_mailbodies.messageID')
                ->where('character_mailbodies.body','LIKE','%'. $mail_keyword .'%')
                ->select('character_mailbodies.messageID', 'character_mailmessages.characterID')
                ->get();
            // create an entry in the security_keywords table if a keyword is found
            foreach ($match as $mailmatch){
                $hash = md5("$mailmatch->characterID$mailmatch->messageID");
                $alert_id = 5;
                $description = "$mailmatch->messageID";
                //BaseSecurity::WriteEvent($hash,$mailmatch->characterID,$alert_id,$description);
                echo "Found $mail_keyword in $mailmatch->messageID\n";
            }
        }
        */
     }
}
