<?php

class Setup extends Model
{

	protected $id;
	protected $master_exp_id;
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
										(master_exp_id, title, interval, amount, time_det, period, number_error, period_repeated_det, flag)
										values
										(:master_exp_id, :title, :interval, :amount, :time_det, :period, :number_error, :period_repeated_det, :flag)';

	private $sql_update_query = 'update setups set
										master_exp_id = :master_exp_id,
										title = :title,
										interval = :interval,
										amount = :amount,
										time_det = :time_det,
										period = :period,
										number_error = :number_error,
										period_repeated_det = :period_repeated_det,
										flag = :flag
									where id = :id';


	function __construct()
	{
		$id = null;
		$master_exp_id = null;
		$title = null;
		$interval = null;
		$amount = null;
		$time_det = null;
		$period = null;
		$number_error = null;
		$period_repeated_det = null;
		$flag = null;
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

		// TODO: add check access to edit by Setup owner (add setup.session_key field to DB as experiment), because master_exp_id field changes on start by other experiment

		// Check if set master
		if(!empty($this->master_exp_id))
		{
			$experiment = (new Experiment())->load($this->master_exp_id);
			if(!$experiment)
			{
				// Unknown/orphaned experiment - only admin can edit
				if($session->getUserLevel() == 3)
				{
					return true;
				}
			}
			else
			{
				// Check access to experiment edit
				if($experiment->userCanEdit($session))
				{
					return true;
				}
			}
		}
		else
		{
			// Only admin can edit w/o master
			if($session->getUserLevel() == 3)
			{
				return true;
			}
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