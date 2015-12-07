<?php
/**
 * Webcam controller
 */

class WebcamController extends Controller
{

	function __construct($action = 'view')
	{
		parent::__construct($action);

		// Get id from request query string webcam/view/%id
		$this->id = App::router(2);
		$this->config = App::config();
	}

	function index()
	{
		System::go('webcam/view');
	}

	/**
	 * Action: View
	 * View single webcam or list of all cams
	 */
	function view()
	{
		if(!is_null($this->id) && is_numeric($this->id))
		{
			self::setViewTemplate('view');
			self::setTitle(L::webcam_TITLE);

			self::addJs('functions');
			self::addJs('class/Webcam');
			// Add language translates for scripts
			//Language::script(array(
			//));

			$this->view->content->item = null;

			// TODO: Get list of available video streams (parse mjpg process run cmdline, get N ids /dev/videoN)
			// Prepare parameters for api method
			$query_params = null;

			// Send request for list cameras
			$socket = new JSONSocket($this->config['socket']['path']);
			$result = $socket->call('Lab.ListVideos', $query_params);
			unset($socket);

			// Get results
			if($result !== false)
			{
				//Prepare results
				if(!empty($result))
				{
					// Some cameras

					/*
					// Lab.ListVideos result format example:
					[{
					Device:  /dev/video0
					Name:    Logitech C900
					Index:   0
					}, ...]
					*/

					// Get streaming info
					foreach ($result as $k => $vid)
					{
						if (strlen($vid->Device) == 0)
						{
							continue;
						}

						if ($vid->Index == (int)$this->id)
						{
							$result[$k]->stream = null;

							// Prepare parameters for api method
							$query_params = array($vid->Device);

							// Send request for get info about camera stream
							$socket = new JSONSocket($this->config['socket']['path']);
							$data = $socket->call('Lab.GetVideoStream', $query_params);

							// Get results
							if ($data)
							{
								/*
								// Lab.GetVideoStream result format example:
								{
								Device:  /dev/video0
								Stream:  0
								Port:    8090
								}
								*/
								if ($data->Stream < 0)
								{
									// No stream for this device
									$data->Stream = -1;
								}

								$result[$k]->stream = $data;
							}
							else
							{
								// error
								// TODO: error get streaming data for camera from backend api, may by need show error
							}
							unset($socket);

							$this->view->content->item = clone $result[$k];

							break;
						}
					}
				}
				else
				{
					// Empty cameras
				}
			}
			else
			{
				// error
				// TODO: error get monitor data from backend api, may by need show error
			
				// TODO: need fix false if empty cameras list returned
				//System::go('webcam/view');
			}

			// Camera not found
			if (empty($this->view->content->item))
			{
				//System::go('webcam/view');
			}
		}
		else
		{
			// All webcams

			self::setViewTemplate('view.all');
			self::setTitle(L::webcam_TITLE_ALL);

			self::addJs('functions');
			self::addJs('class/Webcam');
			//self::addJs('webcam/view.all');
			// Add language translates for scripts
			//Language::script(array(
			//));

			$this->view->content->list = null;

			// TODO: Get list of available video streams (parse mjpg process run cmdline, get N ids /dev/videoN)
			// Prepare parameters for api method
			$query_params = null;

			// Send request for list cameras
			$socket = new JSONSocket($this->config['socket']['path']);
			$result = $socket->call('Lab.ListVideos', $query_params);
			unset($socket);
//error_log('Lab.ListVideos:'.var_export($result,true)); //DEBUG
			// Get results
			if($result !== false)
			{
				//Prepare results
				if(!empty($result))
				{
					// Some cameras

					/*
					// Lab.ListVideos result format example:
					[{
						Device:  /dev/video0
						Name:    Logitech C900
						Index:   0
					}, ...]
					*/

					// Get streaming info
					foreach ($result as $k => $vid)
					{
						if (strlen($vid->Device) == 0){
							continue;
						}

						$result[$k]->stream = null;

						// Prepare parameters for api method
						$query_params = array($vid->Device);

						// Send request for get info about camera stream
						$socket = new JSONSocket($this->config['socket']['path']);
						$data = $socket->call('Lab.GetVideoStream', $query_params);
//error_log('Lab.GetVideoStream:'.var_export($data,true)); //DEBUG
						// Get results
						if ($data)
						{
							/*
							// Lab.GetVideoStream result format example:
							{
								Device:  /dev/video0
								Stream:  0
								Port:    8090
							}
							*/
							if ($data->Stream < 0)
							{
								// No stream for this device
								$data->Stream = -1;
							}

							$result[$k]->stream = $data;
						}
						else
						{
							// error
							// TODO: error get streaming data for camera from backend api, may by need show error
						}

						unset($socket);
					}
				}
				else
				{
					// Empty cameras
				}

				$this->view->content->list = $result;
			}
			else 
			{
				// error
				// TODO: error get monitor data from backend api, may by need show error

				// TODO: need fix false if empty cameras list returned

				$this->view->content->list = array();
			}

			//View all available webcams
		}
	}


