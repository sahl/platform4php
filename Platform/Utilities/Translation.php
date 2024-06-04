<?php
namespace Platform\Utilities;
/**
 * Class for handling multi-language applications
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=translation_class
 */

use Platform\Server\Instance;
use Platform\Security\Property;

$platform_language = array();

class Translation {
    
    private static $translations_loaded_file_table = array();
    
    /**
     * Build CSV files ready for translation from the translation files already in the system.
     */
    public static function buildCSVFilesFromTranslationFiles() {
        $data = array();
        // Gather files
        $files = self::getFileListFromDirectory(self::getTranslationDir(), array('js', 'php'));
        foreach ($files as $file) {
            // Determine language
            if (preg_match('/([a-z]{2})\\.[a-z]{2,3}$/', $file, $match)) {
                $language_key = $match[1];
                $phrases = self::getPhrasesFromTranslationFile($file);
                foreach ($phrases as $original_phrase => $translated_phrase) {
                    // Never overwrite an translated phrase with an untranslated phrase
                    if (trim($translated_phrase) || ! isset($data[$language_key][$original_phrase]))
                        $data[$language_key][$original_phrase] = $translated_phrase;
                }
            }
        }
        foreach(self::getLanguageKeys(false) as $language_key) {
            if (! isset($data[$language_key])) continue;
            self::saveCSVFile($language_key, $data[$language_key]);
        }
    }
    
    /**
     * Convert an URL to the server path to the same file.
     * @param string $url URL
     * @return string|bool Path to file or false if convertion not possible.
     */
    public static function convertURLToServerPath(string $url) {
        // Discard URLs with servers
        if (strpos($url, '://') !== false) return false;
        if (substr($url,0,1) == '/') {
            // Absolute path
            $baseurl = $_SERVER['DOCUMENT_ROOT'];
        } else {
            // Relative path
            $baseurl = $_SERVER['DOCUMENT_ROOT'];
            if (substr($baseurl,-1) == '/') $baseurl = substr($baseurl,0,-1);
            $baseurl .= $_SERVER['PHP_SELF'];
            if (substr($baseurl,-1) != '/') $baseurl = substr($baseurl, 0, strrpos($baseurl, '/')+1);
        }
        return $baseurl.$url;
    }
    
    /**
     * Convert a path to a local file, to an url path
     * @param string $server_path Local path
     * @return string URL
     */
    public static function convertServerPathToURL(string $server_path) : string {
        $baseurl = $_SERVER['DOCUMENT_ROOT'];
        if (substr($baseurl,-1) == '/') $baseurl = substr($baseurl,0,-1);
        return substr($server_path, strlen($baseurl));
    }

    /**
     * Create or update translation files for the given original file with the passed phrases
     * @param string $original_file Full path to an original file
     * @param array $phrases Collected phrases
     */
    public static function createUpdateTranslationFilesWithPhrases(string $original_file, array $phrases) {
        foreach (self::getLanguageKeys(false) as $language_key) {
            $translation_file = self::getTranslationFileFromOriginalFile($original_file, $language_key);
            if (! count($phrases)) {
                // If we haven't collected any phrases, we don't need the file
                if (file_exists($translation_file)) unlink($translation_file);
                continue;
            }
            \Platform\File\File::ensureFullPath($translation_file, true);
            $existing_phrases = self::getPhrasesFromTranslationFile($translation_file);
            $new_phrases = array();
            foreach ($phrases as $phrase) {
                $new_phrases[$phrase] = (string)$existing_phrases[$phrase];
            }
            self::saveTranslationFile($translation_file, $language_key, $new_phrases);
        }
    }
    
    /**
     * Create or update translation files for all files in the given directory.
     * @param string $directory Directory to search for files in
     */
    public static function createUpdateTranslationFilesFromDirectory(string $directory) {
        $files = self::getFileListFromDirectory($directory, array('php', 'js', 'frm', 'form'));
        foreach ($files as $file) {
            // Handle it
            $phrases = self::getPhrasesFromFile($file);
            self::createUpdateTranslationFilesWithPhrases($file, $phrases);
        }
    }

    /**
     * Get a specific array key from the translation configuration
     * @global array $platform_configuration
     * @param string $key Name of key
     * @return mixed Value of key
     */
    public static function getConfiguration(string $key) {
        $translation_configuration = \Platform\Platform::getConfiguration('translation');
        return $translation_configuration[$key];
    }
    
