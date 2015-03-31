<?

/**
 * Карта классов
 * Массив с парами ИмяКласса => путь/к/ФайлуКласса
 */

return array(
	'App' => CONTROLLERS.'/App.php',
	'Controller' => CONTROLLERS.'/Controller.php',
	'DB' => CONTROLLERS.'/helpers/DB.php',
	'System' => CONTROLLERS.'/helpers/System.php',
	'Menu' => CONTROLLERS.'/helpers/Menu.php',
	'Form' => CONTROLLERS.'/helpers/Form.php',
	'PageController' => CONTROLLERS.'/PageController.php',
	'SessionController' => CONTROLLERS.'/SessionController.php',
	'SensorsController' => CONTROLLERS.'/SensorsController.php',
	'DetectionsController' => CONTROLLERS.'/DetectionsController.php',
	'ExperimentController' => CONTROLLERS.'/ExperimentController.php',
	'SetupController' => CONTROLLERS.'/SetupController.php',
	'ApiController' => CONTROLLERS.'/ApiController.php',
	'ModelInterface' => MODELS.'/interfaces/ModelInterface.php',
	'Model' => MODELS.'/Model.php',
	'Session' => MODELS.'/Session.php',
	'Experiment' => MODELS.'/Experiment.php',
	'Setup' => MODELS.'/Setup.php',
	'Plot' => MODELS.'/Plot.php',
	'JSONSocket' => CONTROLLERS.'/helpers/JSONSocket.php'
);