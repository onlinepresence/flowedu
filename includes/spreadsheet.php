<?php
    use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
    use PhpOffice\PhpSpreadsheet\Cell\DataType;
    use PhpOffice\PhpSpreadsheet\IOFactory;
    use PhpOffice\PhpSpreadsheet\Spreadsheet;
    use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

    require_once "$rootPath/vendor/autoload.php";

    class Validation{
        /**
         * @var int $max_cols Sets the maximum number of columns for the sheet
         */
        public int $max_cols = 0;

        /**
         * @var int $row_start Used to define the row on which data should start reading
         * defaults on the second row
         */
        public int $row_start = 2;

        /**
         * @var string|array $columns Holds the name of the columns that are passed
         */
        private $columns = null;

        /**
         * @var bool $has_added_id It is used to check if the id has been added while counting the number of columns
         */
        private bool $has_added_id = false;

        /**
         * @var string The expected value to be found in the first heading.
         * Primarily used to make sure that the expected file template is what has been provided
         */
        public string $first_heading_text = "";

        /**
         * @var null|Worksheet $sheet The worksheet to be processed [usually used during validation]
         */
        private ?Worksheet $sheet = null;

        /**
         * @var string $index_column This is used to define where the index key column can be found
         * Defaults at column A
         */
        public string $index_column = "A";

        /**
         * @var bool $set_columns_from_sheet This is used to set the maximum column and get the columns from the sheet passed
         * When set to true, in the case where the max_cols or columns have been set, it automatically skips getting the details from the sheet 
         * Defaults at true 
         */
        public bool $set_columns_from_sheet = true;

        /**
         * @param mixed $columns The columns of the incoming result data. If its a number, it is automatically passed into the max_cols attribute
         * @param bool $include_id Include the id when setting the maximum colums
         */
        public function __construct($columns = null, $include_id = false){
            if(is_integer($columns)){
                $this->max_cols = intval($columns);
            }elseif(!empty($columns)){
                $this->setMaxColsWithColumns($columns, $include_id);
            }

            $this->columns = $columns;
        }

        /**
         * Use this function to set the maximum column from a string or array of columns. If string, separate by commas
         * This function cannot be used more than once since if the include_id is set to true, it ceases to run another time
         * @param string|array $columns The columns to be used to set the highest data
         * @param bool $include_id This is used to check if it should count an id column or not. If an id column already in columns, this should be set to false
         */
        private function setMaxColsWithColumns($columns, $include_id = false){
            // do nothing if columns is empty
            if(!empty($columns) && !$this->has_added_id){
                // if columns is a string, change to array for counting
                if(!is_array($columns)){
                    $columns = str_replace(" ", "", $columns);
                    $columns = explode(",", $columns);
                }

                $this->max_cols = $include_id ? count($columns) + 1 : count($columns);
                $this->has_added_id = $include_id;
            }
            
        }

        /**
         * This is used to get the columns from the worksheet
         */
        private function set_sheet_columns(){
            if($this->set_columns_from_sheet){
                if(is_null($this->sheet))
                    return "No worksheet for column extraction provided";

                if(!$this->max_cols && empty($this->columns)){
                    $row = $this->get_heading_row();
                    $max_column = $this->sheet->getHighestColumn($row);
                    $column_count = Coordinate::columnIndexFromString($max_column);
                    $columns = [];

                    for($column = 1; $column <= $column_count; $column++){
                        $columns[] = $this->sheet->getCell(Coordinate::stringFromColumnIndex($column).$row)->getValue();
                    }

                    $this->max_cols = count($columns);
                    $this->columns = implode(", ", $columns);

                    // id column will automatically added, so set its flag
                    $this->has_added_id = true;
                }
            }
        }

        /**
         * This is used to set the worksheet to be used
         * @param Worksheet $sheet The worksheet
         */
        public function set_sheet(Worksheet $worksheet){
            $this->sheet = $worksheet;

            // auto set the columns if it hasnt been set already
            $this->set_sheet_columns();
        }

        /**
         * This is used to set the trigger that max_cols value should be incremented by 1
         * Does nothing if columns or maximum column has not been set
         */
        public function countIDColumn(){
            if($this->max_cols > 0 && !$this->has_added_id){
                ++$this->max_cols;
                $this->has_added_id = true;
            }elseif(!empty($this->columns)){
                $this->setMaxColsWithColumns($this->columns, true);
            }
        }

        /**
         * Used for validating the necessary field data
         * @return string|bool
         */
        public function validate(){
            $response = "No Worksheet was provided";

            if(!is_null($this->sheet)){
                $max_column = $this->sheet->getHighestDataColumn();
                $columns = createColumnHeader($this->max_cols);
                $header_row = $this->get_heading_row();
                $first_cell = $this->sheet->getCell("A$header_row")->getValue();

                // remove double spaces
                $first_cell = str_replace("  ", " ", $first_cell);

                if($max_column !== end($columns)){
                    $response = "Maximum column does not match expected maximum column";
                }elseif(!empty($this->first_heading_text) && strtolower($first_cell) !== strtolower($this->first_heading_text)){
                    $response = "First Cell Value does not match expected cell value";
                }else{
                    $response = true;
                }
            }

            return $response;
        }

        /**
         * This is used to return the necessary array keys
         * If the column is provided, it sends an arrayed format, if it isn't then the heading are lowercased and used as the keys
         * @return array|null
         */
        public function create_array_keys(){
            $response = null;

            if(!is_null($this->sheet)){
                $response = $this->columns;

                if(!is_array($response)){
                    $response = str_replace([", ", " , ", " ,"], ",", $response);
                    $response = explode(",", $response);
                }

                // if the columns provided do not match the max_cols, then it might be missing the index key
                if($this->max_cols > 0 && count($response) != $this->max_cols){
                    $index_col_numeric = Coordinate::columnIndexFromString($this->index_column);
                    $value = $this->sheet->getCell($this->index_column.$this->get_heading_row())->getValue();
                    $value = str_replace([" ", "  ", " _"], "_", $value);
                    
                    array_splice($response, $index_col_numeric - 1, 0, $value);
                }

                // convert values to lowercase
                $response = array_map("strtolower", $response);
            }

            return $response;
        }

        /**
         * This is used to return the row number of the heading
         * @return int
         */
        private function get_heading_row(){
            $heading_row = $this->row_start - 1;

            return $heading_row < 1 ? 1 : $heading_row;
        }
    }

    /**
     * Converts any text into an array_key
     * @param string|array $value The value or array to be processed
     * @return string|array
     */
    function convert_to_array_key($value){
        $replace = [" ", "  ", " _", ".", "&", "-"];
        $with = "_";
        $response = null;

        if(is_array($value)){
            $response = array();

            foreach($value as $key => $val){
                $key = strtolower(str_replace($replace, $with, $key));
                $response[$key] = $val;
            }
        }else{
            $value = str_replace($replace, $with, $value);
            $response = strtolower($value);
        }

        return $response;
    }

    /**
     * Creates a column header
     * @param int|string $maxCols The total headings or highest column name
     * @return array
     */
    function createColumnHeader($maxCols) {
        // If the parameter is a column letter, convert it to a number
        if (!is_numeric($maxCols)) {
            $maxCols = Coordinate::columnIndexFromString($maxCols);
        }
    
        $columns = [];
        for ($i = 1; $i <= $maxCols; $i++) {
            $columns[] = Coordinate::stringFromColumnIndex($i);
        }
        return $columns;
    }

    /**
     * Used to set up the spreadsheet
     * @param array $results The result array. It should be in the format [0 => [], 1 => []] etc
     * @param array $exception_headers These are headers (keys) which should not be included when returning the file 
     * @param array $custom_names These are custom names to give to certain keys.
     * Its in the format [$key => $title]
     * 
     * @return Spreadsheet 
     */
    function setup_spreadsheet($results, $exception_headers = [], $custom_names = []) :Spreadsheet{
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $field_names = array_keys($results[0]);

        $current_col_names = createColumnHeader(count($field_names) - count($exception_headers));

        // create the headers
        $headerCounter = 0;
        $headers = [];
        foreach($field_names as $field_name){
            if(!in_array($field_name, $exception_headers)){
                $cellName = $current_col_names[$headerCounter]."1";
    
                //enter value into cells
                $field_name = isset($custom_names[strtolower($field_name)]) ? $custom_names[strtolower($field_name)] : separateNames($field_name);
                $field_name = html_entity_decode(strtolower($field_name));
                $sheet->setCellValue($cellName, ucwords($field_name));
                $headers[] = separateNames($field_name);
    
                //move to next header
                $headerCounter++;
            }
        }

        // reset counters
        $headerCounter = 0;
        $rowCounter = 2;    // data starts on row 2

        foreach($results as $result){
            foreach($result as $column_name => $data){
                if(!in_array($column_name, $exception_headers)){
                    $cellName = $current_col_names[$headerCounter++].$rowCounter;
                    $data_type = is_numeric($data) ? DataType::TYPE_NUMERIC : DataType::TYPE_STRING;

                    $sheet->setCellValueExplicit($cellName, html_entity_decode($data), $data_type);
                }
            }

            // reset counters and move to next row
            $headerCounter = 0;
            $rowCounter++;
        }

        //automatically size up columns
        for( $column = "A"; $column != $sheet->getHighestColumn(); $column++){
            $sheet->getColumnDimension($column)->setAutoSize(TRUE);
        }

        return $spreadsheet;
    }

    /**
     * This file contains functions and utilities for handling spreadsheet operations.
     * 
     * @param Spreadsheet $spreadsheet The spreadsheet object. Create with create_spreadsheet function
     * @param string $filename The file name
     * @param ?string $directory_name The directory name from root
     * @param bool $backup_file This is used to determine if the file should be created on disk or from stream
     */
    function create_spreadsheet(Spreadsheet $spreadsheet, string $filename, $directory_name = null, bool $backup_file = false){
        // clear any outputs
        ob_end_clean();

        //create an IOFactory to ask for location for save file
        $writer = IOFactory::createWriter($spreadsheet, "Xlsx");

        if($backup_file){
            $directory_name = $directory_name ? "$directory_name/" : "";
            $filepath = $_SERVER["DOCUMENT_ROOT"]."/$directory_name";

            if(!is_dir($filepath)){
                $old_mask = umask();
                umask(0);
                mkdir($filepath, 0755, true);
                umask($old_mask);
            }

            $filepath .= "$filename.xlsx";

            $writer->save($filepath);
        }else{
            //set header to accept excel
            header('Content-type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

            //define file name
            header("Content-Disposition: attachment;filename=\"$filename.xlsx\"");

            header('Cache-Control: max-age=0');

            //save to php output
            $writer->save("php://output");
        }
    }

    /**
     * A function to separate names in camelCase to camel case
     * snake_cases are automatically transformed
     * 
     * @param string $name This is the name to be separated
     * @return string returns the separated name
     */
    function separateNames(string $name){
        $temp_name = $name; 

        if(strpos($name, "_")){
            $temp_name = str_replace("_"," ", $name);
        }else{
            $temp_name = "";
            for($i=0; $i < strlen($name); $i++){
                if($name[$i] != strtoupper($name[$i])){
                    $temp_name .= $name[$i];
                }elseif(($i-1 >= 0) && $name[$i] == strtoupper($name[$i]) && $name[$i-1] == strtoupper($name[$i-1])){
                    $temp_name .= $name[$i];
                }else{
                    //separate with space
                    $temp_name .= " ".$name[$i];
                }
            }
        }
        

        return $temp_name;
    }

    /**
     * This is used to extract data from a spreadsheet file
     * @param string $input_name The name of the file input
     * @param ?Validation $validation This holds validation options. Check extract_data for the format
     * @return array
     */
    function extract_data($input_name, $validation = null){
        // default response
        $proceed = true; $message = "";

        if(is_null($validation)){
            $proceed = false;
            $message = "Validation data is required";                
        }

        // check file availability
        if(!isset($_FILES[$input_name]) || is_null($_FILES[$input_name]["tmp_name"])){
            $message = "No file uploaded";
            $proceed = false;
        }

        // check file extension validity
        if(!file_is_valid($input_name)){
            $message = "File format is invalid. An excel 'xls' or 'xlsx' file is required";
            $proceed = false;
        }

        if($proceed){
            $message = spreadsheet_data($input_name, $validation, $proceed);
        }

        return [
            "status" => $proceed, "data" => $message
        ];
    }

    /**
     * This extracts the data from the spreadsheet file
     * @param string $input_name The input name
     * @param Validation $validation The validation context
     * @param ?bool $status This is usually a pointer to a boolean variable. It provides status where the need be
     * @return array|string
     */
    function spreadsheet_data($input_name, $validation, &$status = null){
        // set status to false by default
        $status = false;

        //create a reader
        $reader = IOFactory::createReader('Xlsx');
    
        //create a spreadsheet instance
        $spreadsheet = $reader->load($_FILES[$input_name]["tmp_name"]);
        
        //get the working sheet
        $sheet = $spreadsheet->getActiveSheet();

        // parse sheet into validation
        $validation->set_sheet($sheet);
        
        //get maximum row
        $max_row = $sheet->getHighestDataRow();

        // make validation checks
        if(($message = $validation->validate()) !== true){
            return $message;
        }

        // get array keys
        $sheet_header = $validation->create_array_keys();

        if(is_null($sheet_header)){
            return "Array columns could not be created. Worksheet could not be identified or was not provided";
        }

        // create a range of columns
        $columns = createColumnHeader($validation->max_cols);
        
        $data = [];

        // read data
        for($row = $validation->row_start; $row <= $max_row; $row++){
            $row_data = [];
            for($col = 0; $col < $validation->max_cols; $col++){
                // get the cell value [calculate in case its a formula]
                $value = $sheet->getCell($columns[$col].$row)->getCalculatedValue();
                $row_data[] = $value;
            }

            $data[] = array_combine($sheet_header, $row_data);
        }

        // if execution reaches this part of the function, then set status to true
        $status = true;

        return $data;
    }

    /**
     * This checks if the file is an xlsx or xls file
     * @param string $input_name The file input name
     * @return bool
     */
    function file_is_valid($input_name){
        $accepted = ["xls", "xlsx"];

        //retrieve file extension
        $ext = strtolower(pathinfo($_FILES[$input_name]["name"], PATHINFO_EXTENSION));

        return in_array($ext, $accepted);
    }
