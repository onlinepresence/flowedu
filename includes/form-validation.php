<?php
    /**
     * Used to validate a strong password
     * @param string $password The password to check
     * @return true|string
     */
    function is_valid_password(string $password):true|string{
        $message = true;

        if(!preg_match('/[A-Z]/', $password)){
            $message = "Password must contain at least one uppercase letter";
        }elseif(!preg_match('/[a-z]/', $password)){
            $message = "Password must contain at least one lowercase letter";
        }elseif(!preg_match('/[0-9]/', $password)){
            $message = "Password must contain at least one number";
        }elseif(!preg_match('/[\W_]/', $password)){
            $message = "Password must contain at least one special character";
        }

        return $message;
    }

    /**
     * This is used to make sure the ghana card number provided is valid
     */
    function is_valid_ghana_card_number($number) {
        // Pattern: GHA- followed by 9 digits, then a dash, then 1 digit
        $pattern = '/^GHA-\d{9}-\d{1}$/';
    
        return preg_match($pattern, $number) === 1;
    }

    /**
     * Universal Laravel-style form validator
     *
     * Supports: required, numeric, integer, string, email, date,
     * min, max, confirmed, regex, phone, positive, nullable
     *
     * @param array $rules     Validation rules for each field
     * @param array $messages  Optional custom messages
     * @param ?array $data      Form data (e.g. $_POST)
     * @return array $errors   Validation errors
     */
    function validate_form($rules, $messages = [], $data = null) {
        $errors = [];
    
        if (!$data) {
            $data = $_POST;
            unset($data["submit"]);
        }
    
        foreach ($rules as $field => $rule_string) {
            $value = isset($data[$field]) ? trim($data[$field]) : '';
            $field_rules = explode('|', $rule_string);
    
            $is_nullable = in_array('nullable', $field_rules);
            $is_file_field = isset($_FILES[$field]) && is_array($_FILES[$field]);
    
            foreach ($field_rules as $rule) {
                $param = null;
                if (strpos($rule, ':') !== false) {
                    [$rule, $param] = explode(':', $rule, 2);
                }
    
                $rule = strtolower(trim($rule));
    
                // 🧩 Required
                if ($rule === 'required') {
                    if ($is_file_field) {
                        if ($_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
                            $errors[$field] = $messages[$field]['required'] ?? ucfirst(str_replace('_', ' ', $field)) . " is required";
                            break;
                        }
                    } elseif ($value === '') {
                        $errors[$field] = $messages[$field]['required'] ?? ucfirst(str_replace('_', ' ', $field)) . " is required";
                        break;
                    }
                }
    
                // Skip other checks if empty and nullable
                if ($value === '' && !$is_file_field && $is_nullable) break;
                if ($value === '' && !$is_file_field) continue;
    
                // ================================
                // 🔹 FILE VALIDATION SECTION
                // ================================
                if ($rule === 'file') {
                    if (!$is_file_field || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
                        if (!$is_nullable) {
                            $errors[$field] = $messages[$field]['file'] ?? ucfirst(str_replace('_', ' ', $field)) . " must be uploaded";
                            break;
                        }
                    } elseif ($_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
                        $errors[$field] = $messages[$field]['file'] ?? "Error uploading " . str_replace('_', ' ', $field);
                        break;
                    }
                }
    
                // 🧩 Mimes (e.g. mimes:jpg,png,pdf)
                if ($rule === 'mimes' && $is_file_field && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
                    $allowed = array_map('strtolower', array_map('trim', explode(',', $param)));
                    $extension = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
    
                    if (!in_array($extension, $allowed)) {
                        $errors[$field] = $messages[$field]['mimes'] ?? ucfirst(str_replace('_', ' ', $field)) . " must be a file of type: " . implode(', ', $allowed);
                        break;
                    }
                }
    
                // 🧩 Max (for file or numeric/string)
                if ($rule === 'max' && $param !== null) {
                    if ($is_file_field && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
                        $sizeKB = $_FILES[$field]['size'] / 1024;
                        if ($sizeKB > (float)$param) {
                            $errors[$field] = $messages[$field]['max'] ?? ucfirst(str_replace('_', ' ', $field)) . " must not be larger than $param KB";
                            break;
                        }
                    } elseif (is_numeric($value)) {
                        if ($value > $param) {
                            $errors[$field] = $messages[$field]['max'] ?? ucfirst(str_replace('_', ' ', $field)) . " may not be greater than $param";
                            break;
                        }
                    } elseif (strlen($value) > $param) {
                        $errors[$field] = $messages[$field]['max'] ?? ucfirst(str_replace('_', ' ', $field)) . " may not be longer than $param characters";
                        break;
                    }
                }
    
                // 🧩 Min (for numeric/string)
                if ($rule === 'min' && $param !== null) {
                    if (is_numeric($value)) {
                        if ($value < $param) {
                            $errors[$field] = $messages[$field]['min'] ?? ucfirst(str_replace('_', ' ', $field)) . " must be at least $param";
                            break;
                        }
                    } elseif (strlen($value) < $param) {
                        $errors[$field] = $messages[$field]['min'] ?? ucfirst(str_replace('_', ' ', $field)) . " must be at least $param characters";
                        break;
                    }
                }
    
                // 🧩 Numeric
                if ($rule === 'numeric' && !is_numeric($value)) {
                    $errors[$field] = $messages[$field]['numeric'] ?? ucfirst(str_replace('_', ' ', $field)) . " must be numeric";
                    break;
                }
    
                // 🧩 Integer
                if ($rule === 'integer' && filter_var($value, FILTER_VALIDATE_INT) === false) {
                    $errors[$field] = $messages[$field]['integer'] ?? ucfirst(str_replace('_', ' ', $field)) . " must be an integer";
                    break;
                }
    
                // 🧩 Email
                if ($rule === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[$field] = $messages[$field]['email'] ?? ucfirst(str_replace('_', ' ', $field)) . " must be a valid email address";
                    break;
                }
    
                // 🧩 Date (YYYY-MM-DD)
                if ($rule === 'date' && !preg_match("/^\d{4}-\d{2}-\d{2}$/", $value)) {
                    $errors[$field] = $messages[$field]['date'] ?? ucfirst(str_replace('_', ' ', $field)) . " must be a valid date (YYYY-MM-DD)";
                    break;
                }
    
                // 🧩 Confirmed
                if ($rule === 'confirmed') {
                    $confirmation_field = $field . '_confirmation';
                    if (!isset($data[$confirmation_field]) || $data[$confirmation_field] !== $value) {
                        $errors[$field] = $messages[$field]['confirmed'] ?? ucfirst(str_replace('_', ' ', $field)) . " confirmation does not match";
                        break;
                    }
                }
    
                // 🧩 Regex
                if ($rule === 'regex' && $param !== null && !preg_match($param, $value)) {
                    $errors[$field] = $messages[$field]['regex'] ?? ucfirst(str_replace('_', ' ', $field)) . " format is invalid";
                    break;
                }
    
                // 🧩 Positive
                if ($rule === 'positive' && (!is_numeric($value) || $value <= 0)) {
                    $errors[$field] = $messages[$field]['positive'] ?? ucfirst(str_replace('_', ' ', $field)) . " must be positive";
                    break;
                }
    
                // 🧩 Phone
                if ($rule === 'phone') {
                    $validate_phone = is_valid_phone_number($value);
                    if (!preg_match("/^[0-9]{10}$/", $value)) {
                        $errors[$field] = $messages[$field]['phone'] ?? ucfirst(str_replace('_', ' ', $field)) . " must be a valid 10-digit number";
                        break;
                    } elseif ($validate_phone == -1 || !$validate_phone) {
                        $errors[$field] = "Invalid phone number provided";
                        break;
                    }
                }
    
                // 🧩 Ghana Card
                if ($rule === 'ghana_card' && !is_valid_ghana_card_number($value)) {
                    $errors[$field] = $messages[$field]['ghana_card'] ?? "Invalid Ghana Card number provided";
                    break;
                }
            }
        }
    
        return $errors;
    }
    

    /**
     * Used to validate if a phone number is valid
     * @param string $phone The phone number to be checked
     * @param ?string $provider The service provider
     * @param int
     */
    function is_valid_phone_number(string $phone, ?string $provider = null) :int {
        // Extract the first 3 digits of the phone number
        $prefix = substr($phone, 0, 3);
        
        // If a provider is specified, validate it
        if ($provider) {
            global $provider_prefixes;

            if (!isset($provider[$provider])) {
                return -1;
            }
            return in_array($prefix, $provider_prefixes[$provider]);
        }

        global $phone_prefixes;

        // Check if the prefix exists in the array
        return in_array($prefix, $phone_prefixes);
    }