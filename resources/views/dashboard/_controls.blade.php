@if($experiment->has_winner())
    <form action="{{url("/reopen?experiment=$experiment->name")}}" method='post' onclick="return confirmReopen()">
        {{csrf_field()}}
        <input type="submit" value="Reopen Experiment">
    </form>
@endif

@if(!is_null($experiment->start_time()))
    <form action="{{url("/reset?experiment=$experiment->name")}}" method='post' onclick="return confirmReset()">
        {{csrf_field()}}
        <input type="submit" value="Reset Data">
    </form>
@else
    <form action="{{url("/start?experiment=$experiment->name")}}" method='post'>
        {{csrf_field()}}
        <input type="submit" value="Start">
    </form>
@endif

<form action="{{url("/experiment?experiment=$experiment->name")}}" method='post' onclick="return confirmDelete()">
    {{csrf_field()}}
    {{method_field("DELETE")}}
    <input type="submit" value="Delete" class="red">
</form>
