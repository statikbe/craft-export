<?php
/**
 * Export plugin for Craft CMS 3.x
 *
 * Export elements from Craft
 *
 * @link      https://statik.be
 * @copyright Copyright (c) 2019 Statik
 */

namespace statikbe\export\models;

use statikbe\export\Export;

use Craft;
use craft\base\Model;

/**
 * ExportModel Model
 *
 * Models are containers for data. Just about every time information is passed
 * between services, controllers, and templates in Craft, it’s passed via a model.
 *
 * https://craftcms.com/docs/plugins/models
 *
 * @author    Statik
 * @package   Export
 * @since     1.0.0
 */
class ExportModel extends Model
{

    public $id;
    public $name;
    public $filename;
    public $exportType;
    public $elementType;
    public $elementGroup;
    public $siteId;
    public $sortOrder;
    public $settings;
    public $dateCreated;
    public $dateUpdated;
    public $uid;

    // Public Methods
    // =========================================================================

    /**
     * Returns the validation rules for attributes.
     *
     * Validation rules are used by [[validate()]] to check if attribute values are valid.
     * Child classes may override this method to declare different validation rules.
     *
     * More info: http://www.yiiframework.com/doc-2.0/guide-input-validation.html
     *
     * @return array
     */
    public function rules()
    {
        return [
            [['name', 'elementType', 'siteId'], 'required'],
        ];
    }

    public function getElementColumnTemplate()
    {
        return "export/_includes/elements/" . strtolower($this->getElementTypeDisplayName())  . "/column";
    }

    public function getElementTypeDisplayName()
    {
        $elementType = $this->elementType;
        return $elementType::displayName();
    }
}
