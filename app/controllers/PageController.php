<?php
/**
 * Class App
 *
 * Simple index page actions controller class
 */
class PageController extends Controller
{

	public function __construct($action = 'view')
	{
		$this->user_access_level = 0;
		parent::__construct($action);

	}

	public function view()
	{
		$query = System::cleanVar(App::router(2), 'cmd');
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

		self::setTitle(L('SYSTEM'));
		self::setContentTitle(L('SDLAB_TITLE'));
		self::setViewTemplate($page);
	}
}
