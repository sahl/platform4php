<?php
namespace Platform;

class Log {
    
    private $in_instance = false;
    
    private $lineformat = array();

    private $logname = '';
    
    private $logdir = false;
    
    
    public function __construct($logname, $lineformat = array(), $in_instance = 'autodetect') {
        global $platform_configuration;
        $this->logname = $logname;
        $this->lineformat = $lineformat;
        $this->in_instance = $in_instance == 'autodetect' ? Instance::getActiveInstanceID() > 0 : $in_instance;
        
        // Ensure we have a proper directory
        if ($this->in_instance) {
            $this->logdir = File::getFullFolderPath('logs');
            if (! file_exists($this->logdir)) 
                if (! mkdir($this->logdir)) trigger_error('Could not create log folder', E_USER_ERROR);
        } else {
            $this->logdir = $platform_configuration['dir_log'];
        }
    }
    
    public function log() {
        umask(002);
        $fh = fopen($this->logdir.'/'.date('Y-m-d').'-'.$this->logname.'.log', 'a');
        $line = date('H:i:s');
        for ($i = 0; $i < func_num_args(); $i++) {
            $string = func_get_arg($i);
            if ($i >= count($this->lineformat)) $line .= ' '.$string;
            else $line .= ' '.str_pad($string, $this->lineformat[$i], ' ', STR_PAD_LEFT);
        }
        fwrite($fh, $line."\n");
        fclose($fh);
    }
}