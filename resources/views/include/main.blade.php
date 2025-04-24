<!DOCTYPE html>
<html lang="en">

<head>
    <title>ระบบนำเข้าเอกสาร</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description"
        content="Mantis is made using Bootstrap 5 design framework. Download the free admin template & use it for your project.">
    <meta name="keywords"
        content="Mantis, Dashboard UI Kit, Bootstrap 5, Admin Template, Admin Dashboard, CRM, CMS, Bootstrap Admin Template">
    <meta name="author" content="CodedThemes">
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&display=swap"
        id="main-font-link">
    <link rel="stylesheet" href="{{asset('dist/assets/fonts/feather.css')}}">
    <link rel="stylesheet" href="{{asset('dist/assets/fonts/fontawesome.css')}}">
    <link rel="stylesheet" href="{{asset('dist/assets/fonts/material.css')}}">
    <link rel="stylesheet" href="{{asset('dist/assets/css/style.css')}}" id="main-style-link">
    <link rel="stylesheet" href="{{asset('dist/assets/css/style-preset.css')}}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link href='https://fonts.googleapis.com/css?family=Noto Sans Thai' rel='stylesheet'>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf-lib/1.17.1/pdf-lib.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
    @yield('style')
</head>

<body data-pc-preset="preset-1" data-pc-direction="ltr" data-pc-theme="light">
    @include('include.header')
    <div class="pc-container">
        <div class="pc-content">
            @yield('content')
        </div>
    </div>
</body>

</html>
<script src="{{asset('dist/assets/js/plugins/popper.min.js')}}"></script>
<script src="{{asset('dist/assets/js/plugins/simplebar.min.js')}}"></script>
<script src="{{asset('dist/assets/js/plugins/bootstrap.min.js')}}"></script>
<script src="{{asset('dist/assets/js/fonts/custom-font.js')}}"></script>
<script src="{{asset('dist/assets/js/pcoded.js')}}"></script>
<script src="{{asset('dist/assets/js/plugins/feather.min.js')}}"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.10.377/pdf.min.js"></script>

@yield('script')