<?

class SensorsController extends Controller
{

	function index()
	{
		$this->content->page = $this->renderTemplate('index');
	}
}