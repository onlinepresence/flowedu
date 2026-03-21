/**
 * This function converts an object into a formadata object
 * @param {any} object This is the object to be processed
 * @return {FormData} returns a FormData value
 */
function toFormData(object){
    if(object instanceof FormData){
            return object;
    }
    
    return JSONtoFormData(object);
}

/**
 * Converts formdata format to json object
 * @param {FormData} form_data The form data to be converted
 * @return {JSON}
 */
function FormDataToJSON(form_data){
    // Create an empty object to store the form data
    const jsonObject = {};

    // Iterate over the FormData entries and store them in the jsonObject
    for (var pair of form_data.entries()) {
        jsonObject[pair[0]] = pair[1];
    }

    return jsonObject;
}

/**
 * Converts json to formdata
 * @param {object} json The json object
 * @return {FormData}
 */
function JSONtoFormData(json){
    const formData = new FormData();

    for (const key in json) {
        if (json.hasOwnProperty(key)) {
            formData.append(key, json[key]);
        }
    }

    return formData;
}

function serializedToJson(serializedStr) {
    const obj = {};

    // empty or invalid
    if (!serializedStr || typeof serializedStr !== "string") {
        return obj;
    }

    const pairs = serializedStr.split("&");

    pairs.forEach(pair => {
        if (!pair) return;

        let [key, value] = pair.split("=");

        // Decode URI components
        key = decodeURIComponent(key);
        value = value !== undefined ? decodeURIComponent(value) : "";

        // Handle duplicate fields -> convert to array
        if (obj.hasOwnProperty(key)) {
            if (!Array.isArray(obj[key])) {
                obj[key] = [obj[key]];
            }
            obj[key].push(value);
        } else {
            obj[key] = value;
        }
    });

    return obj;
}

/**
 * This is used for formless transactions
 * @typedef {Object} AJAXOptions
 * @property {string} url The url of the form
 * @property {FormData} data The form data to be sent
 * @property {string} returnType The return type the request
 * @property {string} method The method of the request
 * @property {bool} sendRaw Set this to true if the call contains a file
 * @property {Function} beforeSend A method to be run when beforeSend is called
 * @property {int} timeout The wait time until timeout
 * @param {AJAXOptions} ajaxOptions
 * @return
 */
async function ajaxCall({url, data = {}, returnType = "json", method = "GET", sendRaw = false, beforeSend = null, timeout = 0}){
    let response_ = false;
    try {
        if(data instanceof FormData){
            data.append("response_type", returnType);
        }else{
            if (typeof data === "string") {
                data = serializedToJson(data);   // convert to object
            }
            
            data.response_type = returnType;
        }

        await $.ajax({
            type: method,
            url: url,
            data: data,
            dataType: returnType,
            timeout: timeout,
            contentType: sendRaw ? false : 'application/x-www-form-urlencoded; charset=UTF-8',
            processData: !sendRaw,
            beforeSend: function(){
                if(beforeSend != null)
                    beforeSend();
            },
            success: function (response) {
                response_ = response;
            },
            error: function (xhr, status, errorThrown) {
                console.error(`Error: ${errorThrown}`, xhr);
                let msg = (typeof errorThrown === "string" && errorThrown) ? errorThrown : status;
                if (status === "parsererror" && xhr && xhr.responseText) {
                    const t = xhr.responseText.trim();
                    const jsonStart = t.indexOf("{");
                    if (jsonStart >= 0) {
                        try {
                            const parsed = JSON.parse(t.slice(jsonStart));
                            if (parsed.errors) {
                                const e = parsed.errors;
                                msg = e.system_message || e.system_error || (typeof e === "string" ? e : JSON.stringify(e));
                            }
                        } catch (e) { /* keep msg */ }
                    }
                    if (!msg || msg === "parsererror") {
                        msg = "Server returned invalid response (check for PHP warnings before JSON).";
                    }
                }
                if (msg != null && typeof msg !== "string") {
                    msg = String(msg);
                }
                alert_box(msg || "Request failed", "danger");
            }
        });
    } catch (error) {
        alert_box(error.toString(), "danger");
        console.log(error);
    }

    return response_;
}

function fill_form(object, $form, filePreviewMap = {}) {
    Object.entries(object).forEach(([key, value]) => {
        const $field = $form.find(`[name="${key}"]`);

        if ($field.length) {
            const type = $field.attr('type');

            if ($field.is('select')) {
                $field.val(value).trigger('change');
            } else if ($field.is(':radio') || $field.is(':checkbox')) {
                $form.find(`[name="${key}"][value="${value}"]`).prop('checked', true);
            } else if ($field.is('textarea') || $field.is('input')) {
                if (type === 'file') {
                    $field.prop('disabled', true);

                    // Check if preview already exists
                    if ($field.siblings('.file-preview-link').length === 0 && value) {
                        const previewText = filePreviewMap[key] || "View File";
                        const previewLink = `<a href="/assets/${value}" target="_blank" class="file-preview-link text-blue-600 underline ml-2">${previewText}</a>`;
                        $field.parent().append(previewLink);
                    }
                } else {
                    $field.val(value);
                }
            }
        }
    });
}

