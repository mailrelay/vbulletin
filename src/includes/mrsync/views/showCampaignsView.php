<?php

 // Controller: mrsyncLaunchController
 // Action: showAvailableCampaigns

print_cp_header($this->_vbphrase['list_available_campaigns']);
   
print_form_header('mrsync_launch_admin', 'launch');
print_table_header($this->_vbphrase['launch_previously_created_campaign']);

print_description_row($this->_vbphrase['select_campaign_to_launch']);
print_description_row('<br/>');
print_select_row ('<strong>' . $this->_vbphrase['select_campaign_element'] . '</strong>', 'campaign', $campaigns, '', false, 0, false);
print_description_row('<br/>');

print_description_row($this->_vbphrase['select_campaign_launch_groups']);
print_description_row('<br/>');
print_select_row ('<strong>' . $this->_vbphrase['campaign_launch_groups'] . '</strong>', 'groups[]', $groups, '', false, 0, true);
print_description_row('<br/>');

print_submit_row($this->_vbphrase['sync_send']);

print_cp_footer(); 