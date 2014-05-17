<?php
namespace phlopsi\access_control;

use phlopsi\access_control\exception\LengthException;
use phlopsi\access_control\exception\RuntimeException;
use phlopsi\access_control\propel\Permission as PropelPermission;
use phlopsi\access_control\propel\PermissionQuery as PropelPermissionQuery;
use phlopsi\access_control\propel\Role as PropelRole;
use phlopsi\access_control\propel\RoleQuery as PropelRoleQuery;
use phlopsi\access_control\propel\SessionType as PropelSessionType;
use phlopsi\access_control\propel\SessionTypeQuery as PropelSessionTypeQuery;
use phlopsi\access_control\propel\User as PropelUser;
use phlopsi\access_control\propel\UserQuery as PropelUserQuery;

/**
 * @author Patrick Fischer <nbphobos@gmail.com>
 */
class AccessControl
{
    /**
     * @var Role[]
     */
    private $roles = [];
    
    /**
     * @var SessionType[]
     */
    private $session_types = [];
    
    /**
     * @var User[]
     */
    private $users = [];

    /**
     * @param mixed $permission_id
     * @throws LengthException
     * @throws RuntimeException
     */
    public function createPermission($permission_id)
    {
        $permission_id = (string) $permission_id;

        if (empty($permission_id)) {
            throw new LengthException(LengthException::ARGUMENT_IS_EMPTY_STRING);
        }

        try {
            $new_permission = new PropelPermission();
            $new_permission->setExternalId($permission_id);
            $new_permission->save();
        } catch (\Exception $exception) {
            throw new RuntimeException(null, null, $exception);
        }
    }

    /**
     * @param mixed $role_id
     * @return Role
     * @throws LengthException
     * @throws RuntimeException
     */
    public function createRole($role_id)
    {
        $role_id = (string) $role_id;

        if (empty($role_id)) {
            throw new LengthException(LengthException::ARGUMENT_IS_EMPTY_STRING);
        }

        if (\array_key_exists($role_id, $this->roles)) {
            throw new RuntimeException(sprintf(RuntimeException::ENTITY_ALREADY_EXISTS, $role_id));
        }

        try {
            $new_role = new PropelRole();
            $new_role->setExternalId($role_id);
            $new_role->save();
        } catch (\Exception $exception) {
            throw new RuntimeException(null, null, $exception);
        }

        $this->roles[$role_id] = new Role($new_role);

        return $this->roles[$role_id];
    }

    /**
     * @param mixed $session_type_id
     * @return SessionType
     * @throws LengthException
     * @throws RuntimeException
     */
    public function createSessionType($session_type_id)
    {
        $session_type_id = (string) $session_type_id;

        if (empty($session_type_id)) {
            throw new LengthException(LengthException::ARGUMENT_IS_EMPTY_STRING);
        }

        if (\array_key_exists($session_type_id, $this->session_types)) {
            throw new RuntimeException(sprintf(RuntimeException::ENTITY_ALREADY_EXISTS, $session_type_id));
        }

        try {
            $new_session_type = new PropelSessionType();
            $new_session_type->setExternalId($session_type_id);
            $new_session_type->save();
        } catch (\Exception $exception) {
            throw new RuntimeException(null, null, $exception);
        }

        $this->session_types[$session_type_id] = new SessionType($new_session_type);

        return $this->session_types[$session_type_id];
    }

    /**
     * @param mixed $user_id
     * @return User
     * @throws LengthException
     * @throws RuntimeException
     */
    public function createUser($user_id)
    {
        $user_id = (string) $user_id;

        if (empty($user_id)) {
            throw new LengthException(LengthException::ARGUMENT_IS_EMPTY_STRING);
        }

        if (\array_key_exists($user_id, $this->users)) {
            throw new RuntimeException(sprintf(RuntimeException::ENTITY_ALREADY_EXISTS, $user_id));
        }

        try {
            $new_user = new PropelUser();
            $new_user->setExternalId($user_id);
            $new_user->save();
        } catch (\Exception $exception) {
            throw new RuntimeException(null, null, $exception);
        }

        $this->users[$user_id] = new User($new_user);

        return $this->users[$user_id];
    }

    /**
     * @param mixed $permission_id
     * @throws LengthException
     * @throws RuntimeException
     */
    public function deletePermission($permission_id)
    {
        $permission_id = (string) $permission_id;

        if (empty($permission_id)) {
            throw new LengthException(LengthException::ARGUMENT_IS_EMPTY_STRING);
        }

        try {
            PropelPermissionQuery::create()
                ->findOneByExternalId($permission_id)
                ->delete();
        } catch (\Exception $exception) {
            throw new RuntimeException(null, null, $exception);
        }
    }

