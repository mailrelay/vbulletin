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
class mrsyncLaunchController
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
     * Initialices the mrsyncController with the params from the database
     *
     * @param integer $settingsId The ID for the settings saved in the database
     */
    public function init($settingsId = 0)
    {
        $savedSettings = $this->_model->getSavedSettings($settingsId);
        $hostname = $savedSettings['hostname'];
        $apiKey   = $savedSettings['key'];

        $this->_mrsyncController->initCurl( $hostname, $apiKey );
    }

    /**
     *  Gets a string containing the IDs of the groups for the current campaign. Returns their names
     *
     * @param string $groups Groups for the current campaign, comma separated
     * @return string
     */
    public function getGroupNames($groups='')
    {
        $groupsNames = '';

        $params = array(
            'function' => 'getGroups',
        );

        $groups = explode(',', $groups);

        foreach ($groups AS $group) {

            $params['id'] = $group;
            $groupData = $this->_mrsyncController->APICall($params);
            $groupsNames .= $groupData[0]->name . ', ';

        }

        return substr($groupsNames, 0, strlen($groupsNames)-2);
    }

    /**
     * Gets and array and campaigns from the API and returns and array prepared for a vBulletin select
     *
     * @param array $campaigns Array of campaigns returned from the API
     * @return array
     */
    public function prepareCampaignsForSelect($campaigns = array())
    {
        $campaignSelect = array();

        if (count($campaigns) > 0) {

            foreach ($campaigns AS $campaign) {
                $campaignSelect[$campaign->id] = $campaign->subject . $this->_vbphrase['groups_the_campaign_is_sent_to'] . $this->getGroupNames($campaign->groups);
            }

        }

        return $campaignSelect;
    }

    /**
     * Shows all available campaigns in the Mailing Manager
     */
    public function showAvailableCampaigns()
    {
        $params = array(
            'function' => 'getCampaigns',
        );

        $groups = $this->_mrsyncController->getGroups();
        $campaigns = $this->prepareCampaignsForSelect($this->_mrsyncController->APICall($params));

        require_once(DIR . '/includes/mrsync/views/showCampaignsView.php');
        return null;
    }

    /**
     * Updates a campaign with the selected groups
     *
     * @param integer $campaign The ID of the campaign to launch
     * @param array $groups Array of groups to launch the campaign to
     * @return null
     */
    public function updateCampaign($campaign = 0, $groups = array())
    {
        $groupsText = '';
        foreach ($groups AS $group) {
            $groupsText .= $group . ',';
        }

        $groupsText = substr($groupsText, 0, strlen($groupsText)-1);

        $params = array(
            'function' => 'getCampaigns',
            'id'       => $campaign
        );

        $campaigns = $this->_mrsyncController->APICall($params);

        $params = array(
            'function'         => 'updateCampaign',
            'id'               => $campaign,
            'subject'          => $campaigns[0]->subject,
            'mailboxFromId'    => $campaigns[0]->mailbox_from_id,
            'mailboxReplyId'   => $campaigns[0]->mailbox_reply_id,
            'mailboxReportId'  => $campaigns[0]->mailbox_report_id,
            'emailReport'      => $campaigns[0]->email_report,
            'groups'           => $groupsText,
            'text'             => $campaigns[0]->text,
            'html'             => $campaigns[0]->html,
            'packageId'        => $campaigns[0]->package_id,
            'attachments'      => $campaigns[0]->attachs,
            'campaignFolderId' => $campaigns[0]->id_campaign_folder,
        );

        $result = $this->_mrsyncController->APICall($params);

        return null;
    }

    /**
     * Launches a campaign based on its ID
     *
     * @param integer $campaign The ID of the campaign to launch
     * @param array $groups Array of groups to launch the campaign to
     * @return null
     */
    public function launchCampaign($campaign = 0, $groups = array())
    {
        if (count($groups) > 0) {
            $this->updateCampaign($campaign, $groups);
        }

        $params = array(
            'function' => 'sendCampaign',
            'id'       => $campaign
        );

        $campaigns = $this->_mrsyncController->APICall($params);

        if (is_int($campaigns)) {
            $message = $this->_vbphrase['campaign_successfully_launched'];
        } else {
            $message = $this->_vbphrase['there_was_a_problem_launching_the_campaign'];
        }

        require_once(DIR . '/includes/mrsync/views/showLaunchSummaryView.php');
        return null;
    }

}