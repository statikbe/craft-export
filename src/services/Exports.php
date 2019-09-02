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
use craft\db\Query;
use craft\helpers\Json;
use Exception;
use statikbe\export\Export;
use statikbe\export\models\ExportModel;
use statikbe\export\records\ExportRecord;

class Exports extends Component
{

    public function getExports()
    {
        $query = $this->_getQuery();

        $results = $query->all();

        foreach ($results as $key => $result) {
            $results[$key] = $this->_createModelFromRecord($result);
        }

        return $results;
    }

    public function getExportById($exportId)
    {
        $result = $this->_getQuery()
            ->where(['id' => $exportId])
            ->one();

        return $this->_createModelFromRecord($result);
    }

    /**
     * @param ExportModel $model
     * @param bool $runValidation
     * @return bool
     * @throws \Exception
     */
    public function saveExport(ExportModel $model, $runValidation = true)
    {
        $isNewModel = !$model->id;

        if ($runValidation && !$model->validate()) {
            Craft::info('Export not saved due to validation error.', __METHOD__);
            return false;
        }

        if ($isNewModel) {
            $record = new ExportRecord();
        } else {
            $record = ExportRecord::findOne($model->id);

            if (!$record) {
                throw new \Exception(Craft::t('export', 'No export exists with the ID “{id}”', ['id' => $model->id]));
            }
        }

        $record->name = $model->name;
        $record->filename = $model->filename;
        $record->exportType = $model->exportType;
        $record->elementType = $model->elementType;
        $record->siteId = $model->siteId;
        $record->settings = $model->settings;

        if ($model->elementGroup) {
            $record->setAttribute('elementGroup', json_encode($model->elementGroup));
        }

        if ($isNewModel) {
            $maxSortOrder = (new Query())
                ->from([ExportRecord::tableName()])
                ->max('[[sortOrder]]');

            $record->sortOrder = $maxSortOrder ? $maxSortOrder + 1 : 1;
        }

        $record->save(false);

        if (!$model->id) {
            $model->id = $record->id;
        }

        return true;
    }

    /**
     * @param array $exportIds
     * @return bool
     * @throws \Throwable
     */
    public function reorderExports(array $exportIds): bool
    {
        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            foreach ($exportIds as $exportOrder => $exportId) {
                $exportRecord = $this->_getExportRecordById($exportId);
                $exportRecord->sortOrder = $exportOrder + 1;
                $exportRecord->save();
            }

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();

            throw $e;
        }

        return true;
    }

    public function deleteExportById($exportId)
    {
        return Craft::$app->getDb()->createCommand()
            ->delete(ExportRecord::tableName(), ['id' => $exportId])
            ->execute();
    }

    private function _getQuery()
    {
        return ExportRecord::find()
            ->select([
                'id',
                'name',
                'filename',
                'exportType',
                'elementType',
                'elementGroup',
                'siteId',
                'sortOrder',
                'settings',
                'dateCreated',
                'dateUpdated',
                'uid',
            ])
            ->orderBy(['sortOrder' => SORT_ASC]);
    }

    private function _createModelFromRecord(ExportRecord $record = null)
    {
        if (!$record) {
            return null;
        }

        $record['elementGroup'] = Json::decode($record['elementGroup']);

        $attributes = $record->toArray();


        return new ExportModel($attributes);
    }

    /**
     * @param int|null $exportId
     * @return ExportRecord
     * @throws Exception
     */
    private function _getExportRecordById($exportId = null)
    {
        if ($exportId !== null) {
            $exportRecord = ExportRecord::findOne(['id' => $exportId]);

            if (!$exportRecord) {
                throw new Exception(Craft::t('feed-me', 'No export exists with the ID “{id}”.', ['id' => $exportId]));
            }
        } else {
            $exportRecord = new ExportRecord();
        }

        return $exportRecord;
    }
}