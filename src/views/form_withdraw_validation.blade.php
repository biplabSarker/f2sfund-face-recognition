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
{{--@extends( (View::exists('layouts.kyc_master')) ? 'layouts.kyc_master' : 'calculator::kyc')--}}
@extends((View::exists('web.master')) ? 'web.master' : 'calculator::kyc')
@section('content')
    <div class="page-container d-flex">
        @include( (View::exists('web.layout.user_side_nav')) ? 'web.layout.user_side_nav' : 'calculator::kyc')
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
                                <p class="captured-info message-info text-center" id="message_data">
                                    Please place your face close to the camera and start the capture
                                </p>
                                <h5 class="error-message text-center" id="guide_message_alert"><strong>N.B.</strong> Please ensure sufficient lighting and bring your face close to the oval face shape on the camera</h5>
                                <button class="btn btn-success btn-lg my-3" id="startbutton" disabled>Capture and Submit</button>
                            </div>
                        </div>
                        <input type="hidden" value="{{$user_id}}" name="user_id" id="user_id">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script type="text/javascript" src="{{asset('js/face-api.min.js')}}"></script>
    <script type="text/javascript" src="{{asset('js/face_withdraw.js')}}"></script>

@endsection

<!-- </body>
</html> -->