<?php
$dateFormatter = \IntlDateFormatter::create(
	Locale::getDefault(),
	IntlDateFormatter::NONE,
	IntlDateFormatter::NONE,
	date_default_timezone_get(),
	IntlDateFormatter::GREGORIAN,
	'cccc d MMMM Y'
);
define('PMprefix', chr(0) . '*' . chr(0));

if (!function_exists('get_mass_by_id')) :
	function get_mass_by_id($obj)
	{
		$mass = NULL;
		if (is_guid($obj)) :
			$mass = (ProMissaREST('Schedule', '?size=1&filter=ID,eq,' . sanitize_text_field($obj)))[0];
		elseif(is_object($obj)) :
			$mass = $obj;
		endif;
		if($mass !== NULL) :
			$startDate = GetProMissaDate($mass['start']);
			global $dateFormatter;
			$datum = $dateFormatter->format($startDate) . ' ' .  __('at', 'promissa') . ' ' . $startDate->format('H') . '.' . $startDate->format('i') . ' ' . __('hour', 'promissa');

			return __( 'On', 'promissa') . ' ' . $datum . ' ' . __( 'during the', 'promissa') . ' ' . strtolower($mass['massType']) . ' ' . __('from the', 'promissa') . ' ' . $mass['church'] . ' ' . __('in', 'promissa') . ' ' . $mass['city'];
		else :
			return sanitize_text_field($obj);
		endif;
	}
endif;

if (!function_exists('get_mass_by_text')) :
	function get_mass_by_text($obj)
	{
		foreach (ProMissaREST('Schedule') as $mass) :
			if (get_mass_by_id($mass["ID"]) == $obj) :
				return $mass;
			endif;
		endforeach;
		return $obj;
	}
endif;

if (!function_exists('get_church_by_id')) :
	function get_church_by_id($obj) {
		if(is_guid($obj)) :
			return (ProMissaREST('Churches', '?size=1&filter=ID,eq,' . sanitize_text_field($obj)))[0]['title'];
		else :
			return sanitize_text_field($obj);
		endif;
	}
endif;

if (!function_exists('get_church_by_text')) :
	function get_church_by_text($obj)
	{
		foreach(ProMissaREST('Churches') as $church) :
			if($church["title"] == $obj) :
				return $church["ID"];
			endif;
		endforeach;
		return $obj;
	}
endif;

if (!function_exists('get_date_text')) :
	function get_date_text($obj)
	{
		$time = strtotime($obj);
		if($time === false) :
			return $obj;
		else :
			return LongDate($obj);
		endif;
	}
endif;

/**
 * @snippet       Add an input field to products - WooCommerce
 */

// -----------------------------------------
// 1. Show custom input field above Add to Cart

add_action('woocommerce_before_add_to_cart_button', 'add_on', 9);

