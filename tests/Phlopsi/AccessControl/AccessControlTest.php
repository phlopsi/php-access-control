<?php
/**
 * @author Patrick Fischer <nbphobos@gmail.com>
 */

namespace Phlopsi\AccessControl;

use Propel\Generator\Util\SqlParser;

class AccessControlTest extends \PHPUnit_Extensions_Database_TestCase
{
    use Test\DatabaseTestCaseTrait;

    /**
     * @var AccessControl
     */
    private $access_control_faulty;

    /**
     * @var \PHPUnit_Extensions_Database_DB_IDatabaseConnection
     */
    private $connection;

    protected function setUp()
    {
        $this->connection = $this->createDefaultDBConnection(self::$pdo);

        SqlParser::executeString(self::$sql, self::$pdo);

        $this->access_control_faulty = new AccessControl($this->getFaultyConnection());
    }

    protected function tearDown()
    {
        $this->connection = null;
        $this->access_control = null;
        $this->access_control_faulty = null;
    }

    protected function getConnection()
    {
        return $this->connection;
    }

    protected function getDataSet()
    {
    }

    /**
     * @covers \Phlopsi\AccessControl\AccessControl::createPermission
     * @expectedException \Phlopsi\AccessControl\Exception\LengthException
     */
    public function testCreatePermissionWithEmptyId()
    {
        // Arrange
        $access_control = new AccessControl();

        // Act
        $access_control->createPermission('');
    }

    /**
     * @covers \Phlopsi\AccessControl\AccessControl::createPermission
     * @expectedException \Phlopsi\AccessControl\Exception\RuntimeException
     */
    public function testCreatePermissionException()
    {
        // Act
        $this->access_control_faulty->createPermission('TEST_PERMISSION');
    }

    /**
     * @covers \Phlopsi\AccessControl\AccessControl::createPermission
     */
    public function testCreatePermission()
    {
        // Arrange
        $access_control = new AccessControl();

        // Act
        $access_control->createPermission('TEST_PERMISSION');

        // Assert
        $this->assertEquals(1, $this->getConnection()->getRowCount(Propel\Map\PermissionTableMap::TABLE_NAME));
    }

    /**
     * @covers \Phlopsi\AccessControl\AccessControl::deletePermission
     * @expectedException \Phlopsi\AccessControl\Exception\LengthException
     */
    public function testDeletePermissionWithEmptyId()
    {
        // Arrange
        $access_control = new AccessControl();

        // Act
        $access_control->deletePermission('');
    }

    /**
     * @covers \Phlopsi\AccessControl\AccessControl::deletePermission
     */
    public function testDeletePermissionWithInvalidId()
    {
        // Arrange
        $access_control = new AccessControl();

        // Act
        $result = $access_control->deletePermission('TEST_PERMISSION');

        // Assert
        $this->assertFalse($result);
    }

    /**
     * @covers \Phlopsi\AccessControl\AccessControl::deletePermission
     * @expectedException \Phlopsi\AccessControl\Exception\RuntimeException
     */
    public function testDeletePermissionException()
    {
        // Act
        $this->access_control_faulty->deletePermission('TEST_PERMISSION');
    }

    /**
     * @depends testCreatePermission
     * @covers \Phlopsi\AccessControl\AccessControl::deletePermission
     * @uses \Phlopsi\AccessControl\AccessControl::createPermission
     */
    public function testDeletePermission()
    {
        // Arrange
        $access_control = new AccessControl();
        $access_control->createPermission('TEST_PERMISSION');

        // Act
        $result = $access_control->deletePermission('TEST_PERMISSION');

        // Assert
        $this->assertTrue($result);
        $this->assertEquals(0, $this->getConnection()->getRowCount(Propel\Map\PermissionTableMap::TABLE_NAME));
    }

    /**
     * @covers \Phlopsi\AccessControl\AccessControl::createRole
     * @expectedException \Phlopsi\AccessControl\Exception\LengthException
     */
    public function testCreateRoleWithEmptyId()
    {
        // Arrange
        $access_control = new AccessControl();

        // Act
        $access_control->createRole('');
    }

    /**
     * @covers \Phlopsi\AccessControl\AccessControl::createRole
     * @expectedException \Phlopsi\AccessControl\Exception\RuntimeException
     */
    public function testCreateRoleException()
    {
        // Act
        $this->access_control_faulty->createRole('TEST_ROLE');
    }

