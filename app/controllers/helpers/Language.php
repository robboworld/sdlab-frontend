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
	protected static $i18n;

	/**
	 * Get language object instance
	 *
	 * @param string $default  Default language
	 * 
	 * @return mixed Language object or False on error
	 */
	public static function getInstance($fallbackLang = 'en', $forcedLang = null, $prefix = null, $save = true)
	{
		if (!empty($i18n))
		{
			return $i18n;
		}

		self::$i18n = new Language(APP . '/lang/lang_{LANGUAGE}.ini', APP . '/langcache/', $fallbackLang);

		if ($prefix != null)
		{
			self::$i18n->setPrefix($prefix);
		}

		if ($forcedLang != null)
		{
			self::$i18n->setForcedLang($forcedLang);
		}

		try
		{
			// Load all lang files and translations
			self::$i18n->init();
		}
		catch (Exception $e)
		{
			error_log($e->getMessage());
			return false;
		}

		if ($save)
		{
			self::$i18n->saveUserLang();
		}

		return self::$i18n;
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
			$userLangs[] = $this->forcedLang;
		}

		// 2nd highest priority: GET parameter 'lang'
		if (isset($_GET['lang']) && is_string($_GET['lang']))
		{
			$userLangs[] = $_GET['lang'];
		}
		/*
		// TODO: get lang from cookie sdlab.lang
		// 3rd highest priority: SESSION parameter 'lang'
		if (isset($_SESSION['lang']) && is_string($_SESSION['lang']))
		{
			$userLangs[] = $_SESSION['lang'];
		}
		*/

		// Lowest priority: fallback
		$userLangs[] = $this->fallbackLang;

		// remove duplicate elements
		$userLangs = array_unique($userLangs);

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
		$userLangs = array();

		// Highest priority: forced language
		if ($this->forcedLang != NULL)
		{
			$userLangs[] = $this->forcedLang;
		}

		// 2nd highest priority: GET parameter 'lang'
		if (isset($_GET['lang']) && is_string($_GET['lang']))
		{
			$userLangs[] = $_GET['lang'];
		}

		// Lowest priority: fallback
		$userLangs[] = $this->fallbackLang;

		// remove duplicate elements
		$userLangs = array_unique($userLangs);

		foreach ($userLangs as $key => $value)
		{
			$userLangs[$key] = preg_replace('/[^a-zA-Z0-9_-]/', '', $value); // only allow a-z, A-Z and 0-9
			if (strlen($userLangs[$key]) === 0)
			{
				unset($userLangs[$key]);
			}
		}
		if(empty($userLangs[$key]))
		{
			return false;
		}

		// TODO: save lang to cookie sdlab.lang
		// set cookie sdlab.lang = $userLangs[0]

		return true;
	}
}