    /**
     * @param mixed $role_id
     * @throws LengthException
     * @throws RuntimeException
     */
    public function deleteRole($role_id)
    {
        $role_id = (string) $role_id;

        if (empty($role_id)) {
            throw new LengthException(LengthException::ARGUMENT_IS_EMPTY_STRING);
        }

        if (\array_key_exists($role_id, $this->roles)) {
            unset($this->roles[$role_id]);
        }

        try {
            PropelRoleQuery::create()
                ->findOneByExternalId($role_id)
                ->delete();
        } catch (\Exception $exception) {
            throw new RuntimeException(null, null, $exception);
        }
    }

    /**
     * @param mixed $session_type_id
     * @throws LengthException
     * @throws RuntimeException
     */
    public function deleteSessionType($session_type_id)
    {
        $session_type_id = (string) $session_type_id;

        if (empty($session_type_id)) {
            throw new LengthException(LengthException::ARGUMENT_IS_EMPTY_STRING);
        }

        try {
            PropelSessionTypeQuery::create()
                ->findOneByExternalId($session_type_id)
                ->delete();
        } catch (\Exception $exception) {
            throw new RuntimeException(null, null, $exception);
        }
    }

    /**
     * @param mixed $user_id
     * @throws LengthException
     * @throws RuntimeException
     */
    public function deleteUser($user_id)
    {
        $user_id = (string) $user_id;

        if (empty($user_id)) {
            throw new LengthException(LengthException::ARGUMENT_IS_EMPTY_STRING);
        }

        if (\array_key_exists($user_id, $this->users)) {
            unset($this->users[$user_id]);
        }

        try {
            PropelUserQuery::create()
                ->findOneByExternalId($user_id)
                ->delete();
        } catch (\Exception $exception) {
            throw new RuntimeException(null, null, $exception);
        }
    }

    /**
     * @param mixed $role_id
     * @return Role
     * @throws LengthException
     * @throws RuntimeException
     */
    public function retrieveRole($role_id)
    {
        $role_id = (string) $role_id;

        if (empty($role_id)) {
            throw new LengthException(LengthException::ARGUMENT_IS_EMPTY_STRING);
        }

        if (!\array_key_exists($role_id, $this->roles)) {
            try {
                $role = PropelRoleQuery::create()->findOneByExternalId($role_id);
            } catch (\Exception $exception) {
                throw new RuntimeException(null, null, $exception);
            }

            if (is_null($role)) {
                throw new RuntimeException(sprintf(RuntimeException::ENTITY_NOT_FOUND, $role_id));
            }

            $this->roles[$role_id] = new Role($role);
        }

        return $this->roles[$role_id];
    }

    /**
     * @param mixed $session_type_id
     * @return SessionType
     * @throws LengthException
     * @throws RuntimeException
     */
    public function retrieveSessionType($session_type_id)
    {
        $session_type_id = (string) $session_type_id;

        if (empty($session_type_id)) {
            throw new LengthException(LengthException::ARGUMENT_IS_EMPTY_STRING);
        }

        if (!\array_key_exists($session_type_id, $this->session_types)) {
            try {
                $session_type = PropelSessionTypeQuery::create()->findOneByExternalId($session_type_id);
            } catch (\Exception $exception) {
                throw new RuntimeException(null, null, $exception);
            }

            if (is_null($session_type)) {
                throw new RuntimeException(sprintf(RuntimeException::ENTITY_NOT_FOUND, $session_type_id));
            }

            $this->session_types[$session_type_id] = new SessionType($session_type);
        }

        return $this->session_types[$session_type_id];
    }

    /**
     * @param mixed $user_id
     * @return User
     * @throws LengthException
     * @throws RuntimeException
     */
    public function retrieveUser($user_id)
    {
        $user_id = (string) $user_id;

        if (empty($user_id)) {
            throw new LengthException(LengthException::ARGUMENT_IS_EMPTY_STRING);
        }

        if (!\array_key_exists($user_id, $this->users)) {
            try {
                $user = PropelUserQuery::create()->findOneByExternalId($user_id);
            } catch (\Exception $exception) {
                throw new RuntimeException(null, null, $exception);
            }

            if (is_null($user)) {
                throw new RuntimeException(sprintf(RuntimeException::ENTITY_NOT_FOUND, $user_id));
            }

            $this->users[$user_id] = new User($user);
        }

        return $this->users[$user_id];
    }
}