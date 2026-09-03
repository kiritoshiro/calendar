<?php
/** no direct access **/
defined('MECEXEC') or die();

do_action('mec_start_skin', $this->id);
do_action('mecgeneral_calendar_skin_head');

// Month picker Assets
$this->main->load_month_picker_assets();

// Shortcode Options
$local_time = (isset($this->skin_options['include_local_time']) and !empty( $this->skin_options['include_local_time'] )) ? $this->skin_options['include_local_time'] : false;
$display_label = (isset($this->skin_options['display_label']) and !empty( $this->skin_options['display_label'] )) ? $this->skin_options['display_label'] : false;
$reason_for_cancellation = (isset($this->skin_options['reason_for_cancellation']) and !empty( $this->skin_options['reason_for_cancellation'] )) ? $this->skin_options['reason_for_cancellation'] : false;
$more_event = (isset($this->skin_options['more_event']) and !empty( $this->skin_options['more_event'] )) ? (int) $this->skin_options['more_event'] : 10;
$image_size = $this->skin_options['image_size'] ?? 'default';
$calendar_id = isset($this->atts['id']) ? (int) $this->atts['id'] : 0;

$sed_method = '';
if(isset($this->skin_options['sed_method']) and !empty($this->skin_options['sed_method'])) $sed_method = ($this->skin_options['sed_method']  == 'new') ? '_blank' : ($this->skin_options['sed_method']  == '0' ? '_self' : $this->skin_options['sed_method']);

// Shortcode Filters
$filter_category = $calendar_id ? get_post_meta($calendar_id, 'category', true) : '';
$filter_ex_category = $calendar_id ? get_post_meta($calendar_id, 'ex_category', true) : '';
$filter_location = $calendar_id ? get_post_meta($calendar_id, 'location', true) : '';
$filter_ex_location = $calendar_id ? get_post_meta($calendar_id, 'ex_location', true) : '';
$filter_organizer = $calendar_id ? get_post_meta($calendar_id, 'organizer', true) : '';
$filter_ex_organizer = $calendar_id ? get_post_meta($calendar_id, 'ex_organizer', true) : '';
$filter_label = $calendar_id ? get_post_meta($calendar_id, 'label', true) : '';
$filter_ex_label = $calendar_id ? get_post_meta($calendar_id, 'ex_label', true) : '';
$filter_tag = $calendar_id ? get_post_meta($calendar_id, 'tag', true) : '';
$filter_ex_tag = $calendar_id ? get_post_meta($calendar_id, 'ex_tag', true) : '';
$filter_author = $calendar_id ? get_post_meta($calendar_id, 'author', true) : '';
$filter_ex_author = $calendar_id ? get_post_meta($calendar_id, 'ex_author', true) : '';
$show_past_events = ($this->atts['show_past_events'] ?? '0');
$show_only_past_events = ($this->atts['show_only_past_events'] ?? '0');
$show_only_one_occurrence = (isset($this->atts['show_only_one_occurrence']) && $this->atts['show_only_one_occurrence'] != '0')  ?  '1' : '0';
$mec_tax_input = (isset($this->atts['mec_tax_input']) && $this->atts['mec_tax_input'] != '0') ? $this->atts['mec_tax_input'] : '';

// WordPress Options
$lang = !empty(substr(get_locale(), 0, strpos(get_locale(), "_"))) ? substr(get_locale(), 0, strpos(get_locale(), "_")) : get_locale();
$mec_lang = $lang;
if (class_exists('SitePress')) $mec_lang = apply_filters('wpml_current_language', null) ?: $mec_lang;
else if (function_exists('pll_current_language')) $mec_lang = pll_current_language() ?: $mec_lang;
$direction = is_rtl() ? 'rtl' : 'ltr';
$border_direction = is_rtl() ? 'border-right-width' : 'border-left-width';
$border_direction_style = is_rtl() ? 'border-left-style' : 'border-right-style';
$is_category_page = is_tax('mec_category');
$cat_id = '';
if($is_category_page)
{
	$category = get_queried_object();
	$cat_id = $category->term_id;
}

