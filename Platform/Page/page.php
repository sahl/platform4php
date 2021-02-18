<?php
namespace Platform;

class Page {

    /**
     * Redirect to another page
     * @param string $url Url to page
     */
    public static function redirect($url) {
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
