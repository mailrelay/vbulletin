<?php

// Controller: mrsyncSettingsController
// Action: showSettings

print_cp_header($this->_vbphrase['mailrelay_user_sync']);

if ( !empty($message) ) {
    print_table_start();
    print_table_header($this->_vbphrase['sync_warning']);
    print_description_row($message);
    print_table_footer();
    echo '<br/><br/>';
}

print_form_header('mrsync_settings_admin', 'save');
print_table_header( $this->_vbphrase['mrsync_options'] );

// Mailrelay account Hostname, username, password
print_description_row( $this->_vbphrase['mrsync_settings_mr_data'], false, 2);
print_input_row('<strong>' . $this->_vbphrase['sync_host'] . '</strong>', 'hostname', $formValues['hostname']);
print_input_row('<strong>' . $this->_vbphrase['sync_user'] . '</strong>', 'username', $formValues['username']);
print_password_row('<strong>' . $this->_vbphrase['sync_pass'] . '</strong>', 'password', $formValues['password']);        
print_description_row('<br/><br/>', false, 2);

// Auto sync users when registering
print_description_row( $this->_vbphrase['mrsync_autosync_users'], false, 2);
print_checkbox_row('<strong>' . $this->_vbphrase['mrsync_enable_autosync_users'] . '</strong>', 'enableAutoSync', $formValues['enableAutoSync'], 1);        
print_description_row('<br/><br/>', false, 2);

// If there are no settings saved in the database, we can't fetch the groups from the MR account
// There's no point showing the MR group select for auto syncing new users
if (!empty($formValues['hostname']) && !empty($formValues['username']) && !empty($formValues['password']) ) {
    print_select_row ('<strong>' . $this->_vbphrase['sync_groups'] . '</strong>', 'groups[]', $groupSelect, unserialize($formValues['groupsToSyncNewUsers']), false, 0, true);
    print_description_row('<br/><br/>', false, 2);
}

// Use SMTP for all vBulletin email sends
print_description_row( $this->_vbphrase['mrsync_smtp_explanation'], false, 2);
print_checkbox_row('<strong>' . $this->_vbphrase['mrsync_smtp_enable'] . '</strong>', 'enableSMTP', $formValues['enableSMTP'], 1);        
print_description_row('<br/><br/>', false, 2);

print_submit_row($this->_vbphrase['sync_send']);        
print_cp_footer(); 