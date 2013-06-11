<?php
/**
 * @package MRSync for vBulletin
 * @author Jose Argudo Blanco
 * @website www.consultorpc.com
 * @email jose@consultorpc.com
 * @version 1.0.0
 * @date 26/08/11
 * @copyright ConsultorPC
 * @license Proprietary
**/

/**
 * IMPORTANT: This code will be executed as if it was in vBulletin includes\class_dm_user.php. Called by this HOOK:
 * userdata_delete
 * ($hook = vBulletinHook::fetch_hook('userdata_delete')) ? eval($hook) : false;
 */

/**
 * This plugin is used for syncing deleted users with the mailing manager. When a user is deleted in the vBulletin
 * it will also be deleted in the mailing manager
 */

$status = 'production'; // In production change to anything other than 'development'

// Enable errors while developing
if ( $status == 'development' ) {
    error_reporting(E_ALL & ~E_NOTICE);
    ini_set('display_errors','On');
}

class SyncDeleteUser
{
    private $_validSync = 1;

    private $_email = '';
    private $_db;
    private $_curl;
    private $_apiKey;
    private $_settings;

    /**
     * Username and email come from the register page http://www.yourvBulletin.com/register.php
     * See product-mrsync.xml for details about when this code is called
     *
     * @param string $email The email of the user to be synced
     * @param resource $db The vBulletin database resource
     */
    public function __construct($email = '', $db = '')
    {
        $this->_email = $email;
        $this->_db = $db;

        if ($this->_email == '') {
            $this->_validSync = 0;
        }

        $this->getSettings();
    }

    /**
     * Return the current status of the is validSync variable
     *
     * @return integer
     */
    public function getIfValid()
    {
        return $this->_validSync;
    }

    /**
     * Try getting the configuration from the mrsync database table
     *
     * @return array
     */
    private function getSettings()
    {
        $settings = $this->_db->query_first("SELECT `id`, `hostname`, `key`, `enableAutoSync`, `groupsToSyncNewUsers` FROM " . TABLE_PREFIX . "mrsync");

        if ( is_array($settings) && count($settings) > 0 ) {
            $this->_settings = $settings;
        } else {
            $this->_validSync = 0;
            return null;
        }
    }

    /**
     * Validate if all required settings are present, before syncing
     *
     * @param array $settings The settings found in the mrsync table
     * @return integer
     */
    private function checkSyncSettings($settings = array())
    {
        if (!empty($settings['hostname']) && !empty($settings['key']) && $settings['hostname'] != '' && $settings['key'] != '') {
            return 1;
        } else {
            return 0;
        }
    }

    /**
     * Prepare curl conection
     *
     * @param string $hostname MR host name
     * @param string $apiKey MR API key
     * @return curl
     */
    private function initCurl($hostname = '', $apiKey = '')
    {
        $url = 'http://'. $hostname .'/ccm/admin/api/version/2/&type=json';
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POST, 1);

        if ( $curl == null ) {
            $this->_validSync = 0;
        } else {
            $this->_curl = $curl;
        }

        $this->_apiKey = $apiKey;
    }

    /**
     * Checks if the user is already present in the MR
     *
     * @return StdClass
     */
    private function checkIfUserAlreadyExists()
    {

        $params = array(
            'function' => 'getSubscribers',
            'apiKey' => $this->_apiKey,
            'email' => $this->_email
        );

        curl_setopt($this->_curl, CURLOPT_POSTFIELDS, $params);

        $headers = array(
                'X-Request-Origin: Vbulletin|1.1.0|'.SIMPLE_VERSION
        );
        curl_setopt($this->_curl, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($this->_curl);

        $jsonResult = json_decode($result);

        if (!$jsonResult->status) {
            return new StdClass;
        } else {
            $data = $jsonResult->data;
            return $data[0];
        }
    }

    /**
     * Removes a user from the MR
     *
     * @param object $user A user object got from the MR
     */
    private function removeUser($user = null)
    {

        if (is_object($user) && $user->id > 0) {

            $params = array(
                'function' => 'deleteSubscriber',
                'apiKey' => $this->_apiKey,
                'email' => $user->email
            );

            $post = http_build_query($params);
            curl_setopt($this->_curl, CURLOPT_POSTFIELDS, $post);

            $headers = array(
                'X-Request-Origin: Vbulletin|1.1.0|'.SIMPLE_VERSION
            );
            curl_setopt($this->_curl, CURLOPT_HTTPHEADER, $headers);

            $result = curl_exec($this->_curl);

            $jsonResult = json_decode($result);

            if ( $jsonResult->status ) {
                return 1;
            }

        }

        return 0;
    }

    /**
     * Main syncing process
     */
    public function sync()
    {
        if ($this->_validSync && is_array($this->_settings) && $this->_settings['enableAutoSync'] == '1') {

            $checkSyncSettings = $this->checkSyncSettings($this->_settings);

            if ($this->checkSyncSettings($this->_settings)) {

                $this->initCurl($this->_settings['hostname'], $this->_settings['key']);

                if ($this->_validSync && $this->_apiKey != '') {

                    $this->removeUser($this->checkIfUserAlreadyExists());

                }
            }
        }
    } // End public function Sync()
}

$SyncDeleteUser = new SyncDeleteUser($this->existing['email'], $this->dbobject);

if ($SyncDeleteUser->getIfValid()) {
    $SyncDeleteUser->sync();
}

if ($status == 'development') {
    exit();
}