/**
 * This is a custom override function to show a custom alert display onscreen
 * This automatically adds the alert modal if it was not found on a page
 * 
 * @param {string} message This is the message to be displayed
 * @param {string} color This is the background color of your message. It is set to primary by default
 * @param {integer} time This receives the time for the message to be displayed in seconds
 * 
 * @return this returns a message at the right top of a screen for an amount of time then disappears
 */
function alert_box(message, color = "primary", time = 5){
    if (message != null && typeof message !== "string") {
        message = typeof message === "object" ? JSON.stringify(message) : String(message);
    }
    // Create the container if it doesn't exist
    if($("body").find("#alert_modal").length < 1){
        const alert_modal = `
            <div id="alert_modal" 
                class="fixed top-5 right-5 z-50 flex flex-col space-y-3 w-full max-w-sm pointer-events-none">
            </div>`;
        $("body").append(alert_modal);
    }

    let my_number = $("#alert_modal").children(".alert_box").length;

    // Tailwind color presets for different alert types
    const colorClasses = {
        primary:  "bg-blue-600 text-white border-blue-700",
        success:  "bg-green-600 text-white border-green-700",
        danger:   "bg-red-600 text-white border-red-700",
        warning:  "bg-yellow-400 text-black border-yellow-500",
        dark:     "bg-gray-800 text-white border-gray-900",
    };

    const useColor = colorClasses[color] || colorClasses["primary"];

    const box = `
        <div id="alert_box${my_number + 1}"
             class="alert_box ${useColor} border shadow-lg pointer-events-auto 
                    rounded-lg px-5 py-3 transform transition-all duration-300 
                    opacity-0 translate-y-3">
            <div class="flex items-start space-x-3">
                <div class="flex-1 text-sm font-medium">
                    ${message}
                </div>
                <button class="text-white text-xl leading-none focus:outline-none"
                        onclick="$('#alert_box${my_number + 1}').remove()">
                    &times;
                </button>
            </div>
        </div>
    `;

    $("#alert_modal").append(box);

    // Animate in
    setTimeout(() => {
        $(`#alert_box${my_number + 1}`).removeClass("opacity-0 translate-y-3")
                                      .addClass("opacity-100 translate-y-0");
    }, 10);

    removeAlert("alert_box" + (my_number + 1), time);
}

/**
 * This function works with the alert_box to temporarily show an alert message
 * 
 * @param {string} id This receives the id of the element to be removed in string format
 * @param {int} time Receives the time to wait before element is removed 
 */
function removeAlert(id, time){
    if(time > 0){
        setTimeout(function(){
            $("#" + id).remove();
        }, time*1000);
    }    
}

/**
 * Creates an error span element
 */
function error_span(text){
    return `
            <span class="text-xs text-red-600 dark:text-red-400 error-span block">
              ${text}
            </span>
        `;
}

/**
 * This adds an error element to a specified input field
 * @param {JQuery} $input The input field to add the error to
 * @param {string} message The error message to be displayed
 */
function add_error_to_input($input, message){
    remove_error_from_input($input);

    const $errorSpan = $(error_span(message));
    $input.after($errorSpan);
}

/**
 * This removes an error element from a specified input field
 * @param {JQuery} $input The input field to remove the error from
 */
function remove_error_from_input($input){
    $input.removeClass("border-red-600 dark:border-red-400");
    const $nextElem = $input.next();
    if($nextElem.hasClass("text-red-600") || $nextElem.hasClass("dark:text-red-400")){
        $nextElem.remove();
    }
}

/**
 * This function takes an array of errors and displays them
 * @param {Object} errors An object containing the errors with field names as keys and error messages as values
 * @param {JQuery} $form The form where the errors need to be displayed
 */
function display_form_errors(errors, $form){
    for(const [fieldName, errorMessage] of Object.entries(errors)){
        if(fieldName === "system_message" || fieldName === "system_error"){
            const m = errorMessage != null && typeof errorMessage !== "string"
                ? (typeof errorMessage === "object" ? JSON.stringify(errorMessage) : String(errorMessage))
                : errorMessage;
            alert_box(m, "danger");
            continue;
        }

        const $input = $form.find(`[name="${fieldName}"]`);
        if($input.length){
            const m = errorMessage != null && typeof errorMessage !== "string"
                ? (typeof errorMessage === "object" ? JSON.stringify(errorMessage) : String(errorMessage))
                : errorMessage;
            add_error_to_input($input, m);
            // $input.addClass("border-red-600 dark:border-red-400");
        }
    }
}

/**
 * JS route() helper — works exactly like PHP version
 */
function js_route(name, params = {}) {
    if (!window.namedRoutes || !window.namedRoutes[name]) {
        alert_box("JS Route '" + name + "' not found");
        throw new Error("JS Route '" + name + "' not found");
    }

    let path = window.namedRoutes[name];

    // replace dynamic parameters
    for (const key in params) {
        path = path.replace('{' + key + '}', params[key]);
    }

    return path; // relative path — you can wrap with your JS url() if needed
}
