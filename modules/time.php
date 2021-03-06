<?php

class ContactForm7Datepicker_Time {

	static $inline_js = array();

	public static function register() {
		// Register shortcodes
		self::add_shortcodes();

		// Validations
		add_filter('wpcf7_validate_time', array(__CLASS__, 'validation_filter'), 10, 2);
		add_filter('wpcf7_validate_time*', array(__CLASS__, 'validation_filter'), 10, 2);


		// Tag generator
		add_action('load-toplevel_page_wpcf7', array(__CLASS__, 'tag_generator'));

		// Messages
		add_filter('wpcf7_messages', array(__CLASS__, 'messages'));

		// Print inline javascript
		add_action('wp_print_footer_scripts', array(__CLASS__, 'print_inline_js'), 99999);
	}

	public static function shortcode_handler($tag) {
		if (! is_array($tag))
			return;

		$type = $tag['type'];
		$name = $tag['name'];
		$options = (array) $tag['options'];
		$values = (array) $tag['values'];

		if (empty($name))
			return;

		$validation_error = wpcf7_get_validation_error($name);

		$atts = $id_att = $size_att = $maxlen_att = '';
		$tabindex_att = $title_att = '';

		$class_att = wpcf7_form_controls_class( $type, 'wpcf7-date');

		if ('time*' == $type)
			$class_att .= ' wpcf7-validates-as-required';

		if ($validation_error)
			$class_att .= ' wpcf7-not-valid';
			
		$inline = false;
		
		$dpOptions = array();
		foreach ($options as $option) {
			if (preg_match('%^id:([-_\w\d]+)$%i', $option, $matches)) {
				$id_att = $matches[1];
			} elseif (preg_match('%^class:([-_\w\d]+)$%i', $option, $matches)) {
				$class_att .= " $matches[1]";
			} elseif (preg_match('%^(\d*)[/x](\d*)$%i', $option, $matches)) {
				$size_att = (int) $matches[1];
				$maxlen_att = (int) $matches[2];
			} elseif (preg_match('%^tabindex:(\d+)$%i', $option, $matches)) {
				$tabindex_att = (int) $matches[1];
			} elseif (preg_match('%^time-format:([-_/\.\w\d]+)$%i', $option, $matches)) {
				$dpOptions[$matches[1] . 'Format'] = str_replace('_', ' ', $matches[2]);
			} elseif (preg_match('%^animate:(\w+)$%i', $option, $matches)) {
				$dpOptions['showAnim'] = $matches[1];
			} elseif (preg_match('%^buttons$%', $option, $matches)) {
				$dpOptions['showButtonPanel'] = true;
			} elseif (preg_match('%inline$%', $option, $matches)) {
				$inline = true;
				$dpOptions['altField'] = "#{$name}_alt";
			} elseif (preg_match('%^(min|max)-(hour|minute|second):([\d]+)$%i', $option, $matches)) {
				$dpOptions[$matches[2] . ucfirst($matches[1])] = (int)$matches[3];
			} elseif (preg_match('%^step-(hour|minute|second):([\d]+)$%i', $option, $matches)) {
				$dpOptions['step' . ucfirst($matches[1])] = (int)$matches[2];
			} elseif (preg_match('%^control-type:(slider|select)$%i', $option, $matches)) {
				$dpOptions['controlType'] = $matches[1];
			}

			do_action_ref_array('cf7dp_time_attr_match', array($dpOptions), $option);
		}

		$value = reset($values);

		if (wpcf7_script_is() && preg_grep('%^watermark$%', $options)) {
			$class_att .= ' wpcf7-use-title-as-watermark';
			$title_att .= " $value";
			$value = '';
		}

		if (wpcf7_is_posted() && isset($_POST[$name]))
			$value = stripslashes($_POST[$name]);

		if ($id_att)
			$atts .= ' id="' . trim($id_att) . '"';

		if ($class_att)
			$atts .= ' class="' . trim($class_att) . '"';

		if ($size_att)
			$atts .= ' size="' . $size_att . '"';
		else
			$atts .= ' size="40"';

		if ($maxlen_att)
			$atts .= ' maxlength="' . $maxlen_att . '"';

		if ('' !== $tabindex_att)
			$atts .= ' tabindex="' . $tabindex_att .'"';

		if ($title_att)
			$atts .= ' title="' . trim(esc_attr($title_att)) . '"';

		$input_type = $inline ? 'hidden' : 'text';
		$input_atts = $inline ? "id=\"{$name}_alt\"" : $atts;

		$input = sprintf('<input type="%s" name="%s" value="%s" %s/>',
			$input_type,
			esc_attr($name),
			esc_attr($value),
			$input_atts
		);

		$input = apply_filters('cf7dp_time_input', $input);

		if ($inline)
			$input .= sprintf('<div id="%s_timepicker" %s></div>', $name, $atts);

		$dp_selector = $inline ? '#' . $name . '_timepicker' : $name;

		$dp = new CF7_DateTimePicker('time', $dp_selector, $dpOptions);

		self::$inline_js[] = $dp->generate_code($inline);

		return sprintf('<span class="wpcf7-form-control-wrap %s">%s %s</span>',
			esc_attr($name),
			$input,
			$validation_error
		);
	}

	public static function validation_filter($result, $tag) {
		$type = $tag['type'];
		$name = $tag['name'];

		$value = trim($_POST[$name]);

		if ('time*' == $type && empty($value)) {
			$result['valid'] = false;
			$result['reason'][$name] = wpcf7_get_message('invalid_required');
		}

		if (! empty($value) && ! self::is_valid_date($value)) {
			$result['valid'] = false;
			$result['reason'][$name] = wpcf7_get_message('invalid_time');
		}

		return $result;
	}

	public static function tag_generator() {
		wpcf7_add_tag_generator('time',
			__('Time field', 'wpcf7'),
			'wpcf7-tg-pane-time',
			array(__CLASS__, 'tg_pane')
		);
	}

	public static function tg_pane() {
		require_once dirname(__FILE__) . '/generators/time.php';
	}

	private static function add_shortcodes() {
		if (function_exists('wpcf7_add_shortcode')) {
			wpcf7_add_shortcode('time', array(__CLASS__, 'shortcode_handler'), true);
			wpcf7_add_shortcode('time*', array(__CLASS__, 'shortcode_handler'), true);
		}
	}

	public static function messages($messages) {
		$messages['invalid_time'] = array(
			'description' => __('The time that the sender entered is invalid'),
			'default' => __('Invalid time supplied.'),
		);

		return $messages;
	}

	public static function print_inline_js() {
		if (! wp_script_is('jquery-ui-timepicker', 'done') || empty(self::$inline_js))
			return;

		$out = implode("\n\t", self::$inline_js);
		$out = "jQuery(function($){\n\t$out\n});";

		echo "\n<script type=\"text/javascript\">\n{$out}\n</script>\n";
	}

	private static function animate_dropdown() {
		$html = "<select id=\"animate\">\n";

		foreach (CF7_DateTimePicker::$effects as $val) {
			$html .= '<option value="' . esc_attr($val) . '">' . ucfirst($val) . '</option>';
		}

		$html .= "</select>";

		echo $html;
	}

	private static function is_valid_date($value) {
		return strtotime($value) ? true : false;
	}
}

ContactForm7Datepicker_Time::register();
