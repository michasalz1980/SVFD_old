// Using an object literal for a jQuery feature
var report = {
    init: function (sTo) {
        if ($("#hiddenType").val().match(/aushilfe/i)) {
            this.loadAllJSON('aushilfe');
        } else if ($("#hiddenType").val().match(/kassenabschluss/i)) {
            this.loadAllJSON('kassenabschluss');
        } else {
            this.loadAllJSON('kassenkraft');
        }
    },
    sendReport: function (sTo) {
        var aJSON = {
            'to': sTo,
            'type': $(".btn-info").text().toLowerCase()
        };
        $.ajax({
            type: "POST",
            url: config.URL_POST_REPORT,
            data: JSON.stringify(aJSON),
            success: this.showMessage,
            error: this.ajxFailure,
            dataType: "JSON"
        });
    },
    loadAllJSON: function (type) {
        $.ajax({
            type: "GET",
            url: config.URL_GET_SCHEDULE_ALL + '/' + type.toLowerCase(),
            success: this.preSelectionAll,
            error: this.ajxFailure,
            dataType: "JSON"
        });
    },
    preSelectionAll: function(data, textStatus, errorThrown) {
        /* Clean Table data first */
        $("table > tbody > tr").find("td:gt(0)").empty();
        /* Insert data */
        if (data != null && data.length > 0) {
            for (var i = 0; i < data.length; i++) {
                var sName = data[i].firstname.substr(0,2) + ". " + data[i].surname;
                var sEl = [data[i].start_date, data[i].end_date].join(";");
                var sUserId = data[i].id;
                var sApproved = data[i].approved;
                /* Get DOM element */
                var oTd = $("td[id='" + sEl + "']");
                /* Create new DOM element */
                var sNewEl = $('<div></div>');
                if (sApproved == 'true' && standby == 'false') {
                    oTd.empty();
                    $('<span class="bg-danger">' + sName + '</span>').appendTo(sNewEl);
                } else {
                    if ($("td[id='" + sEl + "'] .bg-danger").length == 0) {
                        $('<span>' + sName + ' </span>').appendTo(sNewEl);
                    }
                }
                /* Append new DOM element to Table Data */
                oTd.append(sNewEl);
            }

        }
        /* Fill not used Table data with value "Offen */
        $("table > tbody > tr > td:empty").html("Offen");
    },
    ajxSuccess: function (jqXHR, textStatus, errorThrown) {
        alert("Daten wurden erfolgreich gespeichert");
    },
    ajxFailure: function (jqXHR, textStatus, errorThrown) {
        alert("Daten wurden nicht gespeichert. Bitte versuchen Sie es noch einmal");
    }
};