$week_start_day = (int) get_option('start_of_week');
if(!function_exists('mec_general_calendar_find_event'))
{
	// Search Options
	function mec_general_calendar_find_event($sf_options, $find_filter)
    {
		if($find_filter === 'find' and is_array($sf_options))
		{
			if(
                (isset($sf_options['category']['type']) && $sf_options['category']['type'] != '0') ||
                (isset($sf_options['location']['type']) && $sf_options['location']['type'] != '0') ||
                (isset($sf_options['organizer']['type']) && $sf_options['organizer']['type'] != '0') ||
                (isset($sf_options['speaker']['type']) && $sf_options['speaker']['type'] != '0') ||
                (isset($sf_options['tag']['type']) && $sf_options['tag']['type'] != '0') ||
                (isset($sf_options['label']['type']) && $sf_options['label']['type'] != '0') ||
                (isset($sf_options['event_cost']['type']) && $sf_options['event_cost']['type'] != '0') ||
                (isset($sf_options['text_search']['type']) && $sf_options['text_search']['type'] != '0') ||
                (isset($sf_options['address_search']['type']) && $sf_options['address_search']['type'] != '0')
            ) return true;
			else return false;
		}

		if($find_filter === 'filter' and is_array($sf_options))
		{
			if(
                (isset($sf_options['category']['type']) && $sf_options['category']['type'] != '0') ||
                (isset($sf_options['location']['type']) && $sf_options['location']['type'] != '0') ||
                (isset($sf_options['organizer']['type']) && $sf_options['organizer']['type'] != '0') ||
                (isset($sf_options['speaker']['type']) && $sf_options['speaker']['type'] != '0') ||
                (isset($sf_options['tag']['type']) && $sf_options['tag']['type'] != '0') ||
                (isset($sf_options['label']['type']) && $sf_options['label']['type'] != '0') ||
                (isset($sf_options['event_cost']['type']) && $sf_options['event_cost']['type'] != '0') ||
                (isset($sf_options['address_search']['type']) && $sf_options['address_search']['type'] != '0')
            ) return true;
			else return false;
		}

		return false;
	}
}
wp_enqueue_script('mec-niceselect-script');
?>
<div class="mec-gCalendar" id="mec_skin_<?php echo esc_attr($this->id); ?>">
	<div id='gCalendar-loading' class="mec-modal-result" style="display: none"></div>
	<?php echo MEC_kses::full($this->gcalbar_render_bar()); ?>
	<div id="mec-gCalendar-wrap"></div>
	<div class="mec-gCalendar-filters">
		<div class="mec-gCalendar-filters-wrap">
			<?php
			echo ((is_array($this->sf_options) && isset($this->sf_options['category']) && $this->sf_options['category']['type'] != '0') ? MEC_kses::form($this->sf_search_field('category', array('type' => $this->sf_options['category']['type']))) : '');
			echo ((is_array($this->sf_options) && isset($this->sf_options['location']) && $this->sf_options['location']['type'] != '0') ? MEC_kses::form($this->sf_search_field('location', array('type' => $this->sf_options['location']['type']))) : '');
			echo ((is_array($this->sf_options) && isset($this->sf_options['organizer']) && $this->sf_options['organizer']['type'] != '0') ? MEC_kses::form($this->sf_search_field('organizer', array('type' => $this->sf_options['organizer']['type']))) : '');
			echo ((is_array($this->sf_options) && isset($this->sf_options['speaker']) && $this->sf_options['speaker']['type'] != '0') ? MEC_kses::form($this->sf_search_field('speaker', array('type' => $this->sf_options['speaker']['type']))) : '');
			echo ((is_array($this->sf_options) && isset($this->sf_options['tag']) && $this->sf_options['tag']['type'] != '0') ? MEC_kses::form($this->sf_search_field('tag', array('type' => $this->sf_options['tag']['type']))) : '');
			echo ((is_array($this->sf_options) && isset($this->sf_options['label']) && $this->sf_options['label']['type'] != '0') ? MEC_kses::form($this->sf_search_field('label', array('type' => $this->sf_options['label']['type']))) : '');
			echo ((is_array($this->sf_options) && isset($this->sf_options['address_search']) && $this->sf_options['address_search']['type'] != '0') ? MEC_kses::form($this->sf_search_field('address_search', array('type' => $this->sf_options['address_search']['type']))) : '');
			echo ((is_array($this->sf_options) && isset($this->sf_options['event_cost']) && $this->sf_options['event_cost']['type'] != '0') ? MEC_kses::form($this->sf_search_field('event_cost', array('type' => $this->sf_options['event_cost']['type']))) : '');
			echo ($this->sf_reset_button ? '<div class="mec-search-reset-button"><button class="button mec-button" id="mec_search_form_'.esc_attr($this->id).'_reset" type="button">'.esc_html__('Reset', 'modern-events-calendar-lite').'</button></div>' : '');
			?>
		</div>
	</div>
