<?
/**
 * Class Model
 * 
 * Base model
 */
abstract class Model implements ModelInterface
{
	protected $db;

	public function __construct()
	{
		try{
			$this->db = new DB();
			$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		} catch (PDOException $e){
			throw new Exception($e->getMessage());
		}

	}

	public function set($var, $value)
	{
		$this->{$var} = $value;
	}

	//Some magic there
	public function __isset($var)
	{
		return $this->$var;
	}

	public function __get($var)
	{
		return $this->$var;
	}

}