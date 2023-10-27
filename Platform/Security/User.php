<?php
namespace Platform\Security;
/**
 * Datarecord class for handling users and passwords
 * 
 * Responsible for handling login.
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=user_class
 */

Use Platform\Security\Accesstoken;

class User extends \Platform\DatarecordExtensible {
    
    protected static $username_is_email = false;
    
    protected static $database_table = 'platform_users';
    protected static $structure = false;
    protected static $key_field = false;
    protected static $title_field = false;
    protected static $location = self::LOCATION_INSTANCE;
    
    protected static $delete_strategy = self::DELETE_STRATEGY_PURGE_REFERERS;
    
    protected static $depending_classes = array(
        'Platform\\Security\\Accesstoken',
        'Platform\\Property'
    );
    
    public function canDelete() : bool {
        if ($this->isInDatabase() && $this->user_id == Accesstoken::getCurrentUserID()) return 'Cannot delete current user';
        return parent::canDelete();
    }

    protected static function buildStructure() {
        self::addStructure(array(
            'user_id' => array(
                'invisible' => true,
                'fieldtype' => self::FIELDTYPE_KEY
            ),
            'username' => array(
                'label' => static::$username_is_email ? 'Email' : 'Username',
                'required' => true,
                'fieldtype' => static::$username_is_email ? self::FIELDTYPE_EMAIL : self::FIELDTYPE_TEXT,
                'searchable' => true,
            ),
            'password' => array(
                'label' => 'Password',
                'required' => true,
                'fieldtype' => self::FIELDTYPE_PASSWORD
            )
        ));
        parent::buildStructure();
    }
    
    /**
     * Get ID of current user (if a user is logged in)
     * @return int ID of current user
     */
    public static function getCurrentUserID() : int {
        return Accesstoken::getCurrentUserID();
    }
    
    private static $current_user_object = false;
    
    
    /**
     * Get the current user object (if a user is logged in)
     * @return User Current user object or empty user object
     */
    public static function getCurrentUser() : User {
        if (self::$current_user_object === false) {
            self::$current_user_object = new User();
            $user_id = self::getCurrentUserID();
            if ($user_id) self::$current_user_object->loadForRead($user_id);
        }
        return self::$current_user_object;
    }
    
    /**
     * Try to login with a user name and password
     * @param string $username
     * @param string $password
     * @param int $expire The number of seconds for the login to live
     * @return mixed An accesstoken or false if no login.
     */
    public static function tryLogin(string $username, string $password, int $expire = 60*60*6) {
        $filter = new \Platform\Filter(get_called_class());
        $filter->addCondition(new \Platform\ConditionMatch('username', $username));
        $filter->addCondition(new \Platform\ConditionMatch('password', $password));
        $result = $filter->execute();
        if ($result->getCount()) {
            $user = $result->get(0);
            $token = Accesstoken::acquire($user, $expire);
            return $token;
        }
        return false;
    }
}