<?php
namespace Platform\Utilities;

use Platform\Platform;
use Platform\File;
use Platform\Server\Instance;

class Log {
    
    private $in_instance = false;
    
    private $lineformat = array();
    
    private $linealign = array();

    private $logname = '';
    
    private $logdir = false;
    
    
    public function __construct(string $logname, array $lineformat = array(), $in_instance = 'autodetect') {
        Errorhandler::checkParams($in_instance, array('string', 'bool'));
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
            $time = Time::now();
            $this->logdir .= $time->getYear().'-'.str_pad($time->getMonth(), 2, '0', STR_PAD_LEFT);
            if (! file_exists($this->logdir)) 
                if (! mkdir($this->logdir, 0774, true)) trigger_error('Could not create log folder', E_USER_ERROR);
        } else {
            $this->logdir = Platform::getConfiguration('dir_log');
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
    
    public static function clean(array $logs = [], int $compress_after_days = 1, int $delete_after_days = 30, $in_instance = 'autodetect') {
        if ($in_instance == 'autodetect') $in_instance = Instance::getActiveInstanceID() > 0;
        if ($in_instance) {
            $basedir = File::getFullFolderPath('logs');
        } else {
            $basedir = Platform::getConfiguration('dir_log');
        }
        if (! $basedir) trigger_error('Couldn\'t deduct base dir');
        static::cleanFolder($basedir, $logs, $compress_after_days, $delete_after_days);
    }
    
    private static function cleanFolder(string $folder, array $logs, int $compress_after_days, int $delete_after_days) {
        if (substr($folder,-1) != '/') $folder .= '/';
        // Open folder
        $dh = opendir($folder);
        if ($dh === false) return;
        while ($file = readdir($dh)) {
            if (in_array($file, ['.','..'])) continue;
            $full_path = $folder.$file;
            if (is_dir($full_path)) {
                static::cleanFolder($full_path, $logs, $compress_after_days, $delete_after_days);
                if (File::isFolderEmpty($full_path)) rmdir($full_path);
            } else {
                // See if we can recognize the file
                if (preg_match('/^(\\d{4}-\\d{2}-\\d{2})-([^.]+)\\.log(\\.gz)?$/', $file, $matches)) {
                    $date = new Time($matches[1]);
                    $basename = $matches[2];
                    $is_archive = $matches[3] == '.gz';
                    // Check if relevant
                    if (! count($logs) || in_array($basename, $logs)) {
                        // Check for delete
                        if ($date->addDays($delete_after_days)->isBeforeEqual(Time::today())) {
                            unlink($full_path);
                        } elseif (! $is_archive && $date->addDays($compress_after_days)->isBeforeEqual(Time::today())) {
                            exec('gzip '.$full_path);
                        }
                    }
                }
            }
        }
    }
    
    public static function jobCleanPlatformLogFilesFromInstance(\Platform\Server\Job $job) {
        static::clean(['measure', 'datarecord'], 1, 30, true);
    }

    public static function jobCleanPlatformLogFilesFromServer(\Platform\Server\Job $job) {
        static::clean(['job_scheduler'], 1, 30, true);
    }
    
}