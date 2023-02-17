<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">

    {{-- As required by an Argos pen-test... --}}
    @if (config('ctrl.csp'))
    <meta http-equiv="Content-Security-Policy" content="default-src * 'unsafe-inline' 'unsafe-eval'; script-src * 'unsafe-inline' 'unsafe-eval'; connect-src * 'unsafe-inline'; img-src * data: blob: 'unsafe-inline'; frame-src *; style-src * 'unsafe-inline'; font-src * 'unsafe-inline'">
    @endif
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    <title>CTRL</title>

    <!-- Bootstrap core CSS -->
    <!-- Latest compiled and minified CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css" integrity="sha384-HSMxcRTRxnN+Bdg0JdbxYKrThecOKuH5zCYotlSAcp1+c8xmyTe9GYg1l9a69psu" crossorigin="anonymous">
    
    <!-- Font Awesome -->   
    @if (app()->environment('local'))
      <link rel="stylesheet" href="{{ asset('assets/vendor/ctrl/vendor/font-awesome/css/font-awesome.min.css') }}">
    @else
      <link rel="stylesheet" href="https://use.fontawesome.com/ededc99dd3.css">
    @endif
    <!-- Animate.css, used by notify -->
    <link rel="stylesheet" href="{{ asset('assets/vendor/ctrl/vendor/animate.css/animate.min.css') }}">

    @yield('css')
    @stack('css') {{-- Individual form fields push to the stack --}}

    <link rel="stylesheet" href="{{ asset('assets/vendor/ctrl/css/main.css') }}">

    @if (view()->exists('ctrl_custom::custom_css'))
      @include('ctrl_custom::custom_css')
    @endif

    <!-- Fix header and footer -->
    <style>

	   </style>

    <!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
      <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->

  </head>

  <body>
    {{-- Pentest recommendation around iframes --}}
    <style>html { display:none }</style>
    <script>
    if (self == top) { document.documentElement.style.display = 'block'; } else {
    top.location = self.location; }
    </script>

    <div class="navbar navbar-inverse navbar-fixed-top" role="navigation">
      <div class="container">
        <div class="navbar-header">
          <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <a class="navbar-brand" href="{{ route('ctrl::dashboard') }}"><i class="fa fa-lg fa-home"></i></a>
        </div>
        <div class="collapse navbar-collapse">
          <ul class="nav navbar-nav">
            {{-- Don't think we need a "home" link
            <li class="active"><a href="{{ route('ctrl::dashboard') }}">Dashboard</a></li>
            --}}
            @foreach ($menu_links as $menu_title=>$links)

              @if (count($links) == 1)
                <li>
                  @if (!empty($links[0]['edit']['link'])) {{-- Indicates a "single item" --}}
                    <a href="{{ $links[0]['edit']['link'] }}">{!! $links[0]['icon'] !!}{{ $links[0]['title'] }}</a>
                  @elseif (!empty($links[0]['list']['link']))
                    <a href="{{ $links[0]['list']['link'] }}">{!! $links[0]['icon'] !!}{{ $links[0]['title'] }}</a>
                  @endif
                </li>
              @else

                <li class="dropdown">
                  <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">{{ $menu_title }} <span class="caret"></span></a>
                  <ul class="dropdown-menu">
                  @foreach ($links as $link)
                    <li>
                      @if (!empty($link['edit']['link'])) {{-- Indicates a "single item" --}}
                        <a href="{{ $link['edit']['link'] }}">{!! $link['icon'] !!}{{ $link['title'] }}</a>
                      @elseif (!empty($link['list']['link']))
                        <a href="{{ $link['list']['link'] }}">{!! $link['icon'] !!}{{ $link['title'] }}</a>
                      @endif
                    </li>
                  @endforeach
                  </ul>
                </li>
              @endif
            @endforeach
          </ul>
        </div><!--/.nav-collapse -->
      </div>
    </div>

    <div class="container">

      @yield('content')

    </div><!-- /.container -->


    <footer class="footer">
      <div class="container">
        <p class="text-muted"><a href="{{ route('ctrl::logout') }}"><i class="fa fa-power-off"></i> Logout</a></p>
      </div>
    </footer>


    <!-- Bootstrap core JavaScript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    <script src="{{ asset('assets/vendor/ctrl/vendor/jquery/jquery-1.11.3.min.js') }}"></script>

    <!-- Latest compiled and minified JavaScript -->
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js" integrity="sha384-aJ21OjlMXNL5UyIl/XNwTMqvzeRMZH2w8c5cRVpzpU8Y5bApTppSuUkhZXN0VxHd" crossorigin="anonymous"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/handlebars.js/4.7.7/handlebars.min.js" integrity="sha512-RNLkV3d+aLtfcpEyFG8jRbnWHxUqVZozacROI4J2F1sTaDqo1dPQYs01OMi1t1w9Y2FdbSCDSQ2ZVdAC8bzgAg==" crossorigin="anonymous"></script>

    <script src="{{ asset('assets/vendor/ctrl/vendor/bootstrap-notify/bootstrap-notify.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/ctrl/vendor/bootbox/bootbox.min.js') }}"></script>

    <script src="{{ asset('assets/vendor/ctrl/js/main.js') }}"></script>

    @yield('js')

    {{-- I think different versions of Laravel require periods or colons...? TBC --}}
    @if (view()->exists('ctrl_custom.custom_js'))
      <!-- Custom JS -->
      @include('ctrl_custom.custom_js')
    @elseif (view()->exists('ctrl_custom::custom_js'))
      <!-- Custom JS -->
      @include('ctrl_custom::custom_js')
    @else
      <!-- No custom JS -->
    @endif

    @include('ctrl::notify')

    @stack('js') {{-- Individual form fields push to the stack --}}

  </body>
</html>
