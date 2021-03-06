<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <title>Mukto</title>

    <!-- Bootstrap -->
    <link href="https://fonts.googleapis.com/css?family=Roboto:300,400,500" rel="stylesheet">
    <link rel="stylesheet" href="{{asset('css\icomoon\style.css')}}">
    {{-- swiper --}}
    {{-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Swiper/4.2.6/css/swiper.min.css"> --}}
    <link rel="stylesheet" href="{{asset('css\custom.css')}}">
    <link rel="stylesheet" href="{{asset('css\swiper.min.css')}}">

    <link rel="stylesheet" href="{{asset('css\style.css')}}">
    <link rel="stylesheet" href="{{asset('css\unicef.css') }}">
    
    <link rel="stylesheet" type="text/css" href="{{asset('css\all-ie-only.css') }}" />
    

  <script>
    // console.log = function() {}
    if (typeof console._commandLineAPI !== 'undefined') {
      console.API = console._commandLineAPI; //chrome
    } else if (typeof console._inspectorCommandLineAPI !== 'undefined') {
        console.API = console._inspectorCommandLineAPI; //Safari
    } else if (typeof console.clear !== 'undefined') {
        console.API = console;
    }
    
  </script>


    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->

    @yield('styles')
    <!-- Global site tag (gtag.js) - Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=UA-120665192-1"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());

      gtag('config', 'UA-120665192-1');
    </script>

  </head>
  <body>
    <div class="header-full">
      <header class="container mb-0">
        <div class="row">
          <nav class="navbar navbar-expand-lg navbar-light navbar-red mb-0 pb-0 pt-0 col-12">
            <a class="navbar-brand" href="/dashboard">
               <img src="{{asset('images\logo.png')}}">
            </a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarSupportedContent">
              <ul class="navbar-nav mr-auto">
                <li class="nav-item {{ Request::path() == 'dashboard' ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('frontend.dashboard') }}">Dashboard <span class="sr-only">(current)</span></a>
                </li>
                <li class="nav-item dropdown {{ (Request::path() == 'outputs/maternal' || Request::path() == 'outputs/child') ? 'active' : '' }}">
                  <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    Outputs
                  </a>
                  <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                    <a class="dropdown-item {{ Request::path() == 'outputs/maternal' ? 'active' : '' }}"
                    href="{{ route('frontend.outcomes.maternal') }}">Maternal</a>
                    <a class="dropdown-item {{ Request::path() == 'outputs/child' ? 'active' : '' }}" href="{{ route('frontend.outcomes.child') }}">Child</a>
                  </div>
                </li>
                <li class="nav-item {{ Request::path() == 'impacts' ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('frontend.impacts') }}">Impacts</a>
                </li>
                {{-- <li class="nav-item {{ Request::path() == 'technical-standard' ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('frontend.technical-standard') }}">Technical Standards</a>
                </li> --}}
              </ul>
                <!-- <form class="form-inline my-2 my-lg-0">
                    <input class="form-control mr-sm-2" type="search" placeholder="Search" aria-label="Search">
                    <button class="btn btn-outline-success my-2 my-sm-0" type="submit">Search</button>
                </form> -->
            </div>
          </nav>
        </div>
      </header>
    </div> {{-- /.header-full --}}
    @yield('content')

    <footer>
      <div class="container">
        <div class="row">
          <div class="col-12">
            <ul class="footer-menu list-inline">
              <li class="list-inline-item"><a href="{{ route('frontend.dashboard') }}">Dashboard</a></li>
              <li class="list-inline-item"><a href="{{ route('frontend.outcomes.maternal') }}">Output Maternal</a></li>
              <li class="list-inline-item"><a href="{{ route('frontend.outcomes.child') }}">Output Child</a></li>
              <li class="list-inline-item"><a href="{{ route('frontend.impacts') }}">Impact</a></li>
              <li class="list-inline-item"><a href="{{ route('frontend.technical-standard') }}">Technical Standards</a></li>
            </ul>
          </div>
        </div>
        
      </div>
    </footer>


    <!-- Latest compiled and minified JavaScript -->
    {{-- <script src="https://code.jquery.com/jquery-3.3.1.min.js" integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8=" crossorigin="anonymous"></script>  --}}
    <script src="{{asset('js\jquery-3.3.1.min.js')}}"></script>

    {{-- <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script> --}}
    <script src="{{asset('js\popper.min.js')}}"></script>

    {{-- <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script> --}}
    <script src="{{asset('js\bootstrap.min.js')}}"></script>

    {{-- <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.4.0/Chart.min.js"></script> --}}
    <script src="{{asset('js\Chart.min.js')}}"></script>

    {{-- d3 js added --}}
    {{-- <script src="https://cdnjs.cloudflare.com/ajax/libs/d3/3.5.0/d3.js"></script> --}}
    <script src="{{asset('js\d3.js')}}"></script>
    <script type="text/javascript" src="{{asset('js\radial-progress-chart.js')}}"></script>
    {{-- for element animation --}}
    {{-- <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/1.20.4/TweenMax.min.js"></script> --}}
    <script src="{{asset('js\TweenMax.min.js')}}"></script>

    <script type="text/javascript" src="{{asset('js\hammer.min.js')}}"></script>
    <script type="text/javascript" src="{{asset('js\chart-plugin-zoom.min.js')}}"></script>
    <script>
        if (Function('/*@cc_on return document.documentMode===10@*/')()){
          document.documentElement.className+=' ie10-11';
        }

        if(navigator.userAgent.match(/Trident.*rv:11\./)) {
          document.documentElement.className+=' ie10-11';
        }


    </script>

    <script>
      @yield('injavascript')
    </script>

    @yield('outjavascript')
    
  </body>
</html>
