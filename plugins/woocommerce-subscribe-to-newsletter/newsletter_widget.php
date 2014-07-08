<?php
/**
 * Subscribe to Newsletter Widget
 *
 * @package		WooCommerce
 * @category	Widgets
 * @author		WooThemes
 */
class WooCommerce_Widget_Subscibe_to_Newsletter extends WP_Widget {

	/** Variables to setup the widget. */
	var $woo_widget_cssclass;
	var $woo_widget_description;
	var $woo_widget_idbase;
	var $woo_widget_name;

	/** constructor */
	function WooCommerce_Widget_Subscibe_to_Newsletter() {

		/* Widget variable settings. */
		$this->woo_widget_cssclass = 'widget_subscribe_to_newsletter';
		$this->woo_widget_description = __( 'Allow users to subscribe to your MailChimp or Campaign Monitor lists.', 'wc_subscribe_to_newsletter' );
		$this->woo_widget_idbase = 'woocommerce_subscribe_to_newsletter';
		$this->woo_widget_name = __('WooCommerce Subscribe to Newsletter', 'wc_subscribe_to_newsletter' );

		/* Widget settings. */
		$widget_ops = array( 'classname' => $this->woo_widget_cssclass, 'description' => $this->woo_widget_description );

		/* Create the widget. */
		$this->WP_Widget('woocommerce_subscribe_to_newsletter', $this->woo_widget_name, $widget_ops);
	}

	/** @see WP_Widget */
	function widget( $args, $instance ) {
		global $WC_Subscribe_To_Newsletter;

		if ( ! $WC_Subscribe_To_Newsletter->service )
			return;

		extract($args);

		$title     = $instance['title'];
		$listid    = $instance['list'];
		$show_name = ! empty( $instance['show_name'] );
		$title     = apply_filters('widget_title', $title, $instance, $this->id_base);

		echo $before_widget;

		if ($title) echo $before_title . $title . $after_title;

		?>
		<form method="post" id="subscribeform" action="#subscribeform" class="woocommerce">
			<?php
				if ( isset( $_POST['newsletter_email'] ) ) :
					$email = woocommerce_clean( $_POST['newsletter_email'] );
					$first = '';
					$last  = '';

					if ( isset( $_POST['newsletter_name'] ) ) {
						$name  = woocommerce_clean( trim( $_POST['newsletter_name'] ) );
						$name  = explode( ' ', $name );
						$first = current( $name );
						$last  = end( $name );
						if ( $first == $last )
							$last = '';
					}

					if ( ! is_email( $email ) ) :
						echo '<div class="woocommerce_error woocommerce-error">'.__('Please enter a valid email address.', 'wc_subscribe_to_newsletter').'</div>';
					else :
						$WC_Subscribe_To_Newsletter->service->subscribe( $first, $last, $email, $listid );
						echo '<div class="woocommerce_message woocommerce-message">'.__('Thanks for subscribing.', 'wc_subscribe_to_newsletter').'</div>';
					endif;
				endif;
			?>
			<div>
				<?php if ( $show_name ) : ?>
					<div>
						<label class="screen-reader-text hidden" for="s"><?php _e('Your Name:', 'wc_subscribe_to_newsletter'); ?></label>
						<input type="text" name="newsletter_name" id="newsletter_name" placeholder="<?php _e('Your name', 'wc_subscribe_to_newsletter'); ?>" />
					</div>
				<?php endif; ?>

				<div>
					<label class="screen-reader-text hidden" for="s"><?php _e('Email Address:', 'wc_subscribe_to_newsletter'); ?></label>
					<input type="text" name="newsletter_email" id="newsletter_email" placeholder="<?php _e('Your email address', 'wc_subscribe_to_newsletter'); ?>" />
				</div>

				<input type="submit" class="button" id="newsletter_subscribe" value="<?php _e('Subscribe', 'wc_subscribe_to_newsletter'); ?>" />
			</div>
		</form>
		<?php

		echo $after_widget;
	}

	/** @see WP_Widget->update */
	function update( $new_instance, $old_instance ) {
		$instance['title']     = strip_tags(stripslashes($new_instance['title']));
		$instance['list']      = strip_tags(stripslashes($new_instance['list']));
		$instance['show_name'] = empty( $new_instance['show_name'] ) ? false : true;
		return $instance;
	}

	/** @see WP_Widget->form */
	function form( $instance ) {
		global $wpdb, $WC_Subscribe_To_Newsletter;

		if ( ! $WC_Subscribe_To_Newsletter->service ) {
			echo '<p>' . __( 'You must set up API details in WooCommerce > Settings > Newsletter before using this widget.</p>', 'wc_subscribe_to_newsletter' );
			return;
		}

		$lists = $WC_Subscribe_To_Newsletter->service->get_lists();
		?>
			<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'wc_subscribe_to_newsletter') ?></label>
			<input type="text" class="widefat" id="<?php echo esc_attr( $this->get_field_id('title') ); ?>" name="<?php echo esc_attr( $this->get_field_name('title') ); ?>" value="<?php if (isset ( $instance['title'])) {echo esc_attr( $instance['title'] );} else {echo __('Newsletter', 'wc_subscribe_to_newsletter');} ?>" /></p>

			<p><label for="<?php echo $this->get_field_id('show_name'); ?>"><input type="checkbox" class="checkbox" id="<?php echo esc_attr( $this->get_field_id('show_name') ); ?>" name="<?php echo esc_attr( $this->get_field_name('show_name') ); ?>" <?php checked( ! empty( $instance['show_name'] ), true ); ?> /> <?php _e('Show Name Field?', 'wc_subscribe_to_newsletter') ?></label></p>

			<p><label for="<?php echo $this->get_field_id('list'); ?>"><?php _e('List:', 'woothemes') ?></label>
				<?php
				echo '<select id="' . esc_attr( $this->get_field_id('list') ) .'" name="' . esc_attr( $this->get_field_name('list') ) .'" class="widefat">';
				if ( $lists ) {
					foreach ( $lists as $key => $value ) {
						echo '<option value="' . $key . '" ' . ( $key == $instance['list'] ? 'selected="selected"' : '' ). '>' . $value . '</option>';
					}
				}
				echo '</select>';
				echo '<small>'.__('Choose a list to subscribe newsletter subscribers to or leave blank to use the list in your setting panel.', 'wc_subscribe_to_newsletter').'</small>';
?>
			</p>
		<?php
	}
} // WooCommerce_Widget_Subscibe_to_Newsletter