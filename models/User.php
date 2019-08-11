<?php

namespace app\models;

use app\components\PersonalCodeValidator;
use app\helpers\PersonalCodeHelper;
use Yii;

/**
 * This is the model class for table "users".
 *
 * @property int $id
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property int $personal_code
 * @property int $phone
 * @property bool $active
 * @property bool $dead
 * @property string $lang
 *
 * @property Loan[] $loans
 */
class User extends \yii\db\ActiveRecord
{
    use PersonalCodeHelper;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['first_name', 'last_name', 'email', 'personal_code', 'phone'], 'required'],
            [['first_name', 'last_name', 'lang'], 'string'],
            [['email'], 'email'],
            [['personal_code', 'phone'], 'integer'],
            [['active', 'dead'], 'boolean'],
            [['personal_code'], PersonalCodeValidator::class],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'first_name' => 'First Name',
            'last_name' => 'Last Name',
            'email' => 'Email',
            'personal_code' => 'Personal Code',
            'phone' => 'Phone',
            'active' => 'Active',
            'dead' => 'Dead',
            'lang' => 'lang',
        ];
    }

    /**
     * Virtual Attribute - Getter for age
     *
     * @return int
     */
    public function getAge()
    {
        $code = strval($this->personal_code);
        return $this->getCurrentAgeInYearsByCode($code);
    }

    /**
     * Virtual Attribute - Getter for birthdate
     *
     * @return \DateTime
     */
    public function getBirthDate()
    {
        $code = strval($this->personal_code);
        return $this->getBirthDateAsDatetimeObj($code);
    }

    /**
     * Check if this user is allow to apply for loan
     *
     * @return bool
     */
    public function isAllowedToApplyLoan()
    {
        return $this->age >= 18;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getLoans()
    {
        return $this->hasMany(Loan::class, ['userId' => 'id']);
    }

    public function getFullName()
    {
        return sprintf('%s %s', $this->first_name, $this->last_name);
    }
}
