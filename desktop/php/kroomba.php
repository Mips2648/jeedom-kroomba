<?php

if (!isConnect('admin')) {
  throw new Exception('{{401 - Accès non autorisé}}');
}
sendVarToJS('eqType', 'kroomba');
$eqLogics = eqLogic::byType('kroomba');

?>

<div class="row row-overflow">
  <div class="col-sm-2">
  <!--<div class="col-lg-2 col-md-3 col-sm-4">-->
    <div class="bs-sidebar">
      <ul id="ul_eqLogic" class="nav nav-list bs-sidenav">
        <a class="btn btn-default eqLogicAction" style="width : 100%;margin-top : 5px;margin-bottom: 5px;" data-action="add"><i class="fas fa-plus-circle"></i> {{Ajouter un équipement}}</a>
        <li class="filter" style="margin-bottom: 5px;"><input class="filter form-control input-sm" placeholder="{{Rechercher}}" style="width: 100%"/></li>
        <?php
        foreach ($eqLogics as $eqLogic) {
          echo '<li class="cursor li_eqLogic" data-eqLogic_id="' . $eqLogic->getId() . '"><a>' . $eqLogic->getHumanName(true) . '</a></li>';
        }
        ?>
      </ul>
    </div>
  </div>

  <div class="col-lg-10 col-md-9 col-sm-8 eqLogicThumbnailDisplay" style="border-left: solid 1px #EEE; padding-left: 25px;">
    <legend><i class="fas fa-cog"></i>  {{Gestion}}</legend>
    <div class="eqLogicThumbnailContainer">
      <div class="cursor eqLogicAction" data-action="gotoPluginConf" style="background-color : #ffffff; height : 120px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;">
        <center>
          <i class="fas fa-wrench" style="font-size : 5em;color:#767676;"></i>
        </center>
        <span style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;word-wrap: break-word;color:#767676">
          <center>{{Configuration}}</center>
        </span>
      </div>
    </div>
    <legend><i class="fas fa-sitemap">  {{Mes Roombas}}</i></legend>
    <div class="eqLogicThumbnailContainer">
      <div class="cursor eqLogicAction" data-action="add" style="background-color : #ffffff; height : 200px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;" >
        <center>
          <i class="fas fa-plus-circle" style="font-size : 7em;color:#00979c;"></i>
        </center>
        <span style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;word-wrap: break-word;">
          <center>{{Ajouter}}</center>
        </span>
      </div>
      <?php
      foreach ($eqLogics as $eqLogic) {
        $opacity = ($eqLogic->getIsEnable()) ? '' : jeedom::getConfiguration('eqLogic:style:noactive');
        echo '<div class="eqLogicDisplayCard cursor" data-eqLogic_id="' . $eqLogic->getId() . '" style="background-color : #ffffff ; height : 200px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;' . $opacity . '" >';
        echo "<center>";
        echo '<img src="plugins/kroomba/plugin_info/kroomba_icon.png" height="105" width="95" />';
        echo "</center>";
        echo '<span style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;"><center>' . $eqLogic->getHumanName(true, true) . '</center></span>';
        echo '</div>';
      }
      ?>
    </div>
  </div>


<div class="col-lg-10 col-md-9 col-sm-8 eqLogic" style="border-left: solid 1px #EEE; padding-left: 25px;display: none;">
 <a class="btn btn-success eqLogicAction pull-right" data-action="save"><i class="fas fa-check-circle"></i> {{Sauvegarder}}</a>
 <a class="btn btn-danger eqLogicAction pull-right" data-action="remove"><i class="fas fa-minus-circle"></i> {{Supprimer}}</a>
 <a class="btn btn-default eqLogicAction pull-right" data-action="configure"><i class="fas fa-cogs"></i> {{Configuration avancée}}</a>
 <ul class="nav nav-tabs" role="tablist">
  <li role="presentation"><a href="#" class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab" data-action="returnToThumbnailDisplay"><i class="fas fa-arrow-circle-left"></i></a></li>
  <li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-tachometer-alt"></i> {{Equipement}}</a></li>
  <li role="presentation"><a href="#commandtab" aria-controls="profile" role="tab" data-toggle="tab"><i class="fas fa-list-alt"></i> {{Commandes}}</a></li>
