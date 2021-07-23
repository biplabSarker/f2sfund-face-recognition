<!-- <!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>F2S Funds</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.7/css/bootstrap.min.css"/>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>

    <link rel="stylesheet" href="{{URL::asset('style/styles.css')}}">

</head>
<body> -->
<style>
    canvas{
        width:0;
        height:0;
    }
</style>
@extends( (View::exists('web.master')) ? 'web.master' : 'calculator::kyc')
@section('content')
    <div class="page-container d-flex">
        @include( (View::exists('web.layout.user_side_nav')) ? 'web.layout.user_side_nav' : 'calculator::kyc')
        {{--    @if(Auth::user()->role_id == USER_ROLE_SUPER_ADMIN)--}}
        {{--        @include('web.layout.side_nav')--}}
        {{--    @else--}}
        {{--        @include('web.layout.user_side_nav')--}}
        {{--    @endif--}}
        <div class="main-content-area px-2 h-100">
            <div class="container-fluid pt-4">
                <div class="row">
                    <div class="col-12 mb-4">
                        <div class="card border-0 w-100 navy-blue-bg">
                            <div class="card-header">
                                <h5 class="text-capitalize">KYC Face Recognition</h5>
                            </div>
                        </div>
                        <div class="text-center my-4">
                            <div class="content-center">
                                @if($exists)
                                    <img id="g_image" src="{{$image_path}}" height="400" width="400">
                                @else
                                    <div id="g_video" class="progress-bar-holder position-relative" data-pct="0">
                                        <svg class="progress-ring" width="100%" height="100%">
                                            <circle class="progress-ring-circle" id="circular_progress" style="stroke-dasharray: 905, 905; stroke-dashoffset: 452.5; stroke: rgb(211, 25, 13);" stroke-width="4%" stroke="#FFFFFF" fill="transparent" r="48%" cx="50%" cy="50%"></circle>
                                        </svg>
                                        <div class="face-catcher position-absolute">
                                            <video id="video" class="position-absolute h-100" autoplay playsinline loop>
                                                <source src="images/demo-video.mp4" type="video/mp4" />
                                            </video>
                                        </div>
                                    </div>
                                @endif
                            </div>
                            <form method="post" id="registration_form" enctype="multipart/form-data">
                                @csrf
                                <div class="form-group">
                                    <input name="hidden_image_data" id='hidden_image_data' type="hidden"/>
                                </div>

                                <div class="form-group">
                                    <input name="hidden_image_id" id='hidden_image_id' type="hidden"/>
                                </div>

                                <div class="form-group">
                                    <input name="hidden_init_status" id='hidden_init_status' type="hidden"/>
                                </div>
                            </form>
                            <canvas hidden id="canvas"></canvas>
                            <div class="captured-image text-center my-4">
                                @if(!$exists)
                                    <div class="captured-alert blue-bg p-2 mb-3">
                                        <img class="mb-2" width="50" src="{{asset('images/home/alert.png')}}" alt="alert"/>
                                        <p id="marked_info"> Please note that 3 wrong attempts will suspend your account. You have only 2 attempts left.</p>
                                    </div>
                                @endif
                                <p class="font-weight-bold" id="message_data">
                                    @if($exists)
                                        <span>Your face is already exist. Thank you.</span>
                                    @else
                                        <span>Please place your face close to the camera and start the capture</span>
                                    @endif
                                    {{--                                <span>Please place your face close to the camera and start the capture</span>--}}
                                    {{--                                <span class="text-Mandy" >You don't have any camera</span>--}}
                                </p>
                                @if(!$exists)
                                    <h5 class="text-Scooter my-2 error-message text-center" id="guide_message_alert"><strong>N.B.</strong> Please ensure sufficient lighting and bring your face close to the oval face shape on the camera</h5>
{{--                                    <button onclick="actionRetry()" class="btn btn-blue-1 text-white btn-lg my-3">Go Home</button>--}}
                                    <a href="{{ url('dashboard') }}" class="btn btn-blue-1 text-white btn-lg my-3">Go Home</a>
                                @endif
                                @if($exists)
                                    <form id="exist_form" method="post" action="/exist-status-submit">
                                        <input type="hidden" value="{{$user_id}}" name="exist_user_id" id="exist_user_id">
                                        <input type="hidden" value="@if($face_id) {{$face_id}} @endif" name="exist_face_id"
                                               id="exist_face_id">
                                        <button type="submit" class="btn btn-success btn-lg" id="okaybutton">OK</button>
                                    </form>
                                @else
                                    <button class="btn btn-green text-white btn-lg my-3" id="startbutton" disabled>Capture and Submit</button>
                                @endif

                            </div>
                        </div>
                        <input type="hidden" value="@if($package_id) {{$package_id}} @endif" name="packageid" id="packageid">
                        <input type="hidden" value="{{$user_id}}" name="user_id" id="user_id">
                        <input type="hidden" value="" name="face_id" id="face_id">
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script type="text/javascript" src="{{asset('js/face-api.min.js')}}"></script>
    <script type="text/javascript" src="{{asset('js/face_rekognition.js')}}"></script>

@endsection
{{--    </body>--}}
{{--    </html>--}}
