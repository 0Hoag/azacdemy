<!-- plugin promotion footer-->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
<?php
// function remove_footer_admin () 
// {
//     echo '<p id="footer-left" class="alignleft">
// 		Please rate <strong>GSheetConnector for CF7</strong> <a href="https://wordpress.org/support/plugin/cf7-google-sheets-connector/reviews/" target="_blank" rel="noopener noreferrer">★★★★★</a> on <a href="https://wordpress.org/support/plugin/cf7-google-sheets-connector/reviews/" target="_blank" rel="noopener">WordPress.org</a> to help us spread the word.	</p>';
// }
// add_filter('admin_footer_text', 'remove_footer_admin');

// Custom footer text with review link
function wc_gsheetconnector_admin_footer_text() {
    $review_url  = 'https://wordpress.org/support/plugin/cf7-google-sheets-connector/reviews/';
    $plugin_name = 'GSheetConnector for CF7';

    $text = sprintf(
        /* translators: %1$s: plugin name, %2$s: link to reviews */
        esc_html__(
            'Enjoy using %1$s? Check out our reviews or leave your own on %2$s.',
            'cf7-google-sheets-connecto'
        ),
        '<strong>' . esc_html( $plugin_name ) . '</strong>',
        '<a href="' . esc_url( $review_url ) . '" target="_blank" rel="noopener">' . esc_html__( 'WordPress.org', 'cf7-google-sheets-connector' ) . '</a>'
    );

    echo wp_kses_post( '<span id="footer-left" class="alignleft">' . $text . '</span>' );
}
add_filter( 'admin_footer_text', 'wc_gsheetconnector_admin_footer_text' );
?>

<div class="gsheetconnect-footer-promotion">
  <p><?php echo esc_html( __('Made with ♥ by the GSheetConnector Team', 'cf7-google-sheets-connector' ) ); ?></p>
  <ul class="wpforms-footer-promotion-links">
    <li> <a href="https://www.gsheetconnector.com/support" target="_blank"><?php echo esc_html( __('Support', 'cf7-google-sheets-connector' ) ); ?></a> </li>
    <li> <a href="https://www.gsheetconnector.com/docs/cf7-gsheetconnector/" target="_blank"><?php echo esc_html( __('Docs', 'cf7-google-sheets-connector' ) ); ?></a> </li>
    <li> <a href="https://profiles.wordpress.org/westerndeal/#content-plugins"><?php echo esc_html( __('Free Plugins', 'cf7-google-sheets-connector' ) ); ?></a> </li>
  </ul>
  <ul class="wpforms-footer-promotion-social">
    <li> <a href="https://www.facebook.com/gsheetconnectorofficial" target="_blank"> <i class="fa fa-facebook-square" aria-hidden="true"></i> </a> </li>
    <li> <a href="https://www.instagram.com/gsheetconnector/" target="_blank"> <i class="fa fa-instagram" aria-hidden="true"></i> </a> </li>
    <li> <a href="https://www.linkedin.com/company/gsheetconnector/" target="_blank"> <i class="fa fa-linkedin-square" aria-hidden="true"></i> </a> </li>
    <li> <a href="https://twitter.com/gsheetconnector?lang=en" target="_blank"> <i class="fa fa-twitter-square" aria-hidden="true"></i> </a> </li>
    <li> <a href="https://www.youtube.com/@GSheetConnector" target="_blank"> <i class="fa fa-youtube-square" aria-hidden="true"></i> </a> </li>
  </ul>
</div>