</ul>
<div class="tab-content" style="height:calc(100% - 50px);overflow:auto;overflow-x: hidden;">
  <div role="tabpanel" class="tab-pane active" id="eqlogictab">
        <form class="form-horizontal">
          <fieldset>
            <div class="form-group">
              <label class="col-sm-3 control-label">{{Nom}}</label>
              <div class="col-sm-3">
                <input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display : none;" />
                <input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom du Roomba}}"/>
              </div>
            </div>
            <div class="form-group">
              <label class="col-sm-3 control-label" >{{Objet parent}}</label>
              <div class="col-sm-3">
                <select class="form-control eqLogicAttr" data-l1key="object_id">
                  <option value="">{{Aucun}}</option>
                  <?php
                  foreach (jeeObject::all() as $object) {
                    echo '<option value="' . $object->getId() . '">' . $object->getName() . '</option>';
                  }
                  ?>
                </select>
              </div>
            </div>
            <div class="form-group">
              <label class="col-sm-3 control-label">{{Catégorie}}</label>
              <div class="col-sm-8">
                <?php
                foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
                  echo '<label class="checkbox-inline">';
                  echo '<input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="' . $key . '" />' . $value['name'];
                  echo '</label>';
                }
                ?>

              </div>
            </div>
            <div class="form-group">
              <label class="col-sm-3 control-label"></label>
              <div class="col-sm-8">
                <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked/>{{Activer}}</label>
                <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked/>{{Visible}}</label>
              </div>
            </div>

            <div class="form-group">
              <label class="col-sm-3 control-label">{{Commentaire}}</label>
              <div class="col-sm-3">
                <textarea class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="commentaire" ></textarea>
              </div>
            </div>

          <div id="roomba_ip" class="form-group">
            <label class="col-sm-3 control-label">{{Adresse IP du Roomba}}</label>
            <div class="col-sm-3">
              <input type="text" class="eqLogicAttr configuration form-control" id="roomba_ip_input" data-l1key="configuration" data-l2key="roomba_ip" placeholder="exemple 192.168.0.77"/>
            </div>
          </div>

          <div id="username" class="form-group">
            <label class="col-sm-3 control-label">{{Nom du Roomba}}</label>
            <div class="col-sm-3">
              <input type="text" class="eqLogicAttr configuration form-control" id="username_input" data-l1key="configuration" data-l2key="username"/>
            </div>
          </div>

          <div id="password" class="form-group">
            <label class="col-sm-3 control-label">{{Mot de passe}}</label>
            <div class="col-sm-3">
              <input type="password" class="eqLogicAttr configuration form-control" id="password_input" data-l1key="configuration" data-l2key="password"/>
            </div>
            <div class="col-lg-2">
              <a class="btn btn-info bt_getPassword" id="bt_getPassword"><i class='fas fa-qrcode'></i> {{Récupérer le mot de passe}}</a>
            </div>
          </div>

          <div class="form-group expertModeVisible">
            <label class="col-sm-3 control-label">{{Auto-actualisation (cron)}}</label>
            <div class="col-sm-8">
              <input type="checkbox" class="eqLogicAttr" data-label-text="{{Activer}}" data-l1key="configuration" data-l2key="cron_isEnable" checked/>
            </div>
          </div>
          <div class="form-group expertModeVisible">
            <label class="col-sm-3 control-label"></label>
            <div class="col-sm-2">
              <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="autorefresh" placeholder="{{Auto-actualisation (cron)}}"/>
            </div>
            <div class="col-sm-1">
              <i class="fas fa-question-circle cursor floatright" id="bt_cronGenerator"></i>
            </div>
          </div>

        </fieldset>
      </form>
    </div>
    <div role="tabpanel" class="tab-pane" id="commandtab">

      <table id="table_cmd" class="table table-bordered table-condensed">
        <thead>
          <tr>
            <th style="width: 100px;">#</th>
            <th style="width: 300px;">{{Nom}}</th>
            <th style="width: 200px;">{{Options}}</th>
            <th style="width: 150px;"></th>
          </tr>
        </thead>
        <tbody>

        </tbody>
      </table>

      <form class="form-horizontal">
        <fieldset>
          <div class="form-actions">
            <a class="btn btn-danger eqLogicAction" data-action="remove"><i class="fas fa-minus-circle"></i> {{Supprimer}}</a>
            <a class="btn btn-success eqLogicAction" data-action="save"><i class="fas fa-check-circle"></i> {{Sauvegarder}}</a>
          </div>
        </fieldset>
      </form>
    </div>
  </div>
</div>
</div>
<div id="md_modal_kroomba" title="{{Récupérer le mot de passe}}">
  <p>
    <span class="glyphicon glyphicon-warning-sign" style="float:left; margin:12px 12px 20px 0;"></span>
    {{Premièrement : assurez-vous qu'aucune application mobile iRobot est connectée au Roomba.}}<br />
    {{Assurez-vous que votre roomba est sur sa base et alimenté (LED vertes allumées).}}<br />
    {{Puis restez appuyé sur le bouton HOME jusqu'à ce que votre roomba joue une série de sons (environ 2 secondes).}}<br />
    {{Pour les Roomba 960, il faut rester appuyé à la fois sur HOME et SPOT.}}<br />
    {{Relachez le bouton, votre roomba va faire clignoter son voyant WIFI.}}<br />
    {{Puis appuyez sur \"Continuer\".}}
  </p>
</div>
<script>
  $('#md_modal_kroomba').dialog({
    autoOpen: false,
    buttons: {
      "{{Continue}}": function() {
        $( this ).dialog( "close" );
        $.ajax({// fonction permettant de faire de l'ajax
            type: "POST", // methode de transmission des données au fichier php
            url: "plugins/kroomba/core/ajax/kroomba.ajax.php", // url du fichier php
            data: {
                action: "getPassword",
                ip:$('#roomba_ip_input').value(),
                blid:$('#username_input').value(),
            },
            dataType: 'json',
            error: function (request, status, error) {
                handleAjaxError(request, status, error);
            },
            success: function (data) { // si l'appel a bien fonctionné
                if (data.state != 'ok') {
                    $('#div_alert').showAlert({message: data.result, level: 'danger'});
                    return;
                }
                $('#password_input').value(data.result);
                $('#div_alert').showAlert({message: '{{Synchronisation réussie}}', level: 'success'});
            }
        });
      },
      Cancel: function() {
        $( this ).dialog( "close" );
      }
    }
  });
  $('#bt_getPassword').on('click', function () {
    $('#md_modal_kroomba').dialog('open');
    return false;
  });
</script>

<?php include_file('desktop', 'kroomba', 'js', 'kroomba'); ?>
<?php include_file('core', 'plugin.template', 'js'); ?>
