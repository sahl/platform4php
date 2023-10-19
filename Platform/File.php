<?php
namespace Platform;

use Platform\Server\Instance;
use Platform\Utilities\Semaphore;

class File extends Datarecord {
    
    protected static $database_table = 'platform_files';
    protected static $structure = false;
    protected static $key_field = false;
    protected static $location = self::LOCATION_INSTANCE;
    
    protected $content_source = false;
    protected $content = false;
    
    protected static function buildStructure() {
        self::addStructure(array(
            'file_id' => array(
                'invisible' => true,
                'fieldtype' => self::FIELDTYPE_KEY
            ),
            'filename' => array(
                'label' => 'File name',
                'required' => true,
                'fieldtype' => self::FIELDTYPE_TEXT
            ),
            'folder' => array(
                'label' => 'Folder',
                'fieldtype' => self::FIELDTYPE_TEXT,
                'key' => true
            ),
            'mimetype' => array(
                'label' => 'Mimetype',
                'fieldtype' => self::FIELDTYPE_TEXT
            ),
        ));
        parent::buildStructure();
    }
    
    /**
     * Attach binary data to this File object. When saved the binary data will be
     * written to the instance store.
     * @param string $binary_data
     */
    public function attachBinaryData(string $binary_data) {
        $this->content_source = 'binary_data';
        $this->content = $binary_data;
    }
    
    /**
     * Attach a file to this File object. When saved the file will be copied to
     * instance store.
     * @param string $filename Full path of file to attach.
     */
    public function attachFile(string $filename) {
        if (! file_exists($filename)) trigger_error('No such file: '.$filename, E_USER_ERROR);
        $this->content_source = 'file';
        $this->content = $filename;
        $this->mimetype = mime_content_type($filename);
        if (! $this->filename) $this->filename = self::extractFilename ($filename);
    }
    
    public function delete(bool $force_remove = false) : bool {
        // Remove file
        $file = $this->getCompleteFilename();
        $result = parent::delete($force_remove);
        if ($result) {
            $folder = $this->getCompleteFolderPath();
            unlink($file);
            // Check if we can remove the folder
            self::removeEmptyFolders($folder);
        }
        return $result;
    }
    
    /**
     * Delete temp files older than a day
     */
    public static function deleteTempFiles() {
        // Delete files older than a day
        $cutdate = \Platform\Utilities\Time::now()->addDays(-1);
        $path = self::getFullFolderPath('temp');
        if (! is_dir($path)) return;
        $dh = opendir($path);
        while (($file = readdir($dh)) !== false) {
            $completefile = $path.$file;
            if (is_dir($completefile)) continue;
            if (filemtime($completefile) < $cutdate->getTimestamp()) unlink($completefile);
        }
    }
    
    /**
     * Ensure that the given folder exist within the instance store.
     * @param string $local_folder (A single) folder name
     * @return bool
     */
    public static function ensureFolderInStore(string $local_folder) : bool {
        return self::ensureFullPath(self::getFullFolderPath($local_folder));
    }
    
    /**
     * Ensure that this full path exists
     * @param string $path Path to ensure
     * @param bool $includes_file_name If true then the path includes a file name (which isn't ensured to exist)
     * @return bool
     */
    public static function ensureFullPath(string $path, bool $includes_file_name = false) : bool {
        if (file_exists($path)) return true;
        if ($includes_file_name) {
            $slash_position = strrpos($path,'/');
            if ($slash_position !== false) $path = substr($path,0,$slash_position);
        }
        return mkdir($path,0774,true);
    }
    
    /**
     * Extract the extention from a file name
     * @param string $filename File name
     * @return string Extension in lower case
     */
    public static function extractExtension(string $filename) : string {
        $dot = strrpos($filename,'.');
        if ($dot === false) return '';
        return strtolower(substr($filename,$dot+1));
    }
    
    /**
     * Extract the filename from a complete path
     * @param string $path Full path name
     * @return string File name in path
     */
    public static function extractFilename(string $path) : string {
        $pos = mb_strrpos($path, '/');
        return $pos === false ? $path : mb_substr($path,$pos+1);
    }
    
