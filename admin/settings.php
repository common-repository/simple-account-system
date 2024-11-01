<?php
if( isset( $_REQUEST['update_simple_account_system_settings'] ) ){ 
				if ( !isset($_POST['simple_account_system_nonce']) || !wp_verify_nonce($_POST['simple_account_system_nonce'],'simple_account_system_settings') ){
				    
				    echo '<div class="error"><p>'. __('Sorry, your nonce did not verify.', 'simple-account-system') . '</p></div>';
				    
				   exit;
				}else{
					
					echo '<div class="updated"><p>'. __('Updated!', 'simple-account-system') . '</p></div>';
				  	update_option('simple_account_system_recaptcha',$_POST['simple_account_system_recaptcha']);
				  	update_option('simple_account_system_recaptcha_site_key',$_POST['simple_account_system_recaptcha_site_key']);
				  	update_option('simple_account_system_recaptcha_secret_key',$_POST['simple_account_system_recaptcha_secret_key']);
				  	update_option('simple_account_system_logout_links',$_POST['simple_account_system_logout_links']);
   
				}
			}
		?>

		<div id="custom-branding-general" class="wrap">
				
				<h2><?php esc_html_e('Help S.A.S. login','simple-account-system'); ?></h2>
				
				
			<div class="metabox-holder">
				<div class="postbox">
				<div class="inside">
				<form method="post" action="admin.php?page=simple-account-system/admin/settings.php">
					<?php settings_fields( 'simple-account-system-settings-group' ); ?>
					<?php do_settings_sections( 'simple-account-system-settings-group' ); ?>
						
				    <table class="form-table">
				        <tr valign="top">
				        <th scope="row"><?php _e('Include reCaptcha?','simple-account-system'); ?>
					        <div class="sidebar-description">
								<p class="description"><?php _e('If your theme already includes reCaptcha set this option to NO.','simple-account-system'); ?></p>
							</div>
						</th>
				        
				        <td>
				        <select name="simple_account_system_recaptcha">
							<?php
								$check_simple_account_system_recaptcha = get_option('simple_account_system_recaptcha');
								for($i=0;$i<2;$i++){
									if($i == 0){
										$yes_no = __('No','simple-account-system');
									}else{
										$yes_no = __('Yes','simple-account-system');
									}
									echo '<option value="'.$i.'"'.selected($check_simple_account_system_recaptcha, $i, false).'>'.$yes_no.'</option>';	 
								}		 
							?>										
						</select>
				        </td>
				        </tr>
				         <tr valign="top">
					         <th scope=""><?php _e('Where you want to place Sign In - Sign Out links','simple-account-system'); ?></th>
					         <td>
						         <select name="simple_account_system_logout_links">
							         <?php
									 	$check_simple_account_system_logout_links = get_option('simple_account_system_logout_links');
									 	$menus = get_registered_nav_menus();
									
									 	foreach ( $menus as $location => $description ) {
											echo '<option value="'.$location.'"'. selected( $check_simple_account_system_logout_links, $location, false ).'>'.$description.'</option>';
											 		
									}
									?>	
						         </select>
					         </td>
				         </tr>
				         
				        <tr valign="top">
				        <th scope=""><?php _e('reCaptcha Site Key','simple-account-system'); ?></th>
				        <?php $check_simple_account_system_recaptcha = get_option('simple_account_system_recaptcha_site_key'); ?>
				        <td><input type="text" name="simple_account_system_recaptcha_site_key" size="50" value="<?php echo esc_attr( get_option('simple_account_system_recaptcha_site_key') ); ?>" /></td>
				        </tr>
				        
				        <tr valign="top">
				        <th scope="row"><?php _e('reCaptcha Secret Key','simple-account-system'); ?></th>
				         <?php $check_simple_account_system_recaptcha = get_option('simple_account_system_recaptcha_secret_key'); ?>
				        <td><input type="text" name="simple_account_system_recaptcha_secret_key" size="50" value="<?php echo esc_attr( get_option('simple_account_system_recaptcha_secret_key') ); ?>" /></td>
				        </tr>
				    </table>
    
					<?php wp_nonce_field( 'simple_account_system_settings', 'simple_account_system_nonce' ); ?>
				    <p class="submit">
				        <input class="button-primary" type="submit" name="update_simple_account_system_settings" value="<?php _e( 'Save Settings', 'simple-account-system' ) ?>" />
				    </p> 

					</form>            
    			</div>
  			</div>
		</div>
		</div>