<?

class SensorsController extends Controller
{

	function index()
	{
		$this->js[] = 'sensors';
		$this->content->sensors_list = $this->sensorList($this->getSensors(), 'available-sensors');
		$this->content->page = $this->renderTemplate('index');
	}

	function getSensors()
	{
		return array(
			new Sensor('temp', 'Температура', 'C'),
			new Sensor('light', 'Light', 'lux')
		);
	}

	private function sensorList(array $sensors, $listid, $class = null)
	{
		if($sensors)
		{
			$list = '';
			$class_string = 'list-group-item';

			if($class != null && is_array($class))
			{
				$class_string = implode(' ', $class);
			}

			foreach($sensors as $item)
			{
				$list .= '<a class="'.$class_string.'" href="#" data-id="'.$item->id.'">'.$item->title.'</a>';
			}
			return '<div class="list-group" id="'.$listid.'">'.$list.'</div>';
		}
		else return false;
	}
}


class Sensor
{
	function __construct($id, $title, $c)
	{
		$this->id = $id;
		$this->title = $title;
		$this->c = $c;
	}
}