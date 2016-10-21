<?php
/**
 * Class Monitor
 *
 * Monitor data model
 */
class Monitor extends Model
{
	protected $id;
	protected $uuid;
	protected $exp_id;
	protected $setup_id;
	protected $interval;
	protected $amount;
	protected $duration;
	protected $created;
	protected $stopat;
	protected $active;

	private $sql_load = 'select * from monitors where id = :id';

	private $sql_insert = 'insert into monitors
										(uuid, exp_id, setup_id, interval, amount, duration, created, stopat, active)
										values
										(:uuid, :exp_id, :setup_id, :interval, :amount, :duration, :created, :stopat, :active)';

	private $sql_update = 'update monitors set
										uuid = :uuid,
										exp_id = :exp_id,
										setup_id = :setup_id,
										interval = :interval,
										amount = :amount,
										duration = :duration,
										created = :created,
										stopat = :stopat,
										active = :active
									where id = :id';


	public function __construct()
	{
		$this->id = null;
		$this->uuid = null;
		$this->exp_id = null;
		$this->setup_id = null;
		$this->interval = null;
		$this->amount = null;
		$this->duration = null;
		$this->created = null;
		$this->stopat = null;
		$this->active = null;

		parent::__construct();
	}

	// todo: delete because not used loading monitor by id
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

	/**
	 * Get monitors list
	 * 
	 * @param array  $where_fields  Fields for filtering (key/fieldname - value). Supported:
	 *                                uuid     - (string) monitor UUID;
	 *                                exp_id   - (integer) experiment id;
	 *                                setup_id - (integer) setup id;
	 *                                created  - (string|array) datetime string or array(from, to) datetime strings;
	 *                                stopat   - (string|array) datetime string or array(from, to) datetime strings;
	 *                                active   - (integer|bool) active monitors;
	 * @param string  $order_col    ordering column name
	 * @param string  $order_dir    ordering direction, ASC or DESC, default ASC if empty
	 * @param integer $limit        null if not used
	 * @param integer $limitstart   null if not used
	 * 
	 * @return boolean|array  Array of Monitor objects, False on error
	 */
	public function loadItems($where_fields = array(), $order_col = 'id', $order_dir = 'ASC', $limit = null, $limitstart = null)
	{
		$sql_load_items = 'select * from monitors';

		$where = array();

		if (isset($where_fields['uuid']))
		{
			if (is_string($where_fields['uuid']))
			{
				$str = (string) preg_replace('/[^A-Z0-9\-]/i', '', $where_fields['uuid']);
				if (strlen($str) == 0)
				{
					return false;
				}
				$where[':uuid'] = $str;
			}
			else
			{
				return false;
			}
		}

		if (isset($where_fields['exp_id']))
		{
			if (is_numeric($where_fields['exp_id']))
			{
				$where[':exp_id'] = $where_fields['exp_id'];
			}
			else
			{
				return false;
			}
		}

		if (isset($where_fields['setup_id']))
		{
			if (is_numeric($where_fields['setup_id']))
			{
				$where[':setup_id'] = $where_fields['setup_id'];
			}
			else
			{
				return false;
			}
		}

		$dates = array('created', 'stopat');
		foreach ($dates as $dname)
		{
			if (isset($where_fields[$dname]))
			{
				if (is_string($where_fields[$dname]))
				{
					$str = (string) preg_replace('/[^A-Z0-9\-\:\.]/i', '', $where_fields[$dname]);
					if (strlen($str) == 0)
					{
						return false;
					}
					$where[':' . $dname] = $str;
				}
				else if (is_array($where_fields[$dname]))
				{
					$value = &$where_fields[$dname];
					// Filters from-to up to seconds precision
					if (isset($value['from']))
					{
						$str = (string) preg_replace('/[^A-Z0-9\-\:\.]/i', '', $value['from']);
						if (strlen($str) == 0)
						{
							return false;
						}
						$where[$dname . '_from'] = '(strftime(\'%s\',' . $dname . ') >= strftime(\'%s\',' . $this->db->quote($str) . '))';
					}
					if (isset($value['to']))
					{
						$str = (string) preg_replace('/[^A-Z0-9\-\:\.]/i', '', $value['to']);
						if (strlen($str) == 0)
						{
							return false;
						}
						$where[$dname . '_to'] = '(strftime(\'%s\',' . $dname . ') <= strftime(\'%s\',' . $this->db->quote($str) . '))';
					}
				}
				else
				{
					return false;
				}
			}
		}

		if (isset($where_fields['active']))
		{
			// bool
			$where['active'] = ((int) $where_fields['active'] > 0) ? '((active notnull) and cast(active as integer)>0)' : '((active isnull) or cast(active as integer)=0)';
		}

		// Set where filters
		$where_cond = array();
		foreach ($where as $key => $value)
		{
			if (substr($key, 0, 1) === ':')
			{
				// Simple equal condition
				$where_cond[] = '' . substr($key, 1) . ' = ' . $key;
			}
			else 
			{
				// Complex condition
				$where_cond[] = '' . $value;
				unset($where[$key]);
			}
		}
		if (!empty($where_cond))
		{
			$sql_load_items .= ' where ' . implode(' and ', $where_cond);
		}

		// Set ordering
		$order_col = (string) preg_replace('/[^A-Z0-9\_]/i', '', $order_col);
		if (!empty($order_col))
		{
			$order_val = $order_col;
			if (in_array($order_col, $dates))
			{
				$order_val = 'strftime(\'%Y-%m-%dT%H:%M:%f\',' . $order_col . ')';
			}

			$sql_load_items .= ' order by ' . $order_val
					. ' ' . ((strtoupper($order_dir) == 'DESC') ? 'DESC' : '');
		}

		// Set limit
		if ($limit !== null)
		{
			$sql_load_items .= ' limit ' . (int)$limit;
			if ($limitstart !== null)
			{
				$sql_load_items .= ', ' . (int)$limitstart;
			}
		}

		$query = $this->db->prepare($sql_load_items);
		$result = $query->execute($where);
		$items = $query->fetchAll(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, "Monitor");

		return $items;
	}

	public function save()
	{
		$result = null;

		if(!is_null($this->id))
		{
			try
			{
				$update = $this->db->prepare($this->sql_update);
				$result = $update->execute(array(
					':id'       => $this->id,
					':uuid'     => $this->uuid,
					':exp_id'   => $this->exp_id,
					':setup_id' => $this->setup_id,
					':interval' => $this->interval,
					':amount'   => $this->amount,
					':duration' => $this->duration,
					':created'  => $this->created,
					':stopat'   => $this->stopat,
					':active'   => $this->active
				));
			}
			catch (PDOException $e)
			{
				//var_dump($e->getMessage());
				error_log('PDOException:'.var_export($e->getMessage(), true)); //DEBUG
			}
		}
		else
		{
			try
			{
				$insert = $this->db->prepare($this->sql_insert);
				$result = $insert->execute(array(
					':uuid'     => $this->uuid,
					':exp_id'   => $this->exp_id,
					':setup_id' => $this->setup_id,
					':interval' => $this->interval,
					':amount'   => $this->amount,
					':duration' => $this->duration,
					':created'  => $this->created,
					':stopat'   => $this->stopat,
					':active'   => $this->active
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
			return $this->duration;
		}
	}
}
