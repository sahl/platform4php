<?php
namespace Platform\Server;
/**
 * Datarecord class for managing background jobs.
 * 
 * Also responsible for scheduling and running jobs.
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=job_class
 */

use Platform\Filter\ConditionGreater;
use Platform\Filter\ConditionLesserEqual;
use Platform\Filter\ConditionMatch;
use Platform\Filter\ConditionNOT;
use Platform\Filter\ConditionOneOf;
use Platform\Filter\ConditionOR;
use Platform\Filter\Filter;
use Platform\Platform;
use Platform\Utilities\Database;
use Platform\Utilities\Log;
use Platform\Utilities\Semaphore;
use Platform\Utilities\Time;
use Platform\Utilities\Translation;

class Job extends \Platform\Datarecord\Datarecord {
    
    protected static $database_table = 'platform_jobs';
    protected static $delete_strategy = self::DELETE_STRATEGY_PURGE_REFERERS;
    protected static $referring_classes = array(
    );

    protected static $location = self::LOCATION_GLOBAL;

    protected static $structure = false;
    protected static $key_field = false;
    protected static $title_field = false;
    
    protected static $log = false;
    
    protected static $custom_script = false;
    
    const FREQUENCY_PAUSED = 0;
    const FREQUENCY_ONCE = -1;
    const FREQUENCY_ALWAYS = -2;
    const FREQUENCY_SETTIME = -3;
    const FREQUENCY_NOCHANGE = -100;
    
    const SLOT_CAPACITY = 100;
    
    protected static function buildStructure() {
        static::addStructure([
            new \Platform\Datarecord\KeyType('job_id'),
            new \Platform\Datarecord\SingleReferenceType('instance_ref', Translation::translateForUser('Instance'), ['is_required' => true, 'is_readonly' => true, 'foreign_class' => 'Platform\Server\Instance']),
            new \Platform\Datarecord\SingleReferenceType('server_ref', Translation::translateForUser('Server'), ['is_required' => true, 'is_readonly' => true, 'foreign_class' => 'Platform\Server\Server']),
            new \Platform\Datarecord\TextType('class', '', ['is_invisible' => true]),
            new \Platform\Datarecord\TextType('function', '', ['is_invisible' => true]),
            new \Platform\Datarecord\IntegerType('frequency', '', ['is_invisible' => true]),
            new \Platform\Datarecord\BoolType('frequency_offset_from_end', '', ['is_invisible' => true]),
            new \Platform\Datarecord\IntegerType('slot_size', '', ['is_invisible' => true]),
            new \Platform\Datarecord\IntegerType('max_runtime', '', ['is_invisible' => true]),
            new \Platform\Datarecord\DateTimeType('last_start', '', ['is_invisible' => true]),
            new \Platform\Datarecord\DateTimeType('next_start', '', ['is_invisible' => true]),
            new \Platform\Datarecord\IntegerType('process_id', '', ['is_invisible' => true]),
            new \Platform\Datarecord\IntegerType('error_count', '', ['is_invisible' => true]),
            new \Platform\Datarecord\TextType('last_error_message', '', ['is_invisible' => true]),
            new \Platform\Datarecord\IntegerType('run_count', '', ['is_invisible' => true]),
            new \Platform\Datarecord\IntegerType('kill_count', '', ['is_invisible' => true]),
            new \Platform\Datarecord\FloatType('last_run_time', '', ['is_invisible' => true]),
            new \Platform\Datarecord\FloatType('average_run_time', '', ['is_invisible' => true]),
        ]);
        parent::buildStructure();
    }
    
