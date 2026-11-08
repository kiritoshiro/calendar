<?php
/** no direct access **/
defined('MECEXEC') or die();

/**
 * Webnus MEC DataBase class.
 * @author Webnus <info@webnus.net>
 */
class MEC_db extends MEC_base
{
    /**
     * Constructor method
     * @author Webnus <info@webnus.net>
     */
    public function __construct()
    {
    }
    
    /**
     * Runs any query
     * @author Webnus <info@webnus.net>
     * @param string $query
     * @param string $type
     * @return mixed
     */
	public function q($query, $type = '')
	{
		// Apply DB prefix
		$query = $this->_prefix($query);
		
		// Converts query type to lowercase
		$type = strtolower($type);
		
		// Calls select function if query type is select
		if($type == 'select') return $this->select($query);
        
		// Get WordPress DB object
		$database = $this->get_DBO();
		
        // If query type is insert, return the insert id
		if($type == 'insert')
		{
			$database->query($query);
			return $database->insert_id;
		}
		
        // Run the query and return the result
		return $database->query($query);
		
	}
    
    /**
     * Returns records count of a query
     * @author Webnus <info@webnus.net>
     * @param string $query
     * @param string $table
     * @return int
     */
	public function num($query, $table = '')
	{
        // If table is filled, generate the query
		if(trim($table) != '')
        {
            $table = $this->sanitize_table_name($table);
            if($table === '') return 0;

            $query = "SELECT COUNT(*) FROM `#__$table`";
        }
		
		// Apply DB prefix
		$query = $this->_prefix($query);
		
		// Get WordPress Db object
		$database = $this->get_DBO();
		return $database->get_var($query);
	}
    
    /**
     * Selects records from Database
     * @author Webnus <info@webnus.net>
     * @param string $query
     * @param string $result
     * @return mixed
     */
	public function select($query, $result = 'loadObjectList')
	{
		// Apply DB prefix
		$query = $this->_prefix($query);
		
		// Get WordPress DB object
		$database = $this->get_DBO();
		
		if($result == 'loadObjectList') return $database->get_results($query, OBJECT_K);
		elseif($result == 'loadObject') return $database->get_row($query, OBJECT);
		elseif($result == 'loadAssocList') return $database->get_results($query, ARRAY_A);
		elseif($result == 'loadAssoc') return $database->get_row($query, ARRAY_A);
		elseif($result == 'loadResult') return $database->get_var($query);
        elseif($result == 'loadColumn') return $database->get_col($query);
		else return $database->get_results($query, OBJECT_K);
	}
    
    /**
     * Get a record from Database
     * @author Webnus <info@webnus.net>
     * @param string|array $selects
     * @param string $table
     * @param string $field
     * @param string $value
     * @param boolean $return_object
     * @param string $condition
     * @return mixed
     */
	public function get($selects, $table, $field, $value, $return_object = true, $condition = '')
	{
		$fields = '';
		
		if(is_array($selects))
		{
			foreach($selects as $select)
            {
                $select = $this->sanitize_identifier($select);
                if($select === '') return false;

                $fields .= '`'.$select.'`,';
            }
			$fields = trim($fields, ' ,');
		}
		else
		{
			$fields = $this->sanitize_select_fields($selects);
            if($fields === '') return false;
		}
		
        // Generate the condition
		if(trim($condition) == '')
        {
            $field = $this->sanitize_identifier($field);
            if($field === '') return false;

            $condition = "`$field`='".esc_sql($value)."'";
        }
        elseif(!$this->is_safe_condition($condition)) return false;
        
        // Generate the query
        $table = $this->sanitize_table_name($table);
        if($table === '') return false;

		$query = "SELECT $fields FROM `#__$table` WHERE $condition";
		
		// Apply DB prefix
		$query = $this->_prefix($query);
		
		// Get WordPress DB object
		$database = $this->get_DBO();
		
		if($selects != '*' and !is_array($selects)) return $database->get_var($query);
		elseif($return_object)
		{
			return $database->get_row($query);
		}
		else
		{
			return $database->get_row($query, ARRAY_A);
		}
	}

