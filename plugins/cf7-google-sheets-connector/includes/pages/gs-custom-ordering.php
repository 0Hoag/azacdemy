
<div class="cd-faq-items">
   <ul id="basics" class="cd-faq-group">
      <li class="content-visible">
         <a class="cd-faq-trigger" data-id="4" href="#0"><?php echo esc_html( __( 'Custom Ordering - ', 'cf7-google-sheets-connector' ) ); ?><span class="pro"><?php echo esc_html( __( 'Pro', 'cf7-google-sheets-connector' ) ); ?></span></a>

         <div class="cd-faq-content cd-faq-content4" style="display: none;">
            <div class="gs-demo-fields gs-second-block">
                <h2 class="inner-title"><span class="gs-info"><?php echo esc_html( __( 'Not showing correct header name ? Un-select and select the fields checkbox again. It happens due to various reasons like change in field/mail tag name.', 'cf7-google-sheets-connector')); ?> </span></h2>

                 <ul class="connected-sortable droppable-area1" id="drag">
                       <?Php    $count = 0; ?>
                           <div class="drag-item"><li class="draggable-item"><?php echo ""; ?><input type="hidden" data-count="<?php echo esc_attr($count); ?>" name="gs-drag-index[<?php echo esc_attr($count); ?>]" id="gs-drag-drop" value="<?php echo ""; ?>"></li>
                           </div>
                            <?php
               $count++;
               ?>
                        </ul>

                        <?php 
                      if(isset($form_id) && !empty($form_id)){

                        $saved_mail_tags = get_post_meta( $form_id, 'gs_map_mail_tags' );
      
      // fetch mail tags
      $assoc_arr = [ ];
      $meta = get_post_meta( $form_id, '_form', true );
      $fields = $this->get_contact_form_fields( $meta );
      if( $fields ) {
         foreach ( $fields as $field ) {
            $single = $this->get_field_assoc( $field );
            if ( $single ) {
               $assoc_arr[] = $single;
            }
         }
      }
      
      if( ! empty( $assoc_arr ) ) {
      ?>
      <ul class="gs-field-list">
      <?php
      $count = 0;
      foreach ( $assoc_arr as $key => $value ) {
         foreach ( $value as $k => $v ) {
            $saved_val = "";
            $checked = "";
            if( ! empty( $saved_mail_tags ) && array_key_exists( $v, $saved_mail_tags[0] ) ) :
               $saved_val = $saved_mail_tags[0][$v];
               $checked = "checked";
            endif;
            
            $placeholder = preg_replace('/[\\_]|\\s+/', '-', $v );
            ?>
               <li>
                  <div class="input-field">
                   
                       <label for="enable-sorting-option" class="button-woo-toggle-cf7" id="sorting-toggle"></label>
                  </div>
                  <div class="label"><?php echo esc_attr($v); ?> </div>
                  <div class="field-input">
                     <input type="text" name="gs-custom-header[<?php echo esc_attr($count); ?>]" value="<?php echo esc_attr($saved_val); ?>" placeholder="<?php echo esc_attr($placeholder); ?>" disabled>
                  </div>
               </li>
         <?php 
         $count++;
         }
      }
   }
}
      ?>
      </ul>
</div>
</div>
</li>
</ul>
</div>