    /**
     * Get the complete path for the CSV file of a given language.
     * @param string $language_key Language key
     * @return string Complete path to file
     */
    public static function getCSVFileName(string $language_key) : string {
        return self::getTranslationDir().$language_key.'.csv';
    }
    
    /**
     * Get directories from configuration. Directories without a leading slash is
     * considered relative from the web site root.
     * @return array Array of complete path to directories
     */
    public static function getDirectoriesFromConfiguration() : array {
        $directories = self::getConfiguration('directories');
        $result = array();
        foreach ($directories as $directory) {
            if (substr($directory,0,1) !== '/') $directory = (substr($_SERVER['DOCUMENT_ROOT'],-1) == '/' ? $_SERVER['DOCUMENT_ROOT'] : $_SERVER['DOCUMENT_ROOT'].'/').$directory;
            $result[] = $directory;
        }
        return $result;
    }

    /**
     * Get a list of all files of a given type, from a directory and subdirectory
     * @param string $directory Directory
     * @param array $extensions Extensions to find
     * @return array Array of complete file paths
     */
    public static function getFileListFromDirectory(string $directory, array $extensions = array()) : array {
        // Ensure trailing slash
        if (substr($directory, strlen($directory) - 1, 1) != "/")
            $directory .= "/";

        $result = array();
        // Go through dir
        $dh = opendir($directory);
        while ($filename = readdir($dh)) {
            // Skip self and parent
            if ($filename == "." || $filename == "..") continue;
            // Iterate on subdirectories
            if (is_dir($directory.$filename))
                $result = array_merge($result, self::getFileListFromDirectory ($directory.$filename, $extensions));
            else {
                // Handle correct file types
                $extension = \Platform\File\File::extractExtension($filename);
                if (in_array($extension, $extensions)) {
                    $result[] = $directory.$filename;
                }
            }
        }
        closedir($dh);
        return $result;
    }
    

    /**
     * Get an array of available languages hashed by language codes
     * @param bool $include_base_language Decide if the base language should be included in the array
     * @return array
     */
    public static function getLanguageArray(bool $include_base_language = true) {
        $result = array();
        foreach (self::getConfiguration('available') as $language_key) {
            $language_key = trim($language_key);
            if (! $include_base_language && self::getConfiguration('base_language') == $language_key) continue;
            if (! isset(self::$complete_languages[$language_key])) trigger_error('Invalid language '.$language_key.' in config file.', E_USER_ERROR);
            $result[$language_key] = self::$complete_languages[$language_key];
        }
        return $result;
    }
    
    public static $languages_to_load = false;
    
    /**
     * Get which languages we should load
     * @return array Language codes to load
     */
    private static function getLanguagesToLoad() {
        if (! self::$languages_to_load) {
            $languages_to_load = array(self::getUserLanguage());
            if (Instance::getActiveInstanceID() && ! in_array(self::getInstanceLanguage(), $languages_to_load)) $languages_to_load[] = self::getInstanceLanguage();
            self::$languages_to_load = $languages_to_load;
        }
        return self::$languages_to_load;
    }
    
    /**
     * Get a save string for writing in the translation file
     * @param string $string String
     * @return string Save string for php/javascript file
     */
    public static function getSaveString(string $string) : string {
        return preg_replace('/([^\\\\])\'/', '$1\\\'', $string);
    }
    
    /**
     * Extract all phrases from a given translation file
     * @param string $translation_file Full path to translation file
     * @return type
     */
    public static function getPhrasesFromTranslationFile(string $translation_file) : array {
        $result = array();
        $lines = file($translation_file);
        foreach ($lines as $line) {
            if (preg_match('/\\$platform_language\\[\'[a-z]*\'\\]\\[\'(.*[^\\\\])?\'\\] = \'(.*[^\\\\])?\'/', $line, $match)) {
                $result[stripslashes($match[1])] = $match[2];
            }
        }
        return $result;
    }
    
