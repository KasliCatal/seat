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

          <table class="table table-condensed compact table-hover" id="altstable">
            <thead>
              <th>Name</th>
              <th>People Group</th>
              <th>Corporation</th>
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

@section('javascript')
<script type="text/javascript" id="js">
  // Allows modal to be repopulated with data
  $('body').on('hidden.bs.modal', '.modal', function () {
    $(this).removeData('bs.modal');
  });

  // settings for this specific data table
  $(document).ready(function() {
      $('#altstable').dataTable( {
          "searching":   false,
          "lengthMenu": [[25, 50, 100, -1], [ 25, 50, 100, "All"]],
          "order":       []
      } );
  } );

  // Events to be triggered when the ajax calls have compelted.
  $( document ).ajaxComplete(function() {

    // Update any outstanding id-to-name fields
    var items = [];
    var arrays = [], size = 250;

    $('[rel="id-to-name"]').each( function(){
      //add item to array
      if ($.isNumeric($(this).text())) {
        items.push( $(this).text() );
      }
    });

    var items = $.unique( items );

    while (items.length > 0)
      arrays.push(items.splice(0, size));

    $.each(arrays, function( index, value ) {

      $.ajax({
        type: 'POST',
        url: "{{ action('HelperController@postResolveNames') }}",
        data: {
          'ids': value.join(',')
        },
        success: function(result){
          $.each(result, function(id, name) {

            $("span:contains('" + id + "')").html(name);
          })
        },
        error: function(xhr, textStatus, errorThrown){
          console.log(xhr);
          console.log(textStatus);
          console.log(errorThrown);
        }
      });
    });
  });
</script>
@stop
