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
                'fieldtype' => self::FIELDTYPE_TEXT
            ),
            'mimetype' => array(
                'label' => 'Mimetype',
                'fieldtype' => self::FIELDTYPE_TEXT
            ),
        ));
        parent::buildStructure();
    }
    
    public function attachFile($filename) {
        if (! file_exists($filename)) trigger_error('No such file: '.$filename);
        $this->content_source = 'file';
        $this->content = $filename;
    }
    
    public function delete() {
        // Remove file
        $file = $this->getCompleteFilename();
        $result = parent::delete();
        if ($result) unlink($file);
        return $result;
    }
    
    public static function ensureFolder($folder) {
        if (file_exists($folder)) return true;
        $result = mkdir($folder, 0774, true);
        if (! $result) trigger_error('Could not create directory: '.$folder, E_USER_ERROR);
        return true;
    }
    
    public function getCompleteFilename() {
        return $this->getFolder().'data'.$this->file_id.'.blob';
    }

    public function getFolder() {
        global $platform_configuration;
        $instance = Instance::getActiveInstanceID();
        if (! $instance) trigger_error('Couldn\'t detect an instance!', E_USER_ERROR);
        $folder = $platform_configuration['dir_store'];
        if (! substr($folder,-1) == '/') $folder .= '/';
        $folder .= $instance.'/';
        if ($this->folder) $folder .= $this->folder.'/';
        return $folder;
    }
    
    public static function getFullFolderPath($folder) {
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
        return '<a href="/Platform/file.php/'.Instance::getActiveInstanceID().'/'.$this->file_id.'/'.$this->filename.'" target="_blank">'.$this->filename.'</a>';
    }
    
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
     * Save the object to the database, if it have changed.
     * @param boolean $force_save Set true to always save object
     * @param boolean $keep_open_for_write Set to true to keep object open for write after saving
     * @return boolean True if we actually saved the object
     */
    public function save($force_save = false, $keep_open_for_write = false) {
       $force_save |= $this->content_source != '';
        $result = parent::save($force_save, $keep_open_for_write);
        // Handle file content
        switch ($this->content_source) {
            case 'file':
                if (file_exists($this->content)) {
                    $this->ensureFolder($this->getFolder());
                    $result = copy($this->content, $this->getCompleteFilename());
                    if (! $result) trigger_error('Couldn\'t copy '.$this->content.' to '.$this->getCompleteFilename(), E_USER_ERROR);
                }
            break;
        }
        return $result;
    }
}