    /**
     * Get the complete name of a translation file from an original file
     * @param string $original_file Complete path to original file
     * @param string $language_key Language key to use
     * @return string Complete path for translation file
     */
    public static function getTranslationFileFromOriginalFile(string $original_file, string $language_key) {
        $web_root = $_SERVER['DOCUMENT_ROOT'];
        if (substr($web_root,-1) != '/') $web_root .= '/';
        $final_slash_position = strrpos($original_file, '/');
        $original_path = substr($original_file, strlen($web_root), $final_slash_position-strlen($web_root)+1);
        $filename = substr($original_file,$final_slash_position+1);
        $dot_position = strrpos($filename, '.');
        if ($dot_position !== false) {
            $extension = \Platform\File\File::extractExtension($filename);
            $filename = substr($filename,0,$dot_position);
        } else {
            $extension = '';
        }
        if (in_array($extension, array('frm', 'tut'))) $extension = 'php';
        $language_path = self::getConfiguration('translation_directory');
        return $web_root.$language_path.$original_path.$filename.'.'.$language_key.'.'.$extension;
    }
    
    /**
     * Get an array of available language codes
     * @param bool $include_base_language Decide if the base language should be included in the array
     * @return array Available language codes
     */
    public static function getLanguageKeys(bool $include_base_language = true) : array {
        return array_keys(self::getLanguageArray($include_base_language));
    }
    
    /**
     * Get all phrases found inside a given original file
     * @param type $original_file Original file full path
     * @return array All phrases in file
     */
    public static function getPhrasesFromFile(string $original_file) : array {
        $ext = \Platform\File\File::extractExtension($original_file);
        
        $function_names = self::getConfiguration('function_names');

        $file_content = implode(" ", @file($original_file));
        $phrases = array();
        if ($ext == 'frm' || $ext == 'form') {
            if (preg_match_all('/(label|head)="(.*?)"/', $file_content, $matches)) {
                foreach ($matches[2] as $m) $phrases[] = stripslashes($m);
            }
            if (preg_match_all('/\{(.*?)\}/', $file_content, $matches)) {
                foreach ($matches[1] as $m) $phrases[] = stripslashes($m);
            }
            if (preg_match_all('/value-[^=]+="(.*?)"/', $file_content, $matches)) {
                foreach ($matches[1] as $m) $phrases[] = stripslashes($m);
            }
        } else {
            foreach ($function_names as $function_name) {
                if (preg_match_all('/([^a-z]|^)'.$function_name.'\("(.*?(?<![\\\\]))"/i', $file_content, $matches)) {
                    foreach ($matches[2] as $m) $phrases[] = stripslashes($m);
                }
                if (preg_match_all('/([^a-z]|^)'.$function_name.'\(\'(.*?(?<![\\\\]))\'/i', $file_content, $matches)) {
                    foreach ($matches[2] as $m) $phrases[] = stripslashes($m);
                }
            }
        }
        return array_unique($phrases);
    }
    
    /**
     * Get the directory containing translation files
     * @return string
     */
    public static function getTranslationDir() : string {
        $root = $_SERVER['DOCUMENT_ROOT'];
        if (substr($root,-1) != '/') $root .= '/';
        return $root.self::getConfiguration('translation_directory');
    }
    
    /**
     * Get the current language of the instance
     * @return string Language key
     */
    public static function getInstanceLanguage() : string {
        if (! \Platform\Server\Instance::getActiveInstanceID()) return self::getConfiguration('default_language') ?: 'en';
        if (! $_SESSION['platform']['instance_language'] || \Platform\Server\Instance::getActiveInstanceID() != $_SESSION['platform']['instance_language_id']) {
            $_SESSION['platform']['instance_language_id'] = \Platform\Server\Instance::getActiveInstanceID();
            $_SESSION['platform']['instance_language'] = Property::getForUser(0, 'instance_language') ?: self::getConfiguration('default_language');
        }
        return $_SESSION['platform']['instance_language'] ?: 'en';
    }

    /**
     * Get the translation js files for the given original js file (if one exists)
     * @param string $js_file Javascript file (as URL)
     * @return array Javascript translation files (as URL array)
     */
    public static function getJSFilesForFile(string $js_file) : array {
        
        $local_file = self::convertURLToServerPath($js_file);
        if ($local_file === false) return array();
        $results = array();
        foreach (self::getLanguagesToLoad() as $language_key) {
            $translation_file = self::getTranslationFileFromOriginalFile($local_file, $language_key);
            if (file_exists($translation_file)) {
                $results[] = self::convertServerPathToURL($translation_file);
            }
        }
        return $results;
    }

