<?php


namespace F2SFund\FaceRekognition;


use App\Http\Controllers\Controller;
use App\Http\Controllers\Payment\PaymentController;
use Aws\Rekognition\RekognitionClient;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\URL;
use Intervention\Image\Facades\Image;

class ValidationVideoController extends Controller
{

    protected $frontKey = 'FrontSide', $leftKey = 'LeftSide', $rightKey = 'RightSide';
    protected $isFaceOkay = 0, $captureCount = 2, $attemptCount = 3;

    protected $collectionName;
    protected $imageName = 'Mimo_Image.png';
    protected $thresholdValue = 90;
    protected $isFaceExist = false;
    protected $faceStartRatio = 10, $faceEndRatio = 55;
    protected $face_id = '';

    // Response codes
    protected $faceOkay = 1, $faceNotMatched = 2, $faceAlreadyExist = 3, $faceNotFound = 4, $faceNotContains = 5, $faceMultipleContains = 6;

    protected function getRekognitionClient() {

        return new RekognitionClient([
            'region'    => env('AWS_DEFAULT_REGION', ''),
            'version'   => 'latest',
            'credentials' => [
                'key' => env('AWS_ACCESS_KEY_ID', ''),
                'secret' => env('AWS_SECRET_ACCESS_KEY', '')
            ]
        ]);
    }

    protected function setCookie($key, $value) {
        setcookie($key, '' . $value, time() + 1800);
    }

    protected function getCookie($key){
        return $this->hasCookieData($key) ? $_COOKIE[$key] : 0;
    }

    protected function hasCookieData($key) {
        return isset($_COOKIE[$key]);
    }

    protected function getFaceValidationProgress($userId) {
        $key = $userId . '_progress';
        return $this->getCookie($key);
    }

    protected function setFaceValidationProgress($userId, $value) {
        $key = $userId . '_progress';
        $this->setCookie($key, $value);
    }

    protected function resetAll($userId) {
        if ($this->getCookie($userId) == $this->attemptCount) {
            $this->setCookie($userId, 0);
        }
    }

    protected function deleteFiles($userId) {
        $extention = '.png';
        $image = __DIR__ . '/images/' . $userId . $extention;
        $image_1 = __DIR__ . '/images/' . $userId . '_1' . $extention;
        $image_2 = __DIR__ . '/images/' . $userId . '_2' . $extention;
        $image_3 = __DIR__ . '/images/' . $userId . '_3' . $extention;
        $image_4 = __DIR__ . '/images/' . $userId . '_4' . $extention;
        $image_5 = __DIR__ . '/images/' . $userId . '_5' . $extention;
        $image_6 = __DIR__ . '/images/' . $userId . '_6' . $extention;
        $files = array($image, $image_1, $image_2, $image_3, $image_4, $image_5, $image_6);
        File::delete($files);
    }

    protected function getFaceId($userId) {
        $client = $this->getRekognitionClient();

        $regFile = __DIR__ . '/images/' . $userId . '.png';
        $regImage = fopen($regFile, 'r');
        $regImageByte = fread($regImage, filesize($regFile));

        $results = $client->searchFacesByImage([
            'CollectionId' => env('FACE_COLLECTION'),
            'FaceMatchThreshold' => $this->thresholdValue,
            'Image' => [
                'Bytes' => $regImageByte
            ]
        ]);

        if (sizeof($results) > 0) {
            $data = $results['FaceMatches'];

            if ($data && sizeof($data) > 0) {
                foreach ($data as $faceInfo) {
                    $similarity = $faceInfo['Similarity'];
                    $face = $faceInfo['Face'];
                    if ($similarity >= $this->thresholdValue) {
                        return $face['FaceId'];
                    }
                }
            }
        }
        return false;
    }

