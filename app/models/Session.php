<?php
/**
 * Class Session
 *
 * Session data model
 */
class Session extends Model
{
	private $id;
	protected $session_key;
	protected $name;
	protected $DateStart;
	protected $DateEnd;
	protected $title;
	protected $comments;
	protected $expiry;


	/**
	 * Available user levels
	 *   0   - guest
	 *   >=1 - registered
	 *   3   - admin (only for session with ID = 1)
	 *
	 * @var array
	 */
	private static $user_levels = array(0,1,2,3);

	/**
	 * User level
	 *
	 * @var integer
	 */
	private $user_level = 1;

	public function __construct()
	{
		parent::__construct();
		self::generateKey();
		$this->DateStart = time();
		$this->expiry = 0;
	}

	public function save()
	{

		if(isset($this->id))
		{
			$result = $this->db->query("update sessions set
										session_key = " . $this->db->quote($this->session_key) . ",
										name = " . $this->db->quote($this->name) . ",
										DateStart = " . $this->db->quote($this->DateStart) . ",
										DateEnd = " . $this->db->quote($this->DateEnd) . ",
										title = " . $this->db->quote($this->title) . ",
										comments = " . $this->db->quote($this->comments) . ",
										expiry = " . $this->db->quote($this->expiry) . "
									where id = " . $this->db->quote($this->id)
			);
		}
		else
		{
			$result = $this->db->query("insert into sessions (
													session_key,
													name,
													DateStart,
													DateEnd,
													title,
													comments,
													expiry
												) values (
													" . $this->db->quote($this->session_key) . ",
													" . $this->db->quote($this->name) . ",
													" . $this->db->quote($this->DateStart) . ",
													" . $this->db->quote($this->DateEnd) . ",
													" . $this->db->quote($this->title) . ",
													" . $this->db->quote($this->comments) . ",
													" . $this->db->quote($this->expiry) . "
												)"
			);
			$this->id = $this->db->lastInsertId();
		}

		if($result) return true;
	}

	public function load($key)
	{
		$row = $this->db->query("select * from sessions where session_key = " . $this->db->quote($key));
		$session = $row->fetch(PDO::FETCH_OBJ);

		if($session)
		{
			$this->id = $session->id;
			$this->session_key = $session->session_key;

			$this->name = !empty($session->name) ? $session->name : L::session_NAME_NOT_PROVIDE;
			if(!empty($session->DateStart)) $this->DateStart = $session->DateStart;
			$this->DateEnd = $session->DateEnd;
			$this->title = !empty($session->title) ? $session->title : L::session_WITHOUT_NAME;
			if(!empty($session->comments)) $this->comments = $session->comments;
			if(!empty($session->expiry)) $this->expiry = $session->expiry;

			if($this->id == 1) $this->user_level = 3;
			return $this;
		}
		else
		{
			return false;
		}

	}

	/**
	 *
	 */
	private function generateKey()
	{
		$key = '';
		do
		{
			for($i= 1; $i <= 6; $i++)
			{
				$key .= rand(0,9);
			}

		}
		while(self::keyExists($key));

		$this->session_key = $key;
	}

	/**
	 * @return mixed
	 */
	public function getKey()
	{
		return $this->session_key;
	}

	/**
	 * @param $key
	 * @return bool
	 */
	public static function keyExists($key)
	{
		$dbh = (new DB())->query("select id from sessions where session_key = '$key' limit 1");
		$result = $dbh->fetch(PDO::FETCH_OBJ);
		if($result)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * @return int
	 */
	public function getUserLevel()
	{
		return $this->user_level;
	}


	public function setSession()
	{
		$_SESSION['sdlab'] = array(
			'id' => $this->id,
			'session_key' => $this->session_key,
			'title' => $this->title,
			'name' => $this->name,
			'comments' => $this->comments
		);
	}
	public static function destroySession()
	{
		unset($_SESSION['sdlab']);
	}


	/**
	 * Get available user levels list
	 * 
	 * @param   boolean  $used  Get only used levels (true) or all available (false)
	 * 
	 * @return  array
	 */
	public static function getUserLevels($used = false)
	{
		$levels = array();

		if ($used)
		{
			// TODO: add new level field to sessions table
			/*
			$db = (new DB())->query("select distinct level from sessions");
			$rows = $db->fetch(PDO::FETCH_COLUMN);
			if(!empty($rows))
			{
				$levels = $rows;
			}
			*/
			$levels = array(1,3);
		}
		else
		{
			$levels = self::$user_levels;
		}

		return $levels;
	}
}
