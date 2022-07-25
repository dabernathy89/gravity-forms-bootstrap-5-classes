<?php

// phpcs:disable Generic.Arrays.DisallowShortArraySyntax.Found
// phpcs:disable WordPress.PHP.YodaConditions.NotYoda

class GravityFormsBootstrap {
	/**
	 * A mapping of Gravity Forms field types to the corresponding HTML type
	 * for fields that will be considered bootstrap form controls
	 *
	 * @var string[]
	 */
	protected $form_control_inputs = [
		'text'       => 'text',
		'email'      => 'email',
		'phone'      => 'tel',
		'number'     => 'number',
		'website'    => 'url',
		'fileupload' => 'file',
	];

	protected $complex_types = [
		'name',
		'address',
	];

	public function __construct() {
		if ( ! is_admin() ) {
			add_filter( 'gform_disable_form_theme_css', '__return_true' );
			add_filter( 'gform_field_choices', [ $this, 'html_encode_choice_labels' ], 5, 2 );
			add_filter( 'gform_get_form_filter', [ $this, 'add_outer_row' ], 10, 2 );
			add_filter( 'gform_field_content', [ $this, 'set_up_list_grid' ], 10, 2 );
			add_filter( 'gform_field_content', [ $this, 'add_description_classes' ], 10, 2 );
			add_filter( 'gform_field_content', [ $this, 'add_checkbox_radio_input_classes' ], 10, 2 );
			add_filter( 'gform_field_content', [ $this, 'add_form_control_classes' ], 10, 2 );
			add_filter( 'gform_field_content', [ $this, 'add_select_input_classes' ], 10, 2 );
			add_filter( 'gform_field_content', [ $this, 'add_label_classes' ], 10, 2 );
			add_filter( 'gform_field_container', [ $this, 'add_field_container_cols' ], 10, 6 );
			add_filter( 'gform_field_content', [ $this, 'set_up_name_container_grid' ], 10, 6 );
			add_filter( 'gform_field_content', [ $this, 'set_up_date_time_container_grid' ], 10, 6 );
			add_filter( 'gform_field_content', [ $this, 'set_up_address_container_grid' ], 10, 6 );
			add_filter( 'gform_submit_button', [ $this, 'modify_button' ], 10, 2 );
		}
	}

	/**
	 * Convert HTML entities
	 *
	 * @param string $string
	 * @return string
	 */
	protected function htmlspecialchars( $string ) {
		return htmlspecialchars(
			$string,
			ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5,
			'UTF-8',
			false
		);
	}

	/**
	 * Responds to GF `gform_field_choices` hook to properly encode choice field labels
	 *
	 * @param string $choices
	 * @param object $field
	 * @return string
	 */
	public function html_encode_choice_labels( $choices, $field ) {
		foreach ( $field->choices as $choice ) {
			$choices = str_replace(
				'>' . $choice['text'] . '<',
				'>' . $this->htmlspecialchars( $choice['text'] ) . '<',
				$choices
			);
		}

		return $choices;
	}

	/**
	 * Set up the form wrapper as a row
	 *
	 * @param string $form_string
	 * @param object $form
	 * @return string
	 */
	public function add_outer_row( $form_string, $form ) {
		return str_replace( 'gform_fields', 'gform_fields row', $form_string );
	}

	/**
	 * Add grid classes for the `list` field type
	 *
	 * @param string $field_content
	 * @param object $field
	 * @return string
	 */
	public function set_up_list_grid( $field_content, $field ) {
		if ( $field->type !== 'list' ) {
			return $field_content;
		}

		$replacements = [
			'gfield_list_group'         => 'row',
			'gfield_list_group_item'    => 'col',
			'gfield_list_header'        => 'row',
			// Ideally there would be an easy way to avoid adding `col` to the header
			// item that also has `--icons`, but that would require some extra logic.
			'gfield_header_item'        => 'col',
			'gfield_list_icons'         => 'col-auto',
			'gfield_header_item--icons' => 'col-auto',
		];

		foreach ( $replacements as $gf_class => $bs_class ) {
			$field_content = preg_replace(
				'/(["\'\s])' . $gf_class . '(["\'\s])/',
				'$1' . $gf_class . ' ' . $bs_class . '$2',
				$field_content
			);
		}

		return $field_content;
	}

