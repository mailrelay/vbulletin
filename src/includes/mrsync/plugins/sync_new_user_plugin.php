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
 * IMPORTANT: This code will be executed as if it was in vBulletin register.php. Called by this HOOK:
 * register_addmember_complete
 * ($hook = vBulletinHook::fetch_hook('register_addmember_complete')) ? eval($hook) : false;
 */

/**
 * This plugin is used for syncing new registered users with the mailing manager
 */

$status = 'production'; // In production change to anything other than 'development'

// Enable errors while developing
if ( $status == 'development' ) {
    error_reporting(E_ALL & ~E_NOTICE);
    ini_set('display_errors','On');
}

class SyncNewUser
{
    private $_validSync = 1;
    
    private $_username = '';
    private $_email = '';
    private $_db;
    private $_curl;
    private $_apiKey;
    private $_settings;
    
    /**
     * Username and email come from the register page http://www.yourvBulletin.com/register.php
     * See product-mrsync.xml for details about when this code is called
     * 
     * @param string $username The username to be synced
     * @param string $email The email of the user to be synced
     * @param resource $db The vBulletin database resource
     */
    public function __construct( $username = '', $email = '', $db = '' ) 
    {
        $this->_username = $username;
        $this->_email = $email;
        $this->_db = $db;
        
        if ($this->_email == '' && $this->_username == '') {
            $this->_validSync = 0;
        }
        
        $this->getSettings();
    }
    
    /**
     * Return the curren status of the is validSync variable
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
        $settings = $this->_db->query_first("
            SELECT id, enableAutoSync, hostname, password, username, enableAutoSync, groupsToSyncNewUsers
            FROM " . TABLE_PREFIX . "mrsync");
        
        if ( is_array($settings) && count($settings) > 0 ) {
            $this->_settings = $settings;
        } else {
            $this->_validSync = 0;
            return null;
        }
    }
    
    /**
     * Try to get the default groups to sync, if not found, return ALL
     * 
     * @param array $settings The settings array from mrsync table
     * @return string 
     */
    private function getSyncGroups($settings = array())
    {
        $groups = unserialize($settings['groupsToSyncNewUsers']);
        
        if (!empty($groups) && count($groups) > 0) {
            return $groups;
        } else {
            // There are no groups selected for syncing
            return 'ALL';
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
        if (!empty($settings['hostname']) && !empty($settings['password']) && !empty($settings['username']) && $settings['hostname'] != '' && $settings['password'] != '' && $settings['username'] != '') {
            return 1;
        } else {
            return 0;
        }
    }
    
    /**
     * Prepare curl conection
     * 
     * @param string $hostname MR host name
     * @return curl
     */
    private function initCurl( $hostname = '')
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
    }
    
