<?php
namespace src\classes;

use src\classes\Models\File;

class ImageHelper {
    public static $IMAGE_FOLDER_LOCATION = "UNKNOWN";
    public static $EXTERNAL_IMAGE_LOCATION = "http://iproject16.icasites.nl/";

    /**
     * @param $file File
     * @return string
     */
    public function getImageLocation($file) {
        if($file !== null) {
            $fileLocation = $file->getFileLocation();
            if(!empty($fileLocation)) {
                return $fileLocation;
            } else {
                $fileLocation = $this->findImageLocation($file);
                if(!empty($fileLocation)) {
                    $file->setFileLocation($fileLocation);
                    $file->save(); //To cache the image location
                }

                return $fileLocation;
            }
        }

        return false;
    }

    private function findImageLocation($file) {
        $externalTypeLocations = array("thumbnails", "pics");
        $fileFoundLocation = "";
        foreach ($externalTypeLocations as $externalTypeLocation) {
            $location = $this::$EXTERNAL_IMAGE_LOCATION . "$externalTypeLocation/" . $file->getFileName();
            if ($this->isAbsolutePathCorrect($location)) {
                $fileFoundLocation = $location;
                break;
            }
        }

        return $fileFoundLocation;
    }

    /**
     * @param $filePath
     * @return bool
     */
    private function isAbsolutePathCorrect($filePath) {
        $file_headers = @get_headers($filePath);
        if($file_headers[0] !== 'HTTP/1.1 404 Not Found') {
            return true;
        }

        return false;
    }
}