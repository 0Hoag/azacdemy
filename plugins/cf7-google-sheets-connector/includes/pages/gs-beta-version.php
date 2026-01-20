<div class="wrap gs-form" id="opener">
    <div class="card" id="googlesheet">
        <div class="wrap gs-form opacity-down">
            <div class="gsc-beta-block">
            
            	 <h2 class="inner-title"><span><?php echo esc_html( __('CF7 Google Sheet Beta Opt-in', 'cf7-google-sheets-connector' ) ); ?></span> <span class="pro-ver"><?php echo esc_html( __('PRO', 'cf7-google-sheets-connector' ) ); ?></span></h2>
            
                <label><h3><?php echo esc_html( __('Beta Opt-in', 'cf7-google-sheets-connector' ) ); ?></h3></label>
                <p><?php echo esc_html( __('Turn on the Beta Version feature to get notified about new beta releases. The beta version will not install automatically and you always have the option to ignore it.', 'cf7-google-sheets-connector' ) ); ?>                
            </p>

                <label class="switch">
                    <input type="checkbox" name="beta-version-setting" value="" class="beta-version-setting">
                    <span class="slider round"></span>
                </label>
                <label><strong style="font-size: 16px;"><?php echo esc_html( __('Enable Beta Version', 'cf7-google-sheets-connector' ) ); ?></strong></label>
                <p><?php echo esc_html( __('Get updates for pre-release versions', 'cf7-google-sheets-connector' ) ); ?></p>
                <input 
    type="hidden" 
    name="gs-ajax-nonce" 
    id="gs-ajax-nonce" 
    value="<?php echo esc_attr( wp_create_nonce( 'gs-ajax-nonce' ) ); ?>">

               
                <div class="select-info" style="margin-top:10px;">
                    <p class="beta-content-msg-cf7gsc" style="color:#479C4B;"></p>
                   <input 
    type="button" 
    class="button button-primary button-large beta-save" 
    name="gsc_pro_beta_settings" 
    value="<?php echo esc_attr__( 'Save', 'cf7-google-sheets-connector' ); ?>" 
    id="beta-save">

                    <span class="beta-loading-sign-cf7gsc">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>

                </div>
            </div>
        </div>
    </div>

</div>

<?php  include( GS_CONNECTOR_PATH . "includes/pages/pro-popup.php" ) ;?>
