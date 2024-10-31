<?php
if (!function_exists('promissa_settings_render_list_page')) {

	function promissa_settings_render_list_page()
	{
		?>
		<div class="wrap">
			<h2><?php echo esc_html(get_admin_page_title()); ?></h2>
			<?php
			$promissa = get_option('promissa');
			if ($_SERVER['REQUEST_METHOD'] === 'POST') :
				if (isset($_POST['promissa-reset'])) :
					wp_redirect(admin_url('index.php'));
				elseif (isset($_POST['promissa-save'])) :

					$promissa['private'] = kei_post_val('private');
					$promissa['public'] = kei_post_val('public');
					$promissa['week_product_id'] = kei_post_val('week_product_id');
					$promissa['feast_product_id'] = kei_post_val('feast_product_id');
					if (is_guid($promissa['private']) && strlen($promissa['public']) == 64) :
						update_option('promissa', $promissa);
					endif;
				endif;
			endif;
			?>
			<form id="masstype-filter" action="<?php echo $_SERVER['REQUEST_URI'] ?>" method="post">
				<p><?= __('You can get the API keys in the portal. You can find the key in the settings page. You should probably ask the administrator to get the API keys', 'promissa') ?>. <a href="https://portal.promissa.nl/api" target="_blank"><?= __('Go to the portal', 'promissa') ?></a></p>
				<div id="naw">
					<table>
						<tr>
							<td>
								<label for="private">
									<?php _e('Private key', 'promissa'); ?>:
								</label>
							</td>
							<td>
								<?php
								echo '<input type="text" id="private" name="private" value="' . $promissa['private'] . '" minlength="36" maxlength="36" size="36" style="width: 300px;" />';
								?>
							</td>
						</tr>
						<tr>
							<td>
								<label for="public">
									<?php _e('Public key', 'promissa'); ?>:
								</label>
							</td>
							<td>
								<?php
								echo '<input type="text" id="public" name="public" value="' . $promissa['public'] . '" minlength="64" maxlength="64" size="64" style="width: 550px;" />';
								?>
							</td>
						</tr>
					</table>
				</div>
				<h2><?php _e('WooCommerce integration', 'promissa'); ?></h2>
				<p><?= __('You can use Pro Missa to enable a webshop with <a href="https://woocommerce.com" target="_blank">WooCommerce</a> where parishioners can order a intention which will be processed into the intention flow in Pro Missa', 'promissa') ?>. <a href="https://www.promissa.nl/webshop" target="_blank"><?= __('More information', 'promissa') ?></a></p>
				<div id="naw">
					<table>
						<tr>
							<td>
								<label for="week_product_id" style="w">
									<?php _e('Intention product for weekdays', 'promissa'); ?>:
								</label>
							</td>
							<td>
								<?php
								echo '<input type="text" id="week_product_id" name="week_product_id" value="' . $promissa['week_product_id'] . '" class="product-autocomplete" />';
								?>
							</td>
						</tr>
						<tr>
							<td>
								<label for="feast_product_id">
									<?php _e('Intention product for Sun- and feastdays', 'promissa'); ?>:
								</label>
							</td>
							<td>
								<?php
								echo '<input type="text" id="feast_product_id" name="feast_product_id" value="' . $promissa['feast_product_id'] . '" class="product-autocomplete" />';
								?>
							</td>
						</tr>
					</table>
				</div>
				<div style="clear:both;">
					<br />
					<?php
					submit_button(__('Save', 'promissa'), 'primary', 'promissa-save', false);
					echo '&nbsp;&nbsp;';
					submit_button(__('Reset', 'promissa'), 'secondary', 'promissa-reset', false);
					?>
				</div>
			</form>
		</div>
<?php
	}
}
?>