    public function columns($table = 'mec_dates', $column = NULL)
    {
        if(trim($table) == '') return false;
        $table = $this->sanitize_table_name($table);
        if($table === '') return false;

        $query = "SHOW COLUMNS FROM `#__".$table."`";
        $results = $this->q($query, "select");

        $columns = [];
        foreach($results as $result) $columns[] = $result->Field;

        if(trim($column) and in_array($column, $columns)) return true;
        elseif(trim($column)) return false;

        return $columns;
    }

    /**
     * Check if a table exist or not
     * @param string $table
     * @return bool
     */
    public function exists($table)
    {
        $table = $this->sanitize_table_name($table);
        if($table === '') return false;

        $query = "SHOW TABLES LIKE '#__".$table."'";
        return (bool) $this->select($query, "loadObject");
    }

    protected function sanitize_table_name($table)
    {
        return preg_replace('/[^A-Za-z0-9_]/', '', (string) $table);
    }

    protected function sanitize_identifier($identifier)
    {
        return preg_replace('/[^A-Za-z0-9_]/', '', (string) $identifier);
    }

    protected function sanitize_select_fields($fields)
    {
        $fields = trim((string) $fields);
        if($fields === '*') return '*';

        $sanitized = [];
        foreach(explode(',', $fields) as $field)
        {
            $field = trim(str_replace('`', '', $field));
            if($field === '') continue;

            $parts = preg_split('/\s+AS\s+/i', $field);
            $column = preg_replace('/[^A-Za-z0-9_\.]/', '', $parts[0] ?? '');
            if($column === '') return '';

            $entry = $column;
            if(isset($parts[1]))
            {
                $alias = $this->sanitize_identifier($parts[1]);
                if($alias === '') return '';

                $entry .= ' AS `'.$alias.'`';
            }

            $sanitized[] = $entry;
        }

        return count($sanitized) ? implode(', ', $sanitized) : '';
    }

    protected function is_safe_condition($condition)
    {
        $condition = trim((string) $condition);
        if($condition === '') return false;

        return !preg_match('/(;|--|#|\/\*|\*\/|\bUNION\b|\bSELECT\b|\bINSERT\b|\bUPDATE\b|\bDELETE\b|\bDROP\b|\bALTER\b|\bCREATE\b)/i', $condition);
    }
	
    /**
     * Apply WordPress table prefix on queries
     * @author Webnus <info@webnus.net>
     * @param string $query
     * @return string
     */
	public function _prefix($query)
	{
        // Get WordPress DB object
		$wpdb = $this->get_DBO();

		$charset = $wpdb->charset;
		if(!trim($charset)) $charset = 'utf8';

		$collate = $wpdb->collate;
        if(!trim($collate))
        {
            $charset = 'utf8';
            $collate = 'utf8_unicode_ci';
        }

        $query = str_replace('#__blogs', $wpdb->base_prefix.'blogs', $query);
		$query = str_replace('#__', $wpdb->prefix, $query);
		$query = str_replace('[:CHARSET:]', $charset, $query);

		return str_replace('[:COLLATE:]', $collate, $query);
	}

    public function escape($parameter)
    {
        $database = $this->get_DBO();
        global $wp_version;

        if(is_array($parameter))
        {
            $return_data = [];
            foreach($parameter as $key=>$value)
            {
                $return_data[$key] = $this->escape($value);
            }
        }
        else
        {
            if(version_compare($wp_version, '3.6', '<')) $return_data = $database->escape($parameter);
            else $return_data = esc_sql($parameter);
        }

        return $return_data;
    }

    public function prepare($query, ...$args)
    {
        // Get WordPress DB object
        $database = $this->get_DBO();

        return $database->prepare($query, $args);
    }
    
    /**
     * Returns WordPres DB Object
     * @author Webnus <info@webnus.net>
     * @global wpdb $wpdb
     * @return wpdb
     */
	public function get_DBO()
	{
		global $wpdb;
		return $wpdb;
	}
}
