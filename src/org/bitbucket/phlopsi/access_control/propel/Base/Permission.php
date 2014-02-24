<?php

namespace org\bitbucket\phlopsi\access_control\propel\Base;

use \Exception;
use \PDO;
use Propel\Runtime\Propel;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Runtime\ActiveRecord\ActiveRecordInterface;
use Propel\Runtime\ActiveRecord\NestedSetRecursiveIterator;
use Propel\Runtime\Collection\Collection;
use Propel\Runtime\Collection\ObjectCollection;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Exception\BadMethodCallException;
use Propel\Runtime\Exception\PropelException;
use Propel\Runtime\Map\TableMap;
use Propel\Runtime\Parser\AbstractParser;
use org\bitbucket\phlopsi\access_control\propel\Permission as ChildPermission;
use org\bitbucket\phlopsi\access_control\propel\PermissionQuery as ChildPermissionQuery;
use org\bitbucket\phlopsi\access_control\propel\ProhibitionsRoles as ChildProhibitionsRoles;
use org\bitbucket\phlopsi\access_control\propel\ProhibitionsRolesQuery as ChildProhibitionsRolesQuery;
use org\bitbucket\phlopsi\access_control\propel\ProhibitionsUsers as ChildProhibitionsUsers;
use org\bitbucket\phlopsi\access_control\propel\ProhibitionsUsersQuery as ChildProhibitionsUsersQuery;
use org\bitbucket\phlopsi\access_control\propel\Map\PermissionTableMap;

abstract class Permission implements ActiveRecordInterface
{
    /**
     * TableMap class name
     */
    const TABLE_MAP = '\\org\\bitbucket\\phlopsi\\access_control\\propel\\Map\\PermissionTableMap';


    /**
     * attribute to determine if this object has previously been saved.
     * @var boolean
     */
    protected $new = true;

    /**
     * attribute to determine whether this object has been deleted.
     * @var boolean
     */
    protected $deleted = false;

    /**
     * The columns that have been modified in current object.
     * Tracking modified columns allows us to only update modified columns.
     * @var array
     */
    protected $modifiedColumns = array();

    /**
     * The (virtual) columns that are added at runtime
     * The formatters can add supplementary columns based on a resultset
     * @var array
     */
    protected $virtualColumns = array();

    /**
     * The value for the external_id field.
     * @var        string
     */
    protected $external_id;

    /**
     * The value for the tree_left field.
     * @var        int
     */
    protected $tree_left;

    /**
     * The value for the tree_right field.
     * @var        int
     */
    protected $tree_right;

    /**
     * The value for the tree_level field.
     * @var        int
     */
    protected $tree_level;

    /**
     * The value for the id field.
     * @var        int
     */
    protected $id;

    /**
     * @var        ObjectCollection|ChildProhibitionsRoles[] Collection to store aggregation of ChildProhibitionsRoles objects.
     */
    protected $collProhibitionsRoless;
    protected $collProhibitionsRolessPartial;

    /**
     * @var        ObjectCollection|ChildProhibitionsUsers[] Collection to store aggregation of ChildProhibitionsUsers objects.
     */
    protected $collProhibitionsUserss;
    protected $collProhibitionsUserssPartial;

    /**
     * Flag to prevent endless save loop, if this object is referenced
     * by another object which falls in this transaction.
     *
     * @var boolean
     */
    protected $alreadyInSave = false;

    // nested_set behavior

    /**
     * Queries to be executed in the save transaction
     * @var        array
     */
    protected $nestedSetQueries = array();

    /**
     * Internal cache for children nodes
     * @var        null|ObjectCollection
     */
    protected $collNestedSetChildren = null;

    /**
     * Internal cache for parent node
     * @var        null|ChildPermission
     */
    protected $aNestedSetParent = null;

    /**
     * Left column for the set
     */
    const LEFT_COL = 'prohibitions.tree_left';

    /**
     * Right column for the set
     */
    const RIGHT_COL = 'prohibitions.tree_right';

    /**
     * Level column for the set
     */
    const LEVEL_COL = 'prohibitions.tree_level';

    /**
     * An array of objects scheduled for deletion.
     * @var ObjectCollection
     */
    protected $prohibitionsRolessScheduledForDeletion = null;

    /**
     * An array of objects scheduled for deletion.
     * @var ObjectCollection
     */
    protected $prohibitionsUserssScheduledForDeletion = null;

    /**
     * Initializes internal state of org\bitbucket\phlopsi\access_control\propel\Base\Permission object.
     */
    public function __construct()
    {
    }

    /**
     * Returns whether the object has been modified.
     *
     * @return boolean True if the object has been modified.
     */
    public function isModified()
    {
        return !empty($this->modifiedColumns);
    }

    /**
     * Has specified column been modified?
     *
     * @param  string  $col column fully qualified name (TableMap::TYPE_COLNAME), e.g. Book::AUTHOR_ID
     * @return boolean True if $col has been modified.
     */
    public function isColumnModified($col)
    {
        return in_array($col, $this->modifiedColumns);
    }

    /**
     * Get the columns that have been modified in this object.
     * @return array A unique list of the modified column names for this object.
     */
    public function getModifiedColumns()
    {
        return array_unique($this->modifiedColumns);
    }

    /**
     * Returns whether the object has ever been saved.  This will
     * be false, if the object was retrieved from storage or was created
     * and then saved.
     *
     * @return boolean true, if the object has never been persisted.
     */
    public function isNew()
    {
        return $this->new;
    }

    /**
     * Setter for the isNew attribute.  This method will be called
     * by Propel-generated children and objects.
     *
     * @param boolean $b the state of the object.
     */
    public function setNew($b)
    {
        $this->new = (Boolean) $b;
    }

    /**
     * Whether this object has been deleted.
     * @return boolean The deleted state of this object.
     */
    public function isDeleted()
    {
        return $this->deleted;
    }

    /**
     * Specify whether this object has been deleted.
     * @param  boolean $b The deleted state of this object.
     * @return void
     */
    public function setDeleted($b)
    {
        $this->deleted = (Boolean) $b;
    }

    /**
     * Sets the modified state for the object to be false.
     * @param  string $col If supplied, only the specified column is reset.
     * @return void
     */
    public function resetModified($col = null)
    {
        if (null !== $col) {
            while (false !== ($offset = array_search($col, $this->modifiedColumns))) {
                array_splice($this->modifiedColumns, $offset, 1);
            }
        } else {
            $this->modifiedColumns = array();
        }
    }

    /**
     * Compares this with another <code>Permission</code> instance.  If
     * <code>obj</code> is an instance of <code>Permission</code>, delegates to
     * <code>equals(Permission)</code>.  Otherwise, returns <code>false</code>.
     *
     * @param  mixed   $obj The object to compare to.
     * @return boolean Whether equal to the object specified.
     */
    public function equals($obj)
    {
        $thisclazz = get_class($this);
        if (!is_object($obj) || !($obj instanceof $thisclazz)) {
            return false;
        }

        if ($this === $obj) {
            return true;
        }

        if (null === $this->getPrimaryKey()
            || null === $obj->getPrimaryKey())  {
            return false;
        }

        return $this->getPrimaryKey() === $obj->getPrimaryKey();
    }

    /**
     * If the primary key is not null, return the hashcode of the
     * primary key. Otherwise, return the hash code of the object.
     *
     * @return int Hashcode
     */
    public function hashCode()
    {
        if (null !== $this->getPrimaryKey()) {
            return crc32(serialize($this->getPrimaryKey()));
        }

        return crc32(serialize(clone $this));
    }

    /**
     * Get the associative array of the virtual columns in this object
     *
     * @return array
     */
    public function getVirtualColumns()
    {
        return $this->virtualColumns;
    }

    /**
     * Checks the existence of a virtual column in this object
     *
     * @param  string  $name The virtual column name
     * @return boolean
     */
    public function hasVirtualColumn($name)
    {
        return array_key_exists($name, $this->virtualColumns);
    }

    /**
     * Get the value of a virtual column in this object
     *
     * @param  string $name The virtual column name
     * @return mixed
     *
     * @throws PropelException
     */
    public function getVirtualColumn($name)
    {
        if (!$this->hasVirtualColumn($name)) {
            throw new PropelException(sprintf('Cannot get value of inexistent virtual column %s.', $name));
        }

        return $this->virtualColumns[$name];
    }

    /**
     * Set the value of a virtual column in this object
     *
     * @param string $name  The virtual column name
     * @param mixed  $value The value to give to the virtual column
     *
     * @return Permission The current object, for fluid interface
     */
    public function setVirtualColumn($name, $value)
    {
        $this->virtualColumns[$name] = $value;

        return $this;
    }

    /**
     * Logs a message using Propel::log().
     *
     * @param  string  $msg
     * @param  int     $priority One of the Propel::LOG_* logging levels
     * @return boolean
     */
    protected function log($msg, $priority = Propel::LOG_INFO)
    {
        return Propel::log(get_class($this) . ': ' . $msg, $priority);
    }

    /**
     * Populate the current object from a string, using a given parser format
     * <code>
     * $book = new Book();
     * $book->importFrom('JSON', '{"Id":9012,"Title":"Don Juan","ISBN":"0140422161","Price":12.99,"PublisherId":1234,"AuthorId":5678}');
     * </code>
     *
     * @param mixed $parser A AbstractParser instance,
     *                       or a format name ('XML', 'YAML', 'JSON', 'CSV')
     * @param string $data The source data to import from
     *
     * @return Permission The current object, for fluid interface
     */
    public function importFrom($parser, $data)
    {
        if (!$parser instanceof AbstractParser) {
            $parser = AbstractParser::getParser($parser);
        }

        $this->fromArray($parser->toArray($data), TableMap::TYPE_PHPNAME);

        return $this;
    }

    /**
     * Export the current object properties to a string, using a given parser format
     * <code>
     * $book = BookQuery::create()->findPk(9012);
     * echo $book->exportTo('JSON');
     *  => {"Id":9012,"Title":"Don Juan","ISBN":"0140422161","Price":12.99,"PublisherId":1234,"AuthorId":5678}');
     * </code>
     *
     * @param  mixed   $parser                 A AbstractParser instance, or a format name ('XML', 'YAML', 'JSON', 'CSV')
     * @param  boolean $includeLazyLoadColumns (optional) Whether to include lazy load(ed) columns. Defaults to TRUE.
     * @return string  The exported data
     */
    public function exportTo($parser, $includeLazyLoadColumns = true)
    {
        if (!$parser instanceof AbstractParser) {
            $parser = AbstractParser::getParser($parser);
        }

        return $parser->fromArray($this->toArray(TableMap::TYPE_PHPNAME, $includeLazyLoadColumns, array(), true));
    }

