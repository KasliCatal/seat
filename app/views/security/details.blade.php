@extends('layouts.emptyLayout')

@section('page_content')
{{-- open a empty form to get a crsf token --}}
{{ Form::open(array()) }} {{ Form::close() }}
<div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
            <h4 class="modal-title"> Details</h4>
        </div>
        <div class="modal-body">
          <fieldset>
              {{ Form::open(array('action' => 'SecurityController@postUpdateEvent', 'class' => 'form-horizontal')) }}
              @foreach ($event as $id)
                <div class="form-group">
                  {{ Form::label('eid', 'Event ID:  ', array('class'=>'control-label col-md-4'))}}
                  <div class="control-label col-md-6">
                    {{ $id['eventid'] }}
                  </div>
                </div>
                <div class="form-group">
                  {{ Form::label('characterName', 'Character Name: ', array('class'=>'control-label col-md-4')) }}
                  <div class="control-label col-md-6">
                    <a href="{{ action('CharacterController@getView', array('characterID' => $id['characterID'])) }}"><span rel="id-to-name">{{ $id['characterID'] }}</span></a>
                  </div>
                </div>
                <div class="form-group">
                  {{ Form::label('description', 'Description: ', array('class'=>'control-label col-md-4')) }} 
                  <div class="control-label col-md-6">
                    {{ $id['alertName'] }} <span rel="id-to-name">{{ $id['description'] }}</span>
                  </div>
                </div>
                  {{ Form::hidden('eventid', $id['eventid']) }}
                <div class="form-group">
                  {{ Form::label('status', 'Status ', array('class'=>'control-label col-md-4')) }}
                  <div class="col-md-6">
                    {{ Form::select('result', array(
                      '0' => 'Undetermined',
                      '1' => 'Passed',
                      '2' => 'Follow Up',
                      '3' => 'Rejected'
                    ), $id['result'], array( 'class'=>'form-control') ) }}
                  </div>
                </div>
                <div class="form-group">
                  <div class="col-md-offset-4 col-md-6">
                    {{ Form::textarea('notes', $id['notes'], array( 'class'=>'form-control', 'rows'=>'4', 'cols'=>'30', 'placeholder'=>'Notes') ) }}
                  </div>
                </div>
                <div class="col-md-offset-4 col-md-6">
                  {{ Form::submit('Submit', array('class' => 'btn bg-olive btn-block')) }}
                </div>
              @endforeach
              {{ Form::close() }}
          </fieldset>
        </div>
    </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->
@stop
@section('javascript')
<script type="text/javascript" id="js">
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