    /**
     * @covers \Phlopsi\AccessControl\AccessControl::createRole
     */
    public function testCreateRole()
    {
        // Arrange
        $access_control = new AccessControl();

        // Act
        $role = $access_control->createRole('TEST_ROLE');

        // Assert
        $this->assertInstanceOf(\Phlopsi\AccessControl\Role::class, $role);
        $this->assertEquals(1, $this->getConnection()->getRowCount(Propel\Map\RoleTableMap::TABLE_NAME));
    }

    /**
     * @covers \Phlopsi\AccessControl\AccessControl::deleteRole
     * @expectedException \Phlopsi\AccessControl\Exception\LengthException
     */
    public function testDeleteRoleWithEmptyId()
    {
        // Arrange
        $access_control = new AccessControl();

        // Act
        $access_control->deleteRole('');
    }

    /**
     * @covers \Phlopsi\AccessControl\AccessControl::deleteRole
     */
    public function testDeleteRoleWithInvalidId()
    {
        // Arrange
        $access_control = new AccessControl();

        // Act
        $result = $access_control->deleteRole('TEST_ROLE');

        // Assert
        $this->assertFalse($result);
    }

    /**
     * @covers \Phlopsi\AccessControl\AccessControl::deleteRole
     * @expectedException \Phlopsi\AccessControl\Exception\RuntimeException
     */
    public function testDeleteRoleException()
    {
        // Act
        $this->access_control_faulty->deleteRole('TEST_ROLE');
    }

    /**
     * @depends testCreateRole
     * @covers \Phlopsi\AccessControl\AccessControl::deleteRole
     * @uses \Phlopsi\AccessControl\AccessControl::createRole
     */
    public function testDeleteRole()
    {
        // Arrange
        $access_control = new AccessControl();
        $access_control->createRole('TEST_ROLE');

        // Act
        $result = $access_control->deleteRole('TEST_ROLE');

        // Assert
        $this->assertTrue($result);
        $this->assertEquals(0, $this->getConnection()->getRowCount(Propel\Map\RoleTableMap::TABLE_NAME));
    }

    /**
     * @covers \Phlopsi\AccessControl\AccessControl::retrieveRole
     * @expectedException \Phlopsi\AccessControl\Exception\LengthException
     */
    public function testRetrieveRoleWithEmptyId()
    {
        // Arrange
        $access_control = new AccessControl();

        // Act
        $access_control->retrieveRole('');
    }

    /**
     * @covers \Phlopsi\AccessControl\AccessControl::retrieveRole
     * @expectedException \Phlopsi\AccessControl\Exception\RuntimeException
     */
    public function testRetrieveRoleWithInvalidId()
    {
        // Arrange
        $access_control = new AccessControl();

        // Act
        $access_control->retrieveRole('TEST_ROLE');
    }

    /**
     * @depends testCreateRole
     * @covers \Phlopsi\AccessControl\AccessControl::retrieveRole
     * @uses \Phlopsi\AccessControl\AccessControl::createRole
     * @expectedException \Phlopsi\AccessControl\Exception\RuntimeException
     */
    public function testRetrieveRoleException()
    {
        // Arrange
        $access_control = new AccessControl();

        try {
            $access_control->createRole('TEST_ROLE');
        } catch (\Exception $exception) {
            $this->fail($exception->getTraceAsString());
        }

        // Act
        $this->access_control_faulty->retrieveRole('TEST_ROLE');
    }

    /**
     * @depends testCreateRole
     * @covers \Phlopsi\AccessControl\AccessControl::retrieveRole
     * @uses \Phlopsi\AccessControl\AccessControl::createRole
     */
    public function testRetrieveRole()
    {
        // Arrange
        $access_control = new AccessControl();
        $access_control->createRole('TEST_ROLE');

        // Act
        $role = $access_control->retrieveRole('TEST_ROLE');

        // Assert
        $this->assertInstanceOf(\Phlopsi\AccessControl\Role::class, $role);
        $this->assertEquals('TEST_ROLE', $role->getId());
    }

    /**
     * @covers \Phlopsi\AccessControl\AccessControl::createSessionType
     * @expectedException \Phlopsi\AccessControl\Exception\LengthException
     */
    public function testCreateSessionTypeWithEmptyId()
    {
        // Arrange
        $access_control = new AccessControl();

        // Act
        $access_control->createSessionType('');
    }

    /**
     * @covers \Phlopsi\AccessControl\AccessControl::createSessionType
     * @expectedException \Phlopsi\AccessControl\Exception\RuntimeException
     */
    public function testCreateSessionTypeException()
    {
        // Act
        $this->access_control_faulty->createSessionType('TEST_SESSION_TYPE');
    }