	/**
	 * Add proper classes for field descriptions
	 *
	 * @param string $field_content
	 * @param object $field
	 * @return string
	 */
	public function add_description_classes( $field_content, $field ) {
		return str_replace( 'gfield_description', 'gfield_description form-text', $field_content );
	}

	/**
	 * Add proper classes for choice field inputs
	 *
	 * @param string $content
	 * @param object $field
	 * @return string
	 */
	public function add_checkbox_radio_input_classes( $content, $field ) {
		if ( $field->type === 'checkbox' || $field->type === 'radio' ) {
			$content = str_replace( 'gfield-choice-input', 'gfield-choice-input form-check-input', $content );
		}

		return $content;
	}

	/**
	 * Add `form-control` class to appropriate inputs
	 *
	 * @param string $content
	 * @param object $field
	 * @return string
	 */
	public function add_form_control_classes( $content, $field ) {
		$dom = $this->create_dom_context_from_fragment( $content );

		$inputs = $dom->getElementsByTagName( 'input' );
		foreach ( $inputs as $input ) {
			if ( in_array( $input->getAttribute( 'type' ), $this->form_control_inputs, true ) ) {
				$css_class = $input->getAttribute( 'class' );
				$input->setAttribute( 'class', 'form-control ' . $css_class );
			}
		}

		$textareas = $dom->getElementsByTagName( 'textarea' );
		foreach ( $textareas as $textarea ) {
			$css_class = $textarea->getAttribute( 'class' );
			$textarea->setAttribute( 'class', 'form-control ' . $css_class );
		}

		return $this->save_html_from_domdocument( $dom );
	}

	/**
	 * Add `form-select` class to selects
	 *
	 * @param string $content
	 * @param object $field
	 * @return string
	 */
	public function add_select_input_classes( $content, $field ) {
		$dom = $this->create_dom_context_from_fragment( $content );

		$selects = $dom->getElementsByTagName( 'select' );
		foreach ( $selects as $select ) {
			$css_class = $select->getAttribute( 'class' );
			$select->setAttribute( 'class', 'form-select ' . $css_class );
		}

		return $this->save_html_from_domdocument( $dom );
	}

	/**
	 * @param string $content
	 * @param object $field
	 * @return string
	 */
	public function add_label_classes( $content, $field ) {
		$dom = $this->create_dom_context_from_fragment( $content );

		// TODO: should this just apply to every single field other than checkbox/radio?
		if ( in_array( $field->type, [ 'select', 'multiselect' ], true )
			|| in_array( $field->type, array_keys( $this->form_control_inputs ), true )
			|| in_array( $field->type, $this->complex_types, true )
		) {
			$labels = $dom->getElementsByTagName( 'label' );
			foreach ( $labels as $label ) {
				$css_class = $label->getAttribute( 'class' );
				$label->setAttribute( 'class', trim( 'form-label ' . $css_class ) );
			}
		}

		if ( $field->type === 'checkbox' || $field->type === 'radio' ) {
			$labels = $dom->getElementsByTagName( 'label' );
			foreach ( $labels as $label ) {
				$css_class = $label->getAttribute( 'class' );
				$label->setAttribute( 'class', trim( 'form-check-label ' . $css_class ) );
			}
		}

		return $this->save_html_from_domdocument( $dom );
	}

	/**
	 * Handle fields arranged in columns
	 *
	 * @param string $field_container
	 * @param object $field
	 * @param object $form
	 * @param string $css_class
	 * @param string $style
	 * @param string $field_content
	 * @return string
	 */
	public function add_field_container_cols( $field_container, $field, $form, $css_class, $style, $field_content ) {
		$classes = explode( ' ', $css_class );

		if ( in_array( 'gfield--width-full', $classes, true ) ) {
			$field_container = str_replace( 'gfield--width-full', 'gfield--width-full col-12', $field_container );
		} elseif ( in_array( 'gfield--width-half', $classes, true ) ) {
			$field_container = str_replace( 'gfield--width-half', 'gfield--width-half col-6', $field_container );
		} elseif ( in_array( 'gfield--width-third', $classes, true ) ) {
			$field_container = str_replace( 'gfield--width-third', 'gfield--width-third col-4', $field_container );
		} elseif ( in_array( 'gfield-checkbox', $classes, true ) ) {
			$field_container = str_replace( 'gfield-checkbox', 'gfield-checkbox col-12', $field_container );
		}

		return $field_container;
	}

