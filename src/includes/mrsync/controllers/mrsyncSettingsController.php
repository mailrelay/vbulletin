<?php

/**
 * Methods for managing the different settings of the MRSync extension
 *
 * @package MRSync for vBulletin
 * @author Jose Argudo Blanco
 * @website www.consultorpc.com
 * @email jose@consultorpc.com
 * @version 1.0.0
 * @date 26/08/11
 * @copyright ConsultorPC
 * @license Proprietary
**/
class mrsyncSettingsController
{
    private $_vbphrase;
    private $_model;
    private $_mrsyncController;

    /**
     * Constructor function
     *
     * @param array $vbphrase vBulletin localized phrases
     */
    public function __construct( $vbphrase = array(), $mrsyncModel = null, $mrsyncController = null )
    {
        $this->_vbphrase = $vbphrase;
        $this->_model = $mrsyncModel;
        $this->_mrsyncController = $mrsyncController;
    }

    /**
     * Shows the required admin rights panel
     */
    public function showRequiresAdmin()
    {
        require_once(DIR . '/includes/mrsync/views/showRequiresAdminView.php');
    }

    /**
     * Used for change bool values into integeres
     *
     * @param integer $value Value to be converted
     * @return integer
     */
    public function boolToInt( $value = null )
    {
        if ( $value == false || $value === NULL ) {
            return 0;
        } else {
            return 1;
        }
    }

    /**
     * Used for preparing vBulletin checkboxes which require a true/false parameter
     * In database we save as 0/1 thus this function converts value
     *
     * @param integer $value Value to be converted
     * @return bool
     */
    public function intToBool( $value = 0 )
    {
        if ( $value == 0 || $value === NULL ) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Gets the saved settings from db and returns them in an array
     *
     * @param integer $id Id of the saved settings row
     * @return array
     */
    public function getSavedSettings( $id = 0 )
    {
        $dbSettings = $this->_model->getSavedSettings( $id );

        return array( 'hostname'             => $dbSettings['hostname'],
                      'key'                  => $dbSettings['key'],
                      'enableAutoSync'       => $this->intToBool($dbSettings['enableAutoSync']),
                      'groupsToSyncNewUsers' => $dbSettings['groupsToSyncNewUsers'],
                      'enableSMTP'           => $this->intToBool($dbSettings['enableSMTP']),
                      'smtpUser'             => $dbSettings['smtpUser'],
                      'smtpPass'             => $dbSettings['smtpPass'] );
    }

    /**
     * Returns an empty array with no saved settings exists, else
     * returns an array with the database saved settings
     *
     * @return array
     */
    public function prepareFormValues()
    {
        $settingsExist = $this->_model->checkIfSavedSettingsExist();

        if ( $settingsExist ) {
            return $this->getSavedSettings( $settingsExist['id'] );
        } else {
            return array( 'hostname'             => '',
                          'key'                  => '',
                          'enableAutoSync'       => false,
                          'groupsToSyncNewUsers' => '',
                          'enableSMTP'           => true,
                          'smtpUser'             => '',
                          'smtpPass'             => '' );
        }
    }

    /**
     * Show the settings form
     *
     * @param string $message Possible error message
     * @return null
     */
    public function showSettings($message = '')
    {
        $formValues = $this->prepareFormValues();

        if ($formValues['hostname'] != '' && $formValues['key'] != '') {
            $this->_mrsyncController->initCurl($formValues['hostname'], $formValues['key']);
            $groupSelect = $this->_mrsyncController->getGroups();
        }

        require_once(DIR . '/includes/mrsync/views/showSettingsView.php');
        return null;
    }

    /**
     * Gets a database save result and returns a message to show to users
     *
     * @param bool $dbResult
     * @return strning
     */
    public function saveResultMessage( $dbResult )
    {
        if ( $dbResult ) {
            return $this->_vbphrase['settings_saved'];
        } else {
            return $this->_vbphrase['settings_not_saved'];
        }
    }

    /**
     * Check if we can connect to the Mailing Manager using provided data.
     * This is done before saving, just to be sure that saved data is valid.
     *
     * @param type $hostname
     * @param type $username
     * @param type $password
     */
    public function checkAbleToConnect($hostname = '', $apiKey = '')
    {
        $url = 'https://'. $hostname .'/ccm/admin/api/version/2/&type=json';
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSLVERSION, 3);

        $params = array(
            'function' => 'getStats',
            'apiKey' => $apiKey
        );

        curl_setopt($curl, CURLOPT_POSTFIELDS, $params);

        $headers = array(
                'X-Request-Origin: Vbulletin|1.1.0|'.SIMPLE_VERSION
        );
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($curl);

        $jsonResult = json_decode($result);

        if (!$jsonResult->status) {
            return 0;
        } else {
            return 1;
        }

    }

    /**
     * Action that fires the saving process
     *
     * @param type $_REQUEST
     * @return type
     */
    public function saveAction( $_REQUEST )
    {
        $hostname = trim($_REQUEST['hostname']);
        $apiKey   = trim($_REQUEST['key']);

        if ($this->checkAbleToConnect($hostname, $apiKey)) {

            $enableAutoSync = $this->boolToInt( $_REQUEST['enableAutoSync'] );
            $groups = $_REQUEST['groups'];
            $enableSMTP = $this->boolToInt( $_REQUEST['enableSMTP'] );
            $smtpUser = trim($_REQUEST['smtpUser']);
            $smtpPass = trim($_REQUEST['smtpPass']);

            if ($hostname != '' && $apiKey != '') {
                $this->_mrsyncController->initCurl($hostname, $apiKey);
                $groupSelect = $this->_mrsyncController->getGroups();
            }

            return $this->saveResultMessage( $this->_model->saveSettings($hostname, $apiKey, $enableAutoSync, $groups, $enableSMTP, $smtpUser, $smtpPass) );

        } else {
            return $this->_vbphrase['settings_not_saved_incorrect_conection'];
        }
    }

}
