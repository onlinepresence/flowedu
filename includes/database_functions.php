<?php 
    /**
     * Present a dumped data in a more readable format
     * @param mixed $data The data to be checked
     */
    function formatDump(...$data) {
        echo "<pre>";
        foreach ($data as $item) {
            var_dump($item);
        }
        echo "</pre>";
    }

    /**
     * This provides an exit after a dumped data
     * @param mixed $data The data to be checked
     */
    function dd(...$data){
        echo "<pre>";
        foreach ($data as $item) {
            var_dump($item);
        }
        echo "</pre>";
        exit;
    }

    /**
     * Used to stringify the column
     * @param string|string[] $column This is the column to be stringified
     * @return string the stringified column
     */
    function stringifyColumn(string|array $column) :string{
        $new_column = "";

        if(!is_array($column)){
            $new_column = $column;
        }else{
            $new_column = implode(", ", $column);
        }

        return $new_column;
    }

    /**
     * Used to stringify the column
     * @param string|string[] $where This is the where query part to be stringified
     * @param string|string[] $binder This is what joins the parts together
     * @return string the stringified where part of the query string
     */
    function stringifyWhere(string|array $where, string|array $bind = "AND"): string {
        // if it's already a string, just return as is
        if (!is_array($where)) {
            return trim($where);
        }
    
        $parts = [];
    
        foreach ($where as $key => $value) {
            // CASE 1: Associative (column => value)
            if (!is_int($key)) {
                $column = $key;
                $operator = '=';
                $val = $value;
            }
            // CASE 2: [column, value] shorthand
            else if (is_array($value) && count($value) === 2) {
                [$column, $val] = $value;
                $operator = '=';
            }
            // CASE 3: [column, operator, value]
            else if (is_array($value) && count($value) === 3) {
                [$column, $operator, $val] = $value;
            }
            // CASE 4: a raw condition string
            else if (is_string($value)) {
                $parts[] = "$value";
                continue;
            }
            // Anything else: skip
            else {
                continue;
            }
    
            // Handle NULLs properly
            if (is_null($val)) {
                $condition = "$column IS NULL";
            } else {
                // Simple quoting for strings (you may replace with proper binding later)
                $quoted = is_numeric($val) ? $val : "'" . addslashes((string)$val) . "'";
                $condition = "$column $operator $quoted";
            }
    
            $parts[] = "$condition";
        }
    
        // Handle $bind array or string
        if (is_array($bind)) {
            // Truncate bind if too many
            while (count($bind) >= count($parts)) {
                array_pop($bind);
            }
    
            $sql = "";
            foreach ($bind as $i => $b) {
                $sql .= $parts[$i] . " $b ";
            }
            $sql .= end($parts);
        } else {
            $joiner = strtoupper(trim($bind)) ?: "AND";
            $sql = implode(" $joiner ", $parts);
        }
    
        return trim($sql);
    }

    /**
     * used to stringify the table query part
     * @param string|string[] $table The table query to stringify
     * @param string $join_type This is the type of join to be used
     * @param array $multiple_table Takes a list of tables that can appear multiple times during joins
     * 
     * @return string The formated version of the table query
     */
    function stringifyTable(string|array $tables, string $join_type, $multiple_table) :string{
        $new_tables = "";

        if(is_array($tables)){
            if(isset($tables[0]) && is_array($tables[0])){
                // at this point, tables should have the following keys
                // "join" => "table1 table2", "alias" => "tb1 tb2" and "on" => "id1 id2"
                foreach($tables as $table){
                    list($table1, $table2, $alias1, 
                        $alias2, $ref1, $ref2) = tableArraySplit($table);

                    //bind table1 to string
                    joinTableString($new_tables, $table1, $alias1, $join_type, $multiple_table);
                    joinTableString($new_tables, $table2, $alias2, $join_type, $multiple_table);
                    onTableString($new_tables, $table);
                }
            }elseif(!isset($tables[0])){
                list($table1, $table2, $alias1, 
                    $alias2, $ref1, $ref2) = tableArraySplit($tables);

                //bind table1 to string
                joinTableString($new_tables, $table1, $alias1, $join_type, $multiple_table);
                joinTableString($new_tables, $table2, $alias2, $join_type, $multiple_table);
                onTableString($new_tables, $tables);
            }else{
                // only table names should assume ids of the tables
                $join_type = empty($join_type) ? "JOIN" : strtoupper($join_type)." JOIN ";
                $new_tables = implode(" $join_type ", $tables);
            }
        }else{
            $new_tables = $tables;
        }

        return $new_tables;
    }

    /**
     * This is used to split the table into join, alias and on
     * @param array $table The table to be split
     * @return array An array of split table data
     */
    function tableArraySplit(array $table) :array{
        return array_merge(
            explode(" ", $table["join"]), 
            explode(" ", $table["alias"]), 
            explode(" ", $table["on"])
        );
    }

    /**
     * Function used to bind a table data
     * @param mixed $new_table The new table been formed
     * @param string $table The table from which to get details from
     * @param string $table_alias The alias of the said tables
     * @param string $join_type This is the type of join
     * @param array $multiple_table Takes a list of tables that can appear multiple times during joins
     * @return void all changes are done into the new_table variable
     */
    function joinTableString(&$new_table, $table, $table_alias, $join_type, $multiple_table){
        $join_type = empty($join_type) ? "JOIN" : strtoupper($join_type)." JOIN ";
        
        if(!str_contains($new_table, $table) || in_array($table, $multiple_table) || (in_array($table, array_keys($multiple_table)) && substr_count($new_table, $table) < $multiple_table[$table])){
            if(empty($new_table)){
                $new_table = $table;
            }else{
                $new_table .= " $join_type " . $table;
            }

            if(!empty($table_alias)){
                $new_table .= " $table_alias";
            }
        }
    }

    /**
     * Function to pull in the on section of the table data
     * @param mixed $new_table The table to be created 
     * @param array $table The table to retrieve the on sections from
     */
    function onTableString(&$new_table, $table){
        list($table1, $table2, $alias1, 
                    $alias2, $ref1, $ref2) = tableArraySplit($table);
        $lhs = empty($alias1) ? $table1 : $alias1;
        $rhs = empty($alias2) ? $table2 : $alias2;

        $new_table .= " ON $lhs.$ref1 = $rhs.$ref2";

        // if an add on has been added, append to this
        if(isset($table["add_on"])){
            addOnTableString($new_table, $table["add_on"]);
        }
    }

    /**
     * This add special conditions to the ON section
     * @param mixed $new_table The table been created
     * @param array|string $add_on The add on value, a list array or a full script 
     */
    function addOnTableString(&$new_table, $add_on){
        if(array_is_list($add_on)){
            $response = implode(" AND ", $add_on);
            $new_table .= " AND $response";
        }elseif(is_string($add_on)){
            $add_on = trim($add_on);
            $add_on = (strpos(strtoupper($add_on), "AND ") === 0 || strpos(strtoupper($add_on), "OR ") === 0) ? $add_on : "AND $add_on";
            $new_table .= " $add_on";
        }
    }

    /**
     * Verifies the existence of a table
     */
    function verifyTable($table_name) :bool{
        global $connect;
        return boolval($connect->query("SHOW TABLES LIKE '$table_name'")->num_rows);
    }

    /**
     * This is used to create the placeholders for database insertions
     * @param int $column_count The number of coulms
     */
    function createPlaceholder(int $column_count): string{
        $placeholder = [];

        while($column_count-- > 0){
            $placeholder[] = "?";
        }

        return implode(", ", $placeholder);
    }

    /**
     * This function is used to insert a new rows into a table
     * @param string $table_name This is the table name
     * @param array $data This is the data to be inserted [NB: It should be an associative array]
     * @return bool returns true or false if something
     */
    function data_insert(string $table_name, array $data) :bool|string{
        $response = false;
        global $errors;

        try {
            if(!is_array($data)){
                $errors["system_message"] = "Data provided is not an array";
                return false;
            }

            if(verifyTable($table_name)){
                $columns = array_keys($data);
                $values = array_values($data);
                $placeholders = createPlaceholder(count($columns));
    
                $sql = "INSERT INTO $table_name (".implode(", ", $columns).") VALUES ($placeholders)";
                $response = parse_statement($sql, create_param_types($columns, $table_name), $values);
                
                /*if(!$response){
                    $errors['system_message'] = "Data could not be added to table '$table_name'";
                }*/
            }else{
                $errors["system_message"] = "Table '$table_name' not found";
            }
        } catch (Throwable $th) {
            $errors["system_message"] = $th->getMessage();
        }

        return $response;
    }

    /**
     * specify the parameter type for columns in prepared statments
     * @param string $type The type of the column
     * @return string
     */
    function get_param_type($type) {
        $type = strtolower($type); // Convert to lowercase for case-insensitive matching
    
        // Integer types
        if (preg_match('/^(tinyint|smallint|mediumint|int|bigint)$/', $type)) return 'i';
    
        // Floating-point and decimal types
        if (preg_match('/^(decimal|double|float|real)$/', $type)) return 'd';
    
        // Binary and blob types (store as binary)
        if (preg_match('/^(blob|binary|varbinary|bit)$/', $type)) return 'b';
    
        // Default to string (covers varchar, text, char, etc.)
        return 's';
    }

