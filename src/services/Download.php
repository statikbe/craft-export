<?php

namespace statikbe\export\services;

use Craft;
use craft\base\Component;
use craft\elements\Category;
use craft\elements\Entry;
use craft\elements\User;
use craft\helpers\Json;

class Download extends Component
{
    public function downloadExport($export, $path)
    {
        if ($export->exportType == 'csv') {
            return $this->exportToCsv($export, $path);
        } elseif ($export->exportType == 'json') {
            return $this->exportToJson($export, $path);
        } else {
            Craft::$app->session->setError(Craft::t('export', 'Unable to export: export type not found.'));
            return null;
        }
    }

    private function exportToCsv($export, $path)
    {
        if ($export->filename) {
            $fileName = $export->filename . '-' . date('U');
        } else {
            $fileName = 'export-' . date('U');
        }

        $settings = Json::decode($export->settings);

        $exportData = [];
        $headings = [];

        // fill array with elementFields
        if (isset($settings['elementData'])) {
            foreach ($settings['elementData'] as $handle => $selected) {
                if ($selected) {
                    array_push($headings, $handle);
                }
            }
        }

        // fill array with custom fields
        if (isset($settings['customData'])) {
            foreach ($settings['customData'] as $handle => $selected) {
                if ($selected) {
                    $field = Craft::$app->fields->getFieldByHandle($handle);
                    if ($field) {
                        array_push($headings, $field->name);
                    }
                }
            }
        }

        $exportData[] = $headings;

        switch ($export->elementType) {
            case 'craft\elements\Entry':
                $elements = Entry::find()->sectionId($export->elementGroup[$export->elementType]['section'])->typeId($export->elementGroup[$export->elementType]['entryType']);
                break;
            case 'craft\elements\User':
                $elements = User::find()->groupId($export->elementGroup[$export->elementType]);
                break;
            case 'craft\elements\Category':
                $elements = Category::find()->groupId($export->elementGroup[$export->elementType]);
        }

        if (isset($settings['filterData'])) {
            // filter on status
            if (isset($settings['filterData']['status'])) {
                $elements->status($settings['filterData']['status']);
            }

            // order elements
            if (isset($settings['filterData']['sortBy'])) {
                if (isset($settings['filterData']['sortByAsc'])) {
                    $elements->orderBy($settings['filterData']['sortBy'] . ' ' . $settings['filterData']['sortByAsc']);
                } else {
                    $elements->orderBy($settings['filterData']['sortBy']);
                }
            }

            // limit
            if (isset($settings['filterData']['limit']) and !empty($settings['filterData']['limit'])) {
                $elements->limit($settings['filterData']['limit']);
            }
        }

        if ($elements) {
            foreach ($elements->all() as $element) {
                $row = [];

                // fill array with elementFields
                if (isset($settings['elementData'])) {
                    foreach ($settings['elementData'] as $handle => $selected) {
                        if ($selected) {
                            $value = $this->getElementFieldValue($handle, $element);
                            array_push($row, $value);
                        }
                    }
                }

                // fill array with custom fields
                if (isset($settings['customData'])) {
                    foreach ($settings['customData'] as $handle => $selected) {
                        if ($selected) {
                            $field = Craft::$app->fields->getFieldByHandle($handle);
                            $value = $this->getFieldValue($field, $handle, $element);
                            array_push($row, $value);
                        }
                    }
                }

                $exportData[] = $row;
            }

            $file = $this->array_to_csv($exportData, $fileName, $path);
            return $file;
        } else {
            Craft::$app->session->setError(Craft::t('export', 'Unable to export: no elements found.'));
            return null;
        }
    }

