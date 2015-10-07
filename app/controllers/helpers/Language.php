<?
/**
 * Class Lang
 * Override i18n class
 * Helper for multilanguage interface
 * 
 * @author Aleksey Odoevsky (alodos)
 * @link https://github.com/alodos/sd-php-i18n
 * @copyright Copyright 2015-2016 Aleksey Odoevsky
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 * @package Sdlab
 */

require_once LIBRARIES . '/php-i18n/i18n.class.php';

class Language extends i18n
{
	protected static $i18n = array();

	/**
	 * Get language object instance
	 *
	 * @param string $fallbackLang  This is the language which is used when there is no language file for all other user languages. It has the lowest priority.
	 * @param string $forcedLang    Forced language is the language which is used first. It has the highest priority. Set to null if not use it.
	 * @param string $prefix        The class name of the compiled class that contains the translated texts. Defaults to 'L'.
	 * @param string $save          If need to save language configuration for user as default language. Boolean or string 'auto'(save only from GET or if $forcedLang set). Defaults to 'auto'.
	 * 
	 * @return mixed Language object or False on error
	 */
	public static function getInstance($fallbackLang = 'en', $forcedLang = null, $prefix = null, $save = 'auto')
	{
		// Check if already get instance with the save parameters
		$id = md5('' . $fallbackLang . $forcedLang . $prefix);
		if (isset(self::$i18n[$id]))
		{
			return self::$i18n[$id];
		}

		// New instance
		self::$i18n[$id] = new Language(APP . '/lang/lang_{LANGUAGE}.ini', APP . '/langcache/', $fallbackLang);

		if ($prefix != null)
		{
			self::$i18n[$id]->setPrefix($prefix);
		}

		if ($forcedLang != null)
		{
			self::$i18n[$id]->setForcedLang($forcedLang);
		}

		try
		{
			// Load all lang files and translations
			self::$i18n[$id]->init();
		}
		catch (Exception $e)
		{
			error_log($e->getMessage());
			return false;
		}

		if ($save === 'auto')
		{
			// Save language config for user only on GET request or force initiated
			$method = self::$i18n[$id]->getAppliedLangFrom();
			if (/*$method == 'POST' ||*/ $method == 'GET' || $method == 'force')
			{
				self::$i18n[$id]->saveUserLang();
			}
		}
		else if ($save)
		{
			self::$i18n[$id]->saveUserLang();
		}

		return self::$i18n[$id];
	}


	/**
	 * Override getUserLangs() for changing lang detect methods
	 * Returns the user languages
	 * Normally it returns an array like this:
	 * 1. Forced language
	 * 2. Language in $_GET['lang']
	 * 3. Language in $_COOKIE['lang']
	 * 4. Fallback language
	 * Note: duplicate values are deleted.
	 *
	 * @return array with the user languages sorted by priority.
	 */
	public function getUserLangs()
	{
		$userLangs = array();

		// Highest priority: forced language
		if ($this->forcedLang != NULL)
		{
			$userLangs['force'] = $this->forcedLang;
		}

		// TODO: get lang from POST parameter
		/*
		// 2nd highest priority: POST parameter 'lang'
		if (isset($_POST['lang']) && is_string($_POST['lang']))
		{
			$userLangs['POST'] = $_POST['lang'];
		}
		*/

		// 2nd highest priority: GET parameter 'lang'
		if (isset($_GET['lang']) && is_string($_GET['lang']))
		{
			$userLangs['GET'] = $_GET['lang'];
		}

		// 3rd priority: COOKIE parameter 'lang'?
		if (isset($_COOKIE["lang"]))
		{
			$userLangs['COOKIE'] = $_COOKIE["lang"];
		}

		/*
		// 4th highest priority: SESSION parameter 'lang'
		if (isset($_SESSION['lang']) && is_string($_SESSION['lang']))
		{
			$userLangs['SESSION'] = $_SESSION['lang'];
		}
		*/

		// Lowest priority: fallback
		$userLangs['default'] = $this->fallbackLang;

		// remove duplicate elements
		//$userLangs = array_unique($userLangs);

		foreach ($userLangs as $key => $value)
		{
			$userLangs[$key] = preg_replace('/[^a-zA-Z0-9_-]/', '', $value); // only allow a-z, A-Z and 0-9
		}

		return $userLangs;
	}


	/**
	 * Save new user lang to cookie if is set
	 * 
	 * @return boolean True on success, false on error
	 */
	public function saveUserLang()
	{
		$appliedLang = $this->getAppliedLang();
		if ($appliedLang == null)
		{
			return false;
		}

		// TODO: infinite lang cookie?
		//return setcookie('lang', $appliedLang, time() - 42000, '/', null, null, true);
		return setcookie('lang', $appliedLang, time() + 60*60*24*30, '/', null, null, true);
	}


	/**
	 * Get list of available languages
	 *
	 * @return array  Array of langcodes of available languages
	 */
	public function getLanguages()
	{
		// Search for language files
		@set_time_limit(ini_get('max_execution_time'));

		$arr = array();
		$path = dirname($this->filePath);
		$fname = basename($this->filePath);
		$filter = str_replace(array('_', '.', '{LANGUAGE}'), array('\_', '\.' ,'[a-zA-Z\-\_]{2,5}'), $fname);

		// Read the source directory
		if (!($handle = @opendir($path)))
		{
			return $arr;
		}

		while (($file = readdir($handle)) !== false)
		{
			if ($file != '.' && $file != '..')
			{
				// Compute the fullpath
				$fullpath = $path . '/' . $file;

				// Compute the isDir flag
				if (!(is_file($fullpath) && preg_match("/$filter/", $file)))
				{
					continue;
				}

				// Filename is requested
				$filter2 = str_replace(array('_', '{LANGUAGE}'), array('\_','([a-zA-Z\-\_]{2,5})'), $fname);
				preg_match("/$filter2/", $file, $matches);
				if (isset($matches[1]) && (strlen($matches[1]) > 0))
				{
					$arr[] = $matches[1];
				}
			}
		}

		closedir($handle);

		return $arr;
	}


	/**
	 * Render language switcher
	 *
	 * @param  string  Active i18n langcode (example: en, ru, en_gb, and etc.)
	 *
	 * @return string  Html
	 */
	public function render($active = null)
	{
		if ($active === null)
		{
			$active = $this->getAppliedLang();
		}

		$html = '';

		$langs = $this->getLanguages();
		if (count($langs))
		{
			$imgpath = 'assets/images/lang';
			$html .= '<ul class="nav navbar-nav lang-bar">';
			foreach ($langs as $lang)
			{
				$link = '?q=page/view';
				$html .= '<li ' . (($active === $lang) ? 'class="active"' : '') . '><a href="' . $link . '&lang=' . strtolower($lang) . '" class="btn btn-link btn-xs">'
							. '<img width="18" class="" src="' . $imgpath . '/' . $lang . '.gif">'
							. '<span class="hidden-xs">' . strtoupper($lang) . '</span>'
						. '</a></li>';
			}
			$html .= '</ul>';
		}
		return $html;
	}
}
