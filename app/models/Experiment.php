<?php
/**
 * Class Experiment
 * 
 * Experiment model
 */
class Experiment extends Model
{
	protected $id;
	protected $session_key;
	protected $title;
	protected $setup_id;
	protected $DateStart_exp;
	protected $DateEnd_exp;
	protected $comments;

	private $sql_load = 'select * from experiments where id = :id';
	private $sql_insert = 'insert into experiments
										(session_key, title, setup_id, DateStart_exp, DateEnd_exp, comments)
										values
										(:session_key, :title, :setup_id, :DateStart_exp, :DateEnd_exp, :comments)';

	private $sql_update = 'update experiments set
										session_key = :session_key,
										title = :title,
										setup_id = :setup_id,
										DateStart_exp = :DateStart_exp,
										DateEnd_exp = :DateEnd_exp,
										comments = :comments
									where id = :id';

	public function __construct($session_key = null)
	{
		if(is_numeric($session_key) && Session::keyExists($session_key))
		{
			$this->session_key = $session_key;
		}
		else
		{
			$this->session_key = null;
		}

		$this->id = null;
		$this->title = null;
		$this->setup_id = null;
		$this->DateStart_exp = null;
		$this->DateEnd_exp = null;
		$this->comments = null;

		parent::__construct();

	}

	public function load($id)
	{
		if(is_numeric($id))
		{
			$load = $this->db->prepare($this->sql_load);
			$load->execute(array(
				':id' => $id
			));
			$experiment = $load->fetch(PDO::FETCH_OBJ);
			if($experiment)
			{
				foreach($experiment as $key => $value)
				{
					$this->$key = $experiment->$key;
				}
				return $this;
			}
			return false;
		}
		return false;
	}

	public function save()
	{

		if(!is_null($this->id))
		{
			$update = $this->db->prepare($this->sql_update);
			$result = $update->execute(array(
				':id' => $this->id,
				':session_key' => $this->session_key,
				':title' => $this->title,
				':setup_id' => $this->setup_id,
				':DateStart_exp' => $this->DateStart_exp,
				':DateEnd_exp' => $this->DateEnd_exp,
				':comments' => $this->comments
			));
		}
		else
		{
			$insert = $this->db->prepare($this->sql_insert);
			$result = $insert->execute(array(
				':session_key' => $this->session_key,
				':title' => $this->title,
				':setup_id' => $this->setup_id,
				':DateStart_exp' => $this->DateStart_exp,
				':DateEnd_exp' => $this->DateEnd_exp,
				':comments' => $this->comments
			));

			$this->id = $this->db->lastInsertId();
		}

		return $result ? $this : false;
	}

	/**
	 * @param $session
	 * @return bool
	 */
	public function userCanCreate($session)
	{
		// TODO: add experiments counter for registered users, check counter not exceed max value, may be max count in config

		if(!$session)
		{
			return false;
		}

		return true;
	}

	/**
	 * @param $session
	 * @return bool
	 */
	public function userCanView($session)
	{
		if(!$session)
		{
			return false;
		}

		if($this->session_key == $session->getKey() || $session->getUserLevel() == 3)
		{
			return true;
		}

		return false;
	}

	/**
	 * @param $session
	 * @return bool
	 */
	public function userCanEdit($session)
	{
		if(!$session)
		{
			return false;
		}

		if($this->session_key == $session->getKey() || $session->getUserLevel() == 3)
		{
			return true;
		}

		return false;
	}

	/**
	 * @param $session
	 * @return bool
	 */
	public function userCanDelete($session)
	{
		if(!$session)
		{
			return false;
		}

		// Only admin can delete experiment
		if(/*$this->session_key == $session->getKey() ||*/ $session->getUserLevel() == 3)
		{
			return true;
		}

		return false;
	}

	/**
	 * Check if active.
	 * Active if there are active monitoring in experiment with at least one setup.
	 *
	 * @param  integer $id        The id of Experiment
	 * @param  integer $setup_id  The id of Setup for check active in experiment
	 * @param  bool    $force     Force checking database
	 *
	 * @return bool
	 */
	public static function isActive($id, $setup_id = null, $force = false)
	{
		return (static::getActiveCount($id, $setup_id, $force) > 0);
	}

	/**
	 * Get active count.
	 * Active if there are active monitoring in experiment with at least one setup.
	 *
	 * @param  integer $id        The id of Experiment
	 * @param  integer $setup_id  The id of Setup for check active in experiment
	 * @param  bool    $force     Force checking database
	 *
	 * @return integer
	 */
	public static function getActiveCount($id, $setup_id = null, $force = false)
	{
		static $cache = null;

		if (!$id)
		{
			return 0;
		}

		$key = '' . (int)$id  . ':' .  (int)$setup_id;

		if(!isset($cache))
		{
			$cache = array();
		}

		if(!isset($cache[$key]) || $force)
		{
			$db = new DB();

			if ($setup_id === null)
			{
				$query = $db->prepare("select count(*) from monitors where exp_id = :exp_id and ((active notnull) and (active != '') and (active > 0))");
				$query->execute(array(
						':exp_id' => (int)$id
				));
			}
			else
			{
				$query = $db->prepare("select count(*) from monitors where exp_id = :exp_id and setup_id = :setup_id and ((active notnull) and (active != '') and (active > 0))");
				$query->execute(array(
						':exp_id'   => (int)$id,
						':setup_id' => (int)$setup_id
				));
			}

			if (($row = $query->fetch(PDO::FETCH_COLUMN)) !== false)
			{
				$cache[$key] = (int)$row[0];
			}
			else
			{
				$cache[$key] = 0;
			}
		}

		return $cache[$key];
	}
}