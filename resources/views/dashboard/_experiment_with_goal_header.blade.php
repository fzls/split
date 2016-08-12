<div class="experiment">
  <div class="experiment-header">
      <div class='inline-controls'>
        <small>
            @if(is_null($experiment->start_time()))
                'Unknown'
            @else
                {{$experiment->start_time()->toDateTimeString()}}
            @endif
        </small>
          @include('dashboard._controls',['experiment'=>$experiment])
      </div>
  </div>
</div>
