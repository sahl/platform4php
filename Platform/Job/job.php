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
                'fieldtype' => self::FIELDTYPE_TEXT
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
    
    public static function getRunningJobs() {
        $filter = new Filter('Platform\Job');
        $filter->addCondition(new FilterConditionGreater('process_id', 0));
        return $filter->execute()->getAll();
    }
    
    public static function getPendingJobs() {
        $filter = new Filter('Platform\Job');
        $filter->addCondition(new FilterConditionMatch('process_id', 0));
        $filter->addCondition(new FilterConditionLesserEqual('next_start', new Timestamp('now')));
        return $filter->execute()->getAll();
    }
    
    public function isOverdue() {
        return $this->last_start->add(0,$this->max_runtime)->isBefore(new Timestamp('now'));
    }
    
    public function isRunning() {
        if (! $this->process_id) return false;
        $result = shell_exec('ps '.((int)$this->process_id));
        return count(preg_split("/\n/", $result)) > 2;
    }
    
    public function kill() {
        if (! $this->process_id) return;
        exec('kill '.((int)$this->process_id));
        $this->cleanUp();
    }
    
    public static function log($event, $text = '', $job = false) {
        if (! self::$log) self::$log = new Log('job_scheduler', array(8, 15, 30));
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
    }
    
}