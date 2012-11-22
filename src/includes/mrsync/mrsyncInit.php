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
$status = 'production'; // In production change to anything other than 'development'

// Enable errors while developing
if ( $status == 'development' ) {
    error_reporting(E_ALL & ~E_NOTICE);
    ini_set('display_errors','On');
}

// Require vBulletin necessary components
require_once(DIR . '/includes/adminfunctions_profilefield.php');
require_once(DIR . '/includes/adminfunctions_user.php');
require_once(DIR . '/includes/adminfunctions_options.php');

try {
    // Require product libraries
    require_once(DIR . '/includes/mrsync/models/mrsyncModel.php');
    require_once(DIR . '/includes/mrsync/controllers/mrsyncController.php');
    require_once(DIR . '/includes/mrsync/controllers/mrsyncSettingsController.php');
    require_once(DIR . '/includes/mrsync/controllers/mrsyncLaunchController.php');

    $mrsyncModel = new mrsyncModel($db);
    $mrsyncController = new mrsyncController( $vbphrase, $mrsyncModel );    
    $mrsyncSettingsController = new mrsyncSettingsController( $vbphrase, $mrsyncModel, $mrsyncController );
    $mrsyncLaunchController = new mrsyncLaunchController( $vbphrase, $mrsyncModel, $mrsyncController );
    
} catch ( Exception $e ) {
    print_cp_header('Mailrelay, vBulletin users sync');
    print_table_start();
    print_table_header('Error');
    print_description_row('Some necessary files weren\'t found. Please make sure you have upload all files.');
    print_table_footer();
    print_cp_footer();    
    exit();    
}

// can_administer is a Core vBulletin function
// Checks whether or not the visiting user has administrative permissions
if ( !can_administer() ) {   
    $mrsyncSettingsController->showRequiresAdmin();   
    exit();
}

// Limit of users to sync in each sync batch
$limit = 50;