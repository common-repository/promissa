<?php
if (!function_exists('promissa_intentions_handler')) :
	define('EOL_SPLIT', '- ');
	define('EOL_SPLIT_SEARCH', ' - ');
	define('EOL_SPLIT_REPLACE', '$#');
	define('WEEK_SPLIT_HOUR', 15);
	define('WEEK_SPLIT_TIME', (WEEK_SPLIT_HOUR - 12) . 'pm');

	function uasortFloat($obj)
	{
		$church_ID = preg_replace('/[^0-9]/', '', $obj['church_ID']);
		if (strlen($church_ID) > 7) :
			$church_ID  = substr($church_ID, 0, 7);
		endif;
		return floatval((string)preg_replace('/[^0-9]/', '', $obj["start"]) . (string)round(floatval($church_ID) % 20) . (string)str_pad($obj["order"], 2, "0", STR_PAD_LEFT));
	}

	function promissa_intentions_handler($atts, $content = null)
	{
		$timestamp = time();
		//if (is_user_logged_in()) :
		//	$timestamp = strtotime('2022-09-11 16:00:00');
		//endif;
		$atts_extended = shortcode_atts(array(
			'church_id' => '',
			'subtitle' => '',
			'limit' => 100,
			'show_title' => 'false',
			'offset' => '',
			'show_types' => 'intention,funeral,baptize,marriage,announcement'
		), $atts);
		extract($atts_extended);
		$itemCount = 0;
		$single_church = ((isset($church_id) && !empty($church_id))) && !str_contains($church_id, ',');
		$multiple_church =  ((isset($church_id) && !empty($church_id))) && str_contains($church_id, ',');
		$intentions = NULL;
		$nextWeekOffset = 1;
		switch(date('w', $timestamp)) :
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
				if(date('H', $timestamp) <= WEEK_SPLIT_HOUR) :
					$from = strtotime('Last Saturday ' . WEEK_SPLIT_TIME);
					$to = strtotime('Next Saturday ' . WEEK_SPLIT_TIME);
				else:
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

		if(!empty($offset) && !is_numeric($offset)) :
			$end = intval(end(preg_split('/(,|-)/', $offset)));
			if($end < 7) :
				$end *= 7;
			endif;
			$end *= 24;
			$end *= 60;
			$end *= 60;
			$to += $end;
		endif;
		//$now = strtotime('Last Saturday ' . WEEK_SPLIT_TIME);
		//$to2 = strtotime('Next Saturday ' . WEEK_SPLIT_TIME);

		//$fromDate = DateTimeAdd(((new DateTime())->setISODate(date('Y', $from), date('W', $from) + $nextWeekOffset)), -2, 'Y-m-d 13:00:00');
		$fromDate = date('Y-m-d H:i:s', $from);
		//varDump($fromDate);
		//$toDate = DateTimeAdd(((new DateTime())->setISODate(date('Y', $to), date('W', $to) + 1)), 'P5D', 'Y-m-d 13:00:00');
		$toDate = date('Y-m-d H:i:s', $to);
		//varDump($toDate);
		//varDump($fromDate, $toDate);
		if ($single_church || $multiple_church) :

			//varDump($fromDate, $toDate);
			$intentions = ProMissaREST('Intenties', '?size=' . $limit . '&filter=church_ID,' . ($single_church ? 'eq' : 'in'). ',' . html_entity_decode($church_id) . '&filter2=start,ge,' . $fromDate . '&filter2=start,le,' . $toDate);

			if (strpos($church_id, '&') !== -1) :
				$church_id = explode('&', $church_id)[0];
			endif;
		else :
			$intentions = ProMissaREST('Intenties', '?size=' . $limit . '&filter=start,ge,' . $fromDate . '&filter2=start,le,' . $toDate);
		endif;
		if(empty($show_types)):
			$show_types = 'intention,funeral,baptize,marriage,announcement';
		endif;
		$types = explode(',', $show_types);
		//varDump($intentions);
		$output = '';
		if (!$single_church && $content != null && !empty($content)) :
			$output .= sprintf('<p>%s</p>', $content);
		endif;
		if (!$single_church && !empty($subtitle)) :
			$output .= sprintf('<strong>%s</strong><br />', $subtitle);
		endif;
		//$output .= sprintf('<strong>%s</strong>', $intention['church']);
		if ($single_church && $content != null && !empty($subtitle)) :
			$output .= sprintf('<h2>%s</h2>', $subtitle);
		endif;
		if ($single_church && !empty($subtitle)) :
			$output .= sprintf('<h2>%s</h2>', $subtitle);
		endif;

		if (count($intentions) > 0) :
			uasort($intentions, function ($a, $b) {
				return uasortFloat($a) > uasortFloat($b) ? 1 : -1;
			});
			$last_date = '';
			$last_day = '';
			$intentionsList = array();
			$sort = 0;
			$maxTime = (3 * 7 * 24 * 60 * 60);
			$idate = null;

			if($offset == 0 || is_numeric($offset)) :
				$date = new DateTime('today');
				$date->modify("+$offset day");
				$start = $date->format('Y-m-d');
				$intentionsFilter = array();
				foreach ($intentions as $intention) :
					if(substr($intention['start'], 0, 10) == $start) :
						//var_dump($intention);
						$intentionsFilter[] = $intention;
					endif;
				endforeach;
				$intentions = $intentionsFilter;
			endif;

			foreach ($intentions as $intention) :
				if (!key_exists($intention['type'], $intentionsList)) :
					$intentionsList[$intention['type']] = array();
				endif;

				$note = strtolower(trim($intention['note']));
				$hourMinute = null;
				for ($time = $from; $time < $to; $time += 86400) :

					for ($h = 0; $h < 24; $h++) :
						foreach (array('00', '15', '30', '45') as $m) :
							if (strpos($note, $h . "." . $m) !== false) :
								$hourMinute = ($h * 60) + (int)$m;
								break 2;
							endif;
						endforeach;
					endfor;
					if ($hourMinute == null) :
						$hourMinute = 77777;
					endif;
					if (strpos($note, DayDateMonth($time)) !== false) :
						$idate = $time + $sort + ($hourMinute ?? 77777);
					elseif (strpos($note, ShortDateAndMonth($time)) !== false) :
						$idate = $time + $sort + ($hourMinute ?? 77777);
					elseif (strpos($note, LongDate($time)) !== false) :
						$idate = $time + $sort + ($hourMinute ?? 77777);
					elseif (strpos($note, ShortDate($time)) !== false) :
						$idate = $time + $sort + ($hourMinute ?? 77777);
					elseif (strpos($note, FullDateAt($time)) !== false) :
						$idate = $time + $sort + ($hourMinute ?? 77777);
					endif;
				endfor;
				if ($idate != null && (strpos($note, 'doop') !== false || strpos($note, 'huwelijk') !== false || strpos($note, 'uitvaart') !== false)) :
					$idate += ($hourMinute ?? 800);
				endif;
				$intention['sort'] = ($idate ?? ($maxTime + $sort));
				$intentionsList[$intention['type']][] = $intention;
				$sort++;
			endforeach;

			foreach ($types as $type) :
				if (isset($intentionsList[$type]) && $intentionsList[$type] != null && is_array($intentionsList[$type])) :
					uasort($intentionsList[$type], function ($a, $b) {
						global $single_church;
						if ($single_church) :
							return str_pad($a["sort"], 13, "0", STR_PAD_LEFT) . '#' . $a["start"] . '#' . str_pad($a["order"], 13, "0", STR_PAD_LEFT) <=> str_pad($b["sort"], 13, "0", STR_PAD_LEFT) . '#' . $b["start"] . '#' . str_pad($b["order"], 13, "0", STR_PAD_LEFT);
						else :
							return (string)$a["start"] . '#' . $b['church_ID'] . '#' . str_pad($a["sort"], 13, "0", STR_PAD_LEFT) <=> (string)$b["start"] . '#' . $a['church_ID'] . '#' . str_pad($b["sort"], 13, "0", STR_PAD_LEFT);
						endif;
					});
					$dayOfWeek = NULL;
					$usedIntentions = array();
					foreach ($intentionsList[$type] as $intention) :
						if ($single_church && $intention['church_ID'] != $church_id) :
							continue;
						endif;

						$day = '';
						$date = new DateTime($intention['start']);
						//varDump($date, $nextWeek);
						if ($date > $nextWeek) :
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

						if ($intention['type'] == $type && $type == 'intention') :
							if ($dayOfWeek !== $date->format('w')) :
								$last_date = sprintf('<h4>%s', (((ucfirst($dateFormatter->format($date))))));
							endif;

							$day .= sprintf(
								'%1$s %2$s %3$02d%4$s%5$02d%6$s%7$s</h4><ul>',


								(!$single_church ? $intention['church'] : $last_date), //1
								__('at', 'promissa'), //2
								$date->format('H'), //3
								Locale::getDefault() == 'nl-NL' ? '.' : ':', //4
								$date->format('i'), //5
								Locale::getDefault() == 'nl-NL' ? ' ' . __('hour', 'promissa') : '', //6
								'', //is_numeric($offset) ? (' ' . __('at the', 'promissa') . ' ' . $intention['church']) : '' //7
							);

							$dayOfWeek = $date->format('N');
						elseif ($intention['type'] == $type && $type == 'announcement') :
							$day .= sprintf('<strong>%1$s</strong><ul>', __('Announcements', 'promissa'));
						elseif ($intention['type'] == $type && $type == 'funeral') :
							$day .= sprintf('<strong>%1$s</strong><ul>', __('Funeral(s) from this week', 'promissa'));
						elseif ($intention['type'] == $type && $type == 'baptize') :
							$day .= sprintf('<strong>%1$s</strong><ul>', __('Baptize(s) from this week', 'promissa'));
						elseif ($intention['type'] == $type && $type == 'marriage') :
							$day .= sprintf('<strong>%1$s</strong><ul>', __('Marriage(s) from this week', 'promissa'));
						endif;
						if ($intention['type'] == $type) :
							if ($day !== $last_day) :
								if (!empty($last_day)) :
									$output .= '</ul>';
								endif;
								$usedIntentions = array();
								$output .= $day;
							endif;
							$last_day = $day;
							if ($show_title == 'true' && !empty($intention['note'])) :
								$itemCount++;
								foreach (array(EOL_SPLIT) as $split) :
									if (substr($intention['note'], 0, 2) == $split) :
										foreach (explode(PHP_EOL, $intention['note']) as $note) :
											$note = KeiFormat::IntentionNote($note, $intention['type']);
											if ($intention['type'] != 'announcement') :
												$output .= '<li>' . ltrim($note, $split) . '</li>';
											endif;
											$usedIntentions[] = removeEndOfString(ltrim($note, $split));
										endforeach;
										continue 2;
									endif;
								endforeach;
								foreach ($usedIntentions as $usedIntention) :
									if (strpos(strtolower(removeEndOfString($usedIntention)), strtolower(removeEndOfString($intention['note']))) !== false) :
										continue 2;
									endif;
								endforeach;

								$intention['note'] = KeiFormat::IntentionNote($intention['note'], $intention['type']);
								$usedIntentions[] = $intention['note'];
								if($intention['type'] != 'announcement') :
									$output .= '<li>' . $intention['note'] . '</li>';
								endif;
							endif;
						endif;

					endforeach;
					//varDump($usedIntentions);
					if($type == 'announcement' && $usedIntentions != null && is_array($usedIntentions) && count($usedIntentions) > 0) :
						$output .= '<li>' . implode('</li><li>', array_unique($usedIntentions, SORT_REGULAR)) . '</li>';
					endif;
					$output .= '</ul>';

				endif;
			endforeach;
		endif;
		if (!empty($output) && $itemCount > 0) :
			return $output;
		endif;
	}
endif;
add_shortcode('promissa-intentions', 'promissa_intentions_handler');
