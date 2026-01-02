<?php

    /**
     * Main Dispatcher for rendering evaluation questions.
     * @param array $question The question record from the database (including 'type', 'question_text', 'options', etc.).
     * @param array $answers Current saved answers (for pre-filling drafts).
     * @param int $index The display number for the question.
     * @return string The generated HTML.
     */
    function render_evaluation_question(array $question, mixed $answer = null, int $index = 1): string {
        $type = $question['rating_type'] ?? 'text_short';
        $current_answer = $answer;

        // Dispatch to specific rendering functions based on database IDs
        switch ($type) {
            case 'scale_5':
                return render_scale_component($question, $current_answer, $index, 5);
            case 'scale_10':
                return render_scale_component($question, $current_answer, $index, 10);
            case 'boolean':
                return render_boolean_component($question, $current_answer, $index);
            case 'select_single':
                return render_choice_component($question, $current_answer, $index, false);
            case 'select_multiple':
                return render_choice_component($question, $current_answer, $index, true);
            case 'text_long':
                return render_text_input_component($question, $current_answer, $index, true);
            case 'text_short':
            default:
                return render_text_input_component($question, $current_answer, $index, false);
        }
    }

    /**
     * Renders Numeric/Likert Scales (1-5 or 1-10)
     */
    function render_scale_component(array $question, $answer, int $index, int $max): string {
        $q_id = $question['id'];
        $text = htmlspecialchars($question['question_text']);
        $required = ($question['is_required'] ?? false) ? '<span class="text-red-500 ml-1">*</span>' : '';
        
        // Labels for 5-point Likert
        $likert_labels = [
            1 => 'Strongly Disagree',
            2 => 'Disagree',
            3 => 'Neutral',
            4 => 'Agree',
            5 => 'Strongly Agree'
        ];

        $options_html = "";
        for ($i = 1; $i <= $max; $i++) {
            $name = "q_{$q_id}";
            $html_id = "{$name}_{$i}";
            $checked = ($answer == $i) ? 'checked' : '';
            $label = ($max === 5) ? ($likert_labels[$i] ?? $i) : $i;
            
            $options_html .= <<<HTML
                <label class="flex flex-col items-center space-y-2 cursor-pointer group flex-1">
                    <input type="radio" id="{$html_id}" name="{$name}" value="{$i}" {$checked}
                        class="w-5 h-5 text-indigo-600 transition duration-150 ease-in-out form-radio border-gray-300 dark:bg-gray-700 dark:border-gray-600 focus:ring-indigo-500">
                    <span class="text-[10px] font-bold text-gray-500 uppercase transition-colors group-hover:text-indigo-600 dark:text-gray-400">{$label}</span>
                </label>
    HTML;
        }

        return <<<HTML
            <div class="p-6 mb-6 bg-white border border-gray-100 rounded-2xl shadow-sm dark:bg-gray-800 dark:border-gray-700 transition-all hover:shadow-md">
                <h4 class="mb-6 text-lg font-bold text-gray-800 dark:text-gray-200">
                    <span class="inline-flex items-center justify-center w-7 h-7 mr-2 text-xs text-white bg-indigo-500 rounded-full">{$index}</span>
                    {$text} {$required}
                </h4>
                <div class="flex flex-wrap items-center justify-between bg-gray-50 dark:bg-gray-900/30 p-5 rounded-xl border border-gray-200 dark:border-gray-600">
                    {$options_html}
                </div>
            </div>
    HTML;
    }

    /**
     * Renders Boolean (Yes/No)
     */
    function render_boolean_component(array $question, $answer, int $index): string {
        $q_id = $question['id'];
        $text = htmlspecialchars($question['question_text']);
        $name = "q_{$q_id}";
        $required = ($question['is_required'] ?? false) ? '<span class="text-red-500 ml-1">*</span>' : '';

        $options = [
            ['val' => '1', 'label' => 'Yes'],
            ['val' => '0', 'label' => 'No']
        ];

        $options_html = "";
        foreach ($options as $opt) {
            $html_id = "{$name}_{$opt['val']}";
            $checked = ($answer !== null && $answer == $opt['val']) ? 'checked' : '';
            $options_html .= <<<HTML
                <label class="flex items-center p-4 border border-gray-200 rounded-xl cursor-pointer hover:bg-indigo-50 dark:hover:bg-gray-700 dark:border-gray-600 transition-colors flex-1">
                    <input type="radio" id="{$html_id}" name="{$name}" value="{$opt['val']}" {$checked}
                        class="w-5 h-5 text-indigo-600 border-gray-300 focus:ring-indigo-500">
                    <span class="ml-3 font-semibold text-gray-700 dark:text-gray-300">{$opt['label']}</span>
                </label>
    HTML;
        }

        return <<<HTML
            <div class="p-6 mb-6 bg-white border border-gray-100 rounded-2xl shadow-sm dark:bg-gray-800 dark:border-gray-700 transition-all hover:shadow-md">
                <h4 class="mb-4 text-lg font-bold text-gray-800 dark:text-gray-200">
                    <span class="inline-flex items-center justify-center w-7 h-7 mr-2 text-xs text-white bg-indigo-500 rounded-full">{$index}</span>
                    {$text} {$required}
                </h4>
                <div class="flex space-x-4">
                    {$options_html}
                </div>
            </div>
    HTML;
    }

    /**
     * Renders Single or Multiple Choice (Radio or Checkbox)
     */
    function render_choice_component(array $question, $answer, int $index, bool $is_multiple): string {
        $q_id = $question['id'];
        $text = htmlspecialchars($question['question_text']);
        $name = $is_multiple ? "q_{$q_id}[]" : "q_{$q_id}";
        $required = ($question['is_required'] ?? false) ? '<span class="text-red-500 ml-1">*</span>' : '';
        
        // Assume options is a JSON string or array in the database
        $options = is_array($question['options']) ? $question['options'] : json_decode($question['options'] ?? '[]', true);
        $input_type = $is_multiple ? 'checkbox' : 'radio';

        $options_html = "";
        foreach ($options as $idx => $opt) {
            $opt_val = htmlspecialchars($opt);
            $html_id = "q_{$q_id}_{$idx}";
            
            $is_checked = false;
            if ($is_multiple && is_array($answer)) {
                $is_checked = in_array($opt_val, $answer);
            } else {
                $is_checked = ($answer == $opt_val);
            }
            $checked_attr = $is_checked ? 'checked' : '';

            $options_html .= <<<HTML
                <label class="flex items-center p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors cursor-pointer">
                    <input type="{$input_type}" id="{$html_id}" name="{$name}" value="{$opt_val}" {$checked_attr}
                        class="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600">
                    <span class="ml-3 text-gray-700 dark:text-gray-300">{$opt_val}</span>
                </label>
    HTML;
        }

        return <<<HTML
            <div class="p-6 mb-6 bg-white border border-gray-100 rounded-2xl shadow-sm dark:bg-gray-800 dark:border-gray-700 transition-all hover:shadow-md">
                <h4 class="mb-4 text-lg font-bold text-gray-800 dark:text-gray-200">
                    <span class="inline-flex items-center justify-center w-7 h-7 mr-2 text-xs text-white bg-indigo-500 rounded-full">{$index}</span>
                    {$text} {$required}
                </h4>
                <div class="space-y-1">
                    {$options_html}
                </div>
            </div>
    HTML;
    }

    /**
     * Renders Text Input (Short or Long)
     */
    function render_text_input_component(array $question, $answer, int $index, bool $is_long): string {
        $q_id = $question['id'];
        $text = htmlspecialchars($question['question_text']);
        $name = "q_{$q_id}";
        $required = ($question['is_required'] ?? false) ? '<span class="text-red-500 ml-1">*</span>' : '';
        $val = htmlspecialchars($answer ?? '');

        $input_html = $is_long 
            ? <<<HTML
                <textarea id="{$name}" name="{$name}" rows="4" 
                    class="w-full p-4 mt-1 border border-gray-200 rounded-xl shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent" 
                    placeholder="Type your detailed feedback here...">{$val}</textarea>
        HTML
            : <<<HTML
                <input type="text" id="{$name}" name="{$name}" value="{$val}"
                    class="w-full p-4 mt-1 border border-gray-200 rounded-xl shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent" 
                    placeholder="Type your response here...">
        HTML;

        return <<<HTML
            <div class="p-6 mb-6 bg-white border border-gray-100 rounded-2xl shadow-sm dark:bg-gray-800 dark:border-gray-700 transition-all hover:shadow-md">
                <h4 class="mb-4 text-lg font-bold text-gray-800 dark:text-gray-200">
                    <span class="inline-flex items-center justify-center w-7 h-7 mr-2 text-xs text-white bg-indigo-500 rounded-full">{$index}</span>
                    {$text} {$required}
                </h4>
                {$input_html}
            </div>
    HTML;
    }