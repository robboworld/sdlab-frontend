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

		$this->js[] = 'main'; /*todo: rename & move script*/

		$this->content->test = 'test content';
		$this->content->title = 'JSON-RPC <small>Class: '.__CLASS__.'</small>';
		$this->content->page = $this->renderTemplate('test');

	}


}

