<!doctype html>
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

    <script>
        /* JS comes here */

        var width = 200; // We will scale the photo width to this
        var height = 200; // This will be computed based on the input stream

        var streaming = false;

        var processing = 0, error = 1, success = 2, captureCount = 3;
        var g_front = 0, g_right = 1, g_left = 2, g_okay = 3;

        var video = null;
        var startbutton = null;
        var canvas = null;
        var messageData = null;
        var guideInfo = null;

        var existStartbutton = null;
        var recordCount = 0, imageId = 0;
        var userIdValue = null;

        function actionRetry() {
            window.location.reload();
        }

        function startup() {
            video = document.getElementById('video');
            canvas = document.getElementById('canvas');
            startbutton = document.getElementById('startbutton');
            messageData = document.getElementById('message_data');
            guideInfo = document.getElementById('guide_img');
            userIdValue = document.getElementById('user_id');

            existStartbutton = document.getElementById('exist_startbutton');

            navigator.mediaDevices.getUserMedia({
                video: { zoom: true },
                audio: false
            }).then(function (stream) {
                video.srcObject = stream;

                const [track] = stream.getVideoTracks();
                track.applyConstraints({advanced: [ {zoom: 180} ]});

                video.play();
            }).catch(function (err) {
                if (err.message === 'Requested device not found') {
                    messageModifier(1, 'You don\'t have any camera');
                    $('#startbutton').hide();
                }
                console.log("An error occurred: " + err);
            });

            video.addEventListener('canplay', function (ev) {
                if (!streaming) {
                    height = video.videoHeight / (video.videoWidth / width);

                    if (isNaN(height)) {
                        height = 400;
                    }

                    width = 200;
                    height = 200;

                    video.setAttribute('width', width);
                    video.setAttribute('height', height);
                    canvas.setAttribute('width', width);
                    canvas.setAttribute('height', height);

                    streaming = true;
                }
            }, false);

            startbutton.addEventListener('click', function (ev) {
                resetAll();
                autoRecord();
                ev.preventDefault();
            }, false);

            clearphoto();
        }

        function resetAll() {
            $('#retry').hide();
            recordCount = 0;
            imageId = 0;
        }

        function clearphoto() {
            var context = canvas.getContext('2d');
            context.fillStyle = "#AAA";
            context.fillRect(0, 0, canvas.width, canvas.height);
        }

        function autoRecord() {
            recordCount = recordCount + 1;
            if (recordCount < 6) {

                if (recordCount === 1 || recordCount === 2 || recordCount === 4) {
                    imageId = imageId + 1;
                    capturePhoto(imageId);
                }
                // capturePhoto(recordCount);
                conditionMessageModifier(recordCount);
                if (recordCount === 4) {
                    setTimeout(autoRecord, 1000);
                } else {
                    setTimeout(autoRecord, 2500);
                }
            }
        }

        function capturePhoto(imageId) {
            var context = canvas.getContext('2d');
            if (width && height) {
                canvas.width = width;
                canvas.height = height;

                context.translate(width, 0);
                context.scale(-1, 1);

                context.drawImage(video, 0, 0, width, height);

                var data = canvas.toDataURL('image/png');

                document.getElementById('hidden_image_data').value = data;
                document.getElementById('hidden_image_id').value = imageId;

                if (imageId === 1) {
                    document.getElementById('hidden_init_status').value = 1;
                } else {
                    document.getElementById('hidden_init_status').value = 0;
                }

                capture_data_send(imageId);
            } else {
                clearphoto();
            }
        }

        function capture_data_send(imageId) {
            let form = $("#registration_form");
            let url = form.attr('action');

            startbutton.disabled = true;

            /*if (imageId === captureCount) {
                console.log("Start Process");
                progressData();
            }*/

            $.ajax({
                type: "POST",
                url: url,
                data: form.serialize(), // serializes the form's elements.
                success: function (data) {
                    if (data.code !== -1) {
                        if (data.status === 1) {
                            guideModifier(g_okay);
                        } else if (data.status === 2) {
                            startbutton.disabled = false;
                            $('#retry').show();
                        } else {
                            $('#retry').show();
                        }
                        messageModifier(data.code, data.message);
                    } else {
                        console.log(data.message);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.log(textStatus, errorThrown);
                }
            });
        }

        var i = 0;
        function progressData() {
            let progressForm = $("#progress_form");
            let progressUrl = progressForm.attr('action');

            $.ajax({
                type: "POST",
                url: progressUrl,
                data: progressForm.serialize(), // serializes the form's elements.
                success: function (data) {
                    /*if (parseInt(data.progress) === 0) {

                    }*/
                },
                complete: function() {
                    i = i + 1;
                    console.log('Count ' + i);
                    if (i < 25) {
                        setTimeout(progressData, 1500);
                    }
                }
            });
        }


        function messageModifier(condition, message) {
            if (condition === 0) {
                messageData.style.color = "#12AC68";
            } else if (condition === 1) {
                messageData.style.color = "#D3190D";
            } else {
                messageData.style.color = "#6a67ce";
            }
            messageData.innerHTML = message;
        }

        function conditionMessageModifier(state) {
            if (state === 1) {
                guideModifier(g_right);
                messageModifier(2, 'Slowly turn your head right.')
            } else if (state === 2) {
                guideModifier(g_front);
                messageModifier(2, 'Slowly turn your head back to the centre.')
            } else if (state === 3) {
                guideModifier(g_left);
                messageModifier(2, 'Slowly turn your head left.')
            } else if (state === 4) {
                guideModifier(g_front);
                messageModifier(2, 'Slowly turn back to the centre.')
            } else if (state === 5) {
                guideModifier(g_front);
                messageModifier(0, 'Processing, please wait....')
            }
        }

        function guideModifier(condition) {
            if (condition === g_front) {
                guideInfo.src = "{{asset('images/home/front.png')}}";
            } else if (condition === g_right) {
                guideInfo.src = "{{asset('images/home/right.png')}}";
            } else if (condition === g_left) {
                guideInfo.src = "{{asset('images/home/left.png')}}";
            } else if (condition === g_okay) {
                startbutton.disabled = true;
                guideInfo.src = "{{asset('images/home/okay_green.png')}}";
                setTimeout(statusSubmit, 2000);
            }
        }

        function statusSubmit() {
            window.location = window.location.protocol + '//' + window.location.hostname + ':' + window.location.port + "/status-withdraw-submit/" + userIdValue.value;
        }

        window.addEventListener('load', startup, false);

    </script>

    <style>

        video {
            -webkit-transform: scaleX(-1);
            transform: scaleX(-1);
            width: 100%;
            height: 100%;
        }

        .video-frame {
            position: relative;
            overflow: hidden;
            height: 1px;
            left: 0;
            top: 0;
            width: 100%;
            padding-bottom: 75%;
            margin-bottom: -1px;
        }

        .video-frame img {
            top: 50%;
            left: 50%;
            position: absolute;
            width: 100%;
            min-height: 100%;
            transform: translate(-50%, -50%);
        }

        .video-frame button {
            position: absolute;
            bottom: 10px;
            margin: 0 auto;
            left: 0;
            right: 0;
        }

        .padding-reset {
            padding-left: 0;
            padding-right: 0;
        }

        .captured-image {
            padding-top: 7%;
            display: flex;
            flex-flow: column nowrap;
            align-items: center;
            align-content: space-around;
            justify-content: center;
        }

        .captured-image * {
            padding-top: 15px;
        }

        .captured-image .message-info {
            color: #6a67ce;
            font-size: 20px;
            font-weight: 700;
            padding-left: 15px;
            padding-right: 15px;
        }

        .guide-image {
            width: 175px;
            height: 185px;
        }

        .opacity-control {
            opacity: 0.7;
        }

    </style>

</head>
<body>
@extends( (View::exists('layouts.kyc_master')) ? 'layouts.kyc_master' : 'calculator::kyc')
@section('content')
    <div class="container">

        <div class="row">
            <div class="col-md-6 padding-reset">
                <div class="video-frame">
                    <div class="camera">
                        <video id="video" autoplay loop playsinline>Video stream not available.</video>
                    </div>

                    <img class="opacity-control" src="{{asset('images/home/video_overlay.png')}}"/>

                    <form method="post" id="registration_form"
                          enctype="multipart/form-data">
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
                    <button class="btn btn-success btn-lg" style="" id="startbutton">Capture and Submit</button>

                </div>
            </div>
            <div class="col-md-6 padding-reset">
                <div class="captured-image">

                    <img class="guide-image" id="guide_img" src="{{asset('images/home/front.png')}}">
                    <p class="captured-info message-info text-center" id="message_data">Please take your valid profile picture and submit it.</p>

                    <input type="hidden" value="{{$user_id}}" name="user_id" id="user_id">

                    <input type="button" class="btn btn-success btn-lg" style="display: none" onclick="actionRetry()" value="Retry" id="retry" />
                </div>
            </div>
        </div>
    </div>
@endsection
</body>
</html>
