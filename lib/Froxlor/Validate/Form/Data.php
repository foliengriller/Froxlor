<?php
namespace Froxlor\Validate\Form;

class Data
{

	public static function validateFormFieldString($fieldname, $fielddata, $newfieldvalue)
	{
		if (isset($fielddata['string_delimiter']) && $fielddata['string_delimiter'] != '') {
			$newfieldvalues = array_map('trim', explode($fielddata['string_delimiter'], $newfieldvalue));
			unset($fielddata['string_delimiter']);

			$returnvalue = true;
			foreach ($newfieldvalues as $single_newfieldvalue) {
				/**
				 * don't use tabs in value-fields, #81
				 */
				$single_newfieldvalue = str_replace("\t", " ", $single_newfieldvalue);
				$single_returnvalue = self::validateFormFieldString($fieldname, $fielddata, $single_newfieldvalue);
				if ($single_returnvalue !== true) {
					$returnvalue = $single_returnvalue;
					break;
				}
			}
		} else {
			$returnvalue = false;

			/**
			 * don't use tabs in value-fields, #81
			 */
			$newfieldvalue = str_replace("\t", " ", $newfieldvalue);

			if (isset($fielddata['string_type']) && $fielddata['string_type'] == 'mail') {
				$returnvalue = (filter_var($newfieldvalue, FILTER_VALIDATE_EMAIL) == $newfieldvalue);
			} elseif (isset($fielddata['string_type']) && $fielddata['string_type'] == 'url') {
				$returnvalue = self::validateUrl($newfieldvalue);
			} elseif (isset($fielddata['string_type']) && $fielddata['string_type'] == 'dir') {
				// check for empty value (it might be allowed)
				if (trim($newfieldvalue) == '') {
					$newfieldvalue = '';
					$returnvalue = 'stringmustntbeempty';
				} else {
					// add trailing slash to validate path if needed
					// refs #331
					if (substr($newfieldvalue, - 1) != '/') {
						$newfieldvalue .= '/';
					}
					$returnvalue = ($newfieldvalue == \Froxlor\FileDir::makeCorrectDir($newfieldvalue));
				}
			} elseif (isset($fielddata['string_type']) && $fielddata['string_type'] == 'confdir') {
				// check for empty value (it might be allowed)
				if (trim($newfieldvalue) == '') {
					$newfieldvalue = '';
					$returnvalue = 'stringmustntbeempty';
				} else {
					// add trailing slash to validate path if needed
					// refs #331
					if (substr($newfieldvalue, - 1) != '/') {
						$newfieldvalue .= '/';
					}
					// if this is a configuration directory, check for stupidity of admins :p
					if (\Froxlor\FileDir::checkDisallowedPaths($newfieldvalue) !== true) {
						$newfieldvalue = '';
						$returnvalue = 'givendirnotallowed';
					} else {
						$returnvalue = ($newfieldvalue == \Froxlor\FileDir::makeCorrectDir($newfieldvalue));
					}
				}
			} elseif (isset($fielddata['string_type']) && $fielddata['string_type'] == 'file') {
				// check for empty value (it might be allowed)
				if (trim($newfieldvalue) == '') {
					$newfieldvalue = '';
					$returnvalue = 'stringmustntbeempty';
				} else {
					$returnvalue = ($newfieldvalue == \Froxlor\FileDir::makeCorrectFile($newfieldvalue));
				}
			} elseif (isset($fielddata['string_type']) && $fielddata['string_type'] == 'filedir') {
				// check for empty value (it might be allowed)
				if (trim($newfieldvalue) == '') {
					$newfieldvalue = '';
					$returnvalue = 'stringmustntbeempty';
				} else {
					$returnvalue = (($newfieldvalue == \Froxlor\FileDir::makeCorrectDir($newfieldvalue)) || ($newfieldvalue == \Froxlor\FileDir::makeCorrectFile($newfieldvalue)));
				}
			} elseif (isset($fielddata['string_type']) && $fielddata['string_type'] == 'validate_ip') {
				// check for empty value (it might be allowed)
				if (trim($newfieldvalue) == '') {
					$newfieldvalue = '';
					$returnvalue = 'stringmustntbeempty';
				} else {
					$newfieldvalue = \Froxlor\Validate\Validate::validate_ip2($newfieldvalue, true);
					$returnvalue = ($newfieldvalue !== false ? true : 'invalidip');
				}
			} elseif (isset($fielddata['string_type']) && $fielddata['string_type'] == 'validate_ip_incl_private') {
				// check for empty value (it might be allowed)
				if (trim($newfieldvalue) == '') {
					$newfieldvalue = '';
					$returnvalue = 'stringmustntbeempty';
				} else {
					$newfieldvalue = \Froxlor\Validate\Validate::validate_ip2($newfieldvalue, true, 'invalidip', true, true, true);
					$returnvalue = ($newfieldvalue !== false ? true : 'invalidip');
				}
			} elseif (preg_match('/^[^\r\n\t\f\0]*$/D', $newfieldvalue)) {
				$returnvalue = true;
			}

			if (isset($fielddata['string_regexp']) && $fielddata['string_regexp'] != '') {
				if (preg_match($fielddata['string_regexp'], $newfieldvalue)) {
					$returnvalue = true;
				} else {
					$returnvalue = false;
				}
			}

			if (isset($fielddata['string_emptyallowed']) && $fielddata['string_emptyallowed'] === true && $newfieldvalue === '') {
				$returnvalue = true;
			} elseif (isset($fielddata['string_emptyallowed']) && $fielddata['string_emptyallowed'] === false && $newfieldvalue === '') {
				$returnvalue = 'stringmustntbeempty';
			}
		}

		if ($returnvalue === true) {
			return true;
		} elseif ($returnvalue === false) {
			return 'stringformaterror';
		} else {
			return $returnvalue;
		}
	}

