<!DOCTYPE html>
<html lang="{{ config('app.locale') }}">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <link rel="shortcut icon" href="/img/favicon/favicon.ico" sizes="16x16 32x32 64x64">
        <link rel="icon" href="/img/favicon/favicon.png" type="image/png">
        <link rel="apple-touch-icon-precomposed" sizes="57x57" href="/img/favicon/apple-touch-icon-57x57.png">
        <link rel="apple-touch-icon-precomposed" sizes="114x114" href="/img/favicon/apple-touch-icon-114x114.png">
        <link rel="apple-touch-icon-precomposed" sizes="72x72" href="/img/favicon/apple-touch-icon-72x72.png">
        <link rel="apple-touch-icon-precomposed" sizes="144x144" href="/img/favicon/apple-touch-icon-144x144.png">

        <title>{{ config('app.name') }}</title>

        <link href="https://fonts.googleapis.com/css?family=Montserrat:700,300,200" rel="stylesheet">

        <link rel="stylesheet" href="/font/fontello/css/fontello.min.css">

        <link href="{{ mix('/css/app.css') }}" rel="stylesheet">
        <script>
            window.Laravel = {!! json_encode([
                'csrfToken' => csrf_token(),
                'siteName'  => config('app.name'),
                'apiDomain' => config('app.url').'/api'
            ]) !!}
        </script>
    </head>

    <body>
        <script>
          (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
              (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
            m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
          })(window,document,'script','//www.google-analytics.com/analytics.js','ga');
          ga('set', 'anonymizeIp', true);
          ga('create', 'UA-66120332-1', 'auto');
          ga('send', 'pageview');
        </script>

        <noscript>
            <h1>Sorry!</h1>
            <p>
                Unfortunately Glazy requires a recent browser with Javascript enabled.
            </p>
            <p>
                If you have disabled Javascript, please <strong>enable Javascript</strong> for this site.
            </p>
            <p>
                If you are using an older web browser, please update it.
                Glazy recommends using the free <strong>Google Chrome</strong> browser.
            </p>
            <p>
                <form action="https://www.google.com/chrome/">
                    <input type="submit" value="Download Google Chrome Now!" />
                </form>
            </p>
        </noscript>

        <div id="app">
            <app></app>
        </div>

        <script src="{{ elixir('/js/manifest.js') }}"></script>
        <script src="{{ elixir('/js/vendor.js') }}"></script>
        <script src="{{ elixir('/js/app.js') }}"></script>
    </body>
</html>
