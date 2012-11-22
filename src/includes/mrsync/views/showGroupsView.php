<?php

// Controller: mrsyncController
// Action: showGroupsForm

print_cp_header($this->_vbphrase['mailrelay_user_sync']);
print_form_header('mrsync_admin', 'sync');
print_table_header($this->_vbphrase['sync_users_step_2']);
print_description_row($this->_vbphrase['second_step_explanation']);

construct_hidden_code('hostname', $hostname);
construct_hidden_code('username', $username);
construct_hidden_code('password', $password);
construct_hidden_code('apiKey', $this->_apiKey);        

print_select_row ('<strong>' . $this->_vbphrase['sync_vbulletin_groups'] . '</strong>', 'vBulletinGroups[]', $vBulletinGroups, '', false, 0, true);
print_description_row('<br/>');

print_select_row ('<strong>' . $this->_vbphrase['sync_vbulletin_groups'] . '</strong>', 'vBulletinSocialGroups[]', $vBulletinSocialGroups, '', false, 0, true);
print_description_row('<br/>');

print_select_row ('<strong>' . $this->_vbphrase['sync_groups'] . '</strong>', 'groups[]', $groupSelect, '', false, 0, true);
print_description_row('<br/>');
print_submit_row($this->_vbphrase['sync_send']);
print_cp_footer();  