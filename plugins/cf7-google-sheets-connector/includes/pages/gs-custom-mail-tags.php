
<div class="cd-faq-items">
	<ul id="basics" class="cd-faq-group">
		<li class="content-visible">
			<a class="cd-faq-trigger" data-id="3" href="#0"><?php echo esc_html( __( 'Custom Mail Tags ', 'cf7-google-sheets-connector' ) ); ?><span class="pro"><?php echo esc_html( __( 'Pro ', 'cf7-google-sheets-connector' ) ); ?></span></a>
			<div class="cd-faq-content cd-faq-content3" style="display: none;">
				<div class="gs-demo-fields gs-third-block">
					<?php 
                     if(isset($form_id) && !empty($form_id)){
					     $this->display_form_custom_tag( $form_id ); 
				      }
					?>
				 </div>
			</div>
		</li>
	</ul>
</div>