var schedule;
schedule = {
    init: function (settings) {

    },
    getJSON: function () {
        var aPostData = [];
        $("input:checkbox:checked").each(function (idx, val) {
            var inp = $(this);
            if (inp.val()) {
                var aDate = inp.val().split(";");
                aPostData.push(aDate)
            }
        });
        var aJSON = JSON.stringify(aPostData);
        return aJSON;
    },
    getAdminJSON: function () {
        var aPostData = [];
        $("input:checkbox:checked").each(function (idx, val) {
            var inp = $(this);
            if (inp.val()) {
                var aDate = inp.val().split(";");
                var userid = inp.attr('userid');
                aDate.push(userid);
                aPostData.push(aDate)
            }
        });
        var aJSON = JSON.stringify(aPostData);
        return aJSON;
    },
    sendAdminJSON: function () {
        var aJSON = this.getAdminJSON();
        $.ajax({
            type: "POST",
            url: config.URL_POST_ADMIN_SCHEDULE,
            data: aJSON,
            success: this.ajxSuccess,
            error: this.ajxFailure,
            dataType: "JSON"
        });
    },
    sendJSON: function () {
        var aJSON = this.getJSON();
        $.ajax({
            type: "POST",
            url: config.URL_POST_SCHEDULE,
            data: aJSON,
            success: this.showMessage,
            error: this.ajxFailure,
            dataType: "JSON"
        });
    },
    loadJSON: function () {
        $.ajax({
            type: "GET",
            url: config.URL_GET_SCHEDULE,
            success: this.preSelection,
            error: this.ajxFailure,
            dataType: "JSON"
        });
    },
    loadIntro: function (data, textStatus, errorThrown) {
        if (data.type.match(/admin/)) {
            window.location = config.URL_GET_ADMIN;
        } else {
            $("#content").load( config.URL_GET_INTRO );
            $("#login").remove();
        }
    },
    loadAllJSON: function (type) {

        $.ajax({
            type: "GET",
            url: config.URL_GET_SCHEDULE_ALL + '/' + type.toLowerCase(),
            success: this.preSelectionAll,
            error: this.ajxFailure,
            dataType: "JSON"
        });
    },
    preSelection: function(data, textStatus, errorThrown) {
        if (data != null) {
            for (var i = 0; i < data.length; i++) {
                var sEl = [data[i].start_date, data[i].end_date].join(";");
                $("input:checkbox[value='" + sEl + "']").prop('checked', true);
            }
        }
    },
    preSelectionAll: function(data, textStatus, errorThrown) {
        /* Clean Table data first */
        $("table > tbody > tr").find("td:gt(0)").empty();
        /* Insert data */
        var aStandby = [];
        if (data != null && data.length > 0) {
            for (var i = 0; i < data.length; i++) {
                var sName = data[i].firstname.substr(0,2) + ". " + data[i].surname;
                var sEl = [data[i].start_date, data[i].end_date].join(";");
                var sUserId = data[i].id;
                var sApproved = data[i].approved;
                var sStandby = data[i].standby;
                /* Get DOM element */
                var oTd = $("td[id='" + sEl + "']");
                /* Create new DOM element */
                var sNewEl = $('<div></div>');
                if (sApproved == 'true' && sStandby == 'false') {
                    $('<input type="radio" name="' + sEl + '" userid="' + sUserId + '" checked>').appendTo(sNewEl);
                } else if (sApproved == 'false' && sStandby == 'true') {
                    var sDate = data[i].start_date.split(" ");
                    aStandby.push({
                        'date': sDate[0],
                        'userid': sUserId
                    });
                } else {
                    $('<input type="radio" name="' + sEl + '" userid="' + sUserId + '">').appendTo(sNewEl);
                }
                $('<span class="bg-warning">' + sName + '</span>').appendTo(sNewEl);
                /* Append new DOM element to Table Data */
                oTd.append(sNewEl);
            }

        }
        /* Fill not used Table data with value "Offen */
        $("table > tbody > tr > td:empty").html("Offen");
        /* IF Type == Aushilfe, add column "Bereitschaft" */
        $("table tr").find("td:gt(5),th:gt(5)").remove();
        if ($(".btn-info").text().toLowerCase() == 'aushilfe') {
            addBereitschaft();
            if (aStandby != null) {
                for(obj in aStandby) {
                    // ["$(\"input:radio[name='\" + aStandby[obj].date+ \"'][userid=22]\")"]
                    $("input:checkbox[name='" + aStandby[obj].date+ "'][userid='" + aStandby[obj].userid+ "']").prop('checked', true);
                }
            }
        }
    },
    showMessage: function (jqXHR, textStatus, errorThrown) {
        $("#content").load( config.URL_GET_MESSAGE );
        $("#schedule").remove();
    },
    ajxSuccess: function (jqXHR, textStatus, errorThrown) {
        // console.log(textStatus);
        document.location.reload(true);
    },
    ajxFailure: function (jqXHR, textStatus, errorThrown) {
        // console.log(textStatus);
    }
};

