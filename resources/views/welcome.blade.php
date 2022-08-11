<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Laravel</title>

        <!-- Styles -->
        <link rel="stylesheet" href="{{ mix('css/app.css') }}">

        <!-- Scripts -->
        <script src="{{ mix('js/app.js') }}" defer></script>
    </head>
    <body class="body-bg min-h-screen pt-12 md:pt-20 pb-6 px-2 md:px-0" style="font-family:'Lato',sans-serif;">
    <header class="max-w-2xl mx-auto text-white text-center">
        <h1 class="text-4xl font-bold">
            Firefly III PayPal Importer
        </h1>

        <div class="text-xs italic text-center text-white">
            version: {{ config('app.version') }}
        </div>

        <h2 class="mt-4">
            Instance contains {{ $txCount }} transactions.
            {{ $txPushed }} of which are pushed to your Firefly instance.
        </h2>

    </header>

    <main class="invisible bg-white max-w-2xl mx-auto p-8 md:p-12 my-10 rounded-lg shadow-2xl"></main>

    <footer class="mx-auto flex justify-center text-white">
        <a href="https://github.com/robvankeilegom/firefly-III-paypal-importer" target="_blank" class="hover:underline">GitHub</a>
        <span class="mx-3">•</span>
        <a href="https://robvankeilegom.be" target="_blank" class="hover:underline">Rob Van Keilegom</a>
        <span class="mx-3">•</span>
        <a href="https://github.com/firefly-iii/firefly-iii" target="_blank" class="hover:underline">Firefly III</a>
        <span class="mx-3">•</span>
        <a href="{{ config('services.firefly.uri') }}" target="_blank" class="hover:underline">Your Firefly III instance</a>

    </footer>

</body>
</html>