function add_on()
{
	if (in_array(get_the_terms(wc_get_product()->id, 'product_cat')[0]->slug, array('stipendia', 'misintentie', 'misintenties', 'intentie'))) :
		$validDays = array(1, 2, 3, 4, 5, 6, 7);
		if (isset(wc_get_product()->attributes) && is_array(wc_get_product()->attributes) && count(wc_get_product()->attributes) > 0) :
			if (isset(wc_get_product()->attributes["dayofweek"])) :
				$validDays = array_map('intval', explode(',', array_shift(array_values(((array)wc_get_product()->attributes["dayofweek"])))["options"][0]));
			endif;
		endif;

		$masses_ID = (isset($_POST['masses_ID']) ? sanitize_text_field($_POST['masses_ID']) : (isset($_GET['masses_ID']) ? sanitize_text_field($_GET['masses_ID']) : ''));

		$church_ID = (isset($_POST['church_ID']) ? sanitize_text_field($_POST['church_ID']) : (isset($_GET['church_ID']) ? sanitize_text_field($_GET['church_ID']) : ''));

		$date = (isset($_POST['date']) ? sanitize_text_field($_POST['date']) : (isset($_GET['date']) ? sanitize_text_field($_GET['date']) : ''));

		$note = (isset($_POST['note']) ? sanitize_text_field($_POST['note']) : (isset($_GET['note']) ? sanitize_text_field($_GET['note']) : ""));
		echo '<input type="hidden" name="masses_ID" value="' . $masses_ID . '" />';
		if(!empty($masses_ID)) :
			echo '<div class="flex_column av_full flex_column_div firs avia-builder-el-first">';
			echo '<p>' .  get_mass_by_id($masses_ID) . '</p>';
			echo '</div>';
		else :
			echo '<div class="flex_column av_one_half flex_column_div first el_before_av_one_half avia-builder-el-first">
						<label>' .  __('Church', 'promissa') . ' <abbr class="required" title="' . __('Required', 'promissa') . '">*</abbr>
							<select required="required" name="church_ID">';
			echo '<option value="">-- ' . __('Choose a church', 'promissa') . ' --</option>';
			foreach (ProMissaREST('Churches') as $church) :
				echo '<option value="' . $church["ID"] . '"' . ($church_ID == $church["ID"] ? ' selected="selected"' : '') . '>' . $church["title"] . '</option>';
			endforeach;
			echo '			</select>
						</label>
					</div>';
			echo '<div class="flex_column av_one_half flex_column_div last el_before_av_one_half avia-builder-el-last">
						<label>' . __('Date', 'promissa') . ' <abbr class="required" title="' . __('Required', 'promissa') . '">*</abbr>
							<input type="date" name="date" required pattern="\d{4}-\d{2}-\d{2}" min="' . date("Y-m-d", strtotime('next wednesday')) . '" value="' . $date . '" />
						</label>
					</div>';
		endif;
		echo '<div class="flex_column av_one_full flex_column_div first avia-builder-el-no-sibling">
					<label>' . __('Intention', 'promissa') . ' <abbr class="required" title="' . __('Required', 'promissa') . '">*</abbr>
						<textarea name="note" cols="40" rows="5">' . $note . '</textarea>
					</label>
				</div>';
	endif;
}

// -----------------------------------------
// 2. Throw error if custom input field empty

add_filter('woocommerce_add_to_cart_validation', 'add_on_validation', 10, 3);

function add_on_validation($passed, $product_id, $qty)
{
	$product = null;
	$dayIsValid = true;
	if ((!isset($_POST['masses_ID']) || sanitize_text_field($_POST['masses_ID']) == '') && isset($_POST['church_ID']) && sanitize_text_field($_POST['church_ID']) == '') :

		wc_add_notice(__('Church is required!', 'promissa'), 'error');

		$passed = false;

	endif;

	if (isset($_POST['note']) && sanitize_text_field($_POST['note']) == '') :

		wc_add_notice(__('Intention is required!', 'promissa'), 'error');

		$passed = false;

	endif;

	if (isset($_POST['date'])) :
		if(sanitize_text_field($_POST['date']) == '') :
			wc_add_notice( __('Date is required!', 'promissa'), 'error');
			$passed = false;
		else :
			$validDays = array('1', '2', '3', '4', '5', '6', '7');
			try {
				$product = (object)((array)wc_get_product($product_id))[PMprefix . "data"];
				$validDays = explode(',', (((array)$product->attributes["dayofweek"])[PMprefix . "data"])["options"][0]);
			} catch (Exception $e) {
				$validDays = array('1', '2', '3', '4', '5', '6', '7');
			}
			$date = GetProMissaDate($_POST['date']);

			if(count($validDays) > 4) :
				$dayIsValid = !FeastDate_YN($date);
			elseif (count($validDays) < 4) :
				$dayIsValid = FeastDate_YN($date);
			elseif(!in_array($date->format('N'), $validDays)) :
				$dayIsValid = false;
			endif;
		endif;

	endif;
	if(!$dayIsValid) :
		$passed = false;
		$cross_sell = null;
		if($product != null && $product->cross_sell_ids !== null && is_array($product->cross_sell_ids) && count($product->cross_sell_ids) > 0 && is_numeric($product->cross_sell_ids[0])) :
			$cross_sell = wc_get_product($product->cross_sell_ids[0]);
		endif;

		wc_add_notice(__('This Mass intention cannot be ordered on the chosen date!', 'promissa'), 'error');
		if($cross_sell != null) :
			wc_add_notice(sprintf(__('<a href="%s"><strong>Order here a intension for %s</strong></a>', 'promissa'), $cross_sell->get_permalink() . '?church_ID=' . $_POST['church_ID'] . (isset($_POST['masses_ID']) && is_guid($_POST['masses_ID']) ? '&masses_ID=' . $_POST['masses_ID'] : '') . (isset($_POST['date']) ? '&date=' . $_POST['date'] : '') . '&note=' . urlencode($_POST['note']), lcfirst($cross_sell->name)), 'error');
		endif;
	endif;

	return $passed;
}