var user = {
    init: function (settings) {

    },
    login: function (username, password) {
        var aJSON = {
            'username': username.toLowerCase(),
            'password': password
        };
        var aJSON = JSON.stringify(aJSON);
        $.ajax({
            type: "POST",
            url: config.URL_POST_LOGIN,
            data: aJSON,
            success: schedule.loadIntro,
            error: this.ajxFailure,
            dataType: "JSON"
        });
    },
    logout: function () {
        $.ajax({
            type: "GET",
            url: config.URL_GET_LOGOUT,
            success: this.ajxSuccess,
            error: this.ajxFailure,
            dataType: "JSON"
        });
    },
    register: function (username, password, type, firstname, surname) {
        var aJSON = {
            'username': username,
            'password': password,
            'type': type,
            'firstname': firstname,
            'surname': surname
        };
        var aJSON = JSON.stringify(aJSON);
        $.ajax({
            type: "POST",
            url: config.URL_POST_REGISTER,
            data: aJSON,
            success: this.loadIntro,
            error: this.ajxFailure,
            dataType: "JSON"
        });
    },
    ajxSuccess: function (jqXHR, textStatus, errorThrown) {
        document.location.reload(true);
    },
    ajxFailure: function (data, textStatus, errorThrown) {
        // console.log(textStatus);
        var $url = this.url;
            if ($url.match(/login/)) {
                alert("Ihr Benutzername (E-Mail) bzw. das Passwort war falsch.")
            }
            if ($url.match(/register/)) {
                alert("Benutzername existiert bereits.")
            }
        },
    loadIntro: function (data, textStatus, errorThrown) {
        $("#content").load( config.URL_GET_INTRO );
        $("#login").remove();

    }
};

var login = {
    init: function (settings) {
        $("input:radio[name='login-process']:eq(0)").prop( "checked", true );
        login.change();
        $("input:radio[name='login-process']").change(function () {
            login.change();
        });
        $(".btn, .btn-primary").click(function () {
            login.click();
        });
        if ($("table").length == 1) {
            if (window.location.href.match(/admin.php/)) {
                // schedule.loadAllJSON();
            } else {
                schedule.loadJSON();
            }
        }
        if (document.URL.match(/admin\.php/)) {
            admin.init();
        }
    },
    change: function () {
        var radio = $("input:radio:checked").val();
        switch (radio) {
            case 'login':
                $("#firstname, #surname, #passwd2").hide();
                $("input:radio[name='type']").parent().hide();
                break;
            case 'register':
                $("#firstname, #surname, #passwd2").show();
                $("input:radio[name='type']").parent().show();
                break;
            default:
                $("#firstname, #surname, #passwd2").hide();
                $("input:radio[name='type']").parent().hide();
        }
    },
    click: function () {
        var radio = $("input:radio:checked").val();
        if (radio.match(/register/)) {
            if ($("#email").val() && $("#passwd1").val() && $("input:radio[name='type']").val()) {
                if ($("#passwd1").val() == $("#passwd2").val()) {
                    user.register($("#email").val(), md5($("#passwd1").val()), $("input:radio[name='type']:checked").val(), $("#firstname").val(), $("#surname").val());
                } else {

                }
            }
        }
        if (radio.match(/login/)) {
            if ($("#email").val() && $("#passwd1").val()) {
                user.login($("#email").val(), md5($("#passwd1").val()));
            }
        }
    }
};

var admin = {
    init: function() {
        schedule.loadAllJSON('aushilfe');
        $(".btn-group button").eq(0).addClass('btn-info');
        $(".btn-group button").click(function () {
            $(".btn-group button").removeClass('btn-info');
            $(this).addClass('btn-info');
            if ($(this).text().match(/Aushilfe/)) {
                schedule.loadAllJSON('aushilfe');
            } else if ($(this).text().match(/Kassenabschluss/)) {
                schedule.loadAllJSON('kassenabschluss');
            } else {
                schedule.loadAllJSON('kassenkraft');
            }
        });
    },
    sendAdminJSON: function () {
        var aJSON = this.getAdminJSON();
        $.ajax({
            type: "POST",
            url: config.URL_POST_ADMIN_SCHEDULE,
            data: aJSON,
            success: this.ajxSuccess,
            error: this.ajxFailure,
            dataType: "JSON"
        });
    },
    getAdminJSON: function () {
        var oaPostData = {
            'type': $(".btn-info").text().toLowerCase(),
            'active': [],
            'standby': []
        };
		
        // $("input:radio:checked").each(function(idx, el) { // previous version
		$("input:checked").each(function(idx, el) { // 14.06.2020
            /* Get data from DOM element */
            var sUserId = $(this).attr('userid');
            var sDates = $(this).attr('name');
            var sName = $(this).closest('td').attr('name');
            if (sName != null && sName == 'bereitschaft') {
                var aDate = [];
                aDate.push(sDates, sDates, sUserId);
                oaPostData['standby'].push(aDate);
            } else {
                /* Convert data */
                var aDate = sDates.split(";");
                if (aDate.length > 1) {
                    aDate.push(sUserId);
                    oaPostData['active'].push(aDate)
                }
            }
        });
        var aJSON = JSON.stringify(oaPostData);
        return aJSON;
    },
    ajxSuccess: function (jqXHR, textStatus, errorThrown) {
        alert("Daten wurden erfolgreich gespeichert");
        /* Refresh page */
        var type = $(".btn-info").text().toLowerCase();
        schedule.loadAllJSON(type);
    },
    ajxFailure: function (jqXHR, textStatus, errorThrown) {
        alert("Daten wurden nicht gespeichert. Bitte versuchen Sie es noch einmal");
    }
};

