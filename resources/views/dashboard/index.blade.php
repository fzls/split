@extends("dashboard.layout")

@section('body')
    @if(!$experiments->isEmpty())
        <p class="intro">The list below contains all the registered experiments along with the number of test
            participants, completed and conversion rate currently in the system.</p>

        <input type="text" placeholder="Begin typing to filter" id="filter"/>
        <input type="button" id="toggle-completed" value="Hide completed"/>
        <input type="button" id="toggle-active" value="Hide active"/>
        <input type="button" id="clear-filter" value="Clear filters"/>

        @foreach($experiments as $experiment)
            @if(!$experiment->goals()->isEmpty())
                @include('dashboard._experiment',[
                    'goal'=>null,
                    'experiment'=>$experiment
                ])
            @else
                @include('dashboard._experiment_with_goal_header',[
'locals'=>['experiment'=>$experiment]
                ])
                @foreach($experiment->goals() as $goal)
                    @include('dashboard._experiment',[
                        'goal'=>$goal,
                        'experiment'=>$experiment
                    ])
                @endforeach
            @endif
        @endforeach
    @else
        <p class="intro">No experiments have started yet, you need to define them in your code and introduce them to
            your users.</p>
        <p class="intro">Check out the <a href='https://github.com/fzls/split#readme'>Readme</a> for more help getting
            started.</p>
    @endif
@endsection


