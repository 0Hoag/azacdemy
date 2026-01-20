<div class="wrap gs-form" id="opener">
	
   

  <div class="gs-card opacity-down">
  	
     <h2 class="inner-title"><span><?php echo esc_html( __( 'CF7 Google Sheet Roles Settings', 'cf7-google-sheets-connector' ) ); ?></span> <span class="pro-ver"><?php echo esc_html( __( 'PRO', 'cf7-google-sheets-connector' ) ); ?></span></h2>
     
  <div>
      <label><?php echo esc_html( __( 'Roles that can access Google Sheet Page', 'cf7-google-sheets-connector' ) ); ?></label>
    </div>
    <label class="toggle-role-cf7">
      <input type="checkbox" class="gs-checkbox" disabled="disabled" checked="checked">
      <span class="slider-role-cf7"></span></label>
    <label style="margin-left:10px;"><?php echo esc_html( __( 'Administrator', 'cf7-google-sheets-connector' ) ); ?></label>
    <span class="spacer"></span>
    <label class="toggle-role-cf7">
      <input type="checkbox" class="gs-checkbox" name="gs_page_roles_setting[]" value="editor"  checked="checked">
      <span class="slider-role-cf7"></span></label>
    <label style="margin-left:10px;"><?php echo esc_html( __( 'Editor', 'cf7-google-sheets-connector' ) ); ?></label>
    <span class="spacer"></span>
    <label class="toggle-role-cf7">
      <input type="checkbox" class="gs-checkbox" name="gs_page_roles_setting[]" value="author">
      <span class="slider-role-cf7"></span></label>
    <label style="margin-left:10px;"><?php echo esc_html( __( 'Author', 'cf7-google-sheets-connector' ) ); ?></label>
    <span class="spacer"></span>
    <label class="toggle-role-cf7">
      <input type="checkbox" class="gs-checkbox" name="gs_page_roles_setting[]" value="contributor">
      <span class="slider-role-cf7"></span></label>
    <label style="margin-left:10px;"><?php echo esc_html( __( 'Contributor', 'cf7-google-sheets-connector' ) ); ?></label>
    <span class="spacer"></span>
    <label class="toggle-role-cf7">
      <input type="checkbox" class="gs-checkbox" name="gs_page_roles_setting[]" value="subscriber">
      <span class="slider-role-cf7"></span></label>
    <label style="margin-left:10px;"><?php echo esc_html( __( 'Subscriber', 'cf7-google-sheets-connector' ) ); ?></label>
    <span class="spacer"></span>
    <label class="toggle-role-cf7">
      <input type="checkbox" class="gs-checkbox" name="gs_page_roles_setting[]" value="customer">
      <span class="slider-role-cf7"></span></label>
    <label style="margin-left:10px;"><?php echo esc_html( __( 'Customer', 'cf7-google-sheets-connector' ) ); ?></label>
    <span class="spacer"></span>
    <label class="toggle-role-cf7">
      <input type="checkbox" class="gs-checkbox" name="gs_page_roles_setting[]" value="shop_manager">
      <span class="slider-role-cf7"></span></label>
    <label style="margin-left:10px;"><?php echo esc_html( __( 'Shop manager', 'cf7-google-sheets-connector' ) ); ?></label>
    <span class="spacer"></span> <br>
    <div>
      <label><?php echo esc_html( __( 'Roles that can access Google Sheet Tab at Contact Form', 'cf7-google-sheets-connector' ) ); ?></label>
    </div>
    <label class="toggle-role-cf7">
      <input type="checkbox" class="gs-checkbox" disabled="disabled" checked="checked">
      <span class="slider-role-cf7"></span></label>
    <label style="margin-left:10px;"><?php echo esc_html( __( 'Administrator', 'cf7-google-sheets-connector' ) ); ?></label>
    <span class="spacer"></span>
    <label class="toggle-role-cf7">
      <input type="checkbox" class="gs-checkbox" name="gs_tab_roles_setting[]" value="editor">
      <span class="slider-role-cf7"></span></label>
    <label style="margin-left:10px;"><?php echo esc_html( __( 'Editor', 'cf7-google-sheets-connector' ) ); ?></label>
    <span class="spacer"></span>
    <label class="toggle-role-cf7">
      <input type="checkbox" class="gs-checkbox" name="gs_tab_roles_setting[]" value="author">
      <span class="slider-role-cf7"></span></label>
    <label style="margin-left:10px;"><?php echo esc_html( __( 'Author', 'cf7-google-sheets-connector' ) ); ?></label>
    <span class="spacer"></span>
    <label class="toggle-role-cf7">
      <input type="checkbox" class="gs-checkbox" name="gs_tab_roles_setting[]" value="contributor">
      <span class="slider-role-cf7"></span></label>
    <label style="margin-left:10px;"><?php echo esc_html( __( 'Contributor', 'cf7-google-sheets-connector' ) ); ?></label>
    <span class="spacer"></span>
    <label class="toggle-role-cf7">
      <input type="checkbox" class="gs-checkbox" name="gs_tab_roles_setting[]" value="subscriber">
      <span class="slider-role-cf7"></span></label>
    <label style="margin-left:10px;"><?php echo esc_html( __( 'Subscriber', 'cf7-google-sheets-connector' ) ); ?></label>
    <span class="spacer"></span>
    <label class="toggle-role-cf7">
      <input type="checkbox" class="gs-checkbox" name="gs_tab_roles_setting[]" value="customer">
      <span class="slider-role-cf7"></span></label>
    <label style="margin-left:10px;"><?php echo esc_html( __( 'Customer', 'cf7-google-sheets-connector' ) ); ?></label>
    <span class="spacer"></span>
    <label class="toggle-role-cf7">
      <input type="checkbox" class="gs-checkbox" name="gs_tab_roles_setting[]" value="shop_manager">
      <span class="slider-role-cf7"></span></label>
    <label style="margin-left:10px;"><?php echo esc_html( __( 'Shop manager', 'cf7-google-sheets-connector' ) ); ?></label>
    <span class="spacer"></span> <br>
    <div class="select-info">
     <input 
    type="submit" 
    class="button button-primary button-large" 
    name="gs_settings" 
    value="<?php echo esc_attr__( 'Save', 'cf7-google-sheets-connector' ); ?>" 
    id="roleSettingsSave">

    </div>
  </div>
</div>

<?php include( GS_CONNECTOR_PATH . "includes/pages/pro-popup.php" ) ;?>
