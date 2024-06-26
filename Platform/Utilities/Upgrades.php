<?php
namespace Platform\Utilities;
/**
 * Class for handling application upgrades which isn't database structure rebuilding
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=upgrades_class
 */


class Upgrades {
    
    /**
     * Executes all upgrades in the given directory
     * @param string $directory The directory to check for upgrades
     * @param bool $is_installation Indicate if this is an installation, so we only do install scripts
     */
    public static function executeUpgrades(string $directory, bool $is_installation = false) {
        if (!is_dir($directory)) trigger_error('No upgrade directory "'.$directory.'"', E_USER_ERROR);
        if (substr($directory,-1) != '/') $directory .= '/';
        $last_upgrade_date = static::getLastUpgradeDate();
        $dh = opendir($directory);
        if ($dh === false) trigger_error('Could not read directory "'.$directory.'"', E_USER_ERROR);
        $scripts_to_run = [];
        while ($file_name = readdir($dh)) {
            $match = [];
            if (preg_match('/^(\\d{4}-\\d{2}-\\d{2})-(\\d{6})-(install|upgrade).*\\.php$/', $file_name,$match)) {
                // Don't run scripts prior to the latest script
                $script_date = new Time($match[1].' '.str_replace('-',':',$match[2]));
                if (! $last_upgrade_date->isNull() && $script_date->isBeforeEqual($last_upgrade_date)) continue;
                // Don't run upgrade scripts during an installation
                //if ($match[3] == 'upgrade' && $is_installation) continue;
                // Add script and new last check date
                $scripts_to_run[$file_name] = ['file_name' => $file_name, 'type' => $match[3], 'script_date' => $script_date];
            }
        }
        // Check if we have anything to run
        if (count($scripts_to_run)) {
            // Ensure we run it chronological
            ksort($scripts_to_run);
            foreach ($scripts_to_run as $script) {
                // Don't run upgrade scripts during an installation
                if ($script['type'] != 'upgrade' || ! $is_installation)
                    include_once $directory.$script['file_name'];
                // Stamp new date no matter what
                static::setLastUpgradeDate($script['script_date']);
            }
        }
    }
    
    /**
     * Get the last upgrade date
     * @return Time
     */
    public static function getLastUpgradeDate() : Time {
        $last_upgrade_date_string = \Platform\Security\Property::getForAll('platform', 'last_upgrade_date');
        return new Time($last_upgrade_date_string);
    }
    
    /**
     * Set the last upgrade date
     * @param Time $last_upgrade_date
     */
    public static function setLastUpgradeDate(Time $last_upgrade_date) {
        \Platform\Security\Property::setForAll('platform', 'last_upgrade_date', $last_upgrade_date->get());
    }
    
}