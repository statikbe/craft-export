<?php
/**
 * Export plugin for Craft CMS 3.x
 *
 * Export elements from Craft
 *
 * @link      https://statik.be
 * @copyright Copyright (c) 2019 Statik
 */

namespace statikbe\export\controllers;

use statikbe\export\Export;

use Craft;
use craft\helpers\Json;
use craft\web\Controller;
use statikbe\export\models\ExportModel;
use statikbe\export\services\Download;
use statikbe\export\services\Elements;
use statikbe\export\services\Exports;
use Cocur\Slugify\Slugify;
use statikbe\export\services\Mail;

/**
 * https://craftcms.com/docs/plugins/controllers
 *
 * @author    Statik
 * @package   Export
 * @since     1.0.0
 */
class ExportController extends Controller
{

    protected $allowAnonymous = [];

    public function actionWelcome()
    {
        return $this->renderTemplate('export/_welcome');
    }

    public function actionOverview()
    {
        $variables['exports'] = Exports::instance()->getExports();

        return $this->renderTemplate('export/_overview', $variables);
    }

    public function actionEditExport($exportId = null)
    {
        $variables = [];

        if ($exportId) {
            $variables['export'] = Exports::instance()->getExportById($exportId);
        } else {
            $variables['export'] = new ExportModel();
        }

        $variables['elements'] = Elements::instance()->getElementTypes();
        $variables['exportTypes'] = [
            'csv' => 'CSV',
            'json' => 'JSON',
        ];

        return $this->renderTemplate('export/_edit', $variables);
    }

    public function actionEditSettings($exportId = null)
    {
        $variables = [];

        if ($exportId) {
            $export = Exports::instance()->getExportById($exportId);
            $variables['export'] = $export;
            $fields = Elements::instance()->getFieldsForElement($export->elementType, $export->elementGroup);
            $variables['fields'] = $fields;

            return $this->renderTemplate('export/_settings', $variables);
        } else {
            return $this->redirect('/admin/export/exports/new');
        }
    }

    public function actionExportReady($exportId = null)
    {
        $variables = [];

        if ($exportId) {
            $variables['export'] = Exports::instance()->getExportById($exportId);
            return $this->renderTemplate('export/_export', $variables);
        } else {
            return $this->redirect('/admin/export/exports/new');
        }
    }

    public function actionSaveExport()
    {
        $export = $this->_getModelFromPost();

        return $this->_saveAndRedirect($export, 'export/exports/', true, 'settings');
    }

    public function actionSaveSettings()
    {
        $this->requirePostRequest();
        $request = Craft::$app->getRequest();

        $export = Exports::instance()->getExportById($request->getBodyParam('exportId'));

        $fields = Craft::$app->request->getBodyParam('fields');
        $json = Json::encode($fields);
        $export->settings = $json;

        return $this->_saveAndRedirect($export, 'export/exports/', true, 'export');

    }

    public function actionReorderExports()
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $exportIds = Json::decode(Craft::$app->getRequest()->getRequiredBodyParam('ids'));
        $exportIds = array_filter($exportIds);
        Exports::instance()->reorderExports($exportIds);

        return $this->asJson(['success' => true]);
    }

    public function actionDeleteExport()
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();

        $exportId = $request->getRequiredBodyParam('id');

        Exports::instance()->deleteExportById($exportId);

        return $this->asJson(['success' => true]);
    }

    public function actionDownloadExport($exportId = null)
    {
        if (empty($exportId)) {
            Craft::$app->session->setError(Craft::t('export', 'Unable to export file with id 0.'));
            return null;
        }

        $export = Exports::instance()->getExportById($exportId);
        if ($export) {
            // Create the export directory in the storage folder
            $path = Craft::$app->path->getTempPath() . Export::EXPORT_PATH;
            if (!is_dir($path)) {
                mkdir($path);
            }

            // Create the file
            $file = Download::instance()->downloadExport($export, $path);

            // Open the file
            header('Content-type: application/csv');
            header('Content-Disposition: inline; filename="' . $file . '"');
            header('Content-Transfer-Encoding: binary');
            header('Accept-Ranges: bytes');
            echo file_get_contents($path . $file);
            die();
        }
    }

    public function actionSendExport() {
        if (Craft::$app->request->getBodyParam('emailaddress')) {
            if (!Craft::$app->request->getBodyParam('exportId')) {
                Craft::$app->session->setError(Craft::t('export', 'Unable to export file with id 0.'));
                return null;
            }

            $exportId = Craft::$app->request->getBodyParam('exportId');
            $export = Exports::instance()->getExportById($exportId);
            if ($export) {
                // Create the export directory in the storage folder
                $path = Craft::$app->path->getTempPath() . Export::EXPORT_PATH;
                if (!is_dir($path)) {
                    mkdir($path);
                }

                // Create the file
                $fileName = Download::instance()->downloadExport($export, $path);

                // Send the e-mail
                $mail = Craft::$app->request->getBodyParam('emailaddress');
                $file = $path . $fileName;
                Mail::instance()->sendMail($mail, $file);
            }
        } else {
            Craft::$app->session->setError(Craft::t('export', 'Unable to send mail: no e-mail address filled in.'));
            return null;
        }

    }

    private function _saveAndRedirect($export, $redirect, $withId = false, $redirectEdge)
    {
        if (!Exports::instance()->saveExport($export)) {
            Craft::$app->session->setError(Craft::t('export', 'Unable to save export.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'export' => $export,
            ]);

            return null;
        }

        Craft::$app->session->setNotice(Craft::t('export', 'Export saved.'));

        if ($withId) {
            $redirect = $redirect . $export->id . '/' . $redirectEdge;
        }

        return $this->redirect($redirect);
    }

    private function _getModelFromPost()
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();

        if ($request->getBodyParam('exportId')) {
            $export = Exports::instance()->getExportById($request->getBodyParam('exportId'));
        } else {
            $export = new ExportModel();
        }

        $export->name = $request->getBodyParam('name', $export->name);

        //slugify the filename so there won't be special characters and spaces
        $fileName = $request->getBodyParam('filename', $export->name);
        $slugify = new Slugify();
        $fileNameAsSlug = $slugify->slugify($fileName);
        $export->filename = $fileNameAsSlug;

        // file the model with the body fields
        $export->exportType = $request->getBodyParam('exportType', $export->exportType);
        $export->elementType = $request->getBodyParam('elementType', $export->elementType);
        $export->elementGroup = $request->getBodyParam('elementGroup', $export->elementGroup);
        $export->siteId = $request->getBodyParam('siteId', $export->siteId);


        // Check conditionally on Element Group fields - depending on the Element Type selected
        if (isset($export->elementGroup[$export->elementType])) {
            $elementGroup = $export->elementGroup[$export->elementType];

            if ($export->elementType == 'craft\elements\Category') {
                if (empty($elementGroup)) {
                    $export->addError('elementGroup', Craft::t('export', 'Category Group is required'));
                }
            }

            if ($export->elementType == 'craft\elements\Entry') {
                if (empty($elementGroup['section']) || empty($elementGroup['entryType'])) {
                    $export->addError('elementGroup', Craft::t('export', 'Entry Section and Type are required'));
                }
            }

            if ($export->elementType == 'craft\elements\User') {
                if (empty($elementGroup)) {
                    $export->addError('elementGroup', Craft::t('export', 'User Group is required'));
                }
            }
        }

        return $export;
    }

}