    protected function checkFaceIsExistInCollection($regImageByte, $isReg, $user_id ,$packageId = 0) {

        $client = $this->getRekognitionClient();

        $results = $client->searchFacesByImage([
            'CollectionId' => env('FACE_COLLECTION'),
            'FaceMatchThreshold' => $this->thresholdValue,
            'Image' => [
                'Bytes' => $regImageByte
            ]
        ]);

        $isMatched = $isReg ? $this->faceOkay : $this->faceNotFound;

        if (sizeof($results) > 0) {
            $data = $results['FaceMatches'];

            if ($data && sizeof($data) > 0) {
                foreach ($data as $faceInfo) {
                    $similarity = $faceInfo['Similarity'];
                    $face = $faceInfo['Face'];
                    if ($similarity >= $this->thresholdValue) {
                        $isMatched = $isReg ? $this->faceAlreadyExist : $this->faceOkay;
                        $this->face_id = $face['FaceId'];
                        break;
                    }
                }
            }
        }

        if ($isMatched == $this->faceAlreadyExist && !empty($this->face_id) && $packageId != 0) {
            try {


                $response  = app(PaymentController::class)->isFaceExist($packageId, $this->face_id, $user_id);

                error_log(json_encode($response));
                if ($response['success']) {

                    if ($response['body']) {
                        error_log('Face: exist');
                    } else {
                        error_log('Face: not exist');
                        $isMatched = $this->faceOkay;
                        $this->isFaceExist = true;
                    }
                } else {
                    error_log('Error: ' . $response['body']);
                }

            } catch (\Throwable $throwable) {
                error_log($throwable->getMessage());
                error_log($throwable->getLine());
            }
        }

        return $isMatched;
    }

    protected function storeExistsImage($userId) {
        try {

//            $path = 'https://i.ibb.co/Ct2j9kv/DSC-4735-1.jpg';
            $path = app(FaceUploadService::class)->getSavedImagePath($userId);
            $filename = basename($path);

            $regFile = __DIR__ . '/images/' . $filename;
            Image::make($path)->save($regFile);

        } catch (\Throwable $t) {
            error_log($t->getMessage());
            error_log($t->getFile());
            error_log($t->getLine());
        }
    }

    protected function compareFace($userId, $targetImage) {
        return $this->compareFaceProcess($userId, '_1', $targetImage);
    }

    protected function compareFaceProcess($userId, $imageId, $targetImage) {
        $client = $this->getRekognitionClient();

        $frontImageName = $userId . $imageId . '.png';
        $frontFile = __DIR__ . '/images/' . $frontImageName;

        $frontImage = fopen($frontFile, 'r');
        $frontImageByte = fread($frontImage, filesize($frontFile));

        $results = $client->compareFaces([
            'SimilarityThreshold' => $this->thresholdValue,
            'SourceImage' => [
                'Bytes' => $frontImageByte
            ],
            'TargetImage' => [
                'Bytes' => $targetImage
            ]
        ]);

        $similarity = 0;
        if (sizeof($results) > 0) {
            $data = $results['FaceMatches'];
            if ($data && sizeof($data) > 0) {
                $arrayData = $data[0];
                $similarity = $arrayData['Similarity'];
            }
        }

        $message = 'Matched ' . $similarity;
        $this->storeLog($message);
        if ($similarity >= $this->thresholdValue) {
            return $this->faceOkay;
        }

        return $this->faceNotMatched;
    }

    protected function collectionOperationControll() {

        $client = $this->getRekognitionClient();

        try {
            $result = $client->deleteCollection([
                'CollectionId' => env('FACE_COLLECTION')
            ]);
            error_log($result);
        } catch (\Throwable $e) {
            error_log($e);
        }
        error_log('////////////////////////////////////////');

        $result = $client->createCollection([
            'CollectionId' => env('FACE_COLLECTION')
        ]);

        error_log($result);
    }

    public function storeLog($logtext) {
        try {
            /*$logFilePath = __DIR__ . '/images/' . 'log.txt';
            $logData = '';

            if (file_exists($logFilePath)) {
                $file = fopen($logFilePath, "r");
                while(!feof($file)) {
                    error_log($logData);
                    $logData = $logData . fgets($file). "\n";
                }
                fclose($file);
            }

            $logData = $logData . '[' . date('Y-m-d  H:i:s') . ']  ' . $logtext;
            file_put_contents($logFilePath, $logData);*/

            error_log($logtext);
        } catch (\Throwable $e) {
            error_log($e);
        }
    }

}