	/**
	 * Handle layout for `name` field
	 *
	 * @param string $content
	 * @param object $field
	 * @return string
	 */
	public function set_up_name_container_grid( $content, $field ) {
		if ( $field->type !== 'name' ) {
			return $content;
		}

		$dom = $this->create_dom_context_from_fragment( $content );

		$divs = $dom->getElementsByTagName( 'div' );
		/** @var DOMElement $div */
		foreach ( $divs as $div ) {
			$class = $div->getAttribute( 'class' );
			if ( strpos( $class, 'ginput_complex' ) !== false ) {
				$div->setAttribute( 'class', trim( $class . ' row' ) );
				$spans = $div->getElementsByTagName( 'span' );
				foreach ( $spans as $span ) {
					$span_class = $span->getAttribute( 'class' );
					$span->setAttribute( 'class', trim( $span_class . ' col-md' ) );
				}
			}
		}

		return $this->save_html_from_domdocument( $dom );
	}

	/**
	 * Handle layout for `date` and `time` fields
	 *
	 * @param string $content
	 * @param object $field
	 * @return string
	 */
	public function set_up_date_time_container_grid( $content, $field ) {
		if ( $field->type !== 'time' && $field->type !== 'date' ) {
			return $content;
		}

		$dom = $this->create_dom_context_from_fragment( $content );

		$divs = $dom->getElementsByTagName( 'div' );
		/** @var DOMElement $div */
		foreach ( $divs as $div ) {
			$class = $div->getAttribute( 'class' );
			if ( strpos( $class, 'ginput_complex' ) !== false ) {
				$div->setAttribute( 'class', trim( $class . ' row' ) );
				$children = $div->getElementsByTagName( 'div' );
				foreach ( $children as $child ) {
					$child_class = $child->getAttribute( 'class' );
					$child->setAttribute( 'class', trim( $child_class . ' col-auto' ) );
				}
			}
		}

		return $this->save_html_from_domdocument( $dom );
	}

	/**
	 * Handle layout for `address` field
	 *
	 * @param string $content
	 * @param object $field
	 * @return string
	 */
	public function set_up_address_container_grid( $content, $field ) {
		if ( $field->type !== 'address' ) {
			return $content;
		}

		$dom = $this->create_dom_context_from_fragment( $content );

		$divs = $dom->getElementsByTagName( 'div' );
		/** @var DOMElement $div */
		foreach ( $divs as $div ) {
			$class = $div->getAttribute( 'class' );
			if ( strpos( $class, 'ginput_complex' ) !== false ) {
				$div->setAttribute( 'class', trim( $class . ' row' ) );
				$span = $div->getElementsByTagName( 'span' );
				foreach ( $span as $span ) {
					$span_class = $span->getAttribute( 'class' );
					$span_class = str_replace( 'ginput_full', 'ginput_full col', $span_class );
					$span_class = str_replace( 'ginput_left', 'ginput_left col-md-6', $span_class );
					$span_class = str_replace( 'ginput_right', 'ginput_right col-md-6', $span_class );
					$span->setAttribute( 'class', $span_class );
				}
			}
		}

		return $this->save_html_from_domdocument( $dom );
	}

	public function modify_button( $button, $form ) {
		$dom = $this->create_dom_context_from_fragment( $button );
		/** @var DOMElement $button */
		$button   = $dom->getElementsByTagName( 'input' )->item( 0 );
		$classes  = $button->getAttribute( 'class' );
		$classes .= ' btn btn-primary';
		$button->setAttribute( 'class', trim( $classes ) );
		return $this->save_html_from_domdocument( $dom );
	}

	/**
	 * Create a DOMDocument container for provided HTML fragment
	 *
	 * @param string $html
	 * @return DOMDocument
	 */
	public function create_dom_context_from_fragment( $html ) {
		$dom     = new DOMDocument();
		$wrapped = '<!doctype html><html lang=en><head><meta charset="utf-8"></head><body>' . $html . '</body></html>';
		$dom->loadHTML( $wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
		return $dom;
	}

	/**
	 * Using a DOMDocument container generated with `create_dom_context_from_fragment`,
	 * return the HTML contents as a string
	 *
	 * @param DOMDocument $dom
	 * @return string
	 */
	public function save_html_from_domdocument( $dom ) {
		$xpath    = new DOMXPath( $dom );
		$fragment = $xpath->query( './body/*' );
		$output   = '';
		foreach ( $fragment as $child ) {
			$output .= $dom->saveHtml( $child );
		}
		return $output;
	}
}

