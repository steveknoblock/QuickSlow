<?php

/**
 * @copyright 2018 Steve Knoblock
 * @license MIT License
 * @package OoMocha
 */

class Post extends OoData {

	var $related_tables = [];

	var $hasa_tables = [];

	public $post_id;
	public $post_thread;
	public $post_timestamp;
	public $post_text;
	

	function __construct() {

		parent::__construct();

		$this->table = 'post';

		$this->columns = [];

		$this->key = 'post_id';

	}

}




























