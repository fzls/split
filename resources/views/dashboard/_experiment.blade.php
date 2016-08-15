@if(is_null($goal))
    <?php $experiment_class = "experiment";?>
@else
    <?php $experiment_class = "experiment experiment_with_goal";?>
@endif

<?php
/* @var Split\Impl\Experiment $experiment */
$experiment->calc_winning_alternatives();
?>

<div class="{{$experiment_class}}" data-name="{{$experiment->name}}" data-complete="{{$experiment->has_winner()}}">
    <div class="experiment-header">
        <h2>
            Experiment: {{$experiment->name}}
            @if($experiment->version()>1)
                <span class="version">v{{$experiment->version()}}</span>
            @endif
            @unless(is_null($goal))
                <span class="goal">Goal:{{$goal}}</span>
            @endunless
            <?php $metrics = \Split\Impl\Metric::all()->filter(function ($metric) use ($experiment) { return $metric->experiments->contains($experiment); });?>
            @unless($metrics->isEmpty())
                <span class="goal">Metrics:{{$metrics->implode('name',', ')}}</span>
            @endunless
        </h2>

        @if(is_null($goal))
            <div class="inline-controls">
                <small>
                    @if(is_null($experiment->start_time()))
                        'Unknown'
                    @else
                        {{$experiment->start_time()->toDateTimeString()}}
                    @endif
                </small>
                @include('dashboard._controls',['experiment'=>$experiment])
            </div>
        @endif
    </div>
    <table>

        <tr>
            <th>Alternative Name</th>
            <th>Participants</th>
            <th>Non-finished</th>
            <th>Completed</th>
            <th>Conversion Rate</th>
            <th>
                <form>
                    <select id="dropdown-{{$experiment->jstring($goal)}}"
                            name="dropdown-{{$experiment->jstring($goal)}}">
                        <option value="confidence-{{$experiment->jstring($goal)}}">Confidence</option>
                        <option value="probability-{{$experiment->jstring($goal)}}">Probability of being Winner</option>
                    </select>
                </form>
            </th>
            <th>Finish</th>
        </tr>
        <?php $total_participants = $total_completed = $total_unfinished = 0;?>
        @foreach($experiment->alternatives as $alternative)
            <tr>
                <td>
                    {{$alternative->name}}
                    @if($alternative->is_control())
                        <em>control</em>
                    @endif
                </td>
                <td>{{$alternative->participant_count()}}</td>
                <td>{{$alternative->unfinished_count()}}</td>
                <td>{{$alternative->completed_count($goal)}}</td>
                <td>
                    {{round($alternative->conversion_rate($goal)*100,2)}}%
                    @if($experiment->control()->conversion_rate($goal)>0 && !$alternative->is_control())
                        @if($alternative->conversion_rate($goal)>$experiment->control()->conversion_rate($goal))
                            <span class="better">
                        +{{round($alternative->conversion_rate($goal)/$experiment->control()->conversion_rate($goal)*100-100,2)}}%
                    </span>
                        @elseif($alternative->conversion_rate($goal)<$experiment->control()->conversion_rate($goal))
                            <span class="worse">
                        {{round($alternative->conversion_rate($goal)/$experiment->control()->conversion_rate($goal)*100-100,2)}}%
                    </span>
                        @endif
                    @endif
                </td>
                <script type="text/javascript" id="sourcecode">
                    $(document).ready(function () {
                        $('.probability-{{$experiment->jstring($goal)}}').hide();
                        $('#dropdown-{{$experiment->jstring($goal)}}').change(function () {
                            $('.box-{{$experiment->jstring($goal)}}').hide();
                            $('.' + $(this).val()).show();
                        });
                    });
                </script>
                <td>
                    <div class="box-{{$experiment->jstring($goal)}} confidence-{{$experiment->jstring($goal)}}">
                        <span title='z-score: {{round($alternative->z_score($goal),3)}}'>{{\Split\Impl\Zscore::confidence_level($alternative->z_score($goal))}}</span>
                        <br>
                    </div>
                    <div class="box-{{$experiment->jstring($goal)}} probability-{{$experiment->jstring($goal)}}">
                        <span title="p_winner: {{round($alternative->p_winner($goal),3)}}">{{round($alternative->p_winner($goal)*100,3)}}
                            %</span>
                    </div>
                </td>
                <td>
                    @if($experiment->has_winner())
                        @if($experiment->winner()->name==$alternative->name)
                            Winner
                        @else
                            Loser
                        @endif
                    @else
                        <form action="{{url("experiment?experiment=$experiment->name")}}" method='post'
                              onclick="return confirmWinner()">
                            <input type='hidden' name='alternative' value='{{$alternative->name}}'>
                            <input type="submit" value="Use this" class="green">
                        </form>
                    @endif
                </td>
            </tr>
            <?php
            $total_participants += $alternative->participant_count();
            $total_unfinished += $alternative->unfinished_count();
            $total_completed += $alternative->completed_count($goal);
            ;?>
        @endforeach

        <tr class="totals">
            <td>Totals</td>
            <td>{{$total_participants}}</td>
            <td>{{$total_unfinished}}</td>
            <td>{{$total_completed}}</td>
            <td>N/A</td>
            <td>N/A</td>
            <td>N/A</td>
        </tr>
    </table>
</div>
