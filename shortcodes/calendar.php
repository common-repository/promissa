<?php
define('FULLCALENDER', '4.2.0');
$ProMissaEvents = array();
if (!function_exists('promissa_calendar_handler')) :
	function promissa_calendar_handler($atts, $content = null)
	{
		$promissa = get_option('promissa');
		$atts_extended = shortcode_atts(array(
			'church_id' => '',
			'limit' => 1000,
			'show_title' => 'false',
			'show_attendees' => 'false',
			'page_id' => NULL,
			'intention_product_id' => $promissa['week_product_id'],
			'feast_product_id' => $promissa['feast_product_id'],
		), $atts);
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

		if ($single_church) :
			$masses = ProMissaREST('Calendar', '?size=' . $limit . '&filter=church_ID,eq,' . $church_id);
		else :
			$masses = ProMissaREST('Calendar', '?size=' . $limit);

		endif;
		$output = '';
		if (!$single_church && $content != null && !empty($content)) :
			$output .= sprintf('<p>%s</p>', $content);
		endif;
		if (!$single_church && !empty($subtitle)) :
			$output .= sprintf('<p>%s</p>', $subtitle);
		endif;

		if ($single_church && $content != null && !empty($subtitle)) :
			$output .= sprintf('<h2>%s</h2>', $subtitle);
		endif;
		if ($single_church && !empty($subtitle)) :
			$output .= sprintf('<h2>%s</h2>', $subtitle);
		endif;
		global $ProMissaEvents;
		$lLdJsonEvents = array();
		wp_enqueue_script('moment-timezone', 'https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/' . FULLCALENDER . '/moment-timezone/main.min.js', array('jquery'), FULLCALENDER, true);
		wp_enqueue_script('fullcalender-core', 'https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/' . FULLCALENDER . '/core/main.min.js', array('moment-timezone'), FULLCALENDER, true);
		wp_enqueue_script('locales-all', 'https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/' . FULLCALENDER . '/core/locales-all.min.js', array('fullcalender-core'), FULLCALENDER, true);

		wp_enqueue_script('fullcalender-daygrid', 'https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/' . FULLCALENDER . '/daygrid/main.min.js', array('fullcalender-core'), FULLCALENDER, true);
		wp_enqueue_script('fullcalender-timegrid', 'https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/' . FULLCALENDER . '/timegrid/main.min.js', array('fullcalender-core'), FULLCALENDER, true);
		wp_enqueue_script('popper', 'https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.15.0/umd/popper.min.js', array('jquery'), '1.15.0', true);
		wp_enqueue_script('tooltip', 'https://cdnjs.cloudflare.com/ajax/libs/tooltip.js/1.3.2/umd/tooltip.min.js', array('popper'), '1.3.2', true);



		wp_enqueue_style('fullcalender', 'https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/' . FULLCALENDER . '/core/main.min.css', false, FULLCALENDER, 'all');
		wp_enqueue_style('fullcalender', 'https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/' . FULLCALENDER . '/daygrid/main.min.css', false, FULLCALENDER, 'all');
		wp_enqueue_style('fullcalender', 'https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/' . FULLCALENDER . '/timegrid/main.min.css', false, FULLCALENDER, 'all');

		if (count($masses) > 0) :
			$dateFormatter = \IntlDateFormatter::create(
				Locale::getDefault(),
				IntlDateFormatter::NONE,
				IntlDateFormatter::NONE,
				date_default_timezone_get(),
				IntlDateFormatter::GREGORIAN,
				'cccc d MMMM Y'
			);
			$dateformat = 'c';



			foreach ($masses as $mass) :

				if ($single_church && $mass['church_ID'] != $church_id) :
					continue;
				endif;
				extract($mass);

				if (function_exists('z_taxonomy_image_url')) :
					if (empty($image)) :
						foreach (get_categories() as $category) {
							if (empty($image)) :
								$image = z_taxonomy_image_url($category->term_id, 'large');
							endif;
						}
					endif;
				endif;
				if (empty($image)) :
					if (function_exists('avia_get_option')) :
						$image = avia_get_option('logo');
					endif;
				endif;

				$date = new DateTime($start);

				$ProMissaEvents[] = array(
					'id' => $ID,
					'title' =>  $massType . (!$single_church ? ' (' . (strlen($short) > 2 ?  substr($short, 0, 2) : $short) . ')' : ''),
					'tooltip' =>  "<table>
							<tr>
								<th colspan=\"2\">" . (!empty($note) ? $note : (!empty($memo) ? Trim($memo) : Trim($massType))) . "</th>
							</tr>
							<tr>
								<td>" . __('Location', 'promissa') . ": </td>
								<td>" . $church . "</td>
							</tr>
							<tr>
								<td>" . __('Date', 'promissa') . ": </td>
								<td>" . $dateFormatter->format($date) . "</td>
							</tr>
							<tr>
								<td>" . __('Time', 'promissa') . ": </td>
								<td>" . $date->format('H.i\\u') . "</td>
							</tr>" .
						(!empty($note) && !empty($memo) ?
							"<tr>
								<td>" . __('Note', 'promissa') . ": </td>
								<td>" . Trim($memo) . "</td>
							</tr>" : "") .
						"</table>",

					'start' => jsDateTime($start),
					'end' => jsEndDateTime($start, $duration),
					'backgroundColor' => HexBackgroundColor($color),
					'borderColor' => HexBorderColor($color),
					'textColor' => HexTextColor($color),
					'editable' => false,
					"url" => ($webshop && $date->getTimestamp() > time() + (24 * 60 * 60) && $orderIntention_YN == 1 ? (FeastDate_YN($date) ? $feast_product_url : $intention_product_url) . '?church_ID=' . $church_ID . '&masses_ID=' . $ID . '&date=' . $date->format('Y-m-d') : ''),
				);

				$startDate = new DateTime($start);
				$endDate = new DateTime($end);

				$mass_is_full = ($subscribe_YN == 0);
				$almost_full = false;
				if($subscribers !== 0 && $maxSubscribers !== NULL) :
					$almost_full = (((($subscribers ?: 1) / $maxSubscribers) * 100) > 85);
				endif;

				$datum = $dateFormatter->format($startDate) . ' ' .  __('at', 'promissa') . ' ' . $startDate->format('H') . '.' . $startDate->format('i') . ' ' . __('hour', 'promissa');
				$description = Trim('Op ' . $datum . ' wordt de ' . strtolower($massType) . ' gevierd vanuit de ' . $church . ' in ' . $city . '. ' . Trim($memo ?? ''));

				$lLdJsonEvents[] = '{
				"@context": "http://schema.org/",
				"@type": "Event",
				"name": "' . (!empty($note) ? $note : $massType) . '",
				"image": "' . $image . '",
				"startdate": "' . $startDate->format($dateformat) . '",
				"enddate": "' . $endDate->format($dateformat) . '",
				"eventAttendanceMode": "https://schema.org/' . ($youtube_id == NULL ?  'OfflineEventAttendanceMode' : ($maxSubscribers == 0 ? 'OnlineEventAttendanceMode' : 'MixedEventAttendanceMode')) . '",
				"eventStatus": "https://schema.org/EventScheduled",
				"location": ' . ($youtube_id != NULL ? '[' : '') . '{
					"@type": "Place",
					"name": "' . $church . '",
					"address": {
						"@type": "PostalAddress",
						"addressCountry": "NL",
						"addresslocality": "' . $city . '",
						"postalcode": "' . $zipcode . '",
						"streetaddress": "' . $address . '"
					}
    			}' . ($youtube_id != NULL ? ',{
					"@type": "VirtualLocation",
					"url": "' . ((substr($youtube_id, 0, 8) === 'https://' || substr($youtube_id, 0, 7) === 'http://') ? $youtube_id : 'https://www.youtube.com/watch?v=' . $youtube_id) . '"
				}]' : '') . ',
				"organizer": {
					"@type": "Organization",
					"name": "' . get_bloginfo('name') . '",
					"url": "' . get_bloginfo('wpurl') . '"
				},
				"performer": [{
					"@type": "Organization",
					"name": "' . get_bloginfo('name') . '",
					"sameAs": "' . get_bloginfo('wpurl') . '	"
				}],
				"offers": {
					"@type": "Offer",
					"url": "' . get_permalink($page_id) . '?ID=' . $ID . '",
					"price": "0",
					"priceCurrency": "EUR",
					"availability": "https://schema.org/' . ($mass_is_full ? 'SoldOut' : ($almost_full ? 'LimitedAvailability' : 'InStock')) . '",
					"validFrom": "' . (new DateTime($insertdate))->format($dateformat) . '",
					"validThrough": "' . $startDate->format($dateformat) . '"
				},
				"description": "' . $description . '"
			}';
			endforeach;
			echo '<script type="application/ld+json">[';
			//remove redundant (white-space) characters
			$replace = array(
				//remove tabs before and after HTML tags
				'/\>[^\S ]+/s'   => '>',
				'/[^\S ]+\</s'   => '<',
				//shorten multiple whitespace sequences; keep new-line characters because they matter in JS!!!
				'/([\t ])+/s'  => ' ',
				//remove leading and trailing spaces
				'/^([\t ])+/m' => '',
				'/([\t ])+$/m' => '',
				// remove JS line comments (simple only); do NOT remove lines containing URL (e.g. 'src="http://server.com/"')!!!
				'~//[a-zA-Z0-9 ]+$~m' => '',
				//remove empty lines (sequence of line-end and white-space characters)
				'/[\r\n]+([\t ]?[\r\n]+)+/s'  => "\n",
				//remove empty lines (between HTML tags); cannot remove just any line-end characters because in inline JS they can matter!
				'/\>[\r\n\t ]+\</s'    => '><',
				//remove "empty" lines containing only JS's block end character; join with next line (e.g. "}\n}\n</script>" --> "}}</script>"
				'/}[\r\n\t ]+/s'  => '}',
				'/}[\r\n\t ]+,[\r\n\t ]+/s'  => '},',
				//remove new-line after JS's function or condition start; join with next line
				'/\)[\r\n\t ]?{[\r\n\t ]+/s'  => '){',
				'/,[\r\n\t ]?{[\r\n\t ]+/s'  => ',{',
				//remove new-line after JS's line end (only most obvious and safe cases)
				'/\),[\r\n\t ]+/s'  => '),',
				//remove quotes from HTML attributes that does not contain spaces; keep quotes around URLs!
				'~([\r\n\t ])?([a-zA-Z0-9]+)="([a-zA-Z0-9_/\\-]+)"([\r\n\t ])?~s' => '$1$2=$3$4', //$1 and $4 insert first white-space character found before/after attribute
				"/\r|\n/" => ""
			);

			echo preg_replace(array_keys($replace), array_values($replace), implode(',', $lLdJsonEvents));
			echo ']</script>';
			$output = json_encode($ProMissaEvents);

			return '<div id="calendar"></div>';
		endif;
		if (!empty($output)) :
			return $output;
		endif;
	}
