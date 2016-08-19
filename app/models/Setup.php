<?php
/**
 * Class Setup
 *
 * Setup data model
 */
class Setup extends Model
{

	protected $id;
	protected $master_exp_id;
	protected $session_key;
	protected $title;
	protected $interval;
	protected $amount;
	protected $time_det;
	protected $period;
	protected $number_error;
	protected $period_repeated_det;
	protected $flag;

	private $sql_load_query = 'select * from setups where id = :id';
	private $sql_insert_query = 'insert into setups
										(master_exp_id, session_key, title, interval, amount, time_det, period, number_error, period_repeated_det, flag)
										values
										(:master_exp_id, :session_key, :title, :interval, :amount, :time_det, :period, :number_error, :period_repeated_det, :flag)';

	private $sql_update_query = 'update setups set
										master_exp_id = :master_exp_id,
										session_key = :session_key,
										title = :title,
										interval = :interval,
										amount = :amount,
										time_det = :time_det,
										period = :period,
										number_error = :number_error,
										period_repeated_det = :period_repeated_det,
										flag = :flag
									where id = :id';


	function __construct($session_key = null)
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
		$this->session_key = null;
		$this->title = null;
		$this->interval = null;
		$this->amount = null;
		$this->time_det = null;
		$this->period = null;
		$this->number_error = null;
		$this->period_repeated_det = null;
		$this->flag = null;
		parent::__construct();
	}

	function load($id)
	{
		if(is_numeric($id))
		{
			$load = $this->db->prepare($this->sql_load_query);
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

	function save()
	{
		if(!is_null($this->id))
		{
			$update = $this->db->prepare($this->sql_update_query);
			$result = $update->execute(array(
				':id' => $this->id,
				':master_exp_id' => $this->master_exp_id,
				':session_key' => $this->session_key,
				':title' => $this->title,
				':interval' => $this->interval,
				':amount' => $this->amount,
				':time_det' => $this->time_det,
				':period' => $this->period,
				':number_error' => $this->number_error,
				':period_repeated_det' => $this->period_repeated_det,
				':flag' => $this->flag
			));
		}
		else
		{
			try
			{
				$insert = $this->db->prepare($this->sql_insert_query);
				$result = $insert->execute(array(
					':master_exp_id' => $this->master_exp_id,
					':session_key' => $this->session_key,
					':title' => $this->title,
					':interval' => $this->interval,
					':amount' => $this->amount,
					':time_det' => $this->time_det,
					':period' => $this->period,
					':number_error' => $this->number_error,
					':period_repeated_det' => $this->period_repeated_det,
					':flag' => $this->flag
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
	 * @param $session
	 * @return bool
	 */
	function userCanEdit($session)
	{
		if(!$session)
		{
			return false;
		}

		// Check active
		if($this->flag)
		{
			return false;
		}

		// Check access to edit Setup by owner
		if(empty($this->session_key) || $this->session_key == $session->getKey() || $session->getUserLevel() == 3)
		{
			return true;
		}
		return false;
	}

	/**
	 * @param $session
	 * @return bool
	 */
	function userCanCreate($session)
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
	 * @param $session
	 * @return bool
	 */
	function userCanDelete($session)
	{
		//TODO: not used delete check for Setups, only experiments can be deleted by admin

		if(!$session)
		{
			return false;
		}

		// Only admin can delete Setups
		if(/*$this->session_key == $session->getKey() ||*/ $session->getUserLevel() == 3)
		{
			return true;
		}

		return false;
	}

	/**
	 * @param $session
	 * @param $exp_id
	 * @return bool
	 */
	function userCanControl($session, $exp_id)
	{
		if(!$session || !$exp_id)
		{
			return false;
		}

		// Check active
		if(!$this->flag)
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

		return false;
	}

	function time()
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
} 