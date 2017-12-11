<?php

	function um_mail_content_type( $content_type ) {

		return 'text/html';
	}

	/***
	 ***    @Trim string by char length
	 ***/
	function um_trim_string( $s, $length = 20 ) {
		$s = strlen( $s ) > $length ? substr( $s, 0, $length ) . "..." : $s;

		return $s;
	}

	/***
	 ***    @Convert urls to clickable links
	 ***/
	function um_clickable_links( $s ) {

		return preg_replace( '@(https?://([-\w\.]+[-\w])+(:\d+)?(/([\w/_\.#-]*(\?\S+)?[^\.\s])?)?)@', '<a href="$1" class="um-link" target="_blank">$1</a>', $s );
	}

	/***
	 ***    @Get where user should be headed after logging
	 ***/
	function um_dynamic_login_page_redirect( $redirect_to = '' ) {

		$uri = um_get_core_page( 'login' );

		if (!$redirect_to) {
			$redirect_to = UM()->permalinks()->get_current_url();
		}

		$redirect_key = urlencode_deep( $redirect_to );

		$uri = add_query_arg( 'redirect_to', $redirect_key, $uri );

		return $uri;
	}

	/**
	 * Set redirect key
	 *
	 * @param  string $url
	 *
	 * @return string $redirect_key
	 */
	function um_set_redirect_url( $url ) {

		if (um_is_session_started() === false) {
			session_start();
		}

		$redirect_key = wp_generate_password( 12, false );

		$_SESSION['um_redirect_key'] = array( $redirect_key => $url );

		return $redirect_key;
	}

	/**
	 * Set redirect key
	 *
	 * @param  string $url
	 *
	 * @return string $redirect_key
	 */
	function um_get_redirect_url( $key ) {

		if (um_is_session_started() === false) {
			session_start();
		}

		if (isset( $_SESSION['um_redirect_key'][$key] )) {

			$url = $_SESSION['um_redirect_key'][$key];

			return $url;

		} else {

			if (isset( $_SESSION['um_redirect_key'] )) {
				foreach ($_SESSION['um_redirect_key'] as $key => $url) {

					return $url;

					break;
				}
			}
		}

		return;
	}


	/**
	 * Checks if session has been started
	 *
	 * @return bool
	 */
	function um_is_session_started() {

		if (php_sapi_name() !== 'cli') {
			if (version_compare( phpversion(), '5.4.0', '>=' )) {
				return session_status() === PHP_SESSION_ACTIVE ? true : false;
			} else {
				return session_id() === '' ? false : true;
			}
		}

		return false;
	}


	/***
	 *** @user clean basename
	 ***/
	function um_clean_user_basename( $value ) {

		$raw_value = $value;
		$value = str_replace( '.', ' ', $value );
		$value = str_replace( '-', ' ', $value );
		$value = str_replace( '+', ' ', $value );

		$value = apply_filters( 'um_clean_user_basename_filter', $value, $raw_value );

		return $value;
	}

	/***
	 ***    @convert template tags
	 ***/
	function um_convert_tags( $content, $args = array() ) {
		$search = array(
			'{display_name}',
			'{first_name}',
			'{last_name}',
			'{gender}',
			'{username}',
			'{email}',
			'{password}',
			'{login_url}',
			'{login_referrer}',
			'{site_name}',
			'{site_url}',
			'{account_activation_link}',
			'{password_reset_link}',
			'{admin_email}',
			'{user_profile_link}',
			'{user_account_link}',
			'{submitted_registration}',
			'{user_avatar_url}',
		);

		$search = apply_filters( 'um_template_tags_patterns_hook', $search );

		$replace = array(
			um_user( 'display_name' ),
			um_user( 'first_name' ),
			um_user( 'last_name' ),
			um_user( 'gender' ),
			um_user( 'user_login' ),
			um_user( 'user_email' ),
			um_user( '_um_cool_but_hard_to_guess_plain_pw' ),
			um_get_core_page( 'login' ),
			um_dynamic_login_page_redirect(),
			UM()->options()->get( 'site_name' ),
			get_bloginfo( 'url' ),
			um_user( 'account_activation_link' ),
			um_user( 'password_reset_link' ),
			um_admin_email(),
			um_user_profile_url(),
			um_get_core_page( 'account' ),
			um_user_submitted_registration(),
			um_get_user_avatar_url(),
		);

		$replace = apply_filters( 'um_template_tags_replaces_hook', $replace );

		$content = wp_kses_decode_entities( str_replace( $search, $replace, $content ) );

		if (isset( $args['tags'] ) && isset( $args['tags_replace'] )) {
			$content = str_replace( $args['tags'], $args['tags_replace'], $content );
		}

		$regex = '~\{(usermeta:[^}]*)\}~';
		preg_match_all( $regex, $content, $matches );

		// Support for all usermeta keys
		if ( ! empty( $matches[1] ) && is_array( $matches[1] ) ) {
			foreach ( $matches[1] as $match ) {
				$strip_key = str_replace( 'usermeta:', '', $match );
				$content = str_replace( '{' . $match . '}', um_user( $strip_key ), $content );
			}
		}

		return $content;

	}

	/**
	 * @function um_user_ip()
	 *
	 * @description This function returns the IP address of user.
	 *
	 * @usage <?php $user_ip = um_user_ip(); ?>
	 *
	 * @returns Returns the user's IP address.
	 *
	 * @example The example below can retrieve the user's IP address
	 *
	 * <?php
	 *
	 * $user_ip = um_user_ip();
	 * echo 'User IP address is: ' . $user_ip; // prints the user IP address e.g. 127.0.0.1
	 *
	 * ?>
	 *
	 *
	 */
	function um_user_ip() {
		$ip = '127.0.0.1';

		if (!empty( $_SERVER['HTTP_CLIENT_IP'] )) {
			//check ip from share internet
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		} else if (!empty( $_SERVER['HTTP_X_FORWARDED_FOR'] )) {
			//to check ip is pass from proxy
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else if (!empty( $_SERVER['REMOTE_ADDR'] )) {
			$ip = $_SERVER['REMOTE_ADDR'];
		}

		return apply_filters( 'um_user_ip', $ip );
	}

	/***
	 ***    @If conditions are met return true;
	 ***/
	function um_field_conditions_are_met( $data ) {
		if (!isset( $data['conditions'] )) return true;

		$state = 1;

		foreach ($data['conditions'] as $k => $arr) {
			if ($arr[0] == 'show') {

				$val = $arr[3];
				$op = $arr[2];

				if (strstr( $arr[1], 'role_' ))
					$arr[1] = 'role';

				$field = um_profile( $arr[1] );

				switch ($op) {
					case 'equals to':

						$field = maybe_unserialize( $field );

						if (is_array( $field ))
							$state = in_array( $val, $field ) ? 1 : 0;
						else
							$state = ( $field == $val ) ? 1 : 0;

						break;
					case 'not equals':

						$field = maybe_unserialize( $field );

						if (is_array( $field ))
							$state = !in_array( $val, $field ) ? 1 : 0;
						else
							$state = ( $field != $val ) ? 1 : 0;

						break;
					case 'empty':

						$state = ( !$field ) ? 1 : 0;

						break;
					case 'not empty':

						$state = ( $field ) ? 1 : 0;

						break;
					case 'greater than':
						if ($field > $val) {
							$state = 1;
						} else {
							$state = 0;
						}
						break;
					case 'less than':
						if ($field < $val) {
							$state = 1;
						} else {
							$state = 0;
						}
						break;
					case 'contains':
						if (strstr( $field, $val )) {
							$state = 1;
						} else {
							$state = 0;
						}
						break;
				}
			} else if ($arr[0] == 'hide') {

				$state = 1;
				$val = $arr[3];
				$op = $arr[2];

				if (strstr( $arr[1], 'role_' ))
					$arr[1] = 'role';

				$field = um_profile( $arr[1] );

				switch ($op) {
					case 'equals to':

						$field = maybe_unserialize( $field );

						if (is_array( $field ))
							$state = in_array( $val, $field ) ? 0 : 1;
						else
							$state = ( $field == $val ) ? 0 : 1;

						break;
					case 'not equals':

						$field = maybe_unserialize( $field );

						if (is_array( $field ))
							$state = !in_array( $val, $field ) ? 0 : 1;
						else
							$state = ( $field != $val ) ? 0 : 1;

						break;
					case 'empty':

						$state = ( !$field ) ? 0 : 1;

						break;
					case 'not empty':

						$state = ( $field ) ? 0 : 1;

						break;
					case 'greater than':
						if ($field <= $val) {
							$state = 0;
						} else {
							$state = 1;
						}
						break;
					case 'less than':
						if ($field >= $val) {
							$state = 0;
						} else {
							$state = 1;
						}
						break;
					case 'contains':
						if (strstr( $field, $val )) {
							$state = 0;
						} else {
							$state = 1;
						}
						break;
				}
			}
		}

		return ( $state ) ? true : false;
	}

	/***
	 ***    @Exit and redirect to home
	 ***/
	function um_redirect_home() {

		exit( wp_redirect( home_url() ) );
	}


	function um_js_redirect( $url ) {
		if (headers_sent() || empty( $url )) {
			//for blank redirects
			if ('' == $url) {
				$url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];
			}

			$funtext = "echo \"<script data-cfasync='false' type='text/javascript'>window.location = '" . $url . "'</script>\";";
			register_shutdown_function( create_function( '', $funtext ) );

			if (1 < ob_get_level()) {
				while (ob_get_level() > 1) {
					ob_end_clean();
				}
			}

			?>
            <script data-cfasync='false' type="text/javascript">
                window.location = '<?php echo $url; ?>';
            </script>
			<?php
			exit;
		} else {
			wp_redirect( $url );
		}
		exit;
	}


	/***
	 ***    @Get limit of words from sentence
	 ***/
	function um_get_snippet( $str, $wordCount = 10 ) {
		if (str_word_count( $str ) > $wordCount) {
			$str = implode(
				'',
				array_slice(
					preg_split(
						'/([\s,\.;\?\!]+)/',
						$str,
						$wordCount * 2 + 1,
						PREG_SPLIT_DELIM_CAPTURE
					),
					0,
					$wordCount * 2 - 1
				)
			);
		}

		return $str;
	}

	/***
	 ***    @Get submitted user information
	 ***/
	function um_user_submitted_registration( $style = false ) {
		$output = null;

		$data = um_user( 'submitted' );

		if ($style)
			$output .= '<div class="um-admin-infobox">';

		if (isset( $data ) && is_array( $data )) {

			$data = apply_filters( 'um_email_registration_data', $data );

			foreach ($data as $k => $v) {

				if (!is_array( $v ) && strstr( $v, 'ultimatemember/temp' )) {
					$file = basename( $v );
					$v = um_user_uploads_uri() . $file;
				}

				if (!strstr( $k, 'user_pass' ) && !in_array( $k, array( 'g-recaptcha-response', 'request', '_wpnonce', '_wp_http_referer' ) )) {

					if (is_array( $v )) {
						$v = implode( ',', $v );
					}

					if ($k == 'timestamp') {
						$k = __( 'date submitted', 'ultimate-member' );
						$v = date( "d M Y H:i", $v );
					}

					if ($style) {
						if (!$v) $v = __( '(empty)', 'ultimate-member' );
						$output .= "<p><label>$k</label><span>$v</span></p>";
					} else {
						$output .= "$k: $v" . "<br />";
					}

				}

			}
		}

		if ($style)
			$output .= '</div>';

		return $output;
	}

	/***
	 ***    @Show filtered social link
	 ***/
	function um_filtered_social_link( $key, $match ) {
		$value = um_profile( $key );
		$submatch = str_replace( 'https://', '', $match );
		$submatch = str_replace( 'http://', '', $submatch );
		if (strstr( $value, $submatch )) {
			$value = 'https://' . $value;
		} else if (strpos( $value, 'http' ) !== 0) {
			$value = $match . $value;
		}
		$value = str_replace( 'https://https://', 'https://', $value );
		$value = str_replace( 'http://https://', 'https://', $value );
		$value = str_replace( 'https://http://', 'https://', $value );

		return $value;
	}

	/**
	 * Get filtered meta value after applying hooks
	 *
	 * @param $key
	 * @param bool $data
	 * @return mixed|string|void
	 */
	function um_filtered_value( $key, $data = false ) {
		$value = um_user( $key );

		if ( ! $data ) {
			$data = UM()->builtin()->get_specific_field( $key );
		}

		$type = ( isset( $data['type'] ) ) ? $data['type'] : '';

		$value = apply_filters( "um_profile_field_filter_hook__", $value, $data, $type );
		$value = apply_filters( "um_profile_field_filter_hook__{$key}", $value, $data );
		$value = apply_filters( "um_profile_field_filter_hook__{$type}", $value, $data );

		return $value;
	}


	function um_profile_id() {

		if (um_get_requested_user()) {
			return um_get_requested_user();
		} else if (is_user_logged_in() && get_current_user_id()) {
			return get_current_user_id();
		}

		return 0;
	}

	/***
	 ***    @Check that temp upload is valid
	 ***/
	function um_is_temp_upload( $url ) {

		if (filter_var( $url, FILTER_VALIDATE_URL ) === false)
			$url = realpath( $url );

		if (!$url)
			return false;

		$url = explode( '/ultimatemember/temp/', $url );
		if (isset( $url[1] )) {

			if (strstr( $url[1], '../' ) || strstr( $url[1], '%' ))
				return false;

			$src = UM()->files()->upload_temp . $url[1];
			if (!file_exists( $src ))
				return false;

			return $src;
		}

		return false;
	}

	/***
	 ***    @Check that temp image is valid
	 ***/
	function um_is_temp_image( $url ) {
		$url = explode( '/ultimatemember/temp/', $url );
		if (isset( $url[1] )) {
			$src = UM()->files()->upload_temp . $url[1];
			if (!file_exists( $src ))
				return false;
			list( $width, $height, $type, $attr ) = @getimagesize( $src );
			if (isset( $width ) && isset( $height ))
				return $src;
		}

		return false;
	}


	/**
	 * Get a translated core page URL
	 *
	 * @param $post_id
	 * @param $language
	 * @return bool|false|string
	 */
	function um_get_url_for_language( $post_id, $language ) {
		if ( ! um_is_wpml_active() )
			return '';

		$lang_post_id = icl_object_id( $post_id, 'page', true, $language );

		if ( $lang_post_id != 0 ) {
			$url = get_permalink( $lang_post_id );
		} else {
			// No page found, it's most likely the homepage
			global $sitepress;
			$url = $sitepress->language_url( $language );
		}

		return $url;
	}


	/**
	 * Check if WPML is active
	 *
	 * @return bool|mixed
	 */
	function um_is_wpml_active() {
		if ( defined( 'ICL_SITEPRESS_VERSION' ) ) {
			global $sitepress;

			return $sitepress->get_setting( 'setup_complete' );
		}

		return false;
	}

	/***
	 ***    @Get core page url
	 ***/
	function um_time_diff( $time1, $time2 ) {
		return UM()->datetime()->time_diff( $time1, $time2 );
	}

	/***
	 ***    @Get user's last login timestamp
	 ***/
	function um_user_last_login_timestamp( $user_id ) {
		$value = get_user_meta( $user_id, '_um_last_login', true );
		if ($value)
			return $value;

		return '';
	}

	/***
	 ***    @Get user's last login time
	 ***/
	function um_user_last_login_date( $user_id ) {
		$value = get_user_meta( $user_id, '_um_last_login', true );
		if ($value)
			return date_i18n( 'F d, Y', $value );

		return '';
	}

	/***
	 ***    @Get user's last login (time diff)
	 ***/
	function um_user_last_login( $user_id ) {
		$value = get_user_meta( $user_id, '_um_last_login', true );
		if ($value) {
			$value = um_time_diff( $value, current_time( 'timestamp' ) );
		} else {
			$value = '';
		}

		return $value;
	}

	/***
	 ***    @Get core page url
	 ***/
	function um_get_core_page( $slug, $updated = false ) {
		$url = '';

		if (isset( UM()->config()->permalinks[$slug] )) {
			$url = get_permalink( UM()->config()->permalinks[$slug] );
			if ($updated)
				$url = add_query_arg( 'updated', esc_attr( $updated ), $url );
		}

		if (function_exists( 'icl_get_current_language' ) && icl_get_current_language() != icl_get_default_language()) {

			$url = um_get_url_for_language( UM()->config()->permalinks[$slug], icl_get_current_language() );

			if (get_post_meta( get_the_ID(), '_um_wpml_account', true ) == 1) {
				$url = get_permalink( get_the_ID() );
			}
			if (get_post_meta( get_the_ID(), '_um_wpml_user', true ) == 1) {
				$url = um_get_url_for_language( UM()->config()->permalinks[$slug], icl_get_current_language() );
			}
		}

		if ($url) {
			$url = apply_filters( 'um_get_core_page_filter', $url, $slug, $updated );

			return $url;
		}

		return '';
	}

	/***
	 ***    @boolean check if we are on UM page
	 ***/
	function is_ultimatemember() {
		global $post;
		if (isset( $post->ID ) && in_array( $post->ID, UM()->config()->permalinks ))
			return true;

		return false;
	}


	/**
	 * Check if we are on a UM Core Page or not
	 *
	 * Default um core pages slugs
	 * 'user', 'login', 'register', 'members', 'logout', 'account', 'password-reset'
	 *
	 * @param string $page UM core page slug
	 *
	 * @return bool
	 */
	function um_is_core_page( $page ) {
		global $post;

		if (isset( $post->ID ) && isset( UM()->config()->permalinks[$page] ) && $post->ID == UM()->config()->permalinks[$page])
			return true;
		if (isset( $post->ID ) && get_post_meta( $post->ID, '_um_wpml_' . $page, true ) == 1)
			return true;

		if (isset( $post->ID )) {
			$_icl_lang_duplicate_of = get_post_meta( $post->ID, '_icl_lang_duplicate_of', true );

			if (isset( UM()->config()->permalinks[$page] ) && ( ( $_icl_lang_duplicate_of == UM()->config()->permalinks[$page] && !empty( $_icl_lang_duplicate_of ) ) || UM()->config()->permalinks[$page] == $post->ID ))
				return true;
		}

		return false;
	}


	function um_is_core_post( $post, $core_page ) {
		if (isset( $post->ID ) && isset( UM()->config()->permalinks[$core_page] ) && $post->ID == UM()->config()->permalinks[$core_page])
			return true;
		if (isset( $post->ID ) && get_post_meta( $post->ID, '_um_wpml_' . $core_page, true ) == 1)
			return true;

		if (isset( $post->ID )) {
			$_icl_lang_duplicate_of = get_post_meta( $post->ID, '_icl_lang_duplicate_of', true );

			if (isset( UM()->config()->permalinks[$core_page] ) && ( ( $_icl_lang_duplicate_of == UM()->config()->permalinks[$core_page] && !empty( $_icl_lang_duplicate_of ) ) || UM()->config()->permalinks[$core_page] == $post->ID ))
				return true;
		}

		return false;
	}

	/***
	 ***    @Is core URL
	 ***/
	function um_is_core_uri() {
		$array = UM()->config()->permalinks;
		$current_url = UM()->permalinks()->get_current_url( get_option( 'permalink_structure' ) );

		if (!isset( $array ) || !is_array( $array )) return false;

		foreach ($array as $k => $id) {
			$page_url = get_permalink( $id );
			if (strstr( $current_url, $page_url ))
				return true;
		}

		return false;
	}

	/***
	 ***    @Check value of queried search in text input
	 ***/
	function um_queried_search_value( $filter, $echo = true ) {
		$value = '';
		if (isset( $_REQUEST['um_search'] )) {
			$query = UM()->permalinks()->get_query_array();
			if (isset( $query[$filter] ) && $query[$filter] != '') {
				$value = stripslashes_deep( $query[$filter] );
			}
		}

		if ($echo) {
			echo $value;

			return '';
		} else {
			return $value;
		}

	}

	/***
	 ***    @Check whether item in dropdown is selected in query-url
	 ***/
	function um_select_if_in_query_params( $filter, $val ) {
		$selected = false;

		if (isset( $_REQUEST['um_search'] )) {
			$query = UM()->permalinks()->get_query_array();

			if (isset( $query[$filter] ) && $val == $query[$filter])
				$selected = true;

			$selected = apply_filters( 'um_selected_if_in_query_params', $selected, $filter, $val );
		}

		echo $selected ? 'selected="selected"' : '';
	}

	/***
	 ***    @get styling defaults
	 ***/
	function um_styling_defaults( $mode ) {

		$new_arr = array();
		$core_form_meta_all = UM()->config()->core_form_meta_all;
		$core_global_meta_all = UM()->config()->core_global_meta_all;

		foreach ( $core_form_meta_all as $k => $v ) {
			$s = str_replace( $mode . '_', '', $k );
			if (strstr( $k, '_um_' . $mode . '_' ) && !in_array( $s, $core_global_meta_all )) {
				$a = str_replace( '_um_' . $mode . '_', '', $k );
				$b = str_replace( '_um_', '', $k );
				$new_arr[$a] = UM()->options()->get( $b );
			} else if (in_array( $k, $core_global_meta_all )) {
				$a = str_replace( '_um_', '', $k );
				$new_arr[$a] = UM()->options()->get( $a );
			}
		}

		return $new_arr;
	}

	/***
	 ***    @get meta option default
	 ***/
	function um_get_metadefault( $id ) {
		$core_form_meta_all = UM()->config()->core_form_meta_all;

		return isset( $core_form_meta_all['_um_' . $id] ) ? $core_form_meta_all['_um_' . $id] : '';
	}

	/***
	 ***    @check if a legitimate password reset request is in action
	 ***/
	function um_requesting_password_reset() {
		if (um_is_core_page( 'password-reset' ) && isset( $_POST['_um_password_reset'] ) == 1)
			return true;

		return false;
	}

	/***
	 ***    @check if a legitimate password change request is in action
	 ***/
	function um_requesting_password_change() {
		if (um_is_core_page( 'account' ) && isset( $_POST['_um_account'] ) == 1)
			return true;
        else if (isset( $_POST['_um_password_change'] ) && $_POST['_um_password_change'] == 1)
			return true;

		return false;
	}

	/***
	 ***    @boolean for account page editing
	 ***/
	function um_submitting_account_page() {
		if (isset( $_POST['_um_account'] ) && $_POST['_um_account'] == 1 && is_user_logged_in())
			return true;

		return false;
	}

	/***
	 ***    @get a user's display name
	 ***/
	function um_get_display_name( $user_id ) {
		um_fetch_user( $user_id );
		$name = um_user( 'display_name' );
		um_reset_user();

		return $name;
	}

	/***
	 ***    @get members to show in directory
	 ***/
	function um_members( $argument ) {
		return UM()->members()->results[$argument];
	}

	/**
	 * @function um_reset_user_clean()
	 *
	 * @description This function is similar to um_reset_user() with a difference that it will not use the logged-in
	 *     user data after resetting. It is a hard-reset function for all user data.
	 *
	 * @usage <?php um_reset_user_clean(); ?>
	 *
	 * @returns Clears the user data. You need to fetch a user manually after using this function.
	 *
	 * @example You can reset user data by using the following line in your code
	 *
	 * <?php um_reset_user_clean(); ?>
	 *
	 *
	 */
	function um_reset_user_clean() {
		UM()->user()->reset( true );
	}

	/**
	 * @function um_reset_user()
	 *
	 * @description This function resets the current user. You can use it to reset user data after
	 * retrieving the details of a specific user.
	 *
	 * @usage <?php um_reset_user(); ?>
	 *
	 * @returns Clears the user data. If a user is logged in, the user data will be reset to that user's data
	 *
	 * @example You can reset user data by using the following line in your code
	 *
	 * <?php um_reset_user(); ?>
	 *
	 *
	 */
	function um_reset_user() {
		UM()->user()->reset();
	}

	/***
	 ***    @gets the queried user
	 ***/
	function um_queried_user() {

		return get_query_var( 'um_user' );
	}

	/***
	 ***    @Sets the requested user
	 ***/
	function um_set_requested_user( $user_id ) {
		UM()->user()->target_id = $user_id;
	}

	/***
	 ***    @Gets the requested user
	 ***/
	function um_get_requested_user() {
		if (!empty( UM()->user()->target_id ))
			return UM()->user()->target_id;

		return false;
	}

	/***
	 ***    @remove edit profile args from url
	 ***/
	function um_edit_my_profile_cancel_uri( $url = '' ) {

		if (empty( $url )) {
			$url = remove_query_arg( 'um_action' );
			$url = remove_query_arg( 'profiletab', $url );
			$url = add_query_arg( 'profiletab', 'main', $url );
		}

		$url = apply_filters( 'um_edit_profile_cancel_uri', $url );

		return $url;
	}

	/***
	 ***    @boolean for profile edit page
	 ***/
	function um_is_on_edit_profile() {
		if (isset( $_REQUEST['profiletab'] ) && isset( $_REQUEST['um_action'] )) {
			if ($_REQUEST['profiletab'] == 'main' && $_REQUEST['um_action'] == 'edit') {
				return true;
			}
		}

		return false;
	}

	/***
	 ***    @can view field
	 ***/
	function um_can_view_field( $data ) {

		if (!isset( UM()->fields()->set_mode ))
			UM()->fields()->set_mode = '';

		if (isset( $data['public'] ) && UM()->fields()->set_mode != 'register') {

			if (!is_user_logged_in() && $data['public'] != '1') return false;

			if (is_user_logged_in()) {

				if ($data['public'] == '-3' && !um_is_user_himself() && !in_array( UM()->roles()->um_get_user_role( get_current_user_id() ), $data['roles'] ))
					return false;

				if (!um_is_user_himself() && $data['public'] == '-1' && !UM()->roles()->um_user_can( 'can_edit_everyone' ))
					return false;

				if ($data['public'] == '-2' && $data['roles'])
					if (!in_array( UM()->roles()->um_get_user_role( get_current_user_id() ), $data['roles'] ))
						return false;
			}

		}

		return true;
	}

	/***
	 ***    @checks if user can view profile
	 ***/
	function um_can_view_profile( $user_id ) {
		if (!um_user( 'can_view_all' ) && $user_id != get_current_user_id() && is_user_logged_in()) return false;

		if (UM()->roles()->um_current_user_can( 'edit', $user_id )) {
			return true;
		}

		if (!is_user_logged_in()) {
			if (UM()->user()->is_private_profile( $user_id )) {
				return false;
			} else {
				return true;
			}
		}

		if (!um_user( 'can_access_private_profile' ) && UM()->user()->is_private_profile( $user_id )) return false;

		if (UM()->roles()->um_user_can( 'can_view_roles' ) && $user_id != get_current_user_id()) {
			if (!in_array( UM()->roles()->um_get_user_role( $user_id ), UM()->roles()->um_user_can( 'can_view_roles' ) )) {
				return false;
			}
		}

		return true;

	}

	/***
	 ***    @boolean check for not same user
	 ***/
	function um_is_user_himself() {
		if (um_get_requested_user() && um_get_requested_user() != get_current_user_id())
			return false;

		return true;
	}

	/***
	 ***    @can edit field
	 ***/
	function um_can_edit_field( $data ) {
		if (isset( UM()->fields()->editing ) && UM()->fields()->editing == true &&
			isset( UM()->fields()->set_mode ) && UM()->fields()->set_mode == 'profile'
		) {

			if (is_user_logged_in() && isset( $data['editable'] ) && $data['editable'] == 0) {

				if (isset( $data['public'] ) && $data['public'] == "-2") {
					return true;
				}
				
				if (um_user( 'can_edit_everyone' )) return true;
				if (um_is_user_himself() && !um_user( 'can_edit_everyone' )) {
					return true;
				}

				if (!um_is_user_himself() && !UM()->roles()->um_user_can( 'can_edit_everyone' ))
					return false;
			}

		}

		return true;

	}


	/***
	 ***    @Check if user is in his profile
	 ***/
	function um_is_myprofile() {
		if (get_current_user_id() && get_current_user_id() == um_get_requested_user()) return true;
		if (!um_get_requested_user() && um_is_core_page( 'user' ) && get_current_user_id()) return true;

		return false;
	}


	/***
	 ***    @Returns the edit profile link
	 ***/
	function um_edit_profile_url() {
		if (um_is_core_page( 'user' )) {
			$url = UM()->permalinks()->get_current_url();
		} else {
			$url = um_user_profile_url();
		}

		$url = remove_query_arg( 'profiletab', $url );
		$url = remove_query_arg( 'subnav', $url );
		$url = add_query_arg( 'profiletab', 'main', $url );
		$url = add_query_arg( 'um_action', 'edit', $url );

		return $url;
	}

	/***
	 ***    @checks if user can edit his profile
	 ***/
	function um_can_edit_my_profile() {
		if (!is_user_logged_in()) return false;
		if (!um_user( 'can_edit_profile' )) return false;

		return true;
	}

	/***
	 ***    @short for admin e-mail
	 ***/
	function um_admin_email() {
		return UM()->options()->get( 'admin_email' );
	}

	/**
	 * @function um_get_option()
	 *
	 * @description This function returns the value of an option or setting.
	 *
	 * @usage <?php $value = um_get_option( $setting ); ?>
	 *
	 * @param $option_id (string) (required) The option or setting that you want to retrieve
	 *
	 * @returns Returns the value of the setting you requested, or a blank value if the setting
	 * does not exist.
	 *
	 * @example Get default user role set in global options
	 *
	 * <?php $default_role = um_get_option('default_role'); ?>
	 *
	 * @example Get blocked IP addresses set in backend
	 *
	 * <?php $blocked_ips = um_get_option('blocked_ips'); ?>
	 * @return mixed
	 */
	function um_get_option( $option_id ) {
		if (!isset( UM()->options ))
			return '';

		$um_options = UM()->options;
		if (!empty( $um_options[$option_id] ))
			return apply_filters( "um_get_option_filter__{$option_id}", $um_options[$option_id] );

		switch ($option_id) {

			case 'site_name':
				return get_bloginfo( 'name' );
				break;

			case 'admin_email':
				return get_bloginfo( 'admin_email' );
				break;
			default:
				return '';
				break;

		}
	}


	function um_update_option( $option_id, $value ) {
		if (!isset( UM()->options ))
			UM()->options = array();

		$um_options = UM()->options;
		$um_options[$option_id] = $value;
		UM()->options = $um_options;

		update_option( 'um_options', $um_options );
	}


	function um_remove_option( $option_id ) {
		if (!isset( UM()->options ))
			UM()->options = array();

		$um_options = UM()->options;
		if (!empty( $um_options[$option_id] ))
			unset( $um_options[$option_id] );

		UM()->options = $um_options;

		update_option( 'um_options', $um_options );
	}

	/***
	 ***    @Display a link to profile page
	 ***/
	function um_user_profile_url() {
		return UM()->user()->get_profile_url( um_user( 'ID' ) );
	}

	/***
	 ***    @Get all UM roles in array
	 ***/
	function um_get_roles() {
		return UM()->roles()->get_roles();
	}

	/**
	 * @function um_fetch_user()
	 *
	 * @description This function sets a user and allow you to retrieve any information for the retrieved user
	 *
	 * @usage <?php um_fetch_user( $user_id ); ?>
	 *
	 * @param $user_id (numeric) (required) A user ID is required. This is the user's ID that you wish to set/retrieve
	 *
	 * @returns Sets a specific user and prepares profile data and user permissions and makes them accessible.
	 *
	 * @example The example below will set user ID 5 prior to retrieving his profile information.
	 *
	 * <?php
	 *
	 * um_fetch_user(5);
	 * echo um_user('display_name'); // returns the display name of user ID 5
	 *
	 * ?>
	 *
	 * @example In the following example you can fetch the profile of a logged-in user dynamically.
	 *
	 * <?php
	 *
	 * um_fetch_user( get_current_user_id() );
	 * echo um_user('display_name'); // returns the display name of logged-in user
	 *
	 * ?>
	 *
	 */
	function um_fetch_user( $user_id ) {
		UM()->user()->set( $user_id );
	}

	/***
	 ***    @Load profile key
	 ***/
	function um_profile( $key ) {

		if (!empty( UM()->user()->profile[$key] )) {
			$value = apply_filters( "um_profile_{$key}__filter", UM()->user()->profile[$key] );
		} else {
			$value = apply_filters( "um_profile_{$key}_empty__filter", false );
		}

		return $value;

	}

	/***
	 ***    @Get youtube video ID from url
	 ***/
	function um_youtube_id_from_url( $url ) {
		$pattern =
			'%^# Match any youtube URL
			(?:https?://)?  # Optional scheme. Either http or https
			(?:www\.)?      # Optional www subdomain
			(?:             # Group host alternatives
			  youtu\.be/    # Either youtu.be,
			| youtube\.com  # or youtube.com
			  (?:           # Group path alternatives
				/embed/     # Either /embed/
			  | /v/         # or /v/
			  | /watch\?v=  # or /watch\?v=
			  )             # End path alternatives.
			)               # End host alternatives.
			([\w-]{10,12})  # Allow 10-12 for 11 char youtube id.
			$%x';
		$result = preg_match( $pattern, $url, $matches );
		if (false !== $result) {
			return $matches[1];
		}

		return false;
	}

	/***
	 ***    @user uploads uri
	 ***/
	function um_user_uploads_uri() {
		if (is_ssl()) {
			UM()->files()->upload_baseurl = str_replace( "http://", "https://", UM()->files()->upload_baseurl );
		}

		$uri = UM()->files()->upload_baseurl . um_user( 'ID' ) . '/';

		return $uri;
	}

	/***
	 ***    @user uploads directory
	 ***/
	function um_user_uploads_dir() {
		$uri = UM()->files()->upload_basedir . um_user( 'ID' ) . '/';

		return $uri;
	}

	/***
	 ***    @find closest number in an array
	 ***/
	function um_closest_num( $array, $number ) {
		sort( $array );
		foreach ($array as $a) {
			if ($a >= $number) return $a;
		}

		return end( $array );
	}

	/***
	 ***    @get cover uri
	 ***/
	function um_get_cover_uri( $image, $attrs ) {
		$uri = false;
		$ext = '.' . pathinfo( $image, PATHINFO_EXTENSION );
		if (file_exists( UM()->files()->upload_basedir . um_user( 'ID' ) . '/cover_photo' . $ext )) {
			$uri = um_user_uploads_uri() . 'cover_photo' . $ext . '?' . current_time( 'timestamp' );
		}
		if (file_exists( UM()->files()->upload_basedir . um_user( 'ID' ) . '/cover_photo-' . $attrs . $ext )) {
			$uri = um_user_uploads_uri() . 'cover_photo-' . $attrs . $ext . '?' . current_time( 'timestamp' );
		}

		return $uri;
	}

	/***
	 ***    @get avatar URL instead of image
	 ***/
	function um_get_avatar_url( $get_avatar ) {
		preg_match( '/src="(.*?)"/i', $get_avatar, $matches );

		return $matches[1];
	}

	/***
	 ***    @get avatar uri
	 ***/
	function um_get_avatar_uri( $image, $attrs ) {
		$uri = false;
		$find = false;
		$ext = '.' . pathinfo( $image, PATHINFO_EXTENSION );

		$cache_time = apply_filters( 'um_filter_avatar_cache_time', current_time( 'timestamp' ), um_user( 'ID' ) );

		if (!empty( $cache_time )) {
			$cache_time = "?{$cache_time}";
		}

		if (file_exists( UM()->files()->upload_basedir . um_user( 'ID' ) . "/profile_photo-{$attrs}{$ext}" )) {

			$uri = um_user_uploads_uri() . "profile_photo-{$attrs}{$ext}{$cache_time}";

		} else {

			$sizes = UM()->options()->get( 'photo_thumb_sizes' );
			if (is_array( $sizes )) $find = um_closest_num( $sizes, $attrs );

			if (file_exists( UM()->files()->upload_basedir . um_user( 'ID' ) . "/profile_photo-{$find}{$ext}" )) {

				$uri = um_user_uploads_uri() . "profile_photo-{$find}{$ext}{$cache_time}";

			} else if (file_exists( UM()->files()->upload_basedir . um_user( 'ID' ) . "/profile_photo{$ext}" )) {

				$uri = um_user_uploads_uri() . "profile_photo{$ext}{$cache_time}";

			}

			if ($attrs == 'original') {
				$uri = um_user_uploads_uri() . "profile_photo{$ext}{$cache_time}";
			}

		}

		return $uri;
	}


	/**
	 * Default avatar URL
	 *
	 * @return string
	 */
	function um_get_default_avatar_uri() {
		$uri = UM()->options()->get( 'default_avatar' );
		$uri = !empty( $uri['url'] ) ? $uri['url'] : '';
		if ( ! $uri ) {
			$uri = um_url . 'assets/img/default_avatar.jpg';
		} else {

			//http <-> https compatibility default avatar option of SSL was changed
			$url_array = parse_url( $uri );

			if (is_ssl() && $url_array['scheme'] == 'http') {
				$uri = str_replace( 'http://', 'https://', $uri );
			} else if (!is_ssl() && $url_array['scheme'] == 'https') {
				$uri = str_replace( 'https://', 'http://', $uri );
			}
		}

		return $uri;
	}


	/***
	 ***    @get user avatar url
	 ***/
	function um_get_user_avatar_url() {
		if (um_profile( 'profile_photo' )) {
			$avatar_uri = um_get_avatar_uri( um_profile( 'profile_photo' ), 32 );
		} else {
			$avatar_uri = um_get_default_avatar_uri();
		}

		return $avatar_uri;
	}

	/***
	 ***    @default cover
	 ***/
	function um_get_default_cover_uri() {
		$uri = UM()->options()->get( 'default_cover' );
		$uri = !empty( $uri['url'] ) ? $uri['url'] : '';
		if ($uri) {
			$uri = apply_filters( 'um_get_default_cover_uri_filter', $uri );

			return $uri;
		}

		return '';
	}

	function um_user( $data, $attrs = null ) {

		switch ($data) {

			default:

				$value = um_profile( $data );

				$value = maybe_unserialize( $value );

				if (in_array( $data, array( 'role', 'gender' ) )) {
					if (is_array( $value )) {
						$value = implode( ",", $value );
					}

					return $value;
				}

				return $value;
				break;

			case 'user_email':

				$user_email_in_meta = get_user_meta( um_user( 'ID' ), 'user_email', true );
				if ($user_email_in_meta) {
					delete_user_meta( um_user( 'ID' ), 'user_email' );
				}

				$value = um_profile( $data );

				return $value;
				break;

			case 'first_name':
			case 'last_name':

				$name = um_profile( $data );

				if ( UM()->options()->get( 'force_display_name_capitlized' ) ) {
					$name = implode( '-', array_map( 'ucfirst', explode( '-', $name ) ) );
				}

				$name = apply_filters( "um_user_{$data}_case", $name );

				return $name;

				break;

			case 'full_name':

				if (um_user( 'first_name' ) && um_user( 'last_name' )) {
					$full_name = um_user( 'first_name' ) . ' ' . um_user( 'last_name' );
				} else {
					$full_name = um_user( 'display_name' );
				}

				$full_name = UM()->validation()->safe_name_in_url( $full_name );

				// update full_name changed
				if (um_profile( $data ) !== $full_name) {
					update_user_meta( um_user( 'ID' ), 'full_name', $full_name );
				}

				return $full_name;

				break;

			case 'first_and_last_name_initial':

				$f_and_l_initial = '';

				if (um_user( 'first_name' ) && um_user( 'last_name' )) {
					$initial = um_user( 'last_name' );
					$f_and_l_initial = um_user( 'first_name' ) . ' ' . $initial[0];
				} else {
					$f_and_l_initial = um_profile( $data );
				}

				$f_and_l_initial = UM()->validation()->safe_name_in_url( $f_and_l_initial );

				if ( UM()->options()->get( 'force_display_name_capitlized' ) ) {
					$name = implode( '-', array_map( 'ucfirst', explode( '-', $f_and_l_initial ) ) );
				} else {
					$name = $f_and_l_initial;
				}

				return $name;

				break;

			case 'display_name':

				$op = UM()->options()->get( 'display_name' );

				$name = '';


				if ($op == 'default') {
					$name = um_profile( 'display_name' );
				}

				if ($op == 'nickname') {
					$name = um_profile( 'nickname' );
				}

				if ($op == 'full_name') {
					if (um_user( 'first_name' ) && um_user( 'last_name' )) {
						$name = um_user( 'first_name' ) . ' ' . um_user( 'last_name' );
					} else {
						$name = um_profile( $data );
					}
					if (!$name) {
						$name = um_user( 'user_login' );
					}
				}

				if ($op == 'sur_name') {
					if (um_user( 'first_name' ) && um_user( 'last_name' )) {
						$name = um_user( 'last_name' ) . ' ' . um_user( 'first_name' );
					} else {
						$name = um_profile( $data );
					}
				}

				if ($op == 'first_name') {
					if (um_user( 'first_name' )) {
						$name = um_user( 'first_name' );
					} else {
						$name = um_profile( $data );
					}
				}

				if ($op == 'username') {
					$name = um_user( 'user_login' );
				}

				if ($op == 'initial_name') {
					if (um_user( 'first_name' ) && um_user( 'last_name' )) {
						$initial = um_user( 'last_name' );
						$name = um_user( 'first_name' ) . ' ' . $initial[0];
					} else {
						$name = um_profile( $data );
					}
				}

				if ($op == 'initial_name_f') {
					if (um_user( 'first_name' ) && um_user( 'last_name' )) {
						$initial = um_user( 'first_name' );
						$name = $initial[0] . ' ' . um_user( 'last_name' );
					} else {
						$name = um_profile( $data );
					}
				}


				if ($op == 'field' && UM()->options()->get( 'display_name_field' ) != '') {
					$fields = array_filter( preg_split( '/[,\s]+/', UM()->options()->get( 'display_name_field' ) ) );
					$name = '';

					foreach ($fields as $field) {
						if (um_profile( $field )) {
							$name .= um_profile( $field ) . ' ';
						} else if (um_user( $field )) {
							$name .= um_user( $field ) . ' ';
						}

					}
				}

				if ( UM()->options()->get( 'force_display_name_capitlized' ) ) {
					$name = implode( '-', array_map( 'ucfirst', explode( '-', $name ) ) );
				}

				return apply_filters( 'um_user_display_name_filter', $name, um_user( 'ID' ), ( $attrs == 'html' ) ? 1 : 0 );

				break;

			case 'role_select':
			case 'role_radio':
				return UM()->roles()->get_role_name( um_user( 'role' ) );
				break;

			case 'submitted':
				$array = um_profile( $data );
				if (empty( $array )) return '';
				$array = unserialize( $array );

				return $array;
				break;

			case 'password_reset_link':
				return UM()->password()->reset_url();
				break;

			case 'account_activation_link':
				return UM()->permalinks()->activate_url();
				break;

			case 'profile_photo':

				$has_profile_photo = false;
				$photo_type = 'um-avatar-default';
				$image_alt = apply_filters( "um_avatar_image_alternate_text", um_user( "display_name" ) );

				if (um_profile( 'profile_photo' )) {
					$avatar_uri = um_get_avatar_uri( um_profile( 'profile_photo' ), $attrs );
					$has_profile_photo = true;
					$photo_type = 'um-avatar-uploaded';
				} else if (um_user( 'synced_profile_photo' )) {
					$avatar_uri = um_user( 'synced_profile_photo' );
				} else {
					$avatar_uri = um_get_default_avatar_uri();
				}

				$avatar_uri = apply_filters( 'um_user_avatar_url_filter', $avatar_uri, um_user( 'ID' ) );


				if (!$avatar_uri)
					return '';

				if ( UM()->options()->get( 'use_gravatars' ) && !um_user( 'synced_profile_photo' ) && !$has_profile_photo) {
					$avatar_hash_id = get_user_meta( um_user( 'ID' ), 'synced_gravatar_hashed_id', true );
					$avatar_uri = um_get_domain_protocol() . 'gravatar.com/avatar/' . $avatar_hash_id;
					$avatar_uri = add_query_arg( 's', 400, $avatar_uri );
					$gravatar_type = UM()->options()->get( 'use_um_gravatar_default_builtin_image' );
					$photo_type = 'um-avatar-gravatar';
					if ( $gravatar_type == 'default' ) {
						if ( UM()->options()->get( 'use_um_gravatar_default_image' ) ) {
							$avatar_uri = add_query_arg( 'd', um_get_default_avatar_uri(), $avatar_uri );
						}
					} else {
						$avatar_uri = add_query_arg( 'd', $gravatar_type, $avatar_uri );
					}

				}

				$default_avatar = um_get_default_avatar_uri();

				return '<img onerror="this.src=\''.esc_attr($default_avatar).'\';"  src="' . $avatar_uri . '" class="func-um_user gravatar avatar avatar-' . $attrs . ' um-avatar ' . $photo_type . '" width="' . $attrs . '"  height="' . $attrs . '" alt="' . $image_alt . '" />';

				break;

			case 'cover_photo':

				$is_default = false;

				if (um_profile( 'cover_photo' )) {
					$cover_uri = um_get_cover_uri( um_profile( 'cover_photo' ), $attrs );
				} else if (um_profile( 'synced_cover_photo' )) {
					$cover_uri = um_profile( 'synced_cover_photo' );
				} else {
					$cover_uri = um_get_default_cover_uri();
					$is_default = true;
				}

				$cover_uri = apply_filters( 'um_user_cover_photo_uri__filter', $cover_uri, $is_default, $attrs );

				if ($cover_uri)
					return '<img src="' . $cover_uri . '" alt="" />';

				if (!$cover_uri)
					return '';

				break;


		}

	}

	/**
	 * Get server protocol
	 *
	 * @return  string
	 */
	function um_get_domain_protocol() {

		if (is_ssl()) {
			$protocol = 'https://';
		} else {
			$protocol = 'http://';
		}

		return $protocol;
	}

	/**
	 * Set SSL to media URI
	 *
	 * @param  string $url
	 *
	 * @return string
	 */
	function um_secure_media_uri( $url ) {

		if (is_ssl()) {
			$url = str_replace( 'http:', 'https:', $url );
		}

		return $url;
	}

	/**
	 * Check if meta_value exists
	 *
	 * @param  string $key
	 * @param  mixed  $value
	 *
	 * @return integer
	 */
	function um_is_meta_value_exists( $key, $value, $return_user_id = false ) {
		global $wpdb;

		if (isset( UM()->profile()->arr_user_slugs['is_' . $return_user_id][$key] )) {
			return UM()->profile()->arr_user_slugs['is_' . $return_user_id][$key];
		}

		if (!$return_user_id) {
			$count = $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) as count FROM {$wpdb->usermeta} WHERE meta_key = %s AND meta_value = %s ",
				$key,
				$value
			) );

			UM()->profile()->arr_user_slugs['is_' . $return_user_id][$key] = $count;

			return $count;
		}

		$user_id = $wpdb->get_var( $wpdb->prepare(
			"SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = %s AND meta_value = %s ",
			$key,
			$value
		) );

		UM()->profile()->arr_user_slugs['is_' . $return_user_id][$key] = $user_id;

		return $user_id;

	}

	/**
	 * Force strings to UTF-8 encoded
	 *
	 * @param  mixed $value
	 *
	 * @return mixed
	 */
	function um_force_utf8_string( $value ) {

		if (is_array( $value )) {
			$arr_value = array();
			foreach ($value as $key => $value) {
				$utf8_decoded_value = utf8_decode( $value );

				if (mb_check_encoding( $utf8_decoded_value, 'UTF-8' )) {
					array_push( $arr_value, $utf8_decoded_value );
				} else {
					array_push( $arr_value, $value );
				}

			}

			return $arr_value;
		} else {

			$utf8_decoded_value = utf8_decode( $value );

			if (mb_check_encoding( $utf8_decoded_value, 'UTF-8' )) {
				return $utf8_decoded_value;
			}
		}

		return $value;
	}

	/**
	 * Filters the search query.
	 *
	 * @param  string $search
	 *
	 * @return string
	 */
	function um_filter_search( $search ) {
		$search = trim( strip_tags( $search ) );
		$search = preg_replace( '/[^a-z \.\@\_\-]+/i', '', $search );

		return $search;
	}

	/**
	 * Returns the user search query
	 *
	 * @return string
	 */
	function um_get_search_query() {
		$query = UM()->permalinks()->get_query_array();
		$search = isset( $query['search'] ) ? $query['search'] : '';

		return um_filter_search( $search );
	}

	/**
	 * Returns the ultimate member search form
	 *
	 * @return string
	 */
	function um_get_search_form() {

		return do_shortcode( '[ultimatemember_searchform]' );
	}

	/**
	 * Display the search form.
	 *
	 * @return string
	 */
	function um_search_form() {

		echo um_get_search_form();
	}

	/**
	 * Get localization
	 *
	 * @return string
	 */
	function um_get_locale() {

		$lang_code = get_locale();

		if (strpos( $lang_code, 'en_' ) > -1 || empty( $lang_code ) || $lang_code == 0) {
			return 'en';
		}

		return $lang_code;
	}

	/**
	 * Get current page type
	 *
	 * @return string
	 */
	function um_get_current_page_type() {
		global $wp_query;
		$loop = 'notfound';

		if ($wp_query->is_page) {
			//$loop = is_front_page() ? 'front' : 'page';
			$loop = 'page';
		} else if ($wp_query->is_home) {
			$loop = 'home';
		} else if ($wp_query->is_single) {
			$loop = ( $wp_query->is_attachment ) ? 'attachment' : 'single';
		} else if ($wp_query->is_category) {
			$loop = 'category';
		} else if ($wp_query->is_tag) {
			$loop = 'tag';
		} else if ($wp_query->is_tax) {
			$loop = 'tax';
		} else if ($wp_query->is_archive) {
			if ($wp_query->is_day) {
				$loop = 'day';
			} else if ($wp_query->is_month) {
				$loop = 'month';
			} else if ($wp_query->is_year) {
				$loop = 'year';
			} else if ($wp_query->is_author) {
				$loop = 'author';
			} else {
				$loop = 'archive';
			}
		} else if ($wp_query->is_search) {
			$loop = 'search';
		} else if ($wp_query->is_404) {
			$loop = 'notfound';
		}

		return $loop;
	}

	/**
	 * Check if running local
	 *
	 * @return boolean
	 */
	function um_core_is_local() {
		if ($_SERVER['HTTP_HOST'] == 'localhost'
			|| substr( $_SERVER['HTTP_HOST'], 0, 3 ) == '10.'
			|| substr( $_SERVER['HTTP_HOST'], 0, 7 ) == '192.168'
		) return true;

		return false;
	}

	/**
	 * Get user host
	 *
	 * Returns the webhost this site is using if possible
	 *
	 * @since 1.3.68
	 * @return mixed string $host if detected, false otherwise
	 */
	function um_get_host() {
		$host = false;

		if (defined( 'WPE_APIKEY' )) {
			$host = 'WP Engine';
		} else if (defined( 'PAGELYBIN' )) {
			$host = 'Pagely';
		} else if (DB_HOST == 'localhost:/tmp/mysql5.sock') {
			$host = 'ICDSoft';
		} else if (DB_HOST == 'mysqlv5') {
			$host = 'NetworkSolutions';
		} else if (strpos( DB_HOST, 'ipagemysql.com' ) !== false) {
			$host = 'iPage';
		} else if (strpos( DB_HOST, 'ipowermysql.com' ) !== false) {
			$host = 'IPower';
		} else if (strpos( DB_HOST, '.gridserver.com' ) !== false) {
			$host = 'MediaTemple Grid';
		} else if (strpos( DB_HOST, '.pair.com' ) !== false) {
			$host = 'pair Networks';
		} else if (strpos( DB_HOST, '.stabletransit.com' ) !== false) {
			$host = 'Rackspace Cloud';
		} else if (strpos( DB_HOST, '.sysfix.eu' ) !== false) {
			$host = 'SysFix.eu Power Hosting';
		} else if (strpos( $_SERVER['SERVER_NAME'], 'Flywheel' ) !== false) {
			$host = 'Flywheel';
		} else {
			// Adding a general fallback for data gathering
			$host = 'DBH: ' . DB_HOST . ', SRV: ' . $_SERVER['SERVER_NAME'];
		}

		return $host;
	}

	/**
	 * Let To Num
	 *
	 * Does Size Conversions
	 *
	 * @since 1.3.68
	 * @author Chris Christoff
	 *
	 * @param unknown $v
	 *
	 * @return int|string
	 */
	function um_let_to_num( $v ) {
		$l = substr( $v, -1 );
		$ret = substr( $v, 0, -1 );

		switch (strtoupper( $l )) {
			case 'P': // fall-through
			case 'T': // fall-through
			case 'G': // fall-through
			case 'M': // fall-through
			case 'K': // fall-through
				$ret *= 1024;
				break;
			default:
				break;
		}

		return $ret;
	}