    /**
     * Clean up internal collections prior to serializing
     * Avoids recursive loops that turn into segmentation faults when serializing
     */
    public function __sleep()
    {
        $this->clearAllReferences();

        return array_keys(get_object_vars($this));
    }

    /**
     * Get the [external_id] column value.
     *
     * @return   string
     */
    public function getExternalId()
    {

        return $this->external_id;
    }

    /**
     * Get the [tree_left] column value.
     *
     * @return   int
     */
    public function getTreeLeft()
    {

        return $this->tree_left;
    }

    /**
     * Get the [tree_right] column value.
     *
     * @return   int
     */
    public function getTreeRight()
    {

        return $this->tree_right;
    }

    /**
     * Get the [tree_level] column value.
     *
     * @return   int
     */
    public function getTreeLevel()
    {

        return $this->tree_level;
    }

    /**
     * Get the [id] column value.
     *
     * @return   int
     */
    public function getId()
    {

        return $this->id;
    }

    /**
     * Set the value of [external_id] column.
     *
     * @param      string $v new value
     * @return   \org\bitbucket\phlopsi\access_control\propel\Permission The current object (for fluent API support)
     */
    public function setExternalId($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->external_id !== $v) {
            $this->external_id = $v;
            $this->modifiedColumns[] = PermissionTableMap::EXTERNAL_ID;
        }