    private function exportToJson($export, $path)
    {
        if ($export->filename) {
            $fileName = $export->filename . '-' . date('U');
        } else {
            $fileName = 'export-' . date('U');
        }

        $settings = Json::decode($export->settings);

        $exportData = [];

        switch ($export->elementType) {
            case 'craft\elements\Entry':
                $elements = Entry::find()->sectionId($export->elementGroup[$export->elementType]['section'])->typeId($export->elementGroup[$export->elementType]['entryType']);
                break;
            case 'craft\elements\User':
                $elements = User::find()->groupId($export->elementGroup[$export->elementType]);
                break;
            case 'craft\elements\Category':
                $elements = Category::find()->groupId($export->elementGroup[$export->elementType]);
        }

        if (isset($settings['filterData'])) {
            if (isset($settings['filterData']['sortBy'])) {
                $elements->orderBy($settings['filterData']['sortBy']);
            }
            if (isset($settings['filterData']['status'])) {
                $elements->status($settings['filterData']['status']);
            }
        }

        if ($elements) {
            foreach ($elements->all() as $element) {
                $row = [];

                // fill array with elementFields
                if (isset($settings['elementData'])) {
                    foreach ($settings['elementData'] as $handle => $selected) {
                        if ($selected) {
                            $value = $this->getElementFieldValue($handle, $element);
                            $row[$handle] = $value;
                        }
                    }
                }

                // fill array with custom fields
                if (isset($settings['customData'])) {
                    foreach ($settings['customData'] as $handle => $selected) {
                        if ($selected) {
                            $field = Craft::$app->fields->getFieldByHandle($handle);
                            $value = $this->getFieldValue($field, $handle, $element);
                            $row[$handle] = $value;
                        }
                    }
                }

                $exportData[] = $row;
            }

            $file = $this->array_to_json($exportData, $fileName, $path);
            return $file;
        } else {
            Craft::$app->session->setError(Craft::t('export', 'Unable to export: no elements found.'));
            return null;
        }
    }

    private function getElementFieldValue($handle, $element)
    {
        if ($handle == 'id' or $handle == 'title' or $handle == 'firstName' or $handle == 'lastName' or $handle == 'email' or $handle == 'status') {
            return $element->$handle;
        } elseif ($handle == 'author') {
            return $element->$handle->email ?? '';
        } elseif ($handle == 'postDate' or $handle == 'dateCreated' or $handle == 'dateUpdated' or $handle == 'lastLoginDate' or $handle == 'expiryDate') {
            if ($element->$handle) {
                return $element->$handle->format('d/m/Y H:i');
            } else {
                return '';
            }
        } else {
            return '-';
        }
    }

    private function getFieldValue($field, $handle, $element)
    {
        $displayName = $field::valueType();
        if ($displayName == 'string|null' or $displayName == 'mixed' or $displayName == 'craft\fields\data\ColorData|null' or $displayName == 'int|float|null') {
            return $element->$handle;
        } elseif ($displayName == 'bool') {
            return $element->$handle == 1 ? 1 : 0;
        } elseif ($displayName == 'craft\fields\data\SingleOptionFieldData') {
            return $element->$handle ? $element->$handle->label : '';
        } elseif ($displayName == 'craft\elements\db\AssetQuery') {
            $assets = $element->$handle->all();
            $value = [];
            foreach ($assets as $asset) {
                array_push($value, $asset->filename);
            }
            return implode(', ', $value);
        } elseif ($displayName == 'craft\elements\db\EntryQuery' or $displayName == 'craft\elements\db\CategoryQuery' or $displayName == 'craft\elements\db\TagQuery') {
            $items = $element->$handle->all();
            $value = [];
            foreach ($items as $item) {
                array_push($value, $item->title);
            }
            return implode(', ', $value);
        } elseif ($displayName == 'craft\elements\db\UserQuery') {
            $users = $element->$handle->all();
            $value = [];
            foreach ($users as $user) {
                array_push($value, $user->email);
            }
            return implode(', ', $value);
        } elseif ($displayName == 'craft\fields\data\MultiOptionsFieldData') {
            $options = $element->$handle->getOptions();
            $value = [];
            foreach ($options as $option) {
                if ($option->selected == true) {
                    array_push($value, $option->label);
                }
            }
            return implode(', ', $value);
        } elseif ($displayName == 'DateTime|null') {
            if ($element->$handle) {
                return $element->$handle->format('d/m/Y H:i');
            } else {
                return '';
            }
        } elseif ($displayName == 'array|null') {
            return json_encode($element->$handle);
        } elseif ($displayName == 'craft\elements\db\MatrixBlockQuery') {
            //d($element->$handle->all());
            //return json_encode($element->$handle->all());

            //foreach ($element->$handle->all() as $block) {
                //d($block);
                //foreach ($block['customFields'] as $subField) {
                    //d($subField);
                    //d($this->getFieldValue($subField, $subField->handle, $element->$handle));
                //}
            //}

            return 'Matrix fields are not supported';

        } else {
            return 'Field Type not found';
        }
    }

    private function array_to_csv($array, $filename = 'export', $path, $delimiter = ";")
    {
        $f = fopen($path . $filename . '.csv', 'ab+');

        foreach ($array as $line) {
            fputcsv($f, $line, $delimiter);
        }
        fclose($f);
        return $filename . '.csv';
    }

    private function array_to_json($array, $filename = 'export', $path, $delimiter = ";")
    {
        $f = fopen($path . $filename . '.json', 'w');
        fwrite($f, json_encode($array));
        fclose($f);
        return $filename . '.json';
    }
}