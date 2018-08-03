<?php

/**
 * @copyright 2018 Steve Knoblock
 * @license MIT License
 * @package OoMocha
 */

/**
 * An ActiveRecord pattern style persistent object storage.
 * One object maps to one table;
 * Each table row represents a persisted instance of this object. Making an object persistent can be very messy. The problem is the abstraction is so different from the mechanism.
 * Directly wraps SQL queries.
 * @package OoMocha
 */

class OoData {

	var $objid; // <-- the object id for persistence, also a database property, this is visible to the database but not visible to users of the object.
	var $table; // <-- name of table containing instances of this object as rows
	var $columns;
	var $key;
	var $joinSQL;
	protected $sql;
	protected $db;

	public function __construct() {

		$this->init();

	}


	public function init($columns = '') {

		//echo "<p>OoData init()";

		$host = '127.0.0.1';
		$db   = 'test';
		$user = 'root';
		$pass = '';
		$charset = 'utf8mb4';
		/*
		$host = 'db72c.pair.com';
		$db   = 'cityg_dev';
		$user = 'cityg_8';
		$pass = 'gBhpj4Xj';
		$charset = 'utf8mb4';
		*/
		//echo "<p>new db w/ config</p>";

		$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
		$opt = [
		    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
		    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
		    PDO::ATTR_EMULATE_PREPARES   => false,
		];
		$this->db = new PDO($dsn, $user, $pass, $opt);
		
	}
   

   	/**
   	 * save()
   	 *
   	 * If object in memory has an object persistence identifier, then update the data in persistent store.
   	 * If object in memory does not have an object persistence identifier, then create a new object in the persistent store,
   	 * get the returned persistence identifier, and set the object id property to it.
   	 */

	public function save() {	
		$this->beforeSave();
		
		$params = $this->columns;

		if($this->objid) {
			/*
				Build query using columns names
			 */
			$sqlForSet = '';
			$pop[0] = array_shift($this->columns);
			foreach ($this->columns as $column) {
				$sqlForSet .= " {$column} = :{$column},";
			}
			$sqlForSet .= " {$pop[0]} = :{$pop[0]}";

			$stmt = $this->db->prepare('UPDATE '. $this->table .' SET ' . $sqlForSet);
			$stmt->execute($params);
		} else {
			$sqlColumns = implode(',', $this->columns); // SQL does not require spaces in the field list, so leave them out

			$sqlParamsList = [];
			foreach ($this->columns as $column) {
				$sqlParamsList[] = ':'. $column;
				// the bug is for auto increment null must be submitted for key
			// $this->key = null
			// We have to make sure the primary key column in the columns list has the value NULL
			// SQL param can be forced to null
				if($this->key == $column) {
					// problem is this is key zero not key some name
					$sqlParamsList[$this->key] = 'NULL';
				}
			}
			var_export($sqlParamsList);
			$sqlParams = implode(', ', $sqlParamsList);

			//preg_replace('^', '\:', $this->columns);
			//echo 'INSERT INTO '. $this->table .' ('. $sqlColumns .') VALUES ('. $sqlParams .')';
			$stmt = $this->db->prepare('INSERT INTO '. $this->table .' ('. $sqlColumns .') VALUES ('. $sqlParams .')');
			$stmt->execute($params);
			$this->objid = $this->db->lastInsertId(); // Get the automatically generated primary key and store it as the instances's or row's persistence identifier.

		}

		$this->afterSave();
	}
	
	public function beforeSave() {
	 
	}
	
	public function afterSave() {
	
	}


	/*
	 * refresh()
	 */

