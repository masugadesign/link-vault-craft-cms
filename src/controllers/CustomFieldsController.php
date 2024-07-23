<?php

namespace Masuga\LinkVault\controllers;

use Craft;
use craft\web\Controller;
use yii\web\Response as YiiResponse;
use Masuga\LinkVault\LinkVault;
use Masuga\LinkVault\elements\LinkVaultCustomField;

class CustomFieldsController extends Controller
{

    /**
     * This controller action loads the user-defined field list.
     * @return YiiResponse
     */
    public function actionCustomFields(): YiiResponse
    {
        return $this->renderTemplate('linkvault/_customfields');
    }

    /**
     * This controller action loads the new user-defined field form.
     * @return YiiResponse
     */
    public function actionCustomFieldForm(array $variables=array()): YiiResponse
    {
        if ( !empty($variables['customField']) ) {
            $templateVars['customField'] = $variables['customField'];
        } else {
            $templateVars['customField'] = new LinkVaultCustomField;
        }
        return $this->renderTemplate('linkvault/_addcustomfield', $templateVars);
    }

    /**
     * This method action is the submission handler for the new custom field
     * form. It creates a new linkvault_customfields row and adds the column
     * to the linkvault_downloads and linkvault_leeches table.
     * @return YiiResponse
     */
    public function actionCustomFieldSubmit(): YiiResponse
    {
        $this->requirePostRequest();
        $request = Craft::$app->getRequest();
        $customField = new LinkVaultCustomField();
        $customField->fieldLabel = Craft::$app->request->post('fieldLabel');
        $customField->fieldName = Craft::$app->request->post('fieldName');
        $customField->fieldType = Craft::$app->request->post('fieldType');

        $customField = LinkVault::getInstance()->customFields->createField($customField);

        if ( ! $customField->hasErrors() ) {
            Craft::$app->getSession()->setNotice(Craft::t('linkvault', 'Link Vault field created.'));
            return $this->redirectToPostedUrl();
        } else {
            Craft::$app->getSession()->setError(Craft::t('linkvault', 'Error creating the Link Vault field.'));
            Craft::$app->urlManager->setRouteVariables(array(
                'customField' => $customField
            ));
            return $this->goBack();
        }
    }
}
