<?php
namespace Platform;

class Accesstoken extends Datarecord {
    
    protected static $database_table = 'platform_accesstokens';
    protected static $structure = false;
    protected static $key_field = false;
    protected static $location = self::LOCATION_INSTANCE;
    protected static $current_user_id = false;
    
    protected static function buildStructure() {
        self::addStructure(array(
            'token_id' => array(
                'invisible' => true,
                'fieldtype' => self::FIELDTYPE_KEY
            ),
            'token_code' => array(
                'label' => 'Token code',
                'fieldtype' => self::FIELDTYPE_TEXT
            ),
            'user_ref' => array(
                'label' => 'User',
                'fieldtype' => self::FIELDTYPE_REFERENCE_SINGLE,
                'foreign_class' => 'Platform\User'
            ),
            'seconds_to_live' => array(
                'label' => 'Seconds to live',
                'fieldtype' => self::FIELDTYPE_INTEGER
            ),
            'expire_date' => array(
                'label' => 'Token expire',
                'fieldtype' => self::FIELDTYPE_DATETIME
            )
        ));
        parent::buildStructure();
    }

    /**
     * Acquire a token which grants the given user access to the system.
     * @param \Platform\User $user User to grant access
     * @param int $seconds_to_live Seconds for the token to live.
     * @return \Platform\Accesstoken The token
     */
    public static function acquire($user, $seconds_to_live = 3600) {
        Errorhandler::checkParams($user, '\\Platform\\User', $seconds_to_live, 'int');
        if (! $user->isInDatabase()) trigger_error('Tried to acquire token for unsaved user!', E_USER_ERROR);
        $accesstoken = new Accesstoken();
        if (!Semaphore::wait('accesstoken_generator')) trigger_error('Waited for token generator for an excess amount of time.', E_USER_ERROR);
        $accesstoken->generateTokenCode();
        $accesstoken->user_ref = $user->user_id;
        $timestamp = new Time('now');
        $accesstoken->expire_date = $timestamp->add($seconds_to_live);
        $accesstoken->seconds_to_live = $seconds_to_live;
        $accesstoken->save();
        Semaphore::release('accesstoken_generator');
        $accesstoken->setSession();
        self::$current_user_id = $accesstoken->user_ref;
        return $accesstoken;
    }
    
    /**
     * Clear token information from session.
     * @param boolean $destroy_entire_session If this is set to true, then we destroy the entire PHP session
     */
    private static function clearSession($destroy_entire_session = false) {
        Errorhandler::checkParams($destroy_entire_session, 'boolean');
        if ($destroy_entire_session) $_SESSION = array();
        else unset($_SESSION['token_code']);
    }

    /**
     * Delete expired access tokens from the database.
     */
    public static function deleteExpiredTokens() {
        $filter = new Filter('\\Platform\\Accesstoken');
        $filter->addCondition(new ConditionLesser('expire_date', Time::now()));
        $datacollection = $filter->execute();
        $datacollection->deleteAll();
    }
    
    /**
     * Destroy current session effectually logging out the user.
     * @param boolean $destroy_entire_session If this is set to true, then we destroy the entire PHP session
     */
    public static function destroySession($destroy_entire_session = true) {
        Errorhandler::checkParams($destroy_entire_session, 'boolean');
        $accesstoken = self::getByTokencode(self::getSavedTokenCode());
        // Nothing to destroy
        if (! $accesstoken->isInDatabase()) return;
        // Destroy it
        Accesstoken::deleteByID($accesstoken->token_id);
        Accesstoken::clearSession($destroy_entire_session);
    }

    /**
     * Generate a token code into this AccessToken
     */
    private function generateTokenCode() {
        // Check if token exists
        do {
            $token_code = sha1(rand());
            $filter = new Filter('\\Platform\\Accesstoken');
            $filter->addCondition(new ConditionMatch('token_code', $token_code));
        } while ($filter->execute()->getCount());
        $this->token_code = $token_code;
    }
    
    /**
     * Try to retrieve an active AccessToken by its tokencode
     * @param string $token_code Token code
     * @return Accesstoken The Accesstoken or a new token if none was found
     */
    public static function getByTokencode($token_code) {
        Errorhandler::checkParams($token_code, 'string');
        $filter = new Filter('\\Platform\\Accesstoken');
        $filter->addCondition(new \Platform\ConditionMatch('token_code', $token_code));
        return $filter->executeAndGetFirst();
    }
    