    /**
     * Get a new or existing job matching class and function
     * @param string $class Class to call
     * @param string $function Function to call in the class.
     * @param int $frequency Job frequency
     * @param bool $frequency_offset_from_end Is offset calculated from when the job ends (in opposition to starts)
     * @param int $slot_size Slot size of the job
     * @param int $max_runtime Max allowed run time in realtime minutes.
     * @return \Platform\Server\Job The job
     */
    private function adjustData(string $class, string $function, int $frequency = self::FREQUENCY_NOCHANGE, $frequency_offset_from_end = -1, int $slot_size = -1, int $max_runtime = -1) {
        if (! $function && strpos($class, '::')) {
            $elements = explode('::', $class);
            $class = $elements[0]; $function = $elements[1];
        }
        // Check functions
        if (!is_callable(array($class,$function))) trigger_error('Tried creating a job on un-callable function '.$class.'::'.$function, E_USER_ERROR);
        // Create basic job
        if ($this->isInDatabase()) {
            if ($frequency != self::FREQUENCY_NOCHANGE) $this->frequency = $frequency;
            if ($frequency_offset_from_end !== -1) $this->frequency_offset_from_end = $frequency_offset_from_end;
            if ($slot_size != -1) $this->slot_size = $slot_size;
            if ($max_runtime != -1) $this->max_runtime = $max_runtime;
        } else {
            // Populate basic fields
            $this->class = $class;
            $this->function = $function;
            $this->error_count = 0;
            $this->run_count = 0;
            $this->last_run_time = 0;
            $this->average_run_time = 0.0;
            $this->kill_count = 0;
            $this->frequency = $frequency == self::FREQUENCY_NOCHANGE ? self::FREQUENCY_PAUSED : $frequency;
            $this->frequency_offset_from_end = $frequency_offset_from_end !== -1 ? $frequency_offset_from_end : false;
            $this->slot_size = $slot_size != -1 ? $slot_size : 10;
            $this->max_runtime = $max_runtime != -1 ? $max_runtime : 60;
            $this->process_id = 0;
        }
    }    

    /**
     * Clean up after a job by reading output, resetting the job object and 
     * take statistics.
     */
    public function cleanUp() {
        $this->log('cleanup', 'Cleaning up', $this);
        $this->reloadForWrite();
        $file = $this->getOutputFile();
        if (file_exists($file)) {
            // We read at most 10K bytes
            $fh = fopen($file,'r');
            if ($fh) {
                $content = trim(fread($fh, 10 * 1024));
                fclose($fh);
            } else {
                $content = 'Failed to read output file from job';
            }
            if ($content) {
                $this->log('error', $content, $this);
                $this->error_count = $this->error_count + 1;
                $this->last_error_message = $content;
            } else {
                $this->last_error_message = '';
            }
            unlink($file);
        }
        $this->process_id = 0;
        $this->run_count = $this->run_count + 1;
        $this->last_run_time = $this->last_start->getMinutesUntil(Time::now());
        $this->average_run_time = (($this->run_count-1)*$this->average_run_time + $this->last_run_time)/$this->run_count;
        if ($this->frequency_offset_from_end && $this->frequency > 0) {
            $this->next_start = Time::now()->add(0, $this->frequency);
        }
        if ($this->frequency == self::FREQUENCY_SETTIME && ! $this->next_start->isNull()) {
            while ($this->next_start->isBefore(Time::now())) {
                $this->next_start = $this->next_start->addDays(1);
            }
        }
        $this->save();
    }
    
    /**
     * Delete a job, while also killing it.
     * @param type $force_remove
     */
    public function delete(bool $force_remove = false) : bool {
        // Delete the job
        $result = parent::delete($force_remove);
        // If it was deleted also kill it (in case it is running)
        if ($result) $this->kill();
        return $result;
    }
    
    /**
     * Get a new or existing job matching class and function
     * @param string $class Class to call
     * @param string $function Function to call in the class.
     * @param int $frequency Job frequency
     * @param bool $frequency_offset_from_end Is offset calculated from when the job ends (in opposition to starts)
     * @param int $slot_size Slot size of the job
     * @param int $max_runtime Max allowed run time in realtime minutes.
     * @return \Platform\Server\Job The job
     */
    public static function getJob(string $class, string $function, int $frequency = self::FREQUENCY_NOCHANGE, $frequency_offset_from_end = -1, int $slot_size = -1, int $max_runtime = -1) {
        // Strip leading slash from class name
        if (substr($class,0,1) == '\\') $class = substr($class,1);
        // Create basic job
        $job = new Job();
        $instance_id = Instance::getActiveInstanceID();
        // The OR sentence is included for backward compatibility
        $qr = Database::globalFastQuery("SELECT job_id FROM ".static::$database_table." WHERE instance_ref = ".((int)$instance_id)." AND (class = '".Database::escape($class)."' OR class = '".Database::escape('\\'.$class)."') AND `function` = '".Database::escape($function)."'");
        if ($qr) $job->loadForWrite($qr['job_id']);
        $job->adjustData($class, $function, $frequency, $frequency_offset_from_end, $slot_size, $max_runtime);
        $job->instance_ref = $instance_id;
        return $job;
    }
    
