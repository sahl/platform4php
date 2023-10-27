<?php
namespace Platform\Form;
/**
 * Field for inputting addresses
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=field_class
 */

use Platform\Datarecord;
use Platform\Utilities\Translation;

class AddressField extends Field {
    
    public static $component_class = 'platform_component_address_field';
    
    /**
     * List of countries
     * @var array
     */
    public static $countries = [];

    private static $sorted = false; // if the countries have been sorted
    
    
    public function __construct() {
        parent::__construct();
    
        if (!self::$sorted) {
            $sort = [];
            foreach (self::$countries as $country)
                $sort[] = $country['name'];
            array_multisort($sort, self::$countries);
            self::$sorted = true;
        }
        
    }
    
    public static function Field(string $label, string $name, array $options = []): Field {
        return parent::Field($label, $name, $options);
    }
    
    /**
     * Format an address, typical it will look like "<div><div>Someroad 3</div><div>2100 Copenhagen, DK</div></div>"
     * @param Datarecord $object
     * @param string $field
     * @return string
     */
    public static function formatAddress(Datarecord $object, string $field) : string {
        $lines = [];
        if ($object->getRawValue($field.'_address'))
            $lines[] = '<div>'.$object->getRawValue($field.'_address').'</div>';
        if ($object->getRawValue($field.'_address'))
            $lines[] = '<div>'.$object->getRawValue($field.'_address2').'</div>';
        
        $lastline = '';
        if ($object->getRawValue($field.'_zip') || $object->getRawValue($field.'_city') || $object->getRawValue($field.'_countrycode')) {
            if ($object->getRawValue($field.'_zip'))
                $lastline .= $object->getRawValue($field.'_zip');
            if ($object->getRawValue($field.'_city')) {
                if ($lastline)   $lastline .= ' ';
                $lastline .= $object->getRawValue($field.'_city');
            }
            if ($object->getRawValue($field.'_countrycode')) {
                if ($lastline)   $lastline .= ', ';
                $lastline .= $object->getRawValue($field.'_countrycode');
            }
        }
        if ($lastline)
           $lines[] = $lastline;
        return '<div class="formattedaddress">'.implode('', $lines).'</div>';
    }
    
    public function parse($value) : bool {
        if (! is_array($value)) {
            $this->triggerError('Invalid input');
            return false;
        }
        $this->value = $value;
        return true;
    }
    
    public function renderInput() {
        $id = $this->getFieldIdForHTML();
        $classes = $this->getFieldClasses();
        $style = 'style="max-width: '.$this->field_width.';"';
        echo '<input '.$style.' id="'.$id.'" class="'.$classes.' address_address" placeholder="'. Translation::translateForUser('Address').'" type="text" name="'.$this->name.'[address]" value="'.htmlentities($this->value['address'], ENT_QUOTES).'"> ';
        echo '<input '.$style.' id="'.$id.'" class="'.$classes.' address_address2" placeholder="'. Translation::translateForUser('Address 2').'" type="text" name="'.$this->name.'[address2]" value="'.htmlentities($this->value['address2'], ENT_QUOTES).'"> ';
        echo '<input '.$style.' id="'.$id.'" class="'.$classes.' address_zip" placeholder="'. Translation::translateForUser('Zip').'" type="text" name="'.$this->name.'[zip]" value="'.htmlentities($this->value['zip'], ENT_QUOTES).'"> ';
        echo '<input '.$style.' id="'.$id.'" class="'.$classes.' address_city" placeholder="'. Translation::translateForUser('City').'"  type="text" name="'.$this->name.'[city]" value="'.htmlentities($this->value['city'], ENT_QUOTES).'"> ';
        echo '<select '.$style.' id="'.$id.'" class="'.$classes.' address_countrycode" name="'.$this->name.'[countrycode]" >';
        echo   '<option value="">';
        foreach (self::$countries as $country) {
            echo '<option value="'.$country['code'].'"';
            if ($country['code'] == $this->value['countrycode'])   echo ' selected';
            echo '>'.$country['name'];
        }
        echo '</select>';

    }
}

