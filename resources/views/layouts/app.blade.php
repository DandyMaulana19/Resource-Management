<!doctype html>
<html>
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>@yield("title")</title>
    {!! ToastMagic::styles() !!}
    @vite('resources/css/app.css')
  </head>
  <body>
      @yield("content")
      {!! ToastMagic::scripts() !!}
  </body>
</html>