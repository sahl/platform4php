<?php
namespace Platform;

class Job extends \Platform\Datarecord {
    
    protected static $database_table = 'jobs';
    protected static $delete_strategy = self::DELETE_STRATEGY_PURGE_REFERERS;
    protected static $referring_classes = array(
        
    );

    protected static $location = self::LOCATION_GLOBAL;

    protected static $structure = false;
    protected static $key_field = false;
    
    protected static $log = false;
    
    const FREQUENCY_PAUSED = 0;
    const FREQUENCY_ONCE = -1;
    const FREQUENCY_ALWAYS = -2;
    const FREQUENCY_NOCHANGE = -100;
    
    const SLOT_CAPACITY = 100;
    
    protected static function buildStructure() {
        // Todo: Define the object structure in this array
        $structure = array(
            'job_id' => array(
                'invisible' => true,
                'fieldtype' => self::FIELDTYPE_KEY
            ),
            'instance_ref' => array(
                'invisible' => true,
                'fieldtype' => self::FIELDTYPE_REFERENCE_SINGLE,
                'foreignclass' => 'Platform\Instance'
            ),
            'class' => array(
                'invisible' => true,
                'fieldtype' => self::FIELDTYPE_TEXT
            ),
            'function' => array(
                'invisible' => true,
                'fieldtype' => self::FIELDTYPE_TEXT
            ),
            'frequency' => array(
                'invisible' => true,
                'fieldtype' => self::FIELDTYPE_INTEGER
            ),
            'max_runtime' => array(
                'invisible' => true,
                'fieldtype' => self::FIELDTYPE_INTEGER
            ),
            'slot_size' => array(
                'invisible' => true,
                'fieldtype' => self::FIELDTYPE_INTEGER
            ),            
            'frequency_offset' => array(
                'invisible' => true,
                'fieldtype' => self::FIELDTYPE_ENUMERATION,
                'enumeration' => array('from_start' => 'From start', 'from_end' => 'From end')
            ),
            'last_start' => array(
                'invisible' => true,
                'fieldtype' => self::FIELDTYPE_DATETIME
            ),
            'next_start' => array(
                'invisible' => true,
                'fieldtype' => self::FIELDTYPE_DATETIME
            ),
            'process_id' => array(
                'invisible' => true,
                'fieldtype' => self::FIELDTYPE_INTEGER
            ),
            'error_count' => array(
                'invisible' => true,
                'fieldtype' => self::FIELDTYPE_INTEGER
            ),
            'last_error_message' => array(
                'invisible' => true,
                'fieldtype' => self::FIELDTYPE_BIGTEXT
            ),
            'run_count' => array(
                'invisible' => true,
                'fieldtype' => self::FIELDTYPE_INTEGER
            ),
            'kill_count' => array(
                'invisible' => true,
                'fieldtype' => self::FIELDTYPE_INTEGER
            ),
            'last_run_time' => array(
                'invisible' => true,
                'fieldtype' => self::FIELDTYPE_INTEGER
            ),
            'average_run_time' => array(
                'invisible' => true,
                'fieldtype' => self::FIELDTYPE_FLOAT
            ),
        );
        self::addStructure($structure);
        // Remember to call parent
        parent::buildStructure();
    }
    
    public function cleanUp() {
        $this->log('cleanup', 'Cleaning up', $this);
        $this->reloadForWrite();
        $file = $this->getOutputFile();
        if (file_exists($file)) {
            $content = trim(implode('',file($file)));
            if ($content) {
                $this->log('error', $content, $this);
                $this->error_count = $this->error_count + 1;
                $this->last_error_message = $content;
            }
        }
        $this->process_id = 0;
        $this->run_count = $this->run_count + 1;
        $this->last_run_time = $this->last_start->getMinutesUntil(Timestamp::now());
        $this->average_run_time = (($this->run_count-1)*$this->average_run_time + $this->last_run_time)/$this->run_count;
        if ($this->frequency_offset == 'from_end' && $this->freqency > 0) {
            $this->next_start = Timestamp::now()->add(0, $this->frequency);
        }
        $this->save();
    }
    
    public function delete($force_purge = false) {
        $this->kill();
        parent::delete($force_purge);
    }
    
    public static function getJob($class, $function = '', $frequency = self::FREQUENCY_NOCHANGE, $frequency_offset = '', $slot_size = -1) {
        if (! $function && strpos($class, '::')) {
            $elements = explode('::', $class);
            $class = $elements[0]; $elements = $split[1];
        }
        // Create basic job
        $job = new Job();
        $instance_id = Instance::getActiveInstanceID();
        $qr = gfq("SELECT job_id FROM jobs WHERE instance_ref = ".((int)$instance_id)." AND class = '".esc($class)."' AND function = '".esc($function)."'");
        if ($qr) {
            $job->loadForWrite($qr['job_id']);
            if ($frequency != self::FREQUENCY_NOCHANGE) $job->frequency = $frequency;
            if ($frequency_offset) $job->frequency_offset = $frequency_offset;
            if ($slot_size != -1) $job->slot_size = $slot_size;
        } else {
            // Populate basic fields
            $job->instance_ref = $instance_id;
            $job->class = $class;
            $job->function = $function;
            $job->error_count = 0;
            $job->run_count = 0;
            $job->last_run_time = 0;
            $job->average_run_time = 0.0;
            $job->kill_count = 0;
            $job->frequency = $frequency == self::FREQUENCY_NOCHANGE ? self::FREQUENCY_PAUSED : $frequency;
            $job->frequency_offset = $frequency_offset ?: 'from_start';
            $job->slot_size = $slot_size != -1 ? $slot_size : 10;
            $job->process_id = 0;
        }
        return $job;
    }
    
