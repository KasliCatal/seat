@extends('layouts.masterLayout')

@section('html_title', 'Security Events')

@section('page_content')
{{-- open a empty form to get a crsf token --}}
{{ Form::open(array()) }} {{ Form::close() }}
  <div class="modal fade" id="detailModal" tabindex="-1" role="dialog" aria-hidden="true">
      <div class="modal-dialog">
          <div class="modal-content">
              <div class="modal-header">
                  <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                  <h4 class="modal-title"> Details</h4>
              </div>
              <div class="modal-body">
                  <p>
                    The Body
                  </p>
              </div>
          </div><!-- /.modal-content -->
      </div><!-- /.modal-dialog -->
  </div><!-- /.modal -->
  <div class="row">
    <div class="col-md-12">
      <div class="box">

        <div class="box-header">
          <h3 class="box-title">Security Events</h3>
            <div class="box-tools">
              {{ Form::open(array('action' => 'SecurityController@postFindEvents')) }}
                <div class="input-group">
                  {{ Form::text('search_criteria', '', array( 'class'=>'form-control input-sm pull-right', 'placeholder'=>'EventID or Character', 'style'=>'width: 150px;') ) }}
                    <div class="input-group-btn">
                        <button class="btn btn-sm btn-default"><i class="fa fa-search"></i></button>
                    </div>
                </div>
              {{ Form::close() }}
            </div>
        </div>

        <div class="box-body">
          <table class="table table-condensed table-hover" id="eventstable">
            <thead>
              <tr>
                <th>EventID</th>
                <th>Character</th>
                <th>People Group</th>
                <th>Alert Type</th>
                <th>Item</th>
                <th></th>
              </tr>
            </thead>
            <tbody>

              @foreach($events as $event)

                <tr>
                  <td>{{ $event['eventid'] }}</td>
                  <td>
                    <a href="{{ action('CharacterController@getView', array('characterID' => $event['characterID'])) }}"><span rel="id-to-name">{{ $event['characterID'] }}</span></a>
                  </td>
                  <td>
                    <a href="{{ action('CharacterController@getView', array('characterID' => $event['peopleGroupID'])) }}"><span rel="id-to-name">{{ $event['peopleGroupID'] }}</span></a>
                  </td>
                  <td>{{ $event['alertName'] }}</td>
                  @if($event['alertName'] == 'Mail')
                    <td>
                      <a href="{{ action('MailController@getRead', array('messageID' => $event['itemID'])) }}">Flagged: {{ $event['details'] }} </a>
                    </td>
                  @else
                    <td><span rel="id-to-name">{{ $event['itemID'] }}</span></td>
                  @endif
                  <td>
                    <a href="{{ action('SecurityController@getDetails', array('eventid' => $event['eventid'])) }}"  data-target="#detailModal" data-toggle="modal"class="btn btn-info btn-xs"><i class="fa fa-desktop"></i> Edit Details</a>
                  </td>
                </tr>

              @endforeach

            </tbody>
          </table>
        </div><!-- /.box-body -->
      </div><!-- /.box -->
    </div>
  </div>

@stop

@section('javascript')
<script type="text/javascript" id="js">
  // Allows modal to be repopulated with data
  $('body').on('hidden.bs.modal', '.modal', function () {
    $(this).removeData('bs.modal');
  });

  // settings for this specific data table
  $(document).ready(function() {
      $('#eventstable').dataTable( {
          "searching":   false,
          "lengthMenu": [[25, 50, 100, -1], [ 25, 50, 100, "All"]],
          "order":       []
      } );
  } );

  // ignore the text in search_criteria if it is pre-populated
  $(function() {
    $("#search_criteria").click(function() {
      if ($("#search_criteria").val() == "EventID or Name"){
        $("#search_criteria").val("");
      }
    });
  });
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
