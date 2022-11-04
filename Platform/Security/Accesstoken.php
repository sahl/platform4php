<?php
namespace Platform\Security;

use Platform\ConditionLesser;
use Platform\ConditionMatch;
use Platform\Datarecord;
use Platform\Filter;
use Platform\Page;
use Platform\Server\Instance;
use Platform\User;
use Platform\Utilities\Database;
use Platform\Utilities\Semaphore;
use Platform\Utilities\Time;

class Accesstoken extends Datarecord {
    
    protected static $database_table = 'platform_accesstokens';
    protected static $structure = false;
    protected static $key_field = false;
    protected static $title_field = false;
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
                'fieldtype' => self::FIELDTYPE_TEXT,
                'key' => true,
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
     * @param User $user User to grant access
     * @param int $seconds_to_live Seconds for the token to live.
     * @return Accesstoken The token
     */
    public static function acquire(User $user, int $seconds_to_live = 3600) : Accesstoken {
        if (! $user->isInDatabase()) trigger_error('Tried to acquire token for unsaved user!', E_USER_ERROR);
        $accesstoken = static::generateBaseToken($seconds_to_live);
        $accesstoken->user_ref = $user->user_id;
        $accesstoken->save();
        self::$current_user_id = $accesstoken->user_ref;
        return $accesstoken;
    }

    /**
     * Acquire an anonymous token, which isn't tied to any user.
     * @param int $seconds_to_live Seconds for the token to live.
     * @return Accesstoken The token
     */
    public static function acquireAnonymous(int $seconds_to_live = 3600) : Accesstoken {
        $accesstoken = static::generateBaseToken($seconds_to_live);
        $accesstoken->setSession();
        return $accesstoken;
    }
    
    /**
     * Clear token information from session.
     * @param bool $destroy_entire_session If this is set to true, then we destroy the entire PHP session
     */
    private static function clearSession(bool $destroy_entire_session = false) {
        if ($destroy_entire_session) $_SESSION = array();
        else unset($_SESSION['token_code']);
    }

    /**
     * Delete expired access tokens from the database.
     */
    public static function deleteExpiredTokens() {
        $filter = new Filter(get_called_class());
        $filter->addCondition(new ConditionLesser('expire_date', Time::now()));
        $datacollection = $filter->execute();
        $datacollection->deleteAll();
    }
    
    /**
     * Destroy current session effectually logging out the user.
     * @param bool $destroy_entire_session If this is set to true, then we destroy the entire PHP session
     */
    public static function destroySession(bool $destroy_entire_session = true) {
        $accesstoken = static::getByTokencode(static::getSavedTokenCode());
        // Nothing to destroy
        if (! $accesstoken->isInDatabase()) return;
        // Destroy it
        static::deleteByID($accesstoken->token_id);
        static::clearSession($destroy_entire_session);
    }
    
    /**
     * Generate a base token and saves it. The token is returned in write mode.
     * @param int $seconds_to_live The number of seconds the token should be valid
     * @return Accesstoken The generated access token
     */
    public static function generateBaseToken(int $seconds_to_live = 3600) : Accesstoken {
        $accesstoken = new static();
        if (!Semaphore::wait('accesstoken_generator')) trigger_error('Waited for token generator for an excess amount of time.', E_USER_ERROR);
        $accesstoken->generateTokenCode();
        $timestamp = new Time('now');
        $accesstoken->expire_date = $timestamp->add($seconds_to_live);
        $accesstoken->seconds_to_live = $seconds_to_live;
        $accesstoken->save(true, true);
        Semaphore::release('accesstoken_generator');
        return $accesstoken;
    }

    /**
     * Generate a token code into this AccessToken
     */
    private function generateTokenCode() {
        // Check if token exists
        do {
            $token_code = sha1(rand());
            $filter = new Filter(get_called_class());
            $filter->addCondition(new ConditionMatch('token_code', $token_code));
        } while ($filter->execute()->getCount());
        $this->token_code = $token_code;
    }
    
