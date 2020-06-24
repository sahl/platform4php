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
}

?>
