<?php
namespace Platform;

class User extends Datarecord {
    
    protected static $database_table = 'users';
    protected static $structure = false;
    protected static $key_field = false;
    protected static $location = self::LOCATION_INSTANCE;
    
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
                'label' => 'Username',
                'required' => true,
                'fieldtype' => self::FIELDTYPE_TEXT
            ),
            'password' => array(
                'label' => 'Password',
                'fieldtype' => self::FIELDTYPE_PASSWORD
            )
        ));
        parent::buildStructure();
    }
    
    /**
     * Try to login with a user name and password
     * @param string $username
     * @param string $password
     * @return boolean True on success and user is logged in.
     */
    public static function tryLogin($username, $password) {
        $filter = new Filter('\\Platform\\User');
        $filter->addCondition(new FilterConditionMatch('username', $username));
        $filter->addCondition(new FilterConditionMatch('password', $password));
        $result = $filter->execute();
        if ($result->getCount()) {
            $user = $result->get(0);
            $token = Accesstoken::acquire($user);
            return true;
        }
        return false;
    }
}