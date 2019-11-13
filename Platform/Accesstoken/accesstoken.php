<?php
namespace Platform;

class Accesstoken extends Datarecord {
    
    protected static $database_table = 'accesstokens';
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
                'foreignclass' => 'Platform\User'
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
        $accesstoken = new Accesstoken();
        if (!Semaphore::wait('accesstoken_generator')) trigger_error('Waited for token generator for an excess amount of time.', E_USER_ERROR);
        $accesstoken->generateTokenCode();
        $accesstoken->user_ref = $user;
        $timestamp = new Timestamp('now');
        $accesstoken->expire_date = $timestamp->add($seconds_to_live);
        $accesstoken->save();
        Semaphore::release('accesstoken_generator');
        $accesstoken->setSession();
        return $accesstoken;
    }
    
    /**
     * Clear token information from session.
     * @param boolean $destroy_entire_session If this is set to true, then we destroy the entire PHP session
     */
    private static function clearSession($destroy_entire_session = false) {
        if ($destroy_entire_session) $_SESSION = array();
        else unset($_SESSION['token_code']);
    }
    
    /**
     * Destroy current session effectually logging out the user.
     * @param boolean $destroy_entire_session If this is set to true, then we destroy the entire PHP session
     */
    public static function destroySession($destroy_entire_session = true) {
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
            $filter->addCondition(new FilterConditionMatch('token_code', $token_code));
        } while ($filter->execute()->getCount());
        $this->token_code = $token_code;
    }
    
    /**
     * Try to retrieve an active AccessToken by its tokencode
     * @param string $token_code Token code
     * @return Accesstoken The Accesstoken or a new token if none was found
     */
    public static function getByTokencode($token_code) {
        $filter = new Filter('\\Platform\\Accesstoken');
        $filter->addCondition(new \Platform\FilterConditionMatch('token_code', $token_code));
        $accesstoken = $filter->executeAndGetFirst();
        return $accesstoken instanceof Accesstoken ? $accesstoken : new Accesstoken();
    }
    
    /**
     * Get the current user, based on an active token
     * @return int User ID
     */
    public static function getCurrentUserID() {
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
        return $this->isInDatabase() && $this->expire_date->isAfter(new Timestamp('now'));
    }
    
    /**
     * Do a quick extension on this Accesstoken to make it last longer
     * @param int $seconds_to_live Seconds to live from now
     */
    public function quickExtend($seconds_to_live = 3600) {
        $timestamp = new Timestamp('now');
        $this->expire_date = $timestamp->add($seconds_to_live);
        // Make a dirty write primary for speed reasons
        if ($this->isInDatabase()) q("UPDATE ".static::$database_table.' SET expire_date = '.$this->getFieldForDatabase('expire_date', $timestamp)." WHERE token_id = ".$this->token_id);
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
     * @param int $seconds_to_live Seconds to extend session by.
     * @return boolean True if valid otherwise false 
     */
    public static function validateSession($redirect = '', $extend = false, $seconds_to_live = 3600) {
        $valid = true;
        // Check if we have a valid instance
        if (Instance::getActiveInstanceID() === false) $valid = false;
        else {
            // Check if we have a (valid) access token
            $token = self::getByTokencode(self::getSavedTokenCode());
            if ($valid && ! $token->isValid()) $valid = false;
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
    
    
}