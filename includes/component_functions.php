<?php
    /**
     * Converts an array of attributes into a string of HTML attributes
     * Excludes specified attributes and optionally adds class attributes
     * @param array $attributes The attributes to convert
     * @param array|null $exclude Attributes to exclude
     * @param bool $add_class Whether to add class attributes
     * @return string The formatted attribute string
     */
    function convert_attributes($attributes, $exclude = null, $add_class = false) {
        $attr = "";

        if($attributes){
            $attr = [];
            $exclude = $exclude ?? ["class", "type", "name", "value", "required"];

            foreach($attributes as $attribute => $value){
                if(!in_array($attribute, $exclude) && $value !== false){
                    $attr[] = is_null($value) ? "$attribute" : "$attribute = \"$value\"";
                }
            }
            $attr = implode(" ", $attr);
        }

        return $attr;
    }

    /**
     * Retrieves old input value from session
     * Used for form persistence after validation failures
     * @param string $key The input field key
     * @param string $default Default value if not found
     * @return string The old input value or default
     */
    function old($key, $default = ''){
        return $_SESSION['old_input'][$key] ?? $default;
    }

    /**
     * Checks if given URL matches current page URL
     * Used for highlighting active navigation items
     * @param string $url The URL to check
     * @return bool Whether URL matches current page
     */
    function is_current($url) {
        // Get the current URL without query parameters
        $currentUrl = $_SERVER['REQUEST_URI'];
    
        // Normalize both URLs to ensure comparison is accurate (strip any trailing slashes)
        $currentUrl = rtrim($currentUrl, '/');
        $url = rtrim($url, '/');
    
        return $currentUrl === $url;
    }

    /**
     * Returns required attribute string if condition is true
     * @param bool $required Whether field is required
     * @return string Required attribute or empty string
     */
    function required($required){
        return $required ? "required" : "";
    }

    /**
     * Creates an HTML select option element
     * @param string $text Option text
     * @param string $value Option value
     * @param array $attr Additional attributes
     * @return string The formatted option HTML
     */
    function create_select_option($text = "", $value = "", $attr = []){
        $attr = convert_attributes($attr);
        return "<option value=\"$value\" $attr>$text</option>";
    }

    /**
     * Gets class attribute value from attributes array
     * @param array $attributes Array containing attributes
     * @return string Class attribute value or empty string
     */
    function merge_class($attributes = []){
        return $attributes["class"] ?? "";
    }

    /**
     * Creates array with value and text keys for select options
     * @param string $value Key for option value
     * @param string $text Key for option text
     * @return array Array with value and text keys
     */
    function select_keys($value = "id", $text = "text"){
        return [
            "value" => $value,
            "text" => $text
        ];
    }

    /**
     * Merges multiple attribute arrays into one
     * @param array ...$attributes Arrays to merge
     * @return array Merged attributes array
     */
    function make_attributes(...$attributes){
        return array_merge($attributes);
    }

    /**
     * Processes list of options into key-value pairs
     * Converts simple array into associative array using values as both keys and values
     * @param array $options Array of options to process
     * @return array Processed options array
     */
    function process_options_list($options){
        $options_ = [];
        foreach($options as $option){
            if(is_array($option)){
                $options_ = $options;
                break;
            }
            if($option)
                $options_[strtolower($option)] = $option;
        }

        return $options_;
    }