    /**
     * Get the complete file name to the local file holding this files content
     * @param bool $use_current_value Indicate if we should get it using current values.
     * Otherwise values from when the File object was loaded will be used.
     * @return string
     */
    public function getCompleteFilename(bool $use_current_value = true) : string {
        return $this->getCompleteFolderPath($use_current_value).'data'.$this->file_id.'.blob';
    }

    /**
     * Get the complete folder path to the folder holding this files content
     * @param bool $use_current_value Indicate if we should get it using current values.
     * Otherwise values from when the File object was loaded will be used.
     * @return string
     */
    public function getCompleteFolderPath(bool $use_current_value = true) : string {
        $instance = Instance::getActiveInstanceID();
        if (! $instance) trigger_error('Couldn\'t detect an instance!', E_USER_ERROR);
        $folder = Platform::getConfiguration('dir_store');
        if (! substr($folder,-1) == '/') $folder .= '/';
        $folder .= $instance.'/';
        if ($use_current_value) {
            if ($this->folder) $folder .= $this->folder.'/';
        } else {
            if ($this->values_on_load['folder']) $folder .= $this->values_on_load['folder'].'/';
        }
        return $folder;
    }
    
    public function getCopy(bool $name_as_copy = false): Datarecord {
        $file = parent::getCopy($name_as_copy);
        $file->attachFile($this->getCompleteFilename());
        return $file;
    }
    
    /**
     * Get the content of this file as binary data
     * @return mixed binary data or false if no content
     */
    public function getFileContent() {
        if (!file_exists($this->getCompleteFilename())) return false;
        return file_get_contents($this->getCompleteFilename());
    }
    
    /**
     * Get file size in bytes
     * @return int
     */
    public function getFileSize() : int {
        if (!file_exists($this->getCompleteFilename())) return false;
        return filesize($this->getCompleteFilename());
    }
    
    /**
     * Get the current filename without extension
     * @return string
     */
    public function getFilenameWithoutExtension() : string {
        if ($this->filename === null) $this->filename = '';
        $dotposition = mb_strrpos($this->filename, '.');
        return $dotposition === false ? $this->filename : mb_substr($this->filename,0,$dotposition);
    }
    
    /**
     * Get an url for an icon representing the current filetype based on extension
     * @return string
     */
    public function getFileTypeURL() : string {
        return self::getFiletypeURLByExtension(self::extractExtension($this->getRawValue('filename')));
    }
    
    /**
     * Get an URL for an icon image corresponding to the given extension
     * @param string $extension
     * @return string URL to image
     */
    public static function getFiletypeURLByExtension(string $extension) : string {
        $extension = strtolower($extension);
        if (! file_exists(__DIR__.'/File/gfx/'.$extension.'.png')) $extension = 'other'; 
        return '/Platform/File/gfx/'.$extension.'.png';
    }
    
    /**
     * Get the full folder path for an specific folder in the current instance.
     * @param string $local_folder Folder name
     * @return string
     */
    public static function getFullFolderPath(string $local_folder) : string {
        $finalfolder = self::getInstancePath();
        if ($local_folder) $finalfolder .= $local_folder.'/';
        return $finalfolder;
    }
    
    /**
     * Get the path to the file store of the active instance
     * @return string
     */
    public static function getInstancePath() : string {
        $instance_id = Instance::getActiveInstanceID();
        if (! $instance_id) trigger_error('Couldn\'t detect an instance!', E_USER_ERROR);
        $final_folder = Platform::getConfiguration('dir_store');
        if (! substr($final_folder,-1) == '/') $final_folder .= '/';
        $final_folder .= $instance_id.'/';
        return $final_folder;
    }
    
    public function getTitle() : string {
        return '<a href="'.$this->getURL().'" target="_blank">'.$this->filename.'</a>';
    }
    
    /**
     * Get a temporary file name in the temp namespace
     * @return string
     */
    public static function getTempFilename() : string {
        $path = self::getFullFolderPath('temp');
        self::ensureFullPath($path);
        if (!Semaphore::wait('tempfilename')) trigger_error('Couldn\'t grab tempfile semaphore!', E_USER_ERROR);
        do {
            $file_id = rand(1,999999999);
            $filename = 'file'.$file_id.'.blob';
        } while (file_exists($path.$filename));
        touch($path.$filename);
        Semaphore::release('tempfilename');
        return $path.$filename;
    }
    