    /**
     * Try to retrieve an active AccessToken by its tokencode
     * @param string $token_code Token code
     * @return Accesstoken The Accesstoken or a new token if none was found
     */
    public static function getByTokencode(string $token_code) : Accesstoken {
        if (! $token_code) return new static();
        $filter = new Filter(get_called_class());
        $filter->addCondition(new ConditionMatch('token_code', $token_code));
        return $filter->executeAndGetFirst();
    }
    
    /**
     * Get the current user, based on an active token
     * @return int User ID
     */
    public static function getCurrentUserID() {
        // Return zero if no active instance ID
        if (! Instance::getActiveInstanceID()) return 0;
        if (! self::$current_user_id) {
            // Try to acquire from current accesstoken
            $token = self::getByTokencode((string)static::getSavedTokenCode());            
            if ($token->isValid()) self::$current_user_id = $token->user_ref;
        }
        return self::$current_user_id;
    }
    
    public static function getSavedToken() {
        $token_code = static::getSavedTokenCode();
        if (! $token_code) return null;
        return static::getByTokencode($token_code);
    }
    
    /**
     * Get the saved token code from session
     * @return string
     */
    public static function getSavedTokenCode() {
        return $_SESSION['token_code'] ?: '';
    }
    
    /**
     * Check if this Accesstoken is valid.
     * @return bool
     */
    public function isValid() : bool {
        return $this->isInDatabase() && $this->expire_date->isAfter(new Time('now'));
    }
    
    /**
     * Do a quick extension on this Accesstoken to make it last longer
     * @param int $seconds_to_live Seconds to live from now or omit to use initial value
     */
    public function quickExtend(int $seconds_to_live = -1) {
        if ($seconds_to_live < 0) $seconds_to_live = $this->seconds_to_live;
        $timestamp = new Time('now');
        $this->expire_date = $timestamp->add($seconds_to_live);
        // Make a dirty write primary for speed reasons
        if ($this->isInDatabase()) Database::instanceQuery("UPDATE ".static::$database_table.' SET expire_date = '.$this->getFieldForDatabase('expire_date', $this->expire_date)." WHERE token_id = ".$this->token_id);
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
    public function setSession() {
        $_SESSION['token_code'] = $this->token_code;
    }
    
    /**
     * Check if the current session is allowed and represented by an Accesstoken
     * @param string $redirect URL to redirect to, if not valid. If set, then script will terminate on invalid session.
     * @param bool $extend Indicates if the session should be extended if valid
     * @param int $seconds_to_live Seconds to extend session by or omit to use initialization value.
     * @return bool True if valid otherwise false 
     */
    public static function validateSession(string $redirect = '', bool $extend = false, int $seconds_to_live = -1) {
        $valid = true;
        // Check if we have a valid instance
        if (Instance::getActiveInstanceID() === false) $valid = false;
        else {
            // Check if we have a (valid) access token
            $token = static::getByTokencode(static::getSavedTokenCode());
            if ($valid && ! $token->isValid()) $valid = false;
        }
        if (! $valid) {
            // Check for information in URL
            if ($_GET['instance_id']) {
                $instance = new Instance();
                $instance->loadForRead($_GET['instance_id']);
                $instance->activate();
            }
            if ($_GET['token_code']) {
                $token = static::getByTokencode($_GET['token_code']);
                $token->setSession();
                Page::redirectToCurrent();
            }
            static::saveLocation();
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
    
    /**
     * Validate a given token code
     * @param string $token_code
     * @return bool True if token code validated
     */
    public static function validateTokenCode(string $token_code) : bool {
        $access_token = static::getByTokencode($token_code);
        if (! $access_token->isInDatabase() || ! $access_token->isValid()) return false;
        self::$current_user_id = $access_token->user_ref;
        return true;
    }
    
    
}