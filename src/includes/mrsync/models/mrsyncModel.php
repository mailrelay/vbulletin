<?php

/**
 * Methods for querying the vBulletin database
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
class mrsyncModel 
{
    public $_db;
    private $_condition;
    
    /**
     * Constructor function
     * 
     * @param object $db vBulletin $db object 
     */
    public function __construct( $db) 
    {
        $this->_db = $db;     
        $this->_condition = fetch_user_search_sql($vbulletin->GPC['user'], $vbulletin->GPC['profile']);   
    }
    
    /**
     * Count all users from database
     * 
     * @return integer $users
     */
    public function countUsers()
    {
        $countusers = $this->_db->query_first("
            SELECT COUNT(*) AS users
            FROM " . TABLE_PREFIX . "user AS user
            LEFT JOIN " . TABLE_PREFIX . "userfield AS userfield ON(userfield.userid = user.userid)
            LEFT JOIN " . TABLE_PREFIX . "usertextfield AS usertextfield ON(usertextfield.userid = user.userid)
            WHERE $this->_condition");

        if ( $countusers['users'] > 0 ) {
            return $countusers['users'];
        } else {
            return 0;
        }
    }

    /**
     * Gets a groups array and returns a string that can be used in a IN query
     * 
     * @param array $vBulletinGroups Array containing the vBulleting groups selected
     * @return string $groups String containing the groups
     */
    public function groupsToInTypeDbQuery( $vBulletinGroups )
    {
        $groupsWhere = '';
        $i = 1;
        $numberGroups = count($vBulletinGroups);
        foreach ( $vBulletinGroups  AS $group ) {
            $groupsWhere .= $group;
            
            if ( $i != $numberGroups ) {
                $groupsWhere .= ',';
            }
            $i++;
        }   
        
        return $groupsWhere;
    }
    
    /**
     * Get all users from database in an array
     * 
     * @param array $vBulletinGroups Array containing the vBulleting groups selected
     * @param array $vBulletinSocialGroups Array containing the vBulleting social groups selected
     * @return array $users Array containing all users from database that belong to the selected groups
     */    
    public function getAllUsers( $vBulletinGroups, $vBulletinSocialGroups, $page, $limit )
    {
        $users = array();
        
        // Table `usergroup`
        if ( $vBulletinGroups[0] == 0 ) {
            $userGroupQuery = '';
        } else {
            $userGroupQuery = "AND usergroupid IN (" . $this->groupsToInTypeDbQuery( $vBulletinGroups ) . ")";
        }
        // Tables `socialgroup`, `socialgroupmember`, `user`
        if ( $vBulletinSocialGroups[0] == 0 ) {
            $socialJoin = '';
            $socialGroupQuery = '';
        } else {
            $socialJoin = "LEFT JOIN socialgroupmember AS socialgroupmember ON ( socialgroupmember.userid = user.userid )";
            $socialGroupQuery = "AND " . TABLE_PREFIX . "socialgroupmember.groupid IN (" . $this->groupsToInTypeDbQuery( $vBulletinSocialGroups ) . ")";
        }        
                
        $searchQuery = "
            SELECT
            user.userid, reputation, username, usergroupid, birthday_search, email,
            parentemail, homepage, icq, aim, yahoo, msn, skype, signature,
            usertitle, joindate, lastpost, posts, ipaddress, lastactivity, userfield.*, infractions, ipoints, warnings
            FROM " . TABLE_PREFIX . "user AS user
            LEFT JOIN " . TABLE_PREFIX . "userfield AS userfield ON(userfield.userid = user.userid)
            LEFT JOIN " . TABLE_PREFIX . "usertextfield AS usertextfield ON(usertextfield.userid = user.userid)
            {$socialJoin}
            WHERE $this->_condition
            {$userGroupQuery}
            {$socialGroupQuery}            
            ORDER BY user.userid
            LIMIT {$page}, {$limit}";

        return $this->_db->query_read($searchQuery);
    }
    
    /**
     * Returns all vBulletin groups ( like Administrators, Banned Users, Registered Users)
     * 
     * @return array $groups Array containing all groups from database
     */
    public function getVbulletinGroups()
    {
        $groupsToReturn = array();
        $groupsToReturn[0] = 'Todos';
        
        $searchQuery = "
            SELECT
            usergroupid, title
            FROM " . TABLE_PREFIX . "usergroup
            ORDER BY title";

        $groups = $this->_db->query_read( $searchQuery );

        while( $group = $this->_db->fetch_array($groups) ) {
            $groupsToReturn[$group['usergroupid']] = $group['title'];
        }        
        
        return $groupsToReturn;
    }
    
    /**
     * Returns all vBulletin social groups
     * 
     * @return array $groups Array containing all social groups from database
     */    
    public function getVbulletinSocialGroups()
    {
        $groupsToReturn = array();
        $groupsToReturn[0] = 'Todos';
        
        $searchQuery = "
            SELECT
            groupid, name
            FROM " . TABLE_PREFIX . "socialgroup
            ORDER BY name";

        $groups = $this->_db->query_read( $searchQuery );

        while( $group = $this->_db->fetch_array($groups) ) {
            $groupsToReturn[$group['groupid']] = $group['name'];
        }        
        
        return $groupsToReturn;        
    }
    
    /**
     * Query for SMTP usage in vBulletin options
     * 
     * @return integer
     */
    public function getUseSmtp()
    {
        $searchQuery = "
            SELECT *
            FROM " . TABLE_PREFIX . "setting
            WHERE grouptitle = 'email' AND varname = 'use_smtp'
            ORDER BY varname";

        $settings = $this->_db->query_first($searchQuery);     
        
        return $settings['value'];
    }
    
    /**
     * Check if there's a previously saved settings row
     * 
     * @return array $checkSettings Array containing the id of the settings row
     */
    public function checkIfSavedSettingsExist()
    {
        $checkSettings = $this->_db->query_first("
            SELECT id
            FROM " . TABLE_PREFIX . "mrsync");        
        
        return $checkSettings;
    }
    
    /**
     * Get an array with the previously saved settings
     * 
     * @return array $savedSettings Returns an array with the existing saved settings
     */
    public function getSavedSettings( $id = 0 )
    {
        $savedSettings = $this->_db->query_first("
            SELECT *
            FROM " . TABLE_PREFIX . "mrsync AS user
            WHERE id = '" . $id . "'
        ");    
        
        return $savedSettings;
    }
    
    /**
     * Sets vBulletin SMTP usage settings
     * 
     * @param integer $getUseSmtp Is SMTP being used for sending vBulletin emails
     * @param integer $enableSMTP Enable or disable SMTP usage 1/0
     * @param string $username Mailrelay username
     * @param string $password Mailrealy password
     * @return null
     */
    public function setSmtpConfig( $getUseSmtp = 0, $enableSMTP = 0, $username = '', $password = ''  )
    {
        if ( $enableSMTP == null ) {
            $enableSMTP = 0;
        }
        // If $enableSMTP value is different from what we have in the database
        // then we change database settings             
        if ( $enableSMTP != $getUseSmtp ) {
            
            $smtpConfig = array(
                'use_smtp' => $enableSMTP,
                'smtp_host' => 'smtp-vbulletin.ip-zone.com',
                'smtp_port' => 25,
                'smtp_user' => mysql_real_escape_string($username),
                'smtp_pass' => mysql_real_escape_string($password),
                'smtp_tls' => 'none'
            );
            
            // save_settings is a Core vBulletin function
            // Updates the setting table based on data passed in then rebuilds the datastore.
            save_settings($smtpConfig);  
        } 
        
        return null;
    }
    
    /**
     * Update existing config settings in database
     * 
     * @param integer $id The id of the row to be updated
     * @param string $hostname Mailrelay account hostname
     * @param string $username Mailrelay account username
     * @param string $password Mailrelay account password
     * @param integer $enableSMTP Enable vBulletin SMTP layer for sending emails
     * @param integer $enableAutoSync Enable the Auto Syncing plugin for new users
     * @return bool $result Returns 0 if fail, 1 if inserted/updated settings
     */    
    public function updateExistingSettings( $id = 0, $hostname = '', $username = '', $password = '', $enableSMTP = 0, $enableAutoSync = 0, $groups = array() )
    {         
        $result = $this->_db->query_write("
            UPDATE " . TABLE_PREFIX . "mrsync
            SET
                hostname             = '{$hostname}', 
                username             = '{$username}', 
                password             = '{$password}', 
                enableSMTP           = {$enableSMTP}, 
                enableAutoSync       = {$enableAutoSync},
                groupsToSyncNewUsers = '{$groups}'
            WHERE
                id = {$id}
        ");
                
        return $result;
    }
    
    /**
     * Create a new settings row in the database
     * 
     * @param string $hostname Mailrelay account hostname
     * @param string $username Mailrelay account username
     * @param string $password Mailrelay account password
     * @param integer $enableSMTP Enable vBulletin SMTP layer for sending emails
     * @param integer $enableAutoSync Enable the Auto Syncing plugin for new users
     * @return bool $result Returns 0 if fail, 1 if inserted/updated settings
     */     
    public function insertNewSettings( $hostname = '', $username = '', $password = '', $enableSMTP = 0, $enableAutoSync = 0, $groups = array() )
    {
        $result = $this->_db->query_write("
            INSERT INTO " . TABLE_PREFIX . "mrsync
                ( hostname, username, password, enableSMTP, enableAutoSync, groupsToSyncNewUsers )
            VALUES
                ( '{$hostname}', '{$username}', '{$password}', {$enableSMTP}, {$enableAutoSync}, '{$groups}' )
        ");  
                
        return $result;
    }
    

    /**
     * Takes a value and escapes it before inserting into database
     * 
     * @param string $element
     * @param integer $key 
     */
    public function escapeValues( &$element, $key)
    {
        $element = $this->_db->escape_string( $element );
    }
    
    /**
     * Save selected settings into the database
     * 
     * @param string $hostname Mailrelay account hostname
     * @param string $username Mailrelay account username
     * @param string $password Mailrelay account password
     * @param integer $enableSMTP Enable vBulletin SMTP layer for sending emails
     * @param integer $enableAutoSync Enable the Auto Syncing plugin for new users
     * @param array $groups Saved selected groups
     * @return bool $result Returns 0 if fail, 1 if inserted/updated settings
     */
    public function saveSettings($hostname = '', $username = '', $password = '', $enableSMTP = 0, $enableAutoSync = 0, $groups = array())
    {
        $groups = serialize($groups);
        $values = array( $hostname, $username, $password, $enableSMTP, $enableAutoSync );       
        array_walk( $values, array( &$this, 'escapeValues' ));       
        list( $hostname, $username, $password, $enableSMTP, $enableAutoSync ) = $values;
        
        $this->setSmtpConfig( $this->getUseSmtp, $enableSMTP, $username, $password );
        
        $checkSettings = $this->checkIfSavedSettingsExist();
        if ( $checkSettings && !empty($checkSettings['id']) ) {
            return $this->updateExistingSettings($checkSettings['id'], $hostname, $username, $password, $enableSMTP, $enableAutoSync, $groups);
        } else {
            return $this->insertNewSettings($hostname, $username, $password, $enableSMTP, $enableAutoSync, $groups);
        }
    }
}