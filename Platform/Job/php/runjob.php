<?php
$_SERVER['DOCUMENT_ROOT'] = __DIR__.'/../../../';
include $_SERVER['DOCUMENT_ROOT'].'/Platform/include.php';

$id = $argv[1];

// Sleep a little to let the scheduler finish writing the job
sleep(2);

$job = new \Platform\Job();
$job->loadForRead($id);
if ($job->isInDatabase()) {
    $job->log('RUNNING', 'Job is running', $job);
    // Activate instance if instance job
    if ($job->instance_ref) {
        $instance = new \Platform\Instance();
        $instance->loadForRead($job->instance_ref);
        $instance->activate();
    }
    // Call desired function
    $func = array($job->class, $job->function);
    if (is_callable($func)) call_user_func($func, $job);
    else echo 'No such function: '.$func[0].'::'.$func[1];
}