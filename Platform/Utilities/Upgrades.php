<?php
namespace Platform\Utilities;
/**
 * Description
 * 
 * @author Michael Sahl
 * @see <a href=""></a>
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
        $new_last_upgrade_date = new Time($last_upgrade_date);
        $dh = opendir($directory);
        if ($dh === false) trigger_error('Could not read directory "'.$directory.'"', E_USER_ERROR);
        $scripts_to_run = [];
        while ($file_name = readdir($dh)) {
            $match = [];
            if (preg_match('/^(\\d{4}-\\d{2}-\\d{2})-(\\d{6})-(install|upgrade).*\\.php$/', $file_name,$match)) {
                // Don't run scripts prior to the latest script
                $script_date = new Time($match[1].' '.str_replace('-',':',$match[2]));
                if (! $last_upgrade_date->isNull() && $script_date->isBeforeEqual($last_upgrade_date)) continue;
                if ($new_last_upgrade_date->isNull() || $new_last_upgrade_date->isBefore($script_date)) $new_last_upgrade_date = new Time($script_date);
                // Don't run upgrade scripts during an installation
                if ($match[3] == 'upgrade' && $is_installation) continue;
                // Add script and new last check date
                $scripts_to_run[] = $file_name;
            }
        }
        // Check if we have anything to run
        if (count($scripts_to_run)) {
            // Ensure we run it chronological
            sort($scripts_to_run);
            foreach ($scripts_to_run as $script) {
                include_once $directory.$script;
            }
        }
        if (! $last_upgrade_date->isEqualTo($new_last_upgrade_date)) {
            // Stamp new upgrade date
            static::setLastUpgradeDate($new_last_upgrade_date);
        }
        
    }
    
    /**
     * Get the last upgrade date
     * @return Time
     */
    public static function getLastUpgradeDate() : Time {
        $last_upgrade_date_string = \Platform\Property::getForAll('platform', 'last_upgrade_date');
        return new Time($last_upgrade_date_string);
    }
    
    /**
     * Set the last upgrade date
     * @param Time $last_upgrade_date
     */
    public static function setLastUpgradeDate(Time $last_upgrade_date) {
        \Platform\Property::setForAll('platform', 'last_upgrade_date', $last_upgrade_date->get());
    }
    
}