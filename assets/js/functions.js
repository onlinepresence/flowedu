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
async function ajaxCall({url, data = {}, returnType = "text", method = "GET", sendRaw = false, beforeSend = null, timeout = 0}){
    let response_ = false;
    try {
        if(data instanceof FormData){
            data.append("response_type", returnType);
        }else{
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
            error: function (xhr, status, error) {
                console.error(`Error: ${error}`, xhr);
                alert_box(status != "" ? status : error, "danger");
            }
        });
    } catch (error) {
        alert_box(error.toString(), "danger");
        console.log(error);
    }

    return response_;
}