        return $this;
    } // setExternalId()

    /**
     * Set the value of [tree_left] column.
     *
     * @param      int $v new value
     * @return   \org\bitbucket\phlopsi\access_control\propel\Permission The current object (for fluent API support)
     */
    public function setTreeLeft($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->tree_left !== $v) {
            $this->tree_left = $v;
            $this->modifiedColumns[] = PermissionTableMap::TREE_LEFT;
        }


        return $this;
    } // setTreeLeft()

    /**
     * Set the value of [tree_right] column.
     *
     * @param      int $v new value
     * @return   \org\bitbucket\phlopsi\access_control\propel\Permission The current object (for fluent API support)
     */
    public function setTreeRight($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->tree_right !== $v) {
            $this->tree_right = $v;
            $this->modifiedColumns[] = PermissionTableMap::TREE_RIGHT;
        }


        return $this;
    } // setTreeRight()

    /**
     * Set the value of [tree_level] column.
     *
     * @param      int $v new value
     * @return   \org\bitbucket\phlopsi\access_control\propel\Permission The current object (for fluent API support)
     */
    public function setTreeLevel($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->tree_level !== $v) {
            $this->tree_level = $v;
            $this->modifiedColumns[] = PermissionTableMap::TREE_LEVEL;
        }


        return $this;
    } // setTreeLevel()

    /**
     * Set the value of [id] column.
     *
     * @param      int $v new value
     * @return   \org\bitbucket\phlopsi\access_control\propel\Permission The current object (for fluent API support)
     */
    public function setId($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->id !== $v) {
            $this->id = $v;
            $this->modifiedColumns[] = PermissionTableMap::ID;
        }


        return $this;
    } // setId()

    /**
     * Indicates whether the columns in this object are only set to default values.
     *
     * This method can be used in conjunction with isModified() to indicate whether an object is both
     * modified _and_ has some values set which are non-default.
     *
     * @return boolean Whether the columns in this object are only been set with default values.
     */
    public function hasOnlyDefaultValues()
    {
        // otherwise, everything was equal, so return TRUE
        return true;
    } // hasOnlyDefaultValues()

    /**
     * Hydrates (populates) the object variables with values from the database resultset.
     *
     * An offset (0-based "start column") is specified so that objects can be hydrated
     * with a subset of the columns in the resultset rows.  This is needed, for example,
     * for results of JOIN queries where the resultset row includes columns from two or
     * more tables.
     *
     * @param array   $row       The row returned by DataFetcher->fetch().
     * @param int     $startcol  0-based offset column which indicates which restultset column to start with.
     * @param boolean $rehydrate Whether this object is being re-hydrated from the database.
     * @param string  $indexType The index type of $row. Mostly DataFetcher->getIndexType().
                                  One of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_STUDLYPHPNAME
     *                            TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM.
     *
     * @return int             next starting column
     * @throws PropelException - Any caught Exception will be rewrapped as a PropelException.
     */
    public function hydrate($row, $startcol = 0, $rehydrate = false, $indexType = TableMap::TYPE_NUM)
    {
        try {


            $col = $row[TableMap::TYPE_NUM == $indexType ? 0 + $startcol : PermissionTableMap::translateFieldName('ExternalId', TableMap::TYPE_PHPNAME, $indexType)];
            $this->external_id = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 1 + $startcol : PermissionTableMap::translateFieldName('TreeLeft', TableMap::TYPE_PHPNAME, $indexType)];
            $this->tree_left = (null !== $col) ? (int) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 2 + $startcol : PermissionTableMap::translateFieldName('TreeRight', TableMap::TYPE_PHPNAME, $indexType)];
            $this->tree_right = (null !== $col) ? (int) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 3 + $startcol : PermissionTableMap::translateFieldName('TreeLevel', TableMap::TYPE_PHPNAME, $indexType)];
            $this->tree_level = (null !== $col) ? (int) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 4 + $startcol : PermissionTableMap::translateFieldName('Id', TableMap::TYPE_PHPNAME, $indexType)];
            $this->id = (null !== $col) ? (int) $col : null;
            $this->resetModified();

            $this->setNew(false);

            if ($rehydrate) {
                $this->ensureConsistency();
            }

            return $startcol + 5; // 5 = PermissionTableMap::NUM_HYDRATE_COLUMNS.

        } catch (Exception $e) {
            throw new PropelException("Error populating \org\bitbucket\phlopsi\access_control\propel\Permission object", 0, $e);
        }
    }

    /**
     * Checks and repairs the internal consistency of the object.
     *
     * This method is executed after an already-instantiated object is re-hydrated
     * from the database.  It exists to check any foreign keys to make sure that
     * the objects related to the current object are correct based on foreign key.
     *
     * You can override this method in the stub class, but you should always invoke
     * the base method from the overridden method (i.e. parent::ensureConsistency()),
     * in case your model changes.
     *
     * @throws PropelException
     */
    public function ensureConsistency()
    {
    } // ensureConsistency

    /**
     * Reloads this object from datastore based on primary key and (optionally) resets all associated objects.
     *
     * This will only work if the object has been saved and has a valid primary key set.
     *
     * @param      boolean $deep (optional) Whether to also de-associated any related objects.
     * @param      ConnectionInterface $con (optional) The ConnectionInterface connection to use.
     * @return void
     * @throws PropelException - if this object is deleted, unsaved or doesn't have pk match in db
     */
    public function reload($deep = false, ConnectionInterface $con = null)
    {
        if ($this->isDeleted()) {
            throw new PropelException("Cannot reload a deleted object.");
        }

        if ($this->isNew()) {
            throw new PropelException("Cannot reload an unsaved object.");
        }

        if ($con === null) {
            $con = Propel::getServiceContainer()->getReadConnection(PermissionTableMap::DATABASE_NAME);
        }

        // We don't need to alter the object instance pool; we're just modifying this instance
        // already in the pool.

        $dataFetcher = ChildPermissionQuery::create(null, $this->buildPkeyCriteria())->setFormatter(ModelCriteria::FORMAT_STATEMENT)->find($con);
        $row = $dataFetcher->fetch();
        $dataFetcher->close();
        if (!$row) {
            throw new PropelException('Cannot find matching row in the database to reload object values.');
        }
        $this->hydrate($row, 0, true, $dataFetcher->getIndexType()); // rehydrate

        if ($deep) {  // also de-associate any related objects?

            $this->collProhibitionsRoless = null;

            $this->collProhibitionsUserss = null;

        } // if (deep)
    }

    /**
     * Removes this object from datastore and sets delete attribute.
     *
     * @param      ConnectionInterface $con
     * @return void
     * @throws PropelException
     * @see Permission::setDeleted()
     * @see Permission::isDeleted()
     */
    public function delete(ConnectionInterface $con = null)
    {
        if ($this->isDeleted()) {
            throw new PropelException("This object has already been deleted.");
        }

        if ($con === null) {
            $con = Propel::getServiceContainer()->getWriteConnection(PermissionTableMap::DATABASE_NAME);
        }

        $con->beginTransaction();
        try {
            $deleteQuery = ChildPermissionQuery::create()
                ->filterByPrimaryKey($this->getPrimaryKey());
            $ret = $this->preDelete($con);
            // nested_set behavior
            if ($this->isRoot()) {
                throw new PropelException('Deletion of a root node is disabled for nested sets. Use ChildPermissionQuery::deleteTree() instead to delete an entire tree');
            }

            if ($this->isInTree()) {
                $this->deleteDescendants($con);
            }

            if ($ret) {
                $deleteQuery->delete($con);
                $this->postDelete($con);
                // nested_set behavior
                if ($this->isInTree()) {
                    // fill up the room that was used by the node
                    ChildPermissionQuery::shiftRLValues(-2, $this->getRightValue() + 1, null, $con);
                }

                $con->commit();
                $this->setDeleted(true);
            } else {
                $con->commit();
            }
        } catch (Exception $e) {
            $con->rollBack();
            throw $e;
        }
    }

    /**
     * Persists this object to the database.
     *
     * If the object is new, it inserts it; otherwise an update is performed.
     * All modified related objects will also be persisted in the doSave()
     * method.  This method wraps all precipitate database operations in a
     * single transaction.
     *
     * @param      ConnectionInterface $con
     * @return int             The number of rows affected by this insert/update and any referring fk objects' save() operations.
     * @throws PropelException
     * @see doSave()
     */
    public function save(ConnectionInterface $con = null)
    {
        if ($this->isDeleted()) {
            throw new PropelException("You cannot save an object that has been deleted.");
        }

        if ($con === null) {
            $con = Propel::getServiceContainer()->getWriteConnection(PermissionTableMap::DATABASE_NAME);
        }

        $con->beginTransaction();
        $isInsert = $this->isNew();
        try {
            $ret = $this->preSave($con);
            // nested_set behavior
            if ($this->isNew() && $this->isRoot()) {
                // check if no other root exist in, the tree
                $nbRoots = ChildPermissionQuery::create()
                    ->addUsingAlias(ChildPermission::LEFT_COL, 1, Criteria::EQUAL)
                    ->count($con);
                if ($nbRoots > 0) {
                        throw new PropelException('A root node already exists in this tree. To allow multiple root nodes, add the `use_scope` parameter in the nested_set behavior tag.');
                }
            }
            $this->processNestedSetQueries($con);
            if ($isInsert) {
                $ret = $ret && $this->preInsert($con);
            } else {
                $ret = $ret && $this->preUpdate($con);
            }
            if ($ret) {
                $affectedRows = $this->doSave($con);
                if ($isInsert) {
                    $this->postInsert($con);
                } else {
                    $this->postUpdate($con);
                }
                $this->postSave($con);
                PermissionTableMap::addInstanceToPool($this);
            } else {
                $affectedRows = 0;
            }
            $con->commit();

            return $affectedRows;
        } catch (Exception $e) {
            $con->rollBack();
            throw $e;
        }
    }

    /**
     * Performs the work of inserting or updating the row in the database.
     *
     * If the object is new, it inserts it; otherwise an update is performed.
     * All related objects are also updated in this method.
     *
     * @param      ConnectionInterface $con
     * @return int             The number of rows affected by this insert/update and any referring fk objects' save() operations.
     * @throws PropelException
     * @see save()
     */
    protected function doSave(ConnectionInterface $con)
    {
        $affectedRows = 0; // initialize var to track total num of affected rows
        if (!$this->alreadyInSave) {
            $this->alreadyInSave = true;

            if ($this->isNew() || $this->isModified()) {
                // persist changes
                if ($this->isNew()) {
                    $this->doInsert($con);
                } else {
                    $this->doUpdate($con);
                }
                $affectedRows += 1;
                $this->resetModified();
            }

            if ($this->prohibitionsRolessScheduledForDeletion !== null) {
                if (!$this->prohibitionsRolessScheduledForDeletion->isEmpty()) {
                    \org\bitbucket\phlopsi\access_control\propel\ProhibitionsRolesQuery::create()
                        ->filterByPrimaryKeys($this->prohibitionsRolessScheduledForDeletion->getPrimaryKeys(false))
                        ->delete($con);
                    $this->prohibitionsRolessScheduledForDeletion = null;
                }
            }

                if ($this->collProhibitionsRoless !== null) {
            foreach ($this->collProhibitionsRoless as $referrerFK) {
                    if (!$referrerFK->isDeleted() && ($referrerFK->isNew() || $referrerFK->isModified())) {
                        $affectedRows += $referrerFK->save($con);
                    }
                }
            }

            if ($this->prohibitionsUserssScheduledForDeletion !== null) {
                if (!$this->prohibitionsUserssScheduledForDeletion->isEmpty()) {
                    \org\bitbucket\phlopsi\access_control\propel\ProhibitionsUsersQuery::create()
                        ->filterByPrimaryKeys($this->prohibitionsUserssScheduledForDeletion->getPrimaryKeys(false))
                        ->delete($con);
                    $this->prohibitionsUserssScheduledForDeletion = null;
                }
            }

                if ($this->collProhibitionsUserss !== null) {
            foreach ($this->collProhibitionsUserss as $referrerFK) {
                    if (!$referrerFK->isDeleted() && ($referrerFK->isNew() || $referrerFK->isModified())) {
                        $affectedRows += $referrerFK->save($con);
                    }
                }
            }

            $this->alreadyInSave = false;

        }

        return $affectedRows;
    } // doSave()

    /**
     * Insert the row in the database.
     *
     * @param      ConnectionInterface $con
     *
     * @throws PropelException
     * @see doSave()
     */
    protected function doInsert(ConnectionInterface $con)
    {
        $modifiedColumns = array();
        $index = 0;

        $this->modifiedColumns[] = PermissionTableMap::ID;
        if (null !== $this->id) {
            throw new PropelException('Cannot insert a value for auto-increment primary key (' . PermissionTableMap::ID . ')');
        }

         // check the columns in natural order for more readable SQL queries
        if ($this->isColumnModified(PermissionTableMap::EXTERNAL_ID)) {
            $modifiedColumns[':p' . $index++]  = 'EXTERNAL_ID';
        }
        if ($this->isColumnModified(PermissionTableMap::TREE_LEFT)) {
            $modifiedColumns[':p' . $index++]  = 'TREE_LEFT';
        }
        if ($this->isColumnModified(PermissionTableMap::TREE_RIGHT)) {
            $modifiedColumns[':p' . $index++]  = 'TREE_RIGHT';
        }
        if ($this->isColumnModified(PermissionTableMap::TREE_LEVEL)) {
            $modifiedColumns[':p' . $index++]  = 'TREE_LEVEL';
        }
        if ($this->isColumnModified(PermissionTableMap::ID)) {
            $modifiedColumns[':p' . $index++]  = 'ID';
        }

        $sql = sprintf(
            'INSERT INTO prohibitions (%s) VALUES (%s)',
            implode(', ', $modifiedColumns),
            implode(', ', array_keys($modifiedColumns))
        );

        try {
            $stmt = $con->prepare($sql);
            foreach ($modifiedColumns as $identifier => $columnName) {
                switch ($columnName) {
                    case 'EXTERNAL_ID':
                        $stmt->bindValue($identifier, $this->external_id, PDO::PARAM_STR);
                        break;
                    case 'TREE_LEFT':
                        $stmt->bindValue($identifier, $this->tree_left, PDO::PARAM_INT);
                        break;
                    case 'TREE_RIGHT':
                        $stmt->bindValue($identifier, $this->tree_right, PDO::PARAM_INT);
                        break;
                    case 'TREE_LEVEL':
                        $stmt->bindValue($identifier, $this->tree_level, PDO::PARAM_INT);
                        break;
                    case 'ID':
                        $stmt->bindValue($identifier, $this->id, PDO::PARAM_INT);
                        break;
                }
            }
            $stmt->execute();
        } catch (Exception $e) {
            Propel::log($e->getMessage(), Propel::LOG_ERR);
            throw new PropelException(sprintf('Unable to execute INSERT statement [%s]', $sql), 0, $e);
        }

        try {
            $pk = $con->lastInsertId();
        } catch (Exception $e) {
            throw new PropelException('Unable to get autoincrement id.', 0, $e);
        }
        $this->setId($pk);

        $this->setNew(false);
    }

    /**
     * Update the row in the database.
     *
     * @param      ConnectionInterface $con
     *
     * @return Integer Number of updated rows
     * @see doSave()
     */
    protected function doUpdate(ConnectionInterface $con)
    {
        $selectCriteria = $this->buildPkeyCriteria();
        $valuesCriteria = $this->buildCriteria();

        return $selectCriteria->doUpdate($valuesCriteria, $con);
    }

    /**
     * Retrieves a field from the object by name passed in as a string.
     *
     * @param      string $name name
     * @param      string $type The type of fieldname the $name is of:
     *                     one of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_STUDLYPHPNAME
     *                     TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM.
     *                     Defaults to TableMap::TYPE_PHPNAME.
     * @return mixed Value of field.
     */
    public function getByName($name, $type = TableMap::TYPE_PHPNAME)
    {
        $pos = PermissionTableMap::translateFieldName($name, $type, TableMap::TYPE_NUM);
        $field = $this->getByPosition($pos);

        return $field;
    }

    /**
     * Retrieves a field from the object by Position as specified in the xml schema.
     * Zero-based.
     *
     * @param      int $pos position in xml schema
     * @return mixed Value of field at $pos
     */
    public function getByPosition($pos)
    {
        switch ($pos) {
            case 0:
                return $this->getExternalId();
                break;
            case 1:
                return $this->getTreeLeft();
                break;
            case 2:
                return $this->getTreeRight();
                break;
            case 3:
                return $this->getTreeLevel();
                break;
            case 4:
                return $this->getId();
                break;
            default:
                return null;
                break;
        } // switch()
    }

    /**
     * Exports the object as an array.
     *
     * You can specify the key type of the array by passing one of the class
     * type constants.
     *
     * @param     string  $keyType (optional) One of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_STUDLYPHPNAME,
     *                    TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM.
     *                    Defaults to TableMap::TYPE_PHPNAME.
     * @param     boolean $includeLazyLoadColumns (optional) Whether to include lazy loaded columns. Defaults to TRUE.
     * @param     array $alreadyDumpedObjects List of objects to skip to avoid recursion
     * @param     boolean $includeForeignObjects (optional) Whether to include hydrated related objects. Default to FALSE.
     *
     * @return array an associative array containing the field names (as keys) and field values
     */
    public function toArray($keyType = TableMap::TYPE_PHPNAME, $includeLazyLoadColumns = true, $alreadyDumpedObjects = array(), $includeForeignObjects = false)
    {
        if (isset($alreadyDumpedObjects['Permission'][$this->getPrimaryKey()])) {
            return '*RECURSION*';
        }
        $alreadyDumpedObjects['Permission'][$this->getPrimaryKey()] = true;
        $keys = PermissionTableMap::getFieldNames($keyType);
        $result = array(
            $keys[0] => $this->getExternalId(),
            $keys[1] => $this->getTreeLeft(),
            $keys[2] => $this->getTreeRight(),
            $keys[3] => $this->getTreeLevel(),
            $keys[4] => $this->getId(),
        );
        $virtualColumns = $this->virtualColumns;
        foreach ($virtualColumns as $key => $virtualColumn) {
            $result[$key] = $virtualColumn;
        }

        if ($includeForeignObjects) {
            if (null !== $this->collProhibitionsRoless) {
                $result['ProhibitionsRoless'] = $this->collProhibitionsRoless->toArray(null, true, $keyType, $includeLazyLoadColumns, $alreadyDumpedObjects);
            }
            if (null !== $this->collProhibitionsUserss) {
                $result['ProhibitionsUserss'] = $this->collProhibitionsUserss->toArray(null, true, $keyType, $includeLazyLoadColumns, $alreadyDumpedObjects);
            }
        }

        return $result;
    }

    /**
     * Sets a field from the object by name passed in as a string.
     *
     * @param      string $name
     * @param      mixed  $value field value
     * @param      string $type The type of fieldname the $name is of:
     *                     one of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_STUDLYPHPNAME
     *                     TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM.
     *                     Defaults to TableMap::TYPE_PHPNAME.
     * @return void
     */
    public function setByName($name, $value, $type = TableMap::TYPE_PHPNAME)
    {
        $pos = PermissionTableMap::translateFieldName($name, $type, TableMap::TYPE_NUM);

        return $this->setByPosition($pos, $value);
    }

    /**
     * Sets a field from the object by Position as specified in the xml schema.
     * Zero-based.
     *
     * @param      int $pos position in xml schema
     * @param      mixed $value field value
     * @return void
     */
    public function setByPosition($pos, $value)
    {
        switch ($pos) {
            case 0:
                $this->setExternalId($value);
                break;
            case 1:
                $this->setTreeLeft($value);
                break;
            case 2:
                $this->setTreeRight($value);
                break;
            case 3:
                $this->setTreeLevel($value);
                break;
            case 4:
                $this->setId($value);
                break;
        } // switch()
    }

    /**
     * Populates the object using an array.
     *
     * This is particularly useful when populating an object from one of the
     * request arrays (e.g. $_POST).  This method goes through the column
     * names, checking to see whether a matching key exists in populated
     * array. If so the setByName() method is called for that column.
     *
     * You can specify the key type of the array by additionally passing one
     * of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_STUDLYPHPNAME,
     * TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM.
     * The default key type is the column's TableMap::TYPE_PHPNAME.
     *
     * @param      array  $arr     An array to populate the object from.
     * @param      string $keyType The type of keys the array uses.
     * @return void
     */
    public function fromArray($arr, $keyType = TableMap::TYPE_PHPNAME)
    {
        $keys = PermissionTableMap::getFieldNames($keyType);

        if (array_key_exists($keys[0], $arr)) $this->setExternalId($arr[$keys[0]]);
        if (array_key_exists($keys[1], $arr)) $this->setTreeLeft($arr[$keys[1]]);
        if (array_key_exists($keys[2], $arr)) $this->setTreeRight($arr[$keys[2]]);
        if (array_key_exists($keys[3], $arr)) $this->setTreeLevel($arr[$keys[3]]);
        if (array_key_exists($keys[4], $arr)) $this->setId($arr[$keys[4]]);
    }

    /**
     * Build a Criteria object containing the values of all modified columns in this object.
     *
     * @return Criteria The Criteria object containing all modified values.
     */
    public function buildCriteria()
    {
        $criteria = new Criteria(PermissionTableMap::DATABASE_NAME);

        if ($this->isColumnModified(PermissionTableMap::EXTERNAL_ID)) $criteria->add(PermissionTableMap::EXTERNAL_ID, $this->external_id);
        if ($this->isColumnModified(PermissionTableMap::TREE_LEFT)) $criteria->add(PermissionTableMap::TREE_LEFT, $this->tree_left);
        if ($this->isColumnModified(PermissionTableMap::TREE_RIGHT)) $criteria->add(PermissionTableMap::TREE_RIGHT, $this->tree_right);
        if ($this->isColumnModified(PermissionTableMap::TREE_LEVEL)) $criteria->add(PermissionTableMap::TREE_LEVEL, $this->tree_level);
        if ($this->isColumnModified(PermissionTableMap::ID)) $criteria->add(PermissionTableMap::ID, $this->id);

        return $criteria;
    }

    /**
     * Builds a Criteria object containing the primary key for this object.
     *
     * Unlike buildCriteria() this method includes the primary key values regardless
     * of whether or not they have been modified.
     *
     * @return Criteria The Criteria object containing value(s) for primary key(s).
     */
    public function buildPkeyCriteria()
    {
        $criteria = new Criteria(PermissionTableMap::DATABASE_NAME);
        $criteria->add(PermissionTableMap::ID, $this->id);

        return $criteria;
    }

    /**
     * Returns the primary key for this object (row).
     * @return   int
     */
    public function getPrimaryKey()
    {
        return $this->getId();
    }

    /**
     * Generic method to set the primary key (id column).
     *
     * @param       int $key Primary key.
     * @return void
     */
    public function setPrimaryKey($key)
    {
        $this->setId($key);
    }

    /**
     * Returns true if the primary key for this object is null.
     * @return boolean
     */
    public function isPrimaryKeyNull()
    {

        return null === $this->getId();
    }

    /**
     * Sets contents of passed object to values from current object.
     *
     * If desired, this method can also make copies of all associated (fkey referrers)
     * objects.
     *
     * @param      object $copyObj An object of \org\bitbucket\phlopsi\access_control\propel\Permission (or compatible) type.
     * @param      boolean $deepCopy Whether to also copy all rows that refer (by fkey) to the current row.
     * @param      boolean $makeNew Whether to reset autoincrement PKs and make the object new.
     * @throws PropelException
     */
    public function copyInto($copyObj, $deepCopy = false, $makeNew = true)
    {
        $copyObj->setExternalId($this->getExternalId());
        $copyObj->setTreeLeft($this->getTreeLeft());
        $copyObj->setTreeRight($this->getTreeRight());
        $copyObj->setTreeLevel($this->getTreeLevel());

        if ($deepCopy) {
            // important: temporarily setNew(false) because this affects the behavior of
            // the getter/setter methods for fkey referrer objects.
            $copyObj->setNew(false);

            foreach ($this->getProhibitionsRoless() as $relObj) {
                if ($relObj !== $this) {  // ensure that we don't try to copy a reference to ourselves
                    $copyObj->addProhibitionsRoles($relObj->copy($deepCopy));
                }
            }

            foreach ($this->getProhibitionsUserss() as $relObj) {
                if ($relObj !== $this) {  // ensure that we don't try to copy a reference to ourselves
                    $copyObj->addProhibitionsUsers($relObj->copy($deepCopy));
                }
            }

        } // if ($deepCopy)

        if ($makeNew) {
            $copyObj->setNew(true);
            $copyObj->setId(NULL); // this is a auto-increment column, so set to default value
        }
    }

    /**
     * Makes a copy of this object that will be inserted as a new row in table when saved.
     * It creates a new object filling in the simple attributes, but skipping any primary
     * keys that are defined for the table.
     *
     * If desired, this method can also make copies of all associated (fkey referrers)
     * objects.
     *
     * @param      boolean $deepCopy Whether to also copy all rows that refer (by fkey) to the current row.
     * @return                 \org\bitbucket\phlopsi\access_control\propel\Permission Clone of current object.
     * @throws PropelException
     */
    public function copy($deepCopy = false)
    {
        // we use get_class(), because this might be a subclass
        $clazz = get_class($this);
        $copyObj = new $clazz();
        $this->copyInto($copyObj, $deepCopy);

        return $copyObj;
    }


    /**
     * Initializes a collection based on the name of a relation.
     * Avoids crafting an 'init[$relationName]s' method name
     * that wouldn't work when StandardEnglishPluralizer is used.
     *
     * @param      string $relationName The name of the relation to initialize
     * @return void
     */
    public function initRelation($relationName)
    {
        if ('ProhibitionsRoles' == $relationName) {
            return $this->initProhibitionsRoless();
        }
        if ('ProhibitionsUsers' == $relationName) {
            return $this->initProhibitionsUserss();
        }
    }

    /**
     * Clears out the collProhibitionsRoless collection
     *
     * This does not modify the database; however, it will remove any associated objects, causing
     * them to be refetched by subsequent calls to accessor method.
     *
     * @return void
     * @see        addProhibitionsRoless()
     */
    public function clearProhibitionsRoless()
    {
        $this->collProhibitionsRoless = null; // important to set this to NULL since that means it is uninitialized
    }

    /**
     * Reset is the collProhibitionsRoless collection loaded partially.
     */
    public function resetPartialProhibitionsRoless($v = true)
    {
        $this->collProhibitionsRolessPartial = $v;
    }

    /**
     * Initializes the collProhibitionsRoless collection.
     *
     * By default this just sets the collProhibitionsRoless collection to an empty array (like clearcollProhibitionsRoless());
     * however, you may wish to override this method in your stub class to provide setting appropriate
     * to your application -- for example, setting the initial array to the values stored in database.
     *
     * @param      boolean $overrideExisting If set to true, the method call initializes
     *                                        the collection even if it is not empty
     *
     * @return void
     */
    public function initProhibitionsRoless($overrideExisting = true)
    {
        if (null !== $this->collProhibitionsRoless && !$overrideExisting) {
            return;
        }
        $this->collProhibitionsRoless = new ObjectCollection();
        $this->collProhibitionsRoless->setModel('\org\bitbucket\phlopsi\access_control\propel\ProhibitionsRoles');
    }

    /**
     * Gets an array of ChildProhibitionsRoles objects which contain a foreign key that references this object.
     *
     * If the $criteria is not null, it is used to always fetch the results from the database.
     * Otherwise the results are fetched from the database the first time, then cached.
     * Next time the same method is called without $criteria, the cached collection is returned.
     * If this ChildPermission is new, it will return
     * an empty collection or the current collection; the criteria is ignored on a new object.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @return Collection|ChildProhibitionsRoles[] List of ChildProhibitionsRoles objects
     * @throws PropelException
     */
    public function getProhibitionsRoless($criteria = null, ConnectionInterface $con = null)
    {
        $partial = $this->collProhibitionsRolessPartial && !$this->isNew();
        if (null === $this->collProhibitionsRoless || null !== $criteria  || $partial) {
            if ($this->isNew() && null === $this->collProhibitionsRoless) {
                // return empty collection
                $this->initProhibitionsRoless();
            } else {
                $collProhibitionsRoless = ChildProhibitionsRolesQuery::create(null, $criteria)
                    ->filterByPermission($this)
                    ->find($con);

                if (null !== $criteria) {
                    if (false !== $this->collProhibitionsRolessPartial && count($collProhibitionsRoless)) {
                        $this->initProhibitionsRoless(false);

                        foreach ($collProhibitionsRoless as $obj) {
                            if (false == $this->collProhibitionsRoless->contains($obj)) {
                                $this->collProhibitionsRoless->append($obj);
                            }
                        }

                        $this->collProhibitionsRolessPartial = true;
                    }

                    $collProhibitionsRoless->getInternalIterator()->rewind();

                    return $collProhibitionsRoless;
                }

                if ($partial && $this->collProhibitionsRoless) {
                    foreach ($this->collProhibitionsRoless as $obj) {
                        if ($obj->isNew()) {
                            $collProhibitionsRoless[] = $obj;
                        }
                    }
                }

                $this->collProhibitionsRoless = $collProhibitionsRoless;
                $this->collProhibitionsRolessPartial = false;
            }
        }

        return $this->collProhibitionsRoless;
    }

    /**
     * Sets a collection of ProhibitionsRoles objects related by a one-to-many relationship
     * to the current object.
     * It will also schedule objects for deletion based on a diff between old objects (aka persisted)
     * and new objects from the given Propel collection.
     *
     * @param      Collection $prohibitionsRoless A Propel collection.
     * @param      ConnectionInterface $con Optional connection object
     * @return   ChildPermission The current object (for fluent API support)
     */
    public function setProhibitionsRoless(Collection $prohibitionsRoless, ConnectionInterface $con = null)
    {
        $prohibitionsRolessToDelete = $this->getProhibitionsRoless(new Criteria(), $con)->diff($prohibitionsRoless);


        //since at least one column in the foreign key is at the same time a PK
        //we can not just set a PK to NULL in the lines below. We have to store
        //a backup of all values, so we are able to manipulate these items based on the onDelete value later.
        $this->prohibitionsRolessScheduledForDeletion = clone $prohibitionsRolessToDelete;

        foreach ($prohibitionsRolessToDelete as $prohibitionsRolesRemoved) {
            $prohibitionsRolesRemoved->setPermission(null);
        }

        $this->collProhibitionsRoless = null;
        foreach ($prohibitionsRoless as $prohibitionsRoles) {
            $this->addProhibitionsRoles($prohibitionsRoles);
        }

        $this->collProhibitionsRoless = $prohibitionsRoless;
        $this->collProhibitionsRolessPartial = false;

        return $this;
    }

    /**
     * Returns the number of related ProhibitionsRoles objects.
     *
     * @param      Criteria $criteria
     * @param      boolean $distinct
     * @param      ConnectionInterface $con
     * @return int             Count of related ProhibitionsRoles objects.
     * @throws PropelException
     */
    public function countProhibitionsRoless(Criteria $criteria = null, $distinct = false, ConnectionInterface $con = null)
    {
        $partial = $this->collProhibitionsRolessPartial && !$this->isNew();
        if (null === $this->collProhibitionsRoless || null !== $criteria || $partial) {
            if ($this->isNew() && null === $this->collProhibitionsRoless) {
                return 0;
            }

            if ($partial && !$criteria) {
                return count($this->getProhibitionsRoless());
            }

            $query = ChildProhibitionsRolesQuery::create(null, $criteria);
            if ($distinct) {
                $query->distinct();
            }

            return $query
                ->filterByPermission($this)
                ->count($con);
        }

        return count($this->collProhibitionsRoless);
    }

    /**
     * Method called to associate a ChildProhibitionsRoles object to this object
     * through the ChildProhibitionsRoles foreign key attribute.
     *
     * @param    ChildProhibitionsRoles $l ChildProhibitionsRoles
     * @return   \org\bitbucket\phlopsi\access_control\propel\Permission The current object (for fluent API support)
     */
    public function addProhibitionsRoles(ChildProhibitionsRoles $l)
    {
        if ($this->collProhibitionsRoless === null) {
            $this->initProhibitionsRoless();
            $this->collProhibitionsRolessPartial = true;
        }

        if (!in_array($l, $this->collProhibitionsRoless->getArrayCopy(), true)) { // only add it if the **same** object is not already associated
            $this->doAddProhibitionsRoles($l);
        }

        return $this;
    }

    /**
     * @param ProhibitionsRoles $prohibitionsRoles The prohibitionsRoles object to add.
     */
    protected function doAddProhibitionsRoles($prohibitionsRoles)
    {
        $this->collProhibitionsRoless[]= $prohibitionsRoles;
        $prohibitionsRoles->setPermission($this);
    }

    /**
     * @param  ProhibitionsRoles $prohibitionsRoles The prohibitionsRoles object to remove.
     * @return ChildPermission The current object (for fluent API support)
     */
    public function removeProhibitionsRoles($prohibitionsRoles)
    {
        if ($this->getProhibitionsRoless()->contains($prohibitionsRoles)) {
            $this->collProhibitionsRoless->remove($this->collProhibitionsRoless->search($prohibitionsRoles));
            if (null === $this->prohibitionsRolessScheduledForDeletion) {
                $this->prohibitionsRolessScheduledForDeletion = clone $this->collProhibitionsRoless;
                $this->prohibitionsRolessScheduledForDeletion->clear();
            }
            $this->prohibitionsRolessScheduledForDeletion[]= clone $prohibitionsRoles;
            $prohibitionsRoles->setPermission(null);
        }

        return $this;
    }


    /**
     * If this collection has already been initialized with
     * an identical criteria, it returns the collection.
     * Otherwise if this Permission is new, it will return
     * an empty collection; or if this Permission has previously
     * been saved, it will retrieve related ProhibitionsRoless from storage.
     *
     * This method is protected by default in order to keep the public
     * api reasonable.  You can provide public methods for those you
     * actually need in Permission.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @param      string $joinBehavior optional join type to use (defaults to Criteria::LEFT_JOIN)
     * @return Collection|ChildProhibitionsRoles[] List of ChildProhibitionsRoles objects
     */
    public function getProhibitionsRolessJoinRole($criteria = null, $con = null, $joinBehavior = Criteria::LEFT_JOIN)
    {
        $query = ChildProhibitionsRolesQuery::create(null, $criteria);
        $query->joinWith('Role', $joinBehavior);

        return $this->getProhibitionsRoless($query, $con);
    }

    /**
     * Clears out the collProhibitionsUserss collection
     *
     * This does not modify the database; however, it will remove any associated objects, causing
     * them to be refetched by subsequent calls to accessor method.
     *
     * @return void
     * @see        addProhibitionsUserss()
     */
    public function clearProhibitionsUserss()
    {
        $this->collProhibitionsUserss = null; // important to set this to NULL since that means it is uninitialized
    }

    /**
     * Reset is the collProhibitionsUserss collection loaded partially.
     */
    public function resetPartialProhibitionsUserss($v = true)
    {
        $this->collProhibitionsUserssPartial = $v;
    }

    /**
     * Initializes the collProhibitionsUserss collection.
     *
     * By default this just sets the collProhibitionsUserss collection to an empty array (like clearcollProhibitionsUserss());
     * however, you may wish to override this method in your stub class to provide setting appropriate
     * to your application -- for example, setting the initial array to the values stored in database.
     *
     * @param      boolean $overrideExisting If set to true, the method call initializes
     *                                        the collection even if it is not empty
     *
     * @return void
     */
    public function initProhibitionsUserss($overrideExisting = true)
    {
        if (null !== $this->collProhibitionsUserss && !$overrideExisting) {
            return;
        }
        $this->collProhibitionsUserss = new ObjectCollection();
        $this->collProhibitionsUserss->setModel('\org\bitbucket\phlopsi\access_control\propel\ProhibitionsUsers');
    }

    /**
     * Gets an array of ChildProhibitionsUsers objects which contain a foreign key that references this object.
     *
     * If the $criteria is not null, it is used to always fetch the results from the database.
     * Otherwise the results are fetched from the database the first time, then cached.
     * Next time the same method is called without $criteria, the cached collection is returned.
     * If this ChildPermission is new, it will return
     * an empty collection or the current collection; the criteria is ignored on a new object.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @return Collection|ChildProhibitionsUsers[] List of ChildProhibitionsUsers objects
     * @throws PropelException
     */
    public function getProhibitionsUserss($criteria = null, ConnectionInterface $con = null)
    {
        $partial = $this->collProhibitionsUserssPartial && !$this->isNew();
        if (null === $this->collProhibitionsUserss || null !== $criteria  || $partial) {
            if ($this->isNew() && null === $this->collProhibitionsUserss) {
                // return empty collection
                $this->initProhibitionsUserss();
            } else {
                $collProhibitionsUserss = ChildProhibitionsUsersQuery::create(null, $criteria)
                    ->filterByPermission($this)
                    ->find($con);

                if (null !== $criteria) {
                    if (false !== $this->collProhibitionsUserssPartial && count($collProhibitionsUserss)) {
                        $this->initProhibitionsUserss(false);

                        foreach ($collProhibitionsUserss as $obj) {
                            if (false == $this->collProhibitionsUserss->contains($obj)) {
                                $this->collProhibitionsUserss->append($obj);
                            }
                        }

                        $this->collProhibitionsUserssPartial = true;
                    }

                    $collProhibitionsUserss->getInternalIterator()->rewind();

                    return $collProhibitionsUserss;
                }

                if ($partial && $this->collProhibitionsUserss) {
                    foreach ($this->collProhibitionsUserss as $obj) {
                        if ($obj->isNew()) {
                            $collProhibitionsUserss[] = $obj;
                        }
                    }
                }

                $this->collProhibitionsUserss = $collProhibitionsUserss;
                $this->collProhibitionsUserssPartial = false;
            }
        }

        return $this->collProhibitionsUserss;
    }

    /**
     * Sets a collection of ProhibitionsUsers objects related by a one-to-many relationship
     * to the current object.
     * It will also schedule objects for deletion based on a diff between old objects (aka persisted)
     * and new objects from the given Propel collection.
     *
     * @param      Collection $prohibitionsUserss A Propel collection.
     * @param      ConnectionInterface $con Optional connection object
     * @return   ChildPermission The current object (for fluent API support)
     */
    public function setProhibitionsUserss(Collection $prohibitionsUserss, ConnectionInterface $con = null)
    {
        $prohibitionsUserssToDelete = $this->getProhibitionsUserss(new Criteria(), $con)->diff($prohibitionsUserss);


        //since at least one column in the foreign key is at the same time a PK
        //we can not just set a PK to NULL in the lines below. We have to store
        //a backup of all values, so we are able to manipulate these items based on the onDelete value later.
        $this->prohibitionsUserssScheduledForDeletion = clone $prohibitionsUserssToDelete;

        foreach ($prohibitionsUserssToDelete as $prohibitionsUsersRemoved) {
            $prohibitionsUsersRemoved->setPermission(null);
        }

        $this->collProhibitionsUserss = null;
        foreach ($prohibitionsUserss as $prohibitionsUsers) {
            $this->addProhibitionsUsers($prohibitionsUsers);
        }

        $this->collProhibitionsUserss = $prohibitionsUserss;
        $this->collProhibitionsUserssPartial = false;

        return $this;
    }

    /**
     * Returns the number of related ProhibitionsUsers objects.
     *
     * @param      Criteria $criteria
     * @param      boolean $distinct
     * @param      ConnectionInterface $con
     * @return int             Count of related ProhibitionsUsers objects.
     * @throws PropelException
     */
    public function countProhibitionsUserss(Criteria $criteria = null, $distinct = false, ConnectionInterface $con = null)
    {
        $partial = $this->collProhibitionsUserssPartial && !$this->isNew();
        if (null === $this->collProhibitionsUserss || null !== $criteria || $partial) {
            if ($this->isNew() && null === $this->collProhibitionsUserss) {
                return 0;
            }

            if ($partial && !$criteria) {
                return count($this->getProhibitionsUserss());
            }

            $query = ChildProhibitionsUsersQuery::create(null, $criteria);
            if ($distinct) {
                $query->distinct();
            }

            return $query
                ->filterByPermission($this)
                ->count($con);
        }

        return count($this->collProhibitionsUserss);
    }

    /**
     * Method called to associate a ChildProhibitionsUsers object to this object
     * through the ChildProhibitionsUsers foreign key attribute.
     *
     * @param    ChildProhibitionsUsers $l ChildProhibitionsUsers
     * @return   \org\bitbucket\phlopsi\access_control\propel\Permission The current object (for fluent API support)
     */
    public function addProhibitionsUsers(ChildProhibitionsUsers $l)
    {
        if ($this->collProhibitionsUserss === null) {
            $this->initProhibitionsUserss();
            $this->collProhibitionsUserssPartial = true;
        }

        if (!in_array($l, $this->collProhibitionsUserss->getArrayCopy(), true)) { // only add it if the **same** object is not already associated
            $this->doAddProhibitionsUsers($l);
        }

        return $this;
    }

    /**
     * @param ProhibitionsUsers $prohibitionsUsers The prohibitionsUsers object to add.
     */
    protected function doAddProhibitionsUsers($prohibitionsUsers)
    {
        $this->collProhibitionsUserss[]= $prohibitionsUsers;
        $prohibitionsUsers->setPermission($this);
    }

    /**
     * @param  ProhibitionsUsers $prohibitionsUsers The prohibitionsUsers object to remove.
     * @return ChildPermission The current object (for fluent API support)
     */
    public function removeProhibitionsUsers($prohibitionsUsers)
    {
        if ($this->getProhibitionsUserss()->contains($prohibitionsUsers)) {
            $this->collProhibitionsUserss->remove($this->collProhibitionsUserss->search($prohibitionsUsers));
            if (null === $this->prohibitionsUserssScheduledForDeletion) {
                $this->prohibitionsUserssScheduledForDeletion = clone $this->collProhibitionsUserss;
                $this->prohibitionsUserssScheduledForDeletion->clear();
            }
            $this->prohibitionsUserssScheduledForDeletion[]= clone $prohibitionsUsers;
            $prohibitionsUsers->setPermission(null);
        }

        return $this;
    }


    /**
     * If this collection has already been initialized with
     * an identical criteria, it returns the collection.
     * Otherwise if this Permission is new, it will return
     * an empty collection; or if this Permission has previously
     * been saved, it will retrieve related ProhibitionsUserss from storage.
     *
     * This method is protected by default in order to keep the public
     * api reasonable.  You can provide public methods for those you
     * actually need in Permission.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @param      string $joinBehavior optional join type to use (defaults to Criteria::LEFT_JOIN)
     * @return Collection|ChildProhibitionsUsers[] List of ChildProhibitionsUsers objects
     */
    public function getProhibitionsUserssJoinUser($criteria = null, $con = null, $joinBehavior = Criteria::LEFT_JOIN)
    {
        $query = ChildProhibitionsUsersQuery::create(null, $criteria);
        $query->joinWith('User', $joinBehavior);

        return $this->getProhibitionsUserss($query, $con);
    }

    /**
     * Clears the current object and sets all attributes to their default values
     */
    public function clear()
    {
        $this->external_id = null;
        $this->tree_left = null;
        $this->tree_right = null;
        $this->tree_level = null;
        $this->id = null;
        $this->alreadyInSave = false;
        $this->clearAllReferences();
        $this->resetModified();
        $this->setNew(true);
        $this->setDeleted(false);
    }

    /**
     * Resets all references to other model objects or collections of model objects.
     *
     * This method is a user-space workaround for PHP's inability to garbage collect
     * objects with circular references (even in PHP 5.3). This is currently necessary
     * when using Propel in certain daemon or large-volume/high-memory operations.
     *
     * @param      boolean $deep Whether to also clear the references on all referrer objects.
     */
    public function clearAllReferences($deep = false)
    {
        if ($deep) {
            if ($this->collProhibitionsRoless) {
                foreach ($this->collProhibitionsRoless as $o) {
                    $o->clearAllReferences($deep);
                }
            }
            if ($this->collProhibitionsUserss) {
                foreach ($this->collProhibitionsUserss as $o) {
                    $o->clearAllReferences($deep);
                }
            }
        } // if ($deep)

        // nested_set behavior
        $this->collNestedSetChildren = null;
        $this->aNestedSetParent = null;
        if ($this->collProhibitionsRoless instanceof Collection) {
            $this->collProhibitionsRoless->clearIterator();
        }
        $this->collProhibitionsRoless = null;
        if ($this->collProhibitionsUserss instanceof Collection) {
            $this->collProhibitionsUserss->clearIterator();
        }
        $this->collProhibitionsUserss = null;
    }

    /**
     * Return the string representation of this object
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->exportTo(PermissionTableMap::DEFAULT_STRING_FORMAT);
    }

    // nested_set behavior

    /**
     * Execute queries that were saved to be run inside the save transaction
     */
    protected function processNestedSetQueries($con)
    {
        foreach ($this->nestedSetQueries as $query) {
            $query['arguments'][]= $con;
            call_user_func_array($query['callable'], $query['arguments']);
        }
        $this->nestedSetQueries = array();
    }

    /**
     * Proxy getter method for the left value of the nested set model.
     * It provides a generic way to get the value, whatever the actual column name is.
     *
     * @return     int The nested set left value
     */
    public function getLeftValue()
    {
        return $this->tree_left;
    }

    /**
     * Proxy getter method for the right value of the nested set model.
     * It provides a generic way to get the value, whatever the actual column name is.
     *
     * @return     int The nested set right value
     */
    public function getRightValue()
    {
        return $this->tree_right;
    }

    /**
     * Proxy getter method for the level value of the nested set model.
     * It provides a generic way to get the value, whatever the actual column name is.
     *
     * @return     int The nested set level value
     */
    public function getLevel()
    {
        return $this->tree_level;
    }

    /**
     * Proxy setter method for the left value of the nested set model.
     * It provides a generic way to set the value, whatever the actual column name is.
     *
     * @param  int $v The nested set left value
     * @return ChildPermission The current object (for fluent API support)
     */
    public function setLeftValue($v)
    {
        return $this->setTreeLeft($v);
    }

    /**
     * Proxy setter method for the right value of the nested set model.
     * It provides a generic way to set the value, whatever the actual column name is.
     *
     * @param      int $v The nested set right value
     * @return     ChildPermission The current object (for fluent API support)
     */
    public function setRightValue($v)
    {
        return $this->setTreeRight($v);
    }

    /**
     * Proxy setter method for the level value of the nested set model.
     * It provides a generic way to set the value, whatever the actual column name is.
     *
     * @param      int $v The nested set level value
     * @return     ChildPermission The current object (for fluent API support)
     */
    public function setLevel($v)
    {
        return $this->setTreeLevel($v);
    }

    /**
     * Creates the supplied node as the root node.
     *
     * @return     ChildPermission The current object (for fluent API support)
     * @throws     PropelException
     */
    public function makeRoot()
    {
        if ($this->getLeftValue() || $this->getRightValue()) {
            throw new PropelException('Cannot turn an existing node into a root node.');
        }

        $this->setLeftValue(1);
        $this->setRightValue(2);
        $this->setLevel(0);

        return $this;
    }

    /**
     * Tests if object is a node, i.e. if it is inserted in the tree
     *
     * @return     bool
     */
    public function isInTree()
    {
        return $this->getLeftValue() > 0 && $this->getRightValue() > $this->getLeftValue();
    }

    /**
     * Tests if node is a root
     *
     * @return     bool
     */
    public function isRoot()
    {
        return $this->isInTree() && $this->getLeftValue() == 1;
    }

    /**
     * Tests if node is a leaf
     *
     * @return     bool
     */
    public function isLeaf()
    {
        return $this->isInTree() &&  ($this->getRightValue() - $this->getLeftValue()) == 1;
    }

    /**
     * Tests if node is a descendant of another node
     *
     * @param      ChildPermission $node Propel node object
     * @return     bool
     */
    public function isDescendantOf($parent)
    {

        return $this->isInTree() && $this->getLeftValue() > $parent->getLeftValue() && $this->getRightValue() < $parent->getRightValue();
    }

    /**
     * Tests if node is a ancestor of another node
     *
     * @param      ChildPermission $node Propel node object
     * @return     bool
     */
    public function isAncestorOf($child)
    {
        return $child->isDescendantOf($this);
    }

    /**
     * Tests if object has an ancestor
     *
     * @return boolean
     */
    public function hasParent()
    {
        return $this->getLevel() > 0;
    }

    /**
     * Sets the cache for parent node of the current object.
     * Warning: this does not move the current object in the tree.
     * Use moveTofirstChildOf() or moveToLastChildOf() for that purpose
     *
     * @param      ChildPermission $parent
     * @return     ChildPermission The current object, for fluid interface
     */
    public function setParent($parent = null)
    {
        $this->aNestedSetParent = $parent;

        return $this;
    }

    /**
     * Gets parent node for the current object if it exists
     * The result is cached so further calls to the same method don't issue any queries
     *
     * @param  ConnectionInterface $con Connection to use.
     * @return mixed Propel object if exists else false
     */
    public function getParent(ConnectionInterface $con = null)
    {
        if (null === $this->aNestedSetParent && $this->hasParent()) {
            $this->aNestedSetParent = ChildPermissionQuery::create()
                ->ancestorsOf($this)
                ->orderByLevel(true)
                ->findOne($con);
        }

        return $this->aNestedSetParent;
    }

    /**
     * Determines if the node has previous sibling
     *
     * @param      ConnectionInterface $con Connection to use.
     * @return     bool
     */
    public function hasPrevSibling(ConnectionInterface $con = null)
    {
        if (!ChildPermissionQuery::isValid($this)) {
            return false;
        }

        return ChildPermissionQuery::create()
            ->filterByTreeRight($this->getLeftValue() - 1)
            ->count($con) > 0;
    }

    /**
     * Gets previous sibling for the given node if it exists
     *
     * @param      ConnectionInterface $con Connection to use.
     * @return     mixed         Propel object if exists else false
     */
    public function getPrevSibling(ConnectionInterface $con = null)
    {
        return ChildPermissionQuery::create()
            ->filterByTreeRight($this->getLeftValue() - 1)
            ->findOne($con);
    }

    /**
     * Determines if the node has next sibling
     *
     * @param      ConnectionInterface $con Connection to use.
     * @return     bool
     */
    public function hasNextSibling(ConnectionInterface $con = null)
    {
        if (!ChildPermissionQuery::isValid($this)) {
            return false;
        }

        return ChildPermissionQuery::create()
            ->filterByTreeLeft($this->getRightValue() + 1)
            ->count($con) > 0;
    }

    /**
     * Gets next sibling for the given node if it exists
     *
     * @param      ConnectionInterface $con Connection to use.
     * @return     mixed         Propel object if exists else false
     */
    public function getNextSibling(ConnectionInterface $con = null)
    {
        return ChildPermissionQuery::create()
            ->filterByTreeLeft($this->getRightValue() + 1)
            ->findOne($con);
    }

    /**
     * Clears out the $collNestedSetChildren collection
     *
     * This does not modify the database; however, it will remove any associated objects, causing
     * them to be refetched by subsequent calls to accessor method.
     *
     * @return     void
     */
    public function clearNestedSetChildren()
    {
        $this->collNestedSetChildren = null;
    }

    /**
     * Initializes the $collNestedSetChildren collection.
     *
     * @return     void
     */
    public function initNestedSetChildren()
    {
        $this->collNestedSetChildren = new ObjectCollection();
        $this->collNestedSetChildren->setModel('\org\bitbucket\phlopsi\access_control\propel\Permission');
    }

    /**
     * Adds an element to the internal $collNestedSetChildren collection.
     * Beware that this doesn't insert a node in the tree.
     * This method is only used to facilitate children hydration.
     *
     * @param      ChildPermission $permission
     *
     * @return     void
     */
    public function addNestedSetChild($permission)
    {
        if (null === $this->collNestedSetChildren) {
            $this->initNestedSetChildren();
        }
        if (!in_array($permission, $this->collNestedSetChildren->getArrayCopy(), true)) { // only add it if the **same** object is not already associated
            $this->collNestedSetChildren[]= $permission;
            $permission->setParent($this);
        }
    }

    /**
     * Tests if node has children
     *
     * @return     bool
     */
    public function hasChildren()
    {
        return ($this->getRightValue() - $this->getLeftValue()) > 1;
    }

    /**
     * Gets the children of the given node
     *
     * @param      Criteria  $criteria Criteria to filter results.
     * @param      ConnectionInterface $con Connection to use.
     * @return     array     List of ChildPermission objects
     */
    public function getChildren($criteria = null, ConnectionInterface $con = null)
    {
        if (null === $this->collNestedSetChildren || null !== $criteria) {
            if ($this->isLeaf() || ($this->isNew() && null === $this->collNestedSetChildren)) {
                // return empty collection
                $this->initNestedSetChildren();
            } else {
                $collNestedSetChildren = ChildPermissionQuery::create(null, $criteria)
                  ->childrenOf($this)
                  ->orderByBranch()
                    ->find($con);
                if (null !== $criteria) {
                    return $collNestedSetChildren;
                }
                $this->collNestedSetChildren = $collNestedSetChildren;
            }
        }

        return $this->collNestedSetChildren;
    }

    /**
     * Gets number of children for the given node
     *
     * @param      Criteria  $criteria Criteria to filter results.
     * @param      ConnectionInterface $con Connection to use.
     * @return     int       Number of children
     */
    public function countChildren($criteria = null, ConnectionInterface $con = null)
    {
        if (null === $this->collNestedSetChildren || null !== $criteria) {
            if ($this->isLeaf() || ($this->isNew() && null === $this->collNestedSetChildren)) {
                return 0;
            } else {
                return ChildPermissionQuery::create(null, $criteria)
                    ->childrenOf($this)
                    ->count($con);
            }
        } else {
            return count($this->collNestedSetChildren);
        }
    }

    /**
     * Gets the first child of the given node
     *
     * @param      Criteria $query Criteria to filter results.
     * @param      ConnectionInterface $con Connection to use.
     * @return     array         List of ChildPermission objects
     */
    public function getFirstChild($query = null, ConnectionInterface $con = null)
    {
        if ($this->isLeaf()) {
            return array();
        } else {
            return ChildPermissionQuery::create(null, $query)
                ->childrenOf($this)
                ->orderByBranch()
                ->findOne($con);
        }
    }

    /**
     * Gets the last child of the given node
     *
     * @param      Criteria $query Criteria to filter results.
     * @param      ConnectionInterface $con Connection to use.
     * @return     array         List of ChildPermission objects
     */
    public function getLastChild($query = null, ConnectionInterface $con = null)
    {
        if ($this->isLeaf()) {
            return array();
        } else {
            return ChildPermissionQuery::create(null, $query)
                ->childrenOf($this)
                ->orderByBranch(true)
                ->findOne($con);
        }
    }

    /**
     * Gets the siblings of the given node
     *
     * @param boolean             $includeNode Whether to include the current node or not
     * @param Criteria            $query Criteria to filter results.
     * @param ConnectionInterface $con Connection to use.
     *
     * @return array List of ChildPermission objects
     */
    public function getSiblings($includeNode = false, $query = null, ConnectionInterface $con = null)
    {
        if ($this->isRoot()) {
            return array();
        } else {
             $query = ChildPermissionQuery::create(null, $query)
                    ->childrenOf($this->getParent($con))
                    ->orderByBranch();
            if (!$includeNode) {
                $query->prune($this);
            }

            return $query->find($con);
        }
    }

    /**
     * Gets descendants for the given node
     *
     * @param      Criteria $query Criteria to filter results.
     * @param      ConnectionInterface $con Connection to use.
     * @return     array         List of ChildPermission objects
     */
    public function getDescendants($query = null, ConnectionInterface $con = null)
    {
        if ($this->isLeaf()) {
            return array();
        } else {
            return ChildPermissionQuery::create(null, $query)
                ->descendantsOf($this)
                ->orderByBranch()
                ->find($con);
        }
    }

    /**
     * Gets number of descendants for the given node
     *
     * @param      Criteria $query Criteria to filter results.
     * @param      ConnectionInterface $con Connection to use.
     * @return     int         Number of descendants
     */
    public function countDescendants($query = null, ConnectionInterface $con = null)
    {
        if ($this->isLeaf()) {
            // save one query
            return 0;
        } else {
            return ChildPermissionQuery::create(null, $query)
                ->descendantsOf($this)
                ->count($con);
        }
    }

    /**
     * Gets descendants for the given node, plus the current node
     *
     * @param      Criteria $query Criteria to filter results.
     * @param      ConnectionInterface $con Connection to use.
     * @return     array         List of ChildPermission objects
     */
    public function getBranch($query = null, ConnectionInterface $con = null)
    {
        return ChildPermissionQuery::create(null, $query)
            ->branchOf($this)
            ->orderByBranch()
            ->find($con);
    }

    /**
     * Gets ancestors for the given node, starting with the root node
     * Use it for breadcrumb paths for instance
     *
     * @param      Criteria $query Criteria to filter results.
     * @param      ConnectionInterface $con Connection to use.
     * @return     array         List of ChildPermission objects
     */
    public function getAncestors($query = null, ConnectionInterface $con = null)
    {
        if ($this->isRoot()) {
            // save one query
            return array();
        } else {
            return ChildPermissionQuery::create(null, $query)
                ->ancestorsOf($this)
                ->orderByBranch()
                ->find($con);
        }
    }

    /**
     * Inserts the given $child node as first child of current
     * The modifications in the current object and the tree
     * are not persisted until the child object is saved.
     *
     * @param      ChildPermission $child    Propel object for child node
     *
     * @return     ChildPermission The current Propel object
     */
    public function addChild(ChildPermission $child)
    {
        if ($this->isNew()) {
            throw new PropelException('A ChildPermission object must not be new to accept children.');
        }
        $child->insertAsFirstChildOf($this);

        return $this;
    }

    /**
     * Inserts the current node as first child of given $parent node
     * The modifications in the current object and the tree
     * are not persisted until the current object is saved.
     *
     * @param      ChildPermission $parent    Propel object for parent node
     *
     * @return     ChildPermission The current Propel object
     */
    public function insertAsFirstChildOf($parent)
    {
        if ($this->isInTree()) {
            throw new PropelException('A ChildPermission object must not already be in the tree to be inserted. Use the moveToFirstChildOf() instead.');
        }
        $left = $parent->getLeftValue() + 1;
        // Update node properties
        $this->setLeftValue($left);
        $this->setRightValue($left + 1);
        $this->setLevel($parent->getLevel() + 1);
        // update the children collection of the parent
        $parent->addNestedSetChild($this);

        // Keep the tree modification query for the save() transaction
        $this->nestedSetQueries []= array(
            'callable'  => array('\org\bitbucket\phlopsi\access_control\propel\PermissionQuery', 'makeRoomForLeaf'),
            'arguments' => array($left, $this->isNew() ? null : $this)
        );

        return $this;
    }

    /**
     * Inserts the current node as last child of given $parent node
     * The modifications in the current object and the tree
     * are not persisted until the current object is saved.
     *
     * @param  ChildPermission $parent Propel object for parent node
     * @return ChildPermission The current Propel object
     */
    public function insertAsLastChildOf($parent)
    {
        if ($this->isInTree()) {
           throw new PropelException(
                'A ChildPermission object must not already be in the tree to be inserted. Use the moveToLastChildOf() instead.'
            );
        }

        $left = $parent->getRightValue();
        // Update node properties
        $this->setLeftValue($left);
        $this->setRightValue($left + 1);
        $this->setLevel($parent->getLevel() + 1);

        // update the children collection of the parent
        $parent->addNestedSetChild($this);

        // Keep the tree modification query for the save() transaction
        $this->nestedSetQueries []= array(
            'callable'  => array('\org\bitbucket\phlopsi\access_control\propel\PermissionQuery', 'makeRoomForLeaf'),
            'arguments' => array($left, $this->isNew() ? null : $this)
        );

        return $this;
    }

    /**
     * Inserts the current node as prev sibling given $sibling node
     * The modifications in the current object and the tree
     * are not persisted until the current object is saved.
     *
     * @param      ChildPermission $sibling    Propel object for parent node
     *
     * @return     ChildPermission The current Propel object
     */
    public function insertAsPrevSiblingOf($sibling)
    {
        if ($this->isInTree()) {
            throw new PropelException('A ChildPermission object must not already be in the tree to be inserted. Use the moveToPrevSiblingOf() instead.');
        }
        $left = $sibling->getLeftValue();
        // Update node properties
        $this->setLeftValue($left);
        $this->setRightValue($left + 1);
        $this->setLevel($sibling->getLevel());
        // Keep the tree modification query for the save() transaction
        $this->nestedSetQueries []= array(
            'callable'  => array('\org\bitbucket\phlopsi\access_control\propel\PermissionQuery', 'makeRoomForLeaf'),
            'arguments' => array($left, $this->isNew() ? null : $this)
        );

        return $this;
    }

    /**
     * Inserts the current node as next sibling given $sibling node
     * The modifications in the current object and the tree
     * are not persisted until the current object is saved.
     *
     * @param      ChildPermission $sibling    Propel object for parent node
     *
     * @return     ChildPermission The current Propel object
     */
    public function insertAsNextSiblingOf($sibling)
    {
        if ($this->isInTree()) {
            throw new PropelException('A ChildPermission object must not already be in the tree to be inserted. Use the moveToNextSiblingOf() instead.');
        }
        $left = $sibling->getRightValue() + 1;
        // Update node properties
        $this->setLeftValue($left);
        $this->setRightValue($left + 1);
        $this->setLevel($sibling->getLevel());
        // Keep the tree modification query for the save() transaction
        $this->nestedSetQueries []= array(
            'callable'  => array('\org\bitbucket\phlopsi\access_control\propel\PermissionQuery', 'makeRoomForLeaf'),
            'arguments' => array($left, $this->isNew() ? null : $this)
        );

        return $this;
    }

    /**
     * Moves current node and its subtree to be the first child of $parent
     * The modifications in the current object and the tree are immediate
     *
     * @param      ChildPermission $parent    Propel object for parent node
     * @param      ConnectionInterface $con    Connection to use.
     *
     * @return     ChildPermission The current Propel object
     */
    public function moveToFirstChildOf($parent, ConnectionInterface $con = null)
    {
        if (!$this->isInTree()) {
            throw new PropelException('A ChildPermission object must be already in the tree to be moved. Use the insertAsFirstChildOf() instead.');
        }
        if ($parent->isDescendantOf($this)) {
            throw new PropelException('Cannot move a node as child of one of its subtree nodes.');
        }

        $this->moveSubtreeTo($parent->getLeftValue() + 1, $parent->getLevel() - $this->getLevel() + 1, $con);

        return $this;
    }

    /**
     * Moves current node and its subtree to be the last child of $parent
     * The modifications in the current object and the tree are immediate
     *
     * @param      ChildPermission $parent    Propel object for parent node
     * @param      ConnectionInterface $con    Connection to use.
     *
     * @return     ChildPermission The current Propel object
     */
    public function moveToLastChildOf($parent, ConnectionInterface $con = null)
    {
        if (!$this->isInTree()) {
            throw new PropelException('A ChildPermission object must be already in the tree to be moved. Use the insertAsLastChildOf() instead.');
        }
        if ($parent->isDescendantOf($this)) {
            throw new PropelException('Cannot move a node as child of one of its subtree nodes.');
        }

        $this->moveSubtreeTo($parent->getRightValue(), $parent->getLevel() - $this->getLevel() + 1, $con);

        return $this;
    }

    /**
     * Moves current node and its subtree to be the previous sibling of $sibling
     * The modifications in the current object and the tree are immediate
     *
     * @param      ChildPermission $sibling    Propel object for sibling node
     * @param      ConnectionInterface $con    Connection to use.
     *
     * @return     ChildPermission The current Propel object
     */
    public function moveToPrevSiblingOf($sibling, ConnectionInterface $con = null)
    {
        if (!$this->isInTree()) {
            throw new PropelException('A ChildPermission object must be already in the tree to be moved. Use the insertAsPrevSiblingOf() instead.');
        }
        if ($sibling->isRoot()) {
            throw new PropelException('Cannot move to previous sibling of a root node.');
        }
        if ($sibling->isDescendantOf($this)) {
            throw new PropelException('Cannot move a node as sibling of one of its subtree nodes.');
        }

        $this->moveSubtreeTo($sibling->getLeftValue(), $sibling->getLevel() - $this->getLevel(), $con);

        return $this;
    }

    /**
     * Moves current node and its subtree to be the next sibling of $sibling
     * The modifications in the current object and the tree are immediate
     *
     * @param      ChildPermission $sibling    Propel object for sibling node
     * @param      ConnectionInterface $con    Connection to use.
     *
     * @return     ChildPermission The current Propel object
     */
    public function moveToNextSiblingOf($sibling, ConnectionInterface $con = null)
    {
        if (!$this->isInTree()) {
            throw new PropelException('A ChildPermission object must be already in the tree to be moved. Use the insertAsNextSiblingOf() instead.');
        }
        if ($sibling->isRoot()) {
            throw new PropelException('Cannot move to next sibling of a root node.');
        }
        if ($sibling->isDescendantOf($this)) {
            throw new PropelException('Cannot move a node as sibling of one of its subtree nodes.');
        }

        $this->moveSubtreeTo($sibling->getRightValue() + 1, $sibling->getLevel() - $this->getLevel(), $con);

        return $this;
    }

    /**
     * Move current node and its children to location $destLeft and updates rest of tree
     *
     * @param      int    $destLeft Destination left value
     * @param      int    $levelDelta Delta to add to the levels
     * @param      ConnectionInterface $con        Connection to use.
     */
    protected function moveSubtreeTo($destLeft, $levelDelta, PropelPDO $con = null)
    {
        $preventDefault = false;
        $left  = $this->getLeftValue();
        $right = $this->getRightValue();


        $treeSize = $right - $left +1;

        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(PermissionTableMap::DATABASE_NAME);
        }

        $con->beginTransaction();
        try {
            // make room next to the target for the subtree
            ChildPermissionQuery::shiftRLValues($treeSize, $destLeft, null, $con);



            if (!$preventDefault) {


                if ($left >= $destLeft) { // src was shifted too?
                    $left += $treeSize;
                    $right += $treeSize;
                }

                if ($levelDelta) {
                    // update the levels of the subtree
                    ChildPermissionQuery::shiftLevel($levelDelta, $left, $right, $con);
                }

                // move the subtree to the target
                ChildPermissionQuery::shiftRLValues($destLeft - $left, $left, $right, $con);
            }

            // remove the empty room at the previous location of the subtree
            ChildPermissionQuery::shiftRLValues(-$treeSize, $right + 1, null, $con);

            // update all loaded nodes
            ChildPermissionQuery::updateLoadedNodes(null, $con);

            $con->commit();
        } catch (PropelException $e) {
            $con->rollback();
            throw $e;
        }
    }

    /**
     * Deletes all descendants for the given node
     * Instance pooling is wiped out by this command,
     * so existing ChildPermission instances are probably invalid (except for the current one)
     *
     * @param      ConnectionInterface $con Connection to use.
     *
     * @return     int         number of deleted nodes
     */
    public function deleteDescendants(ConnectionInterface $con = null)
    {
        if ($this->isLeaf()) {
            // save one query
            return;
        }
        if (null === $con) {
            $con = Propel::getServiceContainer()->getReadConnection(PermissionTableMap::DATABASE_NAME);
        }
        $left = $this->getLeftValue();
        $right = $this->getRightValue();
        $con->beginTransaction();
        try {
            // delete descendant nodes (will empty the instance pool)
            $ret = ChildPermissionQuery::create()
                ->descendantsOf($this)
                ->delete($con);

            // fill up the room that was used by descendants
            ChildPermissionQuery::shiftRLValues($left - $right + 1, $right, null, $con);

            // fix the right value for the current node, which is now a leaf
            $this->setRightValue($left + 1);

            $con->commit();
        } catch (Exception $e) {
            $con->rollback();
            throw $e;
        }

        return $ret;
    }

    /**
     * Returns a pre-order iterator for this node and its children.
     *
     * @return RecursiveIterator
     */
    public function getIterator()
    {
        return new NestedSetRecursiveIterator($this);
    }

    /**
     * Code to be run before persisting the object
     * @param  ConnectionInterface $con
     * @return boolean
     */
    public function preSave(ConnectionInterface $con = null)
    {
        return true;
    }

    /**
     * Code to be run after persisting the object
     * @param ConnectionInterface $con
     */
    public function postSave(ConnectionInterface $con = null)
    {

    }

    /**
     * Code to be run before inserting to database
     * @param  ConnectionInterface $con
     * @return boolean
     */
    public function preInsert(ConnectionInterface $con = null)
    {
        return true;
    }

    /**
     * Code to be run after inserting to database
     * @param ConnectionInterface $con
     */
    public function postInsert(ConnectionInterface $con = null)
    {

    }

    /**
     * Code to be run before updating the object in database
     * @param  ConnectionInterface $con
     * @return boolean
     */
    public function preUpdate(ConnectionInterface $con = null)
    {
        return true;
    }

    /**
     * Code to be run after updating the object in database
     * @param ConnectionInterface $con
     */
    public function postUpdate(ConnectionInterface $con = null)
    {

    }

    /**
     * Code to be run before deleting the object in database
     * @param  ConnectionInterface $con
     * @return boolean
     */
    public function preDelete(ConnectionInterface $con = null)
    {
        return true;
    }

    /**
     * Code to be run after deleting the object in database
     * @param ConnectionInterface $con
     */
    public function postDelete(ConnectionInterface $con = null)
    {

    }


    /**
     * Derived method to catches calls to undefined methods.
     *
     * Provides magic import/export method support (fromXML()/toXML(), fromYAML()/toYAML(), etc.).
     * Allows to define default __call() behavior if you overwrite __call()
     *
     * @param string $name
     * @param mixed  $params
     *
     * @return array|string
     */
    public function __call($name, $params)
    {
        if (0 === strpos($name, 'get')) {
            $virtualColumn = substr($name, 3);
            if ($this->hasVirtualColumn($virtualColumn)) {
                return $this->getVirtualColumn($virtualColumn);
            }

            $virtualColumn = lcfirst($virtualColumn);
            if ($this->hasVirtualColumn($virtualColumn)) {
                return $this->getVirtualColumn($virtualColumn);
            }
        }

        if (0 === strpos($name, 'from')) {
            $format = substr($name, 4);

            return $this->importFrom($format, reset($params));
        }

        if (0 === strpos($name, 'to')) {
            $format = substr($name, 2);
            $includeLazyLoadColumns = isset($params[0]) ? $params[0] : true;

            return $this->exportTo($format, $includeLazyLoadColumns);
        }

        throw new BadMethodCallException(sprintf('Call to undefined method: %s.', $name));
    }

}
