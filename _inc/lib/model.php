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
abstract class Model 
{
	protected $registry;
	protected $hooks;

	public function __construct($registry) 
	{
		$this->registry = $registry;
		$this->hooks = registry()->get('hooks');
	}

	public function __get($key) 
	{
		return $this->registry->get($key);
	}

	public function __set($key, $value) 
	{
		$this->registry->set($key, $value);
	}
}