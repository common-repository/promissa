<?php
if(!function_exists('promissa_form_handler')) :
	function promissa_form_handler( $atts, $content = null )
	{
	    $atts_extended = shortcode_atts( array(
	        'church_id' => '',
	        'subtitle' => '',
	        'limit' => 10,
	        'page' => 0,
	        'show_title' => 'false',
	        'show_attendees' => 'false',
	    ), $atts );
	    extract($atts_extended);

	    $dateFormatter = \IntlDateFormatter::create(
		  Locale::getDefault(),
		  IntlDateFormatter::NONE,
		  IntlDateFormatter::NONE,
		  date_default_timezone_get(),
		  IntlDateFormatter::GREGORIAN,
		  'cccc d MMMM Y'
		);

		$mass = NULL;
		$church = NULL;
		$date = false;
		if(isset($_GET['mass'])) :
			$mass = strtolower(urldecode( $_GET['mass']));
			$mass  = explode(' ' .  __('at', 'promissa') . ' ', $mass );
			$mass = implode('</strong> ' .  __('at', 'promissa') . ' <strong>', $mass);
		endif;
		if(isset($_GET['church'])) :
			$church = urldecode( $_GET['church']);
		endif;

		if(isset($_GET['t'])) :
			$date = strtotime(urldecode( $_GET['t']));
		endif;
		$mass_is_full = false;

		if($mass != NULL && $church != NULL && $date != false) :
			if(!$mass_is_full) :
				return sprintf("Verzoek indienen tot aanwezigheid voor de eucharistieviering van <strong>%s</strong> in de <strong>%s</strong>", $mass, $church);
			else :
				return sprintf("Het is helaas niet meer mogelijk om aan te melden voor de eucharistieviering van <strong>%s</strong> in de <strong>%s</strong><script>
				var message = 'Het is helaas niet meer mogelijk om aan te melden voor de eucharistieviering van %s in de %s';
				jQuery(function() {
					alert(message)
					window.location.href = '" . get_site_url() . "/';
					jQuery('.wpcf7-submit').on('click', function(e) {
						e.preventDefault();
						alert(message)
						window.location.href = '" . get_site_url() . "/';
					});
				});
				document.addEventListener('wpcf7submit', function( e ) {
					e.preventDefault();
					alert(message)
					window.location.href = '" . get_site_url() . "/';
				}, false );</script>", $mass, $church, str_replace('</strong> om <strong>', ' om ', $mass), $church);

				$mass = NULL;
				$church = NULL;
				$date = false;
			endif;
		else :
			$mass = null;
			if(isset($_GET['ID']) && preg_match("/^(\{)?[a-f\d]{8}(-[a-f\d]{4}){4}[a-f\d]{8}(?(1)\})$/i", $_GET['ID'])) :
				$masses = ProMissaREST('Schedule', '?size=' . $limit . '&filter=ID,eq,' . html_entity_decode($_GET['ID']));
				if(count($masses) == 1) :
					$mass = (object)$masses[0];
				endif;
			endif;
			$date = new DateTime($mass->start);

			$Excludes = ProMissaREST('Exclude');
			$excludeJS = 'let exclude = [';
			foreach($Excludes as $Exclude) :
				$excludeJS .= '"' . base64_encode($Exclude["email"]) . '",';
			endforeach;
			$excludeJS = rtrim($excludeJS, ',') . '];';
			if($mass != null) :
				?>

				<p>
					Verzoek indienen tot aanwezigheid voor de eucharistieviering van <strong><?= ucfirst($dateFormatter->format($date)) . ' ' .  __('at', 'promissa') . ' ' . $date->format('H') . '.' . $date->format('i') . ' ' . __('hour', 'promissa')?></strong> in de <strong><?=$mass->church?></strong>
				</p>
				<?php
			endif;
			?>
			<form action="<?=$_SERVER["REQUEST_URI"]?>" method="post" id="subscribeform">
				<input type="hidden" name="Mis" value="<?= ucfirst($dateFormatter->format($date)) . ' om ' . $date->format('H') . '.' . $date->format('i') . ' ' . __('hour', 'promissa')?>" size="40"  />
				<input type="hidden" name="Kerk" value="<?=$mass->church?>"  />
				<input type="hidden" name="Mistijd" value="<?=$date->format('Y-m-d H:i')?>" />


				<div class="flex_column av_one_full flex_column_div first avia-builder-el-no-sibling">
					<label>Uw naam <strong>(verplicht)</strong>
						<input type="text" name="Name" value="" size="40" required="required" />
					</label>
				</div>
				<div class="flex_column av_one_half flex_column_div first el_before_av_one_half avia-builder-el-first">
					<label>Uw e-mail adres <strong>(verplicht)</strong>
						<input type="email" name="Mail" value="" size="40" required="required"  />
					</label>
				</div>
				<div class="flex_column av_one_half flex_column_div el_after_av_one_half avia-builder-el-last">
					<label>Telefoon <strong>(verplicht)</strong>
						<input type="tel" name="Telephone" value="" size="40" required="required" />
					</label>
				</div>
				<div class="flex_column av_one_half flex_column_div first el_before_av_one_half avia-builder-el-first">
					<label>Aantal personen uit één gezin <strong>(verplicht)</strong>
						<input type="number" name="Persons" value="" required="required" min="1" max="30" />
					</label>
				</div>
				<div class="flex_column av_one_full flex_column_div first avia-builder-el-no-sibling">
					<label>Eventuele opmerkingen
						<textarea name="Remarks" cols="40" rows="5"></textarea>
					</label>
				</div>
				<div class="flex_column av_one_full flex_column_div first avia-builder-el-no-sibling">
					<p>Wij gaan vertrouwelijk met uw gegevens om.</p>
				</div>
				<div class="flex_column av_one_full flex_column_div first avia-builder-el-no-sibling">
					<input type="submit" data-crud="POST" value="Verzoek indienen" class="wpcf7-form-control wpcf7-submit" />
					<input type="submit" data-crud="PUT" value="Verzoek wijzigen" class="wpcf7-form-control wpcf7-submit" style="display: none;" />
					<input type="submit" data-crud="DELETE" value="Verzoek verwijderen" class="wpcf7-form-control wpcf7-submit" style="display: none;"  />
				</div>
				<div class="wpcf7-response-output" role="alert" aria-hidden="true" style="display:none;"></div>
			</form>
			<script>
				jQuery(function() {
					var $subscribeForm = jQuery( "#subscribeform" );
					$id = null;
					$json = null;
					if (typeof(Storage) !== "undefined") {
						$id = localStorage.getItem("<?=$mass->ID?>");
						$json = localStorage.getItem("<?=$mass->ID?>_json");
					}
					if($id != null) {
						jQuery('.wpcf7-submit').toggle();
					}
					jQuery( '.wpcf7-submit' ).on('click', function(e) {
						e.preventDefault();
						jQuery(this).prop('disabled', true).val('Versturen...');
						let that = this;
						if($subscribeForm[0].checkValidity() === false) {
							$subscribeForm[0].reportValidity();
						} else {
							<?php $promissa = get_option('promissa');?>
							$data = {};
							if($id == null ) {
								$data["ID"] = '<?=guid();?>';
							}
							$data["account_ID"] = "<?=$mass->account_ID?>";
							$data["masses_ID"] = "<?=$mass->ID?>";
							$data["json"] = JSON.stringify({
									'Name': $subscribeForm.find('input[name="Name"]').val(),
									'Mail': $subscribeForm.find('input[name="Mail"]').val(),
									'Telephone': $subscribeForm.find('input[name="Telephone"]').val(),
									'Remarks': $subscribeForm.find('textarea[name="Remarks"]').val(),
									'Persons': parseInt($subscribeForm.find('input[name="Persons"]').val())
								});
							$data["persons"] = parseInt($subscribeForm.find('input[name="Persons"]').val());
							$data[($id == null ? "insertdate" : "updatedate")] = (new Date()).toISOString().slice(0, 19).replace('T', ' ');
							$data["invited"] = '1900-01-01 00:00:00';
							var data = Object.assign($data);

							<?=$excludeJS?>

							jQuery.ajax({
								type: jQuery(that).data('crud'),
								url: "https://api.promissa.nl/v1.2/records/subscriptions" + ($id !== null ? '/' + $id : ''),
								dataType: 'json',
								contentType: 'application/x-www-form-urlencoded; charset=utf-8',
								headers: {
									"Authorization": "Basic <?=base64_encode($promissa['private'] . ":" . $promissa['public'])?>",
									'Content-Type': 'application/x-www-form-urlencoded'
								},
								data: data,
								success: function (res) {

									if(jQuery(that).data('crud') === 'POST') {
										if(res.length == 36) {
											if (typeof(Storage) !== "undefined") {
												// Store
												if(exclude.includes(window.btoa($subscribeForm.find('input[name="Mail"]').val()))) {
													localStorage.removeItem("<?=$mass->ID?>");
													localStorage.removeItem("Name");
													localStorage.removeItem("Telephone");
													localStorage.removeItem("Persons");
												} else {
													localStorage.setItem("<?=$mass->ID?>", res);
													localStorage.setItem("<?=$mass->ID?>_json", $data["json"]);
													localStorage.setItem("Name", $subscribeForm.find('input[name="Name"]').val());
													localStorage.setItem("Mail", $subscribeForm.find('input[name="Mail"]').val());
													localStorage.setItem("Telephone", $subscribeForm.find('input[name="Telephone"]').val());
													localStorage.setItem("Persons", $subscribeForm.find('input[name="Persons"]').val());
												}
											}
										}
										jQuery('a#<?=$mass->ID?>', window.parent.document).css({'background-color' : '#4BB543', 'border-color': '#26911f'}).text("Zojuist aangemeld");
									} else if(jQuery(that).data('crud') === 'PUT') {
										localStorage.setItem("<?=$mass->ID?>_json", $data["json"]);
										jQuery('a#<?=$mass->ID?>', window.parent.document).css({'background-color' : '#4BB543', 'border-color': '#26911f'}).text("Zojuist bijgewerkt");
									} else if(jQuery(that).data('crud') === 'DELETE') {
										jQuery('a#<?=$mass->ID?>', window.parent.document).text("Aanmelden").removeAttr( 'style' );
										localStorage.removeItem("<?=$mass->ID?>");
									}
									jQuery('button.mfp-close', window.parent.document).trigger('click');
								}
							});
						}
					});

					if (typeof(Storage) !== "undefined") {
						if($id == null) {
							$subscribeForm.find('input[name="Name"]').val(localStorage.getItem("Name"));
							$subscribeForm.find('input[name="Mail"]').val(localStorage.getItem("Mail"));
							$subscribeForm.find('input[name="Telephone"]').val(localStorage.getItem("Telephone"));
							$subscribeForm.find('input[name="Persons"]').val(localStorage.getItem("Persons"));
						} else if($json != null) {
							var obj = JSON.parse(localStorage.getItem("<?=$mass->ID?>_json"));
							$subscribeForm.find('input[name="Name"]').val(obj.Name);
							$subscribeForm.find('input[name="Mail"]').val(obj.Mail);
							$subscribeForm.find('input[name="Telephone"]').val(obj.Telephone);
							$subscribeForm.find('input[name="Persons"]').val(obj.Persons);
							$subscribeForm.find('textarea[name="Remarks"]').text(obj.Remarks);
						}
					}
				});
			</script>
			<?php
		endif;
	}
endif;
add_shortcode( 'promissa-form', 'promissa_form_handler' );
?>