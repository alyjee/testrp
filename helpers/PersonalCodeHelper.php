<?php
namespace app\helpers;
use Exception;

/**
 * PersonalCodeHelper contains the generice functions for Personal Code (iskukood) manipulation.
 *
 * @author Tahir Raza <tahirraza.se@gmail.com>
 * @since 2.0
 */
trait PersonalCodeHelper {

    private static $CODE_REGEX = '/^[1-6](0\d{1}|[1-9]\d{1})(0[1-9]|1[012])(0[1-9]|[12]\d|3[01])\d{4}$/';
    
    /**
     * Performs regular expression check against Estonian Personal Identification Code.
     * Note that this does not perform full validation on dates(leap years), control number etc.
     * Use this only to pre-validate the format of the Personal Identification Code.
     * 
     * 
     * @param type $code Estonian Personal Identification Code.
     * @return bool True if Estonian Personal Identification Code matches the 
     * regular expression, false otherwise.
     */
    public function isValidatedByRegex($code): bool 
    {
        return is_string($code) && preg_match(self::$CODE_REGEX, $code);
    }

    /**
     * Get the person's date of birth as PHP DateTime object by Personal Identification Code.
     * 
     * @param type $code Estonian Personal Identification Code.
     * @return \DateTime The date of birth as PHP DateTime object.
     * @throws Exception
     * If validation of Personal Identification Code fails.
     */
    public function getBirthDateAsDatetimeObj($code): \DateTime 
    {
        if (!$this->isValidatedByRegex($code)) {
            throw new Exception('Invalid Personal Identification Code format!');
        }
        $dateOfBirth = sprintf(
                '%d.%d.%d', $this->getMonthOfBirth($code), $this->getDayOfBirth($code), $this->getYearOfBirth($code)
        );
        return \Datetime::createFromFormat('m.d.Y', $dateOfBirth);
    }

    /**
     * Get the person's current age as PHP DateInterval object.
     * Note that DateInterval calculation precision depends on timezone set in php.ini.
     * 
     * @param type $code Estonian Personal Identification Code.
     * @return \DateInterval The current age as PHP DateInterval object.
     * @throws Exception
     * If validation of Personal Identification Code fails.
     */
    public function getCurrentAgeByCode($code): \DateInterval {
        if (!$this->isValidatedByRegex($code)) {
            throw new Exception('Invalid Personal Identification Code format!');
        }
        $birthDay = $this->getBirthDateAsDatetimeObj($code);
        $now = new \Datetime('now');
        return $now->diff($birthDay);
    }

    /**
     * Get the person's date of birth as PHP DateTime object by Personal Identification Code.
     * 
     * @param type $code Estonian Personal Identification Code.
     * @return \DateTime The date of birth as PHP DateTime object.
     * @throws Exception
     * If validation of Personal Identification Code fails.
     */
    public function getCurrentAgeInYearsByCode($code): int {
        if (!$this->isValidatedByRegex($code)) {
            throw new Exception('Invalid Personal Identification Code format!');
        }
        return $this->getCurrentAgeByCode($code)->y;
    }
    
    /**
     * Get the century of the persons birth date by Personal Identification Code.
     * 
     * @param string $code Estonian Personal Identification Code.
     * @return int The century when the person was born eg. 1900, 2000 etc
     * @throws \Lkallas\Exceptions\Exception 
     * If validation of Personal Identification Code fails.
     */
    public function getBirthCentury($code): int 
    {
        if (!$this->isValidatedByRegex($code)) {
            throw new Exception('Invalid Personal Identification Code format!');
        }
        $century = 0;
        $centuryIdentificator = (int) substr($code, 0, 1);
        if ($centuryIdentificator < 3) {
            $century = 1800;
        } elseif ($centuryIdentificator > 2 && $centuryIdentificator < 5) {
            $century = 1900;
        } elseif ($centuryIdentificator > 4 && $centuryIdentificator < 7) {
            $century = 2000;
        }
        return $century;
    }

     /**
     * Get the person's year of birth by Personal Identification Code.
     * 
     * @param string $code Estonian Personal Identification Code.
     * @return int The year when the person was born.
     * @throws Exception 
     * If validation of Personal Identification Code fails.
     */
    public function getYearOfBirth($code): int 
    {
        if (!$this->isValidatedByRegex($code)) {
            throw new Exception('Invalid Personal Identification Code format!');
        }
        $year = (int) ltrim(substr($code, 1, 2), '0');
        $century = $this->getBirthCentury($code);
        return $year + $century;
    }
    
    /**
     * Get the person's month of birth by Personal Identification Code.
     * 
     * @param type $code Estonian Personal Identification Code.
     * @return int The month when the person was born.
     * @throws Exception
     * If validation of Personal Identification Code fails.
     */
    public function getMonthOfBirth($code): int 
    {
        if (!$this->isValidatedByRegex($code)) {
            throw new Exception('Invalid Personal Identification Code format!');
        }
        return (int) ltrim(substr($code, 3, 2), '0');
    }

    /**
     * Get the person's day of birth by Estonian Personal Identification Code.
     * 
     * @param type $code Estonian Personal Identification Code.
     * @return int The day when the person was born.
     * @throws Exception
     * If validation of Personal Identification Code fails.
     */
    public function getDayOfBirth($code): int 
    {
        if (!$this->isValidatedByRegex($code)) {
            throw new Exception('Invalid Personal Identification Code format!');
        }
        return (int) ltrim(substr($code, 5, 2), '0');
    }
}