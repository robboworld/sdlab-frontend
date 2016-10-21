<?php
/**
 * Class Setup
 *
 * Setup data model
 */
class Setup extends Model
{
	/**
	 * Setups access mode. Shared with all experiments.
	 * @var integer
	 */
	public static $ACCESS_SHARED  = 0;
	/**
	 * Setups access mode. Shared with own experiments.
	 * @var integer
	 */
	public static $ACCESS_PRIVATE = 1;
	/**
	 * Setups access mode. Shared with one own master experiment.
	 * @var integer
	 */
	public static $ACCESS_SINGLE  = 2;

	protected $id;
	protected $master_exp_id;
	protected $session_key;
	protected $access;
	protected $title;
	protected $interval;
	protected $amount;
	protected $time_det;
	protected $period;
	protected $number_error;
	protected $period_repeated_det;

	private $sql_load = 'select * from setups where id = :id';
	private $sql_insert = 'insert into setups
										(master_exp_id, session_key, access, title, interval, amount, time_det, period, number_error, period_repeated_det)
										values
										(:master_exp_id, :session_key, :access, :title, :interval, :amount, :time_det, :period, :number_error, :period_repeated_det)';

	private $sql_update = 'update setups set
										master_exp_id = :master_exp_id,
										session_key = :session_key,
										access = :access,
										title = :title,
										interval = :interval,
										amount = :amount,
										time_det = :time_det,
										period = :period,
										number_error = :number_error,
										period_repeated_det = :period_repeated_det
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
		$this->master_exp_id = null;
		$this->access = null;
		$this->title = null;
		$this->interval = null;
		$this->amount = null;
		$this->time_det = null;
		$this->period = null;
		$this->number_error = null;
		$this->period_repeated_det = null;

