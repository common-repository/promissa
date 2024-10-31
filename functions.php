<?php
	if (!function_exists('varDump')) :
		function varDump(...$params)
		{
			if (is_user_logged_in()) :
				if (is_array($params)) :
					foreach ($params as $param) :
						var_dump($param);
						error_log($param, 0);
					endforeach;
				else :
					var_dump($params);
					error_log($params, 0);
				endif;
				die();
			endif;
		}
	endif;
	if (!function_exists('GetProMissaDate')) :
		function GetProMissaDate($datetime)
		{
			if (is_numeric($datetime)) :
				$date = new DateTime();
				$date->setTimestamp($datetime);
				return $date;
			elseif (!is_object($datetime)) :
				$datetime = new DateTime($datetime);
			endif;

			return $datetime;
		}
	endif;
	if (!function_exists('WithinNextWeek'))
	{
		function WithinNextWeek($val)
		{
			$date = GetProMissaDate($val);
			$now = new DateTime();
			$today = new DateTime();
			$today->add(new DateInterval('P7D'));
			return ($now < $today && $today > $date);
		}
	}

	if (!function_exists('IsTomorrow'))
	{
		function IsTomorrow($val)
		{
			$date = GetProMissaDate($val);
			$now = new DateTime();
			$now->setTime(12, 0);
			$tomorrow = new DateTime();
			$tomorrow->add(new DateInterval('P1D'));
			$tomorrow->setTime(12, 0);
			return ($now < $tomorrow && $tomorrow > $date);
		}
	}

	if (!function_exists('WithinNextWeek_YN'))
	{
		function WithinNextWeek_YN($val)
		{
			$date = GetProMissaDate($val);
			$now = new DateTime();
			$now->add(new DateInterval('P7D'));
			//varDump($now, $date);
			return ($now->getTimestamp() > $date->getTimestamp());
		}
	}

	if (!function_exists('IsTomorrow_YN'))
	{
		function IsTomorrow_YN($val)
		{
			$date = GetProMissaDate($val);
			$date->setTime(12, 0);
			$tomorrow = new DateTime();
			$tomorrow->add(new DateInterval('P1D'));
			$tomorrow->setTime(12, 0);
			return ($tomorrow == $date);
		}
	}

	if (!function_exists('idiv')) {
		function idiv($a, $b)
		{
			return floor($a / $b);
		}
	}

	/**
	 * Calculates the Easter date for a given year
	 *
	 * @param  int $y
	 * @return DateTime
	 */
	if (!function_exists('EasterDate')) {
		function EasterDate($y)
		{
			$firstdig1 = array(21, 24, 25, 27, 28, 29, 30, 31, 32, 34, 35, 38);
			$firstdig2 = array(33, 36, 37, 39, 40);

			$firstdig = idiv($y, 100);
			$remain19 = $y % 19;

			$temp = idiv($firstdig - 15, 2) + 202 - 11 * $remain19;

			if (in_array($firstdig, $firstdig1)) {
				$temp = $temp - 1;
			}
			if (in_array($firstdig, $firstdig2)) {
				$temp = $temp - 2;
			}

			$temp = $temp % 30;

			$ta = $temp + 21;
			if ($temp == 29) {
				$ta = $ta - 1;
			}
			if ($temp == 28 and $remain19 > 10) {
				$ta = $ta - 1;
			}

			$tb = ($ta - 19) % 7;

			$tc = (40 - $firstdig) % 4;
			if ($tc == 3) {
				$tc = $tc + 1;
			}
			if ($tc > 1) {
				$tc = $tc + 1;
			}

			$temp = $y % 100;
			$td = ($temp + idiv($temp, 4)) % 7;

			$te = ((20 - $tb - $tc - $td) % 7) + 1;
			$d = $ta + $te;

			if ($d > 31) {
				$d = $d - 31;
				$m = 4;
			} else {
				$m = 3;
			}
			return new DateTime("$y-$m-$d", new DateTimeZone('Europe/Amsterdam'));
		}
	}

	if (!function_exists('FeastDate_YN')) :
		function FeastDate_YN($date)
		{
			$datetime = GetProMissaDate($date);
			if($datetime->format('N') >= 6) :
				return true;
			endif;
			$feasts = array('08-12','24-12', '25-12', '26-12', '01-01', '06-01', '19-03', '25-03', '24-06', '29-06', '15-08', '01-11', '07-11');
			if (in_array($datetime->format('d-m'), $feasts)) :
				return true;
			endif;

			$easter = EasterDate($datetime->format('Y'));
			$specialDates = array($easter->add(new DateInterval('P39D'))->format('Y-m-d'), $easter->add(new DateInterval('P68D'))->format('Y-m-d'));
			if (in_array($datetime->format('Y-m-d'), $specialDates)) :
				return true;
			endif;

			return false;
		}
	endif;

	if (!function_exists('LongDate')) :
		/**
		 * @param object datetime
		 * @return string maandag 20 december 2010
		 */
		function LongDate($datetime)
		{
			$datetime = GetProMissaDate($datetime);

			$days = array(
				__('sunday', 'promissa'),
				__('monday', 'promissa'),
				__('tuesday', 'promissa'),
				__('wednesday', 'promissa'),
				__('thursday', 'promissa'),
				__('friday', 'promissa'),
				__('saturday', 'promissa')
			);
			$months = array(
				__("January", 'promissa'),
				__("February", 'promissa'),
				__("March", 'promissa'),
				__("April", 'promissa'),
				__("May", 'promissa'),
				__("June", 'promissa'),
				__("July", 'promissa'),
				__("August", 'promissa'),
				__("September", 'promissa'),
				__("October", 'promissa'),
				__("November", 'promissa'),
				__("December", 'promissa')
			);
			return $days[$datetime->format('w')] . ' ' . $datetime->format('j') . ' ' . $months[$datetime->format('m') - 1] . ' ' . $datetime->format('Y');
		}
	endif;

	if (!function_exists('FromLongDate')) :
		/**
		 * @param object datetime
		 * @return string maandag 20 december 2010
		 */
		function FromLongDate($text)
		{

			$days = array(
				__('sunday', 'promissa'),
				__('monday', 'promissa'),
				__('tuesday', 'promissa'),
				__('wednesday', 'promissa'),
				__('thursday', 'promissa'),
				__('friday', 'promissa'),
				__('saturday', 'promissa')
			);
			$months = array(
				'',
				__("January", 'promissa'),
				__("February", 'promissa'),
				__("March", 'promissa'),
				__("April", 'promissa'),
				__("May", 'promissa'),
				__("June", 'promissa'),
				__("July", 'promissa'),
				__("August", 'promissa'),
				__("September", 'promissa'),
				__("October", 'promissa'),
				__("November", 'promissa'),
				__("December", 'promissa')
			);
			$elements = explode(' ', $text);
			$day = array_shift($elements);
			return ($elements[2] . '-' . str_pad(array_search($elements[1], $months), 2, "0", STR_PAD_LEFT) . '-' . str_pad($elements[0], 2, "0", STR_PAD_LEFT));
		}
	endif;

	if (!function_exists('FullDateAt')) :
		/**
		 * @param object datetime
		 * @return string maandag 20 december 2010 om 9:42
		 */
		function FullDateAt($datetime)
		{
			$datetime = GetProMissaDate($datetime);
			$days = array(
				__('sunday', 'promissa'),
				__('monday', 'promissa'),
				__('tuesday', 'promissa'),
				__('wednesday', 'promissa'),
				__('thursday', 'promissa'),
				__('friday', 'promissa'),
				__('saturday', 'promissa')
			);
			$months = array(
				__("January", 'promissa'),
				__("February", 'promissa'),
				__("March", 'promissa'),
				__("April", 'promissa'),
				__("May", 'promissa'),
				__("June", 'promissa'),
				__("July", 'promissa'),
				__("August", 'promissa'),
				__("September", 'promissa'),
				__("October", 'promissa'),
				__("November", 'promissa'),
				__("December", 'promissa')
			);
			return $days[$datetime->format('w')] . ' ' . $datetime->format('j') . ' ' . $months[$datetime->format('m') - 1] . ' ' . $datetime->format('Y') . ' om ' . $datetime->format('G:i');
		}
	endif;



	if (!function_exists('DayDateMonth')) :
		/**
		 * @param object datetime
		 * @return string maandag 20 december
		 */
		function DayDateMonth($datetime)
		{
			$datetime = GetProMissaDate($datetime);
			$days = array(
				__('sunday', 'promissa'),
				__('monday', 'promissa'),
				__('tuesday', 'promissa'),
				__('wednesday', 'promissa'),
				__('thursday', 'promissa'),
				__('friday', 'promissa'),
				__('saturday', 'promissa')
			);
			$months = array(
				__("January", 'promissa'),
				__("February", 'promissa'),
				__("March", 'promissa'),
				__("April", 'promissa'),
				__("May", 'promissa'),
				__("June", 'promissa'),
				__("July", 'promissa'),
				__("August", 'promissa'),
				__("September", 'promissa'),
				__("October", 'promissa'),
				__("November", 'promissa'),
				__("December", 'promissa')
			);
			return $days[$datetime->format('w')] . ' ' . $datetime->format('j') . ' ' . $months[$datetime->format('m') - 1];
		}
	endif;
	if (!function_exists('ShortDateAndMonth')) :
		/**
		 * @param object datetime
		 * @return string 20 dec
		 */
		function ShortDateAndMonth($datetime)
		{
			$datetime = GetProMissaDate($datetime);
			$days = array("zo", "ma", "di", "wo", "do", "vr", "za");
			$months = array("jan", "feb", "mrt", "apr", "mei", "jun", "jul", "aug", "sep", "okt", "nov", "dec");
			return $datetime->format('d') . ' ' . $months[$datetime->format('m') - 1];
		}
	endif;

	if (!function_exists('ShortDate')) :
		/**
		 * @param object datetime
		 * @return string ma 20 december 2010
		 */
		function ShortDate($datetime)
		{
			$datetime = GetProMissaDate($datetime);
			$days = array("zo", "ma", "di", "wo", "do", "vr", "za");
			$months = array(
				__("January", 'promissa'),
				__("February", 'promissa'),
				__("March", 'promissa'),
				__("April", 'promissa'),
				__("May", 'promissa'),
				__("June", 'promissa'),
				__("July", 'promissa'),
				__("August", 'promissa'),
				__("September", 'promissa'),
				__("October", 'promissa'),
				__("November", 'promissa'),
				__("December", 'promissa')
			);
		return $days[$datetime->format('w')] . ' ' . $datetime->format('j') . ' ' . $months[$datetime->format('m') - 1] . ' ' . $datetime->format('Y');
		}
	endif;

	if (!function_exists('DateTimeAdd')) :
		function DateTimeAdd($datetime, $days, $format = 'd-m-Y H:i:s')
		{
			$invert = 0;
			$datetime = GetProMissaDate($datetime);
			if (is_numeric($days)) :
				if ($days < 0) :
					$invert = 1;
				endif;
				$days = 'P' . abs($days) . 'D';
			endif;
			$interval = new DateInterval($days);
			$interval->invert = $invert;
			$datetime->add($interval);
			return $datetime->format($format);
		}
	endif;

	if (!function_exists('removeEndOfString')) :
		function removeEndOfString($string)
		{
			return trim(str_replace(array('.', '!', '?'), '', trim($string)));
		}
	endif;

	if (!function_exists('ProMissaREST'))
	{
		function ProMissaREST($page, $filter = '')
		{
			try {
				$promissa = get_option('promissa');
				$url = 'https://api.promissa.nl/v1.6/records/' . $page . $filter;
				$args = array(
					'headers' => array(
						'Authorization' => 'Basic ' . base64_encode($promissa['private'] . ":" . $promissa['public'])
					)
				);
				if(empty($filter)) :
					$url .= '?';
				else :
					$url .= '&';
				endif;
				$response = wp_remote_get( $url . 'exclude=api_private,domain', $args );

				$body = wp_remote_retrieve_body( $response );

				$manage = json_decode($body, true);
				if($manage["records"] != NULL) :
					return $manage["records"];
				else :
					//var_dump($response );
					return $manage["records"];
				endif;
			} catch (Exception $e) {

			}

		}
	}

	if (!function_exists('ProMissaREST_POST')) {
		function ProMissaREST_POST($page, $data = array())
		{
			try {
				$promissa = get_option('promissa');
				$url = 'https://api.promissa.nl/v1.5/records/' . $page;
				$args = array(
					'headers' => array(
						'Authorization' => 'Basic ' . base64_encode($promissa['private'] . ":" . $promissa['public'])
					),
					'body'        => $data,
					'method'      => 'POST',
					'data_format' => 'body',
				);

				$response = wp_remote_post($url, $args);

				//varDump($response);
			} catch (Exception $e) {
			}
		}
	}

	if (!function_exists('kei_post_val'))
	{
		function kei_post_val($val)
		{
			if(isset($_POST[$val]) && !empty($_POST[$val])) :
				return sanitize_text_field($_POST[$val]);
			endif;
			return null;
		}
	}

	if (!function_exists('callback'))
	{
		function callback($buffer)
		{
			return $buffer;
		}
	}

	if (!function_exists('add_ob_start'))
	{
		function add_ob_start()
		{
			ob_start("callback");
		}
	}

	if (!function_exists('flush_ob_end'))
	{
		function flush_ob_end()
		{
			ob_end_flush();
		}
	}
	if (!function_exists('guid'))
	{
		function guid()
		{
		    if (function_exists('com_create_guid') === true) :
			return strtolower(trim(com_create_guid(), '{}'));
		endif;

		return strtolower(sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535)));
		}
	}

	if (!function_exists('is_guid'))
	{
		function is_guid($guid)
		{
			return !empty($guid) && strlen($guid) == 36 && preg_match('/^\{?[a-z0-9]{8}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{12}\}?$/', $guid);
		}
	}

	if (!function_exists('jsDateTime'))
	{
		function jsDateTime($datetime)
		{
			$DateTime = new DateTime($datetime);
			return $DateTime->format('Y-m-d') . 'T' . $DateTime->format('H:i:s');
		}
	}

	if (!function_exists('jsEndDateTime'))
	{
		function jsEndDateTime($datetime, $duration)
		{
			if(empty($duration))
			{
				$duration = 60;
			}
			$DateTime = new DateTime($datetime);
			$DateTime->add(new DateInterval('PT' . $duration . 'M'));
			return $DateTime->format('Y-m-d') . 'T' . $DateTime->format('H:i:s');
		}
	}

	if (!function_exists('HexTextColor'))
	{
		function HexTextColor($class)
		{
			switch($class)
			{
				case 'white':
					return '#000';
				case 'yellow':
				case 'gray':
					return '#666';
				case 'black':
					return '#ccc';
				default:
					return '#fff';
			}
		}
	}

	if (!function_exists('HexBorderColor'))
	{
		function HexBorderColor($class)
		{
			switch($class)
			{
				case 'green':
					return '#29b765';
				case 'yellow':
					return '#deb200';
				case 'orange':
					return '#d67520';
				case 'red':
					return '#cf4436';
				case 'white':
				case 'gray':
					return '#dfe8f1';
				case 'black':
					return '#000';
				case 'blue':
				case 'blue-alt':
					return '#5388d1';
				case 'purple':
					return '#7a3ecc';
				default:
					return '#00b19b';
			}
		}
	}

	if (!function_exists('HexBackgroundColor'))
	{
		function HexBackgroundColor($class)
		{
			switch($class)
			{
				case 'green':
					return '#2ecc71';
				case 'yellow':
					return '#fc0';
				case 'orange':
					return '#e67e22';
				case 'red':
					return '#e74c3c';
				case 'white':
					return '#fff';
				case 'gray':
					return '#efefef';
				case 'black':
					return '#2d2d2d';
				case 'blue':
				case 'blue-alt':
					return '#65a6ff';
				case 'purple':
					return '#984dff';
				case 'primary':
				default:
					return '#00bca4';
			}
		}
	}

	if (!function_exists('Trim'))
	{
		function Trim($s, $max_length = 300)
		{
			if (strlen($s) > $max_length) :
			    $offset = ($max_length - 3) - strlen($s);
			    $s = substr($s, 0, strrpos($s, ' ', $offset)) . '...';
			endif;
			return $s;
		}
	}

	if (!function_exists('getDayOfWeek'))
	{
		function getDayOfWeek($i)
		{
			switch($i) {
				case 1:
					return __('monday', 'promissa');
				case 2:
					return __('tuesday', 'promissa');
				case 3:
					return __('wednesday', 'promissa');
				case 4:
					return __('thursday', 'promissa');
				case 5:
					return __('friday', 'promissa');
				case 6:
					return __('saturday', 'promissa');
				case 7:
					return __('sunday', 'promissa');
				default:
					return '';
			}
		}
	}