// -----------------------------------------
// Wordt getoont in mail en winkelwagen

add_filter('woocommerce_add_cart_item_data', 'add_on_cart_item_data', 10, 2);

function add_on_cart_item_data($cart_item, $product_id)
{
	if (isset($_POST['masses_ID'])) :
		$cart_item['masses_ID'] = sanitize_text_field($_POST['masses_ID']);
	endif;
	if (isset($_POST['church_ID'])) :
		$cart_item['church_ID'] = sanitize_text_field($_POST['church_ID']);
	endif;
	if (isset($_POST['date'])) :
		$cart_item['date'] = sanitize_text_field($_POST['date']);
	endif;
	if (isset($_POST['note'])) :
		$cart_item['note'] = stripslashes(sanitize_text_field($_POST['note'] ));
	endif;

	return $cart_item;
}

// -----------------------------------------
// Toon data in winkelwagentje en overzicht

add_filter('woocommerce_get_item_data', 'add_on_display_cart', 10, 2);

function add_on_display_cart($data, $cart_item)
{
	if (isset($cart_item['masses_ID']) && !empty($cart_item['masses_ID'])) :
		$data[] = array(
			'name' =>  __('Mass', 'promissa'),
			'value' => get_mass_by_id($cart_item['masses_ID'])
		);
	endif;
	if (isset($cart_item['church_ID'])) :
		$data[] = array(
			'name' =>  __('Church', 'promissa'),
			'value' => get_church_by_id($cart_item['church_ID'])
		);
	endif;
	if (isset($cart_item['date'])) :
		$data[] = array(
			'name' => __('Date', 'promissa'),
			'value' => get_date_text(sanitize_text_field($cart_item['date']))
		);
	endif;
	if (isset($cart_item['note'])) :
		$data[] = array(
			'name' => __('Intention', 'promissa'),
			'value' => sanitize_text_field($cart_item['note'])
		);
	endif;

	return $data;
}


// add the action
add_action('woocommerce_checkout_create_order_line_item', 'checkout_create_order_line_item', 10, 4);
// define the woocommerce_checkout_create_order_line_item callback
function checkout_create_order_line_item($item, $cart_item_key, $values, $order)
{
	if (isset($values['masses_ID'])) {
		$item->add_meta_data(__('Mass', 'promissa'), get_mass_by_id($values['masses_ID']), true);
	}
	if (isset($values['church_ID'])) {
		$item->add_meta_data(  __('Church', 'promissa'), get_church_by_id($values['church_ID']), true );
	}
	if (isset($values['date'])) {
		$item->add_meta_data(__('Date', 'promissa'), get_date_text($values['date']), true);
	}
	if (isset($values['note'])) {
		$item->add_meta_data(__('Intention', 'promissa'), stripslashes($values['note']), true);
	}
};
// -----------------------------------------

// Tonen op pagina na order geplaatst


add_filter('woocommerce_order_items_meta_get_formatted', 'order_items_meta_get_formatted', 10, 2);

function order_items_meta_get_formatted($formatted_meta, $obj) {
	return $formatted_meta;
}
// -----------------------------------------

// 6. Display custom input field value into order table

add_filter('woocommerce_order_item_product', 'add_on_display_order', 10, 2);

function add_on_display_order($cart_item, $order_item)
{
	if (isset($order_item['masses_ID'])) :
		$cart_item['masses_ID'] = get_mass_by_id($order_item['masses_ID']);
	endif;
	if (isset($order_item['church_ID'])) :
		$cart_item['church_ID'] = get_church_by_id($order_item['church_ID']);
	endif;
	if (isset($order_item['date'])) :
		$cart_item['date'] = get_date_text($order_item['date']);
	endif;
	if (isset($order_item['note'])) :
		$cart_item['note'] = $order_item['note'];
	endif;

	return $cart_item;
}


add_action('woocommerce_email_order_meta', 'add_email_order_meta', 10, 3);

/*
 * @param $order_obj Order Object
 * @param $sent_to_admin If this email is for administrator or for a customer
 * @param $plain_text HTML or Plain text (can be configured in WooCommerce > Settings > Emails)
 */
