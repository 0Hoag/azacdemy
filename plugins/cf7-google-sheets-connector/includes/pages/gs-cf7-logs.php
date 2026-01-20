<div id="opener">
<form d="gs_cf7db_setting" method="post">
				<div class="wrap gs-form opacity-down">
                <div class="gs-card">
                    
                     <h2 class="inner-title"><span><?php echo esc_html( __('CF7 Google Sheet Database Settings', 'cf7-google-sheets-connector' ) ); ?></span> <span class="pro-ver"><?php echo esc_html( __('PRO', 'cf7-google-sheets-connector' ) ); ?></span></h2>
						<div><label><?php echo esc_html( __('Enable/Disable CF7 Database Settings', 'cf7-google-sheets-connector' ) ); ?> </label></div>
						<label style="display: block;">
							<input type="checkbox" class="gs-checkbox" name="gs_cf7db_setting" checked="">ON/OFF
						</label>
						<br>
						<div class="select-info">
							<input type="button" class="button button-primary button-large"  name="gs-cf7db-setting-btn" value="Save" id="gs-cf7db-setting-btn">
						</div>
					</div>
				</div>
			</form>
 
<div class="database-table"  style="opeacity:0.75; margin-top:30px;">
			<div id="icon-users" class="icon32"></div>
			<h2 class="inner-title"><?php echo esc_html( __('Contact Forms List', 'cf7-google-sheets-connector' ) ); ?></h2>
		 
		  
	
		<table style="opacity:0.75;" class="wp-list-table widefat fixed striped table-view-list contact_page_wpcf7-google-sheet-config">
			<thead>
	<tr>
		<th scope="col" id="name" class="manage-column column-name column-primary">Name</th><th scope="col" id="count" class="manage-column column-count">Count</th>	</tr>
	</thead>

	<tbody id="the-list">
		<tr><td class="name column-name has-row-actions column-primary" data-colname="Name"><a class="row-title" href="#">Contact form 1</a><button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button></td><td class="count column-count" data-colname="Count"><a class="row-title" href="admin.php?page=wpcf7-google-sheet-config&amp;tab=cf7_db&amp;formId=5">0</a></td></tr>	</tbody>

	<tfoot>
	<tr>
		<th scope="col" class="manage-column column-name column-primary">Name</th><th scope="col" class="manage-column column-count">Count</th>	</tr>
	</tfoot>

</table>
	
	
	
<div class="tablenav bottom">

<div class="alignleft actions bulkactions">
					</div>
<div class="tablenav-pages one-page"><span class="displaying-num">1 item</span>
<span class="pagination-links"><span class="tablenav-pages-navspan button disabled" aria-hidden="true">«</span>
<span class="tablenav-pages-navspan button disabled" aria-hidden="true">‹</span>
<span class="screen-reader-text">Current Page</span><span id="table-paging" class="paging-input"><span class="tablenav-paging-text">1 of <span class="total-pages">1</span></span></span>
<span class="tablenav-pages-navspan button disabled" aria-hidden="true">›</span>
<span class="tablenav-pages-navspan button disabled" aria-hidden="true">»</span></span></div>
		<br class="clear">
</div>
	
	</div>
</div>



<?php include( GS_CONNECTOR_PATH . "includes/pages/pro-popup.php" ) ;?>

