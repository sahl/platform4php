<?php
namespace Platform\Utilities;
/**
 * Class for manipulating images
 * 
 * Also responsible for inter-operating with the File class
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=image_class
 */

use Platform\File;
use Platform\Platform;

class Image {
    
    const RESIZE_STRATEGY_FIT = 1;
    const RESIZE_STRATEGY_FILL = 2;
    const RESIZE_STRATEGY_BORDER_WHITE = 3;
    const RESIZE_STRATEGY_BORDER_TRANSPARENT = 4;
    
    private $image_data = null;
    
    private $preserved_name = null;
    
    public function __construct($object = null) {
        if ($object instanceof File) {
            $this->attachFile($object);
        } else if ($object instanceof Image) {
            $this->attachImageData($object->getImageData());
        } else if ($object !== null) {
            trigger_error('Unknown parameter to Image constructor!', E_USER_ERROR);
        }
    }
    
    /**
     * Attach a file to this Image
     * @param \Platform\File $file File to read image from
     * @param bool $soft_fail If this is true, then the function will return false if an image couldn't be read. Otherwise it will throw an error.
     * @return bool True if an image was read from the file.
     */
    public function attachFile(File $file, bool $soft_fail = false) : bool {
        if (! $file->isInDatabase()) {
            if ($soft_fail) return false;
            trigger_error('Tried to attach unsaved file to Image class.', E_USER_ERROR);
        }
        if (! $file->isImage()) {
            if ($soft_fail) return false;
            trigger_error('Tried to attach non-image file to Image class.', E_USER_ERROR);
        }
        $filedata = file_get_contents($file->getCompleteFilename());
        $image = imagecreatefromstring($filedata);
        if ($image === false) {
            if ($soft_fail) return false;
            trigger_error('Couldn\'t parse file data as valid image data.', E_USER_ERROR);
        }
        $this->image_data = $image;
        $this->preserved_name = $file->getFilenameWithoutExtension();
        return true;
    }    
    
    /**
     * Attach a image resource to this Image object
     * @param resource $image Image resource to attach.
     */
    public function attachImageData($image) {
        $this->image_data = $image;
    }
    
    /**
     * Attached this image to a file object as an JPEG file
     * @param \Platform\File $file File object
     * @param int $quality The desired jpeg quality
     */
    public function attachToFileAsJPEG(File $file, int $quality = -1) {
        if ($this->image_data == null) trigger_error('Tried to get image without data.', E_USER_ERROR);
        $temp_file = tempnam(Platform::getConfiguration('dir_temp'), 'platform_img');
        imagejpeg($this->image_data, $temp_file, $quality);
        // Try to preserve filename
        $name_without_extension = $file->getFilenameWithoutExtension();
        if (! $name_without_extension) $name_without_extension = $this->preserved_name ?: 'image';
        $file->attachFile($temp_file);
        $file->filename = $name_without_extension.'.jpg';
        $file->mimetype = 'image/jpeg';
    }

    /**
     * Attached this image to a file object as an PNG file
     * @param \Platform\File $file File object
     */
    public function attachToFileAsPNG(File $file) {
        if ($this->image_data == null) trigger_error('Tried to get image without data.', E_USER_ERROR);
        $temp_file = tempnam(Platform::getConfiguration('dir_temp'), 'platform_img');
        imagepng($this->image_data, $temp_file);
        // Try to preserve filename
        $name_without_extension = $file->getFilenameWithoutExtension();
        if (! $name_without_extension) $name_without_extension = $this->preserved_name ?: 'image';
        $file->attachFile($temp_file);
        $file->filename = $name_without_extension.'.png';
        $file->mimetype = 'image/png';
    }
    
