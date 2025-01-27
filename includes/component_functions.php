<?php
    function convert_attributes($attributes, $exclude = null) {
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