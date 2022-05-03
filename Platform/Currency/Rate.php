<?php
namespace Platform\Currency;

class Rate extends \Platform\Datarecord {
    
    protected static $database_table = 'platform_currency_rates';
    protected static $delete_strategy = self::DELETE_STRATEGY_BLOCK;
    protected static $location = self::LOCATION_INSTANCE;
    
    protected static $depending_classes = [ ];
    protected static $referring_classes = [ ];

    protected static $structure = false;
    protected static $key_field = false;
    protected static $title_field = false;
    
    protected static function buildStructure() {
        $structure = array(
            'currency_rate_id' => array(
                'invisible' => true,
                'fieldtype' => self::FIELDTYPE_KEY
            ),
            'currency' => array(
                'invisible' => true,
                'fieldtype' => self::FIELDTYPE_TEXT,
                'key' => 'date'
            ),
            'date' => array(
                'invisible' => true,
                'fieldtype' => self::FIELDTYPE_DATE,
            ),
            'rate' => array(
                'invisible' => true,
                'fieldtype' => self::FIELDTYPE_FLOAT,
            )
        );
        self::addStructure($structure);
        parent::buildStructure();
    }
    
    /**
     * Get the exchange rate on a given date
     * @param string $currency Currency to get exchange rate for
     * @param \Platform\Utilities\Time $date Date to check
     * @return float The numeric exchange rate as it was on this date
     */
    public static function getRate(string $currency, \Platform\Utilities\Time $date) : float {
        $rate = self::getRateObject($currency, $date);
        return $rate->isInDatabase() ? $rate->rate : 100;
    }
    
    /**
     * Get the closest rate object on this date or before for the given currency
     * @param string $currency
     * @param \Platform\Utilities\Time $date
     * @return Rate
     */
    public static function getRateObject(string $currency, \Platform\Utilities\Time $date) : Rate {
        if (!Currency::isValidCurrency($currency)) trigger_error('Invalid currency '.$currency, E_USER_ERROR);
        $filter = new \Platform\Filter(get_called_class());
        $filter->addCondition(new \Platform\ConditionMatch('currency', $currency));
        $filter->addCondition(new \Platform\ConditionLesserEqual('date', $date->endOfDay()));
        $filter->setOrderColumn('date', false);
        $filter->setResultLimit(1);
        $rate = $filter->executeAndGetFirst();
        return $rate;
    }
    
    /**
     * Set the rate for a given currency. The time is considered as the date, so any other rate from the
     * same date will be overwritten
     * @param string $currency
     * @param \Platform\Utilities\Time $date
     * @param float $rate_value
     */
    public static function setRate(string $currency, \Platform\Utilities\Time $date, float $rate_value) {
        if (!Currency::isValidCurrency($currency)) trigger_error('Invalid currency '.$currency, E_USER_ERROR);
        $filter = new \Platform\Filter(get_called_class());
        $filter->addCondition(new \Platform\ConditionMatch('currency', $currency));
        $filter->addCondition(new \Platform\ConditionMatch('date', $date->startOfDay()));
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