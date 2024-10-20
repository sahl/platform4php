<?php
namespace Platform\Page;
/**
 * Class for rendering a basic HTML page structure.
 * 
 * Also responsible for including javascript and css
 * Responsible for drawing everything except the <body> content
  * 
 * @link https://wiki.platform4php.dk/doku.php?id=menuitem_class
 */

use Platform\Utilities\Translation;

class Page {
    
    /**
     * Store javascript files to include
     * @var array
     */
    public static $js_files = [];
    /**
     * Store css files to include
     * @var array 
     */
    public static $css_files = [];
    
    /**
     * Data elements to add to body
     * @var type
     */
    public static $data = [];
    
    /**
     * Indicate if page rendering have started.
     * @var bool 
     */
    private static $page_started = false;
    
    /**
     * Add some data to the body
     * @param string $keyword Keyword to use
     * @param string $value Value to add under keyword
     */
    public static function addData($keyword, $value) {
        static::$data[$keyword] = $value;
    }

    /**
     * Add a timestamp to the end of the filename for when the physical file
     * was last modified
     * @param string $filename Filename as URL path
     * @return string Modified filename or original filename if modification couldn't be made
     */
    protected static function addTimeStamp(string $filename) {
        if (! \Platform\Platform::getConfiguration('timestamp_scripts')) return $filename;
        // Discard files on other servers
        if (mb_substr(mb_strtolower($filename),0,4) == 'http') return $filename;
        // Discard relative filer
        if (mb_substr($filename,0,1) != '/') return $filename;
        // Get full path
        $path_filename = \Platform\Platform::getServerRoot().$filename;
        if (! file_exists($path_filename)) return $filename;
        return mb_strpos($filename, '?') !== false ? $filename .= '&'.filemtime($path_filename) : $filename .= '?'.filemtime($path_filename);
    }    
    
    /**
     * Shortcut for including CSS directly on the page. Will queue the css
     * if page isn't started.
     * @param string $css_file
     */
    public static function CSSFile(string $css_file) {
        if (! self::isPageStarted()) self::queueCSSFile ($css_file);
        else {
            // Check if we already have this
            if (in_array($css_file, self::$css_files)) return;
            echo '<div class="platform_invisible platform_css_postload">'.static::addTimeStamp($css_file).'</div>';
            self::$css_files[] = $css_file;
        }
    }
    
    /**
     * Get URL to last page displayed
     * @return string|bool URL or false if no last page
     */
    public static function getLastPage() {
        $last_page = $_SESSION['platform']['page']['last'];
        return $last_page ?: false;
    }
    
    /**
     * Get the exact URL to this page including get parameters
     * @return string
     */
    public static function getCurrentPage() : string {
        return $_SERVER['PHP_SELF'].($_SERVER['QUERY_STRING'] ? '?'.$_SERVER['QUERY_STRING'] : '');
    }
    
    /**
     * Indicate if page output is started
     * @return bool
     */
    public static function isPageStarted() : bool {
        return self::$page_started;
    }

    /**
     * Shortcut for rendering a script tag directly to page. Will queue the
     * javascript if page isn't started.
     * @param string $js_file
     */
    public static function JSFile(string $js_file) {
        if (! self::isPageStarted()) self::queueJSFile ($js_file);
        else {
            // Check if we already have this
            if (in_array($js_file, self::$js_files)) return;
            
            Translation::renderJSFilesForFile($js_file);
            echo '<div class="platform_post_load_javascript" data-src="'.static::addTimeStamp($js_file).'"></div>';
            
            self::$js_files[] = $js_file;            
        }
    }
    
    /**
     * Queue a css file to load when page renders
     * @param string $css_file css file to load
     */
    public static function queueCSSFile(string $css_file) {
        if (self::isPageStarted()) self::CSSFile ($css_file);
        else {
            if (! in_array($css_file, self::$css_files)) self::$css_files[] = $css_file;
        }
    }
    
    /**
     * Queue a javascript file to load when page renders
     * @param string $js_file javascript file to load
     */
    public static function queueJSFile(string $js_file) {
        if (self::isPageStarted()) self::JSFile ($js_file);
        else {
            // Check if we already have this
            if (in_array($js_file, self::$js_files)) return;
            // Get translations
            if (Translation::isEnabled()) {
                foreach (Translation::getJSFilesForFile($js_file) as $js_language_file) if (! in_array($js_language_file, self::$js_files)) self::$js_files[] = $js_language_file;
            }
            self::$js_files[] = $js_file;
        }
    }    
    
    /**
     * Render the page start including html, head and body tag
     * @param string $title Page title
     * @param mixed $js_files Javascript files to include. Either string with one file name or array with one or more file names.
     * @param mixed $css_files CSS files to include. Either string with one file name or array with one or more file names.
     */
    public static function renderPagestart(string $title, $js_files = [], $css_files = [], array $options = []) {
        self::$page_started = true;
        
        if (! is_array($js_files)) $js_files = $js_files ? [$js_files] : [];
        if (! is_array($css_files)) $css_files = $css_files ? [$css_files] : [];
        
        if (! $options['no_history']) self::storeInHistory();
        
        echo '<!DOCTYPE html><html><head>';
        echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">';
        echo '<title>'.$title.'</title>';
        
        if (! is_array($css_files)) $css_files = array($css_files);
        $css_files = array_merge(array(
            
        ), self::$css_files, $css_files);
        foreach ($css_files as $css_file) {
            echo '<link rel="stylesheet" href="'.static::addTimeStamp($css_file).'" type="text/css">';
        }
        
        Translation::renderHeadSection();
        
        if (! is_array($js_files)) $js_files = array($js_files);
        $js_files = array_merge(self::$js_files, $js_files);
        foreach ($js_files as $js_file) {
            echo '<script src="'.static::addTimeStamp($js_file).'" type="text/javascript"></script>';
        }
        if ($options['custom_head_html']) echo $options['custom_head_html'];
        
        echo '</head><body';
        foreach (static::$data as $key => $value) echo ' data-'.$key.'="'.htmlentities ($value, ENT_QUOTES).'"';
        echo '>';
    }
    
    /**
     * Render page end, ending body and html tag
     */
    public static function renderPageend() {
        echo '</body></html>';
    }
    
    /**
     * Redirect to another page
     * @param string $url Url to page
     */
    public static function redirect(string $url) {
        header('location: '.$url);
        exit;        
    }
    
    /**
     * Redirect back to the current page (removing GET parameters)
     */
    public static function redirectToCurrent() {
        header('location: '.$_SERVER['PHP_SELF']);
        exit;
    }
    
    /**
     * Redirect to the last page visited
     * @return boolean False if no last page
     */
    public static function redirectToLast() {
        $url = self::getLastPage();
        if ($url === false) return false;
        self::redirect($url);
    }
    
    /**
     * Reload this page, by redirecting to itself including GET parameters
     */
    public static function reload() {
        header('location: '.self::getCurrentPage());
        exit;
    }

    /**
     * Indicate that the head is already rendered.
     * @param bool $started
     */
    public static function setPagestarted(bool $started = true) {
        self::$page_started = $started;
    }    
    
    /**
     * Store the current page in history as the last page
     */
    public static function storeInHistory() {
        if ($_SESSION['platform']['page']['current'] == $_SERVER['PHP_SELF']) return;
        $_SESSION['platform']['page']['last'] = $_SESSION['platform']['page']['current'];
        $_SESSION['platform']['page']['current'] = $_SERVER['PHP_SELF'];
    }
    
}

?>
