<?php

use Pheal\Pheal;
use Seat\EveApi;
use Seat\EveApi\BaseApi;
use Seat\EveApi\Account;

class SecurityController extends BaseController {

    public function __construct()
    {
            $this->beforeFilter('csrf', array('on' => 'post'));
    }

    /*
    |--------------------------------------------------------------------------
    | getShortStatus()
    |--------------------------------------------------------------------------
    |
    | Return the current security event count as json
    |
    */

    public function getShortStatus()
    {
        // Query the database for all the characters and some related
        // information
        $db_queue_count = DB::table('account_apikeyinfo_characters')
            ->leftJoin('seat_keys', 'account_apikeyinfo_characters.keyID', '=', 'seat_keys.keyID')
            ->join('security_events','security_events.characterID','=','account_apikeyinfo_characters.characterID');

        if (\Auth::hasAccess('wdir')) {
            // Get the Queue information from the database
            $db_queue_count = \SecurityEvents::where('result','0')
                ->count();
        }elseif (\Auth::hasAccess('recruiter')) {
            $db_queue_count = $db_queue_count->whereIn('seat_keys.keyID', Session::get('valid_keys'))
                ->where('security_events.result','0')
                ->count();
        }else{
            $db_queue_count = 0;
        }

        $response = array(
            'security_count' => $db_queue_count
        );

        return Response::json($response);
    }

    public function getUpdateJob($keyID)
    {

        // Ensure that this user may access the data for $keyID
        if (!\Auth::isSuperUser())
            if (!in_array($keyID, Session::get('valid_keys')))
                App::abort(404);

        // Get the full key and vCode
        $key = SeatKey::where('keyID', $keyID)->first();

        if (!$key)
            App::abort(404);

        // Check that there is not already an outstanding job for this keyID
        $queue_check = DB::table('queue_information')
            ->whereIn('status', array('Queued', 'Working'))
            ->where('ownerID', $key->keyID)
            ->first();

        if ($queue_check)
            return Response::json(array('state' => 'existing', 'jobID' => $queue_check->jobID));

        // Else, queue a job for this
        $access = EveApi\BaseApi::determineAccess($key->keyID);

        if (!isset($access['type']))
            return Response::json(array('state' => 'error', 'jobID' => null));

        // Only process Character keys here
        if ($access['type'] == 'Character') {

            // Do a fresh AccountStatus lookup
            Account\AccountStatus::update($keyID, $key->vCode);
            $jobID = \App\Services\Queue\QueueHelper::addToQueue('\Seat\EveQueues\Security\CharacterUpdate', $key->keyID, $key->vCode, 'Security', 'Character');
            $mailJobID = \App\Services\Queue\QueueHelper::addToQueue('\Seat\EveQueues\Security\MailUpdate', '0', NULL, 'Security', 'Mail');

            return Response::json(array('state' => 'new', 'jobID' => $jobID));

        } elseif( $access['type'] == 'Corporation' ){

            $jobID = \App\Services\Queue\QueueHelper::addToQueue('\Seat\EveQueues\Security\CharacterUpdate', $key->keyID, $key->vCode, 'Security', 'Character');
            $mailJobID = \App\Services\Queue\QueueHelper::addToQueue('\Seat\EveQueues\Security\MailUpdate', '0', NULL, 'Security', 'Mail');

            return Response::json(array('state' => 'new', 'jobID' => $jobID));

        } else {

            return Response::json(array('keyID' => $key->keyID, 'vCode' => $key->vCode, 'state' => 'error', 'jobID' => null));
        }

    }

    /*
    |--------------------------------------------------------------------------
    | postUpdateEvent()
    |--------------------------------------------------------------------------
    |
    | Update the eventid in the security_events table
    |
    */

    public function postUpdateEvent()
    {
        $result  = htmlspecialchars(Input::get('result'));
        $notes   = htmlspecialchars(Input::get('notes'));
        $eventid = htmlspecialchars(Input::get('eventid'));
        $user    = \Auth::getUser();
        $updated_by = $user->id;
        $date = new \DateTime;

        DB::table('security_events')
            ->where('id',$eventid)
            ->update(array(
                'result'     => $result,
                'notes'      => $notes,
                'updated_by' => $updated_by,
                'updated_at' => $date));

        return Redirect::action('SecurityController@getView');
    }

    /*
    |--------------------------------------------------------------------------
    | postFindEvents()
    |--------------------------------------------------------------------------
    |
    | Searches the security_events table for events that match a specified
    | eventid or partial character name. Returns a view with the results
    |
    */

