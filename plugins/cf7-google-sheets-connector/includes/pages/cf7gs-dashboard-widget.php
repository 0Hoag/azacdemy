<?php
/*
 * CF7GS Dashboard Widget
 * @since 2.1
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
   exit();
}
?>
<div class="dashboard-content">
<?php 
$gs_connector_service = new Gs_Connector_Service();

$forms_list = $gs_connector_service->get_forms_connected_to_sheet();
?>
    <div class="main-content">
       <div>
          <h3>
           <?php echo esc_html__( "Contact Forms (CF7) connected with Google Sheets.", "cf7-google-sheets-connector" ); ?>
          </h3>

          <ul class="contact-form-list">
				<?php 
				if( ! empty( $forms_list ) ){
               foreach( $forms_list as $key=>$value ) {
                  $meta_value = unserialize( $value->meta_value );
                  $sheet_name = $meta_value['sheet-name'];
                  if( $sheet_name !== "" ) {
				?>
              <a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcf7&post=' . intval( $value->ID ) . '&action=edit' ) ); ?>" target="_blank">
    <li style="list-style:none;"><?php echo esc_html( $value->post_title ); ?></li>
</a>

				<?php 
                  } 
                  
               }
            } else { ?>
            <li>
    <span><?php echo esc_html__( "No Contact Forms (CF7) is connected with Google Sheets.", "cf7-google-sheets-connector" ); ?></span>
</li>

            <?php
            }
            ?>
           </ul>
       </div>
    </div> <!-- main-content end -->
</div> <!-- dashboard-content end -->
<style type="text/css">
.postbox-header .hndle {
justify-content: flex-start !important;
}
</style>