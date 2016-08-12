<!DOCTYPE html>
<html>
<head>
    <meta content='text/html; charset=utf-8' http-equiv='Content-Type'>
    <link rel="stylesheet" href="{{asset('reset.css')}}">
    <link rel="stylesheet" href="{{asset('style.css')}}">
    <script type="text/javascript" src='{{asset('dashboard.js')}}'></script>
    <script type="text/javascript" src='{{asset('jquery-1.11.1.min.js')}}'></script>
    <script type="text/javascript" src='{{asset('dashboard-filtering.js')}}'></script>
    <title>Split</title>
</head>
<body>
<div class="header">
    <h1>Split Dashboard</h1>
    <p class="environment">{{$current_env}}</p>
</div>

<div id="main">
    @yield('body')
</div>

<div id="footer">
    <p>Powered by <a href="http://github.com/fzls/split">Split</a> v{{App::make('split_config')->version}}</p>
    <p>Portted from <a href="http://github.com/splitrb/split">Splitrb</a></p>
</div>
</body>
</html>