    /**
     * Get a new or existing job matching class and function
     * @param string $class Class to call
     * @param string $function Function to call in the class.
     * @param int $frequency Job frequency
     * @param bool $frequency_offset_from_end Is offset calculated from when the job ends (in opposition to starts)
     * @param int $slot_size Slot size of the job
     * @param int $max_runtime Max allowed run time in realtime minutes.
     * @return \Platform\Server\Job The job
     */
    public static function getServerJob(string $class, string $function, int $frequency = self::FREQUENCY_NOCHANGE, $frequency_offset_from_end = -1, int $slot_size = -1, int $max_runtime = -1) : Job {
        $server_id = Server::getThisServerID();
        $server = new Server();
        $server->loadForRead($server_id);
        return self::getServerJobForServer($server, $class, $function, $frequency, $frequency_offset_from_end, $slot_size, $max_runtime);
    }

    /**
     * Get a new or existing job matching class and function for the given server
     * @param Server $server The server to get the job for
     * @param string $class Class to call
     * @param string $function Function to call in the class.
     * @param int $frequency Job frequency
     * @param bool $frequency_offset_from_end Is offset calculated from when the job ends (in opposition to starts)
     * @param int $slot_size Slot size of the job
     * @param int $max_runtime Max allowed run time in realtime minutes.
     * @return \Platform\Server\Job The job
     */
    public static function getServerJobForServer(Server $server, string $class, string $function, int $frequency = self::FREQUENCY_NOCHANGE, $frequency_offset_from_end = -1, int $slot_size = -1, int $max_runtime = -1) : Job {
        if (! $server->isInDatabase()) trigger_error('Cannot get a job for an unsaved Server', E_USER_ERROR);
        // Strip leading slash from class name
        if (substr($class,0,1) == '\\') $class = substr($class,1);
        // Create basic job
        $job = new Job();
        $server_id = $server->server_id;
        // The OR sentence is included for backward compatibility
        $qr = Database::globalFastQuery("SELECT job_id FROM ".static::$database_table." WHERE server_ref = ".((int)$server_id)." AND (class = '".Database::escape($class)."' OR class = '".Database::escape('\\'.$class)."') AND `function` = '".Database::escape($function)."'");
        if ($qr) $job->loadForWrite($qr['job_id']);
        $job->adjustData($class, $function, $frequency, $frequency_offset_from_end, $slot_size, $max_runtime);
        $job->server_ref = $server_id;
        return $job;
    }
    
    /**
     * Get the name for the output file for this job
     * @global type $platform_configuration
     * @return string Path and file name
     */
    public function getOutputFile() : string {
        return Platform::getConfiguration('dir_temp').'job_output_'.$this->job_id;
    }
    
    /**
     * Get all jobs registered as running on this server
     * @return array<Job>
     */
    public static function getRunningJobs() : array {
        $filter = new Filter('Platform\\Server\\Job');
        $filter->addCondition(new ConditionGreater('process_id', 0));
        $filter->addCondition(
                new ConditionOR(
                    new ConditionOneOf('instance_ref', Instance::getIdsOnThisServer()),
                    new ConditionMatch('server_ref', Server::getThisServerID())
                )
            );
        return $filter->execute()->getAll();
    }
    
    /**
     * Get the full path to the script which should run the jobs
     * @return string
     */
    public static function getRunScript() : string {
        return static::$custom_script ?: __DIR__.'/php/runjob.php';
    }
    
    /**
     * Get all jobs pending to run on this server
     * @return array<Job>
     */
    public static function getPendingJobs() : array {
        $filter = new Filter('Platform\\Server\\Job');
        $filter->addCondition(new ConditionMatch('process_id', 0));
        $filter->addCondition(new ConditionNOT(new ConditionMatch('frequency', 0)));
        $filter->addCondition(new ConditionLesserEqual('next_start', new Time('now')));
        $filter->addCondition(
                new ConditionOR(
                    new ConditionOneOf('instance_ref', Instance::getIdsOnThisServer()),
                    new ConditionMatch('server_ref', Server::getThisServerID())
                )
            );
        return $filter->execute()->getAll();
    }
    
    /**
     * Check if a job has ran for too long.
     * @return bool
     */
    public function isOverdue() : bool {
        return $this->last_start->add(0,$this->max_runtime)->isBefore(new Time('now'));
    }
    
    /**
     * Check if a given job is actually running using the ps command
     * @return bool True if running
     */
    public function isRunning() : bool {
        if (! $this->process_id) return false;
        $result = shell_exec('ps '.((int)$this->process_id).' 2> /dev/null');
        $isrunning = strpos($result, (string)$this->process_id) !== false;
        return $isrunning;
    }
    
    /**
     * Kill job
     */
    public function kill() {
        if (! $this->process_id) return;
        $this->log('kill', 'Killing job '.$this->job_id.' with PID: '.$this->process_id, $this);
        exec('kill '.((int)$this->process_id).' 2> /dev/null');
        $this->reloadForWrite();
        $this->kill_count = $this->kill_count + 1;
        $this->save(false, true);
    }
    
