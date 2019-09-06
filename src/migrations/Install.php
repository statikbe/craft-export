<?php
/**
 * Export plugin for Craft CMS 3.x
 *
 * Export elements from Craft
 *
 * @link      https://statik.be
 * @copyright Copyright (c) 2019 Statik
 */

namespace statikbe\export\migrations;

use statikbe\export\Export;

use Craft;
use craft\config\DbConfig;
use craft\db\Migration;
use statikbe\export\records\ExportRecord;

/**
 * Export Install Migration
 *
 * If your plugin needs to create any custom database tables when it gets installed,
 * create a migrations/ folder within your plugin folder, and save an Install.php file
 * within it using the following template:
 *
 * If you need to perform any additional actions on install/uninstall, override the
 * safeUp() and safeDown() methods.
 *
 * @author    Statik
 * @package   Export
 * @since     1.0.0
 */
class Install extends Migration
{

    public function safeUp()
    {
        $this->createTables();

        return true;
    }

    public function safeDown()
    {
        $this->removeTables();

        return true;
    }

    protected function createTables()
    {
        $this->createTable(ExportRecord::tableName(), [
            'id' => $this->primaryKey(),
            'name' => $this->string(255)->notNull()->defaultValue(''),
            'filename' => $this->string(255)->notNull()->defaultValue(''),
            'exportType' => $this->string()->notNull()->defaultValue(''),
            'elementType' => $this->string()->notNull()->defaultValue(''),
            'elementGroup' => $this->string()->notNull()->defaultValue(''),
            'siteId' => $this->integer()->notNull()->defaultValue('1'),
            'sortOrder' => $this->smallInteger()->unsigned(),
            'settings' => $this->string(10000)->defaultValue(''),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
    }

    protected function removeTables()
    {
        $this->dropTableIfExists(ExportRecord::tableName());
    }
}
