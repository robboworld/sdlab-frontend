<?php

class Monitor extends Model
{

	protected $id;
	protected $exp_id;
	protected $setup_id;
	protected $uuid;
	protected $created;
	protected $deleted;

	private $sql_load_query = 'select * from monitors where id = :id';

	private $sql_insert_query = 'insert into monitors
										(exp_id, setup_id, uuid, created, deleted)
										values
										(:exp_id, :setup_id, :uuid, :created, :deleted)';

	private $sql_update_query = 'update monitors set
										exp_id = :exp_id,
										setup_id = :setup_id,
										uuid = :uuid,
										created = :created,
										deleted = :deleted
									where id = :id';


	function __construct()
	{
		$id = null;
		$exp_id = null;
		$setup_id = null;
		$uuid = null;
		$created = null;
		$deleted = null;
		parent::__construct();
	}

	// todo: delete because not used loading monitor by id
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

	function loadItems($fields = array(), $order_col = 'id', $order_dir = 'ASC', $limit = null, $limitstart = null)
	{
		$sql_load_items_query = 'select * from monitors';

		$where = array();

		if (isset($fields['exp_id']))
		{
			if (is_numeric($fields['exp_id']))
			{
				$where[':exp_id'] = $fields['exp_id'];
			}
			else 
			{
				return false;
			}
		}

		if (isset($fields['setup_id']))
		{
			if (is_numeric($fields['setup_id']))
			{
				$where[':setup_id'] = $fields['setup_id'];
			}
			else
			{
				return false;
			}
		}

		if (isset($fields['uuid']))
		{
			if (is_string($fields['uuid']))
			{
				$str = (string) preg_replace('/[^A-Z0-9\-]/i', '', $fields['uuid']);
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

		if (isset($fields['created']))
		{
			if (is_string($fields['created']))
			{
				$str = (string) preg_replace('/[^A-Z0-9\-\:\.]/i', '', $fields['created']);
				if (strlen($str) == 0)
				{
					return false;
				}
				$where[':created'] = $str;
			}
			else if (is_array($fields['created']))
			{
				$created = &$fields['created'];
				if (isset($created['from'])) 
				{
					$str = (string) preg_replace('/[^A-Z0-9\-\:\.]/i', '', $created['from']);
					if (strlen($str) == 0)
					{
						return false;
					}
					$where['created_from'] = '(strftime(\'%s\',deleted) >= strftime(\'%s\',' . $this->db->quote($str) . '))';
				}
				if (isset($created['to']))
				{
					$str = (string) preg_replace('/[^A-Z0-9\-\:\.]/i', '', $created['to']);
					if (strlen($str) == 0)
					{
						return false;
					}
					$where['created_to'] = '(strftime(\'%s\',deleted) <= strftime(\'%s\',' . $this->db->quote($str) . '))';
				}
			}
			else
			{
				return false;
			}
		}

		if (isset($fields['deleted']))
		{
			$where['deleted'] = ((int) $fields['deleted'] > 0) ? '((deleted isnotnull) and cast(deleted as integer)>0)' : '((deleted isnull) or cast(deleted as integer)=0)';
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
			$sql_load_items_query .= ' where ' . implode(' and ', $where_cond);
		}

		// Set ordering
		$order_col = (string) preg_replace('/[^A-Z0-9\_]/i', '', $order_col);
		if (!empty($order_col))
		{
			$sql_load_items_query .= ' order by ' . (($order_col == 'created') ? 'strftime(\'%s\',created)' : $order_col)
					. ' ' . ((strtoupper($order_dir) == 'DESC') ? 'DESC' : '');
		}

		// Set limit
		if ($limit !== null)
		{
			$sql_load_items_query .= ' limit ' . (int)$limit;
			if ($limitstart !== null)
			{
				$sql_load_items_query .= ', ' . (int)$limitstart;
			}
		}

		$query = $this->db->prepare($sql_load_items_query);
		$result = $query->execute($where);
		$items = (array) $query->fetchAll(PDO::FETCH_OBJ);

		return $items;
	}

	function save()
	{
		$result = null;

		if(!is_null($this->id))
		{
			try
			{
				$update = $this->db->prepare($this->sql_update_query);
				$result = $update->execute(array(
					':id'       => $this->id,
					':exp_id'   => $this->exp_id,
					':setup_id' => $this->setup_id,
					':uuid'     => $this->uuid,
					':created'  => $this->created,
					':deleted'  => $this->deleted
				));
			}
			catch (PDOException $e)
			{
				var_dump($e->getMessage());
				error_log('PDOException:'.var_export($e->getMessage(), true)); //DEBUG
			}
		}
		else
		{
			try
			{
				$insert = $this->db->prepare($this->sql_insert_query);
				$result = $insert->execute(array(
					':exp_id'   => $this->exp_id,
					':setup_id' => $this->setup_id,
					':uuid'     => $this->uuid,
					':created'  => $this->created,
					':deleted'  => $this->deleted
				));
			}
			catch (PDOException $e)
			{
				var_dump($e->getMessage());
				error_log('PDOException:'.var_export($e->getMessage(), true)); //DEBUG
			}

			$this->id = $this->db->lastInsertId();
		}

		return $result ? $this : false;
	}
} 