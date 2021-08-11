<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet" type="text/css">

    <!-- Styles -->
    <link href="{{ asset(mix('app.css', 'vendor/cloud-messaging')) }}" rel="stylesheet" type="text/css">

</head>
<body>
    <div id="cloud-messaging">
        <div class="container-fluid mb-5">
            <div class="d-flex align-items-center py-4 header">

                <h4 class="mb-0 ml-3"><strong>JawabApp</strong> Cloud Messaging</h4>

            </div>

            <div class="row mt-4">
                <div class="col-2 sidebar">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a active-class="active" href="{{route('jawab.notifications.index')}}" class="nav-link  d-flex align-items-center {{ (request()->routeIs('jawab.notifications.index')) ? 'active' : '' }} pt-0">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path d="M3 6c0-1.1.9-2 2-2h8l4-4h2v16h-2l-4-4H5a2 2 0 0 1-2-2H1V6h2zm8 9v5H8l-1.67-5H5v-2h8v2h-2z"></path>
                                </svg>
                                <span>Cloud Messaging</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a active-class="active" href="{{route('jawab.notifications.compose')}}" class="nav-link  d-flex align-items-center {{ (request()->routeIs('jawab.notifications.compose')) ? 'active' : '' }}">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path d="M7 17H2a2 2 0 0 1-2-2V2C0 .9.9 0 2 0h16a2 2 0 0 1 2 2v13a2 2 0 0 1-2 2h-5l4 2v1H3v-1l4-2zM2 2v11h16V2H2z"></path>
                                </svg>
                                <span>Compose</span>
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="col-10">
                    @yield('content')
                </div>
            </div>
        </div>
    </div>





















    <!-- Scripts -->
    <script src="{{ asset(mix('app.js', 'vendor/cloud-messaging')) }}"></script>

    @stack('scripts')
</body>
</html>