/**
 * Format column names by prepending table names or aliases based on table structure
 * @param array $columns List of columns to format
 * @param array $tables Array of tables with optional aliases
 * @return array Formatted column names
 */
function formatColumns(array $columns, array $tables): array {
    global $connect;
    $formatted_columns = [];
    $table_columns = [];
    $table_count = count($tables);
    
    // Get all column names for each table
    foreach ($tables as $key => $value) {
        $table_name = is_array($value) ? key($value) : $value;
        $alias = is_array($value) ? current($value) : null;
        
        $query = "SHOW COLUMNS FROM $table_name";
        $result = $connect->query($query);
        
        if ($result) {
            $table_columns[$table_name] = [
                'columns' => array_map(fn($row) => $row['Field'], $result->fetch_all(MYSQLI_ASSOC)),
                'alias' => $alias
            ];
        }
    }

    // Process each column
    foreach ($columns as $column) {
        // Skip if column already has table/alias prefix
        if (strpos($column, '.') !== false) {
            $formatted_columns[] = $column;
            continue;
        }

        $found = false;
        foreach ($table_columns as $table_name => $info) {
            if (in_array($column, $info['columns'])) {
                if ($table_count > 1) {
                    // Multiple tables - prepend alias or table name
                    $prefix = $info['alias'] ?? $table_name;
                    $formatted_columns[] = "$prefix.$column";
                } else {
                    // Single table - keep column name as is
                    $prefix = $info['alias']."." ?? "";
                    $formatted_columns[] = $prefix.$column;
                }
                $found = true;
                break;
            }
        }

        // If column wasn't found in any table, keep it unchanged
        if (!$found) {
            $formatted_columns[] = $column;
        }
    }

    return $formatted_columns;
}

    /**
     * Get the column type from the database
     * @param string $table name of the table
     * @return array 
     */
    function get_column_types(string $table):array {
        global $connect;

        $types = [];
        $query = "SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?";
        
        if ($stmt = $connect->prepare($query)) {
            $stmt->bind_param("s", $table);
            $stmt->execute();
            $result = $stmt->get_result();
    
            while ($row = $result->fetch_assoc()) {
                $types[strtolower($row['COLUMN_NAME'])] = get_param_type($row['DATA_TYPE']);
            }
    
            $stmt->close();
        }
        
        return $types;
    }

    /**
     * This creates the types for a prepared statement
     * @param array $columns The columns
     * @param string $table_name The name of the table
     * @return string
     */
    function create_param_types(array $columns, string $table_name) :string{
        $response = [];

        $table_columns = get_column_types($table_name);

        foreach($columns as $column){
            $response[] = $table_columns[strtolower($column)];
        }

        return implode("", $response);
    }

    /**
     * Auto-increment id from the last successful INSERT executed through parse_statement().
     * After mysqli::commit(), $connect->insert_id may be 0 while mysqli_stmt::$insert_id stays correct.
     */
    function db_last_insert_id(): int {
        return (int)($GLOBALS['db_last_insert_id'] ?? 0);
    }

    /**
     * This function is used to parse prepared statememts
     * Effective for INSERT, UPDATE and DELETE statements
     * @param string $prepared_statement This is the prepared statement
     * @param string $types Specifies the types for the columns to be parsed
     * @param array $values This is the list of values to be inserted
     * @return bool Returns true if successful, or false if failure
     */
    function parse_statement(string $prepared_statement, string $types, array $values) :bool{
        global $connect, $errors;
        $response = false;

        $connect->begin_transaction();
        try{
            unset($GLOBALS['db_last_insert_id']);
            $stmt = $connect->prepare($prepared_statement);
            $stmt->bind_param($types, ...$values);
            $response = $stmt->execute();

            if(!$response){
                throw new Exception($stmt->error);
            }

            $connect->commit();

            if ($response && preg_match('/^\s*INSERT\s+/i', $prepared_statement)) {
                $GLOBALS['db_last_insert_id'] = (int) $stmt->insert_id;
            }
        }catch(Throwable $th){
            $errors["system_message"] = $th->getMessage();
            $connect->rollback();
        }

        return $response;
    }

    /**
     * This function gets the form data from a request
     * @param string|array|null $upload_dir A string of the upload directory or an associative array of them. Files are stored in assets folder
     * @param array $exclude Some more keys to be excluded
     * @param array $key_change Specify an array with keys to be renamed in the new array
     * @param array $preserve This is used to specify some keys that need to be preserved, especially for bools and integer values with 0 as a valid value
     * @return array
     */
    function form_data(string|array|null $upload_dir = null, array $exclude = [], array $key_change = [], array $preserve = []) {
        global $errors;
        $data = [];
        $excludedKeys = array_merge(["submit", "request_type", "response_type"], $exclude); // Default + user-specified keys
        $folder_location = $upload_dir && is_string($upload_dir) ? asset($upload_dir, false, $upload_dir) : null;

        // Ensure the main upload directory exists
        if (is_string($upload_dir) && !is_dir($folder_location)) {
            mkdir($folder_location, 0777, true);
        }

        // Process text input (excluding specific keys)
        foreach ($_REQUEST as $key => $value) {
            if (!in_array($key, $excludedKeys)) {
                $newKey = $key_change[$key] ?? $key; // Rename key if specified
                $value = is_array($value) ? $value : trim($value);
                $data[$newKey] = empty($value) && !in_array($key, $preserve) ? null : $value;
            }
        }

        // Process file uploads
        foreach ($_FILES as $key => $file) {
            if ($file['error'] === UPLOAD_ERR_OK) {
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = uniqid("file_") . "." . $extension;

                // Determine upload directory
                if (is_array($upload_dir) && isset($upload_dir[$key])) {
                    $filePath = rtrim(asset($upload_dir[$key], false, true), "/") . "/" . $filename;
                    if (!is_dir(asset($upload_dir[$key], false, true))) mkdir(asset($upload_dir[$key], false, true), 0777, true);
                } else {
                    $filePath = rtrim($folder_location, "/") . "/" . $filename;
                }

                if (move_uploaded_file($file['tmp_name'], $filePath)) {
                    $newKey = $key_change[$key] ?? $key; // Rename key if specified
                    $data[$newKey] = str_replace(asset('', false, true), '', $filePath);
                } else {
                    $errors[$key] = "File received an error while uploading.";
                }
            }
        }

        return $data; // Return processed request data
    }

    /**
     * Update some set of records
     * @param string[] $original The originial or initial values
     * @param string[] $new_data The new replacement data 
     * @param string $table The name of the table to be updated
     * @param string|array $conditions The set of condition(s) to be checked. Specify just the names of the keys
     * @param string|array $condition_binds This holds either AND, OR or any of the binds for the conditions
     * @return bool|string returns true if successful or a string of message
     */
    function update(array $original, array $new_data, string $table, array $conditions, string|array $condition_binds = "") :bool|string{
        $response = false;

        //grab column keys and values
        $keys = array_keys($new_data);
        $values = array_values($new_data);

        //set column string
        $columns = updateColumns($keys);

        //set condition string
        $conditions_ = updateWhere($conditions, $original, $values, $condition_binds);
        
        $sql = "UPDATE $table SET $columns WHERE $conditions_";

        // parse the statement
        $datatypes = create_param_types(array_merge($keys, $conditions), $table);
        $response = parse_statement($sql, $datatypes, $values);

        return $response;
    }

    /**
     * Used to create the columns for the update statement
     * @param array $columns An array of columns
     * @return string
     */
    function updateColumns(array $columns) :string{
        $response = [];

        foreach($columns as $column){
            $response[] = "`$column` = ?";
        }

        return implode(", ",$response);
    }

    /**
     * Creates the where condition for the update statment
     * @param array $keys The keys for the condition
     * @param array $subject
     * @param array $values
     */
    function updateWhere(array $keys, array $subject, array &$values, string|array $condition_binds) :string{
        $response = [];

        foreach($keys as $key){
            $response[] = "$key = ?";
            
            //pass value into values
            // array_push($values, $subject[$key]);
            $values[] = $subject[$key];
        }

        return stringifyWhere($response, $condition_binds);
    }

    /**
     * This is used to create a where condition from an key=>value pair
     * @param $array The array to be processed
     * @param string $join The value that joins them
     * @return array
     */
    function create_where_from_array($array, string $join = "="){
        $response = [];
        foreach($array as $key => $value){
            $value = is_numeric($value) ? $value : "'$value'";
            $response[] = "$key $join $value";
        }

        return $response;
    }

    /**
     * Delete or soft delete from a table
     *
     * @param string $table The table name
     * @param string|array $condition WHERE condition(s) [key => value] or "key=value"
     * @param string|array $condition_binds Bindings for the condition(s)
     * @param bool $softDelete Set to TRUE to soft delete (update deleted_at), FALSE for hard delete
     * @return bool True if operation was successful, otherwise false
     */
    function delete(string $table, string|array $condition, string|array $condition_binds = "", bool $softDelete = false): bool {
        global $connect;
        $response = false;

        try {
            // Convert condition array/string into SQL "key = ?" and bindings
            $condition = stringifyWhere($condition, $condition_binds);

            $sql = $softDelete ? 
                "UPDATE $table SET deleted_at = NOW() WHERE $condition" : 
                "DELETE FROM $table WHERE $condition";

            // Run query
            if ($connect->query($sql)) {
                $response = true;
            }

        } catch (Throwable $th) {
            logThrowable($th);
        }

        return $response;
    }


    /**
     * This function is used to generate an sql query
     * 
     * @param string|array $columns This receives the roles to fetch
     * @param string|array $table Receives table name
     * @param string|array $where Receives a where clause command
     * @param int $limit Number of rows to deliver. Default is 1. Use 0 to fetch everything
     * @param array|string $where_binds This is used to bind where conditions
     * @param string $join_type This is the type of join to be used in a table
     * @param string|array $group_by This is used in case there is a group function 
     * @param string|array $order_by order results by some columns
     * @param bool $asc order is in ascending order by default
     * @param array $multiple_table Takes a list of tables that can appear multiple times during joins
     * @param int $offset Offset value used for pagination
     * @param string $sort_key       Column name used for keyset pagination.
     *                               When provided, the query will use a WHERE condition
     *                               such as "WHERE <sort_key> > <last_key_value>" (or "<" if descending)
     *                               instead of relying solely on OFFSET-based pagination.
     *                               This approach provides faster performance for large datasets.
     * @param mixed  $last_key_value The last known value of the sort key from the previous page.
     *                               It determines where the next page of results should start.
     *                               Typically this is the value of the sort key from the final record
     *                               of the current page (e.g., the last record's "id" or timestamp).
     *                               Set to null for the first page.
     * 
     * @return string
     */
    function create_query_string(string|array $columns,string|array $table,string|array $where = "",
        int $limit = 1, string|array $where_binds = "", string $join_type = "", string|array $group_by = "", 
        string|array $order_by = "", bool $asc = true, array $multiple_table = [], int $offset = 0, string $sort_key = "",
        mixed $last_key_value = null
    ): string {
    
        $columns = stringifyColumn($columns);
        $table = stringifyTable($table, $join_type, $multiple_table);
        $where = stringifyWhere($where, $where_binds);
    
        // Add keyset condition
        if (!empty($sort_key) && $last_key_value !== null) {
            $comparison = $asc ? '>' : '<';
            $key_clause = "$sort_key $comparison " . (is_numeric($last_key_value) ? $last_key_value : "'$last_key_value'");
            $where = !empty($where) ? "$where AND $key_clause" : $key_clause;
        }
    
        $sql = "SELECT $columns FROM $table";
        $sql .= !empty($where) ? " WHERE $where" : "";
    
        if (!empty($group_by)) {
            $sql .= " GROUP BY " . (is_array($group_by) ? implode(", ", $group_by) : $group_by);
        }
    
        if (!empty($order_by)) {
            $sql .= " ORDER BY " . (is_array($order_by) ? implode(", ", $order_by) : $order_by);
            $sql .= $asc ? " ASC" : " DESC";
        }
    
        if ($limit > 0) {
            $sql .= " LIMIT $limit";
            if ($offset > 0 && empty($sort_key)) {
                $sql .= " OFFSET $offset";
            }
        }
    
        return $sql;
    }
    

    /**
     * This function queries the database, usually for select statements
     * 
     * @param mysqli $adapter The sql connection adapter
     * @param string $sql The sql string
     * @param mixed $error_value Optional value to be displayed when results are false. Default is empty
     * @return mixed
     */
    function fetch_query(mysqli $adapter, string $sql, $error_value = false, $limit = 1){
        $query = $adapter->query($sql);

        if($query->num_rows > 0){
            $result = $limit == 1 ? $query->fetch_assoc() : $query->fetch_all(MYSQLI_ASSOC);
        }else{
            $result = $error_value;
        }

        return $result;
    }

    /**
     * Function to directly query database
     * 
     * @param string|array $columns This receives the roles to fetch
     * @param string|array $table Receives table name
     * @param string|array $where Receives a where clause command
     * @param int $limit Number of rows to deliver. Default is 1. Use 0 to fetch everything
     * @param array|string $where_binds This is used to bind where conditions
     * @param string $join_type This is the type of join to be used in a table
     * @param string|array $group_by This is used in case there is a group function 
     * @param string|array $order_by order results by some columns
     * @param bool $asc order is in ascending order by default
     * @param array $multiple_table Takes a list of tables that can appear multiple times during joins.
     *                              Do something like [table_name => max_occurences] If the table must have 
     *                              multiple occurences for a fixed number of times
     * @param int $offset Offset to be used during pagination
     * @param string $sort_key       Column name used for keyset pagination.
     *                               When provided, the query will use a WHERE condition
     *                               such as "WHERE <sort_key> > <last_key_value>" (or "<" if descending)
     *                               instead of relying solely on OFFSET-based pagination.
     *                               This approach provides faster performance for large datasets.
     * @param mixed  $last_key_value The last known value of the sort key from the previous page.
     *                               It determines where the next page of results should start.
     *                               Typically this is the value of the sort key from the final record
     *                               of the current page (e.g., the last record's "id" or timestamp).
     *                               Set to null for the first page.
     * Do something like [table_name => max_occurences] If the table must have multiple occurences for a fixed number of times
     * 
     * @return string|array returns a(n) array|string of data or error
     */
    function fetchData(string|array $columns, string|array $table, 
        string|array $where = "", int $limit = 1, string|array $where_binds = "",
        string $join_type = "", string|array $group_by = "", string|array $order_by = "", bool $asc = true,
        array $multiple_table = [], int $offset = 0, string $sort_key = "", mixed $last_key_value = null
    ){
        global $connect;

        // generate an sql
        $sql = create_query_string(
            $columns, $table, $where, $limit, $where_binds, 
            $join_type, $group_by, $order_by, $asc, $multiple_table, $offset, $sort_key, $last_key_value
        );

        try{
            $result = fetch_query($connect, $sql, limit: $limit);
        }catch(Throwable $th){
            $result = throwableMessage($th, $sql);
        }

        return $result;
    }
?>