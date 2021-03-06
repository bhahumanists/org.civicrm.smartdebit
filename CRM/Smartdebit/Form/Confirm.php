<?php
/*--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
+--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2017                                |
+--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +-------------------------------------------------------------------*/

/**
 * Class CRM_Smartdebit_Form_Confirm
 *
 * Path: civicrm/smartdebit/syncsd/confirm
 * This displays a confirmation button for import of matched/unmatched, failed and successful contributions from Smartdebit
 * Clicking next will start an import runner which cannot be cancelled.
 * This is the final page in the import process (starting at civicrm/smartdebit/syncsd)
 */
class CRM_Smartdebit_Form_Confirm extends CRM_Core_Form {

  private $status = 0;

  public function preProcess() {
    $state = CRM_Utils_Request::retrieve('state', 'String', CRM_Core_DAO::$_nullObject, FALSE, 'tmp', 'GET');

    if ($state == 'done') {
      $this->status = 1;
      $successes = CRM_Smartdebit_SyncResults::get(['collections' => TRUE]);
      $rejects = CRM_Smartdebit_SyncResults::get(['arudd' => TRUE, 'auddis' => TRUE]);

      $summary = [];
      foreach ($successes as $success) {
        if (isset($success['amount'])) {
          $summary['success']['amount'] += $success['amount'];
        }
      }
      $summary['success']['count'] = count($successes);
      $summary['success']['description'] = ts('Successful contribution(s) synchronised with CiviCRM');

      foreach ($rejects as $reject) {
        if (isset($reject['amount'])) {
          $summary['reject']['amount'] += $reject['amount'];
        }
      }
      $summary['reject']['count'] = count($rejects);
      $summary['reject']['description'] = ts('Failed Contribution(s) synchronised with CiviCRM');

      $this->assign('summary', $summary);
      $this->assign('successes', $successes);
      $this->assign('rejects', $rejects);
    }
    $this->assign('status', $this->status);
  }

  public function buildQuickForm() {
    // Retrieve auddisIDs/aruddIDs if specified as parameters
    $auddisIDs = CRM_Utils_Request::retrieve('auddisID', 'String', $this, false);
    if (isset($auddisIDs)) {
      $auddisIDs = array_filter(explode(',', $auddisIDs));
      $this->add('hidden', 'auddisIDs', serialize($auddisIDs));
    }
    $aruddIDs = CRM_Utils_Request::retrieve('aruddID', 'String', $this, false);
    if (isset($aruddIDs)) {
      $aruddIDs = array_filter(explode(',', $aruddIDs));
      $this->add('hidden', 'aruddIDs', serialize($aruddIDs));
    }

    $redirectUrlBack = CRM_Utils_System::url('civicrm', 'reset=1');

    $this->addButtons(array(
        array(
          'type' => 'cancel',
          'js' => array('onclick' => "location.href='{$redirectUrlBack}'; return false;"),
          'name' => ts('Cancel'),
        ),
        array(
          'type' => 'submit',
          'name' => ts('Confirm'),
          'isDefault' => TRUE,
        ),
      )
    );

    CRM_Utils_System::setTitle(ts('Synchronise CiviCRM with Smart Debit'));
  }

  public function postProcess() {
    $params     = $this->controller->exportValues();
    isset($params['auddisIDs']) ? $auddisIDs = unserialize($params['auddisIDs']) : $auddisIDs = NULL;
    isset($params['aruddIDs']) ? $aruddIDs = unserialize($params['aruddIDs']) : $aruddIDs = NULL;

    $runner = CRM_Smartdebit_Sync::getRunner(TRUE, $auddisIDs, $aruddIDs);
    CRM_Smartdebit_Sync::runViaWeb($runner);
  }
}