    public function postFindEvents()
    {
        // Query the database for all the characters and some related
        // information
        $characters = DB::table('account_apikeyinfo_characters')
            ->leftJoin('seat_keys', 'account_apikeyinfo_characters.keyID', '=', 'seat_keys.keyID')
            ->groupBy('account_apikeyinfo_characters.characterID');

        // Check that we only return characters that the current
        // user has access to. SuperUser() automatically
        // inherits all permissions
        if (\Auth::hasAccess('wdir'))
            $characters = $characters->get();
        elseif (\Auth::hasAccess('recruiter')) 
            $characters = $characters->whereIn('seat_keys.keyID', Session::get('valid_keys'))
                ->get();
        else
            App::abort(404);


        $search_criteria = Input::get('search_criteria');
        if ($search_criteria == '')
            return Redirect::action('SecurityController@getView');

        $search_criteria = htmlspecialchars($search_criteria);

        foreach( $characters as $character) {
            $search_characters = DB::table('security_events')
                ->join('eve_characterinfo','eve_characterinfo.characterID','=','security_events.characterID')
                ->join('security_alerts','security_alerts.alertID','=','security_events.alertID')
                ->where('eve_characterinfo.characterName','like',"%$search_criteria%")
                ->where('security_events.characterID',$character->characterID)
                ->select('security_events.*', 'security_alerts.alertName');
            $search_events = DB::table('security_events')
                ->join('security_alerts','security_alerts.alertID','=','security_events.alertID')
                ->where('id',$search_criteria)
                ->where('security_events.characterID',$character->characterID)
                ->select('security_events.*', 'security_alerts.alertName')
                ->union($search_characters)
                ->get();
            if ($search_events){
                foreach ($search_events as $row) {

                    $events[$row->id] = array (
                        'eventid'         => $row->id,
                        'characterID'     => $row->characterID,
                        'peopleGroupID'   => $this->characterPeopleGroup($row->characterID),
                        'itemID'          => $row->itemID,
                        'details'         => $row->details,
                        'alertName'       => $row->alertName,
                        'alertID'         => $row->alertID
                    );
                }
            }
        }

        if (isset($events))
            return View::make('security.view')
                ->with('events',$events);
        else
            return Redirect::action('SecurityController@getView')
                ->withError($search_criteria . ' Not Found');
    }

    /*
    |--------------------------------------------------------------------------
    | characterPeopleGroup()
    |--------------------------------------------------------------------------
    |
    | Accepts a characterID and returns the characterID of the people group it
    | belongs to. If none is found it just returns its own characterID
    |
    */

