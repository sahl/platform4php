<?php
namespace Platform;

class File extends Datarecord {
    
    protected static $database_table = 'files';
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
     * Attach a file to this File object. When saved the file will be copied to
     * instance store.
     * @param string $filename Full path of file to attach.
     */
    public function attachFile($filename) {
        Errorhandler::checkParams($filename, 'string');
        if (! file_exists($filename)) trigger_error('No such file: '.$filename, E_USER_ERROR);
        $this->content_source = 'file';
        $this->content = $filename;
    }
    
    public function delete($force_remove = false) {
        Errorhandler::checkParams($force_remove, 'boolean');
        // Remove file
        $file = $this->getCompleteFilename();
        $result = parent::delete($force_remove);
        if ($result) unlink($file);
        return $result;
    }
    
    /**
     * Ensure that the given folder exist within the instance store.
     * @param string $folder (A single) folder name
     * @return boolean
     */
    public static function ensureFolder($folder) {
        Errorhandler::checkParams($folder, 'string');
        if (file_exists($folder)) return true;
        $result = mkdir($folder, 0774, true);
        if (! $result) trigger_error('Could not create directory: '.$folder, E_USER_ERROR);
        return true;
    }
    
    /**
     * Extract the extention from a file name
     * @param string $filename File name
     * @return string
     */
    public static function extractExtension($filename) {
        Errorhandler::checkParams($filename, 'string');
        $dot = strrpos($filename,'.');
        if ($dot === false) return '';
        return substr($filename,$dot+1);
    }
    
    /**
     * Get the complete file name to the local file holding this files content
     * @param boolean $use_current_value Indicate if we should get it using current values.
     * Otherwise values from when the File object was loaded will be used.
     * @return string
     */
    public function getCompleteFilename($use_current_value = true) {
        Errorhandler::checkParams($use_current_value, 'boolean');
        return $this->getCompleteFolderPath($use_current_value).'data'.$this->file_id.'.blob';
    }

    /**
     * Get the complete folder path to the folder holding this files content
     * @param boolean $use_current_value Indicate if we should get it using current values.
     * Otherwise values from when the File object was loaded will be used.
     * @return string
     */
    public function getCompleteFolderPath($use_current_value = true) {
        Errorhandler::checkParams($use_current_value, 'boolean');
        global $platform_configuration;
        $instance = Instance::getActiveInstanceID();
        if (! $instance) trigger_error('Couldn\'t detect an instance!', E_USER_ERROR);
        $folder = $platform_configuration['dir_store'];
        if (! substr($folder,-1) == '/') $folder .= '/';
        $folder .= $instance.'/';
        if ($use_current_value) {
            if ($this->folder) $folder .= $this->folder.'/';
        } else {
            if ($this->values_on_load['folder']) $folder .= $this->values_on_load['folder'].'/';
        }
        return $folder;
    }
    
    public function getFileTypeURL() {
        return self::getFiletypeURLByExtension(self::extractExtension($this->getRawValue('filename')));
    }
    
    /**
     * Get an URL for an icon image corresponding to the given extension
     * @param string $extension
     * @return string URL to image
     */
    public static function getFiletypeURLByExtension($extension) {
        Errorhandler::checkParams($extension, 'string');
        $extension = strtolower($extension);
        if (! file_exists(__DIR__.'/gfx/'.$extension.'.png')) $extension = 'other'; 
        return '/Platform/File/gfx/'.$extension.'.png';
    }
    
    /**
     * Get the full folder path for an specific folder in the current instance.
     * @param string $folder Folder name
     * @return string
     */
    public static function getFullFolderPath($folder) {
        Errorhandler::checkParams($folder, 'string');
        global $platform_configuration;
        $instance = Instance::getActiveInstanceID();
        if (! $instance) trigger_error('Couldn\'t detect an instance!', E_USER_ERROR);
        $finalfolder = $platform_configuration['dir_store'];
        if (! substr($finalfolder,-1) == '/') $finalfolder .= '/';
        $finalfolder .= $instance.'/';
        if ($folder) $finalfolder .= $folder.'/';
        return $finalfolder;
    }
    
    public function getTitle() {
        return '<a href="'.$this->getURL().'" target="_blank">'.$this->filename.'</a>';
    }
    
    /**
     * Get a temporary file name in the temp namespace
     * @return string
     */
    public static function getTempFilename() {
        $path = self::getFullFolderPath('temp');
        self::ensureFolder($path);
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
    public function getURL() {
        if (! $this->isInDatabase()) return '';
        return '/Platform/file.php/'.Instance::getActiveInstanceID().'/'.$this->file_id.'/'.$this->filename;
    }

    /**
     * Get the URL for the file with the given ID
     * @param int $file_id File ID
     * @return string
     */
    public static function getURLByID($file_id) {
        Errorhandler::checkParams($file_id, 'int');
        $file = new File();
        $file->loadForRead($file_id);
        return $file->getURL();
    }
    
     /**
     * Save the object to the database, if it have changed.
     * @param boolean $force_save Set true to always save object
     * @param boolean $keep_open_for_write Set to true to keep object open for write after saving
     * @return boolean True if we actually saved the object
     */
    public function save($force_save = false, $keep_open_for_write = false) {
        Errorhandler::checkParams($force_save, 'boolean', $keep_open_for_write, 'boolean');
        // Check if file have moved folder
        if ($this->isInDatabase() && $this->values_on_load['folder'] != $this->folder) {
            $old_filename = $this->getCompleteFilename(false);
            if (file_exists($old_filename)) {
                // We need to move it into place
                $this->ensureFolder($this->getCompleteFolderPath());
                $result = rename($old_filename, $this->getCompleteFilename());
                if (! $result) trigger_error('Couldn\'t move file content from folder '.$this->values_on_load['folder'].' to '.$this->folder, E_USER_ERROR);
            }
        }
        
        $force_save |= $this->content_source != '';
        $result = parent::save($force_save, $keep_open_for_write);
        // Handle file content
        switch ($this->content_source) {
            case 'file':
                if (file_exists($this->content)) {
                    $this->ensureFolder($this->getCompleteFolderPath());
                    $result = copy($this->content, $this->getCompleteFilename());
                    if (! $result) trigger_error('Couldn\'t copy '.$this->content.' to '.$this->getCompleteFilename(), E_USER_ERROR);
                }
            break;
        }
        return $result;
    }
}