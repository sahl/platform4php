<?php
namespace Platform\Form;
/**
 * Field for inputting addresses
 *
 * @link https://wiki.platform4php.dk/doku.php?id=field_class
 */

use Platform\Datarecord\Datarecord;
use Platform\Utilities\Translation;
use Platform\Utilities\Utilities;

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
        static::JSFile(Utilities::directoryToURL(__DIR__).'/js/AddressField.js');

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
        $style = $this->getFieldStyleString();
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
    'AF' => [ 'iso' => 4, 'code' => 'AF', 'code3' => 'AFG', 'name' => Translation::translateForUser('Afghanistan'), 'international_name' => 'Afghanistan', 'local_name' => 'افغانستان' ],
    'AL' => [ 'iso' => 8, 'code' => 'AL', 'code3' => 'ALB', 'name' => Translation::translateForUser('Albania'), 'international_name' => 'Albania', 'local_name' => 'Shqipëria' ],
    'DZ' => [ 'iso' => 12, 'code' => 'DZ', 'code3' => 'DZA', 'name' => Translation::translateForUser('Algeria'), 'international_name' => 'Algeria', 'local_name' => 'الجزائر' ],
    'AS' => [ 'iso' => 16, 'code' => 'AS', 'code3' => 'ASM', 'name' => Translation::translateForUser('American Samoa'), 'international_name' => 'American Samoa', 'local_name' => 'Amerika Sāmoa' ],
    'AD' => [ 'iso' => 20, 'code' => 'AD', 'code3' => 'AND', 'name' => Translation::translateForUser('Andorra'), 'international_name' => 'Andorra', 'local_name' => 'Andorra' ],
    'AO' => [ 'iso' => 24, 'code' => 'AO', 'code3' => 'AGO', 'name' => Translation::translateForUser('Angola'), 'international_name' => 'Angola', 'local_name' => 'Angola' ],
    'AI' => [ 'iso' => 660, 'code' => 'AI', 'code3' => 'AIA', 'name' => Translation::translateForUser('Anguilla'), 'international_name' => 'Anguilla', 'local_name' => 'Anguilla' ],
    'AQ' => [ 'iso' => 10, 'code' => 'AQ', 'code3' => 'ATA', 'name' => Translation::translateForUser('Antarctica'), 'international_name' => 'Antarctica', 'local_name' => 'Antarctica' ],
    'AG' => [ 'iso' => 28, 'code' => 'AG', 'code3' => 'ATG', 'name' => Translation::translateForUser('Antigua And Barbuda'), 'international_name' => 'Antigua and Barbuda', 'local_name' => 'Antigua and Barbuda' ],
    'AR' => [ 'iso' => 32, 'code' => 'AR', 'code3' => 'ARG', 'name' => Translation::translateForUser('Argentina'), 'international_name' => 'Argentina', 'local_name' => 'Argentina' ],
    'AM' => [ 'iso' => 51, 'code' => 'AM', 'code3' => 'ARM', 'name' => Translation::translateForUser('Armenia'), 'international_name' => 'Armenia', 'local_name' => 'Հայաստան' ],
    'AW' => [ 'iso' => 533, 'code' => 'AW', 'code3' => 'ABW', 'name' => Translation::translateForUser('Aruba'), 'international_name' => 'Aruba', 'local_name' => 'Aruba' ],
    'AU' => [ 'iso' => 36, 'code' => 'AU', 'code3' => 'AUS', 'name' => Translation::translateForUser('Australia'), 'international_name' => 'Australia', 'local_name' => 'Australia' ],
    'AT' => [ 'iso' => 40, 'code' => 'AT', 'code3' => 'AUT', 'name' => Translation::translateForUser('Austria'), 'international_name' => 'Austria', 'local_name' => 'Österreich' ],
    'AZ' => [ 'iso' => 31, 'code' => 'AZ', 'code3' => 'AZE', 'name' => Translation::translateForUser('Azerbaijan'), 'international_name' => 'Azerbaijan', 'local_name' => 'Azərbaycan' ],
    'BS' => [ 'iso' => 44, 'code' => 'BS', 'code3' => 'BHS', 'name' => Translation::translateForUser('Bahamas'), 'international_name' => 'Bahamas', 'local_name' => 'Bahamas' ],
    'BH' => [ 'iso' => 48, 'code' => 'BH', 'code3' => 'BHR', 'name' => Translation::translateForUser('Bahrain'), 'international_name' => 'Bahrain', 'local_name' => 'البحرين' ],
    'BD' => [ 'iso' => 50, 'code' => 'BD', 'code3' => 'BGD', 'name' => Translation::translateForUser('Bangladesh'), 'international_name' => 'Bangladesh', 'local_name' => 'বাংলাদেশ' ],
    'BB' => [ 'iso' => 52, 'code' => 'BB', 'code3' => 'BRB', 'name' => Translation::translateForUser('Barbados'), 'international_name' => 'Barbados', 'local_name' => 'Barbados' ],
    'BY' => [ 'iso' => 112, 'code' => 'BY', 'code3' => 'BLR', 'name' => Translation::translateForUser('Belarus'), 'international_name' => 'Belarus', 'local_name' => 'Беларусь' ],
    'BE' => [ 'iso' => 56, 'code' => 'BE', 'code3' => 'BEL', 'name' => Translation::translateForUser('Belgium'), 'international_name' => 'Belgium', 'local_name' => 'België / Belgique' ],
    'BZ' => [ 'iso' => 84, 'code' => 'BZ', 'code3' => 'BLZ', 'name' => Translation::translateForUser('Belize'), 'international_name' => 'Belize', 'local_name' => 'Belize' ],
    'BJ' => [ 'iso' => 204, 'code' => 'BJ', 'code3' => 'BEN', 'name' => Translation::translateForUser('Benin'), 'international_name' => 'Benin', 'local_name' => 'Bénin' ],
    'BM' => [ 'iso' => 60, 'code' => 'BM', 'code3' => 'BMU', 'name' => Translation::translateForUser('Bermuda'), 'international_name' => 'Bermuda', 'local_name' => 'Bermuda' ],
    'BT' => [ 'iso' => 64, 'code' => 'BT', 'code3' => 'BTN', 'name' => Translation::translateForUser('Bhutan'), 'international_name' => 'Bhutan', 'local_name' => 'འབྲུག་ཡུལ' ],
    'BO' => [ 'iso' => 68, 'code' => 'BO', 'code3' => 'BOL', 'name' => Translation::translateForUser('Bolivia'), 'international_name' => 'Bolivia', 'local_name' => 'Bolivia' ],
    'BA' => [ 'iso' => 70, 'code' => 'BA', 'code3' => 'BIH', 'name' => Translation::translateForUser('Bosnia And Herzegowina'), 'international_name' => 'Bosnia and Herzegovina', 'local_name' => 'Bosna i Hercegovina' ],
    'BW' => [ 'iso' => 72, 'code' => 'BW', 'code3' => 'BWA', 'name' => Translation::translateForUser('Botswana'), 'international_name' => 'Botswana', 'local_name' => 'Botswana' ],
    'BV' => [ 'iso' => 74, 'code' => 'BV', 'code3' => 'BVT', 'name' => Translation::translateForUser('Bouvet Island'), 'international_name' => 'Bouvet Island', 'local_name' => 'Bouvetøya' ],
    'BR' => [ 'iso' => 76, 'code' => 'BR', 'code3' => 'BRA', 'name' => Translation::translateForUser('Brazil'), 'international_name' => 'Brazil', 'local_name' => 'Brasil' ],
    'IO' => [ 'iso' => 86, 'code' => 'IO', 'code3' => 'IOT', 'name' => Translation::translateForUser('British Indian Ocean Territory'), 'international_name' => 'British Indian Ocean Territory', 'local_name' => 'British Indian Ocean Territory' ],
    'BN' => [ 'iso' => 96, 'code' => 'BN', 'code3' => 'BRN', 'name' => Translation::translateForUser('Brunei Darussalam'), 'international_name' => 'Brunei Darussalam', 'local_name' => 'Brunei Darussalam' ],
    'BG' => [ 'iso' => 100, 'code' => 'BG', 'code3' => 'BGR', 'name' => Translation::translateForUser('Bulgaria'), 'international_name' => 'Bulgaria', 'local_name' => 'България' ],
    'BF' => [ 'iso' => 854, 'code' => 'BF', 'code3' => 'BFA', 'name' => Translation::translateForUser('Burkina Faso'), 'international_name' => 'Burkina Faso', 'local_name' => 'Burkina Faso' ],
    'BI' => [ 'iso' => 108, 'code' => 'BI', 'code3' => 'BDI', 'name' => Translation::translateForUser('Burundi'), 'international_name' => 'Burundi', 'local_name' => 'Uburundi' ],
    'KH' => [ 'iso' => 116, 'code' => 'KH', 'code3' => 'KHM', 'name' => Translation::translateForUser('Cambodia'), 'international_name' => 'Cambodia', 'local_name' => 'កម្ពុជា' ],
    'CM' => [ 'iso' => 120, 'code' => 'CM', 'code3' => 'CMR', 'name' => Translation::translateForUser('Cameroon'), 'international_name' => 'Cameroon', 'local_name' => 'Cameroun' ],
    'CA' => [ 'iso' => 124, 'code' => 'CA', 'code3' => 'CAN', 'name' => Translation::translateForUser('Canada'), 'international_name' => 'Canada', 'local_name' => 'Canada' ],
    'CV' => [ 'iso' => 132, 'code' => 'CV', 'code3' => 'CPV', 'name' => Translation::translateForUser('Cape Verde'), 'international_name' => 'Cape Verde', 'local_name' => 'Cabo Verde' ],
    'KY' => [ 'iso' => 136, 'code' => 'KY', 'code3' => 'CYM', 'name' => Translation::translateForUser('Cayman Islands'), 'international_name' => 'Cayman Islands', 'local_name' => 'Cayman Islands' ],
    'CF' => [ 'iso' => 140, 'code' => 'CF', 'code3' => 'CAF', 'name' => Translation::translateForUser('Central African Republic'), 'international_name' => 'Central African Republic', 'local_name' => 'Ködörösêse tî Bêafrîka' ],
    'TD' => [ 'iso' => 148, 'code' => 'TD', 'code3' => 'TCD', 'name' => Translation::translateForUser('Chad'), 'international_name' => 'Chad', 'local_name' => 'Tchad / تشاد' ],
    'CL' => [ 'iso' => 152, 'code' => 'CL', 'code3' => 'CHL', 'name' => Translation::translateForUser('Chile'), 'international_name' => 'Chile', 'local_name' => 'Chile' ],
    'CN' => [ 'iso' => 156, 'code' => 'CN', 'code3' => 'CHN', 'name' => Translation::translateForUser('China'), 'international_name' => 'China', 'local_name' => '中国' ],
    'CX' => [ 'iso' => 162, 'code' => 'CX', 'code3' => 'CXR', 'name' => Translation::translateForUser('Christmas Island'), 'international_name' => 'Christmas Island', 'local_name' => 'Christmas Island' ],
    'CC' => [ 'iso' => 166, 'code' => 'CC', 'code3' => 'CCK', 'name' => Translation::translateForUser('Cocos Islands'), 'international_name' => 'Cocos (Keeling) Islands', 'local_name' => 'Cocos (Keeling) Islands' ],
    'CO' => [ 'iso' => 170, 'code' => 'CO', 'code3' => 'COL', 'name' => Translation::translateForUser('Colombia'), 'international_name' => 'Colombia', 'local_name' => 'Colombia' ],
    'KM' => [ 'iso' => 174, 'code' => 'KM', 'code3' => 'COM', 'name' => Translation::translateForUser('Comoros'), 'international_name' => 'Comoros', 'local_name' => 'Komori / Comores' ],
    'CG' => [ 'iso' => 178, 'code' => 'CG', 'code3' => 'COG', 'name' => Translation::translateForUser('Congo'), 'international_name' => 'Congo', 'local_name' => 'Congo' ],
    'CK' => [ 'iso' => 184, 'code' => 'CK', 'code3' => 'COK', 'name' => Translation::translateForUser('Cook Islands'), 'international_name' => 'Cook Islands', 'local_name' => 'Kūki \'Āirani' ],
    'CR' => [ 'iso' => 188, 'code' => 'CR', 'code3' => 'CRI', 'name' => Translation::translateForUser('Costa Rica'), 'international_name' => 'Costa Rica', 'local_name' => 'Costa Rica' ],
    'CI' => [ 'iso' => 384, 'code' => 'CI', 'code3' => 'CIV', 'name' => Translation::translateForUser('Cote D\'ivoire'), 'international_name' => 'Cote D\'ivoire', 'local_name' => 'Côte d\'Ivoire' ],
    'HR' => [ 'iso' => 191, 'code' => 'HR', 'code3' => 'HRV', 'name' => Translation::translateForUser('Croatia'), 'international_name' => 'Croatia', 'local_name' => 'Hrvatska' ],
    'CU' => [ 'iso' => 192, 'code' => 'CU', 'code3' => 'CUB', 'name' => Translation::translateForUser('Cuba'), 'international_name' => 'Cuba', 'local_name' => 'Cuba' ],
    'CY' => [ 'iso' => 196, 'code' => 'CY', 'code3' => 'CYP', 'name' => Translation::translateForUser('Cyprus'), 'international_name' => 'Cyprus', 'local_name' => 'Κύπρος / Kıbrıs' ],
    'CZ' => [ 'iso' => 203, 'code' => 'CZ', 'code3' => 'CZE', 'name' => Translation::translateForUser('Czech Republic'), 'international_name' => 'Czech Republic', 'local_name' => 'Česká republika' ],
    'DK' => [ 'iso' => 208, 'code' => 'DK', 'code3' => 'DNK', 'name' => Translation::translateForUser('Denmark'), 'international_name' => 'Denmark', 'local_name' => 'Danmark' ],
    'DJ' => [ 'iso' => 262, 'code' => 'DJ', 'code3' => 'DJI', 'name' => Translation::translateForUser('Djibouti'), 'international_name' => 'Djibouti', 'local_name' => 'Djibouti / جيبوتي' ],
    'DM' => [ 'iso' => 212, 'code' => 'DM', 'code3' => 'DMA', 'name' => Translation::translateForUser('Dominica'), 'international_name' => 'Dominica', 'local_name' => 'Dominica' ],
    'DO' => [ 'iso' => 214, 'code' => 'DO', 'code3' => 'DOM', 'name' => Translation::translateForUser('Dominican Republic'), 'international_name' => 'Dominican Republic', 'local_name' => 'República Dominicana' ],
    'TP' => [ 'iso' => 626, 'code' => 'TP', 'code3' => 'TMP', 'name' => Translation::translateForUser('East Timor'), 'international_name' => 'Timor-Leste', 'local_name' => 'Timor-Leste' ],
    'EC' => [ 'iso' => 218, 'code' => 'EC', 'code3' => 'ECU', 'name' => Translation::translateForUser('Ecuador'), 'international_name' => 'Ecuador', 'local_name' => 'Ecuador' ],
    'EG' => [ 'iso' => 818, 'code' => 'EG', 'code3' => 'EGY', 'name' => Translation::translateForUser('Egypt'), 'international_name' => 'Egypt', 'local_name' => 'مصر' ],
    'SV' => [ 'iso' => 222, 'code' => 'SV', 'code3' => 'SLV', 'name' => Translation::translateForUser('El Salvador'), 'international_name' => 'El Salvador', 'local_name' => 'El Salvador' ],
    'GQ' => [ 'iso' => 226, 'code' => 'GQ', 'code3' => 'GNQ', 'name' => Translation::translateForUser('Equatorial Guinea'), 'international_name' => 'Equatorial Guinea', 'local_name' => 'Guinea Ecuatorial' ],
    'ER' => [ 'iso' => 232, 'code' => 'ER', 'code3' => 'ERI', 'name' => Translation::translateForUser('Eritrea'), 'international_name' => 'Eritrea', 'local_name' => 'ኤርትራ' ],
    'EE' => [ 'iso' => 233, 'code' => 'EE', 'code3' => 'EST', 'name' => Translation::translateForUser('Estonia'), 'international_name' => 'Estonia', 'local_name' => 'Eesti' ],
    'ET' => [ 'iso' => 231, 'code' => 'ET', 'code3' => 'ETH', 'name' => Translation::translateForUser('Ethiopia'), 'international_name' => 'Ethiopia', 'local_name' => 'ኢትዮጵያ' ],
    'FK' => [ 'iso' => 238, 'code' => 'FK', 'code3' => 'FLK', 'name' => Translation::translateForUser('Falkland Islands'), 'international_name' => 'Falkland Islands', 'local_name' => 'Falkland Islands' ],
    'FO' => [ 'iso' => 234, 'code' => 'FO', 'code3' => 'FRO', 'name' => Translation::translateForUser('Faroe Islands'), 'international_name' => 'Faroe Islands', 'local_name' => 'Føroyar' ],
    'FJ' => [ 'iso' => 242, 'code' => 'FJ', 'code3' => 'FJI', 'name' => Translation::translateForUser('Fiji'), 'international_name' => 'Fiji', 'local_name' => 'Viti' ],
    'FI' => [ 'iso' => 246, 'code' => 'FI', 'code3' => 'FIN', 'name' => Translation::translateForUser('Finland'), 'international_name' => 'Finland', 'local_name' => 'Suomi' ],
    'FR' => [ 'iso' => 250, 'code' => 'FR', 'code3' => 'FRA', 'name' => Translation::translateForUser('France'), 'international_name' => 'France', 'local_name' => 'France' ],
    'GF' => [ 'iso' => 254, 'code' => 'GF', 'code3' => 'GUF', 'name' => Translation::translateForUser('French Guiana'), 'international_name' => 'French Guiana', 'local_name' => 'Guyane' ],
    'PF' => [ 'iso' => 258, 'code' => 'PF', 'code3' => 'PYF', 'name' => Translation::translateForUser('French Polynesia'), 'international_name' => 'French Polynesia', 'local_name' => 'Polynésie française' ],
    'TF' => [ 'iso' => 260, 'code' => 'TF', 'code3' => 'ATF', 'name' => Translation::translateForUser('French Southern Territories'), 'international_name' => 'French Southern Territories', 'local_name' => 'Terres australes françaises' ],
    'GA' => [ 'iso' => 266, 'code' => 'GA', 'code3' => 'GAB', 'name' => Translation::translateForUser('Gabon'), 'international_name' => 'Gabon', 'local_name' => 'Gabon' ],
    'GM' => [ 'iso' => 270, 'code' => 'GM', 'code3' => 'GMB', 'name' => Translation::translateForUser('Gambia'), 'international_name' => 'Gambia', 'local_name' => 'The Gambia' ],
    'GE' => [ 'iso' => 268, 'code' => 'GE', 'code3' => 'GEO', 'name' => Translation::translateForUser('Georgia'), 'international_name' => 'Georgia', 'local_name' => 'საქართველო' ],
    'DE' => [ 'iso' => 276, 'code' => 'DE', 'code3' => 'DEU', 'name' => Translation::translateForUser('Germany'), 'international_name' => 'Germany', 'local_name' => 'Deutschland' ],
    'GH' => [ 'iso' => 288, 'code' => 'GH', 'code3' => 'GHA', 'name' => Translation::translateForUser('Ghana'), 'international_name' => 'Ghana', 'local_name' => 'Ghana' ],
    'GI' => [ 'iso' => 292, 'code' => 'GI', 'code3' => 'GIB', 'name' => Translation::translateForUser('Gibraltar'), 'international_name' => 'Gibraltar', 'local_name' => 'Gibraltar' ],
    'GR' => [ 'iso' => 300, 'code' => 'GR', 'code3' => 'GRC', 'name' => Translation::translateForUser('Greece'), 'international_name' => 'Greece', 'local_name' => 'Ελλάδα' ],
    'GL' => [ 'iso' => 304, 'code' => 'GL', 'code3' => 'GRL', 'name' => Translation::translateForUser('Greenland'), 'international_name' => 'Greenland', 'local_name' => 'Kalaallit Nunaat' ],
    'GD' => [ 'iso' => 308, 'code' => 'GD', 'code3' => 'GRD', 'name' => Translation::translateForUser('Grenada'), 'international_name' => 'Grenada', 'local_name' => 'Grenada' ],
    'GP' => [ 'iso' => 312, 'code' => 'GP', 'code3' => 'GLP', 'name' => Translation::translateForUser('Guadeloupe'), 'international_name' => 'Guadeloupe', 'local_name' => 'Guadeloupe' ],
    'GU' => [ 'iso' => 316, 'code' => 'GU', 'code3' => 'GUM', 'name' => Translation::translateForUser('Guam'), 'international_name' => 'Guam', 'local_name' => 'Guåhan' ],
    'GT' => [ 'iso' => 320, 'code' => 'GT', 'code3' => 'GTM', 'name' => Translation::translateForUser('Guatemala'), 'international_name' => 'Guatemala', 'local_name' => 'Guatemala' ],
    'GN' => [ 'iso' => 324, 'code' => 'GN', 'code3' => 'GIN', 'name' => Translation::translateForUser('Guinea'), 'international_name' => 'Guinea', 'local_name' => 'Guinée' ],
    'GW' => [ 'iso' => 624, 'code' => 'GW', 'code3' => 'GNB', 'name' => Translation::translateForUser('Guinea-bissau'), 'international_name' => 'Guinea-Bissau', 'local_name' => 'Guiné-Bissau' ],
    'GY' => [ 'iso' => 328, 'code' => 'GY', 'code3' => 'GUY', 'name' => Translation::translateForUser('Guyana'), 'international_name' => 'Guyana', 'local_name' => 'Guyana' ],
    'HT' => [ 'iso' => 332, 'code' => 'HT', 'code3' => 'HTI', 'name' => Translation::translateForUser('Haiti'), 'international_name' => 'Haiti', 'local_name' => 'Ayiti' ],
    'HM' => [ 'iso' => 334, 'code' => 'HM', 'code3' => 'HMD', 'name' => Translation::translateForUser('Heard And Mc Donald Islands'), 'international_name' => 'Heard Island and McDonald Islands', 'local_name' => 'Heard Island and McDonald Islands' ],
    'HN' => [ 'iso' => 340, 'code' => 'HN', 'code3' => 'HND', 'name' => Translation::translateForUser('Honduras'), 'international_name' => 'Honduras', 'local_name' => 'Honduras' ],
    'HK' => [ 'iso' => 344, 'code' => 'HK', 'code3' => 'HKG', 'name' => Translation::translateForUser('Hong Kong'), 'international_name' => 'Hong Kong', 'local_name' => '香港' ],
    'HU' => [ 'iso' => 348, 'code' => 'HU', 'code3' => 'HUN', 'name' => Translation::translateForUser('Hungary'), 'international_name' => 'Hungary', 'local_name' => 'Magyarország' ],
    'IS' => [ 'iso' => 352, 'code' => 'IS', 'code3' => 'ISL', 'name' => Translation::translateForUser('Iceland'), 'international_name' => 'Iceland', 'local_name' => 'Ísland' ],
    'IN' => [ 'iso' => 356, 'code' => 'IN', 'code3' => 'IND', 'name' => Translation::translateForUser('India'), 'international_name' => 'India', 'local_name' => 'भारत' ],
    'ID' => [ 'iso' => 360, 'code' => 'ID', 'code3' => 'IDN', 'name' => Translation::translateForUser('Indonesia'), 'international_name' => 'Indonesia', 'local_name' => 'Indonesia' ],
    'IR' => [ 'iso' => 364, 'code' => 'IR', 'code3' => 'IRN', 'name' => Translation::translateForUser('Iran'), 'international_name' => 'Iran', 'local_name' => 'ایران' ],
    'IQ' => [ 'iso' => 368, 'code' => 'IQ', 'code3' => 'IRQ', 'name' => Translation::translateForUser('Iraq'), 'international_name' => 'Iraq', 'local_name' => 'العراق' ],
    'IE' => [ 'iso' => 372, 'code' => 'IE', 'code3' => 'IRL', 'name' => Translation::translateForUser('Ireland'), 'international_name' => 'Ireland', 'local_name' => 'Éire' ],
    'IL' => [ 'iso' => 376, 'code' => 'IL', 'code3' => 'ISR', 'name' => Translation::translateForUser('Israel'), 'international_name' => 'Israel', 'local_name' => 'ישראל' ],
    'IT' => [ 'iso' => 380, 'code' => 'IT', 'code3' => 'ITA', 'name' => Translation::translateForUser('Italy'), 'international_name' => 'Italy', 'local_name' => 'Italia' ],
    'JM' => [ 'iso' => 388, 'code' => 'JM', 'code3' => 'JAM', 'name' => Translation::translateForUser('Jamaica'), 'international_name' => 'Jamaica', 'local_name' => 'Jamaica' ],
    'JP' => [ 'iso' => 392, 'code' => 'JP', 'code3' => 'JPN', 'name' => Translation::translateForUser('Japan'), 'international_name' => 'Japan', 'local_name' => '日本' ],
    'JO' => [ 'iso' => 400, 'code' => 'JO', 'code3' => 'JOR', 'name' => Translation::translateForUser('Jordan'), 'international_name' => 'Jordan', 'local_name' => 'الأردن' ],
    'KZ' => [ 'iso' => 398, 'code' => 'KZ', 'code3' => 'KAZ', 'name' => Translation::translateForUser('Kazakhstan'), 'international_name' => 'Kazakhstan', 'local_name' => 'Қазақстан' ],
    'KE' => [ 'iso' => 404, 'code' => 'KE', 'code3' => 'KEN', 'name' => Translation::translateForUser('Kenya'), 'international_name' => 'Kenya', 'local_name' => 'Kenya' ],
    'KI' => [ 'iso' => 296, 'code' => 'KI', 'code3' => 'KIR', 'name' => Translation::translateForUser('Kiribati'), 'international_name' => 'Kiribati', 'local_name' => 'Kiribati' ],
    'KP' => [ 'iso' => 408, 'code' => 'KP', 'code3' => 'PRK', 'name' => Translation::translateForUser('Korea, Democratic People\'s Republic Of'), 'international_name' => 'North Korea', 'local_name' => '조선' ],
    'KR' => [ 'iso' => 410, 'code' => 'KR', 'code3' => 'KOR', 'name' => Translation::translateForUser('Korea, Republic Of'), 'international_name' => 'South Korea', 'local_name' => '대한민국' ],
    'KW' => [ 'iso' => 414, 'code' => 'KW', 'code3' => 'KWT', 'name' => Translation::translateForUser('Kuwait'), 'international_name' => 'Kuwait', 'local_name' => 'الكويت' ],
    'KG' => [ 'iso' => 417, 'code' => 'KG', 'code3' => 'KGZ', 'name' => Translation::translateForUser('Kyrgyzstan'), 'international_name' => 'Kyrgyzstan', 'local_name' => 'Кыргызстан' ],
    'LA' => [ 'iso' => 418, 'code' => 'LA', 'code3' => 'LAO', 'name' => Translation::translateForUser('Lao People\'s Democratic Republic'), 'international_name' => 'Laos', 'local_name' => 'ລາວ' ],
    'LV' => [ 'iso' => 428, 'code' => 'LV', 'code3' => 'LVA', 'name' => Translation::translateForUser('Latvia'), 'international_name' => 'Latvia', 'local_name' => 'Latvija' ],
    'LB' => [ 'iso' => 422, 'code' => 'LB', 'code3' => 'LBN', 'name' => Translation::translateForUser('Lebanon'), 'international_name' => 'Lebanon', 'local_name' => 'لبنان' ],
    'LS' => [ 'iso' => 426, 'code' => 'LS', 'code3' => 'LSO', 'name' => Translation::translateForUser('Lesotho'), 'international_name' => 'Lesotho', 'local_name' => 'Lesotho' ],
    'LR' => [ 'iso' => 430, 'code' => 'LR', 'code3' => 'LBR', 'name' => Translation::translateForUser('Liberia'), 'international_name' => 'Liberia', 'local_name' => 'Liberia' ],
    'LY' => [ 'iso' => 434, 'code' => 'LY', 'code3' => 'LBY', 'name' => Translation::translateForUser('Libyan Arab Jamahiriya'), 'international_name' => 'Libya', 'local_name' => 'ليبيا' ],
    'LI' => [ 'iso' => 438, 'code' => 'LI', 'code3' => 'LIE', 'name' => Translation::translateForUser('Liechtenstein'), 'international_name' => 'Liechtenstein', 'local_name' => 'Liechtenstein' ],
    'LT' => [ 'iso' => 440, 'code' => 'LT', 'code3' => 'LTU', 'name' => Translation::translateForUser('Lithuania'), 'international_name' => 'Lithuania', 'local_name' => 'Lietuva' ],
    'LU' => [ 'iso' => 442, 'code' => 'LU', 'code3' => 'LUX', 'name' => Translation::translateForUser('Luxembourg'), 'international_name' => 'Luxembourg', 'local_name' => 'Lëtzebuerg' ],
    'MO' => [ 'iso' => 446, 'code' => 'MO', 'code3' => 'MAC', 'name' => Translation::translateForUser('Macau'), 'international_name' => 'Macau', 'local_name' => '澳門' ],
    'MK' => [ 'iso' => 807, 'code' => 'MK', 'code3' => 'MKD', 'name' => Translation::translateForUser('Macedonia'), 'international_name' => 'North Macedonia', 'local_name' => 'Северна Македонија' ],
    'MG' => [ 'iso' => 450, 'code' => 'MG', 'code3' => 'MDG', 'name' => Translation::translateForUser('Madagascar'), 'international_name' => 'Madagascar', 'local_name' => 'Madagasikara' ],
    'MW' => [ 'iso' => 454, 'code' => 'MW', 'code3' => 'MWI', 'name' => Translation::translateForUser('Malawi'), 'international_name' => 'Malawi', 'local_name' => 'Malawi' ],
    'MY' => [ 'iso' => 458, 'code' => 'MY', 'code3' => 'MYS', 'name' => Translation::translateForUser('Malaysia'), 'international_name' => 'Malaysia', 'local_name' => 'Malaysia' ],
    'MV' => [ 'iso' => 462, 'code' => 'MV', 'code3' => 'MDV', 'name' => Translation::translateForUser('Maldives'), 'international_name' => 'Maldives', 'local_name' => 'ދިވެހިރާޖެ' ],
    'ML' => [ 'iso' => 466, 'code' => 'ML', 'code3' => 'MLI', 'name' => Translation::translateForUser('Mali'), 'international_name' => 'Mali', 'local_name' => 'Mali' ],
    'MT' => [ 'iso' => 470, 'code' => 'MT', 'code3' => 'MLT', 'name' => Translation::translateForUser('Malta'), 'international_name' => 'Malta', 'local_name' => 'Malta' ],
    'MH' => [ 'iso' => 584, 'code' => 'MH', 'code3' => 'MHL', 'name' => Translation::translateForUser('Marshall Islands'), 'international_name' => 'Marshall Islands', 'local_name' => 'Aolepān Aorōkin M̧ajeļ' ],
    'MQ' => [ 'iso' => 474, 'code' => 'MQ', 'code3' => 'MTQ', 'name' => Translation::translateForUser('Martinique'), 'international_name' => 'Martinique', 'local_name' => 'Martinique' ],
    'MR' => [ 'iso' => 478, 'code' => 'MR', 'code3' => 'MRT', 'name' => Translation::translateForUser('Mauritania'), 'international_name' => 'Mauritania', 'local_name' => 'موريتانيا' ],
    'MU' => [ 'iso' => 480, 'code' => 'MU', 'code3' => 'MUS', 'name' => Translation::translateForUser('Mauritius'), 'international_name' => 'Mauritius', 'local_name' => 'Maurice' ],
    'YT' => [ 'iso' => 175, 'code' => 'YT', 'code3' => 'MYT', 'name' => Translation::translateForUser('Mayotte'), 'international_name' => 'Mayotte', 'local_name' => 'Mayotte' ],
    'MX' => [ 'iso' => 484, 'code' => 'MX', 'code3' => 'MEX', 'name' => Translation::translateForUser('Mexico'), 'international_name' => 'Mexico', 'local_name' => 'México' ],
    'FM' => [ 'iso' => 583, 'code' => 'FM', 'code3' => 'FSM', 'name' => Translation::translateForUser('Micronesia, Federated States Of'), 'international_name' => 'Micronesia', 'local_name' => 'Micronesia' ],
    'MD' => [ 'iso' => 498, 'code' => 'MD', 'code3' => 'MDA', 'name' => Translation::translateForUser('Moldova, Republic Of'), 'international_name' => 'Moldova', 'local_name' => 'Moldova' ],
    'MC' => [ 'iso' => 492, 'code' => 'MC', 'code3' => 'MCO', 'name' => Translation::translateForUser('Monaco'), 'international_name' => 'Monaco', 'local_name' => 'Monaco' ],
    'MN' => [ 'iso' => 496, 'code' => 'MN', 'code3' => 'MNG', 'name' => Translation::translateForUser('Mongolia'), 'international_name' => 'Mongolia', 'local_name' => 'Монгол улс' ],
    'MS' => [ 'iso' => 500, 'code' => 'MS', 'code3' => 'MSR', 'name' => Translation::translateForUser('Montserrat'), 'international_name' => 'Montserrat', 'local_name' => 'Montserrat' ],
    'MA' => [ 'iso' => 504, 'code' => 'MA', 'code3' => 'MAR', 'name' => Translation::translateForUser('Morocco'), 'international_name' => 'Morocco', 'local_name' => 'المغرب' ],
    'MZ' => [ 'iso' => 508, 'code' => 'MZ', 'code3' => 'MOZ', 'name' => Translation::translateForUser('Mozambique'), 'international_name' => 'Mozambique', 'local_name' => 'Moçambique' ],
    'MM' => [ 'iso' => 104, 'code' => 'MM', 'code3' => 'MMR', 'name' => Translation::translateForUser('Myanmar'), 'international_name' => 'Myanmar', 'local_name' => 'မြန်မာ' ],
    'NA' => [ 'iso' => 516, 'code' => 'NA', 'code3' => 'NAM', 'name' => Translation::translateForUser('Namibia'), 'international_name' => 'Namibia', 'local_name' => 'Namibia' ],
    'NR' => [ 'iso' => 520, 'code' => 'NR', 'code3' => 'NRU', 'name' => Translation::translateForUser('Nauru'), 'international_name' => 'Nauru', 'local_name' => 'Naoero' ],
    'NP' => [ 'iso' => 524, 'code' => 'NP', 'code3' => 'NPL', 'name' => Translation::translateForUser('Nepal'), 'international_name' => 'Nepal', 'local_name' => 'नेपाल' ],
    'NL' => [ 'iso' => 528, 'code' => 'NL', 'code3' => 'NLD', 'name' => Translation::translateForUser('Netherlands'), 'international_name' => 'Netherlands', 'local_name' => 'Nederland' ],
    'AN' => [ 'iso' => 530, 'code' => 'AN', 'code3' => 'ANT', 'name' => Translation::translateForUser('Netherlands Antilles'), 'international_name' => 'Netherlands Antilles', 'local_name' => 'Nederlandse Antillen' ],
    'NC' => [ 'iso' => 540, 'code' => 'NC', 'code3' => 'NCL', 'name' => Translation::translateForUser('New Caledonia'), 'international_name' => 'New Caledonia', 'local_name' => 'Nouvelle-Calédonie' ],
    'NZ' => [ 'iso' => 554, 'code' => 'NZ', 'code3' => 'NZL', 'name' => Translation::translateForUser('New Zealand'), 'international_name' => 'New Zealand', 'local_name' => 'Aotearoa' ],
    'NI' => [ 'iso' => 558, 'code' => 'NI', 'code3' => 'NIC', 'name' => Translation::translateForUser('Nicaragua'), 'international_name' => 'Nicaragua', 'local_name' => 'Nicaragua' ],
    'NE' => [ 'iso' => 562, 'code' => 'NE', 'code3' => 'NER', 'name' => Translation::translateForUser('Niger'), 'international_name' => 'Niger', 'local_name' => 'Niger' ],
    'NG' => [ 'iso' => 566, 'code' => 'NG', 'code3' => 'NGA', 'name' => Translation::translateForUser('Nigeria'), 'international_name' => 'Nigeria', 'local_name' => 'Nigeria' ],
    'NU' => [ 'iso' => 570, 'code' => 'NU', 'code3' => 'NIU', 'name' => Translation::translateForUser('Niue'), 'international_name' => 'Niue', 'local_name' => 'Niuē' ],
    'NF' => [ 'iso' => 574, 'code' => 'NF', 'code3' => 'NFK', 'name' => Translation::translateForUser('Norfolk Island'), 'international_name' => 'Norfolk Island', 'local_name' => 'Norfolk Island' ],
    'MP' => [ 'iso' => 580, 'code' => 'MP', 'code3' => 'MNP', 'name' => Translation::translateForUser('Northern Mariana Islands'), 'international_name' => 'Northern Mariana Islands', 'local_name' => 'Sankattan Siha Na Islas Mariånas' ],
    'NO' => [ 'iso' => 578, 'code' => 'NO', 'code3' => 'NOR', 'name' => Translation::translateForUser('Norway'), 'international_name' => 'Norway', 'local_name' => 'Norge' ],
    'OM' => [ 'iso' => 512, 'code' => 'OM', 'code3' => 'OMN', 'name' => Translation::translateForUser('Oman'), 'international_name' => 'Oman', 'local_name' => 'عُمان' ],
    'PK' => [ 'iso' => 586, 'code' => 'PK', 'code3' => 'PAK', 'name' => Translation::translateForUser('Pakistan'), 'international_name' => 'Pakistan', 'local_name' => 'پاکستان' ],
    'PW' => [ 'iso' => 585, 'code' => 'PW', 'code3' => 'PLW', 'name' => Translation::translateForUser('Palau'), 'international_name' => 'Palau', 'local_name' => 'Belau' ],
    'PA' => [ 'iso' => 591, 'code' => 'PA', 'code3' => 'PAN', 'name' => Translation::translateForUser('Panama'), 'international_name' => 'Panama', 'local_name' => 'Panamá' ],
    'PG' => [ 'iso' => 598, 'code' => 'PG', 'code3' => 'PNG', 'name' => Translation::translateForUser('Papua New Guinea'), 'international_name' => 'Papua New Guinea', 'local_name' => 'Papua Niugini' ],
    'PY' => [ 'iso' => 600, 'code' => 'PY', 'code3' => 'PRY', 'name' => Translation::translateForUser('Paraguay'), 'international_name' => 'Paraguay', 'local_name' => 'Paraguái' ],
    'PE' => [ 'iso' => 604, 'code' => 'PE', 'code3' => 'PER', 'name' => Translation::translateForUser('Peru'), 'international_name' => 'Peru', 'local_name' => 'Perú' ],
    'PH' => [ 'iso' => 608, 'code' => 'PH', 'code3' => 'PHL', 'name' => Translation::translateForUser('Philippines'), 'international_name' => 'Philippines', 'local_name' => 'Pilipinas' ],
    'PN' => [ 'iso' => 612, 'code' => 'PN', 'code3' => 'PCN', 'name' => Translation::translateForUser('Pitcairn'), 'international_name' => 'Pitcairn Islands', 'local_name' => 'Pitcairn Islands' ],
    'PL' => [ 'iso' => 616, 'code' => 'PL', 'code3' => 'POL', 'name' => Translation::translateForUser('Poland'), 'international_name' => 'Poland', 'local_name' => 'Polska' ],
    'PT' => [ 'iso' => 620, 'code' => 'PT', 'code3' => 'PRT', 'name' => Translation::translateForUser('Portugal'), 'international_name' => 'Portugal', 'local_name' => 'Portugal' ],
    'PR' => [ 'iso' => 630, 'code' => 'PR', 'code3' => 'PRI', 'name' => Translation::translateForUser('Puerto Rico'), 'international_name' => 'Puerto Rico', 'local_name' => 'Puerto Rico' ],
    'QA' => [ 'iso' => 634, 'code' => 'QA', 'code3' => 'QAT', 'name' => Translation::translateForUser('Qatar'), 'international_name' => 'Qatar', 'local_name' => 'قطر' ],
    'RE' => [ 'iso' => 638, 'code' => 'RE', 'code3' => 'REU', 'name' => Translation::translateForUser('Reunion'), 'international_name' => 'Reunion', 'local_name' => 'La Réunion' ],
    'RO' => [ 'iso' => 642, 'code' => 'RO', 'code3' => 'ROM', 'name' => Translation::translateForUser('Romania'), 'international_name' => 'Romania', 'local_name' => 'România' ],
    'RU' => [ 'iso' => 643, 'code' => 'RU', 'code3' => 'RUS', 'name' => Translation::translateForUser('Russian Federation'), 'international_name' => 'Russian Federation', 'local_name' => 'Россия' ],
    'RW' => [ 'iso' => 646, 'code' => 'RW', 'code3' => 'RWA', 'name' => Translation::translateForUser('Rwanda'), 'international_name' => 'Rwanda', 'local_name' => 'Rwanda' ],
    'KN' => [ 'iso' => 659, 'code' => 'KN', 'code3' => 'KNA', 'name' => Translation::translateForUser('Saint Kitts And Nevis'), 'international_name' => 'Saint Kitts and Nevis', 'local_name' => 'Saint Kitts and Nevis' ],
    'LC' => [ 'iso' => 662, 'code' => 'LC', 'code3' => 'LCA', 'name' => Translation::translateForUser('Saint Lucia'), 'international_name' => 'Saint Lucia', 'local_name' => 'Saint Lucia' ],
    'VC' => [ 'iso' => 670, 'code' => 'VC', 'code3' => 'VCT', 'name' => Translation::translateForUser('Saint Vincent And The Grenadines'), 'international_name' => 'Saint Vincent and the Grenadines', 'local_name' => 'Saint Vincent and the Grenadines' ],
    'WS' => [ 'iso' => 882, 'code' => 'WS', 'code3' => 'WSM', 'name' => Translation::translateForUser('Samoa'), 'international_name' => 'Samoa', 'local_name' => 'Sāmoa' ],
    'SM' => [ 'iso' => 674, 'code' => 'SM', 'code3' => 'SMR', 'name' => Translation::translateForUser('San Marino'), 'international_name' => 'San Marino', 'local_name' => 'San Marino' ],
    'ST' => [ 'iso' => 678, 'code' => 'ST', 'code3' => 'STP', 'name' => Translation::translateForUser('Sao Tome And Principe'), 'international_name' => 'Sao Tome and Principe', 'local_name' => 'São Tomé e Príncipe' ],
    'SA' => [ 'iso' => 682, 'code' => 'SA', 'code3' => 'SAU', 'name' => Translation::translateForUser('Saudi Arabia'), 'international_name' => 'Saudi Arabia', 'local_name' => 'السعودية' ],
    'SN' => [ 'iso' => 686, 'code' => 'SN', 'code3' => 'SEN', 'name' => Translation::translateForUser('Senegal'), 'international_name' => 'Senegal', 'local_name' => 'Sénégal' ],
    'SC' => [ 'iso' => 690, 'code' => 'SC', 'code3' => 'SYC', 'name' => Translation::translateForUser('Seychelles'), 'international_name' => 'Seychelles', 'local_name' => 'Seychelles' ],
    'SL' => [ 'iso' => 694, 'code' => 'SL', 'code3' => 'SLE', 'name' => Translation::translateForUser('Sierra Leone'), 'international_name' => 'Sierra Leone', 'local_name' => 'Sierra Leone' ],
    'SG' => [ 'iso' => 702, 'code' => 'SG', 'code3' => 'SGP', 'name' => Translation::translateForUser('Singapore'), 'international_name' => 'Singapore', 'local_name' => 'Singapura' ],
    'SK' => [ 'iso' => 703, 'code' => 'SK', 'code3' => 'SVK', 'name' => Translation::translateForUser('Slovakia'), 'international_name' => 'Slovakia', 'local_name' => 'Slovensko' ],
    'SI' => [ 'iso' => 705, 'code' => 'SI', 'code3' => 'SVN', 'name' => Translation::translateForUser('Slovenia'), 'international_name' => 'Slovenia', 'local_name' => 'Slovenija' ],
    'SB' => [ 'iso' => 90, 'code' => 'SB', 'code3' => 'SLB', 'name' => Translation::translateForUser('Solomon Islands'), 'international_name' => 'Solomon Islands', 'local_name' => 'Solomon Islands' ],
    'SO' => [ 'iso' => 706, 'code' => 'SO', 'code3' => 'SOM', 'name' => Translation::translateForUser('Somalia'), 'international_name' => 'Somalia', 'local_name' => 'Soomaaliya' ],
    'ZA' => [ 'iso' => 710, 'code' => 'ZA', 'code3' => 'ZAF', 'name' => Translation::translateForUser('South Africa'), 'international_name' => 'South Africa', 'local_name' => 'Suid-Afrika' ],
    'GS' => [ 'iso' => 239, 'code' => 'GS', 'code3' => 'SGS', 'name' => Translation::translateForUser('South Georgia Islands'), 'international_name' => 'South Georgia and the South Sandwich Islands', 'local_name' => 'South Georgia and the South Sandwich Islands' ],
    'ES' => [ 'iso' => 724, 'code' => 'ES', 'code3' => 'ESP', 'name' => Translation::translateForUser('Spain'), 'international_name' => 'Spain', 'local_name' => 'España' ],
    'LK' => [ 'iso' => 144, 'code' => 'LK', 'code3' => 'LKA', 'name' => Translation::translateForUser('Sri Lanka'), 'international_name' => 'Sri Lanka', 'local_name' => 'ශ්‍රී ලංකාව' ],
    'SH' => [ 'iso' => 654, 'code' => 'SH', 'code3' => 'SHN', 'name' => Translation::translateForUser('St. Helena'), 'international_name' => 'Saint Helena', 'local_name' => 'Saint Helena' ],
    'PM' => [ 'iso' => 666, 'code' => 'PM', 'code3' => 'SPM', 'name' => Translation::translateForUser('St. Pierre And Miquelon'), 'international_name' => 'Saint Pierre and Miquelon', 'local_name' => 'Saint-Pierre-et-Miquelon' ],
    'SD' => [ 'iso' => 736, 'code' => 'SD', 'code3' => 'SDN', 'name' => Translation::translateForUser('Sudan'), 'international_name' => 'Sudan', 'local_name' => 'السودان' ],
    'SR' => [ 'iso' => 740, 'code' => 'SR', 'code3' => 'SUR', 'name' => Translation::translateForUser('Suriname'), 'international_name' => 'Suriname', 'local_name' => 'Suriname' ],
    'SJ' => [ 'iso' => 744, 'code' => 'SJ', 'code3' => 'SJM', 'name' => Translation::translateForUser('Svalbard And Jan Mayen Islands'), 'international_name' => 'Svalbard and Jan Mayen', 'local_name' => 'Svalbard og Jan Mayen' ],
    'SZ' => [ 'iso' => 748, 'code' => 'SZ', 'code3' => 'SWZ', 'name' => Translation::translateForUser('Swaziland'), 'international_name' => 'Eswatini', 'local_name' => 'eSwatini' ],
    'SE' => [ 'iso' => 752, 'code' => 'SE', 'code3' => 'SWE', 'name' => Translation::translateForUser('Sweden'), 'international_name' => 'Sweden', 'local_name' => 'Sverige' ],
    'CH' => [ 'iso' => 756, 'code' => 'CH', 'code3' => 'CHE', 'name' => Translation::translateForUser('Switzerland'), 'international_name' => 'Switzerland', 'local_name' => 'Schweiz / Suisse / Svizzera' ],
    'SY' => [ 'iso' => 760, 'code' => 'SY', 'code3' => 'SYR', 'name' => Translation::translateForUser('Syrian Arab Republic'), 'international_name' => 'Syria', 'local_name' => 'سوريا' ],
    'TW' => [ 'iso' => 158, 'code' => 'TW', 'code3' => 'TWN', 'name' => Translation::translateForUser('Taiwan, Province Of China'), 'international_name' => 'Taiwan', 'local_name' => '臺灣' ],
    'TJ' => [ 'iso' => 762, 'code' => 'TJ', 'code3' => 'TJK', 'name' => Translation::translateForUser('Tajikistan'), 'international_name' => 'Tajikistan', 'local_name' => 'Тоҷикистон' ],
    'TZ' => [ 'iso' => 834, 'code' => 'TZ', 'code3' => 'TZA', 'name' => Translation::translateForUser('Tanzania, United Republic Of'), 'international_name' => 'Tanzania', 'local_name' => 'Tanzania' ],
    'TH' => [ 'iso' => 764, 'code' => 'TH', 'code3' => 'THA', 'name' => Translation::translateForUser('Thailand'), 'international_name' => 'Thailand', 'local_name' => 'ประเทศไทย' ],
    'TG' => [ 'iso' => 768, 'code' => 'TG', 'code3' => 'TGO', 'name' => Translation::translateForUser('Togo'), 'international_name' => 'Togo', 'local_name' => 'Togo' ],
    'TK' => [ 'iso' => 772, 'code' => 'TK', 'code3' => 'TKL', 'name' => Translation::translateForUser('Tokelau'), 'international_name' => 'Tokelau', 'local_name' => 'Tokelau' ],
    'TO' => [ 'iso' => 776, 'code' => 'TO', 'code3' => 'TON', 'name' => Translation::translateForUser('Tonga'), 'international_name' => 'Tonga', 'local_name' => 'Tonga' ],
    'TT' => [ 'iso' => 780, 'code' => 'TT', 'code3' => 'TTO', 'name' => Translation::translateForUser('Trinidad And Tobago'), 'international_name' => 'Trinidad and Tobago', 'local_name' => 'Trinidad and Tobago' ],
    'TN' => [ 'iso' => 788, 'code' => 'TN', 'code3' => 'TUN', 'name' => Translation::translateForUser('Tunisia'), 'international_name' => 'Tunisia', 'local_name' => 'تونس' ],
    'TR' => [ 'iso' => 792, 'code' => 'TR', 'code3' => 'TUR', 'name' => Translation::translateForUser('Turkey'), 'international_name' => 'Turkey', 'local_name' => 'Türkiye' ],
    'TM' => [ 'iso' => 795, 'code' => 'TM', 'code3' => 'TKM', 'name' => Translation::translateForUser('Turkmenistan'), 'international_name' => 'Turkmenistan', 'local_name' => 'Türkmenistan' ],
    'TC' => [ 'iso' => 796, 'code' => 'TC', 'code3' => 'TCA', 'name' => Translation::translateForUser('Turks And Caicos Islands'), 'international_name' => 'Turks and Caicos Islands', 'local_name' => 'Turks and Caicos Islands' ],
    'TV' => [ 'iso' => 798, 'code' => 'TV', 'code3' => 'TUV', 'name' => Translation::translateForUser('Tuvalu'), 'international_name' => 'Tuvalu', 'local_name' => 'Tuvalu' ],
    'UG' => [ 'iso' => 800, 'code' => 'UG', 'code3' => 'UGA', 'name' => Translation::translateForUser('Uganda'), 'international_name' => 'Uganda', 'local_name' => 'Uganda' ],
    'UA' => [ 'iso' => 804, 'code' => 'UA', 'code3' => 'UKR', 'name' => Translation::translateForUser('Ukraine'), 'international_name' => 'Ukraine', 'local_name' => 'Україна' ],
    'AE' => [ 'iso' => 784, 'code' => 'AE', 'code3' => 'ARE', 'name' => Translation::translateForUser('United Arab Emirates'), 'international_name' => 'United Arab Emirates', 'local_name' => 'الإمارات العربية المتحدة' ],
    'GB' => [ 'iso' => 826, 'code' => 'GB', 'code3' => 'GBR', 'name' => Translation::translateForUser('United Kingdom'), 'international_name' => 'United Kingdom', 'local_name' => 'United Kingdom' ],
    'US' => [ 'iso' => 840, 'code' => 'US', 'code3' => 'USA', 'name' => Translation::translateForUser('United States'), 'international_name' => 'United States', 'local_name' => 'United States' ],
    'UM' => [ 'iso' => 581, 'code' => 'UM', 'code3' => 'UMI', 'name' => Translation::translateForUser('United States Minor Outlying Islands'), 'international_name' => 'United States Minor Outlying Islands', 'local_name' => 'United States Minor Outlying Islands' ],
    'UY' => [ 'iso' => 858, 'code' => 'UY', 'code3' => 'URY', 'name' => Translation::translateForUser('Uruguay'), 'international_name' => 'Uruguay', 'local_name' => 'Uruguay' ],
    'UZ' => [ 'iso' => 860, 'code' => 'UZ', 'code3' => 'UZB', 'name' => Translation::translateForUser('Uzbekistan'), 'international_name' => 'Uzbekistan', 'local_name' => 'O\'zbekiston' ],
    'VU' => [ 'iso' => 548, 'code' => 'VU', 'code3' => 'VUT', 'name' => Translation::translateForUser('Vanuatu'), 'international_name' => 'Vanuatu', 'local_name' => 'Vanuatu' ],
    'VA' => [ 'iso' => 336, 'code' => 'VA', 'code3' => 'VAT', 'name' => Translation::translateForUser('Vatican City State'), 'international_name' => 'Vatican City', 'local_name' => 'Città del Vaticano' ],
    'VE' => [ 'iso' => 862, 'code' => 'VE', 'code3' => 'VEN', 'name' => Translation::translateForUser('Venezuela'), 'international_name' => 'Venezuela', 'local_name' => 'Venezuela' ],
    'VN' => [ 'iso' => 704, 'code' => 'VN', 'code3' => 'VNM', 'name' => Translation::translateForUser('Viet Nam'), 'international_name' => 'Vietnam', 'local_name' => 'Việt Nam' ],
    'VG' => [ 'iso' => 92, 'code' => 'VG', 'code3' => 'VGB', 'name' => Translation::translateForUser('Virgin Islands (british)'), 'international_name' => 'British Virgin Islands', 'local_name' => 'British Virgin Islands' ],
    'VI' => [ 'iso' => 850, 'code' => 'VI', 'code3' => 'VIR', 'name' => Translation::translateForUser('Virgin Islands (u.s.)'), 'international_name' => 'United States Virgin Islands', 'local_name' => 'United States Virgin Islands' ],
    'WF' => [ 'iso' => 876, 'code' => 'WF', 'code3' => 'WLF', 'name' => Translation::translateForUser('Wallis And Futuna Islands'), 'international_name' => 'Wallis and Futuna', 'local_name' => 'Wallis-et-Futuna' ],
    'EH' => [ 'iso' => 732, 'code' => 'EH', 'code3' => 'ESH', 'name' => Translation::translateForUser('Western Sahara'), 'international_name' => 'Western Sahara', 'local_name' => 'الصحراء الغربية' ],
    'YE' => [ 'iso' => 887, 'code' => 'YE', 'code3' => 'YEM', 'name' => Translation::translateForUser('Yemen'), 'international_name' => 'Yemen', 'local_name' => 'اليمن' ],
    'RS' => [ 'iso' => 688, 'code' => 'RS', 'code3' => 'RSB', 'name' => Translation::translateForUser('Serbia'), 'international_name' => 'Serbia', 'local_name' => 'Србија' ],
    'ZR' => [ 'iso' => 180, 'code' => 'ZR', 'code3' => 'ZAR', 'name' => Translation::translateForUser('Zaire'), 'international_name' => 'Zaire', 'local_name' => 'Zaïre' ],
    'ZM' => [ 'iso' => 894, 'code' => 'ZM', 'code3' => 'ZMB', 'name' => Translation::translateForUser('Zambia'), 'international_name' => 'Zambia', 'local_name' => 'Zambia' ],
    'ZW' => [ 'iso' => 716, 'code' => 'ZW', 'code3' => 'ZWE', 'name' => Translation::translateForUser('Zimbabwe'), 'international_name' => 'Zimbabwe', 'local_name' => 'Zimbabwe' ],
    'ME' => [ 'iso' => 499, 'code' => 'ME', 'code3' => 'MNE', 'name' => Translation::translateForUser('Montenegro'), 'international_name' => 'Montenegro', 'local_name' => 'Crna Gora' ],
    'IC' => [ 'iso' => 724, 'code' => 'IC', 'code3' => 'CNR', 'name' => Translation::translateForUser('Canary Islands'), 'international_name' => 'Canary Islands', 'local_name' => 'Islas Canarias' ],
    'CD' => [ 'iso' => 180, 'code' => 'CD', 'code3' => 'COD', 'name' => Translation::translateForUser('Congo - Dem.republic'), 'international_name' => 'Democratic Republic of the Congo', 'local_name' => 'République démocratique du Congo' ],
    'XK' => [ 'iso' => 0, 'code' => 'XK', 'code3' => 'XKX', 'name' => Translation::translateForUser('Kosovo'), 'international_name' => 'Kosovo', 'local_name' => 'Kosova' ],
    'JE' => [ 'iso' => 832, 'code' => 'JE', 'code3' => 'JEY', 'name' => Translation::translateForUser('Jersey'), 'international_name' => 'Jersey', 'local_name' => 'Jèrri' ],
    'GG' => [ 'iso' => 831, 'code' => 'GG', 'code3' => 'GGY', 'name' => Translation::translateForUser('Guernsey'), 'international_name' => 'Guernsey', 'local_name' => 'Guernesey' ],
];