    /**
     * @covers \Phlopsi\AccessControl\AccessControl::createSessionType
     */
    public function testCreateSessionType()
    {
        // Arrange
        $access_control = new AccessControl();

        // Act
        $session_type = $access_control->createSessionType('TEST_SESSION_TYPE');

        // Assert
        $this->assertInstanceOf(\Phlopsi\AccessControl\SessionType::class, $session_type);
        $this->assertEquals(1, $this->getConnection()->getRowCount(Propel\Map\SessionTypeTableMap::TABLE_NAME));
    }

    /**
     * @covers \Phlopsi\AccessControl\AccessControl::deleteSessionType
     * @expectedException \Phlopsi\AccessControl\Exception\LengthException
     */
    public function testDeleteSessionTypeWithEmptyId()
    {
        // Arrange
        $access_control = new AccessControl();

        // Act
        $access_control->deleteSessionType('');
    }

    /**
     * @covers \Phlopsi\AccessControl\AccessControl::deleteSessionType
     */
    public function testDeleteSessionTypeWithInvalidId()
    {
        // Arrange
        $access_control = new AccessControl();

        // Act
        $result = $access_control->deleteSessionType('TEST_SESSION_TYPE');

        // Assert
        $this->assertFalse($result);
    }

    /**
     * @covers \Phlopsi\AccessControl\AccessControl::deleteSessionType
     * @expectedException \Phlopsi\AccessControl\Exception\RuntimeException
     */
    public function testDeleteSessionTypeException()
    {
        // Act
        $this->access_control_faulty->deleteSessionType('TEST_SESSION_TYPE');
    }

    /**
     * @depends testCreateSessionType
     * @covers \Phlopsi\AccessControl\AccessControl::deleteSessionType
     * @uses \Phlopsi\AccessControl\AccessControl::createSessionType
     */
    public function testDeleteSessionType()
    {
        // Arrange
        $access_control = new AccessControl();
        $access_control->createSessionType('TEST_SESSION_TYPE');

        // Act
        $result = $access_control->deleteSessionType('TEST_SESSION_TYPE');

        // Assert
        $this->assertTrue($result);
        $this->assertEquals(0, $this->getConnection()->getRowCount(Propel\Map\SessionTypeTableMap::TABLE_NAME));
    }

    /**
     * @covers \Phlopsi\AccessControl\AccessControl::retrieveSessionType
     * @expectedException \Phlopsi\AccessControl\Exception\LengthException
     */
    public function testRetrieveSessionTypeWithEmptyId()
    {
        // Arrange
        $access_control = new AccessControl();

        // Act
        $access_control->retrieveSessionType('');
    }

    /**
     * @covers \Phlopsi\AccessControl\AccessControl::retrieveSessionType
     * @expectedException \Phlopsi\AccessControl\Exception\RuntimeException
     */
    public function testRetrieveSessionTypeWithInvalidId()
    {
        // Arrange
        $access_control = new AccessControl();

        // Act
        $access_control->retrieveSessionType('TEST_SESSION_TYPE');
    }

    /**
     * @depends testCreateSessionType
     * @covers \Phlopsi\AccessControl\AccessControl::retrieveSessionType
     * @uses \Phlopsi\AccessControl\AccessControl::createSessionType
     * @expectedException \Phlopsi\AccessControl\Exception\RuntimeException
     */
    public function testRetrieveSessionTypeException()
    {
        // Arrange
        $access_control = new AccessControl();

        try {
            $access_control->createSessionType('TEST_SESSION_TYPE');
        } catch (\Exception $exception) {
            $this->fail($exception->getTraceAsString());
        }

        // Act
        $this->access_control_faulty->retrieveSessionType('TEST_SESSION_TYPE');
    }

    /**
     * @depends testCreateSessionType
     * @covers \Phlopsi\AccessControl\AccessControl::retrieveSessionType
     * @uses \Phlopsi\AccessControl\AccessControl::createSessionType
     */
    public function testRetrieveSessionType()
    {
        // Arrange
        $access_control = new AccessControl();
        $access_control->createSessionType('TEST_SESSION_TYPE');

        // Act
        $sesssion_type = $access_control->retrieveSessionType('TEST_SESSION_TYPE');

        // Assert
        $this->assertInstanceOf(\Phlopsi\AccessControl\SessionType::class, $sesssion_type);
        $this->assertEquals('TEST_SESSION_TYPE', $sesssion_type->getId());
    }

