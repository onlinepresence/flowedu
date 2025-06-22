<?php
    require_once "component_functions.php";

    /**
     * Creates an input field with label and error handling
     * @param string $type Input type (text, password, etc)
     * @param string $label Label text
     * @param string $name Input name attribute
     * @param string $value Input value
     * @param bool $required Whether field is required
     * @param array $attributes Additional HTML attributes
     * @return string HTML input element
     */
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

    /**
     * Creates an error message span element
     * @param string $text Error message text
     * @return string HTML span element with error styling
     */
    function error_span($text){
        return "
                <span class=\"text-xs text-red-600 dark:text-red-400\">
                  $text
                </span>
            ";
    }

    /**
     * Creates a hidden input field
     * @param string $name Input name attribute
     * @param string $value Input value
     * @return string HTML hidden input element
     */
    function hidden_input($name = "", $value = ""){
        return "<input type=\"hidden\" name=\"$name\" value=\"$value\" \>";
    }

    /**
     * Creates an input field with label, help text and error handling
     * @param string $type Input type
     * @param string $label Label text
     * @param string $name Input name
     * @param string $value Input value
     * @param bool $required Whether field is required
     * @param string $sub_text Help text below input
     * @param array $attributes Additional HTML attributes
     * @return string HTML input element with label and help text
     */
    function input_h($type = "text", $label = "", $name = "", $value = "", $required = false, $sub_text = "", $attributes = []){
        $attr = convert_attributes($attributes);
        $asterisks = $required ? "*" : "";
        $required = required($required);
        $error = $_SESSION["errors"][$name] ?? "";
        $value = old($name, $value);
        
        $input = "
            <label class=\"block text-sm\">
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

    /**
     * Creates a checkbox input with label
     * @param string $name Checkbox name
     * @param string $value Checkbox value
     * @param string $text Label text
     * @param bool $required Whether field is required
     * @param array $attributes Additional HTML attributes
     * @return string HTML checkbox element with label
     */
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
                </span>".(empty($error) ? "" : error_span($error))."
            </label>
        ";
    }

    /**
     * Creates a select dropdown with options
     * @param string $name Select name
     * @param string $text Label text
     * @param array $options Array of options
     * @param bool|string $nullable Whether to add a null option
     * @param bool $multiple Allow multiple selections
     * @param array $keys Custom keys for option values/text
     * @param bool $required Whether field is required
     * @param string $value Selected value
     * @param array $attributes Additional HTML attributes
     * @return string HTML select element
     */
    function select($name = "", $text = "", $options = [], $nullable = false, $multiple = false, $keys = [], $required = false, $value = "", $attributes = []){
        $attr = convert_attributes($attributes);
        $asterisks = $required ? "*" : "";
        $required = required($required);
        $value = old($name, $value);
        $multiple = $multiple ? "multiple" : "";
        $options_ = [];
        $error = $_SESSION["errors"][$name] ?? "";
        $class_ = merge_class($attributes);
        $keys = empty($keys) ? select_keys() : $keys;

        if($nullable){
            $options_[] = create_select_option($nullable === true ? "Select an option" : $nullable);
        }

        if($options){
            if(array_is_list($options)){
                $options = process_options_list($options);
            }

            foreach($options as $key => $text_){
                $attr_ = [];
                $value_ = $text_[$keys["value"]] ?? $key;
                if(is_array($text_)){
                    $attr_ = $text_["attr"] ?? [];
                    $text_ = $text_[$keys["text"]];
                }

                $options_[] = create_select_option($text_, $value_, $value_ == $value ? array_merge($attr_, attribute("selected")) : $attr_);

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
                ".($error ? error_span($error) : '')."
              </label>
        ";
    }

    /**
     * Creates a button element
     * @param string $type Button type
     * @param string $text Button text
     * @param string $name Button name
     * @param string $value Button value
     * @param string $color Button color theme
     * @param array $attributes Additional HTML attributes
     * @return string HTML button element
     */
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

    /**
     * Creates a textarea input with label
     * @param string $name Textarea name
     * @param string $text Label text
     * @param string $value Textarea content
     * @param bool $required Whether field is required
     * @param array $attributes Additional HTML attributes
     * @return string HTML textarea element
     */
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

    /**
     * Creates a placeholder attribute array
     * @param string $text Placeholder text
     * @return array Attribute array
     */
    function placeholder($text = ""){
        return attribute("placeholder", $text);
    }

    /**
     * Creates an attribute array
     * @param string $attribute Attribute name
     * @param string $value Attribute value
     * @return array Attribute array
     */
    function attribute($attribute, $value = ""){
        return [$attribute => $value];
    }

    /**
     * Creates a data attribute array
     * @param string $attr Data attribute name
     * @param string $value Attribute value
     * @return array Data attribute array
     */
    function data_attr($attr = "", $value = ""){
        return attribute("data-$attr", $value);
    }

    /**
     * Creates a header text (empty function)
     * @param string $text Header text
     */
    function header_text($text = ""){

    }

    /**
     * Creates a page header
     * @param string $title Header title
     * @return string HTML header element
     */
    function page_header($title){
        return "
            <h1 class=\"text-2xl font-semibold text-gray-800 dark:text-gray-200 mb-6\">$title</h1>
        ";
    }

    /**
     * Creates a form message element
     * @param string $message Message text
     * @param string $color Message color theme
     * @return string HTML paragraph element with message
     */
    function form_message($message = "", $color = ""){
        $color = empty($color) ? "neutral" : "color";
        return "
            <p class=\"text-xs text-$color-600 dark:text-$color-400\">
                $message
            </p>
        ";
    }

    /**
     * Creates an authentication navigation item
     * @param string $text Link text
     * @param string $link URL
     * @param string $icon Icon class
     * @param bool $active Whether item is active
     * @param array $attributes Additional HTML attributes
     * @return string HTML list item element
     */
    function auth_nav($text = "", $link = "", $icon = "", $active = false, $attributes = []){
        $link = $active ? "javascript:void()" : url($link);
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
                href=\"".$link."\"
                >
                <i class=\"w-5 $icon\"></i>
                
                <span class=\"ml-4\">$text</span>
                </a>
            </li>
        ";
    }

    /**
     * Creates a navigation group with dropdown
     * @param string $text Group title
     * @param string $menu_name Menu identifier
     * @param string $icon Icon class
     * @param array $items Submenu items
     * @return string HTML navigation group element
     */
    function auth_nav_group_link($text = "", $menu_name = "1", $icon = "", $items = []){
        $nav = "
            <li class=\"relative px-6 py-3\">
                <div
                    class=\"inline-flex cursor-pointer items-center justify-between w-full text-sm font-semibold transition-colors duration-150 hover:text-gray-800 dark:hover:text-gray-200\"
                    @click=\"current_menu == '$menu_name' ? current_menu = '':current_menu='$menu_name'\"
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
                </div>

                <template x-if=\"current_menu == '$menu_name'\">
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
                $nav .= nav_submenu_item($item["text"], $item["url"], is_current($item["url"]));
            }
            
            $nav .= "
                    </ul>
                </template>
            </li>
        ";

        return $nav;
    }

    /**
     * Creates a submenu navigation item
     * @param string $text Item text
     * @param string $url Item URL
     * @param bool $active Whether item is active
     * @return string HTML list item element
     */
    function nav_submenu_item($text = "", $url = "", $active = false){
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
            <li
                class=\"px-2 py-1 transition-colors duration-150 hover:text-gray-800 dark:hover:text-gray-200\"
                $active
            >
                <a class=\"w-full $class\" href=\"".url($url)."\">$text</a>
            </li>
        ";
    }

    /**
     * Starts a table element
     * @param array $attributes Additional HTML attributes
     * @return string Opening HTML table tags
     */
    function table_start($attributes = []){
        $attr = convert_attributes($attributes);
        $class_ = merge_class($attributes);
        return "
            <div class=\"w-full overflow-hidden rounded-lg shadow-xs\">
              <div class=\"w-full overflow-x-auto\">
                <table class=\"w-full $class_\" $attr>
        ";
    }
    
    /**
     * Starts a table row
     * @return string Opening HTML tr tag with styling
     */
    function tr_start(){
        return "<tr class=\"text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b dark:border-gray-700 bg-gray-50 dark:text-gray-400 dark:bg-gray-800\">";
    }

    /**
     * Ends a table row
     * @return string Closing HTML tr tag
     */
    function tr_end(){
        return close_tag("tr");
    }

    /**
     * Creates an empty table cell spanning multiple columns
     * @param string $text Cell text
     * @param int $col_count Number of columns to span
     * @return string HTML td element
     */
    function td_empty($text, $col_count){
        return td($text, attributes: 
            array_merge(attribute("class", "text-center"),
            attribute("colspan", $col_count))
        );
    }

    /**
     * Creates a table header cell
     * @param string $text Header text
     * @param array $attributes Additional HTML attributes
     * @return string HTML th element
     */
    function th($text = "", $attributes = []){
        $attr = convert_attributes($attributes);
        $class_ = merge_class($attributes);

        return "<th class=\"px-4 py-3 $class_\" $attr>$text</th>";
    }

    /**
     * Creates a table data cell
     * @param string $text Cell text
     * @param string $icon Icon URL
     * @param string $sub_text Secondary text
     * @param array $attributes Additional HTML attributes
     * @return string HTML td element
     */
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
                <div class=\"dark:text-white text-neutral-700\">
                    <p class=\"font-semibold\">$text</p>
                    $sub_text
                </div>
            </div>
        ";

        return "<td class=\"px-4 py-3 $class_\" $attr>
                    $final_text
                </td>";
    }

    /**
     * Ends a table element
     * 
     * @return string Closing HTML table tags
     */
    function table_end(){
        return close_tag("table, div, div");
    }

    /**
     * Creates an alert message
     * @param string $text Alert text
     * @param string $type Alert type (success/error/warning)
     * @param string $icon Custom icon class
     * @return string HTML alert element
     */
    function alert($text = "", $type = "", $icon = ""){
        $icons = [
            "success" => ["icon" => "fas fa-check", "color" => "green"],
            "error" => ["icon" => "fas fa-times-circle", "color" => "red"],
            "warning" => ["icon" => "fas fa-exclamation", "color" => "yellow"]
        ];

        if(empty($icon)){
            $icon = $icons[$type]["icon"] ?? "fas fa-bell";
            $color = $icons[$type]["color"] ?? "neutral";
        }

        return "
            <div x-data=\"{ show: true }\" x-show=\"show\" @click=\"show = false\" class=\"flex justify-between p-4 cursor-pointer rounded-md bg-$color-50 border border-$color-300\">
                <div class=\"flex gap-3 sm:items-center\">
                    <div>
                        <i class=\"w-6 h-6 $icon text-$color-500\"></i>
                    </div>
                    <p class=\"text-$color-600 sm:text-sm\">
                        $text
                    </p>
                </div>
            </div>
        ";
    }

    /**
     * Creates an information bar
     * @param string $text Bar text
     * @param string $type Bar type
     * @param bool $can_hide Whether bar can be hidden
     * @param array $attributes Additional HTML attributes
     * @return string HTML information bar element
     */
    function information_bar($text = "", $type = "", $can_hide = false, $attributes = []){
        $colors = [
            "success" => "green",
            "error" => " red",
            "warning" => "yellow"
        ];

        $color = $colors[$type] ?? "neutral";
        $attr = convert_attributes($attributes);
        $class_ = merge_class($attributes);
        $alpine = $can_hide ? "x-data=\"{ show: true }\" x-show=\"show\" @click=\"show = false\"" : "";

        return "
            <div $alpine 
                class=\"border-b border-$color-300 bg-$color-200 px-4 py-2 text-$color-900 $class_\" 
                $attr
            >
                <p class=\"text-center font-medium\">
                    $text
                </p>
            </div>
        ";
    }

    /**
     * Starts a table header section
     * @return string Opening HTML thead tags
     */
    function thead_start(){
       return "<thead>\n\t<tr class=\"text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b dark:border-gray-700 bg-gray-50 dark:text-gray-400 dark:bg-gray-800\">";
    }

    /**
     * Ends a table header section
     * @return string Closing HTML thead tags
     */
    function thead_end(){
        return close_tag("tr, thead");
    }

    /**
     * Starts a table body section
     * @param array $attributes Additional HTML attributes
     * @return string Opening HTML tbody tag
     */
    function tbody_start($attributes = []){
        $attr = convert_attributes($attributes);
        $class_ = merge_class($attributes);

        return "<tbody class=\"bg-white divide-y dark:divide-gray-700 dark:bg-gray-800 $class_\" $attr>";
    }

    /**
     * Ends a table body section
     * @return string Closing HTML tbody tag
     */
    function tbody_end(){
        return close_tag("tbody");
    }

    /**
     * Starts a form body section
     * @param array $attributes Additional HTML attributes
     * @return string Opening HTML div tag
     */
    function form_body_start($attributes = []){
        $attr = convert_attributes($attributes);
        $class = merge_class($attributes);
        return "<div $attr class=\"grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 $class\">";
    }

    /**
     * Ends a form body section
     * @return string Closing HTML div tag
     */
    function form_body_end(){
        return close_tag("div");
    }

    /**
     * Closes HTML tags
     * @param string|array $tag Tag(s) to close
     * @return string Closing HTML tag(s)
     */
    function close_tag($tag){
        if(is_array($tag) || str_contains($tag, ",")){
            if(str_contains($tag, ",")){
                $tag = str_replace(" ", "", $tag);
                $tag = explode(",", $tag);
            }

            $tags = [];

            foreach($tag as $tag_){
                $tags[] = close_tag($tag_);
            }

            return implode("\n", $tags);
        }
        return "</$tag>";
    }

    /**
     * Displays system messages
     * @return string HTML alert element for system message
     */
    function system_message(){
        if(isset($_SESSION["errors"]["system_message"])){
            return alert($_SESSION["errors"]["system_message"], "error");
        }elseif(isset($_SESSION["system_message"])){
            return alert($_SESSION["system_message"], "success");
        }elseif(isset($_SESSION["system_warning"])){
            return alert($_SESSION["system_warning"], "warning");
        }
    }

    /**
     * Starts a fieldset element
     * @param array $attributes Additional HTML attributes
     * @return string Opening HTML fieldset tag
     */
    function fieldset_start($attributes = []){
        $attr = convert_attributes($attributes);
        $class = merge_class($attributes);
        return "<fieldset class=\"border border-gray-300 p-4 rounded-lg $class\" $attr>";
    }

    /**
     * Creates a fieldset legend
     * @param string $text Legend text
     * @param array $attributes Additional HTML attributes
     * @return string HTML legend element
     */
    function fieldset_legend($text = "", $attributes = []){
        $attr = convert_attributes($attributes);
        $class = merge_class($attributes);
        return "<legend class=\"px-2 font-semibold text-gray-700 dark:text-white $class\" $attr>$text</legend>";
    }

    /**
     * Ends a fieldset element
     * @return string Closing HTML fieldset tag
     */
    function fieldset_end(){
        return close_tag("fieldset");
    }

    /**
     * Starts a modal dialog
     * @param array $attr Additional HTML attributes
     * @return string Opening HTML modal structure
     */
    function modal_start($attr = []){
        $class = merge_class($attr);
        $attr = convert_attributes($attr);

        return "
            <div
                x-show=\"isModalOpen\"
                x-transition:enter=\"transition ease-out duration-150\"
                x-transition:enter-start=\"opacity-0\"
                x-transition:enter-end=\"opacity-100\"
                x-transition:leave=\"transition ease-in duration-150\"
                x-transition:leave-start=\"opacity-100\"
                x-transition:leave-end=\"opacity-0\"
                class=\"fixed inset-0 z-30 flex items-end bg-black bg-opacity-50 sm:items-center sm:justify-center\"
                >
                <div
                    x-show=\"isModalOpen\"
                    x-transition:enter=\"transition ease-out duration-150\"
                    x-transition:enter-start=\"opacity-0 transform translate-y-1/2\"
                    x-transition:enter-end=\"opacity-100\"
                    x-transition:leave=\"transition ease-in duration-150\"
                    x-transition:leave-start=\"opacity-100\"
                    x-transition:leave-end=\"opacity-0  transform translate-y-1/2\"
                    @click.away=\"closeModal\"
                    @keydown.escape=\"closeModal\"
                    class=\"w-full px-6 py-4 overflow-hidden bg-white rounded-t-lg dark:bg-gray-800 sm:rounded-lg sm:m-4 sm:max-w-xl scrollbar-hidden $class\"
                    role=\"dialog\"
                    $attr
                >
        ";
    }

     /**
     * Creates a modal header with close button
     * @param array $attributes Additional HTML attributes
     * @return string HTML modal header element
     */
    function modal_header($attributes = []){
        $class = merge_class($attributes);
        $attributes = convert_attributes($attributes);
        return "
            <header class=\"flex justify-end $class\" $attributes>
                <button
                    class=\"inline-flex items-center justify-center w-6 h-6 text-gray-400 transition-colors duration-150 rounded dark:hover:text-gray-200 hover: hover:text-gray-700\"
                    aria-label=\"close\"
                    @click=\"closeModal\"
                >
                    <i class=\"fas fa-close w-4 h-4\"></i>
                </button>
            </header>
        ";
    }

    /**
     * Creates a modal title
     * @param string $title Title text
     * @param array $attributes Additional HTML attributes  
     * @return string HTML title element
     */
    function modal_title($title = "", $attributes = []){
        $class = merge_class($attributes);
        $attributes = convert_attributes($attributes);

        return "
            <p
                $attributes
                class=\"mb-2 text-lg font-semibold text-gray-700 dark:text-gray-300 $class\"
            >
                $title
            </p>
        ";
    }

    /**
     * Starts a modal body section
     * @param array $attributes Additional HTML attributes
     * @return string Opening HTML modal body tag
     */
    function modal_body_start($attributes = []){
        $class = merge_class($attributes);
        $attributes = convert_attributes($attributes);
        return "<div class=\"mt-4 mb-6 $class\" $attributes>";
    }

    /**
     * Ends a modal body section
     * @return string Closing HTML div tag
     */
    function modal_body_end(){
        return close_tag("div");
    }

    /**
     * Starts a modal footer section
     * @param array $attributes Additional HTML attributes
     * @return string Opening HTML modal footer tag
     */
    function modal_footer_start($attributes = []){
        $class = merge_class($attributes);
        $attributes = convert_attributes($attributes);

        return "
            <footer
                class=\"flex flex-col items-center justify-end px-6 py-3 -mx-6 -mb-4 space-y-4 sm:space-y-0 sm:space-x-6 sm:flex-row bg-gray-50 dark:bg-gray-800 $class\"
                $attributes
            >
        ";
    }

    /**
     * Creates a modal reset/cancel button
     * @param string $text Button text
     * @param string $color Button color theme
     * @param string $text_color Text color
     * @param array $attributes Additional HTML attributes
     * @return string HTML button element
     */
    function modal_reset_btn($text = "Cancel", $color = "gray", $text_color = "white", $attributes = []){
        $color = strtolower($color);
        $text_color = strtolower($text_color);
        $class = merge_class($attributes);
        $attributes = convert_attributes($attributes);
        return "
            <button
                type=\"button\"
                @click=\"closeModal\"
                $attributes
                class=\"w-full px-5 py-3 text-sm font-medium leading-5 text-$text_color text-$text_color-700 transition-colors duration-150 border border-$color-300 rounded-lg dark:text-$text_color-400 sm:px-4 sm:py-2 sm:w-auto active:bg-transparent hover:border-$color-500 focus:border-$color-500 active:text-$text_color-500 focus:outline-none focus:shadow-outline-$color $class\"
            >
                $text
            </button>
        ";
    }

    /**
     * Creates a modal footer button
     * @param string $text Button text
     * @param string $color Button color theme
     * @param string $text_color Text color
     * @param array $attributes Additional HTML attributes
     * @return string HTML button element
     */
    function modal_footer_btn($text = "", $color = "blue", $text_color = "auto", $attributes = []){
        $color = strtolower($color);
        $text_color = strtolower($text_color);
        $class = merge_class($attributes);
        $attributes = convert_attributes($attributes);

        return "
            <button
                $attributes
                class=\"w-full px-5 py-3 text-sm font-medium leading-5 text-$text_color transition-colors duration-150 bg-$color-600 border border-transparent rounded-lg sm:w-auto sm:px-4 sm:py-2 active:bg-$color-600 hover:bg-$color-700 focus:outline-none focus:shadow-outline-$color $class\"
            >
                $text
            </button>
        ";
    }

    /**
     * Ends a modal footer section
     * @return string Closing HTML footer tag
     */
    function modal_footer_end(){
        return close_tag("footer");
    }

    /**
     * Ends a modal element
     * @return string Closing HTML div tags
     */
    function modal_end(){
        return close_tag("div, div");
    }

    /**
     * Creates a delete item modal component
     * @param string $table Database table name
     * @param string $column Column identifier
     * @param string $form_action Form submission URL
     * @param string $delete_text Confirmation text
     * @param string $modal_title Modal title
     * @return string HTML delete modal component
     */
    function delete_item_component($table, $column = "id", $form_action = "", $delete_text = "", $modal_title = "" ){
        $component = "<form action=\"$form_action\" method=\"POST\" id=\"delete-item-component-form\">\n";
        $component .= modal_body_start();
        
        if($modal_title){
            $component .= modal_title($modal_title);
        }

        $component .= "
            <p class=\"text-sm text-gray-700 dark:text-gray-400\">
                $delete_text
            </p>
        ";

        $component .= modal_body_end();
        $component .= modal_footer_start();
        $component .= modal_reset_btn("No", attributes: array_merge(
            attribute("class", "min-w-24"),
            attribute("type", "button")
        ));
        $component .= modal_footer_btn("Yes", attributes: array_merge(
            attribute("class", "min-w-24"),
            attribute("type", "button"),
            attribute("name", "submit"),
            attribute("value", "delete-item")
        ));
        $component .= modal_footer_end();
        $component .= input("hidden", value: $table, name: "delete-table");
        $component .= input("hidden", value: $column, name: "delete-column");
        $component .= input("hidden", name: "delete-id");
        $component .= input("hidden", name: "submit", value: "delete-item");

        $component .= "</form>";

        return $component;
    }

    /**
     * Provides JavaScript for delete item functionality
     * @param string $element CSS selector for delete trigger
     * @return string JavaScript code
     */
    function delete_item_component_script($element = ".action-delete") {
        $script = <<<JAVASCRIPT
            const main = \$('$element');
            main.click(function() {
                const id = $(this).data("id");
                $("#delete-item-component-form input[name='delete-id']").val(id);
            });

            /*$("#delete-item-component-form").submit(function(e){
                e.preventDefault();
                const id = $(this).find("input[name='delete-id']").val();
                const table = $(this).find("input[name='delete-table']").val();
                const column = $(this).find("input[name='delete-column']").val();
                const item_value = $(this).find("input[name='item-value']").val();

                $.ajaxCall({
                    url: $(this).attr("action"),
                    type: "POST",
                    data: {
                        id: id,
                        table: table,
                        column: column,
                        item_value: item_value,
                        submit: "delete-item"
                    }
                }).then((response) => {

                });
            });*/
        JAVASCRIPT;
    
        return $script;
    }

    /**
     * Starts a card container grid
     * @param int $cols_med Number of columns for medium screens
     * @param int $cols_xl Number of columns for large screens
     * @param array $attributes Additional HTML attributes
     * @return string Opening HTML grid container
     */
    function card_container_start($cols_med = 2, $cols_xl = 4, $attributes = []){
        $class = merge_class($attributes);
        $attributes = convert_attributes($attributes);

        return "
            <div $attributes class=\"grid gap-6 mb-8 grid-cols-1 md:grid-cols-$cols_med xl:grid-cols-$cols_xl $class\">
        ";
    }

    /**
     * Creates a dashboard card button
     * @param string $text Card text
     * @param int $count Numeric value to display
     * @param string $icon Icon class
     * @param string $icon_color Icon color theme
     * @param array $attributes Additional HTML attributes
     * @return string HTML card button element
     */
    function dashboard_card_btn($text = "", $count = 0, $icon = "", $icon_color="orange", $attributes = []){
        $class = merge_class($attributes);
        $attributes = convert_attributes($attributes);
        return "
            <div $attributes class=\"flex items-center p-4 bg-white rounded-lg shadow-xs dark:bg-gray-800 $class\">
                <div class=\"p-3 text-center mr-4 text-$icon_color-500 bg-$icon_color-100 rounded-full dark:text-$icon_color-100 dark:bg-$icon_color-500\">
                  <i class=\"$icon w-5 h-5\"></i>
                </div>
                <div>
                  <p class=\"mb-2 text-sm font-medium text-gray-600 dark:text-gray-400\">
                    $text
                  </p>
                  <p class=\"text-lg font-semibold text-gray-700 dark:text-gray-200\">
                    $count
                  </p>
                </div>
              </div>
        ";
    }

    /**
     * Ends a card container
     * @return string Closing HTML div tag
     */
    function card_container_end(){
        return close_tag("div");
    }