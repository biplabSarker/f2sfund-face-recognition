<?php

namespace F2SFund\FaceRekognition;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class FaceUploadService
{
    private $subFolder = 'images/users/';
    private $subSuspendFolder = 'images/suspend/';
    private $extension = 'png';

    public function uploadFace($file, $fileName, $width = null, $height = null, $isSuspend = false)
    {
        $file_s3_path = "";
        $fullView = true;

        try {

            $fileObj = $this->createFileObject($file);
            $imageFile = Image::make($fileObj);

            if (!is_null($width) && !is_null($height) && is_int($width) && is_int($height)) {

                if ($fullView) {

                    $imageFile->resize($width, $height, function ($constraint) {
                        $constraint->aspectRatio();
                    });

                    $background = Image::canvas($width, $height);
                    $imageFile = $background->insert($imageFile, 'center');
                } else {
                    $imageFile->fit($width, $height);
                }
            } elseif (!is_null($width) && is_int($width)) {

                $imageFile->resize($width, null, function ($constraint) {
                    $constraint->aspectRatio();
                });

            } elseif (!is_null($height) && is_int($height)) {

                $imageFile->resize(null, $height, function ($constraint) {
                    $constraint->aspectRatio();
                });
            }

            $subDir = $isSuspend ? $this->subSuspendFolder : $this->subFolder;

            $imageFile->encode($this->extension, 100);
            $fileName = $fileName . '.' . $this->extension;
            $path = $subDir . $fileName;

            $stored = Storage::disk('s3')->put($path, $imageFile->__toString(), 'public');
            $file_s3_path = Storage::disk('s3')->url($path);

        } catch (\Throwable $ex) {
            error_log($ex->getMessage());
            error_log($ex->getLine());
        }

        return $file_s3_path;
    }

    public function getSavedImagePath($fileName) {
        $fileName = $fileName . '.' . $this->extension;
        $path = $this->subFolder . $fileName;
        return Storage::disk('s3')->url($path);
    }

    public function isImageExist($fileName) {
        $data = Storage::disk('s3')->exists($this->subFolder . $fileName . '.' . $this->extension);
        if ($data == 1) {
            return true;
        }
        return false;
    }

    public function removeImageFromBucket($userId) {
        $fileName = $userId . '.' . $this->extension;
        $path = $this->subFolder . $fileName;

        $deleted = Storage::disk('s3')->delete($path);
        error_log('Deleted: ' . $deleted);
    }

    public function createFileObject($url){
        $path_parts = pathinfo($url);
        $imgInfo = getimagesize($url);

        $file = new UploadedFile(
            $url,
            $path_parts['basename'],
            $imgInfo['mime'],
            filesize($url),
            true,
            TRUE
        );
        return $file;
    }
}
