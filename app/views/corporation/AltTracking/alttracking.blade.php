@extends('layouts.masterLayout')

@section('html_title', 'Corporation Alts Tracking')

@section('page_content')

  <div class="row">
    <div class="col-md-12">
      <div class="panel panel-default">
        <div class="panel-heading">
          <h3 class="panel-title">
            <b>All Corporation Alt(s) @if (count($matched_alts) > 0) ({{ count($matched_alts) }}) @endif</b>
          </h3>
        </div>
        <div class="panel-body">

          <table class="table table-condensed compact table-hover" id="datatable">
            <thead>
              <th>Name</th>
              <th>People Group</th>
              <th>Corporation</th>
			  <th>Key Status</th>
              <th></th>
            </thead>
            <tbody>

              @foreach ($matched_alts as $character)

                <tr>
                  <td>
                    <a href="{{ action('CharacterController@getView', array('characterID' => $character->characterID)) }}">
                      <img src='//image.eveonline.com/Character/{{ $character->characterID }}_32.jpg' class='img-circle' style='width: 18px;height: 18px;'>
                    {{ $character->name }}
                    </a>
                  </td>
				  <td>
                    {{ $character->peopleGroupID }}
                  </td>
                  <td>
                    {{ $character->corporationID }}
                  </td>

                </tr>

              @endforeach

            </tbody>
          </table>
        </div>
      </div>
    </div> <!-- col-md-12 -->
  </div> <!-- row -->

@stop
