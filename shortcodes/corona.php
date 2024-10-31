<?php
date_default_timezone_set(get_option('timezone_string'));
define('PM_DEBUG', false);
ini_set('intl.default_locale', 'nl-NL');

if(!function_exists('promissa_corona_handler')) :
	function promissa_corona_handler( $atts, $content = null )
	{
	    $atts_extended = shortcode_atts( array(
	        'church_id' => '',
	        'title' => '',
	        'subtitle' => '',
			'show_title' => true,
			'show_full' => true,
			'page_id' => NULL,
	        'limit' => 10,
			'limit_hour' => 0,
			'start_hour' => 0
		), $atts );


		$dateFormatter = \IntlDateFormatter::create(
		  Locale::getDefault(),
		  IntlDateFormatter::NONE,
		  IntlDateFormatter::NONE,
		  date_default_timezone_get(),
		  IntlDateFormatter::GREGORIAN,
		  'cccc d MMMM Y'
		);

		extract($atts_extended);
		$time = time();

		if($show_full === 'true') :
			$show_full = true;
		elseif($show_full === 'false') :
			$show_full = false;
		endif;

		$time += ($start_hour * 60 * 60);

		$single_church = (isset($church_id) && !empty($church_id));
		$masses = NULL;
		$offset = '';
		if(isset($page_id) && $page_id != NULL && !empty($page_id) && is_numeric($page_id) && (int)$page_id > 0) :
			$offset = '&page=' . (string)$page_id . '%2C' . (string)$limit;
		endif;
		$filter = '';
		if($single_church) :
			if(empty($filter)) :
				$filter = '&filter=';
			endif;
			$filter .= 'church_ID,eq,' . html_entity_decode($church_id);
			if(strpos($church_id, '&') !== -1) :
				$church_id = explode('&', $church_id)[0];
			endif;
		else :
		if($start_hour > 0) :
			if(empty($filter)) :
				$filter = '&filter=';
			else :
				$filter = '&filter2=';
			endif;
			$filter .= 'start_timestamp,gt,' . $time;
			$filter .= '&filter3=start_timestamp,lt,' . ($time + (7 * 24 * 60 * 60));
		endif;

		endif;

		$masses = ProMissaREST('ScheduleAttendees', '?size=' . $limit . $filter);
		$output = '';

		if(!empty($title)) :
			$output .= sprintf('<div class="av-special-heading av-special-heading-h3 blockquote elegant-quote elegant-centered avia-builder-el-5 el_after_av_blog el_before_av_textblock "><h3 class="av-special-heading-tag " itemprop="headline"><span class="heading-wrap">%s</span></h3></div>', $title);
			if(!empty($subtitle)) :
				$output .= sprintf('<header class="entry-content-header"><blockquote>%s</blockquote></header>', $subtitle);
			endif;
		elseif(!empty($subtitle)) :
			$output .= sprintf('<header class="entry-content-header"><h2 class="post-title entry-title" itemprop="headline">%s</h2></header>', $subtitle);
		endif;
		$dateformat = 'c';

		if(strstr(strtolower($_SERVER['HTTP_USER_AGENT']), "googlebot") || is_user_logged_in()) :
			$mod = get_post_modified_time();
			$time = strtotime($masses[0]["end"]) - 86400;

			if($time > $mod && $time < time()) :
				global $wpdb;

				$mysql_time_format= "Y-m-d H:i:s";
				$post_modified = gmdate( $mysql_time_format, $time );
				$post_modified_gmt = gmdate( $mysql_time_format, ( $time + get_option( 'gmt_offset' ) * HOUR_IN_SECONDS )  );
				$post_id = 2;
				$wpdb->query("UPDATE $wpdb->posts SET post_modified = '{$post_modified}', post_modified_gmt = '{$post_modified_gmt}'  WHERE ID = {$post_id}" );
			endif;
		endif;

		$show_count = 0;
		$cnt = count($masses);
		if($cnt > 0) :
			$i = 0;
			foreach($masses as $mass) :
				$i++;
				extract($mass);
				if($single_church && $church_ID != $church_id) :
					continue;
				endif;
				$livestream = false;
				$mass_is_full = false;
				$almost_full = false;
				$show = true;
				$mass_is_full = ($subscribe_YN == 0);


				if($mass_is_full) :
					$show = $show_full;
				endif;
				if($mass_is_full && $subscribers == 0) :
					$show = false;
				endif;


				if ($start == '2021-12-24 21:00:00') :
					$show = true;
					$mass_is_full = true;
					$livestream = $youtube_id != null;
				endif;

				if($show) :
					$show_count++;
					$percentage_full = (($subscribers / $maxSubscribers) * 100);
					$almost_full = ($percentage_full > 85);
					if($percentage_full > 97) :
						$mass_is_full = true;
					endif;

					$output .= '<div class="event-wrapper" itemscope itemtype="http://schema.org/Event" style="width:100%;display:inline-block;margin-top:.666em;">';
					$day = '';

					$startDate = new DateTime($start);
					$endDate = new DateTime($end);

					$datum = $dateFormatter->format($startDate) . ' ' .  __('at', 'promissa') . ' ' . $startDate->format('H') . '.' . $startDate->format('i') . ' ' . __('hour', 'promissa');


					$output .= '<div class="event-info" style="float:left;">';
					$output .= '	<div class="event-date" itemprop="startDate" content="' . $startDate->format($dateformat) . '" style="display:inline;"><strong>' . ucfirst($datum) . '</strong>&nbsp;</div>';
					if($mass["web"] != NULL) :
						$output .= '<strong style="display:inline;"><em>(<span itemprop="name">' . $mass["web"] . '</span>)</em></strong>';
					else :
						$output .= '<meta itemprop="name" content="' . $massType . ' ' . substr($dateFormatter->format($startDate), 0, -5). '">';
					endif;
					$output .= '	<meta itemprop="endDate" content="' . $endDate->format($dateformat) . '">';
					$output .= '	<meta itemprop="eventStatus" content="https://schema.org/EventScheduled">';

					$output .= '	<meta itemprop="eventAttendanceMode" content="https://schema.org/' . ($youtube_id == NULL ?  'OfflineEventAttendanceMode' : ($maxSubscribers == 0 ? 'OnlineEventAttendanceMode' :'MixedEventAttendanceMode')) . '">';
					if($youtube_id != NULL) :
					 	$output .= '<div class="event-venue" itemprop="location" itemscope itemtype="http://schema.org/VirtualLocation">';
						$output .= '	<meta itemprop="url" content="' . ((substr($youtube_id, 0, 8) === 'https://' || substr($youtube_id, 0, 7) === 'http://') ? $youtube_id : 'https://www.youtube.com/watch?v=' . $youtube_id) . '">';
						$output .= '</div>';
					endif;
					$output .= '	<meta itemprop="description" content="Op ' . $datum . ' wordt de ' . strtolower($massType) . ' gevierd vanuit de ' . $church . ' in ' . $city . '. ' . str_replace(PHP_EOL, ' ', $memo) . '">';

					$output .= '	<div class="event-venue" itemprop="organizer" itemscope itemtype="http://schema.org/Organization">';
					$output .= '		<meta itemprop="name"  content="' . get_bloginfo('name') . '">';
					$output .= '		<meta itemprop="url" content="' . get_bloginfo('wpurl') . '">';
					$output .= '	</div>';
					$output .= '	<div class="event-venue" itemprop="performer" itemscope itemtype="http://schema.org/Organization">';
					$output .= '		<meta itemprop="name" content="' . get_bloginfo('name') . '">';
					$output .= '	</div>';

					$output .= '	<div class="event-venue" itemprop="location" itemscope itemtype="http://schema.org/Place">';
					$output .= '		<span itemprop="name">' . ($short== 'Munsterkerk' ? $church : $church) . '</span>';
					$output .= '		<div itemprop="address" itemscope itemtype="https://schema.org/PostalAddress">';
					$output .= '	    <meta itemprop="streetAddress" content="' . $address . '">';
					$output .= '	    <meta itemprop="addressLocality" content="' . $city . '">';
					$output .= '		<meta itemprop="postalCode" content="' . $zipcode . '">';
					$output .= '	    <meta itemprop="addressRegion" content="Limburg">';
					$output .= '		<meta itemprop="addressCountry" content="NL">';
					$output .= '	</div>';
					if(isset($latitude) && $latitude != null && $latitude > 0 && isset($longitude) && $longitude != null && $longitude > 0) :
						$output .= '	<div itemprop="geo" itemscope itemtype="https://schema.org/GeoCoordinates">';
						$output .= '		<meta itemprop="latitude" content="' . str_replace(',', '.', $latitude) . '" />';
						$output .= '		<meta itemprop="longitude" content="' . str_replace(',', '.', $longitude) . '" />';
						$output .= '	</div>';
					endif;
					//endif;

					$output .= '	</div>';
					$output .= '</div>';
					$output .= '<div itemprop="offers" itemscope itemtype="http://schema.org/Offer" style="float:right;margin: .25em 0;">';
					$output .= '	<meta itemprop="price" content="0.00">';
					$output .= '	<meta itemprop="priceCurrency" content="EUR">';
					$output .= '	<meta itemprop="validFrom" content="' . (new DateTime($insertdate))->format($dateformat) . '">';
					$output .= '	<meta itemprop="validThrough" content="' . $startDate->format($dateformat) . '">';
					if($mass_is_full && $livestream) :
						if($youtube_id != null) :
							if(substr($youtube_id, 0, 8) === 'https://' || substr($youtube_id, 0, 7) === 'http://') :
								$output .= '<a href="' . $youtube_id . '" target="_blank" class="avia-button avia-icon_select-no avia-color-theme-color avia-size-small" style="background-color:#841a18;line-height:2em;">&nbsp;Bekijk de livestream&nbsp;&nbsp;<span style="font-family: \'entypo-fontello\';color:#ffffff;font-size:1.5em;"></span>&nbsp;</a>';

							else :
							$output .= '<a href="https://www.youtube.com/watch?v=' . $youtube_id . '" target="_blank" class="avia-button avia-icon_select-no avia-color-theme-color avia-size-small" style="background-color:#841a18;line-height:2em;">&nbsp;Bekijk op YouTube&nbsp;&nbsp;<span style="font-family: \'entypo-fontello\';color:#ffffff;font-size:1.5em;"></span>&nbsp;</a>';
							endif;
						endif;
					else :
					$output .= '	<a id="' . $ID . '" href="' . ($mass_is_full ? 'javascript:alert(\'Het is niet mogelijk om voor deze mis aan te melden!\')' : get_permalink($page_id) .'?ID=' . $ID . '&iframe=true&width=80%&height=80%') . '" class="avia-button avia-icon_select-no ' . (!$mass_is_full ? ($almost_full ? 'avia-color-orange ' : 'avia-color-theme-color ') : ' ') . 'avia-size-small">' . ($mass_is_full ? 'Volgeboekt' :

						($almost_full ? 'Bijna vol' : 'Aanmelden')) . '</a>';
					endif;
					$output .= '	<meta itemprop="url" content="' . get_permalink($page_id) .'?ID=' . $ID  . '">';
					$output .= '<link itemprop="availability" href="https://schema.org/' . ($mass_is_full ? 'SoldOut' : ($almost_full ? 'LimitedAvailability' : 'InStock')) . '" /></div>';
					$output .= '<div style="clear:left;">' . nl2br($memo) . '</div>';
					if(empty($image)) :
						if (function_exists('z_taxonomy_image_url')) :
							if(empty($image)) :
								foreach(get_categories() as $category)
								{
									if(empty($image)) :
										$image = z_taxonomy_image_url($category->term_id, 'large');
									endif;
								}
							endif;
						endif;
						if(empty($image)) :
							if(function_exists('avia_get_option')) :
								$image = avia_get_option('logo');
							endif;
						endif;
					endif;
					if(!empty($image)) :
						$output .= '	<meta itemprop="image" content="' . $image. '">';
					endif;
					$output .= '<script> if (typeof(Storage) !== "undefined") { if(localStorage.getItem("' . $ID . '") != null) {  jQuery("a#' . $ID . '").css({"background-color" : "#4BB543", "border-color": "#26911f"}).addClass("avia-color-theme-color").text("Reeds aangemeld"); }  }</script>';
					$output .= '</div>';
					if($cnt > $i) :
						$output .= '<hr style="margin:0;" />';
					endif;
				endif;
			endforeach;
		endif;
		if(!empty($output) && $show_count > 0) :
			return $output;
		endif;
	}
endif;
add_shortcode( 'promissa-corona', 'promissa_corona_handler' );
?>