    /**
     * @covers \Phlopsi\AccessControl\AccessControl::createUser
     * @expectedException \Phlopsi\AccessControl\Exception\LengthException
     */
    public function testCreateUserWithEmptyId()
    {
        // Arrange
        $access_control = new AccessControl();

        // Act
        $access_control->createUser('');
    }

    /**
     * @covers \Phlopsi\AccessControl\AccessControl::createUser
     * @expectedException \Phlopsi\AccessControl\Exception\RuntimeException
     */
    public function testCreateUserException()
    {
        // Act
        $this->access_control_faulty->createUser('TEST_USER');
    }

    /**
     * @covers \Phlopsi\AccessControl\AccessControl::createUser
     */
    public function testCreateUser()
    {
        // Arrange
        $access_control = new AccessControl();

        // Act
        $user = $access_control->createUser('TEST_USER');

        // Assert
        $this->assertInstanceOf(\Phlopsi\AccessControl\User::class, $user);
        $this->assertEquals(1, $this->getConnection()->getRowCount(Propel\Map\UserTableMap::TABLE_NAME));
    }

    /**
     * @covers \Phlopsi\AccessControl\AccessControl::deleteUser
     * @expectedException \Phlopsi\AccessControl\Exception\LengthException
     */
    public function testDeleteUserWithEmptyId()
    {
        // Arrange
        $access_control = new AccessControl();

        // Act
        $access_control->deleteUser('');
    }

    /**
     * @covers \Phlopsi\AccessControl\AccessControl::deleteUser
     */
    public function testDeleteUserWithInvalidId()
    {
        // Arrange
        $access_control = new AccessControl();

        // Act
        $result = $access_control->deleteUser('TEST_USER');

        // Assert
        $this->assertFalse($result);
    }

    /**
     * @covers \Phlopsi\AccessControl\AccessControl::deleteUser
     * @expectedException \Phlopsi\AccessControl\Exception\RuntimeException
     */
    public function testDeleteUserException()
    {
        // Act
        $this->access_control_faulty->deleteUser('TEST_USER');
    }

    /**
     * @depends testCreateUser
     * @covers \Phlopsi\AccessControl\AccessControl::deleteUser
     * @uses \Phlopsi\AccessControl\AccessControl::createUser
     */
    public function testDeleteUser()
    {
        // Arrange
        $access_control = new AccessControl();
        $access_control->createUser('TEST_USER');

        // Act
        $result = $access_control->deleteUser('TEST_USER');

        // Assert
        $this->assertTrue($result);
        $this->assertEquals(0, $this->getConnection()->getRowCount(Propel\Map\UserTableMap::TABLE_NAME));
    }

    /**
     * @covers \Phlopsi\AccessControl\AccessControl::retrieveUser
     * @expectedException \Phlopsi\AccessControl\Exception\LengthException
     */
    public function testRetrieveUserWithEmptyId()
    {
        // Arrange
        $access_control = new AccessControl();

        // Act
        $access_control->retrieveUser('');
    }

    /**
     * @covers \Phlopsi\AccessControl\AccessControl::retrieveUser
     * @expectedException \Phlopsi\AccessControl\Exception\RuntimeException
     */
    public function testRetrieveUserWithInvalidId()
    {
        // Arrange
        $access_control = new AccessControl();

        // Act
        $access_control->retrieveUser('TEST_USER');
    }

    /**
     * @depends testCreateUser
     * @covers \Phlopsi\AccessControl\AccessControl::retrieveUser
     * @uses \Phlopsi\AccessControl\AccessControl::createUser
     * @expectedException \Phlopsi\AccessControl\Exception\RuntimeException
     */
    public function testRetrieveUserException()
    {
        // Arrange
        $access_control = new AccessControl();

        try {
            $access_control->createUser('TEST_USER');
        } catch (\Exception $exception) {
            $this->fail($exception->getTraceAsString());
        }

        // Act
        $this->access_control_faulty->retrieveUser('TEST_USER');
    }

    /**
     * @depends testCreateUser
     * @covers \Phlopsi\AccessControl\AccessControl::retrieveUser
     * @uses \Phlopsi\AccessControl\AccessControl::createUser
     */
    public function testRetrieveUser()
    {
        // Arrange
        $access_control = new AccessControl();
        $access_control->createUser('TEST_USER');

        // Act
        $user = $access_control->retrieveUser('TEST_USER');

        // Assert
        $this->assertInstanceOf(\Phlopsi\AccessControl\User::class, $user);
        $this->assertEquals('TEST_USER', $user->getId());
    }
}