endif;

function footer_script()
{
	global $ProMissaEvents; ?>
	<script>
		(function($) {
			document.addEventListener('DOMContentLoaded', function() {
				if (typeof FullCalendar != "undefined" && FullCalendar !== undefined) {
					var calendarEl = document.getElementById('calendar');
					var calendar = new FullCalendar.Calendar(calendarEl, {
						plugins: ['dayGrid', 'timeGrid'],
						header: {
							left: 'prev,next today',
							center: 'title',
							right: 'dayGridMonth,timeGridWeek,timeGridDay'
						},
						navLinks: true,
						editable: false,
						selectable: false,
						locale: '<?= substr(get_bloginfo("language"), 0, 2) ?>',
						events: <?= json_encode($ProMissaEvents); ?>,
						eventRender: function(info) {
							var tooltip = new Tooltip(info.el, {
								title: info.event.extendedProps.tooltip,
								placement: 'top',
								trigger: 'hover',
								container: 'body',
								html: true
							})
						}
					});
					calendar.render()
				}
			})
		}(jQuery));
	</script>
<?php }

add_action('wp_footer', 'footer_script');
add_action(
	'wp_enqueue_scripts',
	'wpshout_enqueue_styles'
);
function wpshout_enqueue_styles()
{
	$file_url = plugins_url(
		'css/popper.css', // File name
		__FILE__ // Magic PHP constant that means the "current file"
	);
	// Actually load up the stylesheet
	wp_enqueue_style(
		'popper', // A "name" for our file
		$file_url // Location variable
	);
}

add_shortcode('promissa-calendar', 'promissa_calendar_handler');
?>