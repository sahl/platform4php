<?php
namespace Platform;

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
     * Indicate if page rendering have started.
     * @var bool 
     */
    private static $page_started = false;


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
            echo '<div class="platform_invisible platform_css_postload">'.$css_file.'</div>';
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
            echo '<script src="'.$js_file.'" type="text/javascript"></script>';
            
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
     * @param array $js_files Javascript files to include
     * @param array $css_files CSS files to include
     */
    public static function renderPagestart(string $title, array $js_files = [], array $css_files = []) {
        self::$page_started = true;
        
        self::storeInHistory();
        
        echo '<!DOCTYPE html><html><head>';
        echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">';
        echo '<title>'.$title.'</title>';
        
        if (! is_array($css_files)) $css_files = array($css_files);
        $css_files = array_merge(array(
            
        ), self::$css_files, $css_files);
        foreach ($css_files as $css_file) {
            echo '<link rel="stylesheet" href="'.$css_file.'" type="text/css">';
        }
        
        if (Translation::isEnabled()) {
            Translation::renderHeadSection();
        }
        
        if (! is_array($js_files)) $js_files = array($js_files);
        $js_files = array_merge(self::$js_files, $js_files);
        foreach ($js_files as $js_file) {
            echo '<script src="'.$js_file.'" type="text/javascript"></script>';
        }
        echo '</head><body>';
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
        header('location: '.$_SERVER['PHP_SELF'].($_SERVER['QUERY_STRING'] ? '?'.$_SERVER['QUERY_STRING'] : ''));
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
