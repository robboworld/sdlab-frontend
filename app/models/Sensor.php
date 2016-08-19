<?php
/**
 * Class Sensor
 *
 * Sensor data model
 */
class Sensor
{
	function __construct($id, $title, $c)
	{
		$this->id = $id;
		$this->title = $title;
		$this->c = $c;
	}
} 