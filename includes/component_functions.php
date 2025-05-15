<?php
    function convert_attributes($attributes, $exclude = null, $add_class = false) {
        $attr = "";

        if($attributes){
            $attr = [];
            $exclude = $exclude ?? ["class", "type", "name", "value", "required"];

            foreach($attributes as $attribute => $value){
                if(!in_array($attribute, $exclude)){
                    $attr[] = "$attribute = \"$value\"";
                }
            }
            $attr = implode(" ", $attr);
        }

        return $attr;
    }

    function old($key, $default = ''){
        return $_SESSION['old_input'][$key] ?? $default;
    }

    function is_current($url) {
        // Get the current URL without query parameters
        $currentUrl = $_SERVER['REQUEST_URI'];
    
        // Normalize both URLs to ensure comparison is accurate (strip any trailing slashes)
        $currentUrl = rtrim($currentUrl, '/');
        $url = rtrim($url, '/');
    
        return $currentUrl === $url;
    }

    function required($required){
        return $required ? "required" : "";
    }

    function create_select_option($text = "", $value = "", $attr = []){
        $attr = convert_attributes($attr);
        return "<option value=\"$value\" $attr>$text</option>";
    }

    function merge_class($attributes = []){
        return $attributes["class"] ?? "";
    }

    function select_keys($value = "id", $text = "text"){
        return [
            "value" => $value,
            "text" => $text
        ];
    }

    function make_attributes(...$attributes){
        return array_merge($attributes);
    }

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