AddressField::$countries = [
    'AF' => [ 'iso' => 4, 'code' => 'AF', 'code3' => 'AFG', 'name' => Translation::translateForUser('Afghanistan') ],
    'AL' => [ 'iso' => 8, 'code' => 'AL', 'code3' => 'ALB', 'name' => Translation::translateForUser('Albania') ],
    'DZ' => [ 'iso' => 12, 'code' => 'DZ', 'code3' => 'DZA', 'name' => Translation::translateForUser('Algeria') ],
    'AS' => [ 'iso' => 16, 'code' => 'AS', 'code3' => 'ASM', 'name' => Translation::translateForUser('American Samoa') ],
    'AD' => [ 'iso' => 20, 'code' => 'AD', 'code3' => 'AND', 'name' => Translation::translateForUser('Andorra') ],
    'AO' => [ 'iso' => 24, 'code' => 'AO', 'code3' => 'AGO', 'name' => Translation::translateForUser('Angola') ],
    'AI' => [ 'iso' => 660, 'code' => 'AI', 'code3' => 'AIA', 'name' => Translation::translateForUser('Anguilla') ],
    'AQ' => [ 'iso' => 10, 'code' => 'AQ', 'code3' => 'ATA', 'name' => Translation::translateForUser('Antarctica') ],
    'AG' => [ 'iso' => 28, 'code' => 'AG', 'code3' => 'ATG', 'name' => Translation::translateForUser('Antigua And Barbuda') ],
    'AR' => [ 'iso' => 32, 'code' => 'AR', 'code3' => 'ARG', 'name' => Translation::translateForUser('Argentina') ],
    'AM' => [ 'iso' => 51, 'code' => 'AM', 'code3' => 'ARM', 'name' => Translation::translateForUser('Armenia') ],
    'AW' => [ 'iso' => 533, 'code' => 'AW', 'code3' => 'ABW', 'name' => Translation::translateForUser('Aruba') ],
    'AU' => [ 'iso' => 36, 'code' => 'AU', 'code3' => 'AUS', 'name' => Translation::translateForUser('Australia') ],
    'AT' => [ 'iso' => 40, 'code' => 'AT', 'code3' => 'AUT', 'name' => Translation::translateForUser('Austria') ],
    'AZ' => [ 'iso' => 31, 'code' => 'AZ', 'code3' => 'AZE', 'name' => Translation::translateForUser('Azerbaijan') ],
    'BS' => [ 'iso' => 44, 'code' => 'BS', 'code3' => 'BHS', 'name' => Translation::translateForUser('Bahamas') ],
    'BH' => [ 'iso' => 48, 'code' => 'BH', 'code3' => 'BHR', 'name' => Translation::translateForUser('Bahrain') ],
    'BD' => [ 'iso' => 50, 'code' => 'BD', 'code3' => 'BGD', 'name' => Translation::translateForUser('Bangladesh') ],
    'BB' => [ 'iso' => 52, 'code' => 'BB', 'code3' => 'BRB', 'name' => Translation::translateForUser('Barbados') ],
    'BY' => [ 'iso' => 112, 'code' => 'BY', 'code3' => 'BLR', 'name' => Translation::translateForUser('Belarus') ],
    'BE' => [ 'iso' => 56, 'code' => 'BE', 'code3' => 'BEL', 'name' => Translation::translateForUser('Belgium') ],
    'BZ' => [ 'iso' => 84, 'code' => 'BZ', 'code3' => 'BLZ', 'name' => Translation::translateForUser('Belize') ],
    'BJ' => [ 'iso' => 204, 'code' => 'BJ', 'code3' => 'BEN', 'name' => Translation::translateForUser('Benin') ],
    'BM' => [ 'iso' => 60, 'code' => 'BM', 'code3' => 'BMU', 'name' => Translation::translateForUser('Bermuda') ],
    'BT' => [ 'iso' => 64, 'code' => 'BT', 'code3' => 'BTN', 'name' => Translation::translateForUser('Bhutan') ],
    'BO' => [ 'iso' => 68, 'code' => 'BO', 'code3' => 'BOL', 'name' => Translation::translateForUser('Bolivia') ],
    'BA' => [ 'iso' => 70, 'code' => 'BA', 'code3' => 'BIH', 'name' => Translation::translateForUser('Bosnia And Herzegowina') ],
    'BW' => [ 'iso' => 72, 'code' => 'BW', 'code3' => 'BWA', 'name' => Translation::translateForUser('Botswana') ],
    'BV' => [ 'iso' => 74, 'code' => 'BV', 'code3' => 'BVT', 'name' => Translation::translateForUser('Bouvet Island') ],
    'BR' => [ 'iso' => 76, 'code' => 'BR', 'code3' => 'BRA', 'name' => Translation::translateForUser('Brazil') ],
    'IO' => [ 'iso' => 86, 'code' => 'IO', 'code3' => 'IOT', 'name' => Translation::translateForUser('British Indian Ocean Territory') ],
    'BN' => [ 'iso' => 96, 'code' => 'BN', 'code3' => 'BRN', 'name' => Translation::translateForUser('Brunei Darussalam') ],
    'BG' => [ 'iso' => 100, 'code' => 'BG', 'code3' => 'BGR', 'name' => Translation::translateForUser('Bulgaria') ],
    'BF' => [ 'iso' => 854, 'code' => 'BF', 'code3' => 'BFA', 'name' => Translation::translateForUser('Burkina Faso') ],
    'BI' => [ 'iso' => 108, 'code' => 'BI', 'code3' => 'BDI', 'name' => Translation::translateForUser('Burundi') ],
    'KH' => [ 'iso' => 116, 'code' => 'KH', 'code3' => 'KHM', 'name' => Translation::translateForUser('Cambodia') ],
    'CM' => [ 'iso' => 120, 'code' => 'CM', 'code3' => 'CMR', 'name' => Translation::translateForUser('Cameroon') ],
    'CA' => [ 'iso' => 124, 'code' => 'CA', 'code3' => 'CAN', 'name' => Translation::translateForUser('Canada') ],
    'CV' => [ 'iso' => 132, 'code' => 'CV', 'code3' => 'CPV', 'name' => Translation::translateForUser('Cape Verde') ],
    'KY' => [ 'iso' => 136, 'code' => 'KY', 'code3' => 'CYM', 'name' => Translation::translateForUser('Cayman Islands') ],
    'CF' => [ 'iso' => 140, 'code' => 'CF', 'code3' => 'CAF', 'name' => Translation::translateForUser('Central African Republic') ],
    'TD' => [ 'iso' => 148, 'code' => 'TD', 'code3' => 'TCD', 'name' => Translation::translateForUser('Chad') ],
    'CL' => [ 'iso' => 152, 'code' => 'CL', 'code3' => 'CHL', 'name' => Translation::translateForUser('Chile') ],
    'CN' => [ 'iso' => 156, 'code' => 'CN', 'code3' => 'CHN', 'name' => Translation::translateForUser('China') ],
    'CX' => [ 'iso' => 162, 'code' => 'CX', 'code3' => 'CXR', 'name' => Translation::translateForUser('Christmas Island') ],
    'CC' => [ 'iso' => 166, 'code' => 'CC', 'code3' => 'CCK', 'name' => Translation::translateForUser('Cocos Islands') ],
    'CO' => [ 'iso' => 170, 'code' => 'CO', 'code3' => 'COL', 'name' => Translation::translateForUser('Colombia') ],
    'KM' => [ 'iso' => 174, 'code' => 'KM', 'code3' => 'COM', 'name' => Translation::translateForUser('Comoros') ],
    'CG' => [ 'iso' => 178, 'code' => 'CG', 'code3' => 'COG', 'name' => Translation::translateForUser('Congo') ],
    'CK' => [ 'iso' => 184, 'code' => 'CK', 'code3' => 'COK', 'name' => Translation::translateForUser('Cook Islands') ],
    'CR' => [ 'iso' => 188, 'code' => 'CR', 'code3' => 'CRI', 'name' => Translation::translateForUser('Costa Rica') ],
    'CI' => [ 'iso' => 384, 'code' => 'CI', 'code3' => 'CIV', 'name' => Translation::translateForUser('Cote D\'ivoire') ],
    'HR' => [ 'iso' => 191, 'code' => 'HR', 'code3' => 'HRV', 'name' => Translation::translateForUser('Croatia') ],
    'CU' => [ 'iso' => 192, 'code' => 'CU', 'code3' => 'CUB', 'name' => Translation::translateForUser('Cuba') ],
    'CY' => [ 'iso' => 196, 'code' => 'CY', 'code3' => 'CYP', 'name' => Translation::translateForUser('Cyprus') ],
    'CZ' => [ 'iso' => 203, 'code' => 'CZ', 'code3' => 'CZE', 'name' => Translation::translateForUser('Czech Republic') ],
    'DK' => [ 'iso' => 208, 'code' => 'DK', 'code3' => 'DNK', 'name' => Translation::translateForUser('Denmark') ],
    'DJ' => [ 'iso' => 262, 'code' => 'DJ', 'code3' => 'DJI', 'name' => Translation::translateForUser('Djibouti') ],
    'DM' => [ 'iso' => 212, 'code' => 'DM', 'code3' => 'DMA', 'name' => Translation::translateForUser('Dominica') ],
    'DO' => [ 'iso' => 214, 'code' => 'DO', 'code3' => 'DOM', 'name' => Translation::translateForUser('Dominican Republic') ],
    'TP' => [ 'iso' => 626, 'code' => 'TP', 'code3' => 'TMP', 'name' => Translation::translateForUser('East Timor') ],
    'EC' => [ 'iso' => 218, 'code' => 'EC', 'code3' => 'ECU', 'name' => Translation::translateForUser('Ecuador') ],
    'EG' => [ 'iso' => 818, 'code' => 'EG', 'code3' => 'EGY', 'name' => Translation::translateForUser('Egypt') ],
    'SV' => [ 'iso' => 222, 'code' => 'SV', 'code3' => 'SLV', 'name' => Translation::translateForUser('El Salvador') ],
    'GQ' => [ 'iso' => 226, 'code' => 'GQ', 'code3' => 'GNQ', 'name' => Translation::translateForUser('Equatorial Guinea') ],
    'ER' => [ 'iso' => 232, 'code' => 'ER', 'code3' => 'ERI', 'name' => Translation::translateForUser('Eritrea') ],
    'EE' => [ 'iso' => 233, 'code' => 'EE', 'code3' => 'EST', 'name' => Translation::translateForUser('Estonia') ],
    'ET' => [ 'iso' => 231, 'code' => 'ET', 'code3' => 'ETH', 'name' => Translation::translateForUser('Ethiopia') ],
    'FK' => [ 'iso' => 238, 'code' => 'FK', 'code3' => 'FLK', 'name' => Translation::translateForUser('Falkland Islands') ],
    'FO' => [ 'iso' => 234, 'code' => 'FO', 'code3' => 'FRO', 'name' => Translation::translateForUser('Faroe Islands') ],
    'FJ' => [ 'iso' => 242, 'code' => 'FJ', 'code3' => 'FJI', 'name' => Translation::translateForUser('Fiji') ],
    'FI' => [ 'iso' => 246, 'code' => 'FI', 'code3' => 'FIN', 'name' => Translation::translateForUser('Finland') ],
    'FR' => [ 'iso' => 250, 'code' => 'FR', 'code3' => 'FRA', 'name' => Translation::translateForUser('France') ],
    'GF' => [ 'iso' => 254, 'code' => 'GF', 'code3' => 'GUF', 'name' => Translation::translateForUser('French Guiana') ],
    'PF' => [ 'iso' => 258, 'code' => 'PF', 'code3' => 'PYF', 'name' => Translation::translateForUser('French Polynesia') ],
    'TF' => [ 'iso' => 260, 'code' => 'TF', 'code3' => 'ATF', 'name' => Translation::translateForUser('French Southern Territories') ],
    'GA' => [ 'iso' => 266, 'code' => 'GA', 'code3' => 'GAB', 'name' => Translation::translateForUser('Gabon') ],
    'GM' => [ 'iso' => 270, 'code' => 'GM', 'code3' => 'GMB', 'name' => Translation::translateForUser('Gambia') ],
    'GE' => [ 'iso' => 268, 'code' => 'GE', 'code3' => 'GEO', 'name' => Translation::translateForUser('Georgia') ],
    'DE' => [ 'iso' => 276, 'code' => 'DE', 'code3' => 'DEU', 'name' => Translation::translateForUser('Germany') ],
    'GH' => [ 'iso' => 288, 'code' => 'GH', 'code3' => 'GHA', 'name' => Translation::translateForUser('Ghana') ],
    'GI' => [ 'iso' => 292, 'code' => 'GI', 'code3' => 'GIB', 'name' => Translation::translateForUser('Gibraltar') ],
    'GR' => [ 'iso' => 300, 'code' => 'GR', 'code3' => 'GRC', 'name' => Translation::translateForUser('Greece') ],
    'GL' => [ 'iso' => 304, 'code' => 'GL', 'code3' => 'GRL', 'name' => Translation::translateForUser('Greenland') ],
    'GD' => [ 'iso' => 308, 'code' => 'GD', 'code3' => 'GRD', 'name' => Translation::translateForUser('Grenada') ],
    'GP' => [ 'iso' => 312, 'code' => 'GP', 'code3' => 'GLP', 'name' => Translation::translateForUser('Guadeloupe') ],
    'GU' => [ 'iso' => 316, 'code' => 'GU', 'code3' => 'GUM', 'name' => Translation::translateForUser('Guam') ],
    'GT' => [ 'iso' => 320, 'code' => 'GT', 'code3' => 'GTM', 'name' => Translation::translateForUser('Guatemala') ],
    'GN' => [ 'iso' => 324, 'code' => 'GN', 'code3' => 'GIN', 'name' => Translation::translateForUser('Guinea') ],
    'GW' => [ 'iso' => 624, 'code' => 'GW', 'code3' => 'GNB', 'name' => Translation::translateForUser('Guinea-bissau') ],
    'GY' => [ 'iso' => 328, 'code' => 'GY', 'code3' => 'GUY', 'name' => Translation::translateForUser('Guyana') ],
    'HT' => [ 'iso' => 332, 'code' => 'HT', 'code3' => 'HTI', 'name' => Translation::translateForUser('Haiti') ],
    'HM' => [ 'iso' => 334, 'code' => 'HM', 'code3' => 'HMD', 'name' => Translation::translateForUser('Heard And Mc Donald Islands') ],
    'HN' => [ 'iso' => 340, 'code' => 'HN', 'code3' => 'HND', 'name' => Translation::translateForUser('Honduras') ],
    'HK' => [ 'iso' => 344, 'code' => 'HK', 'code3' => 'HKG', 'name' => Translation::translateForUser('Hong Kong') ],
    'HU' => [ 'iso' => 348, 'code' => 'HU', 'code3' => 'HUN', 'name' => Translation::translateForUser('Hungary') ],
    'IS' => [ 'iso' => 352, 'code' => 'IS', 'code3' => 'ISL', 'name' => Translation::translateForUser('Iceland') ],
    'IN' => [ 'iso' => 356, 'code' => 'IN', 'code3' => 'IND', 'name' => Translation::translateForUser('India') ],
    'ID' => [ 'iso' => 360, 'code' => 'ID', 'code3' => 'IDN', 'name' => Translation::translateForUser('Indonesia') ],
    'IR' => [ 'iso' => 364, 'code' => 'IR', 'code3' => 'IRN', 'name' => Translation::translateForUser('Iran') ],
    'IQ' => [ 'iso' => 368, 'code' => 'IQ', 'code3' => 'IRQ', 'name' => Translation::translateForUser('Iraq') ],
    'IE' => [ 'iso' => 372, 'code' => 'IE', 'code3' => 'IRL', 'name' => Translation::translateForUser('Ireland') ],
    'IL' => [ 'iso' => 376, 'code' => 'IL', 'code3' => 'ISR', 'name' => Translation::translateForUser('Israel') ],
    'IT' => [ 'iso' => 380, 'code' => 'IT', 'code3' => 'ITA', 'name' => Translation::translateForUser('Italy') ],
    'JM' => [ 'iso' => 388, 'code' => 'JM', 'code3' => 'JAM', 'name' => Translation::translateForUser('Jamaica') ],
    'JP' => [ 'iso' => 392, 'code' => 'JP', 'code3' => 'JPN', 'name' => Translation::translateForUser('Japan') ],
    'JO' => [ 'iso' => 400, 'code' => 'JO', 'code3' => 'JOR', 'name' => Translation::translateForUser('Jordan') ],
    'KZ' => [ 'iso' => 398, 'code' => 'KZ', 'code3' => 'KAZ', 'name' => Translation::translateForUser('Kazakhstan') ],
    'KE' => [ 'iso' => 404, 'code' => 'KE', 'code3' => 'KEN', 'name' => Translation::translateForUser('Kenya') ],
    'KI' => [ 'iso' => 296, 'code' => 'KI', 'code3' => 'KIR', 'name' => Translation::translateForUser('Kiribati') ],
    'KP' => [ 'iso' => 408, 'code' => 'KP', 'code3' => 'PRK', 'name' => Translation::translateForUser('Korea, Democratic People\'s Republic Of') ],
    'KR' => [ 'iso' => 410, 'code' => 'KR', 'code3' => 'KOR', 'name' => Translation::translateForUser('Korea, Republic Of') ],
    'KW' => [ 'iso' => 414, 'code' => 'KW', 'code3' => 'KWT', 'name' => Translation::translateForUser('Kuwait') ],
    'KG' => [ 'iso' => 417, 'code' => 'KG', 'code3' => 'KGZ', 'name' => Translation::translateForUser('Kyrgyzstan') ],
    'LA' => [ 'iso' => 418, 'code' => 'LA', 'code3' => 'LAO', 'name' => Translation::translateForUser('Lao People\'s Democratic Republic') ],
    'LV' => [ 'iso' => 428, 'code' => 'LV', 'code3' => 'LVA', 'name' => Translation::translateForUser('Latvia') ],
    'LB' => [ 'iso' => 422, 'code' => 'LB', 'code3' => 'LBN', 'name' => Translation::translateForUser('Lebanon') ],
    'LS' => [ 'iso' => 426, 'code' => 'LS', 'code3' => 'LSO', 'name' => Translation::translateForUser('Lesotho') ],
    'LR' => [ 'iso' => 430, 'code' => 'LR', 'code3' => 'LBR', 'name' => Translation::translateForUser('Liberia') ],
    'LY' => [ 'iso' => 434, 'code' => 'LY', 'code3' => 'LBY', 'name' => Translation::translateForUser('Libyan Arab Jamahiriya') ],
    'LI' => [ 'iso' => 438, 'code' => 'LI', 'code3' => 'LIE', 'name' => Translation::translateForUser('Liechtenstein') ],
    'LT' => [ 'iso' => 440, 'code' => 'LT', 'code3' => 'LTU', 'name' => Translation::translateForUser('Lithuania') ],
    'LU' => [ 'iso' => 442, 'code' => 'LU', 'code3' => 'LUX', 'name' => Translation::translateForUser('Luxembourg') ],
    'MO' => [ 'iso' => 446, 'code' => 'MO', 'code3' => 'MAC', 'name' => Translation::translateForUser('Macau') ],
    'MK' => [ 'iso' => 807, 'code' => 'MK', 'code3' => 'MKD', 'name' => Translation::translateForUser('Macedonia') ],
    'MG' => [ 'iso' => 450, 'code' => 'MG', 'code3' => 'MDG', 'name' => Translation::translateForUser('Madagascar') ],
    'MW' => [ 'iso' => 454, 'code' => 'MW', 'code3' => 'MWI', 'name' => Translation::translateForUser('Malawi') ],
    'MY' => [ 'iso' => 458, 'code' => 'MY', 'code3' => 'MYS', 'name' => Translation::translateForUser('Malaysia') ],
    'MV' => [ 'iso' => 462, 'code' => 'MV', 'code3' => 'MDV', 'name' => Translation::translateForUser('Maldives') ],
    'ML' => [ 'iso' => 466, 'code' => 'ML', 'code3' => 'MLI', 'name' => Translation::translateForUser('Mali') ],
    'MT' => [ 'iso' => 470, 'code' => 'MT', 'code3' => 'MLT', 'name' => Translation::translateForUser('Malta') ],
    'MH' => [ 'iso' => 584, 'code' => 'MH', 'code3' => 'MHL', 'name' => Translation::translateForUser('Marshall Islands') ],
    'MQ' => [ 'iso' => 474, 'code' => 'MQ', 'code3' => 'MTQ', 'name' => Translation::translateForUser('Martinique') ],
    'MR' => [ 'iso' => 478, 'code' => 'MR', 'code3' => 'MRT', 'name' => Translation::translateForUser('Mauritania') ],
    'MU' => [ 'iso' => 480, 'code' => 'MU', 'code3' => 'MUS', 'name' => Translation::translateForUser('Mauritius') ],
    'YT' => [ 'iso' => 175, 'code' => 'YT', 'code3' => 'MYT', 'name' => Translation::translateForUser('Mayotte') ],
    'MX' => [ 'iso' => 484, 'code' => 'MX', 'code3' => 'MEX', 'name' => Translation::translateForUser('Mexico') ],
    'FM' => [ 'iso' => 583, 'code' => 'FM', 'code3' => 'FSM', 'name' => Translation::translateForUser('Micronesia, Federated States Of') ],
    'MD' => [ 'iso' => 498, 'code' => 'MD', 'code3' => 'MDA', 'name' => Translation::translateForUser('Moldova, Republic Of') ],
    'MC' => [ 'iso' => 492, 'code' => 'MC', 'code3' => 'MCO', 'name' => Translation::translateForUser('Monaco') ],
    'MN' => [ 'iso' => 496, 'code' => 'MN', 'code3' => 'MNG', 'name' => Translation::translateForUser('Mongolia') ],
    'MS' => [ 'iso' => 500, 'code' => 'MS', 'code3' => 'MSR', 'name' => Translation::translateForUser('Montserrat') ],
    'MA' => [ 'iso' => 504, 'code' => 'MA', 'code3' => 'MAR', 'name' => Translation::translateForUser('Morocco') ],
    'MZ' => [ 'iso' => 508, 'code' => 'MZ', 'code3' => 'MOZ', 'name' => Translation::translateForUser('Mozambique') ],
    'MM' => [ 'iso' => 104, 'code' => 'MM', 'code3' => 'MMR', 'name' => Translation::translateForUser('Myanmar') ],
    'NA' => [ 'iso' => 516, 'code' => 'NA', 'code3' => 'NAM', 'name' => Translation::translateForUser('Namibia') ],
    'NR' => [ 'iso' => 520, 'code' => 'NR', 'code3' => 'NRU', 'name' => Translation::translateForUser('Nauru') ],
    'NP' => [ 'iso' => 524, 'code' => 'NP', 'code3' => 'NPL', 'name' => Translation::translateForUser('Nepal') ],
    'NL' => [ 'iso' => 528, 'code' => 'NL', 'code3' => 'NLD', 'name' => Translation::translateForUser('Netherlands') ],
    'AN' => [ 'iso' => 530, 'code' => 'AN', 'code3' => 'ANT', 'name' => Translation::translateForUser('Netherlands Antilles') ],
    'NC' => [ 'iso' => 540, 'code' => 'NC', 'code3' => 'NCL', 'name' => Translation::translateForUser('New Caledonia') ],
    'NZ' => [ 'iso' => 554, 'code' => 'NZ', 'code3' => 'NZL', 'name' => Translation::translateForUser('New Zealand') ],
    'NI' => [ 'iso' => 558, 'code' => 'NI', 'code3' => 'NIC', 'name' => Translation::translateForUser('Nicaragua') ],
    'NE' => [ 'iso' => 562, 'code' => 'NE', 'code3' => 'NER', 'name' => Translation::translateForUser('Niger') ],
    'NG' => [ 'iso' => 566, 'code' => 'NG', 'code3' => 'NGA', 'name' => Translation::translateForUser('Nigeria') ],
    'NU' => [ 'iso' => 570, 'code' => 'NU', 'code3' => 'NIU', 'name' => Translation::translateForUser('Niue') ],
    'NF' => [ 'iso' => 574, 'code' => 'NF', 'code3' => 'NFK', 'name' => Translation::translateForUser('Norfolk Island') ],
    'MP' => [ 'iso' => 580, 'code' => 'MP', 'code3' => 'MNP', 'name' => Translation::translateForUser('Northern Mariana Islands') ],
    'NO' => [ 'iso' => 578, 'code' => 'NO', 'code3' => 'NOR', 'name' => Translation::translateForUser('Norway') ],
    'OM' => [ 'iso' => 512, 'code' => 'OM', 'code3' => 'OMN', 'name' => Translation::translateForUser('Oman') ],
    'PK' => [ 'iso' => 586, 'code' => 'PK', 'code3' => 'PAK', 'name' => Translation::translateForUser('Pakistan') ],
    'PW' => [ 'iso' => 585, 'code' => 'PW', 'code3' => 'PLW', 'name' => Translation::translateForUser('Palau') ],
    'PA' => [ 'iso' => 591, 'code' => 'PA', 'code3' => 'PAN', 'name' => Translation::translateForUser('Panama') ],
    'PG' => [ 'iso' => 598, 'code' => 'PG', 'code3' => 'PNG', 'name' => Translation::translateForUser('Papua New Guinea') ],
    'PY' => [ 'iso' => 600, 'code' => 'PY', 'code3' => 'PRY', 'name' => Translation::translateForUser('Paraguay') ],
    'PE' => [ 'iso' => 604, 'code' => 'PE', 'code3' => 'PER', 'name' => Translation::translateForUser('Peru') ],
    'PH' => [ 'iso' => 608, 'code' => 'PH', 'code3' => 'PHL', 'name' => Translation::translateForUser('Philippines') ],
    'PN' => [ 'iso' => 612, 'code' => 'PN', 'code3' => 'PCN', 'name' => Translation::translateForUser('Pitcairn') ],
    'PL' => [ 'iso' => 616, 'code' => 'PL', 'code3' => 'POL', 'name' => Translation::translateForUser('Poland') ],
    'PT' => [ 'iso' => 620, 'code' => 'PT', 'code3' => 'PRT', 'name' => Translation::translateForUser('Portugal') ],
    'PR' => [ 'iso' => 630, 'code' => 'PR', 'code3' => 'PRI', 'name' => Translation::translateForUser('Puerto Rico') ],
    'QA' => [ 'iso' => 634, 'code' => 'QA', 'code3' => 'QAT', 'name' => Translation::translateForUser('Qatar') ],
    'RE' => [ 'iso' => 638, 'code' => 'RE', 'code3' => 'REU', 'name' => Translation::translateForUser('Reunion') ],
    'RO' => [ 'iso' => 642, 'code' => 'RO', 'code3' => 'ROM', 'name' => Translation::translateForUser('Romania') ],
    'RU' => [ 'iso' => 643, 'code' => 'RU', 'code3' => 'RUS', 'name' => Translation::translateForUser('Russian Federation') ],
    'RW' => [ 'iso' => 646, 'code' => 'RW', 'code3' => 'RWA', 'name' => Translation::translateForUser('Rwanda') ],
    'KN' => [ 'iso' => 659, 'code' => 'KN', 'code3' => 'KNA', 'name' => Translation::translateForUser('Saint Kitts And Nevis') ],
    'LC' => [ 'iso' => 662, 'code' => 'LC', 'code3' => 'LCA', 'name' => Translation::translateForUser('Saint Lucia') ],
    'VC' => [ 'iso' => 670, 'code' => 'VC', 'code3' => 'VCT', 'name' => Translation::translateForUser('Saint Vincent And The Grenadines') ],
    'WS' => [ 'iso' => 882, 'code' => 'WS', 'code3' => 'WSM', 'name' => Translation::translateForUser('Samoa') ],
    'SM' => [ 'iso' => 674, 'code' => 'SM', 'code3' => 'SMR', 'name' => Translation::translateForUser('San Marino') ],
    'ST' => [ 'iso' => 678, 'code' => 'ST', 'code3' => 'STP', 'name' => Translation::translateForUser('Sao Tome And Principe') ],
    'SA' => [ 'iso' => 682, 'code' => 'SA', 'code3' => 'SAU', 'name' => Translation::translateForUser('Saudi Arabia') ],
    'SN' => [ 'iso' => 686, 'code' => 'SN', 'code3' => 'SEN', 'name' => Translation::translateForUser('Senegal') ],
    'SC' => [ 'iso' => 690, 'code' => 'SC', 'code3' => 'SYC', 'name' => Translation::translateForUser('Seychelles') ],
    'SL' => [ 'iso' => 694, 'code' => 'SL', 'code3' => 'SLE', 'name' => Translation::translateForUser('Sierra Leone') ],
    'SG' => [ 'iso' => 702, 'code' => 'SG', 'code3' => 'SGP', 'name' => Translation::translateForUser('Singapore') ],
    'SK' => [ 'iso' => 703, 'code' => 'SK', 'code3' => 'SVK', 'name' => Translation::translateForUser('Slovakia') ],
    'SI' => [ 'iso' => 705, 'code' => 'SI', 'code3' => 'SVN', 'name' => Translation::translateForUser('Slovenia') ],
    'SB' => [ 'iso' => 90, 'code' => 'SB', 'code3' => 'SLB', 'name' => Translation::translateForUser('Solomon Islands') ],
    'SO' => [ 'iso' => 706, 'code' => 'SO', 'code3' => 'SOM', 'name' => Translation::translateForUser('Somalia') ],
    'ZA' => [ 'iso' => 710, 'code' => 'ZA', 'code3' => 'ZAF', 'name' => Translation::translateForUser('South Africa') ],
    'GS' => [ 'iso' => 239, 'code' => 'GS', 'code3' => 'SGS', 'name' => Translation::translateForUser('South Georgia Islands') ],
    'ES' => [ 'iso' => 724, 'code' => 'ES', 'code3' => 'ESP', 'name' => Translation::translateForUser('Spain') ],
    'LK' => [ 'iso' => 144, 'code' => 'LK', 'code3' => 'LKA', 'name' => Translation::translateForUser('Sri Lanka') ],
    'SH' => [ 'iso' => 654, 'code' => 'SH', 'code3' => 'SHN', 'name' => Translation::translateForUser('St. Helena') ],
    'PM' => [ 'iso' => 666, 'code' => 'PM', 'code3' => 'SPM', 'name' => Translation::translateForUser('St. Pierre And Miquelon') ],
    'SD' => [ 'iso' => 736, 'code' => 'SD', 'code3' => 'SDN', 'name' => Translation::translateForUser('Sudan') ],
    'SR' => [ 'iso' => 740, 'code' => 'SR', 'code3' => 'SUR', 'name' => Translation::translateForUser('Suriname') ],
    'SJ' => [ 'iso' => 744, 'code' => 'SJ', 'code3' => 'SJM', 'name' => Translation::translateForUser('Svalbard And Jan Mayen Islands') ],
    'SZ' => [ 'iso' => 748, 'code' => 'SZ', 'code3' => 'SWZ', 'name' => Translation::translateForUser('Swaziland') ],
    'SE' => [ 'iso' => 752, 'code' => 'SE', 'code3' => 'SWE', 'name' => Translation::translateForUser('Sweden') ],
    'CH' => [ 'iso' => 756, 'code' => 'CH', 'code3' => 'CHE', 'name' => Translation::translateForUser('Switzerland') ],
    'SY' => [ 'iso' => 760, 'code' => 'SY', 'code3' => 'SYR', 'name' => Translation::translateForUser('Syrian Arab Republic') ],
    'TW' => [ 'iso' => 158, 'code' => 'TW', 'code3' => 'TWN', 'name' => Translation::translateForUser('Taiwan, Province Of China') ],
    'TJ' => [ 'iso' => 762, 'code' => 'TJ', 'code3' => 'TJK', 'name' => Translation::translateForUser('Tajikistan') ],
    'TZ' => [ 'iso' => 834, 'code' => 'TZ', 'code3' => 'TZA', 'name' => Translation::translateForUser('Tanzania, United Republic Of') ],
    'TH' => [ 'iso' => 764, 'code' => 'TH', 'code3' => 'THA', 'name' => Translation::translateForUser('Thailand') ],
    'TG' => [ 'iso' => 768, 'code' => 'TG', 'code3' => 'TGO', 'name' => Translation::translateForUser('Togo') ],
    'TK' => [ 'iso' => 772, 'code' => 'TK', 'code3' => 'TKL', 'name' => Translation::translateForUser('Tokelau') ],
    'TO' => [ 'iso' => 776, 'code' => 'TO', 'code3' => 'TON', 'name' => Translation::translateForUser('Tonga') ],
    'TT' => [ 'iso' => 780, 'code' => 'TT', 'code3' => 'TTO', 'name' => Translation::translateForUser('Trinidad And Tobago') ],
    'TN' => [ 'iso' => 788, 'code' => 'TN', 'code3' => 'TUN', 'name' => Translation::translateForUser('Tunisia') ],
    'TR' => [ 'iso' => 792, 'code' => 'TR', 'code3' => 'TUR', 'name' => Translation::translateForUser('Turkey') ],
    'TM' => [ 'iso' => 795, 'code' => 'TM', 'code3' => 'TKM', 'name' => Translation::translateForUser('Turkmenistan') ],
    'TC' => [ 'iso' => 796, 'code' => 'TC', 'code3' => 'TCA', 'name' => Translation::translateForUser('Turks And Caicos Islands') ],
    'TV' => [ 'iso' => 798, 'code' => 'TV', 'code3' => 'TUV', 'name' => Translation::translateForUser('Tuvalu') ],
    'UG' => [ 'iso' => 800, 'code' => 'UG', 'code3' => 'UGA', 'name' => Translation::translateForUser('Uganda') ],
    'UA' => [ 'iso' => 804, 'code' => 'UA', 'code3' => 'UKR', 'name' => Translation::translateForUser('Ukraine') ],
    'AE' => [ 'iso' => 784, 'code' => 'AE', 'code3' => 'ARE', 'name' => Translation::translateForUser('United Arab Emirates') ],
    'GB' => [ 'iso' => 826, 'code' => 'GB', 'code3' => 'GBR', 'name' => Translation::translateForUser('United Kingdom') ],
    'US' => [ 'iso' => 840, 'code' => 'US', 'code3' => 'USA', 'name' => Translation::translateForUser('United States') ],
    'UM' => [ 'iso' => 581, 'code' => 'UM', 'code3' => 'UMI', 'name' => Translation::translateForUser('United States Minor Outlying Islands') ],
    'UY' => [ 'iso' => 858, 'code' => 'UY', 'code3' => 'URY', 'name' => Translation::translateForUser('Uruguay') ],
    'UZ' => [ 'iso' => 860, 'code' => 'UZ', 'code3' => 'UZB', 'name' => Translation::translateForUser('Uzbekistan') ],
    'VU' => [ 'iso' => 548, 'code' => 'VU', 'code3' => 'VUT', 'name' => Translation::translateForUser('Vanuatu') ],
    'VA' => [ 'iso' => 336, 'code' => 'VA', 'code3' => 'VAT', 'name' => Translation::translateForUser('Vatican City State') ],
    'VE' => [ 'iso' => 862, 'code' => 'VE', 'code3' => 'VEN', 'name' => Translation::translateForUser('Venezuela') ],
    'VN' => [ 'iso' => 704, 'code' => 'VN', 'code3' => 'VNM', 'name' => Translation::translateForUser('Viet Nam') ],
    'VG' => [ 'iso' => 92, 'code' => 'VG', 'code3' => 'VGB', 'name' => Translation::translateForUser('Virgin Islands (british)') ],
    'VI' => [ 'iso' => 850, 'code' => 'VI', 'code3' => 'VIR', 'name' => Translation::translateForUser('Virgin Islands (u.s.)') ],
    'WF' => [ 'iso' => 876, 'code' => 'WF', 'code3' => 'WLF', 'name' => Translation::translateForUser('Wallis And Futuna Islands') ],
    'EH' => [ 'iso' => 732, 'code' => 'EH', 'code3' => 'ESH', 'name' => Translation::translateForUser('Western Sahara') ],
    'YE' => [ 'iso' => 887, 'code' => 'YE', 'code3' => 'YEM', 'name' => Translation::translateForUser('Yemen') ],
    'RS' => [ 'iso' => 688, 'code' => 'RS', 'code3' => 'RSB', 'name' => Translation::translateForUser('Serbia') ],
    'ZR' => [ 'iso' => 180, 'code' => 'ZR', 'code3' => 'ZAR', 'name' => Translation::translateForUser('Zaire') ],
    'ZM' => [ 'iso' => 894, 'code' => 'ZM', 'code3' => 'ZMB', 'name' => Translation::translateForUser('Zambia') ],
    'ZW' => [ 'iso' => 716, 'code' => 'ZW', 'code3' => 'ZWE', 'name' => Translation::translateForUser('Zimbabwe') ],
    'ME' => [ 'iso' => 499, 'code' => 'ME', 'code3' => 'MNE', 'name' => Translation::translateForUser('Montenegro') ],
    'IC' => [ 'iso' => 724, 'code' => 'IC', 'code3' => 'CNR', 'name' => Translation::translateForUser('Canary Islands') ],
    'CD' => [ 'iso' => 180, 'code' => 'CD', 'code3' => 'COD', 'name' => Translation::translateForUser('Congo - Dem.republic') ],
    'XK' => [ 'iso' => 0, 'code' => 'XK', 'code3' => 'XKX', 'name' => Translation::translateForUser('Kosovo') ],
    'JE' => [ 'iso' => 832, 'code' => 'JE', 'code3' => 'JEY', 'name' => Translation::translateForUser('Jersey') ],
    'GG' => [ 'iso' => 831, 'code' => 'GG', 'code3' => 'GGY', 'name' => Translation::translateForUser('Guernsey') ],
];