	/**
	 * Returns whether a URL is in a correct format or not
	 *
	 * @param string $url
	 *        	URL to be tested
	 * @return bool
	 * @author Christian Hoffmann
	 * @author Froxlor team <team@froxlor.org> (2010-)
	 *        
	 */
	public static function validateUrl($url)
	{
		if (strtolower(substr($url, 0, 7)) != "http://" && strtolower(substr($url, 0, 8)) != "https://") {
			$url = 'http://' . $url;
		}

		// needs converting
		try {
			$idna_convert = new \Froxlor\Idna\IdnaWrapper();
			$url = $idna_convert->encode($url);
		} catch (\Exception $e) {
			return false;
		}

		$pattern = '%^(?:(?:https?)://)(?:\S+(?::\S*)?@)?(?:(?!10(?:\.\d{1,3}){3})(?!127(?:\.\d{1,3}){3})(?!169\.254(?:\.\d{1,3}){2})(?!192\.168(?:\.\d{1,3}){2})(?!172\.(?:1[6-9]|2\d|3[0-1])(?:\.\d{1,3}){2})(?:[1-9]\d?|1\d\d|2[01]\d|22[0-3])(?:\.(?:1?\d{1,2}|2[0-4]\d|25[0-5])){2}(?:\.(?:[1-9]\d?|1\d\d|2[0-4]\d|25[0-4]))|(?:(?:[a-z\x{00a1}-\x{ffff}0-9]+-?)*[a-z\x{00a1}-\x{ffff}0-9]+)(?:\.(?:[a-z\x{00a1}-\x{ffff}0-9]+-?)*[a-z\x{00a1}-\x{ffff}0-9]+)*(?:\.(?:[a-z\x{00a1}-\x{ffff}]{2,})))(?::\d{2,5})?(?:/[^\s]*)?$%iuS';
		if (preg_match($pattern, $url)) {
			return true;
		}

		// not an fqdn
		if (strtolower(substr($url, 0, 7)) == "http://" || strtolower(substr($url, 0, 8)) == "https://") {
			if (strtolower(substr($url, 0, 7)) == "http://") {
				$ip = strtolower(substr($url, 7));
			}

			if (strtolower(substr($url, 0, 8)) == "https://") {
				$ip = strtolower(substr($url, 8));
			}

			$ip = substr($ip, 0, strpos($ip, '/'));
			// possible : in IP (when a port is given), #1173
			// but only if there actually IS ONE
			if (strpos($ip, ':') !== false) {
				$ip = substr($ip, 0, strpos($ip, ':'));
			}

			if (\Froxlor\Validate\Validate::validate_ip2($ip, true) !== false) {
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	public static function validateFormFieldBool($fieldname, $fielddata, $newfieldvalue)
	{
		if ($newfieldvalue === '1' || $newfieldvalue === 1 || $newfieldvalue === true || strtolower($newfieldvalue) === 'yes' || strtolower($newfieldvalue) === 'ja' || $newfieldvalue === '0' || $newfieldvalue === 0 || $newfieldvalue === false || strtolower($newfieldvalue) === 'no' || strtolower($newfieldvalue) === 'nein' || strtolower($newfieldvalue) === '') {
			return true;
		} else {
			return 'noboolean';
		}
	}

	public static function validateFormFieldDate($fieldname, $fielddata, $newfieldvalue)
	{
		if ($newfieldvalue == '0000-00-00' || preg_match('/^(19|20)\d\d[-](0[1-9]|1[012])[-](0[1-9]|[12][0-9]|3[01])$/', $newfieldvalue)) {
			$returnvalue = true;
		} else {
			$returnvalue = false;
		}

		return $returnvalue;
	}

	public static function validateFormFieldFile($fieldname, $fielddata, $newfieldvalue)
	{
		return true;
	}

	public static function validateFormFieldHidden($fieldname, $fielddata, $newfieldvalue)
	{
		/**
		 * don't show error on cronjob-timestamps changing
		 * because it might be possible that the cronjob ran
		 * while settings have been edited (bug #52)
		 */
		if ($newfieldvalue === $fielddata['value'] || $fieldname == 'system_last_tasks_run' || $fieldname == 'system_last_traffic_run' || $fieldname == 'system_lastcronrun') {
			return true;
		} else {
			return 'hiddenfieldvaluechanged';
		}
	}

	public static function validateFormFieldHiddenString($fieldname, $fielddata, $newfieldvalue)
	{
		if (isset($fielddata['string_delimiter']) && $fielddata['string_delimiter'] != '') {
			$newfieldvalues = explode($fielddata['string_delimiter'], $newfieldvalue);
			unset($fielddata['string_delimiter']);

			$returnvalue = true;
			foreach ($newfieldvalues as $single_newfieldvalue) {
				/**
				 * don't use tabs in value-fields, #81
				 */
				$single_newfieldvalue = str_replace("\t", " ", $single_newfieldvalue);
				$single_returnvalue = \Froxlor\Validate\Form\Data::validateFormFieldString($fieldname, $fielddata, $single_newfieldvalue);
				if ($single_returnvalue !== true) {
					$returnvalue = $single_returnvalue;
					break;
				}
			}
		} else {
			$returnvalue = false;

			/**
			 * don't use tabs in value-fields, #81
			 */
			$newfieldvalue = str_replace("\t", " ", $newfieldvalue);

			if (isset($fielddata['string_type']) && $fielddata['string_type'] == 'mail') {
				$returnvalue = (filter_var($newfieldvalue, FILTER_VALIDATE_EMAIL) == $newfieldvalue);
			} elseif (isset($fielddata['string_type']) && $fielddata['string_type'] == 'url') {
				$returnvalue = \Froxlor\Validate\Form\Data::validateUrl($newfieldvalue);
			} elseif (isset($fielddata['string_type']) && $fielddata['string_type'] == 'dir') {
				// add trailing slash to validate path if needed
				// refs #331
				if (substr($newfieldvalue, - 1) != '/') {
					$newfieldvalue .= '/';
				}
				$returnvalue = ($newfieldvalue == \Froxlor\FileDir::makeCorrectDir($newfieldvalue));
			} elseif (isset($fielddata['string_type']) && $fielddata['string_type'] == 'file') {
				$returnvalue = ($newfieldvalue == \Froxlor\FileDir::makeCorrectFile($newfieldvalue));
			} elseif (isset($fielddata['string_type']) && $fielddata['string_type'] == 'filedir') {
				$returnvalue = (($newfieldvalue == \Froxlor\FileDir::makeCorrectDir($newfieldvalue)) || ($newfieldvalue == \Froxlor\FileDir::makeCorrectFile($newfieldvalue)));
			} elseif (preg_match('/^[^\r\n\t\f\0]*$/D', $newfieldvalue)) {
				$returnvalue = true;
			}

			if (isset($fielddata['string_regexp']) && $fielddata['string_regexp'] != '') {
				if (preg_match($fielddata['string_regexp'], $newfieldvalue)) {
					$returnvalue = true;
				} else {
					$returnvalue = false;
				}
			}

			if (isset($fielddata['string_emptyallowed']) && $fielddata['string_emptyallowed'] === true && $newfieldvalue === '') {
				$returnvalue = true;
			} elseif (isset($fielddata['string_emptyallowed']) && $fielddata['string_emptyallowed'] === false && $newfieldvalue === '') {
				$returnvalue = 'stringmustntbeempty';
			}
		}

		if ($returnvalue === true) {
			return true;
		} elseif ($returnvalue === false) {
			return 'stringformaterror';
		} else {
			return $returnvalue;
		}
	}

	public static function validateFormFieldInt($fieldname, $fielddata, $newfieldvalue)
	{
		if (isset($fielddata['int_min']) && (int) $newfieldvalue < (int) $fielddata['int_min']) {
			return ('intvaluetoolow');
		}

		if (isset($fielddata['int_max']) && (int) $newfieldvalue > (int) $fielddata['int_max']) {
			return ('intvaluetoohigh');
		}

		return true;
	}

	public static function validateFormFieldOption($fieldname, $fielddata, $newfieldvalue)
	{
		$returnvalue = true;

		if (isset($fielddata['option_mode']) && $fielddata['option_mode'] == 'multiple') {
			$options = explode(',', $newfieldvalue);
			foreach ($options as $option) {
				$returnvalue = ($returnvalue && isset($fielddata['option_options'][$option]));
			}
		} else {
			$returnvalue = isset($fielddata['option_options'][$newfieldvalue]);
		}

		if ($returnvalue === true || $fielddata['visible'] == false) {
			return true;
		} else {
			if (isset($fielddata['option_emptyallowed']) && $fielddata['option_emptyallowed']) {
				return true;
			}
			return 'not in option';
		}
	}

	public static function validateFormFieldText($fieldname, $fielddata, $newfieldvalue)
	{
		$returnvalue = 'stringformaterror';

		if (preg_match('/^[^\0]*$/', $newfieldvalue)) {
			$returnvalue = true;
		}

		return $returnvalue;
	}
}
