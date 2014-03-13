<?

class PageController extends Controller
{
	function view()
	{
		$query = App::router();
		if(isset($query[2]) && $query[2] !='')
		{
			$page = $query[2];
		}
		else
		{
			$page = 'index';
		}

		$this->content->test = 'test content';
		$this->content->title = 'JSON-RPC <small>Class: '.__CLASS__.'</small>';
		$this->content->page = $this->renderTemplate('test');

	}

	function renderView()
	{
		if(isset($this->action))
		{
			$this->{$this->action}();
		}
		else
		{
			$this->view();
		}

		$layout = $this->renderTemplate('layout');
		include(DEFAULT_HTML_TEMPLATE);
	}


}

