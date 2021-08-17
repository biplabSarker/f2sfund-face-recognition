<?php

namespace W3Engineers\FaceRekognition;
//require 'vendor/autoload.php';
use Illuminate\Http\Request;

class RegistrationVideoController extends ValidationVideoController
{

    public function showForm($userId = '', $packageId = '') {

        if ($userId) {

            $this->imageName = $userId;

            $this->resetAll($userId);
            $userImage = '';
            $faceId = false;

            $exists = app(FaceUploadService::class)->isImageExist($this->imageName);
            if ($exists) {
                $exists = true;
                $userImage = app(FaceUploadService::class)->getSavedImagePath($userId);

                $this->storeExistsImage($userId);

                $faceId = $this->getFaceId($userId);
            }
            $this->deleteFiles($this->imageName);
            return view('calculator::form_validation', ['exists' => $exists, 'user_id' => $userId,
                'image_path' => $userImage.'?t='.time(), 'package_id' => $packageId, 'face_id' => $faceId]);
        } else {
            return redirect()->back();
        }
    }

    public function storeFaces(Request $request, $userId = '', $packageId = '') {
        $this->imageName = $userId;

        define(__DIR__, './images/');
        $regImageString = $request->input('hidden_image_data');
        $regImageId = $request->input('hidden_image_id');
        $initStatus = $request->input('hidden_init_status');

        $regImageString = str_replace('data:image/png;base64,', '', $regImageString);
        $regImageString = str_replace(' ', '+', $regImageString);
        $regData = base64_decode($regImageString);

        $regImageName = $this->imageName . '_' . $regImageId . '.png';

        $regFile = __DIR__ . '/images/' . $regImageName;
        file_put_contents($regFile, $regData);

        if ($regImageId == $this->captureCount) {
            $this->storeLog('UserId: ' . $userId);
            $response = $this->startDataProcess($userId, 1, $packageId);
        } else {
            $response = [
                'message' => 'Processing, please wait....',
                'code' => -1,
                'status' => 0,
                'attempts' => 0
            ];
        }

        return $response;
    }

    private function startDataProcess($userId, $regImageId, $packageId)
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
                                    $this->isFaceOkay = $this->checkFaceIsExistInCollection($regImageByte, true, $userId, $packageId);
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
                    $this->storeLog('New Image Id: ' . $latestId);
                    return $this->startDataProcess($userId, $latestId, $packageId);
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
                'status' => 2,
                'attempts' => 0
            ];
        }
    }

    private function finalAction($status, $userId) {
        $this->storeLog('Face Validation status ' . $status);

        $message = 'All face captures are looking good';
        $color_code = 2;
        $response_status = 0;
        $attempts = 0;
        $face_id = '';

        if ($status == $this->faceOkay) {

            $frontImageName = $userId . '_1' . '.png';
            $frontFile = __DIR__ . '/images/' . $frontImageName;

            // TODO
            app(FaceUploadService::class)->uploadFace($frontFile, $userId, 300, 300);
            $this->storeLog('Image Saved');

            $this->storeLog('Face exist ' . $this->isFaceExist);
            if (!$this->isFaceExist) {
                $face_id = $this->addInCollection($userId);
                $this->storeLog('Face Saved');
            } else {
                $face_id = $this->face_id;
                $this->storeLog('Existing Face Saved');
            }

            $message = 'All face captures are looking good';
            $color_code = 2;
            $response_status = 1;
        } else if ($status == $this->faceNotMatched) {
            $message = 'Face captures are not matched';
            $color_code = 1;
        } else if ($status == $this->faceAlreadyExist) {
            $attempts = $this->getCookie($userId);
            $attempts = intval($attempts) + 1;

            $message = 'We have already recognized your face with another account. This account requires separate facial verification.';
            $color_code = 1;

            if ($attempts < $this->attemptCount) {
                $this->setCookie($userId, $attempts);
            } else {

                $this->setCookie($userId, $attempts);
                $frontImageName = $userId . '_1' . '.png';
                $frontFile = __DIR__ . '/images/' . $frontImageName;

                app(FaceUploadService::class)->uploadFace($frontFile, $userId, 300, 300, true);
                $this->storeLog('Image Saved');

                $face_id = $this->face_id;
                $message = 'Your account is currently suspended for multiple failure, please contract with F2sFund support team.';
            }

            // TODO
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

        error_log('Attempt: ' . $attempts);
        $this->deleteFiles($userId);

        return [
            'message' => $message,
            'code' => $color_code,
            'status' => $response_status,
            'face_id' => $face_id,
            'attempts' => $attempts
        ];
    }

    private function addInCollection($imageName) {

        $client = $this->getRekognitionClient();

        $frontImageName = $imageName . '_1' . '.png';
        $frontFile = __DIR__ . '/images/' . $frontImageName;
        $frontImage = fopen($frontFile, 'r');
        $frontImageByte = fread($frontImage, filesize($frontFile));
        $face_id = '';
        $res = $client->indexFaces([
            'CollectionId' => env('FACE_COLLECTION'),
            'Image' => [
                'Bytes' => $frontImageByte
            ]
        ]);

        $FaceRecords = $res['FaceRecords'];
        if ($FaceRecords && sizeof($FaceRecords) > 0) {
            $arrayData = $FaceRecords[0];
            $Face = $arrayData['Face'];
            $face_id = $Face['FaceId'];
            error_log($face_id);
        }
        $this->storeLog("Add Collection Done");
        return $face_id;
    }

    public function getProgess(Request $request) {
        error_log('id: ' . $request->input('user_id'));
        return [
            'progress' => $this->getFaceValidationProgress($request->input('user_id'))
        ];
    }

    public function statusSubmit($userId = '', $faceId = '') {
        $imagePath = app(FaceUploadService::class)->getSavedImagePath($userId);
        $response = [
            'data' => $imagePath,
            'f_info' => $faceId
        ];
        $this->deleteFiles($userId);
        return redirect()->route('profile.edit', $response)->with(['report' => 1]);
    }

    public function attemptsError($userId = '') {
        $response = [
            'userId' => $userId
        ];
        return redirect()->route('profile-suspend', $response)->with(['report' => 1]);
    }

    public function existStatusSubmit(Request $request) {
        return $this->statusSubmit($request->input('exist_user_id'), $request->input('exist_face_id'));
    }

    public function collectionOperation() {
        $this->collectionOperationControll();
    }
}
