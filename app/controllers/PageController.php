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
		$query = System::clean(App::router(2), 'cmd');
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
		self::setContentTitle(L::SDLAB_TITLE);
		self::setViewTemplate($page);

	}


}

