<?php 
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
    function stringifyWhere(string|array $where, string|array $bind = "") :string{
        $new_where = "";

        if(!is_array($where)){
            $new_where = $where;
        }else{
            if(is_array($bind)){
                // bind should be one less than the where
                while(count($bind) >= count($where)){
                    array_pop($bind);
                }
                
                foreach($bind as $key => $binder){
                    if(!empty($new_where)){
                        $new_where .= " ";
                    }

                    $new_where .= $where[$key]." ".$binder;
                }

                //add last parts of the where
                $new_where .= " ". end($where);
            }else if(!empty($bind) && 
                array_search(strtolower($bind), ["or", "and", "like"], true) !== false
            ){
                $new_where = implode(" $bind ", $where);
            }else{
                $new_where = implode(" ", $where);
            }
        }

        return $new_where;
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
?>