<?php
namespace Platform;

class Page {
    
    /**
     * Store javascript files to include
     * @var array
     */
    public static $js_files_to_load = [];
    /**
     * Store css files to include
     * @var array 
     */
    public static $css_files_to_load = [];
    
    /**
     * Indicate if page rendering have started.
     * @var boolean 
     */
    private static $page_started = false;
    

    public static function isPageStarted() {
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
            \Platform\Translation::renderJSFilesForFile($js_file);
            echo '<script src="'.$js_file.'" type="text/javascript"></script>';
        }
    }
    
    /**
     * Queue a css file to load when page renders
     * @param string $css_file css file to load
     */
    public static function queueCSSFile(string $css_file) {
        if (! in_array($css_file, self::$css_files_to_load)) self::$css_files_to_load[] = $css_file;
    }
    
    /**
     * Queue a javascript file to load when page renders
     * @param string $js_file javascript file to load
     */
    public static function queueJSFile(string $js_file) {
        // Get translations
        if (Translation::isEnabled()) {
            foreach (Translation::getJSFilesForFile($js_file) as $js_language_file) if (! in_array($js_language_file, self::$js_files_to_load)) self::$js_files_to_load[] = $js_language_file;
        }
        if (! in_array($js_file, self::$js_files_to_load)) self::$js_files_to_load[] = $js_file;
    }    
    
    /**
     * Render the page start including html, head and body tag
     * @param string $title Page title
     * @param array $js_files Javascript files to include
     * @param array $css_files CSS files to include
     */
    public static function renderPagestart(string $title, array $js_files = [], array $css_files = []) {
        self::$page_started = true;
        
        echo '<!DOCTYPE html><html><head>';
        echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">';
        echo '<title>'.$title.'</title>';
        
        if (! is_array($css_files)) $css_files = array($css_files);
        $css_files = array_merge(array(
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css',
            '/Platform/Jquery/css/jquery-ui.css',
            '/Platform/Design/css/platform.css',
        ), self::$css_files_to_load, $css_files);
        foreach ($css_files as $css_file) {
            echo '<link rel="stylesheet" href="'.$css_file.'" type="text/css">';
        }
        
        if (\Platform\Translation::isEnabled()) {
            \Platform\Translation::renderHeadSection();
        }
        
        if (! is_array($js_files)) $js_files = array($js_files);
        $js_files = array_merge(self::$js_files_to_load, $js_files);
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
    
    public static function setPagestarted(bool $started = true) {
        self::$page_started = $started;
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
}

?>
