<?php

namespace statikbe\export\variables;

use craft\models\Section;
use craft\services\Sections;
use statikbe\export\Export;

use Craft;

/**
 * @author    Statik
 * @package   Explore
 * @since     1.0.0
 */
class ExportVariable
{
    private $_elementTypesByRefHandle = [];

    public function getSelectOptions($options, $label = 'name', $index = 'id', $includeNone = true)
    {
        $values = [];
        if ($includeNone) {
            if (is_string($includeNone)) {
                $values[''] = $includeNone;
            } else {
                $values[''] = 'None';
            }
        }

        if (is_array($options)) {
            foreach ($options as $key => $value) {
                $values[$value[$index]] = $value[$label];
            }
        }
        return $values;
    }
}
