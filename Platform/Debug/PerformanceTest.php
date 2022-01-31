<?php
namespace Platform\Debug;


class PerformanceTest {
    
    private $class = null;
    
    public function __construct(string $classname) {
        
        for ($i = 0; $i < $this->number_of_objects; $i++) {
            $object = new $class();
            $this->autoFill($object);
            $object->save();
        }
        
        \Platform\Utilities\Errorhandler::measure('Finished: Generate '.$this->number_of_objects.' objects.', $this->number_of_objects);
        
        \Platform\Utilities\Errorhandler::measure('Start: Read objects by filter.');
        $filter = new \Platform\Filter($class);
        $filter->addCondition(new \Platform\ConditionGreaterEqual($class::getKeyField(), $maxid));
        $collection = $filter->execute();
        \Platform\Utilities\Errorhandler::measure('Finished: Read objects by filter.', $this->number_of_objects);
        
        \Platform\Utilities\Errorhandler::measure('Start: Get all fields in visual mode.');
        foreach ($collection as $object) {
            foreach ($this->class_structure as $fieldname => $data) {
                $dummy = $object->getFullValue($fieldname);
            }
        }
        \Platform\Utilities\Errorhandler::measure('Finished: Get all fields in visual mode.', $this->number_of_objects);
        
        $collection = null;
        
        \Platform\Utilities\Errorhandler::measure('Start: Read objects by id one by one.');
        for ($i = $maxid; $i < $maxid + $this->number_of_objects; $i++) {
            $object = new $class();
            $object->loadForRead($i, false);
            $dummy = $object;
        }
        \Platform\Utilities\Errorhandler::measure('Finished: Read objects by id one by one.', $this->number_of_objects);
        
        $filter = new \Platform\Filter($class);
        $filter->addCondition(new \Platform\ConditionGreaterEqual($class::getKeyField(), $maxid));
        $collection = $filter->execute();
        \Platform\Utilities\Errorhandler::measure('Start: Delete objects by collection.');
        $collection->deleteAll();
        \Platform\Utilities\Errorhandler::measure('End: Delete objects by collection.', $this->number_of_objects);
        
        \Platform\Utilities\Errorhandler::measure('Test complete.');
        
        // Restore auto increment
        $class::query("ALTER TABLE ".$class::getDatabaseTable()." AUTO_INCREMENT = ".$maxid);
        
        \Platform\Utilities\Errorhandler::renderMeasures();
    }
    
    const string_content = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789                             ';
    
    private static function generateString($minlength, $maxlength) {
        $length = rand(0,$maxlength-$minlength)+$minlength;
        $str = '';
        for ($i = 0; $i < $length; $i++) {
            $str .= substr(self::string_content, rand(0,strlen(self::string_content)-1),1);
        }
        return $str;
    }
    
    private function getRandomReference(string $foreign_class) {
        if ($this->reference_map[$foreign_class]) {
            return $this->reference_map[$foreign_class][rand(0, count($this->reference_map[$foreign_class])-1)];
        }
        return 0;
    }
}