	/** Action: Start
	 * Start web camera streaming
	 */
	function start()
	{
		if (isset($_POST) && isset($_POST['dev_id']))
		{
			//if ($this->session()->getUserLevel() == 3)
			{
				if (isset($_POST) && isset($_POST['form-id']) && $_POST['form-id'] === 'action-webcam-form')
				{
					$dev_id = ((int)$_POST['dev_id'] >= 0) ? (int)$_POST['dev_id'] : -1;

					if ($dev_id < 0)
					{
						// Error: do nothing
						// Redirect to list
						System::go('webcam/view');

						return;
					}

					// Prepare parameters for api method
					$query_params = array('/dev/video' . (int)$dev_id);

					// Send request for list cameras
					$socket = new JSONSocket($this->config['socket']['path']);
					$result = $socket->call('Lab.StartVideoStream', $query_params);
					unset($socket);

					// Check result
					if (!$result)
					{
						// Error: cannot start stream, or camera not found
						// Redirect back
						System::goback('webcam/view', 'post');
						return;
					}
				}

				// Redirect back
				System::goback('webcam/view', 'post');

				return;
			}
			//else
			//{
			//	// Redirect back
			//	System::goback('webcam/view');
			//}
		}
		else
		{
			// Redirect back
			System::goback('webcam/view', 'post');
		}
	}


	/** Action: Stop
	 * Stop web camera streaming
	 */
	function stop()
	{
		if (isset($_POST) && isset($_POST['dev_id']))
		{
			//if($this->session()->getUserLevel() == 3)
			{
				if (isset($_POST) && isset($_POST['form-id']) && $_POST['form-id'] === 'action-webcam-form')
				{
					$dev_id = ((int)$_POST['dev_id'] >= 0) ? (int)$_POST['dev_id'] : -1;

					if ($dev_id < 0)
					{
						// Error: do nothing
						// Redirect to list
						System::go('webcam/view');

						return;
					}

					// Prepare parameters for api method
					$query_params = array('/dev/video' . (int)$dev_id);

					// Send request for list cameras
					$socket = new JSONSocket($this->config['socket']['path']);
					$result = $socket->call('Lab.StopVideoStream', $query_params);
					unset($socket);

					// Check result
					if (!$result)
					{
						// Error: cannot stop stream, or camera not found
						// Redirect back
						System::goback('webcam/view', 'post');
						return;
					}
				}

				// Redirect back
				System::goback('webcam/view', 'post');

				return;
			}
			//else
			//{
			//	// Redirect back
			//	if(isset($_GET['destination']) && $_GET['destination'] != $_GET['q'])
			//	{
			//		System::go(System::clean($_GET['destination'], 'path'));
			//	}
			//	else
			//	{
			//		System::go('webcam/view');
			//	}
			//}
		}
		else
		{
			// Redirect back
			System::goback('webcam/view', 'post');
		}
	}

	/** Action: Start all
	 * Start all web cameras streaming
	 */
	function startall()
	{
		//if ($this->session()->getUserLevel() == 3)
		{
			if (isset($_POST) && isset($_POST['form-id']) && $_POST['form-id'] === 'action-webcam-form')
			{
				// Prepare parameters for api method
				$query_params = null;

				// Send request for list cameras
				$socket = new JSONSocket($this->config['socket']['path']);
				$result = $socket->call('Lab.StartVideoStreamAll', $query_params);
				unset($socket);

				// Check result
				if (!$result)
				{
					// Error: cannot start stream, or camera not found
					System::goback('webcam/view', 'post');
					return;
				}
			}

			// Redirect back
			System::goback('webcam/view', 'post');

			return;
		}
		//else
		//{
		//	System::go('experiment/view');
		//}
	}

	/** Action: Stop all
	 * Stop all web cameras streaming
	 */
	function stopall()
	{
		//if($this->session()->getUserLevel() == 3)
		{
			if (isset($_POST) && isset($_POST['form-id']) && $_POST['form-id'] === 'action-webcam-form')
			{
				// Prepare parameters for api method
				$query_params = null;

				// Send request for list cameras
				$socket = new JSONSocket($this->config['socket']['path']);
				$result = $socket->call('Lab.StopVideoStreamAll', $query_params);
				unset($socket);

				// Check result
				if (!$result)
				{
					// Error: cannot stop streams, or cameras not found
					// Redirect back
					System::goback('webcam/view', 'post');
					return;
				}
			}

			// Redirect back
			System::goback('webcam/view', 'post');
			return;
		}
		//else
		//{
		//	System::go('experiment/view');
		//}
	}
}
