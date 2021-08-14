<?php


namespace f2sfund\face_recognition;


class KycUtilController extends ValidationVideoController
{
    public function rejectKYC($userId, $faceId, $isFaceIdRemove) {

        try {
            app(FaceUploadService::class)->removeImageFromBucket($userId);

            if ($isFaceIdRemove) {
                $client = $this->getRekognitionClient();

                $results = $client->deleteFaces([
                    'CollectionId' => env('FACE_COLLECTION'),
                    'FaceIds' => [
                        $faceId
                    ]
                ]);

            }
            return true;
        } catch (\Throwable $throwable) {
            error_log($throwable->getMessage());
            return false;
        }
    }
}
