
/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

$('#typeEq').change(function(){
});

$('#bt_healthkroomba').on('click', function () {
	$('#md_modal').dialog({title: "{{Santé K Roomba}}"});
	$('#md_modal').load('index.php?v=d&plugin=kroomba&modal=health').dialog('open');
});

$('#bt_cronGenerator').on('click',function(){
   jeedom.getCronSelectModal({},function (result) {
       $('.eqLogicAttr[data-l1key=configuration][data-l2key=autorefresh]').value(result.value);
   });
});
$("#table_cmd").sortable({axis: "y", cursor: "move", items: ".cmd", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true});
function addCmdToTable(_cmd) {
    if (!isset(_cmd)) {
        var _cmd = {configuration: {}};
    }
    if (!isset(_cmd.configuration)) {
        _cmd.configuration = {};
    }
      var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';
      tr += '<td>';
      tr += '<span class="cmdAttr" data-l1key="id"></span>';
      tr += '</td><td>';
      tr += '<input class="cmdAttr form-control input-sm" data-l1key="name" style="width : 140px;" placeholder="{{Nom de la commande}}"></td>';
      tr += '</td><td>';
      tr += '<span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isVisible" checked/>{{Afficher}}</label></span> ';
      if (_cmd.subType == 'numeric' || _cmd.subType == 'binary') {
        tr += '<span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isHistorized" checked/>{{Historiser}}</label></span> ';
      }
      tr += '</td><td>';
      tr += '<input class="cmdAttr form-control input-sm" data-l1key="type" style="display : none;">';
      tr += '<input class="cmdAttr form-control input-sm" data-l1key="subType" style="display : none;">';
      if (is_numeric(_cmd.id)) {
        tr += '<a class="btn btn-default btn-xs cmdAction" data-action="configure"><i class="fas fa-cogs"></i></a> ';
        tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fas fa-rss"></i> {{Tester}}</a>';
      }
      tr += '<i class="fas fa-minus-circle pull-right cmdAction cursor" data-action="remove"></i></td>';
      tr += '</tr>';
      $('#table_cmd tbody').append(tr);
      $('#table_cmd tbody tr').last().setValues(_cmd, '.cmdAttr');
      jeedom.cmd.changeType($('#table_cmd tbody tr').last(), init(_cmd.subType));
}
