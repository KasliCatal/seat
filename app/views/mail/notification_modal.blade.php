@extends('layouts.emptyLayout')

@section('page_content')
{{-- open a empty form to get a crsf token --}}
{{ Form::open(array()) }} {{ Form::close() }}
  <!-- override the CCP styles -->
  <style>
    #mail {
      font-size: 1.0em;
      line-height: 100%;
    }
    #mail font {
      font-size: inherit;
      color: #000000 !important;
    }
    .modal-content {
      width: 900px;
      margin-left: -150px;
    }
  </style>
  
    <!-- notification reveal modal -->
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
          <h4 class="modal-title">{{ $notifications->description }} From: {{ $notifications->senderName }}</h4>
        </div>
        <div class="modal-body">
          <p class="text-center"><b>{{ $notifications->text }}</b></p>
        </div>
      </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
  @stop
