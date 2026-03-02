// MOD EN ARCHIVO CRÍTICO: Obtención robusta del token CSRF
$('i.glyphicon-refresh-animate').hide();

function getCsrfToken() {
    if (typeof yii !== 'undefined' && yii.getCsrfToken) {
        var token = yii.getCsrfToken();
        if (token) return token;
    }
    var metaToken = $('meta[name="csrf-token"]').attr('content');
    if (metaToken) return metaToken;
    var hiddenToken = $('input[name="_csrf"]').first().val();
    if (hiddenToken) return hiddenToken;
    console.warn('No se pudo obtener el token CSRF');
    return '';
}

var csrfToken = getCsrfToken();

if (csrfToken) {
    $.ajaxSetup({
        headers: {
            'X-CSRF-Token': csrfToken
        }
    });
}

function updateRoutes(r) {
    _opts.routes.available = r.available;
    _opts.routes.assigned = r.assigned;
    search('available');
    search('assigned');
}

$('#btn-new').click(function () {
    var $this = $(this);
    var route = $('#inp-route').val().trim();
    if (route != '') {
        $this.children('i.glyphicon-refresh-animate').show();
        $.post($this.attr('href'), {
            route: route,
            _csrf: csrfToken
        }, function (r) {
            $('#inp-route').val('').focus();
            updateRoutes(r);
        }).always(function () {
            $this.children('i.glyphicon-refresh-animate').hide();
        });
    }
    return false;
});

$('.btn-assign').click(function () {
    var $this = $(this);
    var target = $this.data('target');
    var routes = $('select.list[data-target="' + target + '"]').val();

    if (routes && routes.length) {
        $this.children('i.glyphicon-refresh-animate').show();
        $.post($this.attr('href'), {
            routes: routes,
            _csrf: csrfToken
        }, function (r) {
            updateRoutes(r);
        }).always(function () {
            $this.children('i.glyphicon-refresh-animate').hide();
        });
    }
    return false;
});

$('#btn-refresh').click(function () {
    var $icon = $(this).children('span.glyphicon');
    $icon.addClass('glyphicon-refresh-animate');
    $.post($(this).attr('href'), {
        _csrf: csrfToken
    }, function (r) {
        updateRoutes(r);
    }).always(function () {
        $icon.removeClass('glyphicon-refresh-animate');
    });
    return false;
});

$('.search[data-target]').keyup(function () {
    search($(this).data('target'));
});

function search(target) {
    var $list = $('select.list[data-target="' + target + '"]');
    $list.html('');
    var q = $('.search[data-target="' + target + '"]').val();
    $.each(_opts.routes[target], function () {
        var r = this;
        if (r.indexOf(q) >= 0) {
            $('<option>').text(r).val(r).appendTo($list);
        }
    });
}

search('available');
search('assigned');