	public function refresh($objid = null) {
		$this->beforeRefresh();
		
		//echo "<p>In refresh() and dumping SQL from this-></p>";
		//var_dump($this->sql);
		//var_dump($this->db);

		/*
			Ok, here's youre problem. The addJoin is being added to the query string and right after it
			you're fucking setting the string to something else. What the fuck?

			The query string cannot be initialized here. It must only be added to. I don't like this.
			It leaves open the possiblity for something before the method is called to screw up the
			string.

		*/


		$this->sql .= 'SELECT * FROM '. $this->table;

		$this->sql .= $this->joinSQL;

		$this->sql .= ' WHERE '. $this->key .' = :'. $this->key;

		//echo "<p>The SQL string this->sql after adding where</p>";
		//var_dump($this->sql);

		//echo "<p>In OoData dumping sql</p>";
		//var_dump($this->sql);
		//echo "<p>In OoData dumping db</p>";
		//var_dump($this->db);
		//echo "<p>Prepare</p>";
		$stmt = $this->db->prepare($this->sql);
		//echo "<div>Statement</div>";
		//var_dump($stmt);

		$attributes = array(
		    "CLIENT_VERSION", "CONNECTION_STATUS",
		    "SERVER_INFO", "SERVER_VERSION"
		);

		//foreach ($attributes as $val) {
		    //echo "PDO::ATTR_$val: ";
		    //echo $this->db->getAttribute(constant("PDO::ATTR_$val")) . "\n";
		//}

		// already populated, refresh
		if($this->objid) {
			$stmt->execute([$this->key => $this->objid]);
		// force populate from storage
		} elseif($objid) {
			$stmt->execute([$this->key => $objid]);
		}
		
		$result = $stmt->fetch();

		/*
		 * Copy query result to object properties (only when they exist).
		 */
		// todo: check for empty array
		foreach($result as $column => $value) {
			if(property_exists($this, $column)) {
				$this->$column = $value;
			}
		}
		
		$this->afterRefresh();
	}
	
	public function beforeRefresh() {
	
	}
	
	public function afterRefresh() {
	
	}


	/*
	 * delete()
	 */

	public function delete() {
		$this->beforeDelete();
		
		$stmt = $this->db->prepare('DELETE FROM faq_headings WHERE hid = :hid');
		$stmt->execute(['hid' => $this->objid]);

		$this->afterDelete();
	}
	
	public function beforeDelete() {
	
	}
	
	public function afterDelete() {
	
	}


	function addJoin($child_table, $child_table_key) {
		//echo "<p><b>addJoin:</b> $child_table, $child_table_key</p>";

		$this->joinSQL .= ' JOIN '. $child_table .' ON '. $this->table .'.'. $child_table_key .' = '. $child_table .'.'. $child_table_key;
	}

	function buildSelectList($columns) {

	}

	/**
	 * Retrieve list of related entities.
	 */
	
	// for example, model filmmakers as list
	function getList($table, $key_name, $key_value) {

			$this->sql2 = 'SELECT * FROM '. $table;

			//$this->sql .= $this->joinSQL;

			$this->sql2 .= ' WHERE '. $key_name .' = :'. $key_name;

			var_dump($this->sql2);
			var_dump($key_name);
			var_dump($key_value);
			
			$stmt = $this->db->prepare($this->sql2);

			if($key) {
				$stmt->execute([$key_name => $key_value]);
			}
			
			$result = $stmt->fetch();

			var_dump($result);

	}

}

/*
	Here's where I start to go beyond object persistence to retrival of multiple related items or entities.

	This is the "Browse" part of CRUD that comes under R or is added as an afterthough.

	Technically, a data structure of multiple items is a _collection_ and that suggests it is
	outside the scope of basic persistence.

	I need more than one item, a list, a collection, to contain all the filmmakers who worked
	on a film.

	Yet, isn't pulling up a list of filmmakers for a film part of the film's persistience? If so,
	then are not collections part of persistence? Not something outside of it?

	Search? Find? Collection? Which is it?

	How do joins figure into this? Because it's possible to have a list of filmmakers for a film, each of
	whom have some value that is from another table that must be joined.

	Thus, a list is like the query for a single film, where each item on the list is an individual query,
	potentially with joins. Here is recursion to take advantage of.

	Minimally viable solution to retrieving a list of filmmakers.

	- query
	- that results in multiple rows
	- returned in array

	This is different from the Film object, which has properties for each value, but like it, in that
	the value for the filmmakers property is a list or collection of filmmaker objects.

	It's a collection, but it's also the value of a property. So, how do I draw a line seperating
	concerns?

	Should the collection be an object?

	The simplest solution is to query the rows for filmmakers and then assign the resulting array to a property on the
	Film object.

	I should be able to reuse the base code for refresh to do this and include any joins necessary.

	How to represent this data object? How to define it as a class?

	Do I define a Filmmakers class? How does the Film class use the Filmmakers class?

	Should I create an OoCollection class?
	
*/




/*

	A read only API requires much less functionality. Update, delete, create are unnecessary.

	If it were an API, each table could be mapped to an endpoint, simplifying the code. The
	API user would be required to tie the data together.

	In that case, there would be a class file for each table, for example, films, stock, distributor
	etc. No joins would be used.

	A better API would be a mix of capabilities, where joins are used to retrieve data that naturally
	belongs together, like a film with all its foreign key data filled in, relations like distributor or
	filmmakers, etc. Others could just give access to a single table.

*/