<div id="register-form" class="widecolumn">
	<?php 
		
		if(isset($_POST['action']) && $_POST['action'] == 'sas_update_account'){
			
			do_action( 'sas_update_account');
			exit;	
		}
	?>
	<?php if ( count( $attributes['errors'] ) > 0 ) : ?>
		<?php foreach ( $attributes['errors'] as $error ) : ?>
			<p>
				<?php echo $error; ?>
			</p>
		<?php endforeach; ?>
	<?php endif; ?>
	<!--form id="your-profile" action="<?php echo esc_url( admin_url( 'profile.php' ) ); ?>" method="post"-->
	<form id="your-profile" action="<?php echo the_permalink(); ?>" method="post">
		<?php wp_nonce_field('update-profile_' . $attributes['userdata']->ID) ?>
		<input type="hidden" name="from" value="profile">
		<input type="hidden" name="checkuser_id" value="<?php echo $attributes['userdata']->ID; ?>" />
		
		<p class="form-row">
			
			<input type="text" name="email" id="email" placeholder="<?php _e( '* Email', 'simple-account-system' ); ?>" value="<?php echo $attributes['userdata']->user_email; ?>">
		</p>

		<p class="form-row">
			
			<input type="text" name="first_name" id="first-name" placeholder="<?php _e( 'First name', 'simple-account-system' ); ?>" value="<?php echo $attributes['usermeta']['first_name'];  ?>">
		</p>

		<p class="form-row">
			
			<input type="text" name="last_name" id="last-name" placeholder="<?php _e( 'Last name', 'simple-account-system' ); ?>" value="<?php echo $attributes['usermeta']['last_name']; ?>">
		</p>
		
		<p class="form-row">
			
			<input type="text" name="sas_phone" id="sas_phone" placeholder="<?php _e( 'Phone', 'simple-account-system' ); ?>"  value="<?php echo $attributes['usermeta']['sas_phone']; ?>">
		</p>
		
		<p class="form-row">
			
			<input type="text" name="sas_address" id="sas_address" placeholder="<?php _e( 'Address', 'simple-account-system' ); ?>" value="<?php echo $attributes['usermeta']['sas_address']; ?>">
		</p>
		<p class="form-row">
		
			<input type="text" name="sas_country" id="sas_country" placeholder="<?php _e( 'Country', 'simple-account-system' ); ?>" value="<?php echo $attributes['usermeta']['sas_country']; ?>">
		</p>
		<p class="form-row">
		
			<input type="text" name="sas_city" id="sas_city" placeholder="<?php _e( 'City', 'simple-account-system' ); ?>" value="<?php echo $attributes['usermeta']['sas_city']; ?>">
		</p>
		
		<p class="form-row">
			
			<input type="text" name="sas_zipcode" id="sas_zipcode" placeholder="<?php _e( 'Zip code', 'simple-account-system' ); ?>" value="<?php echo $attributes['usermeta']['sas_zipcode']; ?>">
		</p>
		
		
		<p class="submit">
			<input type="hidden" name="action" value="sas_update_account" />
			<input type="hidden" name="user_id" id="user_id" value="<?php echo $attributes['userdata']->ID;?>">
			<input type="submit" name="submit" class="button button-primary btn btn-block" value="<?php _e( 'Update', 'simple-account-system' ); ?>"/>
		</p>
	</form>
</div>