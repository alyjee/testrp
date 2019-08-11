<?php
namespace app\helpers;

use Yii;
use \JsonMachine\JsonMachine;

/**
 * ImportHelper is a helper class which includes methods related to Imports/Import Commands.
 *
 * @author Tahir Raza <tahirraza.se@gmail.com>
 * @since 2.0
 */
trait ImportHelper
{
    /**
     * [[streamInsert()]] function to fill table with JSON data via stream.
     * @param string $model
     * @param string $file
     * @param string $table
     * @param array $columnNames
     * @return bool
     */
    public function streamInsert($model, $file, $table, $columnNames)
    {
        // Read the json file with stream
        $rows = JsonMachine::fromFile(Yii::$app->basePath.'/'.$file);
        $userData = [];
        foreach ($rows as $id => $row) {
            $this->preProcess($model, $row);
            if( count($userData) < 500){
                $userData[] = $row;
            } else {
                // send a batch of 100 records for insertion
                $insertCount = Yii::$app->db->createCommand()
                    ->batchInsert(
                        $table, $columnNames, $userData
                    )
                    ->execute();
                // empty the userData array after insetion
                // and insert the current user in the array as a first element
                $userData = [];
                $userData[] = $row;
            }
        }
        
        // insert the remaining data if any
        if(count($userData) > 0)
        {
            $insertCount = Yii::$app->db->createCommand()
                ->batchInsert(
                    $table, $columnNames, $userData
                )
                ->execute();
        }
        return true;
    }

    /**
     * [[beforeImport()]] function to prepare databse for import, creates temp table.
     * @param string $model
     * @return bool
     */
    public function beforeImport($model)
    {
        $className = 'app\\models\\'.$model;
        $modelInstance = new $className();

        $columnSchema = [];
        $columns = $modelInstance->getTableSchema()->columns;
        foreach($columns as $column) {
            $columnSchema[$column->name] = $column->type;
        }
        
        // Create a temporary table just like passed model 'User' as default
        $baseTable = $modelInstance->getTableSchema()->fullName;
        $tmpTable =  'temp_'.$modelInstance->getTableSchema()->fullName;

        // DROP Table IF EXISTS
        // Overide the dropTable function to support IF EXISTS option
        // otherwise, it will throw an error becuase table is not existing on first time
        // Yii::$app->db->createCommand()->dropTable($tmpTable)->execute();
        Yii::$app->db->createCommand("DROP TABLE IF EXISTS $tmpTable")->execute();
        
        /*
         * TODO: Overide the function createTable 
         * in order to support UNLOGGED, IF NOT EXISTS options
         *
         */
        $rawSql = Yii::$app->db->createCommand()->createTable($tmpTable, $columnSchema)->getRawSql();
        $rawSql = str_replace('CREATE TABLE', 'CREATE UNLOGGED TABLE IF NOT EXISTS ', $rawSql);
        return Yii::$app->db->createCommand($rawSql)->execute();
    }

    /**
     * [[afterImport()]] function to cleanup database once import is done.
     * @param string $table
     * @return bool
     */
    public function afterImport($table)
    {
        return Yii::$app->db->createCommand("DROP TABLE IF EXISTS $table")->execute();
    }

    /**
     * [[preProcess()]] process data before insertion.
     * @param string $model
     * @param array $row
     * @return bool
     */
    public function preProcess($model, &$row)
    {
        switch ($model) {
            case 'Loan':
                $row['start_date'] = date('Y/m/d H:i:s', $row['start_date']);
                $row['end_date'] = date('Y/m/d H:i:s', $row['end_date']);
                $row['status'] = boolval(intval($row['status']));
                break;
            
            default:
                # code...
                break;
        }
        
        return true;
    }
}