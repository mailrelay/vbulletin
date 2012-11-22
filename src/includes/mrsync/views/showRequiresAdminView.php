<?php

// Controller: mrsyncSettingsController
// Action: showRequiresAdmin

print_cp_header($this->_vbphrase['mailrelay_user_sync']);
print_table_start();
print_table_header($this->_vbphrase['sync_warning']);
print_description_row($this->_vbphrase['sync_can_administer']);
print_table_footer();
print_cp_footer();