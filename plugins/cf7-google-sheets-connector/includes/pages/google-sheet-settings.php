<?php
/*
 * Google Sheet configuration and settings page
 * @since 1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
   exit();
}

$active_tab = 'integration';

if ( isset( $_GET['tab'] ) ) {
    $active_tab = sanitize_text_field(
        wp_unslash( $_GET['tab'] )
    );
}


$active_tab_name = '';
if($active_tab ==  'integration'){
  $active_tab_name = 'Integration';
}
elseif($active_tab ==  'settings'){
  $active_tab_name = 'Role Settings';
}
elseif($active_tab ==  'cf7_db'){
  $active_tab_name = 'CF7 Database';
}
elseif($active_tab ==  'beta_version'){
  $active_tab_name = 'Beta Version';
}
elseif($active_tab ==  'system-status'){
  $active_tab_name = 'System Status';
}

// Check plugin version and subscription plan
 $plugin_version = defined('GS_CONNECTOR_VERSION') ? GS_CONNECTOR_VERSION : 'N/A';

?>



<div class="gsheet-header">
			<div class="gsheet-logo">
				<a href="https://www.gsheetconnector.com/"><i></i></a></div>
			<h1 class="gsheet-logo-text"><span><?php echo esc_html( __('GSheetConnector for CF7', 'cf7-google-sheets-connector' ) ); ?></span> <small><?php echo esc_html( __('Version :', 'cf7-google-sheets-connector' ) ); ?> <?php echo esc_html($plugin_version,'cf7-google-sheets-connector'); ?> </small></h1>
			
	<ul> 
		<li><a href="https://www.gsheetconnector.com/docs/cf7-gsheetconnector/introduction" title="Document" target="_blank"><i class="fa-regular fa-file-lines"></i></a></li>
		<li><a href="https://www.gsheetconnector.com/support" title="Support" target="_blank"><i class="fa-regular fa-life-ring"></i></a></li>
		<li><a href="https://wordpress.org/plugins/cf7-google-sheets-connector/#developers" title="Changelog" target="_blank"><i class="fa-solid fa-bullhorn"></i></a></li>
	</ul>
	
	
		</div>

<div class="breadcrumb">
    <span class="dashboard-gsc"><?php echo esc_html( __('DASHBOARD', 'cf7-google-sheets-connector' ) ); ?></span>

    <span class="divider-gsc"> / </span>

    <span class="modules-gsc">
    <?php echo esc_html( $active_tab_name ); ?>
</span>

</div>

	<?php
    $tabs = array(  
    'integration'   => esc_html__( 'Integration', 'cf7-google-sheets-connector' ),
    'settings'      => esc_html__( 'Role Settings', 'cf7-google-sheets-connector' ),
    'cf7_db'        => esc_html__( 'CF7 Database', 'cf7-google-sheets-connector' ),
    'beta_version'  => esc_html__( 'Beta Version', 'cf7-google-sheets-connector' ),
    // 'faq'        => esc_html__( 'FAQ', 'cf7-google-sheets-connector' ),
    // 'demos'      => esc_html__( 'Demos', 'cf7-google-sheets-connector' ),
    'system-status' => esc_html__( 'System Status', 'cf7-google-sheets-connector' ),
);

       echo '<div id="icon-themes" class="icon32"><br></div>';
       echo '<div class="nav-tab-wrapper">';
       foreach( $tabs as $tab => $name ){
           $class = ( $tab == $active_tab ) ? ' nav-tab-active' : '';
            echo '<a class="nav-tab' . esc_attr( $class ) . '" href="' .
              esc_url(
                  add_query_arg(
                      array(
                          'page' => 'wpcf7-google-sheet-config',
                          'tab'  => $tab,
                      ),
                      admin_url( 'admin.php' )
                  )
              ) . '">' .
              esc_html( $name ) .
          '</a>';

       }
       echo '</div><div class="wrap-gsc">';
   	switch ( $active_tab ){
        case 'integration' :
   		   $gs_intigrate = new Gs_Connector_Free_Init();
			   $gs_intigrate->google_sheet_config();
   		   break;
		case 'settings' :
   		   include( GS_CONNECTOR_PATH . "includes/pages/gs-role-settings.php" );
   		   break;
		case 'cf7_db' :
   		   include( GS_CONNECTOR_PATH . "includes/pages/gs-cf7-logs.php" );
   		   break;
		case 'beta_version' :
   		   include( GS_CONNECTOR_PATH . "includes/pages/gs-beta-version.php" );
   		   break;
		case 'system-status' :
   		   include( GS_CONNECTOR_PATH . "includes/pages/gs-integrate-info.php" );
   		   break;
	}
	?>
</div>

<?php include( GS_CONNECTOR_PATH . "includes/pages/admin-footer.php" ) ;?>