    /**
     * Get the apiKey of the MR account, this is required for syncing to happen
     * 
     * @param string $username The MR user name
     * @param string $password The MR user password
     * @return bool 
     */
    private function getApiKey($username = '', $password = '')
    {
        $params = array(
            'function' => 'doAuthentication',
            'username' => $username,
            'password' => $password
        ); 

        curl_setopt($this->_curl, CURLOPT_POSTFIELDS, $params);

        $headers = array(
                'X-Request-Origin: Vbulletin|1.0.0|'.SIMPLE_VERSION
        );
        curl_setopt($this->_curl, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($this->_curl);

        $jsonResult = json_decode($result);

        if (!$jsonResult->status) {
            return FALSE;
        } else {
            $this->_apiKey = $jsonResult->data;
            return TRUE;
        }        
    }
    
    /**
     * Get all the groups from the MR
     * 
     * @return array
     */
    private function getMailrelayGroups()
    {
        $params = array(
            'function' => 'getGroups',
            'apiKey' => $this->_apiKey
        );

        curl_setopt( $this->_curl, CURLOPT_POSTFIELDS, $params );

        $headers = array(
                'X-Request-Origin: Vbulletin|1.0.0|'.SIMPLE_VERSION
        );
        curl_setopt($this->_curl, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec( $this->_curl );
        $jsonResult = json_decode($result);

        if ( (!$jsonResult->status) ) {
            $groups = '';
        } else {
            $groups = $jsonResult->data;
        }  
        
        return $groups;
    }
    
    /**
     * Prepares the groups array to be used in the sync process
     * 
     * @param array $groups The array of groups to be synced
     * @return array 
     */
    private function prepareGroups($groups=array())
    {
        $groupsToSync = array();
        
        foreach ( $groups AS $group ) {
            if ( $group->enable == 1 AND $group->visible == 1) {
                $groupsToSync[] = $group->id;
            }
        }        
        
        return $groupsToSync;
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
                'X-Request-Origin: Vbulletin|1.0.0|'.SIMPLE_VERSION
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
     * Update an already existing user in the MR
     * 
     * @param integer $id ID of the MR user to be updated
     * @param array $groupsToSync Array of groups to be synced
     * @return integer 
     */
    private function updateSubscriber($id = 0, $groupsToSync = array())
    {        
        $params = array(
            'function' => 'updateSubscriber',
            'apiKey' => $this->_apiKey,
            'id' => $id,
            'email' => $this->_email,
            'name' => $this->_username,
            'groups' => $groupsToSync
        );

        $post = http_build_query($params);			
        curl_setopt($this->_curl, CURLOPT_POSTFIELDS, $post);

        $headers = array(
                'X-Request-Origin: Vbulletin|1.0.0|'.SIMPLE_VERSION
        );
        curl_setopt($this->_curl, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($this->_curl);    
        
        $jsonResult = json_decode($result);
        
        if ( $jsonResult->status ) {
            return 1;
        } else {
            return 0;
        }
    }
    
    /**
     * Add a new user to the MR
     * 
     * @param array $groupsToSync Array of groups to be synced
     * @return integer 
     */
    private function addSubscriber($groupsToSync = array())
    {        
        $params = array(
            'function' => 'addSubscriber',
            'apiKey' => $this->_apiKey,
            'email' => $this->_email,
            'name' => $this->_username,
            'groups' => $groupsToSync
        );

        $post = http_build_query($params);			
        curl_setopt($this->_curl, CURLOPT_POSTFIELDS, $post);

        $headers = array(
                'X-Request-Origin: Vbulletin|1.0.0|'.SIMPLE_VERSION
        );
        curl_setopt($this->_curl, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($this->_curl); 
        
        $jsonResult = json_decode($result);
                
        if ( $jsonResult->status ) {
            return 1;
        } else {
            return 0;
        }
    }
    
    
    /**
     * Main syncing process
     */
    public function sync()
    {
        if ($this->_validSync && is_array($this->_settings) && $this->_settings['enableAutoSync'] == '1') {

            $groupsToSync = $this->getSyncGroups($this->_settings);
            $checkSyncSettings = $this->checkSyncSettings($this->_settings);
            
            if ($this->checkSyncSettings($this->_settings)) {

                $this->initCurl($this->_settings['hostname']);
                
                if ($this->_validSync && $this->getApiKey($this->_settings['username'], $this->_settings['password'])) {

                    if ($groupsToSync == 'ALL') {
                        $groups = $this->getMailrelayGroups();
                        $groupsToSync = $this->prepareGroups($groups);
                    }

                    $mailrelayUser = $this->checkIfUserAlreadyExists();

                    if($mailrelayUser->email === $this->_email){
                        $result = $this->updateSubscriber($mailrelayUser->id, $groupsToSync);
                    }else{
                        $result = $this->addSubscriber($groupsToSync);
                    }
                } 
            } 
        }            
    } // End public function Sync()
}

$syncNewUser = new SyncNewUser( $vbulletin->GPC['username'], $vbulletin->GPC['email'], $db);

if ($syncNewUser->getIfValid()) {
    $syncNewUser->sync();
}

if ($status == 'development') {
    exit();
}
