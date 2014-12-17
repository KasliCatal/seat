<?php

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

        // Get the Queue information from the database
        $db_queue_count = \SecurityEvents::where('result','0')
            ->count();

        $response = array(
            'security_count' => $db_queue_count
        );

        return Response::json($response);
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
        $search_criteria = Input::get('search_criteria');
        if ($search_criteria == '')
            return Redirect::action('SecurityController@getView');

        $search_criteria = htmlspecialchars($search_criteria);

        $searchCharacters = DB::table('security_events')
            ->join('eve_characterinfo','eve_characterinfo.characterID','=','security_events.characterID')
            ->join('security_alerts','security_alerts.alertID','=','security_events.alertID')
            ->where('eve_characterinfo.characterName','like',"%$search_criteria%")
            ->select('security_events.*', 'security_alerts.alertName');
        $searchEvents = DB::table('security_events')
            ->join('security_alerts','security_alerts.alertID','=','security_events.alertID')
            ->where('id',$search_criteria)
            ->select('security_events.*', 'security_alerts.alertName')
            ->union($searchCharacters)
            ->get();
        if ($searchEvents){

            foreach ($searchEvents as $row) {

                $events[$row->id] = array (
                    'eventid'         => $row->id,
                    'characterID'     => $row->characterID,
                    'peopleGroupID'   => $this->characterPeopleGroup($row->characterID),
                    'description'     => $row->description,
                    'alertName'       => $row->alertName
                );
            }
            return View::make('security.view')
                ->with('events',$events);
        }

        return Redirect::action('SecurityController@getView');
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
        $characterPeopleGroup = DB::table('seat_people_main')
                ->join('seat_people','seat_people_main.personID','=','seat_people.personID')
                ->join('account_apikeyinfo_characters','account_apikeyinfo_characters.keyID','=','seat_people.keyID')
                ->where('account_apikeyinfo_characters.characterID',$characterID)
                ->select('seat_people_main.characterID')
                ->first();
        if (isset ($characterPeopleGroup )){
            return $characterPeopleGroup->characterID;
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
        $eventid = htmlspecialchars($eventid);
        $eventDetails = \DB::table('security_events')
            ->join('security_alerts','security_alerts.alertID','=','security_events.alertID')
            ->where('id',$eventid)
            ->first();
        $event[$eventDetails->id] = array (
            'eventid'         => $eventDetails->id,
            'characterID'     => $eventDetails->characterID,
            'peopleGroupID'   => $this->characterPeopleGroup($eventDetails->characterID),
            'description'     => $eventDetails->description,
            'alertName'       => $eventDetails->alertName,
            'result'          => $eventDetails->result,
            'notes'           => $eventDetails->notes,
        );
        return View::make('security.details')
            ->with('event',$event);
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

        $openEvents = \DB::table('security_events')
            ->join('security_alerts','security_alerts.alertID','=','security_events.alertID')
            ->where('result',0)
            ->get();

        foreach ($openEvents as $row) {

            $events[$row->id] = array (
                'eventid'         => $row->id,
                'characterID'     => $row->characterID,
                'peopleGroupID'   => $this->characterPeopleGroup($row->characterID),
                'description'     => $row->description,
                'alertName'       => $row->alertName
            );
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
        $keywords = $eventDetails = \DB::table('security_keywords')->get();

        return View::make('security.settings')
            ->with('keywords',$keywords);
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
        $keyword = \SecurityKeywords::find($keywordID);

        $keyword->delete();

        return Redirect::action('SecurityController@getSettings')
            ->with('success', 'Keyword "' . $keyword->keyword . '" Deleted');
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
        $newword = htmlspecialchars(Input::get('newword'));
        $type    = htmlspecialchars(Input::get('type'));
        $keyword = new \SecurityKeywords;
        $keyword->keyword = $newword;
        $keyword->type    = $type;
        $keyword->save();

        return Redirect::action('SecurityController@getSettings')
            ->with('success', 'Keyword "' . $newword . '" Added');
    }
}