    /**
     * Ensure that a given image can fit inside the given bounds. If the image is
     * smaller than the given size, a border is added.
     * @param int $width Crop width
     * @param int $height Crop height
     * @param int $strategy Strategy
     */
    public function crop(int $width, int $height, int $strategy = self::RESIZE_STRATEGY_FILL) {
        if (! self::isValidStrategy($strategy)) trigger_error('Invalid strategy: '.$strategy, E_USER_ERROR);
        if ($this->image_data == null) trigger_error('Tried operation on image without data.', E_USER_ERROR);
        $current_width = imagesx($this->image_data);
        $current_height = imagesy($this->image_data);
        if ($current_width == $width && $current_height == $height) return;
        $target_image = imagecreatetruecolor($width, $height);
        switch ($strategy) {
            case self::RESIZE_STRATEGY_BORDER_WHITE:
                $white = imagecolorallocate($target_image, 255, 255, 255);
                imagefill($target_image, 1, 1, $white);
                break;
            default:
                imagesavealpha($target_image, true);
                break;
        }
        $source_x = max(0, $current_width/2-$width/2);
        $dest_x = max(0,-($current_width/2-$width/2));
        $source_y = max(0, $current_height/2-$height/2);
        $dest_y = max(0,-($current_height/2-$height/2));
        imagecopy($target_image, $this->image_data, $dest_x, $dest_y, $source_x, $source_y, min($width, $current_width), min($height,$current_height));
        $this->image_data = $target_image;
    }

    /**
     * Display image as PNG
     */
    public function displayPNG() {
        if ($this->image_data == null) trigger_error('Tried to display image without data.', E_USER_ERROR);
        header('Content-type: image/png');
        imagepng($this->image_data);
    }
    
    /**
     * Display image as JPEG
     */
    public function displayJPG() {
        if ($this->image_data == null) trigger_error('Tried to display image without data.', E_USER_ERROR);
        header('Content-type: image/jpeg');
        imagejpeg($this->image_data);
    }
    
    /**
     * Downsize an image to fit inside the given dimensions
     * @param int $width Width
     * @param int $height Height
     * @param int $strategy Resize strategy
     */
    public function downsize(int $width, int $height, int $strategy = self::RESIZE_STRATEGY_FILL) {
        if (! self::isValidStrategy($strategy)) trigger_error('Invalid strategy: '.$strategy, E_USER_ERROR);
        if ($this->image_data == null) trigger_error('Tried operation on image without data.', E_USER_ERROR);
        $current_width = imagesx($this->image_data);
        $current_height = imagesy($this->image_data);
        if ($current_height <= $height && $current_width <= $width) {
            if (in_array($strategy, [self::RESIZE_STRATEGY_FILL,self::RESIZE_STRATEGY_FIT])) return;
            $this->crop($width, $height, $strategy);
        } else {
            $this->resize($width, $height, $strategy);
        }
    }

    /**
     * Get the image resource attached to this Image object
     * @return resource Image resource
     */
    public function getImageData() {
        return $this->image_data;
    }
    
    /**
     * Check if the given strategy is a valid strategy
     * @param int $strategy Strategy
     * @return bool
     */
    public static function isValidStrategy(int $strategy) : bool {
        return in_array($strategy, array(self::RESIZE_STRATEGY_FIT, self::RESIZE_STRATEGY_FILL, self::RESIZE_STRATEGY_BORDER_WHITE, self::RESIZE_STRATEGY_BORDER_TRANSPARENT));
    }

    /**
     * Resize an image to fit inside the given dimensions
     * @param int $width Width
     * @param int $height Height
     * @param int $strategy Resize strategy
     */
    public function resize(int $width, int $height, int $strategy = self::RESIZE_STRATEGY_FILL) {
        if (! self::isValidStrategy($strategy)) trigger_error('Invalid strategy: '.$strategy, E_USER_ERROR);
        if ($this->image_data == null) trigger_error('Tried operation on image without data.', E_USER_ERROR);
        $current_width = imagesx($this->image_data);
        $current_height = imagesy($this->image_data);
        if ($width == $current_width && $height == $current_height) return;
        $scalefactor = $strategy == self::RESIZE_STRATEGY_FILL ? min($current_width / $width, $current_height / $height) : max($current_width / $width, $current_height / $height);
        // Do the resize
        $target_image = imagecreatetruecolor($current_width/$scalefactor, $current_height/$scalefactor);
        // Copy the resized image onto the new canvas
        imagecopyresampled($target_image, $this->image_data, 0, 0, 0, 0, $current_width/$scalefactor, $current_height/$scalefactor, $current_width, $current_height);
        // Copy back
        $this->image_data = $target_image;
        // In some cases we need to crop
        if ($strategy == self::RESIZE_STRATEGY_FIT) return;
        $this->crop($width, $height, $strategy);
    }
}
