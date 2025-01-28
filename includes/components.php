<?php
    require_once "component_functions.php";

    // component elements
    function input($type = "text", $label = "", $name = "", $value = "", $required = false, $attributes = []){
        if($type == "hidden"){
            return hidden_input($name, $value);
        }

        $attr = convert_attributes($attributes);
        $class_ = merge_class($attributes);
        $asterisks = $required ? "*" : "";
        $required = required($required);
        $error = $_SESSION["errors"][$name] ?? "";
        $value = old($name, $value);

        $input = "
            <label class=\"block text-sm\">
                <span class=\"text-gray-700 dark:text-gray-400\">$label $asterisks</span>
                <input 
                type=\"$type\" name=\"$name\" value=\"$value\" $required
                class=\"block w-full mt-1 text-sm dark:border-gray-600 dark:bg-gray-700 focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:text-gray-300 dark:focus:shadow-outline-gray form-input $class_\" 
                $attr />";
        if(!empty(trim($error))){
            $input .= error_span($error);
        }

        $input .= "</label>";
        return $input;
    }

    function error_span($text){
        return "
                <span class=\"text-xs text-red-600 dark:text-red-400\">
                  $text
                </span>
            ";
    }

    function hidden_input($name = "", $value = ""){
        return "<input type=\"hidden\" name=\"$name\" value=\"$value\" \>";
    }

    function input_h($type = "text", $label = "", $name = "", $value = "", $required = false, $sub_text = "", $attributes = []){
        $attr = convert_attributes($attributes);
        $asterisks = $required ? "*" : "";
        $required = required($required);
        $error = $_SESSION["errors"][$name] ?? "";
        $value = old($name, $value);
        
        $input = "
            <label class=\"block text-sms\">
                <span class=\"text-gray-700 dark:text-gray-400\">$label $asterisks</span>
                <input 
                type=\"$type\" name=\"$name\" value=\"$value\" $required
                class=\"block w-full mt-1 text-sm dark:border-gray-600 dark:bg-gray-700 focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:text-gray-300 dark:focus:shadow-outline-gray form-input\" 
                $attr />
                <span class=\"text-xs text-gray-600 dark:text-gray-400\">
                  $sub_text
                </span>";
            if(!empty(trim($error))){
                $input .= error_span($error);
            }
            $input .= "</label>";
            return $input;
    }

    function checkbox($name = "", $value = "", $text = "", $required = false, $attributes = []){
        $asterisks = $required ? "*" : "";
        $attr = convert_attributes($attributes);
        $required = required($required);
        return "
            <label class=\"flex items-center dark:text-gray-400\">
                <input
                    type=\"checkbox\"
                    name=\"$name\" value=\"$value\" $required
                    class=\"text-purple-600 form-checkbox focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:focus:shadow-outline-gray\"
                    $attr
                />
                <span class=\"ml-2\">
                    $text $asterisks
                </span>
            </label>
        ";
    }

    function select($name = "", $text = "", $options = [], $nullable = false, $multiple = false, $required = false, $value = "", $attributes = []){
        $attr = convert_attributes($attributes);
        $asterisks = $required ? "*" : "";
        $required = required($required);
        $value = old($name, $value);
        $multiple = $multiple ? "multiple" : "";
        $options_ = [];
        $class_ = merge_class($attributes);

        if($nullable){
            $options_[] = create_select_option("Select an option");
        }

        if($options){
            if($options){
                foreach($options as $key => $text_){
                    $options_[] = create_select_option($text_, $key, $key == $value ? attribute("selected") : []);
                }
            }
        }

        $options = implode("\n", $options_);

        return "
            <label class=\"block text-sm\">
                <span class=\"text-gray-700 dark:text-gray-400\">
                  $text $asterisks
                </span>
                <select
                  name=\"$name\" value=\"$value\"
                  class=\"block w-full mt-1 text-sm dark:text-gray-300 dark:border-gray-600 dark:bg-gray-700 form-multiselect focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:focus:shadow-outline-gray $class_\"
                  $multiple
                  $attr
                >
                  $options
                </select>
              </label>
        ";
    }

    function button($type = "", $text = "", $name = "", $value = "", $color = "", $attributes = []){
        $attr = convert_attributes($attributes);
        $color = empty($color) ? "blue" : $color;
        $class_ = merge_class($attributes);

        return "
        <button type=\"$type\" name=\"$name\" value=\"$value\" 
            class=\"block w-full px-4 py-2 text-sm font-medium leading-5 text-center text-white transition-colors duration-150 bg-$color-600 border border-transparent rounded-lg active:bg-$color-600 hover:bg-$color-700 focus:outline-none focus:shadow-outline-$color $class_\" $attr>
            $text
        </button>
        ";
    }

    function textarea($name="", $text="", $value="", $required = false, $attributes = []){
        $attr = convert_attributes($attributes);
        $required = required($required);
        $value = old($name, $value);

        return "
            <label class=\"block text-sm\">
                <span class=\"text-gray-700 dark:text-gray-400\">$text</span>
                <textarea
                  class=\"block w-full mt-1 text-sm dark:text-gray-300 dark:border-gray-600 dark:bg-gray-700 form-textarea focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:focus:shadow-outline-gray\"
                  rows=\"3\" name=\"$name\"
                  $attr
                >$value</textarea>
            </label>
        ";
    }

    function placeholder($text = ""){
        return attribute("placeholder", $text);
    }

    function attribute($attribute, $value = ""){
        return [$attribute => $value];
    }

    function data_attr($attr = "", $value = ""){
        return attribute("data-$attr", $value);
    }

    function header_text($text = ""){

    }

    function page_header($title){
        return "
            <h1 class=\"text-2xl font-semibold text-gray-800 dark:text-gray-200 mb-6\">$title</h1>
        ";
    }

    function form_message($message = "", $color = ""){
        $color = empty($color) ? "neutral" : "color";
        return "
            <p class=\"text-xs text-$color-600 dark:text-$color-400\">
                $message
            </p>
        ";
    }

    // navigation items
    function auth_nav($text = "", $link = "", $icon = "", $active = false){
        $link = $active ? "javascript:void()" : $link;
        $class = $active ? 
            "text-gray-800 hover:text-gray-800 dark:hover:text-gray-200 dark:text-gray-100" : 
            "hover:text-gray-800 dark:hover:text-gray-200" ;
        $active = $active ? "
            <span
                class=\"absolute inset-y-0 left-0 w-1 bg-purple-600 rounded-tr-lg rounded-br-lg\"
                aria-hidden=\"true\"
            ></span>
        " : "";

        return "
            <li class=\"relative px-6 py-3\">
                $active
                <a
                class=\"inline-flex items-center w-full text-sm font-semibold transition-colors duration-150 $class\"
                href=\"".url($link)."\"
                >
                <i class=\"w-5 $icon\"></i>
                
                <span class=\"ml-4\">$text</span>
                </a>
            </li>
        ";
    }

    function auth_nav_group_link($text = "", $icon = "", $items = []){
        $nav = "
            <button
                class=\"inline-flex items-center justify-between w-full text-sm font-semibold transition-colors duration-150 hover:text-gray-800 dark:hover:text-gray-200\"
                @click=\"togglePagesMenu\"
                aria-haspopup=\"true\"
            >
                <span class=\"inline-flex items-center\">
                    <i class=\"w-5 h-5 $icon\"></i>
                    <span class=\"ml-4\">$text</span>
                </span>
                <svg
                    class=\"w-4 h-4\"
                    aria-hidden=\"true\"
                    fill=\"currentColor\"
                    viewBox=\"0 0 20 20\"
                >
                    <path
                    fill-rule=\"evenodd\"
                    d=\"M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z\"
                    clip-rule=\"evenodd\"
                    ></path>
                </svg>
            </button>

            <template x-if=\"isPagesMenuOpen\">
                <ul
                    x-transition:enter=\"transition-all ease-in-out duration-300\"
                    x-transition:enter-start=\"opacity-25 max-h-0\"
                    x-transition:enter-end=\"opacity-100 max-h-xl\"
                    x-transition:leave=\"transition-all ease-in-out duration-300\"
                    x-transition:leave-start=\"opacity-100 max-h-xl\"
                    x-transition:leave-end=\"opacity-0 max-h-0\"
                    class=\"p-2 mt-2 space-y-2 overflow-hidden text-sm font-medium text-gray-500 rounded-md shadow-inner bg-gray-50 dark:text-gray-400 dark:bg-gray-900\"
                    aria-label=\"submenu\"
                >";

        foreach($items as $item){
            $nav .= nav_submenu_item($item["text"], $item["url"]);
        }
        
        $nav .= "
                </ul>
            </template>
        ";
    }

    function nav_submenu_item($text = "", $url = ""){
        return "
            <li
                class=\"px-2 py-1 transition-colors duration-150 hover:text-gray-800 dark:hover:text-gray-200\"
            >
            <a class=\"w-full\" href=\"".url($url)."\">$text</a>
            </li>
        ";
    }

    function table_start($attributes = []){
        $attr = convert_attributes($attributes);
        $class_ = merge_class($attributes);
        return "
            <div class=\"w-full overflow-hidden rounded-lg shadow-xs\">
              <div class=\"w-full overflow-x-auto\">
                <table class=\"w-full whitespace-no-wrap $class_\" $attr>
        ";
    }
    
    function tr_start(){
        return "<tr class=\"text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b dark:border-gray-700 bg-gray-50 dark:text-gray-400 dark:bg-gray-800\">";
    }

    function tr_end(){
        return close_tag("tr");
    }

    function td_empty($text, $col_count){
        return td($text, attributes: 
            array_merge(attribute("class", "text-center"),
            attribute("colspan", $col_count))
        );
    }

    function th($text = "", $attributes = []){
        $attr = convert_attributes($attributes);
        $class_ = merge_class($attributes);

        return "<th class=\"px-4 py-3 $class_\" $attr>$text</th>";
    }

    function td($text = "", $icon = '', $sub_text = "", $attributes = []){
        $attr = convert_attributes($attributes);
        $class_ = merge_class($attributes);
        $final_text = "";

        if($icon){
            $icon = "
                <div class=\"relative hidden w-8 h-8 mr-3 rounded-full md:block\" >
                    <img
                        class=\"object-cover w-full h-full rounded-full\"
                        src=\"$icon\"
                        alt=\"\"
                        loading=\"lazy\"
                    />
                    <div
                        class=\"absolute inset-0 rounded-full shadow-inner\"
                        aria-hidden=\"true\"
                    ></div>
                </div>
            ";
        }

        if($sub_text){
            $sub_text = "<p class=\"text-xs text-gray-600 dark:text-gray-400\">
                            $sub_text
                        </p>";
        }

        $final_text = "
            <div class=\"flex items-center text-sm\">
                $icon
                <div>
                    <p class=\"font-semibold\">$text</p>
                    $sub_text
                </div>
            </div>
        ";

        return "<td class=\"px-4 py-3 $class_\" $attr>
                    $final_text
                </td>";
    }

    function table_end(){
        return close_tag("table, div, div");
    }

    function alert($text = "", $type = "", $icon = ""){
        $icons = [
            "success" => "fas fa-check",
            "error" => "",
            "warning" => "fas fa-exclamation"
        ];

        if(empty($icon)){
            $icon = $icons[$type] ?? "fas fa-bell";
        }

        return "
            <div class=\"flex justify-between p-4 rounded-md bg-red-50 border border-red-300\">
                <div class=\"flex gap-3 sm:items-center\">
                    <div>
                        <i class=\"w-6 h-6 $icon\"></i>
                    </div>
                    <p class=\"text-red-600 sm:text-sm\">
                        $text
                    </p>
                </div>
            </div>
        ";
    }

    function thead_start(){
       return "<thead>\n\t<tr class=\"text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b dark:border-gray-700 bg-gray-50 dark:text-gray-400 dark:bg-gray-800\">";
    }

    function thead_end(){
        return close_tag("tr, thead");
    }

    function tbody_start($attributes = []){
        $attr = convert_attributes($attributes);
        $class_ = merge_class($attributes);

        return "<tbody class=\"bg-white divide-y dark:divide-gray-700 dark:bg-gray-800 $class_\" $attr>";
    }

    function tbody_end(){
        return close_tag("tbody");
    }

    function form_body_start(){
        return "<div class=\"grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6\">";
    }

    function form_body_end(){
        return close_tag("div");
    }

    function close_tag($tag){
        if(is_array($tag) || str_contains($tag, ",")){
            if(str_contains($tag, ",")){
                $tag = str_replace(" ", "", $tag);
                $tag = explode(",", $tag);
            }

            $tags = [];

            foreach($tag as $tag_){
                $tag[] = close_tag($tag_);
            }

            return implode("\n", $tags);
        }
        return "</$tag>";
    }