    public function characterPeopleGroup($characterID)
    {
        $character_people_group = DB::table('seat_people_main')
                ->join('seat_people','seat_people_main.personID','=','seat_people.personID')
                ->join('account_apikeyinfo_characters','account_apikeyinfo_characters.keyID','=','seat_people.keyID')
                ->where('account_apikeyinfo_characters.characterID',$characterID)
                ->select('seat_people_main.characterID')
                ->first();
        if (isset ($character_people_group )){
            return $character_people_group->characterID;
        }else{
            return $characterID;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | getDetails()
    |--------------------------------------------------------------------------
    |
    | Accepts an eventid returns all the details and creates a view, that view
    | is designed to be a modal.
    |
    */

    public function getDetails($eventid)
    {

        // Query the databse for all the characters and some related
        // information
        $characters = DB::table('account_apikeyinfo_characters')
            ->leftJoin('seat_keys', 'account_apikeyinfo_characters.keyID', '=', 'seat_keys.keyID')
            ->groupBy('account_apikeyinfo_characters.characterID');

        $eventid = htmlspecialchars($eventid);
        $event_details = \DB::table('security_events')
            ->join('security_alerts','security_alerts.alertID','=','security_events.alertID')
            ->where('id',$eventid)
            ->first();
        $event[$event_details->id] = array (
            'eventid'         => $event_details->id,
            'characterID'     => $event_details->characterID,
            'peopleGroupID'   => $this->characterPeopleGroup($event_details->characterID),
            'itemID'          => $event_details->itemID,
            'details'         => $event_details->details,
            'alertName'       => $event_details->alertName,
            'alertID'         => $row->alertID,
            'result'          => $event_details->result,
            'notes'           => $event_details->notes,
        );

        if(\Auth::hasAccess('wdir')) {
            return View::make('security.details')
                ->with('event',$event);
        }elseif (\Auth::hasAccess('recruiter')){
            $characters = $characters->whereIn('seat_keys.keyID', Session::get('valid_keys'))
                ->where('account_apikeyinfo_characters.characterID',$event_details->characterID)
                ->get();
            if($characters)
                return View::make('security.details')
                    ->with('event',$event);
            else
                App::abort(404);
        }else{
            App::abort(404);
        }

    }

    /*
    |--------------------------------------------------------------------------
    | getView()
    |--------------------------------------------------------------------------
    |
    | Creates a view with all the open (result = 0) events
    |
    */

    public function getView()
    {
        $events =[];
        // Query the databse for all the characters and some related
        // information
        $characters = DB::table('account_apikeyinfo_characters')
            ->leftJoin('seat_keys', 'account_apikeyinfo_characters.keyID', '=', 'seat_keys.keyID')
            ->groupBy('account_apikeyinfo_characters.characterID');

        // Check that we only return characters that the current
        // user has access to. SuperUser() automatically
        // inherits all permissions
        if (\Auth::hasAccess('wdir'))
            $characters = $characters->get();
        elseif (\Auth::hasAccess('recruiter')) 
            $characters = $characters->whereIn('seat_keys.keyID', Session::get('valid_keys'))
                ->get();
        else
            App::abort(404);

        foreach( $characters as $character) {
            $open_events = \DB::table('security_events')
                ->join('security_alerts','security_alerts.alertID','=','security_events.alertID')
                ->where('result',0)
                ->where('CharacterID',$character->characterID)
                ->get();
            foreach ($open_events as $row) {

                $events[$row->id] = array (
                    'eventid'         => $row->id,
                    'characterID'     => $row->characterID,
                    'peopleGroupID'   => $this->characterPeopleGroup($row->characterID),
                    'itemID'          => $row->itemID,
                    'details'         => $row->details,
                    'alertName'       => $row->alertName,
                    'alertID'         => $row->alertID
                );
            }
        }
        return View::make('security.view')
            ->with('events',$events);
    }

    /*
    |--------------------------------------------------------------------------
    | getSettings()
    |--------------------------------------------------------------------------
    |
    | Get the current settings state
    |
    */

    public function getSettings()
    {
        if(\Auth::hasAccess('wdir')) {
            $keywords = $event_details = \DB::table('security_keywords')->get();

            return View::make('security.settings')
                ->with('keywords',$keywords);
        }else{
            App::abort(404);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | deleteKeyword()
    |--------------------------------------------------------------------------
    |
    | Removes a keyword from the security_keywords table
    |
    */

    public function getDeleteKeyword($keywordID)
    {
        if(\Auth::hasAccess('wdir')) {
            $keyword = \SecurityKeywords::find($keywordID);

            $keyword->delete();

            return Redirect::action('SecurityController@getSettings')
                ->with('success', 'Keyword "' . $keyword->keyword . '" Deleted');
        }else{
            App::abort(404);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | addKeyword()
    |--------------------------------------------------------------------------
    |
    | Removes a keyword from the security_keywords table
    |
    */

    public function postAddKeyword()
    {
        if(\Auth::hasAccess('wdir')) {
            $newword = htmlspecialchars(Input::get('newword'));
            $type    = htmlspecialchars(Input::get('type'));

            // Try to get a corp ID for the name, if it isn't found return an error
            if ($type == 'corp'){
                $newword = $this->getCorporationID($newword);
                if (!is_numeric($newword) ){
                    return Redirect::action('SecurityController@getSettings')
                        ->withErrors($newword);
                }
            }

            $keyword = new \SecurityKeywords;
            $keyword->keyword = $newword;
            $keyword->type    = $type;
            $keyword->save();

            return Redirect::action('SecurityController@getSettings')
                ->with('success', 'Keyword "' . $newword . '" Added');
        }else{
            App::abort(404);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | getCorporationID()
    |--------------------------------------------------------------------------
    |
    | Get the corporationID for a give name
    |
    */

    public function getCorporationID($corporation_name)
    {
        $pheal = new Pheal();

        try {
            $corporation = $pheal->CharacterID(array("names" => $corporation_name));
        } catch (\Pheal\Exceptions\PhealException $e) {
            throw $e;
        }

        if ( $corporation->characterID ){
            return $corporation->characterID;
        }else{
            return "$corporation_name Not Found";
        }

    }
}
