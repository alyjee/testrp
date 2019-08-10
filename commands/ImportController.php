<?php
namespace app\commands;

use Yii;
use yii\console\Controller;
use \JsonMachine\JsonMachine;

/**
 * This command imports the user.json and loan.json to DB.
 */
class ImportController extends Controller
{
    public $message;

    public function options($actionID){
        return ['message'];
    }

    public function optionAliasess()
    {
        return ['m' => 'message'];
    }

    public function actionIndex($model='User', $file='users.json')
    {
        echo "Import started for $model model @ ".date('Y-m-d H:i:s')."\n";
        $class_name = 'app\\models\\'.$model;
        $modelInstance = new $class_name();

        $columnSchema = [];
        $columns = $modelInstance->getTableSchema()->columns;
        foreach($columns as $column) {
            $columnSchema[$column->name] = $column->type;
        }
        
        // Create a temporary table just like user
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
        Yii::$app->db->createCommand($rawSql)->execute();
        
        echo "Inserting data in temporary table...\n";
        // Read the json file with stream
        $users = JsonMachine::fromFile(Yii::$app->basePath.'/'.$file);
        $userData = [];
        foreach ($users as $id => $user) {
            if( $id < 10){
                $userData[] = $user;
                // call batch insert on this
            } else {
                // send a batch of 100 records for insertion
                $insertCount = Yii::$app->db->createCommand()
                    ->batchInsert(
                        $tmpTable, $modelInstance->getTableSchema()->columnNames, $userData
                    )
                    ->execute();
                // empty the userData array after insetion
                // and insert the current user in the array as a first element
                $userData = [];
                $userData[] = $user;
            }
        }
        // insert the remaining data if any
        if(count($userData) > 0)
        {
            $insertCount = Yii::$app->db->createCommand()
                ->batchInsert(
                    $tmpTable, $modelInstance->getTableSchema()->columnNames, $userData
                )
                ->execute();
        }
        echo "Data insertion in temp table is completed.\n";
        $commaSepratedColumnNames =  implode(', ', $modelInstance->getTableSchema()->columnNames);
        $rawSql = "INSERT INTO \"$baseTable\" ($commaSepratedColumnNames) SELECT temp.* FROM \"$tmpTable\" temp ON CONFLICT (\"id\") DO UPDATE SET first_name = excluded.first_name, last_name = excluded.last_name, email = excluded.email, personal_code = excluded.personal_code, phone = excluded.phone, active = excluded.active, dead = excluded.dead, lang = excluded.lang";

        echo "Inserting data in main table...\n";
        Yii::$app->db->createCommand($rawSql)->execute();
        echo "Data insertion in main table is completed.\n";

        Yii::$app->db->createCommand("DROP TABLE IF EXISTS $tmpTable")->execute();
        echo "Temporary table dropped.\n";
        echo "Task completed @ ".date('Y-m-d H:i:s')."\n";
        return;
    }

    public function actionGenerate($limit=5, $fileName='users.json')
    {
        $file = Yii::$app->basePath.'/'.$fileName;
        $fhandler = fopen($file, 'w');
        
        // empty the file
        fwrite($fhandler, NULL);

        // add start of array and a new line
        fwrite($fhandler, "[");
        fwrite($fhandler, "\n");

        fclose($fhandler);

        $fhandler = fopen($file, 'a');
        
        for ($i=1; $i <= $limit ; $i++) {
            $user = [
                "id" => $i,
                "first_name" => "$i-Ullalaa",
                "last_name" => "Ziip",
                "email" => "ulallaaa@mldllm.tt",
                "personal_code" => "49005025465",
                "phone" => "50170262",
                "active"=> "1",
                "dead" => "0",
                "lang" => "est"
            ];
            $userString = json_encode($user);
            fwrite($fhandler, $userString);
            if( $i == $limit )
            {
                fwrite($fhandler, "\n");
            } else
            {
                fwrite($fhandler, ",\n");
            }
        }
        
        fwrite($fhandler, "]");
        fwrite($fhandler, "\n");

        fclose($fhandler);

        echo "File Generated.\n";
    }
}
