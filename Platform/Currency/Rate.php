<?php
namespace Platform\Currency;
/**
 * Datarecord class that stores currency rates, which can be used by the currency system
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=rate_class
 */

use Platform\Filter\ConditionLesserEqual;
use Platform\Filter\ConditionMatch;
use Platform\Datarecord\Datarecord;
use Platform\Filter\Filter;
use Platform\Utilities\Time;

class Rate extends Datarecord {
    
    protected static $database_table = 'platform_currency_rates';
    protected static $delete_strategy = self::DELETE_STRATEGY_BLOCK;
    protected static $location = self::LOCATION_INSTANCE;
    
    protected static $depending_classes = [ ];
    protected static $referring_classes = [ ];

    protected static $structure = false;
    protected static $key_field = false;
    protected static $title_field = false;
    
    protected static function buildStructure() {
        static::addStructure([
            new \Platform\Datarecord\KeyType('currency_rate_id'),
            new \Platform\Datarecord\TextType('currency', '', ['is_required' => true, 'is_invisible' => true, 'index' => true]),
            new \Platform\Datarecord\DateType('date', '', ['is_required' => true, 'is_invisible' => true, 'index' => true]),
            new \Platform\Datarecord\FloatType('rate', '', ['is_required' => true, 'is_invisible' => true]),
        ]);
        parent::buildStructure();
    }
    
    /**
     * Get the exchange rate on a given date
     * @param string $currency Currency to get exchange rate for
     * @param Time $date Date to check
     * @return float The numeric exchange rate as it was on this date
     */
    public static function getRate(string $currency, Time $date) : float {
        $rate = self::getRateObject($currency, $date);
        return $rate->isInDatabase() ? $rate->rate : 100;
    }
    
    /**
     * Get todays rate for a given currency
     * @param string $currency Currency to get exchange rate for
     * @return float The numeric exchange rate for today
     */
    public static function getRateForToday(string $currency) : float {
        return static::getRate($currency, Time::today());
    }
    
    /**
     * Get the reverse exchange rate on a given date
     * @param string $currency Currency to get exchange rate for
     * @param Time $date Date to check
     * @return float The numeric exchange rate as it was on this date
     */
    public static function getReverseRate(string $currency, Time $date) : float {
        return 10000.0/static::getRate($currency, $date);
    }
    
    /**
     * Get the reverse exchange rate for today
     * @param string $currency Currency to get exchange rate for
     * @return float The numeric reverse exchange rate for today
     */
    public static function getReverseRateForToday(string $currency) : float {
        return static::getReverseRate($currency, Time::today());
    }
    
    /**
     * Get the closest rate object on this date or before for the given currency
     * @param string $currency
     * @param Time $date
     * @return Rate
     */
    public static function getRateObject(string $currency, Time $date) : Rate {
        if (!Currency::isValidCurrency($currency)) trigger_error('Invalid currency '.$currency, E_USER_ERROR);
        $filter = new Filter(get_called_class());
        $filter->addCondition(new ConditionMatch('currency', $currency));
        $filter->addCondition(new ConditionLesserEqual('date', $date->endOfDay()));
        $filter->setOrderColumn('date', false);
        $filter->setResultLimit(1);
        $rate = $filter->executeAndGetFirst();
        return $rate;
    }
    
    /**
     * Set the rate for a given currency. The time is considered as the date, so any other rate from the
     * same date will be overwritten
     * @param string $currency
     * @param Time $date
     * @param float $rate_value
     */
    public static function setRate(string $currency, Time $date, float $rate_value) {
        if (!Currency::isValidCurrency($currency)) trigger_error('Invalid currency '.$currency, E_USER_ERROR);
        $filter = new Filter(get_called_class());
        $filter->addCondition(new ConditionMatch('currency', $currency));
        $filter->addCondition(new ConditionMatch('date', $date->startOfDay()));
        $filter->setOrderColumn('date', false);
        $filter->setResultLimit(1);
        $rate = $filter->executeAndGetFirst();
        $rate->reloadForWrite();
        $rate->currency = $currency;
        $rate->date = $date->startOfDay();
        $rate->rate = $rate_value;
        $rate->save();
    }
    
}