    /**
     * Write job system log events
     * @param string $event Short string for event type
     * @param string $text Longer event text
     * @param \Platform\Server\Job $job The job the event is about
     */
    public static function log(string $event, string $text = '', $job = false) {
        if (! self::$log) self::$log = new Log('job_scheduler', array(8, 15, 55), false);
        $event = strtoupper($event);
        if ($job instanceof Job) self::$log->log($job->instance_ref, $event, $job->class.'::'.$job->function, $text);
        else self::$log->log('global', $event, '-', $text);
    }
    
    /**
     * Process the job queue
     */
    public static function process() {
        if (!Semaphore::grab('process_jobs',2)) {
            self::log('', 'Could not grab semaphore. Exiting.');
            return;
        };
        
        $start_timestamp = time();
        // Loop for 50 seconds as we expect to run every minute
        self::log('', 'Starting');
        while (time()-$start_timestamp <= 50) {
            // Get running jobs
            $running_jobs = self::getRunningJobs();
            // Go over jobs to check if finished or overdue
            $used_slots = 0; $current_job_count = 0; $pending_job_count = 0;
            foreach ($running_jobs as $running_job) {
                if ($running_job->isRunning()) {
                    if ($running_job->isOverdue()) {
                        $running_job->log('overdue', 'Job have exceeded '.$running_job->max_runtime.' minutes runtime.', $running_job);
                        $running_job->kill();
                        $running_job->cleanUp();
                    } else {
                        $used_slots += $running_job->slot_size;
                        $current_job_count++;
                    }
                } else {
                    $running_job->cleanUp();
                }
            }
            if ($used_slots < self::SLOT_CAPACITY) {
                // Check for new jobs
                $pending_jobs = self::getPendingJobs();
                $pending_job_count = count($pending_jobs);
                foreach ($pending_jobs as $pending_job) {
                    if ($pending_job->slot_size + $used_slots > self::SLOT_CAPACITY) {
                        // No room for next job. Bail...
                        break;
                    }
                    $pending_job->start();
                    $current_job_count++;
                    $pending_job_count--;
                    $used_slots += $pending_job->slot_size;
                }
            }
            if ($current_job_count) self::log('capacity', 'Slot use '.$used_slots.'/'.self::SLOT_CAPACITY.' on '.$current_job_count.' jobs. ('.$pending_job_count.' waiting for free slots)');
            // Sleep a little
            sleep(4);
        }
        self::log('', 'Exiting');
        Semaphore::release('process_jobs');
    }

    /**
     * Save a job
     * @param bool $force_save
     * @param bool $keep_open_for_write
     * @return bool
     */
    public function save(bool $force_save = false, bool $keep_open_for_write = false) : bool {
        // Ensure that we have a run time
        if ($this->frequency != self::FREQUENCY_PAUSED && $this->next_start->getTimestamp() === null) $this->next_start = Time::now();
            
        $result = parent::save($force_save, $keep_open_for_write);
        if (Instance::getActiveInstanceID()) $this->log('updated', 'Job updated', $this);
        return $result;
    }
    
    /**
     * Set the script responsible for executing jobs.
     * @param string $script
     */
    public static function setRunScript(string $script) {
        if (!file_exists($script)) trigger_error('Script '.$script.' does not exists', E_USER_ERROR);
        static::$custom_script = $script;
    }
    
    public function setValue(string $field, $value) {
        // Strip leading slash from class names
        if ($field == 'class' && substr($value,0,1) == '\\') $value = substr($value,1);
        parent::setValue($field, $value);
    }
    
    /**
     * Start a job
     */
    public function start() {
        $this->reloadForWrite();
        self::log('start', 'Starting job scheduled at '.$this->getFullValue('next_start'), $this);
        $this->last_start = Time::now();
        if ($this->frequency == self::FREQUENCY_ONCE) $this->frequency = self::FREQUENCY_PAUSED;
        if ($this->frequency > 0 && ! $this->frequency_offset_from_end) $this->next_start = Time::now()->add(0, $this->frequency);
        $result = (int)shell_exec('php '.static::getRunScript().' '.$this->job_id.' > '.$this->getOutputFile().' 2>&1 & echo $!');
        if ($result) {
            self::log('started', 'Running with PID: '.$result, $this);
            $this->process_id = $result;
        } else {
            self::log('no PID', 'Couldn\'t extract PID!', $this);
        }
        $this->save();
    }
}