		parent::__construct();
	}

	/**
	 * Load Setup
	 * @see ModelInterface::load()
	 * 
	 * @param integer $id
	 * 
	 * @return Setup  object or False on error
	 */
	public function load($id)
	{
		if(is_numeric($id))
		{
			$load = $this->db->prepare($this->sql_load);
			$load->execute(array(
				':id' => $id
			));
			$setup = $load->fetch(PDO::FETCH_OBJ);
			if($setup)
			{
				foreach($setup as $key => $value)
				{
					$this->$key = $setup->$key;
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
				':master_exp_id' => $this->master_exp_id,
				':session_key' => $this->session_key,
				':access' => $this->access,
				':title' => $this->title,
				':interval' => $this->interval,
				':amount' => $this->amount,
				':time_det' => $this->time_det,
				':period' => $this->period,
				':number_error' => $this->number_error,
				':period_repeated_det' => $this->period_repeated_det
			));
		}
		else
		{
			try
			{
				$insert = $this->db->prepare($this->sql_insert);
				$result = $insert->execute(array(
					':master_exp_id' => $this->master_exp_id,
					':session_key' => $this->session_key,
					':access' => $this->access,
					':title' => $this->title,
					':interval' => $this->interval,
					':amount' => $this->amount,
					':time_det' => $this->time_det,
					':period' => $this->period,
					':number_error' => $this->number_error,
					':period_repeated_det' => $this->period_repeated_det
				));
			}
			catch (PDOException $e)
			{
				//var_dump($e->getMessage());
				error_log('PDOException:'.var_export($e->getMessage(), true)); //DEBUG
			}

			$this->id = $this->db->lastInsertId();
		}

		return $result ? $this : false;
	}

	/**
	 * Check if user can edit sensors Setup
	 * 
	 * @param Session $session  User session object to check with
	 * @param  bool    $force   Force checking database
	 * 
	 * @return bool
	 */
	public function userCanEdit($session, $force = false)
	{
		if(!$session)
		{
			return false;
		}

		// Check access to edit Setup by owner
		if(!empty($this->session_key))
		{
			if($this->session_key != $session->getKey() && $session->getUserLevel() != 3)
			{
				return false;
			}
		}

		// Check active
		// XXX: can edit active now, because its a simple template now
		/*
		if(static::isActive($this->id, $force))
		{
			return false;
		}
		*/

		return true;
	}

	/**
	 * Check if user can create sensors Setup
	 * 
	 * @param Session $session  User session object to check with
	 * 
	 * @return bool
	 */
	public function userCanCreate($session)
	{
		// TODO: not used, not only admin can create now, remove this
		// TODO: add Setups counter for registered users, check counter not exceed max value, may be max count in config

		if(!$session)
		{
			return false;
		}
		/*
		// Admin can create Setups
		if($session->getUserLevel() == 3)
		{
			return true;
		}
		else
		{
			return false;
		}
		*/
		return true;
	}

	/**
	 * Check if user can delete sensors Setup
	 * 
	 * @param Session $session  User session object to check with
	 * @param integer $exp_id   The id of Experiment
	 * 
	 * @return bool
	 */
	public function userCanDelete($session, $exp_id)
	{
		//TODO: not used delete check for Setups, only experiments can be deleted by admin

		if(!$session)
		{
			return false;
		}

		// Check admin
		if($session->getUserLevel() != 3)
		{
			// Check Setup mastering by experiment
			if(!$exp_id)
			{
				return false;
			}

			if(!$this->master_exp_id || $this->master_exp_id != $exp_id)
			{
				return false;
			}
		}

		// Only admin or with mastering by experiment can delete Setups

		return true;
	}

	/**
	 * Check if user can control sensors Setup (use in experiment).
	 * Control actions in experiment: Select, Start
	 * 
	 * @param Session $session  User session object to check with
	 * @param integer $exp_id   The id of Experiment
	 * //@param bool    $force    Force checking database
	 * 
	 * @return bool
	 */
	public function userCanControl($session, $exp_id)
	{
		if(!$session || !$exp_id)
		{
			return false;
		}

		// Check access mode
		if(!empty($this->session_key))
		{
			// Check ownership or admin
			if($this->session_key != $session->getKey() && $session->getUserLevel() != 3)
			{
				if((int)$this->access >= static::$ACCESS_PRIVATE)
				{
					return false;
				}
			}
		}

		/*
		// Check active
		if(!static::isActive($this->id, $exp_id, $force))
		{
			if($this->master_exp_id == $exp_id)
			{
				return true;
			}
		}
		else
		{
			return true;
		}
		*/

		return true;
	}

	/**
	 * Get duration in number of seconds
	 * @see Setup::time()
	 *
	 * @return integer Number of seconds
	 */
	public function time()
	{
		if(!empty($this->amount))
		{
			return $this->amount * $this->interval;
		}
		else
		{
			return $this->time_det;
		}
	}

	/**
	 * Check if active.
	 * Active if there are active monitoring with it at least in one experiment.
	 *
	 * @param  integer $id      The id of Setup
	 * @param  integer $exp_id  The id of Experiment for check active in experiment
	 * @param  bool    $force   Force checking database
	 *
	 * @return bool
	 */
	public static function isActive($id, $exp_id = null, $force = false)
	{
		return (static::getActiveCount($id, $exp_id, $force) > 0);
	}

	/**
	 * Get active count.
	 * Active if there are active monitoring with it at least in one experiment.
	 *
	 * @param  integer $id      The id of Setup
	 * @param  integer $exp_id  The id of Experiment for check active in experiment
	 * @param  bool    $force   Force checking database
	 *
	 * @return integer
	 */
	public static function getActiveCount($id, $exp_id = null, $force = false)
	{
		static $cache = null;

		if (!$id)
		{
			return 0;
		}

		$key = '' . (int)$id  . ':' .  (int)$exp_id;

		if(!isset($cache))
		{
			$cache = array();
		}

		if(!isset($cache[$key]) || $force)
		{
			$db = new DB();

			if ($exp_id === null)
			{
				$query = $db->prepare("select count(*) from monitors where setup_id = :setup_id and ((active notnull) and (active != '') and (active > 0))");
				$query->execute(array(
						':setup_id' => (int)$id
				));
			}
			else
			{
				$query = $db->prepare("select count(*) from monitors where setup_id = :setup_id and exp_id = :exp_id and ((active notnull) and (active != '') and (active > 0))");
				$query->execute(array(
						':setup_id' => (int)$id,
						':exp_id'   => (int)$exp_id
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

	// TODO: add method Get setup using count: in monitors, in experiment current?
}
