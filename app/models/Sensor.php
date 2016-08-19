<?php
/**
 * Class Sensor
 *
 * Sensor data model
 */
class Sensor
{
	public function __construct($id, $title, $c)
	{
		$this->id = $id;
		$this->title = $title;
		$this->c = $c;
	}
} 