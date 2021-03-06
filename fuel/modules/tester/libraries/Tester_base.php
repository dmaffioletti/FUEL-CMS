<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * FUEL CMS
 * http://www.getfuelcms.com
 *
 * An open source Content Management System based on the 
 * Codeigniter framework (http://codeigniter.com)
 *
 * @package		FUEL CMS
 * @author		David McReynolds @ Daylight Studio
 * @copyright	Copyright (c) 2010, Run for Daylight LLC.
 * @license		http://www.getfuelcms.com/user_guide/general/license
 * @link		http://www.getfuelcms.com
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * The base class Test classes should inherit from
 *
 * @package		Tester Module
 * @subpackage	Libraries
 * @category	Libraries
 * @author		David McReynolds @ Daylight Studio
 * @link		http://www.getfuelcms.com/user_guide/modules/tester/tester_base
 */

abstract class Tester_base 
{
	protected $CI;
	
	private $_is_db_created;
	
	// --------------------------------------------------------------------

	/**
	 * Constructor sets up the CI instance
	 *
	 * @access	public
	 * @return	array
	 */
	public function __construct()
	{
		$this->CI =& get_instance();
	}
	
	// --------------------------------------------------------------------

	/**
	 * Returns where condition based on the users logged in state
	 *
	 * @access	public
	 * @param	mixed
	 * @param	mixed
	 * @param	string
	 * @return	string
	 */
	public function run($test, $expected, $name = '')
	{
		$name = $this->format_test_name($name, $test, $expected);
		return $this->CI->unit->run($test, $expected, $name);
	}
	
	// --------------------------------------------------------------------

	/**
	 *  Placeholder to be overwritten by child classes for test setup (like database table etc)
	 *
	 * @access	public
	 * @return	void
	 */
	public function setup()
	{
		
	}
	
	// --------------------------------------------------------------------

	/**
	 *  Is called at the end of the test and will remove any test database that has been created
	 *
	 * @access	public
	 * @return	void
	 */
	public function tear_down()
	{
		if ($this->_is_db_created)
		{
			$this->remove_db();
		}
	}
	
	// --------------------------------------------------------------------

	/**
	 *  Formats the test name to include the test and expected results
	 *
	 * @access	public
	 * @param	string
	 * @param	mixed
	 * @param	mixed
	 * @return	string
	 */
	protected function format_test_name($name, $test, $expected)
	{
		$str = '<strong>'.$name.'</strong><br />';
		// $str .= '<strong>Test:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</strong> <pre>'.htmlentities($test).'</pre><br />';
		// $str .= '<strong>Expected:</strong> <pre>'.htmlentities($expected).'</pre>';
		return $str;
	}
	
	// --------------------------------------------------------------------

	/**
	 *  Return Tester specific configuration items
	 *
	 * @access	public
	 * @param	string
	 * @return	mixed
	 */
	protected function config_item($key)
	{
		$tester_config = $this->CI->config->item('tester');
		return $tester_config[$key];
	}
	
	// --------------------------------------------------------------------

	/**
	 *  Connects to the testing database
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	protected function db_connect($dsn = '')
	{
		// check config if $dsn is empty
		if (empty($dsn))
		{
			$tester_config = $this->CI->config->item('tester');
			$dsn = $this->config_item('dsn');
		}
		
		// default to test group name in the database config
		if (empty($dsn))
		{
			$dsn = 'test';
		}
		
		$this->CI->load->database($dsn);
	}
	
	// --------------------------------------------------------------------

	/**
	 *  Checks to see if the database exists or not
	 *
	 * @access	public
	 * @return	void
	 */
	protected function db_exists()
	{
		$result = $this->CI->db->query('SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = "'.$this->config_item('db_name').'"');
		$table = $result->row_array();
		return (!empty($table['SCHEMA_NAME']) && strtoupper($table['SCHEMA_NAME']) == strtoupper($this->config_item('db_name')));
	}
	
	// --------------------------------------------------------------------

	/**
	 *  Creates the database and connects if $connect parameter is set to TRUE
	 *
	 * @access	public
	 * @param	boolean
	 * @return	void
	 */
	protected function create_db($connect = TRUE)
	{
		if ($connect) $this->db_connect();
		
		$this->CI->load->dbforge();
		
		// create the database if it doesn't exist'
		if (!$this->db_exists())
		{
			$this->CI->dbforge->create_database($this->config_item('db_name'));
		}

		// create database
		$this->_is_db_created = TRUE;
	}
	
	// --------------------------------------------------------------------

	/**
	 *  Removes the test database and connects if $connect parameter is set to TRUE
	 *
	 * @access	public
	 * @param	boolean
	 * @return	void
	 */
	protected function remove_db($connect = TRUE)
	{
		if ($connect) $this->db_connect();
		
		$this->CI->load->dbforge();
		
		// drop the database if it exists
		if ($this->db_exists())
		{
			$this->CI->dbforge->drop_database($this->config_item('db_name'));
		}
		$this->_is_db_created = FALSE;
	}
	
	// --------------------------------------------------------------------

	/**
	 *  Loads the sql from a file in the {module}/test/sql folder
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @return	void
	 */
	protected function load_sql($file = NULL, $module = 'tester')
	{
		if (!$this->_is_db_created) $this->create_db();
		$sql_path = APPPATH.MODULES_FOLDER.'/'.$module.'/tests/sql/'.$file;
		
		// select the database
		$sql = 'USE '.$this->config_item('db_name');
		
		$this->CI->db->query($sql);
		if (file_exists($sql_path))
		{
			$sql = file_get_contents($sql_path);
			$sql = str_replace('`', '', $sql);
			$sql = preg_replace('#^/\*(.+)\*/$#U', '', $sql);
			$sql = preg_replace('/^#(.+)$/U', '', $sql);
		}
		$sql_arr = explode(";\n", $sql);
		
		foreach($sql_arr as $s)
		{
			$s = trim($s);
			if (!empty($s))
			{
				$this->CI->db->query($s);
			}
		}
	}

	// --------------------------------------------------------------------

	/**
	 *  Loads the results of call to a controller. Additionally creates pq function to query dom nodes
	 *
	 * @access	public
	 * @param	string
	 * @param	array
	 * @return	string
	 */
	protected function load_page($page, $post = array())
	{
		
		$this->CI->load->library('user_agent');
		
		$_SERVER['PATH_INFO'] = $page;
		$_SERVER['REQUEST_URI'] = $page;

		// must suppres warnings to remove constant warnings
//		$exec = TESTER_PATH.'libraries/Controller_runner.php --run='.$page.' -CI='.FCPATH.' -D='.$_SERVER['SERVER_NAME'].' -P='.$_SERVER['SERVER_PORT'].' -X='.base64_encode(serialize($post));
//		$output = shell_exec($exec);
		
		$ch = curl_init(); 
		curl_setopt($ch, CURLOPT_URL, site_url($page));
		curl_setopt($ch, CURLOPT_HEADER, 0); 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		if (!empty($post))
		{
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		}
		curl_setopt($ch, CURLOPT_USERAGENT, $this->CI->agent->agent_string());
		
		$output = curl_exec($ch);
		curl_close($ch); 
		
		
		//http://code.google.com/p/phpquery/wiki/Manual
		require_once(TESTER_PATH.'libraries/phpQuery.php');
		phpQuery::newDocumentHTML($output, strtolower($this->CI->config->item('charset')));
		return $output;
	}
	
	// --------------------------------------------------------------------

	/**
	 *  Class desctruct magic method which will run teardown
	 *
	 * @access	public
	 * @return	void
	 */
	public function __destruct()
	{
		$this->tear_down();
	}
}

/* End of file Tester_base.php */
/* Location: ./application/modules/tester/libraries/Tester_base.php */