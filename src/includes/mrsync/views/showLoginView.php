<?php

 // Controller: mrsyncController
 // Action: showLogin

print_cp_header($this->_vbphrase['mailrelay_user_sync']);

if ( !empty($message) ) {
    print_table_start();
    print_table_header($this->_vbphrase['sync_warning']);
    print_description_row($message);
    print_table_footer();
    echo '<br/><br/>';
}

print_form_header('mrsync_admin', 'authenticate');
print_table_header($this->_vbphrase['sync_users_step_1']);
print_description_row($this->_vbphrase['first_step_explanation']);
print_input_row('<strong>' . $this->_vbphrase['sync_host'] . '</strong>', 'hostname', $hostname);
print_input_row('<strong>' . $this->_vbphrase['sync_user'] . '</strong>', 'username', $username);
print_password_row('<strong>' . $this->_vbphrase['sync_pass'] . '</strong>', 'password', $password);
print_description_row('<br/><br/>');       
print_submit_row($this->_vbphrase['sync_send']);
print_cp_footer(); 