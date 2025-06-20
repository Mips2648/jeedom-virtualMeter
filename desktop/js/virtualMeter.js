
/*
 * Fonction pour l'ajout de commande, appellé automatiquement par plugin.template
 */
function addCmdToTable(_cmd) {
    if (!isset(_cmd)) {
        var _cmd = { configuration: {} };
    }
    if (!isset(_cmd.configuration)) {
        _cmd.configuration = {};
    }
    let tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';

    tr += '<td>';
    tr += '<span class="cmdAttr" data-l1key="id" style="display:none;"></span>';
    tr += '<input class="cmdAttr form-control input-sm" data-l1key="type" value="' + init(_cmd.type) + '" style="display : none;">';
    tr += '<input class="cmdAttr form-control input-sm" data-l1key="subType" value="' + init(_cmd.subType) + '" style="display : none;">';

    tr += '<div class="input-group">';
    tr += '<input class="cmdAttr form-control input-sm roundedLeft" data-l1key="name" placeholder="{{Nom de la commande}}">';
    tr += '<span class="input-group-btn"><a class="cmdAction btn btn-sm btn-default" data-l1key="chooseIcon" title="{{Choisir une icône}}"><i class="fas fa-icons"></i></a></span>';
    tr += '<span class="cmdAttr input-group-addon roundedRight" data-l1key="display" data-l2key="icon" style="font-size:19px;padding:0 5px 0 0!important;"></span>';
    tr += '</div>';
    tr += '</td>';

    tr += '<td>';
    tr += '<select class="form-control cmdAttr input-sm" data-l1key="configuration" data-l2key="type">';
    tr += '<option value="daily">{{Journalier}}</option>';
    tr += '<option value="monthly">{{Mensuel}}</option>';
    tr += '</select>';
    tr += '</td>';

    tr += '<td>';
    tr += '<div class="input-group">';
    tr += '<input class="cmdAttr form-control input-sm roundedLeft" data-l1key="configuration" data-l2key="meter" placeholder="{{Valeur}}">';
    tr += '<span class="input-group-btn">';
    tr += '<a class="btn btn-default btn-sm listEquipementInfo roundedRight" data-input="meter"><i class="fas fa-list-alt"></i></a>';
    tr += '</span>';
    tr += '</div>';
    tr += '</td>';

    tr += '<td>';
    tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isVisible" checked/>{{Afficher}}</label> ';
    tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isHistorized" checked/>{{Historiser}}</label> ';
    tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="display" data-l2key="invertBinary"/>{{Inverser}}</label> ';
    tr += '<div style="margin-top:7px;">';
    tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="minValue" placeholder="{{Min}}" title="{{Min}}" style="width:30%;max-width:80px;display:inline-block;margin-right:2px;">';
    tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="maxValue" placeholder="{{Max}}" title="{{Max}}" style="width:30%;max-width:80px;display:inline-block;margin-right:2px;">';
    tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="unite" placeholder="Unité" title="{{Unité}}" style="width:30%;max-width:80px;display:inline-block;margin-right:2px;">';
    tr += '</div>';
    tr += '</td>';

    tr += '<td>';
    tr += '<span class="cmdAttr" data-l1key="htmlstate"></span>';
    tr += '</td>';

    tr += '<td>';
    if (is_numeric(_cmd.id)) {
        tr += '<a class="btn btn-default btn-xs cmdAction" data-action="configure"><i class="fas fa-cogs"></i></a> ';
        tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fas fa-rss"></i> {{Tester}}</a>';
    }
    tr += '<i class="fas fa-minus-circle pull-right cmdAction cursor" data-action="remove" title="{{Supprimer la commande}}"></i>';
    tr += '</td>';
    tr += '</tr>';

    $('#table_cmd tbody').append(tr);

    const $tr = $('#table_cmd tbody tr:last');
    $tr.setValues(_cmd, '.cmdAttr');
    jeedom.cmd.changeType($tr, init(_cmd.subType));

    $tr.find('.cmdAttr[data-l1key=type]').prop("disabled", true);
}

$("#table_cmd").sortable({ axis: "y", cursor: "move", items: ".cmd", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true });

$('.pluginAction[data-action=openLocation]').on('click', function () {
    window.open($(this).attr("data-location"), "_blank", null);
});

$("#bt_addVirtualMetter").on('click', function (event) {
    addCmdToTable({ type: 'info' })
    modifyWithoutSave = true
})

$("#table_cmd").delegate(".listEquipementInfo", 'click', function () {
    var el = $(this)
    jeedom.cmd.getSelectModal({ cmd: { type: 'info', subType: 'numeric' } }, function (result) {
        var calcul = el.closest('tr').find('.cmdAttr[data-l1key=configuration][data-l2key=' + el.data('input') + ']')
        calcul.atCaret('insert', result.human)
    })
})