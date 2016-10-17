<?php
/**
 * Time configuration controller
 */
class TimeController extends Controller
{
	public function __construct($action = 'index')
	{
		parent::__construct($action);
		$this->config = App::config();
	}

	public function index()
	{
		System::go('time/edit');
	}


	/**
	 * Action: Edit
	 * Edit time settings
	 */
	public function edit()
	{
		if ($this->session()->getUserLevel() == 3)
		{
			self::setViewTemplate('edit');
			self::setTitle(L('time_TITLE_EDIT'));
			self::setContentTitle(L('time_TITLE_EDIT'));

			self::addJs('functions');
			//self::addJs('class/Time');
			// Add language translates for scripts
			//Language::script(array(
			//		'time_WAIT_FOR_REBOOT'  // class/Time
			//));

			// Form object
			$this->view->form = new Form('edit-time-form');
			$this->view->form->submit->value = L('SAVE');

			// Get current time and timezone
			$now = new DateTime();
			$this->view->form->datetime = $now;
			$this->view->form->timezone = $now->getTimezone()->getName();

			// Get available timezones list
			$this->view->form->timezones = DateTimeZone::listIdentifiers();
			$this->view->form->timezones_groups = (array) System::getTimezonesGroups();

			$exists = ($this->view->form->timezone == 'UTC') || System::in_multiarray($this->view->form->timezone, $this->view->form->timezones_groups, 'value');

			// Inject UTC and current timezone select options
			$elements = array(
					/*
					// Select option
					array(
							'type'     => 'option',
							'value'    => '',
							'text'     => L('time_SELECT_TIMEZONE'),
							'disabled' => false,
							'class'    => '',
							'onclick'  => ''
							//'label'    => '',
							//'children' => array()
					),
					*/
					array(
							'type'     => 'option',
							'value'    => 'UTC',
							'text'     => 'UTC',
							'disabled' => false,
							'class'    => '',
							'onclick'  => ''
							//'label'    => '',
							//'children' => array()
					)
			);
			if (!$exists)
			{
				$elements[] = array(
						'type'     => 'option',
						'value'    => $this->view->form->timezone,
						'text'     => $this->view->form->timezone,
						'disabled' => false,
						'class'    => '',
						'onclick'  => ''
						//'label'    => '',
						//'children' => array()
				);
			}
			$this->view->form->timezones_html = self::getTimezoneInput('time_timezone_id', 'time_timezone', $this->view->form->timezone, false, $elements, array('class' => 'form-control'));

			// Form save
			if (isset($_POST) && isset($_POST['form-id']) && $_POST['form-id'] === 'edit-time-form')
			{
				// Get parameters for request
				$datetime = (isset($_POST['time_datetime']) ? $_POST['time_datetime'] : '');
				$timezone = trim((string) preg_replace('/[^A-Z0-9_\.\-\+\/\(\)]/i', '', (isset($_POST['time_timezone']) ? $_POST['time_timezone'] : '')));

				if (empty($datetime) /*|| (strlen($timezone) == 0*/)
				{
					// Error: Not save
					System::go('time/edit');
				}

				// Check timezone
				// Get the list of time zones from the server.
				$zones = (array) System::getTimezonesGroups();
				if ($timezone == 'UTC' || (strlen($timezone) != 0 && System::in_multiarray($timezone, $zones, 'value')))
				{
					$dtz = new DateTimeZone($timezone);
				}
				else
				{
					// Current timezone by default
					$timezone = null;
					$dtz = $now->getTimezone();
				}

				// Check datetime
				$dt = DateTime::createFromFormat('Y.m.d?H:i+', $datetime, $dtz);
				$err = DateTime::getLastErrors();
				if ($dt === false || $err['error_count'] > 0)
				{
					// Error date format
					System::go('time/edit');
				}
				//Reset seconds (default is current time)
				$dt->setTime($dt->format('H'), $dt->format('i'), 0);
				$dt->setTimezone(new DateTimeZone('UTC'));

				$reboot = false;
				if ($dtz->getName() == $now->getTimezone()->getName())
				{
					$timezone = null;  // No need switch timezone
				}
				else
				{
					$reboot = true;
				}

				// Prepare array of parameters for API method
				$request_params = array(
						'Datetime' => $dt->format(System::DATETIME_RFC3339_UTC),
						'TZ'       => $timezone,  // null if not changed
						'Reboot'   => $reboot  // true - reboot, false - no reboot
				);

				// Send request for setting time
				$socket = new JSONSocket($this->config['socket']['path']);
				if ($socket->error())
				{
					// Error
					System::go('time/edit');
				}

				// Get results
				$result = $socket->call('Lab.SetDatetime', (object) $request_params);
				if ($result && $result['result'])
				{
					if ($reboot)
					{
						$this->view->content->reboot = true;
						return;
					}
				}
				else
				{
					// Error set date time
					System::go('time/edit');
				}

				System::go('time/edit');
			}
		}
		else
		{
			System::go();
		}
	}


	/**
	 * Method to get the field input markup for a grouped list.
	 * 
	 * @param  string  $id        Form field id
	 * @param  string  $name      Form field name
	 * @param  string  $value     Selected value
	 * @param  bool    $readonly  If readonly field
	 * @param  array   $elements  Array of custom elements (@see System::getGroups() for format)
	 * @param  array   $attrib    Array of field attributes
	 *
	 * @return string  The field input markup.
	 */
	protected function getTimezoneInput($id = 'timezone_id', $name = 'timezone', $value = '', $readonly = false,
			$elements = array(),
			$attrib = array('class' => '', 'disabled' => false, 'size' => null, 'required' => false, 'autofocus' => false, 'onchange' => ''))
	{
		$html = array();
		$attr = '';

		// Initialize some field attributes.
		$attr .= (isset($attrib['class']) && !empty($attrib['class'])) ? ' class="' . $attrib['class'] . '"' : '';
		$attr .= (isset($attrib['disabled']) && $attrib['disabled']) ? ' disabled' : '';
		$attr .= (isset($attrib['size']) && !empty($attrib['size'])) ? ' size="' . $attrib['size'] . '"' : '';
		$attr .= (isset($attrib['required']) && $attrib['required'])  ? ' required aria-required="true"' : '';
		$attr .= (isset($attrib['autofocus']) && $attrib['autofocus']) ? ' autofocus' : '';

		// Initialize JavaScript field attributes.
		$attr .= (isset($attrib['onchange']) && !empty($attrib['onchange'])) ? ' onchange="' . $attrib['onchange'] . '"' : '';

		// Get the field groups.
		$groups = (array) System::getTimezonesGroups($elements);

		// Create a read-only list (no name) with a hidden input to store the value.
		if ($readonly)
		{
			$html[] = Html::groupedlist(
					$groups, null,
					array(
							'list.attr' => $attr, 'id' => $id, 'list.select' => $value, 'group.items' => null, 'option.key.toHtml' => false,
							'option.text.toHtml' => false
					)
			);
			$html[] = '<input type="hidden" name="' . $name . '" value="' . htmlspecialchars($value, ENT_COMPAT, 'UTF-8') . '"/>';
		}

		// Create a regular list.
		else
		{
			$html[] = Html::groupedlist(
					$groups, $name,
					array(
							'list.attr' => $attr, 'id' => $id, 'list.select' => $value, 'group.items' => null, 'option.key.toHtml' => false,
							'option.text.toHtml' => false
					)
			);
		}

		return implode($html);
	}

}