    /**
     * Get the current user, based on an active token
     * @return int User ID
     */
    public static function getCurrentUserID() {
        if (! self::$current_user_id) {
            // Try to acquire from current accesstoken
            $token = self::getByTokencode(self::getSavedTokenCode());            
            if ($token->isValid()) self::$current_user_id = $token->user_ref;
        }
        return self::$current_user_id;
    }
    
    /**
     * Get the saved token code from session
     * @return string
     */
    public static function getSavedTokenCode() {
        return $_SESSION['token_code'];
    }
    
    /**
     * Check if this Accesstoken is valid.
     * @return boolean
     */
    public function isValid() {
        return $this->isInDatabase() && $this->expire_date->isAfter(new Time('now'));
    }
    
    /**
     * Do a quick extension on this Accesstoken to make it last longer
     * @param int $seconds_to_live Seconds to live from now or omit to use initial value
     */
    public function quickExtend($seconds_to_live = -1) {
        if ($seconds_to_live < 0) $seconds_to_live = $this->seconds_to_live;
        Errorhandler::checkParams($seconds_to_live, 'int');
        $timestamp = new Time('now');
        $this->expire_date = $timestamp->add($seconds_to_live);
        // Make a dirty write primary for speed reasons
        if ($this->isInDatabase()) Database::instanceQuery("UPDATE ".static::$database_table.' SET expire_date = '.$this->getFieldForDatabase('expire_date', $timestamp)." WHERE token_id = ".$this->token_id);
    }
    
    /**
     * Return to a previously saved location, or do nothing if no location was
     * saved.
     */
    public static function resumeLocation() {
        if ($_SESSION['session_resume_url']) {
            header('location: '.$_SESSION['session_resume_url']);
            exit;
        }
    }
    
    /**
     * Store the current URL location
     */
    public static function saveLocation() {
        $_SESSION['session_resume_url'] = $_SERVER['QUERY_STRING'] ? $_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'] : $_SERVER['PHP_SELF'];
    }
    
    /**
     * Store the token code in session for later retrieval
     */
    private function setSession() {
        $_SESSION['token_code'] = $this->token_code;
    }
    
    /**
     * Check if the current session is allowed and represented by an Accesstoken
     * @param string $redirect URL to redirect to, if not valid. If set, then script will terminate on invalid session.
     * @param boolean $extend Indicates if the session should be extended if valid
     * @param int $seconds_to_live Seconds to extend session by or omit to use initialization value.
     * @return boolean True if valid otherwise false 
     */
    public static function validateSession($redirect = '', $extend = false, $seconds_to_live = -1) {
        Errorhandler::checkParams($redirect, 'string', $extend, 'boolean', $seconds_to_live, 'int');
        // Check for information in URL
        if ($_GET['instance_id']) {
            $instance = new Instance();
            $instance->loadForRead($_GET['instance_id']);
            $instance->activate();
        }
        if ($_GET['token_code']) {
            $token = self::getByTokencode($_GET['token_code']);
            $token->setSession();
        }
        $valid = true;
        // Check if we have a valid instance
        if (Instance::getActiveInstanceID() === false) $valid = false;
        else {
            // Check if we have a (valid) access token
            $token = self::getByTokencode(self::getSavedTokenCode());
            if ($valid && ! $token->isValid()) $valid = false;
        }
        if (! $valid) {
            self::saveLocation();
        }
        
        if (! $valid && $redirect) {
            header('location: '.$redirect);
            exit;
        }
        if ($valid && $extend) {
            $token->quickExtend($seconds_to_live);
        }
        if ($valid) {
            self::$current_user_id = $token->user_ref;
        }
        return $valid;
    }
    
    public static function validateTokenCode($token_code) {
        Errorhandler::checkParams($token_code, 'string');
        $access_token = Accesstoken::getByTokencode($token_code);
        if (! $access_token->isInDatabase() || ! $access_token->isValid()) return false;
        self::$current_user_id = $access_token->user_ref;
        return true;
    }
    
    
}