if (!class_exists('KeiFormat')) {
	/**
	 * Format various of objects into the correct layout
	 *
	 * @author     Marco van 't Klooster, Kerk en IT <info@kerkenit.nl>
	 */

	class KeiFormat {

		/**
		 * Check if text is a valid JSON
		 *
		 * @param  string $string
		 * @return bool
		 */
		public static function IsJson($string)
		{
			json_decode($string);
			return json_last_error() === JSON_ERROR_NONE;
		}

		/**
		 * Get Date from various types
		 *
		 * @param  int|string|DateTime $datetime
		 * @return DateTime DateTime object
		 */
		private static function GetDate($datetime)
		{
			if (is_numeric($datetime)) :
				$date = new DateTime();
				$date->setTimestamp($datetime);
				return $date;
			elseif (!is_object($datetime)) :
				$datetime = new DateTime($datetime ?? 'now');
			endif;

			return $datetime;
		}

		/**
		 * Get's a list with the full names of all months
		 *
		 * @return array
		 */
		private static function months_full()
		{
			return array("januari", "februari", "maart", "april", "mei", "juni", "juli", "augustus", "september", "oktober", "november", "december");
		}

		/**
		 * Get's a list with the short names of all months
		 *
		 * @return array
		 */
		private static function months_short()
		{
			return array("jan", "feb", "mrt", "apr", "mei", "jun", "jul", "aug", "sep", "okt", "nov", "dec");
		}

		/**
		 * Get's a list with the full names of the days of the week
		 *
		 * @return array
		 */
		private static function days_full()
		{
			return array("zondag", "maandag", "dinsdag", "woensdag", "donderdag", "vrijdag", "zaterdag");
		}

		/**
		 * Get's a list with the short names of the days of the week
		 *
		 * @return array
		 */
		private static function days_short()
		{
			return array("zo", "ma", "di", "wo", "do", "vr", "za");
		}

		/**
		 * maandag 20 december 2010
		 *
		 * @param object datetime
		 * @return string maandag 20 december 2010
		 */
		public static function FullDate($datetime)
		{
			$datetime = self::GetDate($datetime);
			return self::days_full()[$datetime->format('w')] . ' ' . $datetime->format('j') . ' ' . self::months_full()[$datetime->format('m') - 1] . ' ' . $datetime->format('Y');
		}

		/**
		 * Get types for intentions to format JSON
		 *
		 * @param  string $type
		 * @return array|null
		 */
		public static function GetIntentionJson($type)
		{
			switch ($type):
				case 'funeral';
					return array(
						'Naam' => NULL,
						'Leeftijd' => 0
					);
					break;
				case 'baptize';
					return array(
						'Dopeling' => NULL,
					);
					break;
				case 'marriage';
					return array(
						'Bruid' => NULL,
						'Bruidegom' => NULL
					);
					break;
				default:
					return NULL;
			endswitch;
		}

		/**
		 * Gets the funeral formatted text
		 *
		 * @param  string $json
		 * @param  string $date
		 * @return string
		 */
		public static function Funeral($json, $date = '')
		{
			$note_arr = (array)json_decode($json);
			$note_arr['Uitvaart op'] = $date;
			if(is_numeric($note_arr['Leeftijd'])) :
				$note_arr['Naam'] .= ' (' . $note_arr['Leeftijd'] . ' jaar)';
				unset($note_arr['Leeftijd']);
			endif;
			if(empty($date)) :
				$note_arr = array_filter($note_arr, fn ($value) => !is_null($value) && !empty($value));
			endif;
			$note_text = '';
			foreach (self::GetIntentionJson('funeral') as $key => $value) :
				if (key_exists($key, $note_arr)) :
					if($key == 'Naam') :
						$note = $note_arr[$key];
					elseif ($key == 'Uitvaart op') :
						$note = $key . ' ' . $note_arr[$key];
					else :
						$note = $key . ': ' . $note_arr[$key];
					endif;
					$note = htmlspecialchars(ltrim(str_replace(EOL_SPLIT_REPLACE, EOL_SPLIT_SEARCH, $note), EOL_SPLIT));
					if (!empty($note)) :
						$note_text .= $note . '.' . PHP_EOL;
					endif;
				endif;
			endforeach;
			return $note_text;
		}

		/**
		 * Gets the baptize formatted text
		 *
		 * @param  string $json
		 * @param  string $date
		 * @return string
		 */
		public static function Baptize($json, $date = '')
		{
			$note_arr = (array)json_decode($json);
			$note_arr['Doop op'] = self::FullDate($date);
			if(isset($note_arr['Geboortedatum'])):
				if (is_numeric($note_arr['Geboortedatum'])) :
					$note_arr['Dopeling'] .= ' (' . $note_arr['Geboortedatum'] . ' jaar)';
					unset($note_arr['Geboortedatum']);
				else :
					$note_arr['Geboortedatum'] = self::FullDate($note_arr['Geboortedatum']);
				endif;
			endif;
			if (empty($date)) :
				$note_arr = array_filter($note_arr, fn ($value) => !is_null($value) && !empty($value));
			endif;
			$note_text = '';
			foreach (self::GetIntentionJson('baptize') as $key => $value) :
				if (key_exists($key, $note_arr)) :
					if ($key == 'Dopeling') :
						$note = $note_arr[$key];
					elseif ($key == 'Doop op') :
						$note = $key . ' ' . $note_arr[$key];
					else :
						$note = $key . ': ' . $note_arr[$key];
					endif;
					$note = htmlspecialchars(ltrim(str_replace(EOL_SPLIT_REPLACE, EOL_SPLIT_SEARCH, $note), EOL_SPLIT));
					if (!empty($note)) :
						$note_text .= $note . '.' . PHP_EOL;
					endif;
				endif;
			endforeach;
			return $note_text;
		}

		/**
		 * Gets the marriage formatted text
		 *
		 * @param  string $json
		 * @param  string $date
		 * @return string
		 */
		public static function Marriage($json, $date = '')
		{
			$note_arr = (array)json_decode($json);
			$note_arr['Huwelijk op'] = self::FullDate($date);

			if (empty($date)) :
				$note_arr = array_filter($note_arr, fn ($value) => !is_null($value) && !empty($value));
			endif;
			$note_text = '';
			foreach (self::GetIntentionJson('marriage') as $key => $value) :
				if (key_exists($key, $note_arr)) :
					if ($key == 'Bruid') :
						$note = $note_arr['Bruid']  . ' & ' . $note_arr['Bruidegom'];
					elseif ($key == 'Bruidegom') :
						continue;
					elseif ($key == 'Huwelijk op') :
						$note = 'op ' . $note_arr[$key];
					else :
						$note = $key . ': ' . $note_arr[$key];
					endif;
					$note = htmlspecialchars(ltrim(str_replace(EOL_SPLIT_REPLACE, EOL_SPLIT_SEARCH, $note), EOL_SPLIT));
					if (!empty($note)) :
						$note_text .= $note . '.' . PHP_EOL;
					endif;
				endif;
			endforeach;
			return $note_text;
		}

		/**
		 * Get the note text of an intention
		 *
		 * @param  string $note
		 * @param  string $type
		 * @return string
		 */
		public static function IntentionNote($note, $type = '')
		{
			if (self::IsJson($note)) :
				if ($type == 'funeral') :
					$note = self::Funeral($note);
				elseif ($type == 'baptize') :
					$note = self::Baptize($note);
				elseif ($type == 'marriage') :
					$note = self::Marriage($note);
				endif;
			endif;
			return nl2br($note);
		}
	}
}