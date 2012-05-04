<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\DBAL\Platforms;

use Doctrine\DBAL\DBALException,
    Doctrine\DBAL\Schema\TableDiff,
    Doctrine\DBAL\Schema\Index,
    Doctrine\DBAL\Schema\Table;

/**
 * Drizzle platform
 * 
 * @author Kim Hemsø Rasmussen <kimhemsoe@gmail.com> 
 */
class DrizzlePlatform extends AbstractPlatform
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'drizzle';
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifierQuoteCharacter()
    {
        return '`';
    }

    public function getConcatExpression()
    {
        $args = func_get_args();
        return 'CONCAT(' . join(', ', (array) $args) . ')';
    }

    public function getDateDiffExpression($date1, $date2)
    {
        return 'DATEDIFF(' . $date1 . ', ' . $date2 . ')';
    }

    public function getDateAddDaysExpression($date, $days)
    {
        return 'DATE_ADD(' . $date . ', INTERVAL ' . $days . ' DAY)';
    }

    public function getDateSubDaysExpression($date, $days)
    {
        return 'DATE_SUB(' . $date . ', INTERVAL ' . $days . ' DAY)';
    }

    public function getDateAddMonthExpression($date, $months)
    {
        return 'DATE_ADD(' . $date . ', INTERVAL ' . $months . ' MONTH)';
    }

    public function getDateSubMonthExpression($date, $months)
    {
        return 'DATE_SUB(' . $date . ', INTERVAL ' . $months . ' MONTH)';
    }

    /**
     * @override
     */
    public function getBooleanTypeDeclarationSQL(array $field)
    {
        return 'BOOLEAN';
    }


    public function getIntegerTypeDeclarationSQL(array $field)
    {
        return 'INT' . $this->_getCommonIntegerTypeDeclarationSQL($field);
    }


    protected function _getCommonIntegerTypeDeclarationSQL(array $columnDef)
    {
        $autoinc = '';
        if ( ! empty($columnDef['autoincrement'])) {
            $autoinc = ' AUTO_INCREMENT';
        }
        return $autoinc;
    }

    public function getBigIntTypeDeclarationSQL(array $field)
    {
        return 'BIGINT' . $this->_getCommonIntegerTypeDeclarationSQL($field);
    }


    public function getSmallIntTypeDeclarationSQL(array $field)
    {
        return 'INT' . $this->_getCommonIntegerTypeDeclarationSQL($field);
    }

    protected function getVarcharTypeDeclarationSQLSnippet($length, $fixed)
    {
        return $length ? 'VARCHAR(' . $length . ')' : 'VARCHAR(255)';
    }

    protected function initializeDoctrineTypeMappings()
    {
        $this->doctrineTypeMapping = array(
            'boolean'       => 'boolean',
            'varchar'       => 'string',
            'integer'       => 'integer',
            'blob'          => 'text',
            'decimal'       => 'decimal',
            'datetime'      => 'datetime',
            'date'          => 'date',
            'time'          => 'time',
            'text'          => 'text',
            'timestamp'     => 'datetime',
            'double'        => 'float',
            'bigint'        => 'bigint',
        );
    }

    public function getClobTypeDeclarationSQL(array $field)
    {
        return 'TEXT';
    }

    /**
     * Gets the SQL Snippet used to declare a BLOB column type.
     */
    public function getBlobTypeDeclarationSQL(array $field)
    {
        return 'BLOB';
    }

    public function getCreateDatabaseSQL($name)
    {
        return 'CREATE DATABASE ' . $name;
    }

    public function getDropDatabaseSQL($name)
    {
        return 'DROP DATABASE ' . $name;
    }

    public function getListDatabasesSQL()
    {
        return "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE CATALOG_NAME='LOCAL'";
    }

    protected function getReservedKeywordsClass()
    {
        return 'Doctrine\DBAL\Platforms\Keywords\DrizzleKeywords';
    }

    public function getListTablesSQL()
    {
        return "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE='BASE' AND TABLE_SCHEMA=DATABASE()";
    }

    public function getListTableColumnsSQL($table, $database = null)
    {
        if ($database) {
            $database = "'" . $database . "'";
        } else {
            $database = 'DATABASE()';
        }

        return "SELECT COLUMN_NAME, DATA_TYPE, COLUMN_COMMENT, IS_NULLABLE, IS_AUTO_INCREMENT, CHARACTER_MAXIMUM_LENGTH, COLUMN_DEFAULT," .
               " NUMERIC_PRECISION, NUMERIC_SCALE" .
               " FROM DATA_DICTIONARY.COLUMNS" .
               " WHERE TABLE_SCHEMA=" . $database . " AND TABLE_NAME = '" . $table . "'";
    }

    public function getListTableForeignKeysSQL($table, $database = null)
    {
        if ($database) {
            $database = "'" . $database . "'";
        } else {
            $database = 'DATABASE()';
        }

        return "SELECT CONSTRAINT_NAME, CONSTRAINT_COLUMNS, REFERENCED_TABLE_NAME, REFERENCED_TABLE_COLUMNS, UPDATE_RULE, DELETE_RULE" .
               " FROM DATA_DICTIONARY.FOREIGN_KEYS" .
               " WHERE CONSTRAINT_SCHEMA=" . $database . " AND CONSTRAINT_TABLE='" . $table . "'";
    }

    public function getListTableIndexesSQL($table, $database = null)
    {
        if ($database) {
            $database = "'" . $database . "'";
        } else {
            $database = 'DATABASE()';
        }

        return "SELECT INDEX_NAME AS 'key_name', COLUMN_NAME AS 'column_name', IS_USED_IN_PRIMARY AS 'primary', IS_UNIQUE=0 AS 'non_unique'" .
               " FROM DATA_DICTIONARY.INDEX_PARTS" .
               " WHERE TABLE_SCHEMA=" . $database . " AND TABLE_NAME='" . $table . "'";
    }

    public function prefersIdentityColumns()
    {
        return true;
    }

    public function supportsIdentityColumns()
    {
        return true;
    }

    public function supportsInlineColumnComments()
    {
        return true;
    }

    public function supportsViews()
    {
        return false;
    }

    public function getDropIndexSQL($index, $table=null)
    {
        if($index instanceof Index) {
            $indexName = $index->getQuotedName($this);
        } else if(is_string($index)) {
            $indexName = $index;
        } else {
            throw new \InvalidArgumentException('DrizzlePlatform::getDropIndexSQL() expects $index parameter to be string or \Doctrine\DBAL\Schema\Index.');
        }

        if($table instanceof Table) {
            $table = $table->getQuotedName($this);
        } else if(!is_string($table)) {
            throw new \InvalidArgumentException('DrizzlePlatform::getDropIndexSQL() expects $table parameter to be string or \Doctrine\DBAL\Schema\Table.');
        }

        if ($index instanceof Index && $index->isPrimary()) {
            // drizzle primary keys are always named "PRIMARY",
            // so we cannot use them in statements because of them being keyword.
            return $this->getDropPrimaryKeySQL($table);
        }

        return 'DROP INDEX ' . $indexName . ' ON ' . $table;
    }

    /**
     * @param Index $index
     * @param Table $table
     */
    protected function getDropPrimaryKeySQL($table)
    {
        return 'ALTER TABLE ' . $table . ' DROP PRIMARY KEY';
    }

    public function getDateTimeTypeDeclarationSQL(array $fieldDeclaration)
    {
        if (isset($fieldDeclaration['version']) && $fieldDeclaration['version'] == true) {
            return 'TIMESTAMP';
        } else {
            return 'DATETIME';
        }
    }

    public function getTimeTypeDeclarationSQL(array $fieldDeclaration)
    {
        return 'TIME';
    }

    public function getDateTypeDeclarationSQL(array $fieldDeclaration)
    {
        return 'DATE';
    }

    /**
     * C/P from mysql platform
     *
     * @param TableDiff $diff
     * @return type
     */
    public function getAlterTableSQL(TableDiff $diff)
    {
        $columnSql = array();
        $queryParts = array();

        if ($diff->newName !== false) {
            $queryParts[] =  'RENAME TO ' . $diff->newName;
        }

        foreach ($diff->addedColumns as $fieldName => $column) {
            if ($this->onSchemaAlterTableAddColumn($column, $diff, $columnSql)) {
                continue;
            }

            $columnArray = $column->toArray();
            $columnArray['comment'] = $this->getColumnComment($column);
            $queryParts[] = 'ADD ' . $this->getColumnDeclarationSQL($column->getQuotedName($this), $columnArray);
        }

        foreach ($diff->removedColumns as $column) {
            if ($this->onSchemaAlterTableRemoveColumn($column, $diff, $columnSql)) {
                continue;
            }

            $queryParts[] =  'DROP ' . $column->getQuotedName($this);
        }

        foreach ($diff->changedColumns as $columnDiff) {
            if ($this->onSchemaAlterTableChangeColumn($columnDiff, $diff, $columnSql)) {
                continue;
            }

            /* @var $columnDiff Doctrine\DBAL\Schema\ColumnDiff */
            $column = $columnDiff->column;
            $columnArray = $column->toArray();
            $columnArray['comment'] = $this->getColumnComment($column);
            $queryParts[] =  'CHANGE ' . ($columnDiff->oldColumnName) . ' '
                    . $this->getColumnDeclarationSQL($column->getQuotedName($this), $columnArray);
        }

        foreach ($diff->renamedColumns as $oldColumnName => $column) {
            if ($this->onSchemaAlterTableRenameColumn($oldColumnName, $column, $diff, $columnSql)) {
                continue;
            }

            $columnArray = $column->toArray();
            $columnArray['comment'] = $this->getColumnComment($column);
            $queryParts[] =  'CHANGE ' . $oldColumnName . ' '
                    . $this->getColumnDeclarationSQL($column->getQuotedName($this), $columnArray);
        }

        $sql = array();
        $tableSql = array();

        if ( ! $this->onSchemaAlterTable($diff, $tableSql)) {
            if (count($queryParts) > 0) {
                $sql[] = 'ALTER TABLE ' . $diff->name . ' ' . implode(", ", $queryParts);
            }
            $sql = array_merge(
                $this->getPreAlterTableIndexForeignKeySQL($diff),
                $sql,
                $this->getPostAlterTableIndexForeignKeySQL($diff)
            );
        }

        return array_merge($sql, $tableSql, $columnSql);
    }

    public function getDropTemporaryTableSQL($table)
    {
        if ($table instanceof \Doctrine\DBAL\Schema\Table) {
            $table = $table->getQuotedName($this);
        } else if(!is_string($table)) {
            throw new \InvalidArgumentException('getDropTableSQL() expects $table parameter to be string or \Doctrine\DBAL\Schema\Table.');
        }

        return 'DROP TEMPORARY TABLE ' . $table;
    }

    public function convertBooleans($item)
    {
        if (is_array($item)) {
            foreach ($item as $key => $value) {
                if (is_bool($value) || is_numeric($item)) {
                    $item[$key] = ($value) ? 'true' : 'false';
                }
            }
        } else {
           if (is_bool($item) || is_numeric($item)) {
               $item = ($item) ? 'true' : 'false';
           }
        }
        return $item;
        return $item;
    }

    public function getLocateExpression($str, $substr, $startPos = false)
    {
        if ($startPos == false) {
            return 'LOCATE(' . $substr . ', ' . $str . ')';
        } else {
            return 'LOCATE(' . $substr . ', ' . $str . ', '.$startPos.')';
        }
    }

    public function getGuidExpression()
    {
        return 'UUID()';
    }

    public function getRegexpExpression()
    {
        return 'RLIKE';
    }
}
