<div class="wrap">
<?php screen_icon(); ?>
<h2><?php echo self::$longTitle; ?></h2>
<form method="post" action="options.php">
	<?php wp_nonce_field('update-options'); ?>
	<input type="hidden" name="action" value="update" />
	<input type="hidden" name="page_options" value="fancy_anywhere_username,fancy_anywhere_custom_button" />
	<table class="form-table">
	<tr valign="top">
		<th scope="row"><label for="fancy_anywhere_username"><?php _e('Username', 'fancy'); ?></label></th>
		<td>
			<input type="text" name="fancy_anywhere_username" id="fancy_anywhere_username" value="<?php echo get_option('fancy_anywhere_username') ?>" class="regular-text" />
			<p class="description">Your Fancy username</p>
		</td>
	</tr>
	<tr valign="top">
		<th scope="row"><label for="fancy_anywhere_custom_button"><?php _e('Custom Button', 'fancy'); ?></label></th>
		<td>
			<input type="text" name="fancy_anywhere_custom_button" id="fancy_anywhere_custom_button" value="<?php echo get_option('fancy_anywhere_custom_button') ?>" class="regular-text" />
			<p class="description">(optional) URL of custom button image</p>
		</td>
	</tr>
	</table>
	<p class="submit">
		<input type="submit" id="submit" class="button button-primary" value="<?php _e('Save Changes', 'fancy'); ?>" />
	</p>
</form>
</div>
