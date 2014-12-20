@extends('layouts.masterLayout')

@section('html_title', 'Security Alerts')

@section('page_content')
{{-- open a empty form to get a crsf token --}}
{{ Form::open(array()) }} {{ Form::close() }}
  <div class="row">
    <div class="col-md-12">
      <div class="box">

        <div class="box-header">
          <h3 class="box-title">Security Events</h3>
          <div align="right">
            <a data-target="#addKeywordModal" data-toggle="modal" class="btn btn-success btn-small"><i class="fa fa-plus"></i> Add Keyword</a>
          </div>
        </div>

        <div class="box-body">
          <table class="table table-condensed table-hover" id="datatable">
            <thead>
              <tr>
                <th>Keyword</th>
                <th>Type</th>
                <th></th>
              </tr>
            </thead>
            <tbody>

              @foreach($keywords as $keyword)

                <tr>
                  @if($keyword->type == 'corp')
                    <td><span rel="id-to-name">{{ $keyword->keyword }}</span></td>
                  @else
                    <td>{{ $keyword->keyword }}</td>
                  @endif
                  <td>{{ $keyword->type }}</td>
                  <td>
                    <a href="{{ action('SecurityController@getDeleteKeyword', array('keyword' => $keyword->id, '')) }}" class="btn btn-danger btn-xs"><i class="fa fa-trash"></i> Delete Keyword</a>
                  </td>
                </tr>

              @endforeach

            </tbody>
          </table>
        </div><!-- /.box-body -->
      </div><!-- /.box -->
    </div>
  </div>

  <!-- add keyword modal -->
  <div class="modal fade" id="addKeywordModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
          <div class="modal-header">
              <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
              <h4 class="modal-title"> Add Keyword</h4>
          </div>
          <div class="modal-body">
            <fieldset>
                {{ Form::open(array('action' => 'SecurityController@postAddKeyword', 'class' => 'form-horizontal')) }}
                  <div class="form-group">
                    {{ Form::label('status', 'Keyword ', array('class'=>'control-label col-md-4')) }}
                    {{ Form::text('newword', '',array('class'=>'control-label col-md-4')) }}
                  </div>
                  <div class="form-group">
                    {{ Form::label('status', 'Type ', array('class'=>'control-label col-md-4')) }}
                    <div class="col-md-6">
                      {{ Form::select('type', array(
                        'alnc' => 'Alliance',
                        'cnct' => 'Contact',
                        'corp' => 'Corporation',
                        'mail' => 'Mail Keyword',
                      ),'alnc' , array( 'class'=>'form-control') ) }}
                    </div>
                  </div>
                  <div class="col-md-offset-4 col-md-6">
                    {{ Form::submit('Submit', array('class' => 'btn bg-olive btn-block')) }}
                  </div>
                {{ Form::close() }}
            </fieldset>
          </div>
      </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
  </div><!-- /.modal -->

@stop

@section('javascript')
<script type="text/javascript" id="js">
  // Events to be triggered when the ajax calls have completed.
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
