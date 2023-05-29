<?php
/*
| -----------------------------------------------------
| PRODUCT NAME: 	MODERN POS
| -----------------------------------------------------
| AUTOR:			web.geoffdeep.pw
| -----------------------------------------------------
| EMAIL:			info@web.geoffdeep.pw
| -----------------------------------------------------
| COPYRIGHT:		RESERVED BY web.geoffdeep.pw
| -----------------------------------------------------
| WEBSITE:			http://web.geoffdeep.pw
| -----------------------------------------------------
*/
final class Registry 
{
	private $data = array();

	public function get($key) 
	{
		return (isset($this->data[$key]) ? $this->data[$key] : null);
	}

	public function set($key, $value) 
	{
		$this->data[$key] = $value;
	}

	public function has($key) 
	{
		return isset($this->data[$key]);
	}
}