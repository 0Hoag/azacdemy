
<div class="cd-faq-items">
	<ul id="basics" class="cd-faq-group">
		<li class="content-visible">
			<a class="cd-faq-trigger" data-id="1" href="#0"><?php echo esc_html( __( 'Field List - ', 'cf7-google-sheets-connector' ) ); ?><span class="pro"><?php echo esc_html( __( 'Pro', 'cf7-google-sheets-connector' ) ); ?></span></a>
			<div class="cd-faq-content cd-faq-content1" style="display: block;">
				<div class="gs-demo-fields gs-second-block">
                  <h2 class="inner-title"><span class="gs-info"><?php echo esc_html( __( 'Map mail tags with custom header name and save automatically to google sheet. ', 'cf7-google-sheets-connector')); ?> </span></h2>
					<?php 
                 if(isset($form_id) && !empty($form_id)){
	               $this->display_form_fields( $form_id );
                  }
                  ?>
				</div>
			</div>
		</li>
	</ul>
</div>
			
			