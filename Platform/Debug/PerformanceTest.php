<?php
namespace Platform\Debug;
/**
 * Class that can be used to make a performance test on a given Datarecord object
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=performancetest_class
 */

use Platform\Filter\ConditionGreaterEqual;
use Platform\Datarecord\Datarecord;
use Platform\Filter\Filter;
use Platform\Utilities\Errorhandler;
use Platform\Utilities\Time;

class PerformanceTest {
    
    private static $reference_map = [];
    
    public function __construct(string $classname, $number_of_objects) {
        
        $class_structure = $classname::getStructure();
        
        $maxid = 0;
        
        Errorhandler::measure('Start Generate '.$number_of_objects);
        
        for ($i = 0; $i < $number_of_objects; $i++) {
            $object = new $classname();
            self::autoFill($object);
            $object->save();
            if (! $maxid) $maxid = $object->getKeyValue();
        }
        
        Errorhandler::measure('Finished: Generate '.$number_of_objects.' objects.', $number_of_objects);
        
        Errorhandler::measure('Start: Read objects by filter.');
        $filter = new Filter($classname);
        $filter->addCondition(new ConditionGreaterEqual($classname::getKeyField(), $maxid));
        $collection = $filter->execute();
        Errorhandler::measure('Finished: Read objects by filter.', $number_of_objects);
        
        foreach ($class_structure as $fieldname => $data) {
            Errorhandler::measure('Start: Get field '.$fieldname.' in in visual mode.');
            foreach ($collection as $object) {
                $dummy = $object->getFullValue($fieldname);
            }
            Errorhandler::measure('Finished: Get field '.$fieldname.' in in visual mode.', $number_of_objects);
        }
        
        $collection = null;
        
        Errorhandler::measure('Start: Read objects by id one by one.');
        for ($i = $maxid; $i < $maxid + $number_of_objects; $i++) {
            $object = new $classname();
            $object->loadForRead($i, false);
            $dummy = $object;
        }
        Errorhandler::measure('Finished: Read objects by id one by one.', $number_of_objects);

        $filter = new Filter($classname);
        $filter->addCondition(new ConditionGreaterEqual($classname::getKeyField(), $maxid));
        $collection = $filter->execute();
        Errorhandler::measure('Start: Delete objects by collection.');
        $collection->deleteAll();
        Errorhandler::measure('End: Delete objects by collection.', $number_of_objects);

        Errorhandler::measure('Test complete.');
        
        // Restore auto increment
        $classname::query("ALTER TABLE ".$classname::getDatabaseTable()." AUTO_INCREMENT = ".$maxid);
        
        Errorhandler::renderMeasures();
    }
    
    const string_content = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789                             ';
    const string_content_nospaces = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    
    private static function generateString($minlength, $maxlength, $nospaces = false) {
        $length = rand(0,$maxlength-$minlength)+$minlength;
        $str = '';
        $const = $nospaces ? self::string_content_nospaces : self::string_content;
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($const, rand(0,strlen($const)-1),1);
        }
        return $str;
    }
    
    private static function getRandomReference(string $foreign_class) {
        if (! self::$reference_map[$foreign_class]) {
            $filter = new Filter($foreign_class);
            $collection = $filter->execute();
            self::$reference_map[$foreign_class] = $collection->getAllRawValues($foreign_class::getKeyField());
        }
        return self::$reference_map[$foreign_class][rand(0, count(self::$reference_map[$foreign_class])-1)];
    }
    
    public static function autoFill($object) {
        foreach ($object->getStructure() as $key => $definition) {
            if ($definition['subfield']) continue;
            switch ($definition['fieldtype']) {
                case Datarecord::FIELDTYPE_TEXT:
                    $object->setValue($key, self::generateString(5, 125));
                    break;
                case Datarecord::FIELDTYPE_INTEGER:
                    $object->setValue($key, rand());
                    break;
                case Datarecord::FIELDTYPE_FLOAT:
                    $object->setValue($key, rand()/rand(1,1000));
                    break;
                case Datarecord::FIELDTYPE_BOOLEAN:
                    $object->setValue($key, rand(0,1));
                    break;
                case Datarecord::FIELDTYPE_BIGTEXT:
                    $object->setValue($key, self::generateString(1024, 4096));
                    break;
                case Datarecord::FIELDTYPE_HTMLTEXT:
                    $object->setValue($key, self::generateString(1024, 4096));
                    break;
                case Datarecord::FIELDTYPE_DATETIME:
                    $time = new Time('now');
                    $time = $time->addDays(-rand(0,10*365));
                    $time = $time->add(-rand(0,60*60*24));
                    $object->setValue($key, $time);
                    break;
                case Datarecord::FIELDTYPE_DATE:
                    $time = new Time('now');
                    $time = $time->addDays(-rand(0,10*365));
                    $object->setValue($key, $time);
                    break;
                case Datarecord::FIELDTYPE_CURRENCY:
                    break;
                case Datarecord::FIELDTYPE_EMAIL:
                    $object->setValue($key, self::generateString(2, 15, true).'@'.self::generateString(5, 20, true).'.'.self::generateString(2, 3, true));
                    break;
                case Datarecord::FIELDTYPE_ARRAY:
                    $j = rand(10,50);
                    $result = [];
                    for ($i = 0; $i < $j; $i++) {
                        $result[] = rand(1,10000);
                    }
                    $object->setValue($key, $result);
                    break;
                case Datarecord::FIELDTYPE_OBJECT:
                    $j = rand(10,50);
                    $result = [];
                    for ($i = 0; $i < $j; $i++) {
                        $result[] = self::generateString(10, 40);
                    }
                    $object->setValue($key, $result);
                    break;
                case Datarecord::FIELDTYPE_ENUMERATION:
                    $enums = array_keys($definition['enumeration']);
                    shuffle($enums);
                    $object->setValue($key, current($enums));
                    break;
                case Datarecord::FIELDTYPE_ENUMERATION_MULTI:
                    $enums = array_keys($definition['enumeration']);
                    shuffle($enums);
                    $object->setValue($key, array_slice($enums,0, min(rand(2,10), count($enums))));
                    break;
                case Datarecord::FIELDTYPE_PASSWORD:
                    $object->setValue($key, self::generateString(8, 25));
                    break;
                case Datarecord::FIELDTYPE_FILE:
                case Datarecord::FIELDTYPE_IMAGE:
                    break;
                case Datarecord::FIELDTYPE_REFERENCE_SINGLE:
                    $object->setValue($key, self::getRandomReference($definition['foreign_class']));
                    break;
                case Datarecord::FIELDTYPE_REFERENCE_MULTIPLE:
                    $j = rand(2,20);
                    $result = [];
                    for ($i = 0; $i < $j; $i++) {
                        $result[] = self::getRandomReference($definition['foreign_class']);
                    }
                    $object->setValue($key, $result);
                    break;
                case Datarecord::FIELDTYPE_REFERENCE_HYPER:
                    break;
            }
        }
    }
}