    /**
     * Get the URL to this file
     * @return string
     */
    public function getURL() : string {
        if (! $this->isInDatabase()) return '';
        return '/Platform/File/php/file.php/'.\Platform\Server\Instance::getActiveInstanceID().'/'.$this->file_id.'/'.urlencode($this->filename);
    }

    /**
     * Get the URL for the file with the given ID
     * @param int $file_id File ID
     * @return string
     */
    public static function getURLByID(int $file_id) : string {
        $file = new File();
        $file->loadForRead($file_id);
        return $file->getURL();
    }
    
    /**
     * Check if a given folder is empty or not
     * @param string $path Full path to folder
     * @return bool
     */
    public static function isFolderEmpty(string $path) : bool {
        // If the folder doesn't exists it is considered empty
        if (!file_exists($path)) return true;
        $dh = opendir($path);
        while ($filename = readdir($dh)) {
            if (in_array($filename, ['.','..'])) continue;
            // If we find at least one file, the folder isn't empty
            closedir($dh);
            return false;
        }
        return true;
    }
    
    /**
     * Return if this is an image based on a simple analysis of the stored mimetype
     * @return bool
     */
    public function isImage() : bool {
        return substr($this->mimetype,0,5) == 'image';
    }
    
    /**
     * Remove all empty folders in the given path, but only if it is part of the
     * active instances path and no longer that the base folder for that instance
     * @param string $full_path Path to remove
     */
    public static function removeEmptyFolders(string $full_path) {
        $instance_path = self::getInstancePath();
        // Bail if path not part of instance path
        if (strpos($full_path, $instance_path) !== 0) return;
        // Remove trailing slash if any
        if (substr($full_path,-1) == '/') $full_path = substr($full_path, 0, strlen($full_path)-1);
        if (self::isFolderEmpty($full_path)) {
            // Remove it
            if (! rmdir($full_path)) return;
            // Take a bite
            $full_path = substr($full_path, 0, strrpos($full_path,'/'));
            // Recursive
            self::removeEmptyFolders($full_path);
        }
    }
    
     /**
     * Save the object to the database, if it have changed.
     * @param bool $force_save Set true to always save object
     * @param bool $keep_open_for_write Set to true to keep object open for write after saving
     * @return bool True if we actually saved the object
     */
    public function save(bool $force_save = false, bool $keep_open_for_write = false) : bool {
        // Check if file have moved folder
        if ($this->isInDatabase() && $this->values_on_load['folder'] != $this->folder) {
            $old_filename = $this->getCompleteFilename(false);
            if (file_exists($old_filename)) {
                // We need to move it into place
                if ($this->folder) $this->ensureFolderInStore($this->folder);
                $result = rename($old_filename, $this->getCompleteFilename());
                if (! $result) trigger_error('Couldn\'t move file content from folder '.$this->values_on_load['folder'].' to '.$this->folder, E_USER_ERROR);
            }
        }
        
        $force_save = $force_save || $this->content_source != '';
        $result = parent::save($force_save, $keep_open_for_write);
        // Handle file content
        switch ($this->content_source) {
            case 'file':
                if (file_exists($this->content)) {
                    if ($this->folder) $this->ensureFolderInStore($this->folder);
                    $result = copy($this->content, $this->getCompleteFilename());
                    if (! $result) trigger_error('Couldn\'t copy '.$this->content.' to '.$this->getCompleteFilename(), E_USER_ERROR);
                }
                break;
            case 'binary_data':
                if ($this->folder) $this->ensureFolderInStore($this->folder);
                $fh = fopen($this->getCompleteFilename(), 'w');
                if (! $fh) trigger_error('Couldn\'t write binary data to '.$this->getCompleteFilename (), E_USER_ERROR);
                fwrite($fh, $this->content);
                fclose($fh);
                break;
        }
        return $result;
    }
}