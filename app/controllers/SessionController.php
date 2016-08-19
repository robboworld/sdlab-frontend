<?php
/**
 * Class SessionController
 * 
 * Actions with user sessions and profile
 */
class SessionController extends Controller
{
	public $user_access_level = 0;
	public function create()
	{
		if(isset($_POST['session_key']))
		{
			$session = new Session();
			if($session->load($_POST['session_key']) && strlen($_POST['session_key']) == 6)
			{
				$this->session($session);
				$this->session()->setSession();

				$destination = System::getVarBackurl();
				if($destination !== null && $destination != $_GET['q'])
				{
					System::go(System::cleanVar($destination, 'path'));
				}
				else
				{
					System::go();
				}
			}
		}

		if(isset($_POST['session_new']) && $_POST['session_new'] == true)
		{
			if(isset($_POST['session_title']) && isset($_POST['session_name']))
			{
				if($_POST['session_name'] != '' && $_POST['session_title'] !='')
				{
					$this->session(new Session());
					$this->session()->set('title', $_POST['session_title']);
					$this->session()->set('name', $_POST['session_name']);
					$this->session()->save();
					$this->session()->setSession();

					$destination = System::getVarBackurl();
					if($destination !== null && $destination != $_GET['q'])
					{
						System::go(System::cleanVar($destination, 'path'));
					}
					else
					{
						System::go();
					}
				}
			}
		}
		self::setTitle(L::session_NEW_SESSION);
		self::setContentTitle(L::session_NEW_SESSION);
	}

	public function edit()
	{
		// Check access
		if(!$this->session())
		{
			// Only for registered
			System::go();
		}

		// If form is sent
		if(isset($_POST) && isset($_POST['form-id']) && $_POST['form-id'] === 'edit-session-form')
		{
			if(isset($_POST['session_comments']))
			{
				$this->session()->set('comments', $_POST['session_comments']);
			}

			if(isset($_POST['session_title']))
			{
				$this->session()->set('title', $_POST['session_title']);
			}

			if(isset($_POST['session_name']))
			{
				$this->session()->set('name', $_POST['session_name']);
			}

			if(isset($_POST['session_expiry']))
			{
				$this->session()->set('expiry', $_POST['session_expiry']);
			}

			// Save
			$this->session()->save();
			// Rewrite in $_SESSION
			$this->session()->setSession();
			// Redirect for prevent next form send
			Form::redirect();
		}

		self::setTitle(L::session_TITLE_EDIT);

		// Load experiments available in session
		$experiments_in_session = ExperimentController::loadExperiments($this->session()->getKey());
		if($experiments_in_session)
		{
			foreach($experiments_in_session as $key => $item)
			{
				$experiments_in_session[$key] = (new Experiment())->load($item->id);
			}
			$this->view->experiments_in_session = $experiments_in_session;
		}

	}

	public function destroy()
	{
		Session::destroySession();
		if($this->session())
		{
			$this->session()->save();
			unset($this->app->session);
		}
		System::go();
	}
}