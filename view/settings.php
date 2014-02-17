<div class="aws-content aws-settings waz-settings">

	<h3>Access Key</h3>

	<?php if ( $this->are_key_constants_set() ) : ?>

	<p>
		<?php _e( 'You&#8217;ve already defined your Zencoder access keys in your wp-config.php.', 'waz' ); ?>
	</p>
	<p>
		<?php _e( 'If you&#8217;d prefer to manage them here and store them in the database (not recommended), simply remove the lines from your wp-config.', 'waz' ); ?>
	</p>

	<?php else : ?>

	<p>
		<?php printf( __( 'If you don&#8217;t have a Zencoder account yet, you need to <a href="%s">sign up</a>.', 'waz' ), 'https://app.zencoder.com/signup/' ); ?>
	</p>
	<p>
		<?php printf( __( 'Once you&#8217;ve signed up, you can use the Full Access API Key provided, or create a new one. <a href="%s">You can view your keys here.</a>', 'waz' ), 'https://app.zencoder.com/api' ); ?>
	</p>
	<p>
		<?php _e( 'Once you have the key you\'d like to use, copy the folowing code to your wp-config.php and replace the stars with the key.', 'waz' ); ?>
	</p>

	<pre>define( 'AWS_ZENCODER_API_KEY', '********************' );</pre>

	<p class="reveal-form">
		<?php _e( 'If you&#8217;d rather not to edit your wp-config.php and are ok storing the keys in the database (not recommended), <a href="">click here to reveal a form.</a>', 'waz' ); ?>
	</p>

	<form method="post" <?php echo ( !$this->get_api_key() ) ? 'style="display: none;"' : ''; ?>>

	<?php if ( isset( $_POST['api_key'] ) ) : ?>
	<div class="aws-updated">
		<p><strong>Settings saved.</strong></p>
	</div>
	<?php endif; ?>

	<input type="hidden" name="action" value="save" />
	<?php wp_nonce_field( 'waz-save-settings' ) ?>

	<table class="form-table">
	<tr valign="top">
		<th width="33%" scope="row"><?php _e( 'API Key:', 'waz' ); ?></th>
		<td><input type="text" name="api_key" value="<?php echo esc_attr( $this->get_api_key() ); ?>" size="50" autocomplete="off" /></td>
	</tr>
	<tr valign="top">
		<td colspan="2">
			<button type="submit" class="button button-primary"><?php _e( 'Save Changes', 'waz' ); ?></button>
			<?php if ( $this->get_api_key() ) : ?>
			&nbsp;<button class="button remove-keys"><?php _e( 'Remove Keys', 'waz' ); ?></button>
			<?php endif; ?>
		</td>
	</tr>
	</table>

	</form>

	<?php endif; ?>

</div>
