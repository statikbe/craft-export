<?php
/**
 * Export plugin for Craft CMS 3.x
 *
 * Export elements from Craft
 *
 * @link      https://statik.be
 * @copyright Copyright (c) 2019 Statik
 */

namespace statikbe\export\services;

use Craft;
use craft\base\Component;
use craft\elements\Category;
use craft\elements\Entry;
use craft\elements\User;
use craft\models\Section;


class Elements extends Component
{
    private $_elements = [];

    public function getElementTypes()
    {
        if (count($this->_elements)) {
            return $this->_elements;
        }

        $elements = [
            Entry::class => [
                'name' => Entry::displayName(),
                'groupsTemplate' => 'export/_includes/elements/entry/groups',
                'columnTemplate' => 'export/_includes/elements/entry/column',
                'groups' => $this->getSections(),
                'entryTypes' => Craft::$app->sections->getAllEntryTypes()
            ],
            User::class => [
                'name' => User::displayName(),
                'groupsTemplate' => 'export/_includes/elements/user/groups',
                'columnTemplate' => 'export/_includes/elements/user/column',
                'groups' => Craft::$app->userGroups->getAllGroups()
            ],
            Category::class => [
                'name' => Category::displayName(),
                'groupsTemplate' => 'export/_includes/elements/category/groups',
                'columnTemplate' => 'export/_includes/elements/category/column',
                'groups' => Craft::$app->categories->getAllGroups()
            ],
        ];

        return $elements;
    }

    public function getSections()
    {
        $channels = Craft::$app->sections->getSectionsByType(Section::TYPE_CHANNEL);
        $structures = Craft::$app->sections->getSectionsByType(Section::TYPE_STRUCTURE);
        $sections = array_merge($channels, $structures);
        return $sections;
    }

    public function getFieldsForElement($type, $group)
    {
        if ($type == "craft\\elements\\Entry") {
            $element = Craft::$app->sections->getEntryTypeById($group[$type]['entryType']);
        } elseif ($type == "craft\\elements\\Category") {
            $element = Craft::$app->categories->getGroupById($group[$type]);
        } elseif ($type == "craft\\elements\\User") {
            $element = Craft::$app->userGroups->getGroupById($group[$type]);
        }

        if (!empty($element->fieldLayoutId)) {
            return Craft::$app->fields->getFieldsByLayoutId($element->fieldLayoutId);
        } else {
            return [];
        }
    }
}