function add_email_order_meta($order_obj, $sent_to_admin, $plain_text)
{

	// this order meta checks if order is marked as a intention
	$note = get_post_meta($order_obj->get_order_number(), 'note', true);

	// we won't display anything if it is not a intention
	if (empty($note))
		return;

	// ok, if it is the intention order, get all the other fields
	$mass = get_mass_by_id(get_post_meta($order_obj->get_order_number(), 'masses_ID', true));
	$church = get_church_by_id(get_post_meta($order_obj->get_order_number(), 'church_ID', true));
	$date = get_date_text(get_post_meta($order_obj->get_order_number(), 'date', true));

	// ok, we will add the separate version for plaintext emails
	if ($plain_text === false) {

		// you shouldn't have to worry about inline styles, WooCommerce adds them itself depending on the theme you use
		echo '<h2>' . __('Intention', 'promissa') . '</h2>';
		echo '<ul>';
		if (!empty($mass)) :
			echo '	<li><strong>' .  __('Mass', 'promissa') . ':</strong> ' . $mass . '</li>';
		endif;
		if (!empty($church)) :
			echo '	<li><strong>' .  __('Church', 'promissa') . ':</strong> ' . $church . '</li>';
		endif;
		if (!empty($date)) :
			echo '	<li><strong>' . __('Date', 'promissa') . ':</strong> ' . $date . '</li>';
		endif;
		echo '	<li><strong>' . __('Intention', 'promissa') . ':</strong> ' . wpautop($note) . '</li>';
		echo '</ul>';
	} else {

		echo "Misintentie\n";
		if (!empty($mass)) :
			echo __('Mass', 'promissa') . ": $mass" . "\n";
		endif;
		if (!empty($church)) :
			echo __('Church', 'promissa') . ": $church" . "\n";
		endif;
		if (!empty($date)) :
			echo __('Date', 'promissa') . ": $date" . "\n";
		endif;
		echo __('Intention', 'promissa') . ": $note" . "\n";
	}
}

add_action('woocommerce_order_status_completed', 'payment_complete', 11, 1);
function payment_complete($order_id)
{
	$result = array();
	$order = new WC_Order($order_id);
	$order_data = $order->get_data();
	$global = array(
		'remark' => $order_data['customer_note'],
		'name' => $order_data['billing']['first_name'] . ' ' . $order_data['billing']['last_name'],
		'address' => $order_data['billing']['address_1'],
		'zipcode' => $order_data['billing']['postcode'],
		'city' => $order_data['billing']['city'],
		'phone' => $order_data['billing']['phone'],
		'mail' => $order_data['billing']['email'],
		'order_id' => $order->get_id(),
		'order_status'  => $order->get_status(),
		'transaction_id' => $order->get_transaction_id(),
		'payment_method' => $order->get_payment_method(),
		'insertdate' => $order->get_date_created()->date('Y-m-d H:i:s')
	);

	$i = 0;
	foreach ($order->get_items() as $key => $item) :
		$result[$i] = $global;
		$result[$i]['ID'] = guid();
		$data = (((array)$item)[PMprefix . "meta_data"]);
		$result[$i]['price'] = str_replace(',', '.', $item->get_product()->get_price());
		$result[$i]['time'] = NULL;
		foreach($data as $item) :
			$item = (object)(((array)$item)[PMprefix . "current_data"]);
			switch($item->key) :
				case __('Mass', 'promissa'):
					$mass = get_mass_by_text($item->value);
					if(!empty($mass["ID"]) && is_guid($mass["ID"])) :
						$result[$i]['masses_ID'] = $mass["ID"];
						$result[$i]['church_ID'] = $mass["church_ID"];
						$result[$i]['date'] = GetProMissaDate($mass["start"])->format('Y-m-d');
						$result[$i]['time'] = GetProMissaDate($mass["start"])->format('H:i:s');
					endif;
					break;
				case __('Church', 'promissa'):
					$result[$i]['church_ID'] = get_church_by_text($item->value);
					break;
				case __('Date', 'promissa'):
					$result[$i]['date'] = FromLongDate($item->value);
					break;
				case __('Intention', 'promissa'):
					$result[$i]['note'] = $item->value;
					break;
				default:
					$result[$i][$item->key] = $item->value;
					break;
			endswitch;
		endforeach;
		$i++;
	endforeach;

	foreach($result as $row) :
		ProMissaREST_POST('intentions_queue', $row);
	endforeach;
}
