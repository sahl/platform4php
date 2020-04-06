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
     * Get classes from the style array
     * @param string $keyword Class keyword
     * @param string $additional_classes Additional classes to return.
     * @return string Combined class string
     */
    public static function getClass($keyword, $additional_classes = '') {
        Errorhandler::checkParams($keyword, 'string', $additional_classes, 'string');
        return trim(self::$style_array[$keyword].' '.$additional_classes);
    }
    
    /**
     * Shortcut for rendering a script tag directly to page. Will queue the
     * javascript if page isn't started.
     * @param string $js_file
     */
    public static function JSFile($js_file) {
        Errorhandler::checkParams($js_file, 'string');
        echo '<script src="'.$js_file.'" type="text/javascript"></script>';
    }
    
    /**
     * Queue a css file to load when page renders
     * @param string $css_file css file to load
     */
    public static function queueCSSFile($css_file) {
        Errorhandler::checkParams($css_file, 'string');
        if (self::$page_started) self::JSFile ($js_file);
        if (! in_array($css_file, self::$css_files_to_load)) self::$css_files_to_load[] = $css_file;
    }
    
    /**
     * Queue a javascript file to load when page renders
     * @param string $js_file javascript file to load
     */
    public static function queueJSFile($js_file) {
        Errorhandler::checkParams($js_file, 'string');
        if (self::$page_started) trigger_error('Tried to queue JS after page was started!', E_USER_ERROR);
        if (! in_array($js_file, self::$js_files_to_load)) self::$js_files_to_load[] = $js_file;
    }    
    
    /**
     * Render the page start including html, head and body tag
     * @param string $title Page title
     * @param array $js_files Javascript files to include
     * @param array $css_files CSS files to include
     */
    public static function renderPagestart($title, $js_files = array(), $css_files = array()) {
        Errorhandler::checkParams($title, 'string', $js_files, array('array', 'string'), $css_files, array('array', 'string'));
        self::$page_started = true;
        
        echo '<!DOCTYPE html><html><head>';
        echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">';
        echo '<title>'.$title.'</title>';
        
        if (! is_array($css_files)) $css_files = array($css_files);
        $css_files = array_merge(array(
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css',
            '/Platform/Jquery/css/jquery-ui.css',
            '/Platform/Design/css/platform.css',
            '/Platform/Form/css/form.css'
        ), self::$css_files_to_load, $css_files);
        foreach ($css_files as $css_file) {
            echo '<link rel="stylesheet" href="'.$css_file.'" type="text/css">';
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
    
    public static function setPagestarted($started = true) {
        Errorhandler::checkParams($started, 'boolean');
        self::$page_started = $started;
    }
    
    /**
     * Set another style array.
     * @param type $array
     */
    public static function setStyleArray($array) {
        Errorhandler::checkParams($array, 'array');
        self::$style_array = $array;
    }
    
    
    private static $style_array = array(
        'button' => '',
        'datarecord_editcomplex' => 'platform_autocenter',
        'datarecord_row' => 'platform_row',
        'datarecord_column' => 'platform_column',
        'formfield' => 'platform_field',
        'formfield_checkbox' => '',
        'file_input_frame' => '',
        'platform_table' => '',
        'dropdown_menu' => 'platform_dropdown_menu',
        'dropdown_menu_content' => 'platform_dropdown_menu_content',
        'dropdown_menu_button' => 'platform_dropdown_menu platform_dropdown_menu_button',
        'dropdown_menu_item' => 'platform_dropdown_menu_item',
        'dropdown_menu_item_selected' => 'platform_dropdown_menu_item platform_menuitem_selected',
        'dropdown_menu_top_item' => 'platform_dropdown_menu_top_item',
        'dropdown_menu_top_item_selected' => 'platform_dropdown_menu_top_item platform_menuitem_selected'
    );
    
}