    public function getOutputFile() {
        global $platform_configuration;
        return $platform_configuration['dir_temp'].'job_output_'.$this->job_id;
    }
    
    public static function getRunningJobs() {
        $filter = new Filter('Platform\Job');
        $filter->addCondition(new FilterConditionGreater('process_id', 0));
        return $filter->execute()->getAll();
    }
    
    public static function getPendingJobs() {
        $filter = new Filter('Platform\Job');
        $filter->addCondition(new FilterConditionMatch('process_id', 0));
        $filter->addCondition(new FilterConditionNOT(new FilterConditionMatch('frequency', 0)));
        $filter->addCondition(new FilterConditionLesserEqual('next_start', new Timestamp('now')));
        return $filter->execute()->getAll();
    }
    
    public function isOverdue() {
        return $this->last_start->add(0,$this->max_runtime)->isBefore(new Timestamp('now'));
    }
    
    public function isRunning() {
        if (! $this->process_id) return false;
        $result = shell_exec('ps '.((int)$this->process_id));
        $isrunning = strpos($result, (string)$this->process_id) !== false;
        return $isrunning;
    }
    
    public function kill() {
        if (! $this->process_id) return;
        $this->log('kill', 'Killed because '.$this->max_runtime.' minutes was exceeded!', $this);
        exec('kill '.((int)$this->process_id));
        $this->reloadForWrite();
        $this->kill_count = $this->kill_count + 1;
        $this->save(false, true);
    }
    
    public static function log($event, $text = '', $job = false) {
        if (! self::$log) self::$log = new Log('job_scheduler', array(8, 15, 30), false);
        $event = strtoupper($event);
        if ($job instanceof Job) self::$log->log($job->instance_ref, $event, $job->class.'::'.$job->function, $text);
        else self::$log->log('global', $event, '-', $text);
    }
    
    public static function process() {
        if (!Semaphore::grab('process_jobs',2)) return;
        
        $start_timestamp = time();
        // Loop for 50 seconds as we expect to run every minute
        self::log('', 'Starting');
        while (time()-$start_timestamp <= 50) {
            // Get running jobs
            $running_jobs = self::getRunningJobs();
            // Go over jobs to check if finished or overdue
            $used_slots = 0;
            foreach ($running_jobs as $running_job) {
                if ($running_job->isRunning()) {
                    if ($running_job->isOverdue()) {
                        $running_job->kill();
                        $running_job->cleanUp();
                    } else {
                        $used_slots += $running_job->slot_size;
                    }
                } else {
                    $running_job->cleanUp();
                }
            }
            if ($used_slots < self::SLOT_CAPACITY) {
                // Check for new jobs
                $pending_jobs = self::getPendingJobs();
                foreach ($pending_jobs as $pending_job) {
                    if ($pending_job->slot_size + $used_slots > self::SLOT_CAPACITY) {
                        // No room for next job. Bail...
                        break;
                    }
                    $pending_job->start();
                    $used_slots += $job->slot_size;
                }
            }
            // Sleep a little
            sleep(4);
        }
        self::log('', 'Exiting');
        Semaphore::release('process_jobs');
    }
    
    public function save($force_save = false, $keep_open_for_write = false) {
        // Ensure that we have a run time
        if ($this->frequency != self::FREQUENCY_PAUSED && $this->next_start->getTimestamp() === null) $this->next_start = Timestamp::now();
            
        $result = parent::save($force_save, $keep_open_for_write);
        if (Instance::getActiveInstanceID()) $this->log('updated', 'Job updated', $this);
        return $result;
    }
    
    public function start() {
        $this->reloadForWrite();
        self::log('start', 'Starting job scheduled at '.$this->getFullValue('next_start'), $this);
        $this->last_start = Timestamp::now();
        if ($this->frequency == self::FREQUENCY_ONCE) $this->frequency = self::FREQUENCY_PAUSED;
        if ($this->frequency > 0 && $this->frequency_offset == 'from_start') $this->next_start = Timestamp::now()->add(0, $this->frequency);
        $result = (int)shell_exec('php '.__DIR__.'/php/runjob.php '.$this->job_id.' > '.$this->getOutputFile().' & echo $!');
        if ($result) {
            self::log('started', 'Running with PID: '.$result, $this);
            $this->process_id = $result;
        } else {
            self::log('no PID', 'Couldn\'t extract PID!', $this);
        }
        $this->save();
    }
    
}