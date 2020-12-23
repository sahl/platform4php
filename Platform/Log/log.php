<?php
namespace Platform;

class Log {
    
    private $in_instance = false;
    
    private $lineformat = array();
    
    private $linealign = array();

    private $logname = '';
    
    private $logdir = false;
    
    
    public function __construct($logname, $lineformat = array(), $in_instance = 'autodetect') {
        Errorhandler::checkParams($logname, 'string', $lineformat, 'array', $in_instance, array('string', 'boolean'));
        global $platform_configuration;
        $this->logname = $logname;
        foreach ($lineformat as $format) {
            if (substr($format,-1) == 'r') {
                $this->linealign[] = 'r';
                $this->lineformat[] = substr($format,0,-1);
            } else {
                $this->linealign[] = 'l';
                $this->lineformat[] = $format;
            }
        }
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
    
    /**
     * Log something to the log file
     */
    public function log() {
        umask(002);
        // Sort log items into columns
        $columns = array();
        $columns[0][-1] = date('H:i:s');
        for ($i = 0; $i < func_num_args(); $i++) {
            $full_text = func_get_arg($i);
            if ($i < count($this->lineformat)) $full_text = wordwrap($full_text, $this->lineformat[$i]-1, "\n", true);
            $j = 0;
            foreach (explode("\n", $full_text) as $line) $columns[$j++][$i] = $line;
        }
        // Render to file
        $fh = fopen($this->logdir.'/'.date('Y-m-d').'-'.$this->logname.'.log', 'a');
        if ($fh === false) trigger_error('Could not open logfile '.$this->logdir.'/'.date('Y-m-d').'-'.$this->logname.'.log for writing', E_USER_ERROR);
        foreach ($columns as $line) {
            for ($i = -1; $i < func_num_args(); $i++) {
                $string = $line[$i];
                if ($i == -1) $output_line = str_pad($string,9,' ').' ';
                elseif ($i >= count($this->lineformat)) $output_line .= ' '.$string;
                else $output_line .= ' '.str_pad($string, $this->lineformat[$i], ' ', $this->linealign[$i] == 'r' ? STR_PAD_LEFT : STR_PAD_RIGHT);
            }
            fwrite($fh, $output_line."\n");
        }
        fclose($fh);
    }
}