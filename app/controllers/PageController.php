<?

class PageController extends Controller
{

	function __construct($action = 'view')
	{
		$this->user_access_level = 0;
		parent::__construct($action);

	}

	function view()
	{
		$query = App::router(2);
		if(!empty($query))
		{
			$page = $query;
		}
		else
		{
			$page = 'index';

			$this->view->ip_address = System::get_ip_address('eth0');
		}

		self::addJs('functions');

		self::setTitle('Система');
		self::setContentTitle('ScratchDuino.Лаборатория');
		self::setViewTemplate($page);

	}


}

