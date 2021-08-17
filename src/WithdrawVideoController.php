<?php

namespace W3Engineers\FaceRekognition;
//require 'vendor/autoload.php';
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class WithdrawVideoController extends ValidationVideoController
{

    public function showForm($userId = '') {

        if ($userId) {

            $this->imageName = $userId;

            $this->deleteFiles($this->imageName);
            $this->resetAll($userId);
            return view('calculator::form_withdraw_validation', ['user_id' => $userId]);
        } else {
            return redirect()->back();
        }
    }

    public function storeFaces(Request $request, $userId = '') {
        $this->imageName = $userId;

        define(__DIR__, './images/');
        $regImageString = $request->input('hidden_image_data');
        $regImageId = $request->input('hidden_image_id');

        $regImageString = str_replace('data:image/png;base64,', '', $regImageString);
        $regImageString = str_replace(' ', '+', $regImageString);
        $regData = base64_decode($regImageString);

        $regImageName = $this->imageName . '_' . $regImageId . '.png';

        $regFile = __DIR__ . '/images/' . $regImageName;
        file_put_contents($regFile, $regData);

        if ($regImageId == $this->captureCount) {

            $isExist = app(FaceUploadService::class)->isImageExist($this->imageName);

            if ($isExist) {
                $this->storeExistsImage($this->imageName);

                $this->storeLog('UserId: ' . $userId);
                $response = $this->startDataProcess($userId, 1);
            } else {
                $response = $this->finalAction($this->faceNotFound, $userId);
            }
        } else {
            $response = [
                'message' => 'Processing, please wait....',
                'code' => -1,
                'status' => 0
            ];
        }

        return $response;
    }

    private function startDataProcess($userId, $regImageId)
    {
        try {

            $regFile = __DIR__ . '/images/' . $userId . '_' . $regImageId . '.png';
            if (file_exists($regFile)) {

                $client = $this->getRekognitionClient();

                $regImage = fopen($regFile, 'r');
                $regImageByte = fread($regImage, filesize($regFile));

                try {
                    $results = $client->detectFaces([
                        'Image' => [
                            'Bytes' => $regImageByte
                        ]
                    ]);
                } catch (\Throwable $t) {
                    $this->storeLog('Error >> ' . $t->getMessage());
                }

                if (sizeof($results) > 0) {

                    $FaceDetails = $results['FaceDetails'];
                    if ($FaceDetails && sizeof($FaceDetails) > 0) {

                        if (sizeof($FaceDetails) == 1) {

                            $arrayData = $FaceDetails[0];
                            $BoundingBox = $arrayData['BoundingBox'];
                            $Pose = $arrayData['Pose'];

                            $Left = $BoundingBox['Left'];
                            $Yaw = $Pose['Yaw'];

                            $faceRatio = $Left * 100;
                            $this->storeLog('Ratio: ' . $faceRatio);

                            if ($faceRatio >= $this->faceStartRatio && $faceRatio <= $this->faceEndRatio) {

                                $this->storeLog('Yaw: ' . $Yaw);

                                if ($regImageId == 1) {

                                    $this->isFaceOkay = $this->compareFaceProcess($userId, "", $regImageByte);

                                    if ($this->isFaceOkay == $this->faceOkay) {
                                        $this->isFaceOkay = $this->checkFaceIsExistInCollection($regImageByte, false, $userId);
                                    }
                                } else {
                                    $this->isFaceOkay = $this->compareFace($userId, $regImageByte);
                                }
                            } else {
                                $this->isFaceOkay = $this->faceNotContains;
                            }

                        } else {
                            $this->isFaceOkay = $this->faceMultipleContains;
                        }
                    } else {
                        $this->isFaceOkay = $this->faceNotContains;
                    }
                } else {
                    $this->isFaceOkay = $this->faceNotContains;
                }

                $this->storeLog('Status: ' . $this->isFaceOkay);

                if ($this->isFaceOkay == $this->faceOkay) {

                    $latestId = $regImageId + 1;
                    $this->storeLog('New Id: ' . $latestId);
                    return $this->startDataProcess($userId, $latestId);
                } else {
                    $status = $this->finalAction($this->isFaceOkay, $userId);
                    if ($status) {
                        return $status;
                    }
                }

            } else {
                $status = $this->finalAction($this->faceOkay, $userId);
                if ($status) {
                    return $status;
                }
            }

        } catch (\Throwable $t) {
            $this->storeLog('Error: ' . $t->getMessage());

            $this->deleteFiles($userId);
            $this->resetAll($userId);

            return [
                'message' => 'Please try again',
                'code' => 2,
                'status' => 2
            ];
        }
    }

    private function finalAction($status, $userId) {
        error_log('Face Validation status ' . $status);

        $message = 'All face captures are looking good';
        $color_code = 2;
        $response_status = 0;
        if ($status == $this->faceOkay) {

            $message = 'All face captures are looking good';
            $color_code = 2;
            $response_status = 1;
        } else if ($status == $this->faceNotMatched) {
            $message = 'Face captures are not matched';
            $color_code = 1;
        } else if ($status == $this->faceAlreadyExist) {
            $message = 'We have already recognized your face with another account. This account requires separate facial verification';
            $color_code = 1;
        } else if ($status == $this->faceNotFound) {
            $message = 'Valid face not found';
            $color_code = 1;
        } else if ($status == $this->faceNotContains) {
            $message = 'Face not properly recognized, please try and submit again';
            $color_code = 1;
        } else if ($status == $this->faceMultipleContains) {
            $message = 'Multiple faces found, please submit only your face';
            $color_code = 1;
        }

        $this->deleteFiles($userId);

        return [
            'message' => $message,
            'code' => $color_code,
            'status' => $response_status
        ];
    }

    public function getProgess(Request $request) {
        error_log('id: ' . $request->input('user_id'));
        return [
            'progress' => $this->getFaceValidationProgress($request->input('user_id'))
        ];
    }

    public function statusSubmit($userId = '') {
        $imagePath = app(FaceUploadService::class)->getSavedImagePath($userId);
        $response = [
            'data' => $imagePath
        ];
        $this->deleteFiles($userId);
        return redirect()->route('withdraw_balance', $response)->with(['report' => 1]);
    }
}
