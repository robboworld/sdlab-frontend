<?php
/**
 * Users controller
 * 
 * User management actions controller
 */
class UsersController extends Controller
{

	public function __construct($action = 'index', $config = array('default_action' => 'index'))
	{
		parent::__construct($action, $config);

		// Register the methods as actions.
		$this->registerAction('view', 'view');

		// Get id from request query string users/edit/%id
		$this->id = App::router(2);

		// Get Application config
		$this->config = App::config();
	}

	public function index()
	{
		System::go('users/view');
	}

	/**
	 * Action: View
	 * View single user profile or list all
	 */
	public function view()
	{
		if(!is_null($this->id) && is_numeric($this->id))
		{
			// Single user page

			// TODO: implement single user profile view

			System::go('users/list');
		}
		else
		{
			// List of users

			// XXX: Only admin can view now!
			$session = $this->session();
			if(!$session)
			{
				// TODO: go error 403
				System::go();
			}
			if($session->getUserLevel() != 3)
			{
				// TODO: go error 403
				System::go();
			}

			// Filter users list by current user level
			$user_level = $this->session()->getUserLevel();
			$filter_user_level = null;
			if ($user_level != 3)
			{
				// Only registered for non admins
				$filter_user_level = array(1);
			}

			self::setViewTemplate('view.all');
			self::setTitle(L('users_TITLE_ALL'));

			self::addJs('functions');
			//self::addJs('users/view.all');
			// Add language translates for scripts
			//Language::script(array(
			//		'ERROR'  // users/view.all
			//));

			//View users in this session
			$this->view->content->list = $this->usersList($filter_user_level);
		}
	}

	/**
	 * Get list of users/sessions.
	 * 
	 * @param   mixed  $filter_user_level  Filter user levels (optional). Array or int value of user levels for filter. Default: get all.
	 * 
	 * @return  array                      Array of objects with users data or empty
	 */
	protected function usersList($filter_user_level = null)
	{
		$db = new DB();

		if ($filter_user_level !== null)
		{
			if(!is_array($filter_user_level))
			{
				$filter_user_level = array((int)$filter_user_level);
			}

			// Filter incorrect levels
			$all_levels = Session::getUserLevels();
			$filter_user_level = array_intersect($all_levels, $filter_user_level);
		}

		if (!empty($filter_user_level))
		{
			// TODO: add user level to sessions list and filter query
			// XXX: now return all users always
			/*
			$query = $db->prepare('select * from sessions where level IN (:levels)');
			$query->execute(array(
					':levels' => implode(',', $filter_user_level)
			));
			*/
			$query = $db->prepare('select * from sessions');
			$query->execute();
		}
		else
		{
			$query = $db->prepare('select * from sessions');
			$query->execute();
		}

		return $query->fetchAll(PDO::FETCH_OBJ);
	}
}
