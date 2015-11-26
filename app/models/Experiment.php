<?

/**
 * Class Experiment
 */

class Experiment extends Model
{
	protected $id;
	protected $session_key;
	protected $title;
	protected $setup_id;
	protected $DateStart_exp;
	protected $DateEnd_exp;
	protected $comments;

	private $sql_load_query = 'select * from experiments where id = :id';
	private $sql_insert_query = 'insert into experiments
										(session_key, title, setup_id, DateStart_exp, DateEnd_exp, comments)
										values
										(:session_key, :title, :setup_id, :DateStart_exp, :DateEnd_exp, :comments)';

	private $sql_update_query = 'update experiments set
										session_key = :session_key,
										title = :title,
										setup_id = :setup_id,
										DateStart_exp = :DateStart_exp,
										DateEnd_exp = :DateEnd_exp,
										comments = :comments
									where id = :id';

	function __construct($session_key = null)
	{
		if(is_numeric($session_key) && Session::keyExists($session_key))
		{
			$this->session_key = $session_key;
		}
		else
		{
			$this->session_key = null;
		}

		$this->id = null;
		$this->title = null;
		$this->setup_id = null;
		$this->DateStart_exp = null;
		$this->DateEnd_exp = null;
		$this->comments = null;
		parent::__construct();

	}

	function load($id)
	{
		if(is_numeric($id))
		{
			$load = $this->db->prepare($this->sql_load_query);
			$load->execute(array(
				':id' => $id
			));
			$experiment = $load->fetch(PDO::FETCH_OBJ);
			if($experiment)
			{
				foreach($experiment as $key => $value)
				{
					$this->$key = $experiment->$key;
				}
				return $this;
			}
			return false;
		}
		return false;
	}

	function save()
	{

		if(!is_null($this->id))
		{
			$update = $this->db->prepare($this->sql_update_query);
			$result = $update->execute(array(
				':id' => $this->id,
				':session_key' => $this->session_key,
				':title' => $this->title,
				':setup_id' => $this->setup_id,
				':DateStart_exp' => $this->DateStart_exp,
				':DateEnd_exp' => $this->DateEnd_exp,
				':comments' => $this->comments
			));
		}
		else
		{
			$insert = $this->db->prepare($this->sql_insert_query);
			$result = $insert->execute(array(
				':session_key' => $this->session_key,
				':title' => $this->title,
				':setup_id' => $this->setup_id,
				':DateStart_exp' => $this->DateStart_exp,
				':DateEnd_exp' => $this->DateEnd_exp,
				':comments' => $this->comments
			));

			$this->id = $this->db->lastInsertId();
		}

		return $result ? $this : false;
	}
}