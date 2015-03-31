<?


/**
 * Class DetectionsController
 * NOT extends Controller
 */
class DetectionsController
{
	function __construct()
	{
		$this->db = new DB();
	}

	function getGraphData(array $params)
	{
		if($plot = (new Plot())->load($params['plot']))
		{
			$experiment = (new Experiment())->load($plot->exp_id);
			$setup = (new Setup())->load($experiment->setup_id);

			$detections_query = $this->db->prepare('select * from detections where exp_id LIKE :experiment_id and id_sensor LIKE :sensor_id');

			$ordinate_query = $this->db->query('select ordinate.*, setup_conf.name, setup_conf.sensor_id from ordinate left join setup_conf on setup_conf.setup_id LIKE '.$setup->id.' AND setup_conf.sensor_id = ordinate.id_sensor_y where id_plot LIKE '.$plot->id.' ', PDO::FETCH_OBJ);
			//System::dump($ordinate_query);
			if($ordinate_query)
			{
				$result = array();
				foreach($ordinate_query as $item)
				{
					//System::dump($item);
					$sensor_select = $detections_query->execute(array(
						':experiment_id' => $experiment->id,
						':sensor_id' => $item->sensor_id
					));
					if($sensor_select)
					{
						$sensor_data = $detections_query->fetchAll(PDO::FETCH_OBJ);
						$graph_object = new StdClass();
						$graph_object->label = $item->name;
						$graph_object->color = ++$i;
						foreach($sensor_data as $point)
						{
							$time = explode('.', $point->time);
							$time = new DateTime($time[0]);
							$graph_object->data[] = array(
								$time->getTimestamp()*1000,
								$point->detection
							);
						}
						$result[] = $graph_object;
					}
				}
				return $result ? $result : false;
			}
			return false;
		}
		else
		{
			$this->error = 'Графика не существует.';
		}
	}

	function error()
	{
		return $this->error;
	}
}