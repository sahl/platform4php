<?php
namespace Platform\Utilities;

use Platform\Platform;

class Semaphore {
    
    private static $carried_semaphores = array();
    
    private static $php_semaphore = false;
    
    /**
     * Get a real php semaphore
     * @return resource
     */
    private static function getSemaphoreObject() {
        if (self::$php_semaphore === false) {
            self::$php_semaphore = sem_get(ftok(__FILE__, 'a'));
        }
        return self::$php_semaphore;
    }
    
    /**
     * Get a complete semaphore file name from a title
     * @param string $title
     * @return string Complete file name
     */
    private static function getSemaphoreFileNameFromTitle(string $title) : string {
        return Platform::getConfiguration('dir_temp').'Semaphore_'.$title;
    }
    
    /**
     * Try to grab the semaphore with the given title.
     * @param string $title The title of the semaphore
     * @param int $minutesbeforebreak The delay in minutes before we can break a previous locked semaphore
     * @return bool True if it was possible to grab the semaphore
     */
    public static function grab(string $title, int $minutesbeforebreak = 30) : bool {
        $php_semaphore = self::getSemaphoreObject();
        
        $semfile = self::getSemaphoreFileNameFromTitle($title);
        
        // Check if we already carry it
        if (in_array($semfile, self::$carried_semaphores)) return true;
        
        // Acquire real semaphore
        sem_acquire($php_semaphore);
        
        // Check if lock file exists
        if (file_exists($semfile)) {
            // Check if we can break it
            if (time()-filemtime($semfile) > $minutesbeforebreak * 60) {
                unlink($semfile);
            } else {
                // We cannot break it. Bail
                sem_release($php_semaphore);
                return false;
            }
        }
        // Set the semaphore file
        $fh = fopen($semfile,'w');
        fwrite($fh, $title);
        fclose($fh);
        
        // Add it to carried semaphores
        self::$carried_semaphores[] = $semfile;

        // Release and return
        sem_release($php_semaphore);
        return true;
    }
    
    /**
     * Releases the semaphore with the given title, if it exists
     * @param string $title Semaphore title
     */
    public static function release(string $title) {
        $semfile = self::getSemaphoreFileNameFromTitle($title);
        
        // We can only release a semaphore we actually carry.
        if (! in_array($semfile, self::$carried_semaphores)) {
            return;
        }

        $php_semaphore = self::getSemaphoreObject();
        sem_acquire($php_semaphore);
        
        if (file_exists($semfile)) unlink($semfile);
        sem_release($php_semaphore);
        \Platform\Utilities::arrayRemove(self::$carried_semaphores, $semfile);
    }
    
    /**
     * Release all carried semaphores.
     */
    public static function releaseAll() {
        foreach (self::$carried_semaphores as $semfile) {
            if (file_exists($semfile)) unlink($semfile);
        }
        self::$carried_semaphores = array();
    }
    
    /**
     * Wait for the semaphore with the given title
     * The function will block and then return false if not able to obtain the semaphore within this time.
     * @param string $title The title of the semaphore
     * @param int $minutesbeforebreak The delay in minutes before we can break a previous locked semaphore
     * @param bool $maxwaitinseconds The max number of seconds to try to grab the semaphore. 
     * @return bool True if it was possible to grab the semaphore within the allotted time
     */
    public static function wait(string $title, int $minutesbeforebreak = 30, int $maxwaitinseconds = 30) : bool {
        $waited = 0;
        while (! self::grab($title, $minutesbeforebreak)) {
            if ($waited > $maxwaitinseconds) return false;
            $wait = rand(1,3);
            sleep($wait);
            $waited += $wait;
        }
        return true;
    }
    
}

?>
