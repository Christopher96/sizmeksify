
var checks_json;
var fade_time = 500;

$(document).ready(function(){
    $.ajax({
        url: "data/alerts.json", 
        method: "GET",
        complete: function(res){
            checks_json = $.parseJSON(res.responseText);

            $("#sizmeksify").submit(function(e){
                e.preventDefault();
        
                var fd = new FormData(this);

                var submit = $(this).find("[type=submit]");
                var submitTxt = submit.text();
                submit.text("Validating...");
                submit.prop("disabled", true);

                var loader = $(this).find(".loader");
                loader.show();

                $.ajax({
                    url: 'php/read.php',
                    data: fd,
                    processData: false,
                    contentType: false,
                    type: 'POST',
                    success: function(data){
                        printAlerts(data);
                    },
                    complete: function(res) {
                        console.log(res.responseText);
                        submit.text(submitTxt);
                        submit.prop("disabled", false);
                        loader.hide();
                    }
                });
            });
        }
    });
});

function validate(formdata) {
    
}

function printAlerts(data) {
    try {
        data = $.parseJSON( data );
    } catch(e) {
        console.log(e);
    }

    $("#checks").empty();
    $("#errors").empty();

    var checks_container = $("#checks-container");
    var errors_container = $("#errors-container");

    checks_container.hide();
    errors_container.hide();

    if( data.errors ){
        $.each( data.errors, function( key, value ){
            newAlert( true, value, "" );
        });

        errors_container.show();

        return;
    }

    if( data.input ) {
        handleInput(data.input);
    } else {
        return;
    }

    if( data.checks ) {
        handleChecks(data.checks);
    }

}

function handleInput(input) {
    console.log(input);
    $("#file_name").text( input.name );
    $("#file_size").text( input.size );
    $("#file_info").fadeIn(fade_time);
    
    var bar = $("#file_info .progress-bar");

    bar.find(".bar").animate({
        width: input.progress
    }, fade_time*2)
    .removeClass("error")
    .removeClass("success")
    .addClass(input.pass ? "success" : "error");

    bar.find(".size").text(input.size);
}


function newAlert( isError, title, info ){
    
    $.ajax({
        url: 'includes/alert.html',
        type: 'GET',
        success: function(data){
            var alert = $(data);

            alert.addClass((isError) ? "error" : "success");
            var container = (isError) ? $("#errors") : $("#checks");
            var iconclass = (isError) ? "times-circle" : "check-circle";
            
            container.append( alert );

            alert.find(".icon")
                .removeClass("fa-times-circle")
                .removeClass("fa-check-circle")
                .addClass("fa-"+iconclass);
            alert.find(".text").html( "<span class='bold'>"+title+"</span><br/><span class='light'>"+info+"</span>" );

            alert.fadeIn( fade_time );
        }
    });
}

function handleChecks(checks) {
        
    $("#checks").empty();
    $("#errors").empty();

    var checks_container = $("#checks_container");
    var errors_container = $("#errors_container");

    var check_names = Object.keys(checks_json);

    var success_checks = 0;
    var total_checks= check_names.length;

    for(i in check_names) {
        var isError = false;
        var check_name = check_names[i];
        var check = checks_json[check_name];
        var title = false;
        var info = "";

        if( checks[check_name] ){
            success_checks++;

            var values = checks[check_name];
            
            switch(check_name) {
                case "document": 
                    info =  "Path: " + values.path;
                break;
                case "library":
                    info =  "Source: " + values.source + "<br/>" + "Protocol: " + values.protocol;
                break;
                case "clickthrough":
                case "initialize":
                    info =  "Path: " + values.path + "<br/>" + "Line " + values.line_num + ": <i>" + values.line + "</i>";
                break;
                case "fallback":
                    var multiple = (values.images.length > 1);
                    $.each(values.images, function(i, fallback) {
                        if(multiple) info += "["+i+"] ";
                        info += "Path: " + fallback.path + "<br/>";
                    });
                break;
            }

            switch(check_name) {
                case "library":
                case "initialize":
                    title = (values.type == "Adkit") ? check.success[0] : check.success[1];
                break;
                case "fallback":
                    title = (values.images.length > 1) ? check.success[1] : check.success[0];
                    title += " (" + values.width + "x" + values.height + " px)";
                break;
            }

        } else {
            isError = true;
            info = "Reference: <a target='_blank' href=" + check.link + ">" + check.link + "</a>";
        }  

        if(!title) {
            title = isError ? check.error : check.success;
        }

        newAlert( isError, title, info );
    }

    if( success_checks > 0 ){
        checks_container.show();
    } else {
        checks_container.hide();
    }

    if( success_checks < total_checks ){
        errors_container.show();
    } else {
        errors_container.hide();
    }

    $("#checks_num").text( success_checks );
    $("#total_checks").text( total_checks );

}