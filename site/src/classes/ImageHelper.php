<?php
namespace src\classes;

use src\classes\Models\File;

class ImageHelper {
    public static $IMAGE_FOLDER_LOCATION = "UNKNOWN";
    public static $EXTERN_IMAGE_LOCATION = "http://iproject16.icasites.nl/";

    /**
     * @param $file File
     * @return string
     */
    public function getImageLocation($file) {
        if($file !== null) {
            $externTypeLocations = array("thumbnails", "pics");
            $fileFoundLocation = "";
            foreach ($externTypeLocations as $externTypeLocation) {
                $location = $this::$EXTERN_IMAGE_LOCATION . "$externTypeLocation/" . $file->getFileName();
                if ($this->isAbsolutePathCorrect($location)) {
                    $fileFoundLocation = $location;
                    break;
                }
            }

            return $fileFoundLocation;
        }

        return false;
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