</div>
<?php echo $this->display_credit_url(); ?>
<style>.nice-select{color: #838383;-webkit-tap-highlight-color:transparent;background-color:#fff;border:solid 1px #E3E4E5;box-sizing:border-box;clear:both;cursor:pointer;display:block;float:left;font-family:inherit;font-size:12px;font-weight:400;height:42px;line-height:40px;outline:0;padding-left:18px;padding-right:30px;position:relative;text-align:left!important;-webkit-transition:all .2s ease-in-out;transition:all .2s ease-in-out;-webkit-user-select:none;-moz-user-select:none;-ms-user-select:none;user-select:none;white-space:nowrap;width: 100%;border-radius: 0 3px 3px 0;height: 40px;}.nice-select:hover{border-color:#dbdbdb}.nice-select:after{border-bottom: 1px solid #c1c2c3; border-right: 1px solid #c1c2c3; width: 8px; height: 8px; margin-top: -5px; right: 15px;content:'';display:block;pointer-events:none;position:absolute;top:50%;-webkit-transform-origin:66% 66%;-ms-transform-origin:66% 66%;transform-origin:66% 66%;-webkit-transform:rotate(45deg);-ms-transform:rotate(45deg);transform:rotate(45deg);-webkit-transition:all .15s ease-in-out;transition:all .15s ease-in-out}.nice-select.open:after{-webkit-transform:rotate(-135deg);-ms-transform:rotate(-135deg);transform:rotate(-135deg)}.nice-select.open .list{opacity:1;pointer-events:auto;-webkit-transform:scale(1) translateY(0);-ms-transform:scale(1) translateY(0);transform:scale(1) translateY(0)}.nice-select.disabled{border-color:#ededed;color:#999;pointer-events:none}.nice-select.disabled:after{border-color:#ccc}.nice-select.wide{width:100%}.nice-select.wide .list{left:0!important;right:0!important}.nice-select.right{float:right}.nice-select.right .list{left:auto;right:0}.nice-select.small{font-size:12px;height:36px;line-height:34px}.nice-select.small:after{height:4px;width:4px}.nice-select.small .option{line-height:34px;min-height:34px}.nice-select .list{width: 100%;background-color:#fff;border-radius:0 0 3px 3px;box-shadow:0 0 0 1px rgba(68,68,68,.11);box-sizing:border-box;margin-top:4px;opacity:0;overflow:hidden;padding:0;pointer-events:none;position:absolute;top:100%;left:0;-webkit-transform-origin:50% 0;-ms-transform-origin:50% 0;transform-origin:50% 0;-webkit-transform:scale(.75) translateY(-21px);-ms-transform:scale(.75) translateY(-21px);transform:scale(.75) translateY(-21px);-webkit-transition:all .2s cubic-bezier(.5,0,0,1.25),opacity .15s ease-out;transition:all .2s cubic-bezier(.5,0,0,1.25),opacity .15s ease-out;z-index:9}.nice-select .list:hover .option:not(:hover){background-color:transparent!important}.nice-select .option{ cursor: pointer; font-weight: 400;line-height: 1.2;list-style: none;min-height: 30px;outline: 0;padding: 10px 6px 10px 18px;text-align: left;-webkit-transition: all .2s;transition: all .2s;font-size: 14px;letter-spacing: -0.1px;white-space: break-spaces;}.nice-select .option.focus,.nice-select .option.selected.focus,.nice-select .option:hover{background-color:#f6f6f6}.nice-select .option.selected{font-weight:700}.nice-select .option.disabled{background-color:transparent;color:#999;cursor:default}.no-csspointerevents .nice-select .list{display:none}.no-csspointerevents .nice-select.open .list{display:block}</style>
<?php
$javascript = '<script>
    document.addEventListener("DOMContentLoaded", function () {
			if ("'. esc_js($lang) .'" === "is" && typeof FullCalendar !== "undefined" && FullCalendar.globalLocales && !FullCalendar.globalLocales.some(function(locale) { return locale.code === "is"; })) {
				FullCalendar.globalLocales.push({
					code: "is",
					week: { dow: 1, doy: 4 },
					buttonText: {
						prev: "Fyrri",
						next: "N\u00e6sti",
						today: "\u00cd dag",
						year: "\u00c1r",
						month: "M\u00e1nu\u00f0ur",
						week: "Vika",
						day: "Dagur",
						list: "Dagskr\u00e1"
					},
					weekText: "Vika",
					allDayText: "Allan daginn",
					moreLinkText: "meira",
					noEventsText: "Engir vi\u00f0bur\u00f0ir til a\u00f0 s\u00fdna",
					dayNames: ["Sunnudagur", "M\u00e1nudagur", "\u00deri\u00f0judagur", "Mi\u00f0vikudagur", "Fimmtudagur", "F\u00f6studagur", "Laugardagur"],
					dayNamesShort: ["Sun", "M\u00e1n", "\u00deri", "Mi\u00f0", "Fim", "F\u00f6s", "Lau"],
					monthNames: ["Jan\u00faar", "Febr\u00faar", "Mars", "Apr\u00edl", "Ma\u00ed", "J\u00fan\u00ed", "J\u00fal\u00ed", "\u00c1g\u00fast", "September", "Okt\u00f3ber", "N\u00f3vember", "Desember"],
					monthNamesShort: ["Jan", "Feb", "Mar", "Apr", "Ma\u00ed", "J\u00fan", "J\u00fal", "\u00c1g\u00fa", "Sep", "Okt", "N\u00f3v", "Des"]
				});
			}
		var calendarEl = document.getElementById("mec-gCalendar-wrap");
		var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: "dayGridMonth",
			initialDate: "'.  esc_js($this->get_start_date()[0].'-'.$this->get_start_date()[1].'-'.$this->get_start_date()[2]) .'",
			editable: false,
			selectable: false,
			businessHours: false,
			height: "auto",
			direction: "'. $direction .'",
			locale: "'. esc_js($lang) .'",
	';
if (mec_general_calendar_find_event($this->sf_options, 'find')) :
$javascript .='
			customButtons: {
				findEvents: {
					text: "'. esc_html__('Find Events', 'modern-events-calendar-lite') .'",
					click: function() {
						jQuery(".mec-gCalendar-filters").css("display" , "none")
						var eventSource = [];
						eventSource = calendar.getEventSources();
						jQuery.each(eventSource, function (key, value) {
							value.remove();
						});
						calendar.addEventSource({
							url: "'. get_rest_url() .'mec/v1/events",
							method: "GET",
							startParam: "startParam",
							endParam: "endParam",
							textColor: "#000",
							ajax: true,
							extraParams: {
								show_past_events: "'. esc_js($show_past_events).'",
								show_only_past_events: "'. esc_js($show_only_past_events).'",
								show_only_one_occurrence: "'. esc_js($show_only_one_occurrence).'",
								categories: (jQuery("select[id^=\"mec_sf_category\"]").length > 0) ? jQuery("select[id^=\"mec_sf_category\"]").val() : "",
								multiCategories: (jQuery(".select2-hidden-accessible").length > 0) ? JSON.stringify(jQuery(".select2-hidden-accessible").val()) : "",
								location: jQuery("select[id^=\"mec_sf_location\"]").val(),
								organizer: jQuery("select[id^=\"mec_sf_organizer\"]").val(),
								speaker: jQuery("select[id^=\"mec_sf_speaker\"]").val(),
								tag: jQuery("select[id^=\"mec_sf_tag\"]").val(),
								label: jQuery("select[id^=\"mec_sf_label\"]").val(),
								cost_min: jQuery("input[id^=\"mec_sf_event_cost_min\"]").val(),
								cost_max: jQuery("input[id^=\"mec_sf_event_cost_max\"]").val(),
								display_label: "'. esc_js($display_label) .'",
								reason_for_cancellation: "'. esc_js($reason_for_cancellation) .'",
								is_category_page: "'. esc_js($is_category_page) .'",
								cat_id: "'. esc_js($cat_id) .'",
								local_time: "'. esc_js($local_time) .'",
								image_size: "'. esc_js($image_size) .'",
								filter_category: "'. esc_js($filter_category) .'",
								filter_ex_category: "'. esc_js($filter_ex_category) .'",
								filter_location: "'. esc_js($filter_location) .'",
								filter_ex_location: "'. esc_js($filter_ex_location) .'",
								filter_organizer: "'. esc_js($filter_organizer) .'",
								filter_ex_organizer: "'. esc_js($filter_ex_organizer) .'",
								filter_label: "'. esc_js($filter_label) .'",
								filter_ex_label: "'. esc_js($filter_ex_label) .'",
								filter_tag: "'. esc_js($filter_tag) .'",
								filter_ex_tag: "'. esc_js($filter_ex_tag) .'",
								filter_author: "'. esc_js($filter_author) .'",
								filter_ex_author: "'. esc_js($filter_ex_author) .'",
								locale: "'. esc_js($lang) .'",
								lang: "'. esc_js($mec_lang) .'",
							},
						});
						calendar.refetchEvents();
					}
				},
			';
if (mec_general_calendar_find_event($this->sf_options, 'filter')) :
$javascript .='
				filterEvents: {
					text: "'. esc_html__('Filter', 'modern-events-calendar-lite') .'",
					click: function() {
						jQuery(".mec-gCalendar-filters").fadeToggle( "fast", "linear" );
					}
				}
				';
endif;
$javascript .='
			},
			';
endif;
$javascript .='
			datesSet: function(arg) {
			    mecGcalbarSync(arg.view.currentStart);
			    var mecGcalbarTitle = arg.view.title;
			    var mecGcalbarYear = arg.view.currentStart.getUTCFullYear();
			';
if ($lang === 'is') :
$javascript .='
			    var months = ["Jan\u00faar", "Febr\u00faar", "Mars", "Apr\u00edl", "Ma\u00ed", "J\u00fan\u00ed", "J\u00fal\u00ed", "\u00c1g\u00fast", "September", "Okt\u00f3ber", "N\u00f3vember", "Desember"];
			    // view.currentStart (the displayed month), not arg.start (the
			    // visible range, which begins in the previous month whenever
			    // the grid opens with trailing days) - and getUTC*, since
			    // these are FullCalendar markers. See mecGcalbarMonthStart().
			    mecGcalbarTitle = months[arg.view.currentStart.getUTCMonth()] + " " + arg.view.currentStart.getUTCFullYear();
';
endif;
$javascript .='
			    mecGcalbarSetTitle(mecGcalbarTitle, mecGcalbarYear);
			},
			';
if ($lang === 'is') :
$javascript .='
			dayHeaderContent: function(arg) {
			    return ["Sun", "M\u00e1n", "\u00deri", "Mi\u00f0", "Fim", "F\u00f6s", "Lau"][arg.date.getDay()];
			},
			';
endif;
$javascript .='
			firstDay: "'.esc_js($week_start_day).'",
			';
// adventistai.lt: the title and the prev/today/next/prevYear/nextYear
// buttons that used to fill this toolbar now live in the .mec-ymtabs
// panel above the calendar (gcalbar_render_bar()), so the only thing
// left for it to hold is the optional Filter/Find buttons. When those
// are off there is nothing to put in it, and an *empty* toolbar is not
// free: FullCalendar still renders the element (with an empty
// .fc-toolbar-chunk per section, so a CSS :empty rule can't catch it)
// and it keeps picking up padding/margin/border from MEC's own CSS and
// from the theme — which is exactly the stray empty bar that appeared
// between the tab panel and the grid. So don't render it at all.
if (mec_general_calendar_find_event($this->sf_options, 'find')):
$javascript .='
            headerToolbar: {
                left: "",
                center: "",
                right: "filterEvents,findEvents"
            },
			';
else :
$javascript .='
            headerToolbar: false,
			';
endif;
$javascript .='
			buttonText: {
                today: "'. esc_html__('Today', 'modern-events-calendar-lite') .'"
            },
			eventDidMount: function(info) {
				var searchField = jQuery(".mec-gCalendar-search-text");
				if (searchField.length > 0) {
					var searchTerms = jQuery(".mec-gCalendar-search-text").val();
					if (searchTerms.length > 0){
						if (info.event._def.title.toLowerCase().indexOf(searchTerms) >= 0 || info.event._def.extendedProps.description.toLowerCase().indexOf(searchTerms) >= 0) {
							info.event.setProp("display","block")
						} else {
							info.event.setProp("display","none")
						}
					} else {
						info.event.setProp("display","block")
					}
				} else {
					info.event.setProp("display","block")
				}
				var backgroundColor = info.backgroundColor == "#" ? "#00acf8" : info.backgroundColor;
				var borderColor = info.borderColor == "#" ? "#00acf8" : info.borderColor;
    			jQuery(info.el).css("padding", "8px 3px");
    			jQuery(info.el).css("font-size", "12px");
    			jQuery(info.el).css("font-weight", "400");
    			jQuery(info.el).css("border-radius", "0");
    			jQuery(info.el).css("border-top", "none");
    			jQuery(info.el).css("border-bottom", "none");
    			jQuery(info.el).css("'.$border_direction.'", "3px");
    			jQuery(info.el).css("'.$border_direction_style.'", "none");
    			jQuery(info.el).css("background-color", "#fff");
    			jQuery(info.el).css("border-color", borderColor);
    			jQuery(info.el).css("white-space", "normal");
    			jQuery(info.el).css("font-family", "-apple-system,BlinkMacSystemFont,\"Segoe UI\",Roboto,sans-serif");
    			// jQuery(info.el).css("z-index", "1");
    			jQuery(info.el).css("line-height", "1.2");
    			jQuery(info.el).css("margin-top", "0");
    			jQuery(info.el).attr("target", "'. esc_js($sed_method) .'");
				';
if ( $sed_method == 'no' ) :
$javascript .='
					jQuery(info.el).css({
					"cursor": "default",
        			"pointer-events": "none",
        			"text-decoration": "none",
				});
				';
endif;
$javascript .='
    			jQuery(info.el).attr("data-event-id", info.event._def.publicId);
    			jQuery(info.el).append("<span class=\"\" style=\"background-color:" +  backgroundColor + ";position: absolute;top: 0;right: 0;bottom: 0;left: -1px;z-index: 0;opacity: .25;\"></span>");
    			jQuery(info.el).append(info.event._def.extendedProps.reason_for_cancellation);
    			jQuery(info.el).append(info.event._def.extendedProps.locaTimeHtml);
    			jQuery(info.el).append(info.event._def.extendedProps.labels);
				';
if ( $sed_method == 'm1') :
$javascript .='
				jQuery(info.el).attr("rel", "noopener");
				jQuery("#mec_skin_'.esc_attr($this->id).'").mecGeneralCalendarView(
				{
					id: "'. esc_attr($this->id) .'",
					atts: "'. http_build_query(array('atts' => $this->atts), '', '&')  .'",
					ajax_url: "'. admin_url('admin-ajax.php', NULL)  .'",
					sed_method: "'. esc_js($sed_method) .'",
					image_popup: "'. esc_js($this->image_popup) .'",
					sf:
					{
						reset: "'. ($this->sf_reset_button ? 1 : 0) .'",
						refine: "'. ($this->sf_refine ? 1 : 0) .'",
					},
				});
				';
endif;
$title_and_location_pattern = apply_filters( 'mec_skin_general_calendar_title_and_location_structure', 'Title + Location' );
$javascript .='
				jQuery(".fc-daygrid-event-harness").mouseleave(function(e) {
					jQuery(".mec-gCalendar-tooltip").remove();
				});
			},
			eventMouseEnter: function(info) {
				var Image = info.event._def.extendedProps.image ? "<div class=\"mec-gCalendar-tooltip-image\">" + info.event._def.extendedProps.gridsquare + "</div>" : "";
				var dateText = info.event._def.extendedProps.startDateStr != info.event._def.extendedProps.endDateStr  ? "'.addslashes($this->icons->display('calendar')).'<div><span class=\"mec-gCalendar-tooltip-date-start\">" + info.event._def.extendedProps.start_date + "</span>" + "<span class=\"mec-gCalendar-tooltip-date-end\">" + info.event._def.extendedProps.end_date + "</span></div>" : "'.addslashes($this->icons->display('calendar')).'<div><span class=\"mec-gCalendar-tooltip-date-start\">" + info.event._def.extendedProps.start_date + "</span>" + "<span class=\"mec-gCalendar-tooltip-date-day\">" + info.event._def.extendedProps.startDay + "</span></div>";

				var dateTime = "'.addslashes($this->icons->display('clock')).'</i><div><span class=\"mec-gCalendar-tooltip-time-start\">" + info.event._def.extendedProps.start_time + "</span>" + "<span class=\"mec-gCalendar-tooltip-time-end\">" + info.event._def.extendedProps.end_time + "</span></div>";

				var Location = info.event._def.extendedProps.location ? "<div class=\"mec-gCalendar-tooltip-location\">'.addslashes($this->icons->display('location-pin')).'" + info.event._def.extendedProps.location + "</div>" : "";

				var Title = "<div class=\"mec-gCalendar-tooltip-title\"><a data-event-id=\"" + info.event._def.publicId + "\" target=\"'. esc_js($sed_method) .'\" href=\"" +  info.event._def.url + "\">" + info.event._def.title + "<span style=\"background:" + info.event._def.ui.backgroundColor + "\"></span></a></div>";

				var tooltip = "<div class=\"mec-gCalendar-tooltip\">" + Image +
				"<div class=\"mec-gCalendar-tooltip-date\">" +
					"<div class=\"mec-gCalendar-tooltip-date-text\">" + dateText + "</div>" +
					"<div class=\"mec-gCalendar-tooltip-date-time\">" + dateTime + "</div>" +
			    "</div>" + '. $title_and_location_pattern .' +
				"</div>";
				if ( jQuery(info.el).parent().find(".mec-gCalendar-tooltip").length < 1 ) jQuery(info.el).parent().append(tooltip);
				';
if ($sed_method == 'm1') :
$javascript .= '
				jQuery("#mec_skin_'.esc_attr($this->id).' .mec-gCalendar-tooltip-title a").off("click").on("click", function (e) {
					e.preventDefault();
					var sed_method = jQuery(this).attr("target");
					if ("_blank" === sed_method || "_self" === sed_method || "no" === sed_method) {

						return;
					}
					e.preventDefault();
					var href = jQuery(this).attr("href");

					var id = jQuery(this).data("event-id");
					var occurrence = get_parameter_by_name("occurrence", href);
					var time = get_parameter_by_name("time", href);

					if( "undefined" == typeof id ){
						return;
					}
					mecSingleEventDisplayer.getSinglePage(id, occurrence, time, "'. admin_url('admin-ajax.php', NULL)  .'", "'. esc_js($sed_method) .'", "'. esc_js($this->image_popup) .'");
				});
				';
endif;
$javascript .= '
			},
			dayMaxEvents: ' .esc_js($more_event) .',
			moreLinkContent: function(arg) {
			  return "+"+arg.num+" '.esc_js(__('more', 'modern-events-calendar-lite')).'";
			},
			timeZone:"' .get_option('gmt_offset') .'",
			events: {
				url: "'.get_rest_url() .'mec/v1/events",
				method: "GET",
				startParam: "startParam",
				endParam: "endParam",
  				textColor: "#000",
				ajax: true,
				extraParams: {
					show_past_events: "' . esc_js($show_past_events) . '",
					show_only_past_events: "' . esc_js($show_only_past_events) . '",
					show_only_one_occurrence: "' . esc_js($show_only_one_occurrence) . '",
					categories: (jQuery("select[id^=\"mec_sf_category\"]").lenght > 0) ?  jQuery("select[id^=\"mec_sf_category\"]").val() : "",
					multiCategories: (jQuery(".select2-hidden-accessible").lenght > 0) ? jQuery(".select2-hidden-accessible").val() : "",
					location: jQuery("select[id^=\"mec_sf_location\"]").val(),
					organizer: jQuery("select[id^=\"mec_sf_organizer\"]").val(),
					speaker: jQuery("select[id^=\"mec_sf_speaker\"]").val(),
					tag: jQuery("select[id^=\"mec_sf_tag\"]").val(),
					label: jQuery("select[id^=\"mec_sf_label\"]").val(),
					cost_min: jQuery("input[id^=\"mec_sf_event_cost_min\"]").val(),
					cost_max: jQuery("input[id^=\"mec_sf_event_cost_max\"]").val(),
					display_label: "' . esc_js($display_label) . '",
					reason_for_cancellation: "' . esc_js($reason_for_cancellation) . '",
					is_category_page: "' . esc_js($is_category_page) . '",
					cat_id: "' . esc_js($cat_id) . '",
                                        local_time: "' . esc_js($local_time) . '",
                                        image_size: "' . esc_js($image_size) . '",
                                        filter_category: "' . esc_js($filter_category) . '",
					filter_ex_category: "' . esc_js($filter_ex_category) . '",
					filter_location: "' . esc_js($filter_location) . '",
					filter_ex_location: "' . esc_js($filter_ex_location) . '",
					filter_organizer: "' . esc_js($filter_organizer) . '",
					filter_ex_organizer: "' . esc_js($filter_ex_organizer) . '",
					filter_label: "' . esc_js($filter_label) . '",
					filter_ex_label: "' . esc_js($filter_ex_label) . '",
					filter_tag: "' . esc_js($filter_tag) . '",
					filter_ex_tag: "' . esc_js($filter_ex_tag) . '",
					filter_author: "' . esc_js($filter_author) . '",
					filter_ex_author: "' . esc_js($filter_ex_author) . '",
					locale: "' . esc_js($lang) . '",
					lang: "' . esc_js($mec_lang) . '",
				},
				failure: function() {
					alert("there was an error while fetching events!");
				},
			},
			forceEventDuration: true,
			loading: function(bool) {
				document.getElementById("gCalendar-loading").style.display =
				bool ? "block" : "none";
			},
		});

		var $mecGcalbarBar = jQuery("#mec-gcalbar-'.esc_js($this->id).'");
		var mecGcalbarAjaxUrl = "'. admin_url('admin-ajax.php', NULL) .'";
		var mecGcalbarYearRequest = null;

		/* adventistai.lt: talk to FullCalendar in its OWN time, never the
		   browser\'s. This calendar is initialised with timeZone set to
		   WordPress\'s gmt_offset (a bare number like "2"), which is not a
		   timeZone value FullCalendar understands - it wants "local", "UTC",
		   or a named zone from a plugin - so it falls back to UTC. In that
		   mode (and in "local" mode too) FullCalendar hands out "marker"
		   dates whose UTC fields carry the calendar\'s wall clock, and it
		   reads any Date you give it the same way.

		   So new Date(year, month - 1, 1) - a date at the BROWSER\'s local
		   midnight - is the wrong thing to hand it: at UTC+2 local Feb 1
		   00:00 is Jan 31 22:00 UTC, and the calendar duly showed January.
		   That was the "clicking a month opens the one to its left" bug:
		   every tab was off by one for any visitor east of Greenwich, and
		   correct for anyone west of it, which is why it was so slippery.

		   Fix, both directions: hand FullCalendar a plain "YYYY-MM-01"
		   string (it parses that in its own zone - no browser offset can
		   creep in), and read markers back with the getUTC* accessors.
		   Both are correct whichever timeZone the calendar ends up in. */
		function mecGcalbarMonthStart(year, month) {
			return year + "-" + ("0" + month).slice(-2) + "-01";
		}

		function mecGcalbarSetActive(year, month) {
			var mm = ("0" + month).slice(-2);
			$mecGcalbarBar.find(".mec-ymtabs-item").removeClass("mec-ymtabs-item-active");
			$mecGcalbarBar.find(".mec-ymtabs-item[data-mec-year=\"" + year + "\"][data-mec-month=\"" + mm + "\"]").addClass("mec-ymtabs-item-active");
		}

		function mecGcalbarPopulateYearSelect(year) {
			var $select = $mecGcalbarBar.find(".mec-ymtabs-year-select");
			var yearString = String(year);
			var hasYear = $select.find("option[value=\"" + yearString + "\"]").length > 0;

			if (!hasYear || $select.find("option").length < 2) {
				var firstYear = Math.max(1900, year - 25);
				var lastYear = Math.min(2100, year + 25);
				var fragment = document.createDocumentFragment();

				for (var optionYear = firstYear; optionYear <= lastYear; optionYear++) {
					var option = document.createElement("option");
					option.value = String(optionYear);
					option.textContent = String(optionYear);
					fragment.appendChild(option);
				}

				$select.empty();
				if ($select[0]) $select[0].appendChild(fragment);
			}

			$select.val(yearString);
		}

		function mecGcalbarSetTitle(title, year) {
			var titleText = String(title || "");
			var yearText = String(year);
			var yearPosition = titleText.indexOf(yearText);
			var before = "";
			var after = "";

			if (yearPosition >= 0) {
				before = titleText.slice(0, yearPosition);
				after = titleText.slice(yearPosition + yearText.length);
			} else {
				after = titleText ? " " + titleText : "";
			}

			$mecGcalbarBar.find(".mec-ymtabs-gcal-title-before").text(before);
			$mecGcalbarBar.find(".mec-ymtabs-gcal-title-after").text(after);
			mecGcalbarPopulateYearSelect(year);
		}

		function mecGcalbarLoadYear(year, activeYear, activeMonth) {
			if (mecGcalbarYearRequest) mecGcalbarYearRequest.abort();
			$mecGcalbarBar.addClass("mec-ymtabs-loading");
			var request = jQuery.ajax({
				url: mecGcalbarAjaxUrl,
				type: "post",
				dataType: "json",
				data: {
					action: "mec_gcalbar_get_year",
					year: year,
					active_year: activeYear,
					active_month: activeMonth
				},
				success: function(response) {
					if (!response || typeof response.html === "undefined") return;
					if (parseInt(response.year, 10) !== year) return;
					$mecGcalbarBar.find(".mec-ymtabs-months").html(response.html);
					$mecGcalbarBar.attr("data-mec-shown-year", response.year);
					mecGcalbarPopulateYearSelect(parseInt(response.year, 10));
				},
				complete: function() {
					if (mecGcalbarYearRequest === request) {
						mecGcalbarYearRequest = null;
						$mecGcalbarBar.removeClass("mec-ymtabs-loading");
					}
				}
			});
			mecGcalbarYearRequest = request;
		}

		function mecGcalbarSync(dateObj) {
			var year = dateObj.getUTCFullYear();
			var month = dateObj.getUTCMonth() + 1;
			var shownYear = parseInt($mecGcalbarBar.attr("data-mec-shown-year"), 10);
			if (shownYear !== year) {
				mecGcalbarLoadYear(year, year, month);
			} else {
				mecGcalbarSetActive(year, month);
			}
		}

		$mecGcalbarBar.on("change", ".mec-ymtabs-year-select", function() {
			var targetYear = parseInt(jQuery(this).val(), 10);
			if (!Number.isFinite(targetYear)) return;
			targetYear = Math.max(1900, Math.min(2100, targetYear));
			var curMonth = calendar.getDate().getUTCMonth() + 1;
			calendar.gotoDate(mecGcalbarMonthStart(targetYear, curMonth));
		});

		$mecGcalbarBar.on("click", ".mec-ymtabs-item", function(e) {
			e.preventDefault();
			// .attr() not .data(): jQuery coerces "10".."12" to numbers but
			// leaves "01".."09" as strings, so read the attribute as written.
			var y = parseInt(jQuery(this).attr("data-mec-year"), 10);
			var m = parseInt(jQuery(this).attr("data-mec-month"), 10);
			calendar.gotoDate(mecGcalbarMonthStart(y, m));
		});

		// adventistai.lt: fixed-position month controls in the merged header
		// (gcalbar_render_bar()) — these replace FullCalendar\'s
		// own headerToolbar prev/today/next buttons (turned off above), so
		// this is the only navigation control now, all driven off the same
		// calendar instance as the tabs.
		$mecGcalbarBar.on("click", ".mec-gcalbar-nav-prev", function(e) {
			e.preventDefault();
			calendar.prev();
		});
		$mecGcalbarBar.on("click", ".mec-gcalbar-nav-next", function(e) {
			e.preventDefault();
			calendar.next();
		});
		$mecGcalbarBar.on("click", ".mec-gcalbar-nav-today", function(e) {
			e.preventDefault();
			calendar.today();
		});

		calendar.render();

		// adventistai.lt: this used to be FullCalendar\'s own h2 title
		// (".fc-toolbar-chunk h2"), which the Month Filter button was hung
		// off. The month filter belongs in the open fifth grid column of our
		// merged bar; keeping it outside the fixed-width title prevents it
		// from shifting or covering the month arrows. The native h2 lookup is
		// retained as a fallback for a future upstream toolbar change.
		const calendarHeaderFirstChild = $mecGcalbarBar.find(".mec-ymtabs-gcal-navigator").length
			? $mecGcalbarBar.find(".mec-ymtabs-gcal-navigator")
			: jQuery(".fc-header-toolbar").find(".fc-toolbar-chunk h2");
		const calendarHeaderLastChild = jQuery(".fc-header-toolbar").find(".fc-toolbar-chunk:last-child");
		const calendarHeaderButton = calendarHeaderLastChild.find(".fc-button-group");
		';
		// Search Bar Filter
if (is_array($this->sf_options) and $this->sf_options['text_search']['type'] != '0') :
$javascript .='
			jQuery( "<div class=\"mec-gCalendar-search-text-wrap\">'.addslashes($this->icons->display('magnifier')).'<input type=\"text\" class=\"mec-gCalendar-search-text\" placeholder=\"'. ((is_array($this->sf_options) and $this->sf_options['text_search']['placeholder']) ? esc_html__($this->sf_options['text_search']['placeholder']) : esc_html__('Search for events', 'modern-events-calendar-lite')) .'\" /></div>" ).insertBefore( ".fc-header-toolbar .fc-toolbar-chunk:last-child .fc-button-group" );

			jQuery(".mec-gCalendar-search-text").keypress(function(event){
				var keycode = (event.keyCode ? event.keyCode : event.which);
				if(keycode == "13"){
					jQuery(".fc-findEvents-button").trigger("click");
				}
			});
			';
endif;

		// Month Filter
if (is_array($this->sf_options) and $this->sf_options['month_filter']['type'] != '0') :
$javascript .='
		calendarHeaderFirstChild.append("<button class=\"gCalendarMonthFilterButton input-append date\" id=\"gCalendarMonthFilterButton\" data-date=\"12-02-2012\" data-date-format=\"dd-mm-yyyy\"><input id=\"mec-gCalendar-month-filter\" class=\"span2\" size=\"16\" type=\"text\" value=\"12-02-2012\"><span class=\"openMonthFilter add-on\"><i class=\"mec-sl-arrow-down\"></i></span></button>");
		jQuery("#gCalendarMonthFilterButton").on("changeDate", function(ev) {
			var s = new Date(ev.date.valueOf());
			let ye = new Intl.DateTimeFormat("en", { year: "numeric" }).format(s);
			let mo = new Intl.DateTimeFormat("en", { month: "2-digit" }).format(s);
			let da = new Intl.DateTimeFormat("en", { day: "2-digit" }).format(s);
			jQuery("#gCalendarMonthFilterButton").monthPicker("hide");
			calendar.gotoDate(`${ye}-${mo}-${da}`)
		})
		setTimeout(function(){ jQuery(".datepicker").appendTo(".gCalendarMonthFilterButton"); }, 1000);
		';
endif;

if (mec_general_calendar_find_event($this->sf_options, 'filter') ) :
$javascript .='
		setTimeout(function(){ jQuery(".mec-gCalendar-filters").appendTo(calendarHeaderButton); }, 1000);
		jQuery("<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"14\" height=\"14.024\" viewBox=\"0 0 14 14.024\"><path id=\"Path_5991\" data-name=\"Path 5991\" d=\"M24.387,11H11.7a.654.654,0,0,0-.465,1.118l5.057,5.063v5.657a.654.654,0,0,0,.281.54l2.161,1.529a.659.659,0,0,0,1.032-.54V17.2l5.057-5.063A.654.654,0,0,0,24.387,11Z\" transform=\"translate(-11.041 -11)\" fill=\"#babfc2\"/></svg>").appendTo("button.fc-filterEvents-button.fc-button.fc-button-primary");
		if ( jQuery(".mec-gCalendar-filters-wrap").length > 0 ) jQuery(".mec-gCalendar-filters-wrap .mec-dropdown-search").find("select").niceSelect();

		jQuery(document).on("click", function(e) {
			var button = jQuery(".fc-filterEvents-button");
			var wrap = jQuery(".mec-gCalendar-filters");
			if ((!button.is(e.target) && button.has(e.target).length === 0) && (!wrap.is(e.target) && wrap.has(e.target).length === 0)) {
				wrap.hide();
				if ( jQuery(".mec-searchbar-category-wrap select").length > 0 ) jQuery(".mec-searchbar-category-wrap select").select2("close");
			} else {
			}
		});
		jQuery(document).ready(function ($) {
			jQuery(".mec-gCalendar-filters-wrap").find(".mec-search-reset-button").parents().eq(2).addClass("mec-there-reset-button");
			jQuery(".mec-gCalendar-filters-wrap").find(".mec-search-reset-button").on("click", function() {
				reset()
			})
			function reset() {
				var $wrapper = jQuery("#mec_search_form_'. esc_attr($this->id) .'");
				var isEnhanced = $wrapper.hasClass("mec-dropdown-enhanced");
				var $event_cost_min = $("#mec_sf_event_cost_min_'. esc_attr($this->id).'");
				var $event_cost_max = $("#mec_sf_event_cost_max_'. esc_attr($this->id).'");
				var $time_start = $("#mec_sf_timepicker_start_'. esc_attr($this->id).'");
				var $time_end = $("#mec_sf_timepicker_end_'. esc_attr($this->id).'");
				var $s = $("#mec_sf_s_'. esc_attr($this->id).'");
				var $address = $("#mec_sf_address_s_'. esc_attr($this->id).'");
				var $address_radius = $("#mec_sf_address_radius_'. esc_attr($this->id).'");
				var $date_start = $("#mec_sf_date_start_'. esc_attr($this->id).'");
				var $date_end = $("#mec_sf_date_end_'. esc_attr($this->id).'");
				var $event_type = $("#mec_sf_event_type_'. esc_attr($this->id).'");
				var $event_type_2 = $("#mec_sf_event_type_2_'. esc_attr($this->id).'");
				var $attribute = $("#mec_sf_attribute_'. esc_attr($this->id).'");
				var $category = jQuery("#mec_sf_category_'. esc_attr($this->id).'");
				var $location = jQuery("#mec_sf_location_'. esc_attr($this->id).'");
				var $organizer = jQuery("#mec_sf_organizer_'. esc_attr($this->id).'");
				var $speaker = jQuery("#mec_sf_speaker_'. esc_attr($this->id).'");
				var $tag = jQuery("#mec_sf_tag_'. esc_attr($this->id).'");
				var $label = jQuery("#mec_sf_label_'. esc_attr($this->id).'");
				var $month = $("#mec_sf_month_'. esc_attr($this->id).'");
        		var $year = $("#mec_sf_year_'. esc_attr($this->id).'");
        		var $month_or_year = $("#mec_sf_month_'. esc_attr($this->id).'" + ", " + "#mec_sf_year_'. esc_attr($this->id) .'");

				if ($category.length && $category.prop("tagName") && $category.prop("tagName").toLowerCase() === "div") {
					$category.find("select").each(function () {
						jQuery(this).val(null).trigger("change");
					});
					if(isEnhanced && jQuery().select2) $category.find("select").select2();
					else if(jQuery().niceSelect) $category.find("select").niceSelect("update");
				} else {
					if ($category.length) {
						$category.val(null);
						if(isEnhanced && jQuery().select2) $category.select2();
						else if(jQuery().niceSelect) $category.niceSelect("update")
					}
				}

				if ($location.length) $location.val(null);
				if ($organizer.length) $organizer.val(null);
				if ($speaker.length) $speaker.val(null);
				if ($tag.length) $tag.val(null);
				if ($label.length) $label.val(null);
				if ($s.length) $s.val(null);
				if ($address.length) $address.val(null);
				if ($address_radius.length) $address_radius.val(null);
				if ($month.length) $month.val(null);
				if ($year.length) $year.val(null);
				if ($event_cost_min.length) $event_cost_min.val(null);
				if ($event_cost_max.length) $event_cost_max.val(null);
				if ($date_start.length) $date_start.val(null);
				if ($date_end.length) $date_end.val(null);
				if ($time_start.length) $time_start.val(null);
				if ($time_end.length) $time_end.val(null);

				function get_fields(){
					return [
						"state",
						"city",
						"region",
						"region",
						"street",
						"postal_code",
					];
				}
				var fields = get_fields();
				$.each(fields,function(i,field){

					if( jQuery("#mec_sf_"+ field +"_'. esc_attr($this->id) .'").length ){

						jQuery("#mec_sf_"+ field +"_'. esc_attr($this->id) .'").val(null);
						if( jQuery("#mec_sf_"+ field +"_'. esc_attr($this->id) .'").is("select") ){
							jQuery("#mec_sf_"+ field +"_'. esc_attr($this->id) .'").niceSelect("update");
						}
					}
				});

				// Search Again
				setTimeout(function () {
					jQuery(".fc-findEvents-button").trigger("click");
				}, 1);
			}
		});
		';
endif;
$javascript .='
	});
</script>';

$factory = new MEC_factory();
$factory->params('footer', $javascript);
