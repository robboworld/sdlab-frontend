<?

/**
 * Class Plot
 */
class Plot extends Model
{
	protected  $id;
	protected $exp_id;
	protected $id_sensor_x;
	protected $sensor_val_id_x;
	protected $scales;
	protected $start;
	protected $stop;

	private $sql_load_query = 'select * from plots where id = :id';
	private $sql_insert_query = 'insert into setups
										(exp_id, id_sensor_x, sensor_val_id_x, scales, start, stop)
										values
										(:exp_id, :id_sensor_x, :sensor_val_id_x, :scales, :start, :stop)';

	private $sql_update_query = 'update setups set
										exp_id = :exp_id,
										id_sensor_x = :id_sensor_x,
										sensor_val_id_x = :sensor_val_id_x,
										scales = :scales,
										start = :start,
										stop = :stop
									where id = :id';

	function __construct()
	{
		$this->id = null;
		$this->exp_id = null;
		$this->id_sensor_x = null;
		$this->sensor_val_id_x = null;
		$this->scales = null;
		$this->start = null;
		$this->stop = null;
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
			$plot = $load->fetch(PDO::FETCH_OBJ);
			if($plot)
			{
				foreach($plot as $key => $value)
				{
					$this->$key = $plot->$key;
				}
				return $this;
			}
			return false;
		}
		return false;
	}

	function save()
	{
		if(!empty($this->id))
		{
			$update = $this->db->prepare($this->sql_insert_query);
			$result = $update->execute(array(
				':id' => $this->id,
				':exp_id' => $this->exp_id,
				':id_sensor_x' => $this->id_sensor_x,
				':sensor_val_id_x' => $this->sensor_val_id_x,
				':scales' => $this->scales,
				':start' => $this->start,
				':stop' => $this->stop
			));
		}
		else
		{
			$insert = $this->db->prepare($this->sql_insert_query);
			$result = $insert->execute(array(
				':exp_id' => $this->exp_id,
				':id_sensor_x' => $this->id_sensor_x,
				':sensor_val_id_x' => $this->sensor_val_id_x,
				':scales' => $this->scales,
				':start' => $this->start,
				':stop' => $this->stop
			));

			$this->id = $this->db->lastInsertId();
		}

		return $result ? $this : false;
	}
}