<?php
namespace app\commands;

use Yii;
use yii\console\Controller;
use yii\db\Query;
use app\helpers\ImportHelper;

/**
 * This command imports the user.json and loan.json to DB.
 */
class ImportController extends Controller
{
    /**
     * [[actionIndex()]] function to import data from JSON file into database tables.
     * @param string $model
     * @param string $file
     * @return
     */
    public function actionIndex($model='User', $file='users.json')
    {
        $className = 'app\\models\\'.$model;
        $modelInstance = new $className();

        $columnNames = $modelInstance->getTableSchema()->columnNames;
        $baseTable = $modelInstance->getTableSchema()->fullName;
        $tmpTable =  'temp_'.$modelInstance->getTableSchema()->fullName;

        ImportHelper::beforeImport($model);
        
        ImportHelper::streamInsert($model, $file, $tmpTable, $columnNames);
        
        $selectTmp = (new Query())->select($columnNames)->from($tmpTable);
        $updateColumns = true;
        $params = [];
        Yii::$app->db->createCommand()->upsert($baseTable, $selectTmp, $updateColumns, $params)->execute();
        
        ImportHelper::afterImport($tmpTable);
        
        return;
    }

    public function actionLoan($model='Loan', $file='loans.json')
    {
        if( isset($row['start_date']) )
        {
            $row['start_date'] = date('Y/m/d H:i:s', $row['start_date']);
        }
        if( isset($row['end_date']) )
        {
            $row['end_date'] = date('Y/m/d H:i:s', $row['end_date']);
        }
        if( isset($row['status']) )
        {
            $row['status'] = boolval(intval($row['status']));
        }
        return $row;
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