var password = {
    init: function() {
        $("#btnRequestPassword").click(function () {
            password.sendAdminJSON();
        });
    },
    sendAdminJSON: function () {
        var aJSON = this.getAdminJSON();
        $.ajax({
            type: "POST",
            url: config.URL_POST_RESETPASSWORD,
            data: aJSON,
            success: this.ajxSuccess,
            error: this.ajxFailure,
            dataType: "JSON"
        });
    },
    getAdminJSON: function () {
        var oaPostData = {
            'email': $("#email").val()
        };
        var aJSON = JSON.stringify(oaPostData);
        return aJSON;
    },
    ajxSuccess: function (jqXHR, textStatus, errorThrown) {
        alert("Sie erhalten ein neues Passwort per E-Mail.");
    },
    ajxFailure: function (jqXHR, textStatus, errorThrown) {
        alert("Es ist ein Fehler aufgetreten. Bitte probieren Sie es erneut.");
    }
};

var config = {
    "URL_POST_RESETPASSWORD": "/schedule/api/resetPassword",
    "URL_POST_SCHEDULE": "/schedule/api/save",
    "URL_POST_ADMIN_SCHEDULE": "/schedule/api/admin/save",
    "URL_GET_SCHEDULE": "/schedule/api/load",
    "URL_GET_SCHEDULE_ALL": "/schedule/api/admin/loadAll",
    "URL_GET_LOGOUT": "/schedule/api/logout",
    "URL_POST_LOGIN": "/schedule/api/login",
    "URL_POST_REGISTER": "/schedule/api/register",
    "URL_GET_INTRO": "/schedule/intro.html",
    "URL_GET_MESSAGE": "/schedule/successful.html",
    "URL_POST_REPORT": "/schedule/api/report",
    "URL_GET_ADMIN": "/schedule/admin.php"
};

$(document).ready(function () {
    var sUrl = window.location.href;
    if (sUrl.match(/admin\.php/)) {
        admin.init();
    }
    if (sUrl.match(/content\.php/)) {
        login.init();
    }
    if (sUrl.match(/report\.php/)) {
        report.init();
    }
    if (sUrl.match(/resetPassword\.php/)) {
        password.init();
    }
});

/* Utilities */
function fnJoinValues(aItems) {
    var sJointValues = '';
    for (var idx in aItems) {
        sJointValues += aItems[idx];
    }
    return sJointValues;
}
function addBereitschaft() {
    var sTh = '<th>Bereitschaft</th>';
    var sTd = '<td name="bereitschaft">-</td>';
    $("table > thead > tr").append(sTh);
    $("table > tbody > tr").append(sTd);

    var aItems = getNameHtmlEl();
    $.each(aItems, function( idx, value ) {
        $("table > tbody > tr:eq(" + idx + ") > td").last().html(fnJoinValues(aItems[idx]));
    });
	$("td[name='bereitschaft'] input[type='radio']").attr('type','checkbox'); // 14.06.2020
}
function getNameHtmlEl() {
    var ItemArray = {};
    $("table > tbody > tr span").each(function(idx) {
        var sName = $(this).text();
        var iTr = $(this).closest("tr").index();
        if (!ItemArray[iTr]) {
            ItemArray[iTr] = {};
        }
        var radio = $(this).prev().clone().removeAttr("checked");
        /* Extract data */
        var tDates = radio.attr('name').split(" ");
        var start = tDates[0];
        radio.attr('name', start);
        var sNewEl = $('<div></div>');
        radio.appendTo(sNewEl);
        $(this).clone().appendTo(sNewEl);
        ItemArray[iTr][sName] = sNewEl.get(0).outerHTML;
    });
    return ItemArray
};