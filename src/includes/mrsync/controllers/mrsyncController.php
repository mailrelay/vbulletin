<?php

/**
 * Methods for showing the different steps in the sync process
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
class mrsyncController
{
    private $_vbphrase;
    private $_model;
    private $_curl = NULL;
    private $_apiKey;
    private $_syncedNewUsers = 0;
    private $_syncedUpdatedUsers = 0;
    private $_syncedDeleteUsers = 0;

    /**
     * Constructor function
     *
     * @param array $vbphrase vBulletin localized phrases
     */
    public function __construct( $vbphrase = array(), $mrsyncModel = null )
    {
        $this->_vbphrase = $vbphrase;
        $this->_model = $mrsyncModel;
    }

    /**
     * Returns the Curl conection if one is present
     *
     * @return resource
     */
    public function getCurl()
    {
        return $this->_curl;
    }

    /**
     * Checks if a valid curl conection has been stablished
     *
     * @param curl $curl
     */
    public function checkCurlInit( curl $curl )
    {
        if ( $curl == null ) {
            $warning = $this->_vbphrase['curl_fail'];
            exec_header_redirect('mrsync_admin.php?do=showLogin&message=' . $warning);
        }
    }

    /**
     * Prepare curl
     *
     * @param string $hostname MR host name
     * @param string $apiKey MR API key
     * @return curl
     */
    public function initCurl($hostname = '', $apiKey = '')
    {
        $url = 'https://'. $hostname .'/ccm/admin/api/version/2/&type=json';
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSLVERSION, 3);

        $this->checkCurlInit( $curl );

        $this->_curl = $curl;
        $this->_apiKey = $apiKey;
    }

    /**
     * Executes an API call against the API
     *
     * @param array $params Array with the API methods to execute
     * @return object
     */
    public function APICall( $params = array(), $apiKey = NULL )
    {
        if ( $apiKey == NULL ) {
            $params['apiKey'] = $this->_apiKey;
        } else {
            $params['apiKey'] = $apiKey;
        }

        curl_setopt( $this->_curl, CURLOPT_POSTFIELDS, $params );

        $headers = array(
                'X-Request-Origin: Vbulletin|1.1.0|'.SIMPLE_VERSION
        );
        curl_setopt($this->_curl, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec( $this->_curl );
        $jsonResult = json_decode($result);

        if ($jsonResult->status) {
            return $jsonResult->data;
        } else {
            return NULL;
        }
    }

    /**
     * Get MR groups
     *
     * @return object
     */
    public function getGroups()
    {
        $params = array(
            'function' => 'getGroups',
            'apiKey' => $this->_apiKey
        );

        $data = $this->APICall($params);

        if ( ($data == NULL) || (!(count( $this->apiGroupsToArray( $data ) ) > 0)) ) {
            $warning = $this->_vbphrase['no_groups_found'];
            exec_header_redirect('mrsync_admin.php?do=showLogin&message=' . $warning);
        } else {
            return $this->apiGroupsToArray( $data );
        }
    }

    /**
     * Get vBulletin user groups
     *
     * @return array
     */
    public function getVbulletinGroups()
    {
        return $this->_model->getVbulletinGroups();
    }

    /**
     * Get vBulletin social groups
     *
     * @return array
     */
    public function getVbulletinSocialGroups()
    {
        return $this->_model->getVbulletinSocialGroups();
    }

    /**
     * Prepare a json of groups obtained from the API and turn it into an array
     *
     * @param json $rawGroups A json of groups obtained from the API
     * @return array
     */
    public function apiGroupsToArray( $rawGroups )
    {
        $groupSelect = array();

        foreach ( $rawGroups AS $group ) {
            if ( $group->enable == 1 AND $group->visible == 1) {
                $groupSelect[$group->id] = $group->name;
            }
        }

        return $groupSelect;
    }

    /**
     * Check that at least one group has been selected for syncing
     *
     * @param array $groups Array of selected groups
     */
    public function checkAtLeastOneGroupWasSelected( $groups = array() )
    {
        if ( !is_array($groups) && !(count($groups) > 0) ) {
            $warning = $this->_vbphrase['select_at_least_one_group'];
            exec_header_redirect('mrsync_admin.php?do=showLogin&message=' . $warning);
        }
    }

    /**
     * Checks that at least one user exists for syncing, if there're no users for syncing redirects
     *
     * @param integer $numUsers The number of users for syncing
     * @return null
     */
    public function checkAtLeastOneUserExists($numUsers = 0)
    {
        if ( !($numUsers > 0) ) {
            $warning = $this->_vbphrase['no_users_found_in_vbulletin'];
            exec_header_redirect('mrsync_admin.php?do=showLogin&message=' . $warning);
        }

        return null;
    }

    /**
     * Counts number of users in vBulletin database, if zero redirects
     *
     * @return integer
     */
    public function countUsers()
    {
        $numUsers = $this->_model->countUsers();
        $this->checkAtLeastOneUserExists($numUsers);
        return $numUsers;
    }

    /**
     * Returns all the vBulletin users
     *
     * @param array $vBulletinGroups Array with the selected vbulletin groups
     * @param array $vBulletinSocialGroups Array with the selected vBulletin social groups
     *
     * return object
     */
    public function getAllUsers( $vBulletinGroups = array(), $vBulletinSocialGroups = array(), $page = 0, $limit = 0 )
    {
        return $this->_model->getAllUsers( $vBulletinGroups, $vBulletinSocialGroups, $page, $limit );
    }

    /**
     * Try to get user from Mailrelay by matching his/her email
     *
     * @param curl $curl
     * @param string $apiKey Mailrelay API Key
     * @param string $email User email used to find Mailrelay user
     * return object
     */
    public function getMailrelayUser( $apiKey = '', $email = '' )
    {
        if ( !empty($apiKey) && !empty($email) ) {

            $params = array(
                'function' => 'getSubscribers',
                'email' => $email
            );

            $data = $this->APICall($params, $apiKey);

            if ($data == NULL) {
                return new StdClass;
            } else {
                return $data[0];
            }

        } else {
            return new StdClass;
        }
    }

    /**
     * Update an already existing Mailrelay user
     *
     * @param string $apiKey Mailrelay API key
     * @param integer $id User id in the Mailrelay system
     * @param string $email User email from the vBulletin database
     * @param string $username Username from the vBulletin database
     * @param array $groups Selected groups to sync the user to
     * return integer
     */
    public function updateMailrelayUser( $apiKey = '', $id = 0, $email = '', $username = '', array $groups = array())
    {
        if ( !empty($apiKey) && !empty($email) && !empty($username) && (count($groups) > 0) && $id != 0 ) {

            $params = array(
                'function' => 'updateSubscriber',
                'apiKey' => $apiKey,
                'id' => $id,
                'email' => $email,
                'name' => $username,
                'groups' => $groups
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
        } else {
            return 0;
        }
    }

    /**
     * Delete an already existing Mailrelay user
     *
     * @param string $apiKey Mailrelay API key
     * @param string $email User email from the vBulletin database
     * return integer
     */
    public function deleteMailrelayUser( $apiKey = '', $email = '')
    {
        if ( !empty($apiKey) && !empty($email) ) {

            $params = array(
                'function' => 'deleteSubscriber',
                'apiKey' => $apiKey,
                'email' => $email
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
        } else {
            return 0;
        }
    }

    /**
     * Add a new Mailrelay user
     *
     * @param string $apiKey Mailrelay API key
     * @param string $email User email from the vBulletin database
     * @param string $username Username from the vBulletin database
     * @param array $groups Selected groups to sync the user to
     * return integer
     */
    public function addMailrelayUser( $apiKey = '', $email = '', $username = '', array $groups = array())
    {
        if ( !empty($apiKey) && !empty($email) && !empty($username) && (count($groups) > 0) ) {

            $params = array(
                'function' => 'addSubscriber',
                'apiKey' => $apiKey,
                'email' => $email,
                'name' => $username,
                'groups' => $groups
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
        } else {
            return 0;
        }
    }

    /**
     * Starts de process of syncing and updating users
     *
     * @param array $users An array of the users found in the vBulletin database
     * @param string $apiKey A string containing the API key to use with the API
     * @param array $groups The groups to sync the users to
     * @param integer $numUsers The total number of users for syncing
     * @return array Returns the resulted number of users synced and updated
     */
    public function syncUsers( $users = '', $apiKey = '', $groups = array(), $page )
    {

        if (!function_exists('fetch_userinfo')) {
            $warning = $this->_vbphrase['function_fetch_userinfo_not_available'];
            exec_header_redirect('mrsync_admin.php?do=showLogin&message=' . $warning);
        }

        //This script can not be aborted by user and has no time limit
        ignore_user_abort(true);
        set_time_limit(0);

        while( $user = $this->_model->_db->fetch_array($users) ) {

            // This is used to get the following value: $userinfo['adminemail']
            // This value has the user configuration for wanting to receive admin emails or not
            // Usuario -> Panel de control -> Configuración general -> Mensajería y notificaciones -> Recibir correo del equipo del foro
            $userinfo = fetch_userinfo($user['userid']);

            $mailrelayUser = $this->getMailrelayUser( $apiKey, $user['email'] );
            // We check if the user already exists in the API
            // If exists then update, else create new
            if( $mailrelayUser->email === $user['email'] ){
                if ($userinfo['adminemail']) {
                    $this->_syncedUpdatedUsers += $this->updateMailrelayUser( $apiKey, $mailrelayUser->id, $user['email'], $user['username'], $groups );
                } else {
                    $this->_syncedDeleteUsers += $this->deleteMailrelayUser( $apiKey, $user['email'] );
                }
            }else{
                if ($userinfo['adminemail']) {
                    $this->_syncedNewUsers += $this->addMailrelayUser( $apiKey, $user['email'], $user['username'], $groups );
                }
            }
        }

        return array( $this->_syncedNewUsers, $this->_syncedUpdatedUsers, $this->_syncedDeleteUsers );
    }

    /**
     * Checks if we have settings saved in the database, if not redirect to settings page
     *
     * @return integer $id
     */
    public function checkIfSavedSettingsExist()
    {
        $settingsExist = $this->_model->checkIfSavedSettingsExist();

        if ( $settingsExist === false ) {
            $redirectMessage = $this->_vbphrase['no_settings_found'];
            exec_header_redirect ('mrsync_settings_admin.php?do=showSettings&amp;message=' . $redirectMessage);
        }

        return $settingsExist['id'];
    }

    /**
     * Show the login form
     *
     * @param string $message Possible error message
     * @param integer $id Id for the database settings field
     * @return null
     */
    public function showLogin( $message = '', $id = 0 )
    {
        $savedSettings = $this->_model->getSavedSettings( $id );
        $hostname = $savedSettings['hostname'];
        $apiKey   = $savedSettings['key'];

        require_once(DIR . '/includes/mrsync/views/showLoginView.php');
        return null;
    }

    /**
     * Show the select groups form
     *
     * @param string $hostname Mailrelay account hostname
     * @param string $username Mailrelay account username
     * @param string $password Mailrelay account password
     * @param array $groupSelect Array of groups to show on select input
     * @return null
     *
     */
    public function showGroupsForm( $hostname = '', $apiKey = '', $groupSelect = array(), $vBulletinGroups = array(), $vBulletinSocialGroups = array() )
    {
        if ( !empty($hostname) && !empty($apiKey) && (count($groupSelect) > 0) ) {
            require_once(DIR . '/includes/mrsync/views/showGroupsView.php');
        } else {
            return null;
        }
    }

    /**
     * Create a form that automatically redirects to the summary action
     * using the print_form_auto_submit vBulletin function
     *
     * @param array $resultOfSyncing Contains the result of the syncing process
     * @return null
     */
    public function redirecToSummary( $resultOfSyncing = array() )
    {
        list( $syncedNewUsers, $syncedUpdatedUsers, $syncedDeletedUsers ) = $resultOfSyncing;
        require_once(DIR . '/includes/mrsync/views/showSyncRedirectView.php');
        return null;
    }

    /**
     * Shows the summary of synced users
     *
     * @param integer $syncedNewUsers Number of new users synced
     * @param integer $syncedUpdatedUsers Number of updated users synced
     * @return null
     */
    public function showSummary( $syncedNewUsers = 0, $syncedUpdatedUsers = 0, $syncedDeletedUsers = 0 )
    {
        require_once(DIR . '/includes/mrsync/views/showSummaryView.php');
        return null;
    }

    /**
     * Checks if all values required values for validating against the API are present
     *
     * @param string $hostname
     * @param string $username
     * @param string $password
     */
    public function checkRequiredFormValues( $hostname = '', $apiKey = '' )
    {
        if ( empty($hostname) || empty($apiKey) ) {
            $warning = $this->_vbphrase['all_params_required'];
            exec_header_redirect('mrsync_admin.php?do=showLogin&message=' . $warning);
        }
    }
}
