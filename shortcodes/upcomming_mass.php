<?php

if(!function_exists('promissa_upcoming_masses_handler')) :
	function promissa_upcoming_masses_handler( $atts, $content = null )
	{
		$promissa = get_option('promissa');
	    $atts_extended = shortcode_atts( array(
	        'church_id' => '',
			'masstype' => '',
	        'subtitle' => '',
	        'limit' => 10,
	        'page' => 0,
			'offset' => '',
	        'show_title' => 'false',
	        'show_attendees' => 'false',
			'intention_product_id' => $promissa['week_product_id'],
			'feast_product_id' => $promissa['feast_product_id'],
	    ), $atts );
	    extract($atts_extended);

		$webshop = $intention_product_id !== NULL && $feast_product_id !== NULL && function_exists('wc_get_product');
		$intention_product_url = NULL;
		$feast_product_url = NULL;
		if ($webshop) :
			$intention_product_url = wc_get_product($intention_product_id)->get_permalink();
			$feast_product_url = wc_get_product($feast_product_id)->get_permalink();
		endif;

		$single_church = (isset($church_id) && !empty($church_id));
		$masses = NULL;
		$filter = '';
		if (!empty($offset) && !is_numeric($offset)) :
			$timestamp = time();

			switch (date('w', $timestamp)):
				case 0: //Sunday
				case 1: //Monday
				case 2: //Tuesday
				case 3: //Wednesday
				case 4: //Thursday
					$from = strtotime('Last Saturday ' . WEEK_SPLIT_TIME);
					$to = strtotime('Next Saturday ' . WEEK_SPLIT_TIME);
					$nextWeek = new DateTime('Saturday next week');
					//varDump($from);
					break;
				case 5: //Friday
					if (date('H', $timestamp) <= WEEK_SPLIT_HOUR) :
						$from = strtotime('Last Saturday ' . WEEK_SPLIT_TIME);
						$to = strtotime('Next Saturday ' . WEEK_SPLIT_TIME);
					else :
						$from = strtotime('Last Saturday ' . WEEK_SPLIT_TIME);
						$to = strtotime('Saturday next week ' . WEEK_SPLIT_TIME);
					endif;
					$nextWeek = new DateTime('Saturday next week');
					break;
				case 6: //Saturday
					$from = strtotime('Today ' . WEEK_SPLIT_TIME);
					$to = strtotime('Next Saturday ' . WEEK_SPLIT_TIME);
					$nextWeek = new DateTime('Saturday next week');
					break;
			endswitch;

			$end = intval(end(preg_split('/(,|-)/', $offset)));
			if ($end < 7) :
				$end *= 7;
			endif;
			$end *= 24;
			$end *= 60;
			$end *= 60;
			$to += $end;

			$fromDate = date('Y-m-d H:i:s', $from);
			$toDate = date('Y-m-d H:i:s', $to);
			//varDump($fromDate, $toDate);


			$offset = '&page=1%2C10';
			if ($single_church) :
				$filter .= '&filter2=start,ge,' . $fromDate . '&filter2=start,le,' . $toDate;
			else :
				$filter .= '&filter=start,ge,' . $fromDate . '&filter2=start,le,' . $toDate;
			endif;
		elseif($page != NULL && !empty($page) && is_numeric($page) && (int)$page > 0) :
			$offset = '&page=' . (string)$page . '%2C' . (string)$limit;
		endif;

		if(!empty($masstype)) :
			$filter .= '&filter';
			if(empty($filter) && !$single_church) :
				$filter .= '';
			else :
				$filter .= '2';
			endif;
			$filter .= '=massType,in,'. $masstype;
		endif;
		if($single_church) :
			$masses = ProMissaREST('Schedule', '?size=' . $limit . $offset . '&filter=church_ID,eq,' . html_entity_decode($church_id) . $filter);
			if(strpos($church_id, '&') !== -1) :
				$church_id = explode('&', $church_id)[0];
			endif;
		else :
			$masses = ProMissaREST('Schedule', '?size=' . $limit . $offset . $filter);
		endif;

		//if (!empty($masstype)) :
		//	var_dump(count($masses));
		//endif;

		$output = '';
		if(!$single_church && $content != null && !empty($content) && empty($masstype)) :
			$output .= sprintf('<p>%s</p>', $content);
		endif;
		if(!$single_church && !empty($subtitle) && empty($masstype)) :
			$output .= sprintf('<p>%s</p>', $subtitle);
		endif;
		//$output .= sprintf('<strong>%s</strong>', $mass['church']);
		if($single_church && $content != null && empty($subtitle)) :
			$output .= sprintf('<h2>%s</h2>', $subtitle);
		endif;
		if(($single_church && !empty($subtitle)) || (!$single_church && !empty($subtitle) && count($masses) > 0)) :
			$output .= sprintf('<h2>%s</h2>', $subtitle);
		endif;
		$now = time();
		if(count($masses) > 0) :
			$dayOfWeekNumber = NULL;
			foreach($masses as $mass)
			{
				if($single_church && $mass['church_ID'] != $church_id) :
					continue;
				endif;

				$dayOfWeek = NULL;
				$output .= '<p>';
				$day = '';

				$start = new DateTime($mass['start']);
				$end = new DateTime($mass['end']);
				if($dayOfWeek !== NULL && $dayOfWeek !== $start->format('N')) :
					break;
				endif;

				$dateFormatter = \IntlDateFormatter::create(
				  Locale::getDefault(),
				  IntlDateFormatter::NONE,
				  IntlDateFormatter::NONE,
				  date_default_timezone_get(),
				  IntlDateFormatter::GREGORIAN,
				  'cccc d MMMM Y'
				);
				if($dayOfWeek !== $start->format('N')) :
					if($dayOfWeek !== NULL) :
						$day .= '</p><p>';
					endif;
					if($dayOfWeekNumber !== $start->format('Y-m-d')) :
						$output .= sprintf('<strong>%s</strong><br />', ($start->format('Y-m-d') == date('Y-m-d') ?
						($now > $start->getTimestamp() - 3600 &&  $start->getTimestamp() > $now ? __('Soon', 'promissa') :
							($start->getTimestamp() < $now && $end->getTimestamp() > $now ? __('Now', 'promissa') : __('Today', 'promissa'))
						)
						 : (IsTomorrow($mass['start']) ? __('Tomorrow', 'promissa') : (WithinNextWeek($mass['start']) ? __('Upcoming', 'promissa') . ' ' .  getDayOfWeek($start->format('N')) : ucfirst($dateFormatter->format($start))))));
					endif;
				endif;
				$day .= sprintf('%1$s<em> %2$s %3$02d%4$s%5$02d%6$s</em><br />',

				(!$single_church ? $mass['church'] : ''), //1
				__('at', 'promissa'), //2
				$start->format('H'), //3
				Locale::getDefault() == 'nl-NL' ? '.' : ':', //4
				$start->format('i'), //5
				Locale::getDefault() == 'nl-NL' ? ' ' . __('hour', 'promissa') : '' //6
				);
				$dayOfWeek = $start->format('N');
				$dayOfWeekNumber = $start->format('Y-m-d');

				if($show_title == 'true' && !empty($mass['note'])) :
					$output .= $mass['note'] . ' <br />';
				endif;





				if(!empty($mass['web'])) :
					$output .= $mass['web'] . ' ' . (!$single_church ? __('in the', 'promissa') . ' ' : '');
				elseif(!empty($mass['massType'])) :
					$output .= $mass['massType'] . ' ' . (!$single_church ? __('in the', 'promissa') . ' ' : '');
				endif;
				$output .= $day;

				$youtube_id = $mass['youtube_id'];
				if($youtube_id != null) :
					$output = substr($output,0, strlen($output) - 6);
					if(substr($youtube_id, 0, 8) === 'https://' || substr($youtube_id, 0, 7) === 'http://') :
						$output .= '&nbsp;<a href="' . $youtube_id . '" target="_blank" style="line-height:2em;">&nbsp;<span style="font-family: \'entypo-fontello\';color:#841a18;font-size:1.5em;"></span>&nbsp;</a><br />';
					else :
						$output .= '&nbsp;<a href="https://www.youtube.com/watch?v=' . $youtube_id . '" target="_blank" style="line-height:2em;">&nbsp;<span style="font-family: \'entypo-fontello\';color:#841a18;font-size:1.5em;"></span>&nbsp;</a><br />';
					endif;
				endif;
				if($webshop && $start->getTimestamp() > time() + (4 * 24 * 60 * 60) && array_key_exists('orderIntention_YN', $mass) && $mass['orderIntention_YN'] == 1) :
					$url = (FeastDate_YN($start) ? $feast_product_url : $intention_product_url) . '?church_ID=' . $mass['church_ID'] . '&masses_ID=' . $mass['ID'] . '&date=' . $start->format('Y-m-d');
					$output .=  '<a href="' . $url . '" class="single_add_to_cart_button button alt" style="float: none;padding: 1em;display: inline-block;">' . __('Order intention online', 'promissa') . '&nbsp;<span style="font-family: \'entypo-fontello\';font-size:1.5em;"></span>&nbsp;</a>';
				endif;
				if($show_attendees == 'true' && !empty($mass['attendees'])) :
					$output .= implode('<br />', explode(';', $mass['attendees']))  . ' <br />';
				endif;
			}
			$output .= '</p>';
		endif;
		if(!empty($output)) :
			return $output;
		endif;
	}
endif;
add_shortcode( 'promissa-upcoming-masses', 'promissa_upcoming_masses_handler' );
?>