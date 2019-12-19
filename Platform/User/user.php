<?php
namespace Platform;

class User extends DatarecordExtensible {
    
    protected static $username_is_email = false;
    
    protected static $database_table = 'users';
    protected static $structure = false;
    protected static $key_field = false;
    protected static $location = self::LOCATION_INSTANCE;
    
    protected static $delete_strategy = self::DELETE_STRATEGY_PURGE_REFERERS;
    
    protected static $depending_classes = array(
        'Platform\\Accesstoken',
        'Platform\\UserProperty',
    );
    
    public function canDelete() {
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
                'fieldtype' => static::$username_is_email ? self::FIELDTYPE_EMAIL : self::FIELDTYPE_TEXT
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
     * Try to login with a user name and password
     * @param string $username
     * @param string $password
     * @param boolean $resume_if_possible If this is true, the system will try
     * to return to a previously saved location.
     * @return boolean True on success and user is logged in.
     */
    public static function tryLogin($username, $password, $resume_if_possible = true) {
        $filter = new Filter(get_called_class());
        $filter->addCondition(new FilterConditionMatch('username', $username));
        $filter->addCondition(new FilterConditionMatch('password', $password));
        $result = $filter->execute();
        if ($result->getCount()) {
            $user = $result->get(0);
            $token = Accesstoken::acquire($user);
            if ($resume_if_possible) Accesstoken::resumeLocation ();
            return true;
        }
        return false;
    }
}