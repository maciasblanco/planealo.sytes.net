/*$.fn.modal.Constructor.prototype.enforceFocus = function() {};*/

/**
 * Activates the Select2 plugin for a specific dropdown
 */
var loadSelect2 = function(dropdown) {
    var select2DefaultConf = {
        placeholder: "Seleccione",
        theme: "classic",
        allowClear: true,
        language: "es",
        loadingMsg : "Cargando...",
    };

    var select2ParamConf = {};

    //If the body has data-select2DefaultConf, this is used to configure the Select2
    if ($("body").data('select2DefaultConf')) {
        select2ParamConf = Object.assign({}, $("body").data('select2DefaultConf'));
    } 

    var select2Conf = $.extend(true, select2DefaultConf, select2ParamConf);

    $(dropdown).data("loadingMsg", select2Conf.loadingMsg);

    //If is a multiple dropdown the allowClear is false to avoid deplaced options
    if (typeof $(dropdown).attr('multiple') != "undefined") {
        select2Conf.allowClear = false;
    }

    //If dropdown has data-ajax-url means is filled by ajax request on keypress
    if ($(dropdown).data('ajax-url')) {
        select2Conf.ajax = {
            url: $(dropdown).data('ajax-url'),
            dataType: 'json',
        };

        //To add more params to the query
        if ($(dropdown).data('data')) {
            var dataParam = $(dropdown).data('data');

            //Function to get the query params
            var dataFunc = function (searchParams) {
                $.each(dataParam, function (index, param) {
                    var val = "";

                    //If the param is an ID, get the object value
                    if (/^#[\w-]+$/.test(param)) {
                        val = $(param).val();
                    } else {
                        val = param;
                    }

                    //Add the param to the query params
                    if (val != "") {
                       searchParams[index] = val;
                    }
                });

                return searchParams;
            }

            //Set the function to get the query params
            select2Conf.ajax.data = dataFunc;
        }

        if ($(dropdown).data('ajax-min-length')) {
            select2Conf.minimumInputLength = $(dropdown).data('ajax-min-length');
        } else {
            select2Conf.minimumInputLength = 3;
        }
    }

    //If data-placeholder is defined the dropdown placeholder text is changed
    if ($(dropdown).data('placeholder')) {
        select2Conf.placeholder = $(dropdown).data('placeholder');
    }

    //Select2 is activated
    $(dropdown).select2(select2Conf).on('select2:unselecting', function(e) {
        $(dropdown).data('unselecting', true);
    }).on('select2:open', function(e) {
        if ($(dropdown).data('unselecting')) {    
            $(dropdown).removeData('unselecting');
            $(dropdown).select2('close');
        }
    });
}

/**
 * When a dropdown with class "has-dependent-dropdown" triggers the change event
 * an ajax request is send to fill the dependent dropdown
 */
$(document).on("change", ".has-dependent-dropdown", function(){
    var undef = "undefined";
    var self = $(this);
    var dataDepen = $(this).data('dependent');

    //Checks if data-dependent is not defined in the dropdown or is empty
    if (typeof dataDepen == undef || $.isEmptyObject(dataDepen)) {
        return false;
    }

    var loadingMsg = self.data("loadingMsg");

    $.each(dataDepen, function(index, row) {
        //The url, data and destiny attribute are required
        if (typeof row.url == undef || typeof row.data == undef || typeof row.destiny == undef) {
            return;
        }

        //If has a custom condition to be executed, is checked
        var hasCondition = (typeof row.condition != undef) ? true : false;
        var conditionFunc = null;

        if (hasCondition) {
            conditionFunc = new Function('return ' + row.condition);
        }

        var autoSelectSingle = (typeof row.autoSelectIfSingle != undef) ? row.autoSelectIfSingle : false;
        var destiny = $("#" + row.destiny);
        var data = {};
        var allEmpty = true;

        //Collects the values of all the elements passed in data attributes
        $.each(row.data, function(index, param) {
            var val = (param == "this") ? self.val() : $("#" + param).val();
            data[index] = val;

            if (val != "") {
                allEmpty = false;
            }
        });

        //Makes the ajax request
        $.ajax({
            url: row.url,
            type: (typeof row.type == undef) ? "get" : row.type,
            data: data,
            beforeSend: function(jqXHR) {
                //Clear the destiny dropdown, leaving the prompt
                destiny.find("option").filter(function() {
                    return $(this).val() != "";
                }).each(function() {
                    $(this).remove();
                });

                //If all the elements defined in the data attribute are empty, the request is not sent
                //and the destiny change event is triggered
                //Also abort if a condition was send and it returns false
                if ((hasCondition && !conditionFunc()) || allEmpty) {
                    jqXHR.abort();
                    destiny.trigger("change");
                } else {
                    destiny.prop("disabled", true);
                }

            },
            complete: function(jqXHR, textStatus) {
                destiny.prop("disabled", false);
            },
            success: function(data, textStatus, jqXHR) {
                //Fills the destiny dropdown with the received data
                var datos = null;

                if (typeof jqXHR.responseJSON != "undefined") {
                    datos = data;
                } else {
                    datos = JSON.parse(data);
                }

                //Construct the option tag for each result
                $.each(datos.results, function(index, row){
                    var opt = $("<option>");

                    //Adds all the received attributes
                    $.each(row, function(name, value) {
                        if (name == "text") {
                            opt.text(value);
                        } else if (name == "id") {
                            opt.attr("value", value);
                        } else {
                            opt.data(name, value);
                        }
                    });

                    destiny.append(opt);
                });

                //If the results contains only a single value, is selected
                if (datos.results.length == 1 && autoSelectSingle) {
                    destiny.find('option').filter(function() {
                        return $(this).val() != "";
                    }).first().prop('selected', true);
                }

                //Triggers the destiny change event
                destiny.trigger("change");
            }
        });
    });
});


/**
 * Event attached to the selects allowing to reload its options
 */
$(document).on("reload-select-options", "select", function(e, callback) {
    var dropdown = $(this);
    var url = dropdown.data('reload-url');
    var reloadData = dropdown.data('reload-data');

    if (typeof url == "undefined") {
        return false;
    }

    var data = {};

    if (typeof reloadData != "undefined") {
        $.each(reloadData, function(name, value) {
            //Check if is an ID
            if (/^\#.+/.test(value)) {
                data[name] = $(value).val();
            } else {
                data[name] = value;
            }
        });
    }

    var jqXHR = $.ajax({
        url: url,
        type: 'get',
        data: data,
        beforeSend: function() {
            dropdown.find('option').filter(function(index, element) {
                return $(element).val() != "";
            }).each(function(index, element) {
                element.remove();
            });
        },
        success: function(data) {
            $.each(data.results, function(index, row) {
                var opt = new Option(row.text, row.id, false, false);
                dropdown.append(opt);
            });

            dropdown.trigger('change');
        },
        complete: function() {
            if (typeof callback == "function") {
                callback();
            }
        }
    });

    return jqXHR;
});