    /**
     * Extract accepted language keys from HTTP header
     * @return array
     */
    private static function getLanguageCodeFromHTTP() : array {
        $result = array();
        $header_parts = explode(';', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
        $language_codes_string = current($header_parts);
        $language_codes = explode(',', $language_codes_string);
        foreach ($language_codes as $language_code) {
            $code_parts = explode('-',$language_code);
            $relevant_code = $code_parts[0];
            $result[] = $relevant_code;
        }
        return $result;
    }
    
    /**
     * Get the current user language. First try from cookie, then from browser, then from instance
     * and at last select the default language
     * @return string Language key
     */
    public static function getUserLanguage() : string {
        $valid_languages = self::getLanguageKeys();
        if (in_array($_COOKIE['platform_translation_language'], $valid_languages)) return $_COOKIE['platform_translation_language'];
        foreach (self::getLanguageCodeFromHTTP() as $language_key) {
            if (in_array($language_key, $valid_languages)) return $language_key;
        }
        return self::getInstanceLanguage();
    }

    /**
     * Check if translation is enabled in the configuration
     * @global array $platform_configuration
     * @return bool True if enabled
     */
    public static function isEnabled() {
        return is_array(\Platform\Platform::getConfiguration('translation'));
    }

    /**
     * Read translations from a CSV file and return them
     * @param string $csv_file CSV file name
     * @return array Translations hashed by original sentences.
     */
    public static function loadTranslationsFromCSVFile(string $csv_file) : array {
        $translation_map = array();
        $fh = fopen($csv_file, 'r');
        if ($fh === false) trigger_error('Couldn\'t open translation file '.$csv_file, E_USER_ERROR);
        while ($csv_fields = fgetcsv($fh, 0)) {
            if (count($csv_fields) < 2) continue;
            $translation_map[stripslashes($csv_fields[0])] = $csv_fields[1];
        }
        fclose($fh);
        return $translation_map;
    }
    
    /**
     * Prepare the translations for an original file by including the relevant translation files.
     * @global array $platform_language Array with relevant translations
     * @param string $original_file Original file to locate translations from
     */
    public static function prepareTranslationsForFile(string $original_file) {
        global $platform_language;
        if (! in_array($original_file, self::$translations_loaded_file_table)) self::$translations_loaded_file_table[] = $original_file;
        foreach (self::getLanguagesToLoad() as $language_key) {
            $translation_file = self::getTranslationFileFromOriginalFile($original_file, $language_key);
            if (file_exists($translation_file)) {
                include_once $translation_file;
            } else {
                if ($_POST['powerdebug']) echo "NOT FOUND!";
            }
        }
    }
    
    /**
     * This will reload translations for all php files, where translations have
     * already been loaded.
     */
    public static function reloadAllTranslations() {
        self::$languages_to_load = false;
        foreach (self::$translations_loaded_file_table as $original_file) {
            if ($_POST['powerdebug']) echo "\nRELOAD FOR: ".$original_file;
            self::prepareTranslationsForFile($original_file);
        }
    }

    /**
     * Render the nessecery head elements for using translations
     */
    public static function renderHeadSection() {
        echo '<script type="text/javascript">'."\n";
        echo 'var $platform_language = [];'."\n";
        echo 'var $user_language = \''.self::getUserLanguage().'\';'."\n";
        echo 'var $instance_language = \''.self::getInstanceLanguage().'\';'."\n";
        
        foreach (self::getLanguageKeys() as $language_key) {
            echo '$platform_language[\''.$language_key.'\'] = [];'."\n";
        }
        echo '</script>'."\n";
    }
    
    /**
     * Render javascript include files for the given javascript file
     * @param string $original_file
     */
    public static function renderJSFilesForFile(string $original_file) {
        if (self::isEnabled()) {
            $js_files = self::getJSFilesForFile($original_file);
            foreach ($js_files as $language_file)
                echo '<script src="'.$language_file.'" type="text/javascript"></script>';
        }
    }
    
    /**
     * Apply replacement values on a string
     * @param string $string Original string
     * @param array $replacement_map Replacement values
     * @return string The replaced string
     */
    public static function replaceStringParameters(string $string, array $replacement_map) : string {
        $search_map = array(); $replace_map = array();
        $i = 1;
        foreach ($replacement_map as $replace) {
            $search_map[] = '%'.($i++);
            $replace_map[] = $replace;
        }
        return str_replace($search_map, $replace_map, $string);
    }
    
    /**
     * Save a CSV-file with phrases
     * @param string $language_key
     * @param array $phrases
     */
    public static function saveCSVFile(string $language_key, array $phrases) {
        $fh = fopen(self::getCSVFileName($language_key), 'w');
        foreach ($phrases as $original_phrase => $translated_phrase) {
            fwrite($fh, '"'.str_replace('"', '""', $original_phrase).'",');
            fwrite($fh, '"'.str_replace('"', '""', $translated_phrase).'"');
            fwrite($fh, "\n");
        }
        fclose($fh);
    }
    

    /**
     * Save a translation file with the given phrases
     * @param string $translation_file Full pathname of file
     * @param string $language_key Language key for language in file
     * @param array $phrases Translated phrases hashed by original phrases.
     */
    public static function saveTranslationFile(string $translation_file, string $language_key, array $phrases) {
        $fh = fopen($translation_file, 'w');
        $is_php_file = \Platform\File\File::extractExtension($translation_file) == 'php';
        if ($is_php_file) fwrite($fh, "<?php\n");
        foreach ($phrases as $original_text => $translated_text) {
            fwrite($fh, '$platform_language[\''.$language_key.'\'][\''.self::getSaveString($original_text).'\'] = \''.self::getSaveString($translated_text).'\';'."\n");
        }
        fclose($fh);
    }
    
    /**
     * Set the language for the current instance
     * @param string $language_key Language key
     */
    public static function setInstanceLanguage(string $language_key) {
        if (! in_array($language_key, self::getLanguageKeys())) trigger_error('Tried to set invalid language '.$language_key, E_USER_ERROR);
        // Check if this language is something which isn't loaded yet
        $new_language = ! in_array($language_key, self::getLanguagesToLoad());
        \Platform\Security\Property::setForUser(0, 'instance_language', '', $language_key);
        unset($_SESSION['platform']['instance_language']);
        if ($new_language) self::reloadAllTranslations();
    }
    
    /**
     * Set the user language and store it in a cookie living for a year
     * @param string $language_key User language
     */
    public static function setUserLanguage(string $language_key) {
        if (! in_array($language_key, self::getLanguageKeys())) trigger_error('Tried to set invalid language '.$language_key, E_USER_ERROR);
        // Check if this language is something which isn't loaded yet
        $new_language = ! in_array($language_key, self::getLanguagesToLoad());
        setcookie('platform_translation_language', $language_key, time()+60*60*24*360, '/');
        if ($new_language) self::reloadAllTranslations();
    }
    
    /**
     * Translate a phrase for a user
     * @global array $platform_language Global translation array
     * @param string $phrase Phrase to translate
     * @return string Translated phrase or original phrase if no translation found
     */
    public static function translateForUser() : string {
        global $platform_language;
        $args = func_get_args();
        if (count($args) < 1) return '';
        
        $phrase = array_shift($args);
        
        if (self::isEnabled()) $translated_phrase = $platform_language[self::getUserLanguage()][$phrase] ?: $phrase;
        else $translated_phrase = $phrase;
        
        return self::replaceStringParameters($translated_phrase, $args);
    }
    
    /**
     * Translate a phrase for an instance
     * @global array $platform_language Global translation array
     * @param string... First the phrase and then the replacement values
     * @return string Translated phrase or original phrase if no translation found
     */
    public static function translateForInstance() : string {
        global $platform_language;
        $args = func_get_args();
        if (count($args) < 1) return '';
        
        $phrase = array_shift($args);

        if (! self::isEnabled()) return $phrase;
        
        $translated_phrase = $platform_language[self::getInstanceLanguage()][$phrase] ?: $phrase;
        
        return self::replaceStringParameters($translated_phrase, $args);
    }
    
    /**
     * Update translation files with phrases from the CSV-files
     */
    public static function updateTranslationFilesFromCSVFiles() {
        // Gather files
        $files = self::getFileListFromDirectory(self::getTranslationDir(), array('js', 'php'));
        foreach(self::getLanguageKeys(false) as $language_key) {
            $csv_file = self::getCSVFileName($language_key);
            if (!file_exists($csv_file)) continue;
            // Load language map
            $translations = self::loadTranslationsFromCSVFile($csv_file);
            foreach ($files as $file) {
                // Only pick current language files
                if (preg_match('/([a-z]{2})\\.[a-z]{2,3}$/', $file, $match)) {
                    // Continue if language doesn't match
                    if ($match[1] != $language_key) continue;
                    // Load existing.
                    $phrases = self::getPhrasesFromTranslationFile($file);
                    // Write new phrases where they are needed.
                    $newphrases = array();
                    foreach ($phrases as $original_phrase => $translated_phrase) {
                        if (isset($translations[$original_phrase]) && trim($translations[$original_phrase]) != '') $newphrases[$original_phrase] = $translations[$original_phrase];
                        else $newphrases[$original_phrase] = (string)$translated_phrase;
                    }
                    // Write new file
                    self::saveTranslationFile($file, $language_key, $newphrases);
                }
            }            
        }        
    }
    
    /**
     * Update all language files with the latest phrases from the code
     */
    public static function updateTranslationFilesFromCode() {
        $function_names = self::getConfiguration('function_names');
        $directories = self::getDirectoriesFromConfiguration();
        if (! is_array($function_names)) trigger_error('Invalid function names in configuration', E_USER_ERROR);
        if (! is_array($directories)) trigger_error('Invalid directory names in configuration', E_USER_ERROR);
        foreach ($directories as $directory) {
            if (! is_dir($directory)) trigger_error('Invalid directory: '.$directory, E_USER_ERROR);
            self::createUpdateTranslationFilesFromDirectory($directory);
        }
    }

    /**
     * Complete language code array
     * @var array 
     */
    private static $complete_languages = array(
        'aa' => 'Afar',
        'ab' => 'Abkhaz',
        'ae' => 'Avestan',
        'af' => 'Afrikaans',
        'ak' => 'Akan',
        'am' => 'Amharic',
        'an' => 'Aragonese',
        'ar' => 'Arabic',
        'as' => 'Assamese',
        'av' => 'Avaric',
        'ay' => 'Aymara',
        'az' => 'Azerbaijani',
        'ba' => 'Bashkir',
        'be' => 'Belarusian',
        'bg' => 'Bulgarian',
        'bh' => 'Bihari',
        'bi' => 'Bislama',
        'bm' => 'Bambara',
        'bn' => 'Bengali',
        'bo' => 'Tibetan Standard, Tibetan, Central',
        'br' => 'Breton',
        'bs' => 'Bosnian',
        'ca' => 'Catalan; Valencian',
        'ce' => 'Chechen',
        'ch' => 'Chamorro',
        'co' => 'Corsican',
        'cr' => 'Cree',
        'cs' => 'Czech',
        'cu' => 'Old Church Slavonic, Church Slavic, Church Slavonic, Old Bulgarian, Old Slavonic',
        'cv' => 'Chuvash',
        'cy' => 'Welsh',
        'da' => 'Danish',
        'de' => 'German',
        'dv' => 'Divehi; Dhivehi; Maldivian;',
        'dz' => 'Dzongkha',
        'ee' => 'Ewe',
        'el' => 'Greek, Modern',
        'en' => 'English',
        'eo' => 'Esperanto',
        'es' => 'Spanish; Castilian',
        'et' => 'Estonian',
        'eu' => 'Basque',
        'fa' => 'Persian',
        'ff' => 'Fula; Fulah; Pulaar; Pular',
        'fi' => 'Finnish',
        'fj' => 'Fijian',
        'fo' => 'Faroese',
        'fr' => 'French',
        'fy' => 'Western Frisian',
        'ga' => 'Irish',
        'gd' => 'Scottish Gaelic; Gaelic',
        'gl' => 'Galician',
        'gn' => 'GuaranÃƒÂ­',
        'gu' => 'Gujarati',
        'gv' => 'Manx',
        'ha' => 'Hausa',
        'he' => 'Hebrew (modern)',
        'hi' => 'Hindi',
        'ho' => 'Hiri Motu',
        'hr' => 'Croatian',
        'ht' => 'Haitian; Haitian Creole',
        'hu' => 'Hungarian',
        'hy' => 'Armenian',
        'hz' => 'Herero',
        'ia' => 'Interlingua',
        'id' => 'Indonesian',
        'ie' => 'Interlingue',
        'ig' => 'Igbo',
        'ii' => 'Nuosu',
        'ik' => 'Inupiaq',
        'io' => 'Ido',
        'is' => 'Icelandic',
        'it' => 'Italian',
        'iu' => 'Inuktitut',
        'ja' => 'Japanese (ja)',
        'jv' => 'Javanese (jv)',
        'ka' => 'Georgian',
        'kg' => 'Kongo',
        'ki' => 'Kikuyu, Gikuyu',
        'kj' => 'Kwanyama, Kuanyama',
        'kk' => 'Kazakh',
        'kl' => 'Kalaallisut, Greenlandic',
        'km' => 'Khmer',
        'kn' => 'Kannada',
        'ko' => 'Korean',
        'kr' => 'Kanuri',
        'ks' => 'Kashmiri',
        'ku' => 'Kurdish',
        'kv' => 'Komi',
        'kw' => 'Cornish',
        'ky' => 'Kirghiz, Kyrgyz',
        'la' => 'Latin',
        'lb' => 'Luxembourgish, Letzeburgesch',
        'lg' => 'Luganda',
        'li' => 'Limburgish, Limburgan, Limburger',
        'ln' => 'Lingala',
        'lo' => 'Lao',
        'lt' => 'Lithuanian',
        'lu' => 'Luba-Katanga',
        'lv' => 'Latvian',
        'mg' => 'Malagasy',
        'mh' => 'Marshallese',
        'mi' => 'Maori',
        'mk' => 'Macedonian',
        'ml' => 'Malayalam',
        'mn' => 'Mongolian',
        'mr' => 'Marathi (Mara?hi)',
        'ms' => 'Malay',
        'mt' => 'Maltese',
        'my' => 'Burmese',
        'na' => 'Nauru',
        'nb' => 'Norwegian BokmÃƒÂ¥l',
        'nd' => 'North Ndebele',
        'ne' => 'Nepali',
        'ng' => 'Ndonga',
        'nl' => 'Dutch',
        'nn' => 'Norwegian Nynorsk',
        'no' => 'Norwegian',
        'nr' => 'South Ndebele',
        'nv' => 'Navajo, Navaho',
        'ny' => 'Chichewa; Chewa; Nyanja',
        'oc' => 'Occitan',
        'oj' => 'Ojibwe, Ojibwa',
        'om' => 'Oromo',
        'or' => 'Oriya',
        'os' => 'Ossetian, Ossetic',
        'pa' => 'Panjabi, Punjabi',
        'pi' => 'Pali',
        'pl' => 'Polish',
        'ps' => 'Pashto, Pushto',
        'pt' => 'Portuguese',
        'qu' => 'Quechua',
        'rm' => 'Romansh',
        'rn' => 'Kirundi',
        'ro' => 'Romanian, Moldavian, Moldovan',
        'ru' => 'Russian',
        'rw' => 'Kinyarwanda',
        'sa' => 'Sanskrit (Sa?sk?ta)',
        'sc' => 'Sardinian',
        'sd' => 'Sindhi',
        'se' => 'Northern Sami',
        'sg' => 'Sango',
        'si' => 'Sinhala, Sinhalese',
        'sk' => 'Slovak',
        'sl' => 'Slovene',
        'sm' => 'Samoan',
        'sn' => 'Shona',
        'so' => 'Somali',
        'sq' => 'Albanian',
        'sr' => 'Serbian',
        'ss' => 'Swati',
        'st' => 'Southern Sotho',
        'su' => 'Sundanese',
        'sv' => 'Swedish',
        'sw' => 'Swahili',
        'ta' => 'Tamil',
        'te' => 'Telugu',
        'tg' => 'Tajik',
        'th' => 'Thai',
        'ti' => 'Tigrinya',
        'tk' => 'Turkmen',
        'tl' => 'Tagalog',
        'tn' => 'Tswana',
        'to' => 'Tonga (Tonga Islands)',
        'tr' => 'Turkish',
        'ts' => 'Tsonga',
        'tt' => 'Tatar',
        'tw' => 'Twi',
        'ty' => 'Tahitian',
        'ug' => 'Uighur, Uyghur',
        'uk' => 'Ukrainian',
        'ur' => 'Urdu',
        'uz' => 'Uzbek',
        've' => 'Venda',
        'vi' => 'Vietnamese',
        'vo' => 'VolapÃƒÂ¼k',
        'wa' => 'Walloon',
        'wo' => 'Wolof',
        'xh' => 'Xhosa',
        'yi' => 'Yiddish',
        'yo' => 'Yoruba',
        'za' => 'Zhuang, Chuang',
        'zh' => 'Chinese',
        'zu' => 'Zulu',
    );

}
