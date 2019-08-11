<?php
namespace app\components;

use yii\validators\Validator;
use app\helpers\PersonalCodeHelper;
use Exception;

class PersonalCodeValidator extends Validator
{
    use PersonalCodeHelper;

    /**
     * Validates the personal code.
     * 
     * @param \app\models\User $model User model.
     * @param String $attribute Estonian Personal Identification Code.
     * @throws Exception
     * If validation of Personal Identification Code fails.
     */
    public function validateAttribute($model, $attribute)
    {
        $iskukood = $model->$attribute;
        try {
            if( !$this->isValidatedByRegex($iskukood) ) {
                throw new Exception($iskukood . ' has an invalid format!');
            }

            // Check if the birth date is a valid date. Leap years are taken into consideration.
            if (!checkdate($this->getMonthOfBirth($iskukood), $this->getDayOfBirth($iskukood), $this->getYearOfBirth($iskukood))) {
                throw new Exception($iskukood . ' has invalid birthdate!');
            }

        } catch (Exception $exception) {
            $this->addError($model, $attribute, $exception->getMessage());
        }
    }
    
}
