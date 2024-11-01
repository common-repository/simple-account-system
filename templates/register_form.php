<div id="register-form" class="widecolumn">
	
		<h3><?php _e( 'Create an Account', 'simple-account-system' ); ?></h3>
	

	<?php if ( count( $attributes['errors'] ) > 0 ) : ?>
		<?php foreach ( $attributes['errors'] as $error ) : ?>
			<p>
				<?php echo $error; ?>
			</p>
		<?php endforeach; ?>
	<?php endif; ?>

	<form id="signupform" action="<?php echo wp_registration_url(); ?>" method="post">
		<p class="form-row">
			
			<input type="text" name="email" id="email" placeholder="<?php _e( '* Email', 'simple-account-system' ); ?>">
		</p>

		<p class="form-row">
			
			<input type="text" name="first_name" id="first-name" placeholder="<?php _e( 'First name', 'simple-account-system' ); ?>">
		</p>

		<p class="form-row">
			
			<input type="text" name="last_name" id="last-name" placeholder="<?php _e( 'Last name', 'simple-account-system' ); ?>">
		</p>
		
		<p class="form-row">
			
			<input type="text" name="sas_phone" id="sas_phone" placeholder="<?php _e( 'Phone', 'simple-account-system' ); ?>">
		</p>
		
		<p class="form-row">
			
			<input type="text" name="sas_address" id="sas_address" placeholder="<?php _e( 'Address', 'simple-account-system' ); ?>">
		</p>
		
		<p class="form-row">
		
			<input type="text" name="sas_country" id="sas_country" placeholder="<?php _e( 'Country', 'simple-account-system' ); ?>">
		</p>
		
		<p class="form-row">
		
			<input type="text" name="sas_city" id="sas_city" placeholder="<?php _e( 'City', 'simple-account-system' ); ?>">
		</p>
		
		<p class="form-row">
			
			<input type="text" name="sas_zipcode" id="sas_zipcode" placeholder="<?php _e( 'Zip code', 'simple-account-system' ); ?>">
		</p>
		
		<p class="form-row">
			<?php _e( 'Note: Your password will be generated automatically and emailed to the address you specify above.', 'simple-account-system' ); ?>
		</p>

		<?php  
			
			$check_simple_account_system_recaptcha = get_option('simple_account_system_recaptcha');
			
			if ( $check_simple_account_system_recaptcha == 1 && $attributes['recaptcha_site_key'] ) : ?>
			<div class="recaptcha-container">
				<div class="g-recaptcha" data-sitekey="<?php echo $attributes['recaptcha_site_key']; ?>"></div>
			</div>
		<?php endif; ?>

		<p></p>

		<p class="signup-submit">
			<input type="submit" name="submit" class="register-button btn btn-block"
			       value="<?php _e( 'Register', 'simple-account-system' ); ?>"/>
		</p>
	</form>
</div>