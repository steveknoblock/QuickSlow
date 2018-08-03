<?php

/**
 * @copyright 2018 Steve Knoblock
 * @license MIT License
 * @package OoMocha
 */


class Notebook extends OoData {

	public $notebook_id;
	public $notebook_content;

	function __construct() {

		parent::__construct();

		$this->table = 'notebook';

		$this->columns = [];

		$this->key = 'notebook_id';
		
	}

}





























