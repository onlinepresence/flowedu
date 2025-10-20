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
     * @param ?array $data     Form data (e.g. $_POST)
     * @param ?array $alias    Key names and their alias names to use
     * @return array $errors   Validation errors
     */
    function validate_form($rules, $messages = [], $data = null, $alias = null) {
        $errors = [];

        if (!$data) {
            $data = $_REQUEST;
            unset($data["submit"]);
        }

        foreach ($rules as $field => $rule_string) {
            $value = isset($data[$field]) ? trim($data[$field]) : '';
            $field_rules = explode('|', $rule_string);

            $is_nullable = in_array('nullable', $field_rules);
            $is_file_field = isset($_FILES[$field]) && is_array($_FILES[$field]);

            // Determine display name (use alias or fallback)
            $display_name = isset($alias[$field]) 
                ? $alias[$field] 
                : ucfirst(str_replace('_', ' ', $field));

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
                            $errors[$field] = $messages[$field]['required'] ?? "$display_name is required";
                            break;
                        }
                    } elseif ($value === '') {
                        $errors[$field] = $messages[$field]['required'] ?? "$display_name is required";
                        break;
                    }
                }

                // 🧩 Required If
                if ($rule === 'required_if' && $param !== null) {
                    [$otherField, $otherValue] = explode(',', $param, 2);
                    $otherField = trim($otherField);
                    $otherValue = trim($otherValue);

                    $otherFieldValue = isset($data[$otherField]) ? trim($data[$otherField]) : '';
                    $otherDisplay = isset($alias[$otherField])
                        ? $alias[$otherField]
                        : str_replace('_', ' ', $otherField);

                    if ($otherFieldValue === $otherValue && $value === '') {
                        $errors[$field] = $messages[$field]['required_if'] ?? "$display_name is required when $otherDisplay is $otherValue";
                        break;
                    }
                }

                // Skip nullable or empty
                if ($value === '' && !$is_file_field && $is_nullable) break;
                if ($value === '' && !$is_file_field) continue;

                // ================================
                // 🔹 FILE VALIDATION SECTION
                // ================================
                if ($rule === 'file') {
                    if (!$is_file_field || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
                        if (!$is_nullable) {
                            $errors[$field] = $messages[$field]['file'] ?? "$display_name must be uploaded";
                            break;
                        }
                    } elseif ($_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
                        $errors[$field] = $messages[$field]['file'] ?? "Error uploading $display_name";
                        break;
                    }
                }

                // 🧩 Mimes
                if ($rule === 'mimes' && $is_file_field && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
                    $allowed = array_map('strtolower', array_map('trim', explode(',', $param)));
                    $extension = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
                    if (!in_array($extension, $allowed)) {
                        $errors[$field] = $messages[$field]['mimes'] ?? "$display_name must be a file of type: " . implode(', ', $allowed);
                        break;
                    }
                }

                // 🧩 Max
                if ($rule === 'max' && $param !== null) {
                    if ($is_file_field && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
                        $sizeKB = $_FILES[$field]['size'] / 1024;
                        if ($sizeKB > (float)$param) {
                            $errors[$field] = $messages[$field]['max'] ?? "$display_name must not be larger than $param KB";
                            break;
                        }
                    } elseif (is_numeric($value)) {
                        if ($value > $param) {
                            $errors[$field] = $messages[$field]['max'] ?? "$display_name may not be greater than $param";
                            break;
                        }
                    } elseif (strlen($value) > $param) {
                        $errors[$field] = $messages[$field]['max'] ?? "$display_name may not be longer than $param characters";
                        break;
                    }
                }

                // 🧩 Min
                if ($rule === 'min' && $param !== null) {
                    if (is_numeric($value)) {
                        if ($value < $param) {
                            $errors[$field] = $messages[$field]['min'] ?? "$display_name must be at least $param";
                            break;
                        }
                    } elseif (strlen($value) < $param) {
                        $errors[$field] = $messages[$field]['min'] ?? "$display_name must be at least $param characters";
                        break;
                    }
                }

                // 🧩 Numeric
                if ($rule === 'numeric' && !is_numeric($value)) {
                    $errors[$field] = $messages[$field]['numeric'] ?? "$display_name must be numeric";
                    break;
                }

                // 🧩 Integer
                if ($rule === 'integer' && filter_var($value, FILTER_VALIDATE_INT) === false) {
                    $errors[$field] = $messages[$field]['integer'] ?? "$display_name must be an integer";
                    break;
                }

                // 🧩 Email
                if ($rule === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[$field] = $messages[$field]['email'] ?? "$display_name must be a valid email address";
                    break;
                }

                // 🧩 Date
                if ($rule === 'date' && !preg_match("/^\d{4}-\d{2}-\d{2}$/", $value)) {
                    $errors[$field] = $messages[$field]['date'] ?? "$display_name must be a valid date (YYYY-MM-DD)";
                    break;
                }

                // 🧩 Confirmed
                if ($rule === 'confirmed') {
                    $confirmation_field = $param ?? $field . '_confirmation';
                    if (!isset($data[$confirmation_field]) || $data[$confirmation_field] !== $value) {
                        $errors[$field] = $messages[$field]['confirmed'] ?? "$display_name confirmation does not match";
                        break;
                    }
                }

                // 🧩 Regex
                if ($rule === 'regex' && $param !== null && !preg_match($param, $value)) {
                    $errors[$field] = $messages[$field]['regex'] ?? "$display_name format is invalid";
                    break;
                }

                // 🧩 Positive
                if ($rule === 'positive' && (!is_numeric($value) || $value <= 0)) {
                    $errors[$field] = $messages[$field]['positive'] ?? "$display_name must be positive";
                    break;
                }

                // 🧩 Phone
                if ($rule === 'phone') {
                    $validate_phone = is_valid_phone_number($value);
                    if (!preg_match("/^[0-9]{10}$/", $value)) {
                        $errors[$field] = $messages[$field]['phone'] ?? "$display_name must be a valid 10-digit number";
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

                // 🧩 Unique
                if ($rule === 'unique' && $param !== null) {
                    list($column, $table, $where, $where_bind) = split_query_information($param, $value);
                    $exists = fetchData($column, $table, $where, where_binds: $where_bind);

                    if (!empty($exists)) {
                        $errors[$field] = $messages[$field]['unique'] ?? "$display_name has already been taken";
                        break;
                    }
                }

                // 🧩 Exists
                if ($rule === 'exists' && $param !== null) {
                    list($column, $table, $where, $where_bind) = split_query_information($param, $value);
                    $exists = fetchData($column, $table, $where, where_binds: $where_bind);
                    if (empty($exists)) {
                        $errors[$field] = $messages[$field]['exists'] ?? "$display_name does not exist in the database";
                        break;
                    }
                }

                // 🧩 In
                if ($rule === 'in' && $param !== null) {
                    $allowedValues = array_map('trim', explode(',', $param));
                    if (!in_array($value, $allowedValues)) {
                        $errors[$field] = $messages[$field]['in'] ?? "$display_name must be one of the following: " . implode(', ', $allowedValues);
                        break;
                    }
                }

                // 🧩 Not In
                if ($rule === 'not_in' && $param !== null) {
                    $disallowedValues = array_map('trim', explode(',', $param));
                    if (in_array($value, $disallowedValues)) {
                        $errors[$field] = $messages[$field]['not_in'] ?? "$display_name must not be one of the following: " . implode(', ', $disallowedValues);
                        break;
                    }
                }

                // 🧩 Starts With
                if ($rule === 'starts_with' && $param !== null) {
                    $prefixes = array_map('trim', explode(',', $param));
                    $startsWith = false;
                    foreach ($prefixes as $prefix) {
                        if (strpos($value, $prefix) === 0) {
                            $startsWith = true;
                            break;
                        }
                    }
                    if (!$startsWith) {
                        $errors[$field] = $messages[$field]['starts_with'] ?? "$display_name must start with one of the following: " . implode(', ', $prefixes);
                        break;
                    }
                }

                // 🧩 Ends With
                if ($rule === 'ends_with' && $param !== null) {
                    $suffixes = array_map('trim', explode(',', $param));
                    $endsWith = false;
                    foreach ($suffixes as $suffix) {
                        if (substr($value, -strlen($suffix)) === $suffix) {
                            $endsWith = true;
                            break;
                        }
                    }
                    if (!$endsWith) {
                        $errors[$field] = $messages[$field]['ends_with'] ?? "$display_name must end with one of the following: " . implode(', ', $suffixes);
                        break;
                    }
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

    /**
     * This function creates the table variables used to query form validation tables
     * @param ?string $param The parameters
     * @return array
     */
    function split_query_information(?string $param, $value){
        // Split parameters safely
        $parts = array_map('trim', explode(',', $param));
        $table = array_shift($parts);   // first part = table
        $column = array_shift($parts);  // second part = column
    
        // Default WHERE condition
        $where = [$column => $value];
        $where_bind = 'AND'; // default logical binder
    
        // Process additional parameters like deleted_at=null or where_bind=OR
        foreach ($parts as $condition) {
            $where[] = trim($condition);
        }

        return [$column, $table, $where, $where_bind];
    }