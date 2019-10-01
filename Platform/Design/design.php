<?php
namespace Platform;

class Design {
    
    /**
     * Store javascript files to include
     * @var array
     */
    public static $js_files_to_load = array();
    /**
     * Store css files to include
     * @var array 
     */
    public static $css_files_to_load = array();
    
    /**
     * Indicate if page rendering have started.
     * @var boolean 
     */
    private static $page_started = false;
    
    /**
     * Queue a css file to load when page renders
     * @param string $css_file css file to load
     */
    public static function queueCSSFile($css_file) {
        if (self::$page_started) trigger_error('Tried to queue CSS after page was started!', E_USER_ERROR);
        if (! in_array($css_file, self::$css_files_to_load)) self::$css_files_to_load[] = $css_file;
    }
    
    /**
     * Queue a javascript file to load when page renders
     * @param string $js_file javascript file to load
     */
    public static function queueJSFile($js_file) {
        if (self::$page_started) trigger_error('Tried to queue JS after page was started!', E_USER_ERROR);
        if (! in_array($js_file, self::$js_files_to_load)) self::$js_files_to_load[] = $js_file;
    }    
    
    public static function renderContentBox($box_id, $source, $parameters = array(), $prepare_function = '', $reveal = '') {
        echo '<div class="platform_content_box" id="'.$box_id.'" data-source="'.$source.'" data-parameters="'.http_build_query($parameters).'"';
        if ($prepare_function) echo ' data-prepare_function="'.$prepare_function.'"';
        if ($reveal) echo ' data-reveal="'.$reveal.'"';
        echo '></div>';
    }
    
    /**
     * Render the page start including html, head and body tag
     * @param string $title Page title
     * @param array $js_files Javascript files to include
     * @param array $css_files CSS files to include
     */
    public static function renderPagestart($title, $js_files = array(), $css_files = array()) {
        
        self::$page_started = true;
        
        echo '<!DOCTYPE html><html><head>';
        echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">';
        echo '<title>'.$title.'</title>';
        
        if (! is_array($css_files)) $css_files = array($css_files);
        $css_files = array_merge(array(
            'https://www.w3schools.com/w3css/4/w3.css',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css',
            '/Platform/Jquery/css/jquery-ui.css',
            '/Platform/Design/css/platform.css',
            '/Platform/Form/css/form.css'
        ), self::$css_files_to_load, $css_files);
        foreach ($css_files as $css_file) {
            echo '<link rel="stylesheet" href="'.$css_file.'" type="text/css">';
        }
        
        if (! is_array($js_files)) $js_files = array($js_files);
        $js_files = array_merge(array(
            '/Platform/Jquery/js/jquery.js',
            '/Platform/Jquery/js/jquery-ui.min.js',
            '/Platform/Design/js/general.js',
            '/Platform/Design/js/contentbox.js',
            '/Platform/Design/js/dialogs.js'
        ),self::$js_files_to_load, $js_files);
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
    
}