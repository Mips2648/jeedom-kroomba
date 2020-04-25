<?php

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
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

class kroomba extends eqLogic {

  public static $_widgetPossibility = array('custom' => true);

	public static function templateWidget(){
		$return = array('info' => array('string' => array(), 'numeric' => array(), 'binary' => array()));
		$return['info']['string']['state'] = array(
			'template' => 'tmplmultistate',
			'replace' => array("#_desktop_width_#" => "80","#_mobile_width_#" => "50"),
			'test' => array(
				array('operation' => "#value# == 'charge'", 'state_light' => "<img src='plugins/kroomba/core/img/kroomba_charge.png' title ='" . __('En charge', __FILE__) . "'>",
                        'state_dark' => "<img src='plugins/kroomba/core/img/kroomba_charge.png' title ='" . __('En charge', __FILE__) . "'>"),
				array('operation' => "#value# == 'home' || #value# == 'hmUsrDock'",'state_light' => "<img src='plugins/kroomba/core/img/kroomba_home.png' title ='" . __('Retour à la base', __FILE__) . "'>",
                        'state_dark' => "<img src='plugins/kroomba/core/img/kroomba_home.png' title ='" . __('Retour à la base', __FILE__) . "'>"),
				array('operation' => "#value# == 'run'",'state_light' => "<img src='plugins/kroomba/core/img/kroomba_run.png' title ='" . __('Nettoyage', __FILE__) . "'>",
                        'state_dark' => "<img src='plugins/kroomba/core/img/kroomba_run.png' title ='" . __('Nettoyage', __FILE__) . "'>"),
				array('operation' => "#value# == 'stop'",'state_light' => "<img src='plugins/kroomba/core/img/kroomba_stop.png' title ='" . __('Arrété', __FILE__) . "'>",
                        'state_dark' => "<img src='plugins/kroomba/core/img/kroomba_stop.png' title ='" . __('Arrété', __FILE__) . "'>"),
				array('operation' => "#value# == 'stuck'",'state_light' => "<img src='plugins/kroomba/core/img/kroomba_stuck.png' title ='" . __('Bloqué', __FILE__) . "'>",
                        'state_dark' => "<img src='plugins/kroomba/core/img/kroomba_stuck.png' title ='" . __('Bloqué', __FILE__) . "'>"),
				array('operation' => "#value# == 'unknown'|| #value# == ''",'state_light' => "<img src='plugins/kroomba/core/img/kroomba_unknown.png' title ='" . __('Inconnu', __FILE__) . "'>",
                        'state_dark' => "<img src='plugins/kroomba/core/img/kroomba_unknown.png' title ='" . __('Inconnu', __FILE__) . "'>")
			)
		);
		$return['info']['numeric']['battery'] = array(
			'template' => 'tmplmultistate',
			'test' => array(
				array('operation' => "#value# >75", 'state_light' => '<i class="icon icon_green jeedom-batterie3"></i>', 'state_dark' => '<i class="icon icon_green jeedom-batterie3"></i>'),
				array('operation' => "#value# <= 75 && #value# > 50",'state_light' => '<i class="icon icon_green jeedom-batterie2"></i>', 'state_dark' => '<i class="icon icon_green jeedom-batterie2"></i>'),
				array('operation' => "#value# <= 50 && #value# > 25",'state_light' => '<i class="icon icon_yellow jeedom-batterie1"></i>', 'state_dark' => '<i class="icon icon_yellow jeedom-batterie1"></i>'),
				array('operation' => "#value# <= 25", 'state_light' => '<i class="icon icon_red jeedom-batterie0"></i>', 'state_dark' => '<i class="icon icon_red jeedom-batterie0"></i>')
			)
		);
		$return['info']['binary']['binfull'] = array(
			'template' => 'tmplicon',
            'replace' => array('#_icon_on_#' => '<i class=\'icon icon_red maison-poubelle\'></i>','#_icon_off_#' => '<i class=\'icon icon_green fas fa-check\'></i>')
        );
		return $return;
	}

  public static function cron() {
    foreach (self::byType('kroomba', true) as $kroomba) {
      $cron_isEnable = $kroomba->getConfiguration('cron_isEnable',0);
      $autorefresh = $kroomba->getConfiguration('autorefresh','');
      $password = $kroomba->getConfiguration('password','');
      if ($cron_isEnable == 1 && $password != '' && $autorefresh != '') {
        try {
            $c = new Cron\CronExpression(checkAndFixCron($autorefresh), new Cron\FieldFactory);
            if ($c->isDue()) {
                try {
                    $kroomba->mission();
                } catch (Exception $exc) {
                    log::add('kroomba', 'error', __('Error in ', __FILE__) . $kroomba->getHumanName() . ' : ' . $exc->getMessage());
                }
            }
        } catch (Exception $exc) {
            log::add('kroomba', 'error', __('Expression cron non valide pour ', __FILE__) . $kroomba->getHumanName() . ' : ' . $autorefresh);
        }
      }
    }
  }

  public function preSave() {
		if ($this->getConfiguration('autorefresh') == '') {
			$this->setConfiguration('autorefresh', '*/5 * * * *');
		}
		if ($this->getConfiguration('cron_isEnable',"initial") == 'initial') {
			$this->setConfiguration('cron_isEnable', 1);
		}
  }

  public function postInsert() {
    $cmdlogic = $this->getCmd(null, 'status');
    if (!is_object($cmdlogic)) {
      $cmdlogic = new kroombaCmd();
      $cmdlogic->setName(__('Etat', __FILE__));
      $cmdlogic->setEqLogic_id($this->getId());
      $cmdlogic->setLogicalId('status');
      $cmdlogic->setIsVisible(1);
      $cmdlogic->setDisplay('generic_type', 'MODE_STATE');
      // On definit le template a appliquer par rapport à la version Jeedom utilisée
      if (version_compare(jeedom::version(), '4.0.0') >= 0) {
          $cmdlogic->setTemplate('dashboard','kroomba::state');
          $cmdlogic->setTemplate('mobile','kroomba::state');
      }
      $cmdlogic->setType('info');
      $cmdlogic->setSubType('string');
      $cmdlogic->save();
    }

    $cmdlogic = $this->getCmd(null, 'binFull');
    if (!is_object($cmdlogic)) {
      $cmdlogic = new kroombaCmd();
      $cmdlogic->setName(__('Bac plein', __FILE__));
      $cmdlogic->setEqLogic_id($this->getId());
      $cmdlogic->setLogicalId('binFull');
      $cmdlogic->setIsVisible(1);
      $cmdlogic->setDisplay('generic_type', 'GENERIC_INFO');
      // On definit le template a appliquer par rapport à la version Jeedom utilisée
      if (version_compare(jeedom::version(), '4.0.0') >= 0) {
          $cmdlogic->setTemplate('dashboard','kroomba::binfull');
          $cmdlogic->setTemplate('mobile','kroomba::binfull');
      }
      $cmdlogic->setType('info');
      $cmdlogic->setSubType('binary');
      $cmdlogic->save();
    }

    $cmdlogic = $this->getCmd(null, 'battery');
    if (!is_object($cmdlogic)) {
      $cmdlogic = new kroombaCmd();
      $cmdlogic->setName(__('Batterie', __FILE__));
      $cmdlogic->setEqLogic_id($this->getId());
      $cmdlogic->setLogicalId('battery');
      $cmdlogic->setDisplay('generic_type', 'BATTERY');
      $cmdlogic->setIsVisible(0);
      // On definit le template a appliquer par rapport à la version Jeedom utilisée
      if (version_compare(jeedom::version(), '4.0.0') >= 0) {
          $cmdlogic->setTemplate('dashboard','kroomba::battery');
          $cmdlogic->setTemplate('mobile','kroomba::battery');
      }
      $cmdlogic->setType('info');
      $cmdlogic->setSubType('numeric');
      $cmdlogic->save();
    }

    $cmdlogic = $this->getCmd(null, 'refresh');
    if (!is_object($cmdlogic)) {
      $cmdlogic = new kroombaCmd();
      $cmdlogic->setName(__('Rafraichir', __FILE__));
      $cmdlogic->setEqLogic_id($this->getId());
      $cmdlogic->setLogicalId('refresh');
      $cmdlogic->setIsVisible(0);
      $cmdlogic->setDisplay('generic_type', 'GENERIC_ACTION');
      $cmdlogic->setType('action');
      $cmdlogic->setSubType('other');
      $cmdlogic->save();
    }

    $cmdlogic = $this->getCmd(null, 'start');
    if (!is_object($cmdlogic)) {
      $cmdlogic = new kroombaCmd();
      $cmdlogic->setName(__('Démarrer', __FILE__));
      $cmdlogic->setEqLogic_id($this->getId());
      $cmdlogic->setLogicalId('start');
      $cmdlogic->setIsVisible(1);
      $cmdlogic->setDisplay('generic_type', 'GENERIC_ACTION');
      $cmdlogic->setDisplay('icon', '<i class="fas fa-play"></i>');
      $cmdlogic->setType('action');
      $cmdlogic->setSubType('other');
      $cmdlogic->save();
    }

    $cmdlogic = $this->getCmd(null, 'pause');
    if (!is_object($cmdlogic)) {
      $cmdlogic = new kroombaCmd();
      $cmdlogic->setName(__('Pause', __FILE__));
      $cmdlogic->setEqLogic_id($this->getId());
      $cmdlogic->setLogicalId('pause');
      $cmdlogic->setIsVisible(1);
      $cmdlogic->setDisplay('generic_type', 'GENERIC_ACTION');
      $cmdlogic->setDisplay('icon', '<i class="fas fa-pause"></i>');
      $cmdlogic->setType('action');
      $cmdlogic->setSubType('other');
      $cmdlogic->save();
    }

    $cmdlogic = $this->getCmd(null, 'resume');
    if (!is_object($cmdlogic)) {
      $cmdlogic = new kroombaCmd();
      $cmdlogic->setName(__('Continuer', __FILE__));
      $cmdlogic->setEqLogic_id($this->getId());
      $cmdlogic->setLogicalId('resume');
      $cmdlogic->setIsVisible(1);
      $cmdlogic->setDisplay('generic_type', 'GENERIC_ACTION');
      $cmdlogic->setDisplay('icon', '<i class="fas fa-step-forward"></i>');
      $cmdlogic->setType('action');
      $cmdlogic->setSubType('other');
      $cmdlogic->save();
    }

    $cmdlogic = $this->getCmd(null, 'stop');
    if (!is_object($cmdlogic)) {
      $cmdlogic = new kroombaCmd();
      $cmdlogic->setName(__('Stop', __FILE__));
      $cmdlogic->setEqLogic_id($this->getId());
      $cmdlogic->setLogicalId('stop');
      $cmdlogic->setIsVisible(1);
      $cmdlogic->setDisplay('generic_type', 'GENERIC_ACTION');
      $cmdlogic->setDisplay('icon', '<i class="fas fa-stop"></i>');
      $cmdlogic->setType('action');
      $cmdlogic->setSubType('other');
      $cmdlogic->save();
    }

    $cmdlogic = $this->getCmd(null, 'dock');
    if (!is_object($cmdlogic)) {
      $cmdlogic = new kroombaCmd();
      $cmdlogic->setName(__('Base', __FILE__));
      $cmdlogic->setEqLogic_id($this->getId());
      $cmdlogic->setLogicalId('dock');
      $cmdlogic->setIsVisible(1);
      $cmdlogic->setDisplay('generic_type', 'GENERIC_ACTION');
      $cmdlogic->setDisplay('icon', '<i class="fas fa-home"></i>');
      $cmdlogic->setType('action');
      $cmdlogic->setSubType('other');
      $cmdlogic->save();
    }
  }

  public function mission() {
    $resource_path = realpath(dirname(__FILE__) . '/../../resources');
    $cmd = 'cd ' . $resource_path . ' && python3 roombaStatus.py "'
      . $this->getConfiguration('roomba_ip','') . '" "'
      . $this->getConfiguration('username','') . '" "'
      . $this->getConfiguration('password','') . '"';
    log::add('kroomba', 'debug', 'Mission command : ' . str_replace($this->getConfiguration('password',''),'****',$cmd));
    if ($this->getConfiguration('roomba_ip','') == '' || $this->getConfiguration('username','') == '' || $this->getConfiguration('password','') == '') {
        log::add('kroomba', 'error', 'Missing arguments in mission');
        return;
    }
    exec($cmd . ' 2>&1',$result1);
    log::add('kroomba', 'debug', 'Mission raw result : ' . print_r($result1, true));

    $result = "{}";
    foreach ($result1 as $res) {
        $tempo = json_decode($res, true);
        if (json_last_error() == JSON_ERROR_NONE) {
            if (isset($tempo['state'])) {
                log::add('kroomba', 'debug', 'Roomba state : ' . $res);
                $result = $tempo;
            } else {
                log::add('kroomba', 'debug', 'Mission result : ' . $res);
            }
        }
    }

    if (!isset($result['state'])) {
        log::add('kroomba', 'debug', 'Wrong answer : ' . print_r($result,true));
        return;
    }
    $changed = false;
    if (isset($result['state']['reported']['cleanMissionStatus']['phase'])) {
        $phase = $result['state']['reported']['cleanMissionStatus']['phase'];
        log::add('kroomba', 'debug', 'phase : ' . $phase);
        $changed = $this->checkAndUpdateCmd('status', $phase) || $changed;
    }
    if (isset($result['state']['reported']['batPct'])) {
        $battery = $result['state']['reported']['batPct'];
        log::add('kroomba', 'debug', 'battery : ' . $battery);
        $changed = $this->checkAndUpdateCmd('battery', $battery) || $changed;
        $this->batteryStatus($battery);
        $this->setStatus('battery', $battery);
    }
    if (isset($result['state']['reported']['bin']['full'])) {
        $binFull = $result['state']['reported']['bin']['full'];
        if ($binFull == 0) {
            $changed = $this->checkAndUpdateCmd('binfull', false) || $changed;
            log::add('kroomba', 'debug', 'binfull : false');
        } else {
            $changed = $this->checkAndUpdateCmd('binfull', true) || $changed;
            log::add('kroomba', 'debug', 'binfull : true');
        }
    }
    if ($changed == true) {
        $this->refreshWidget();
    }
    // log::add('kroomba', 'debug', 'getStatus Battery: ' . $this->getStatus('battery', -2));
  }

  public function send_command($cmd) {
    $resource_path = realpath(dirname(__FILE__) . '/../../resources');
    $cmd = 'cd ' . $resource_path . ' && python3 roombaCmd.py ' . $cmd . ' "'
      . $this->getConfiguration('roomba_ip','') . '" "'
      . $this->getConfiguration('username','') . '" "'
      . $this->getConfiguration('password','') . '"';
    log::add('kroomba', 'debug', 'Send command : ' . str_replace($this->getConfiguration('password',''),'****',$cmd));
    exec($cmd . ' 2>&1',$result);
    log::add('kroomba', 'debug', 'Send command result : ' . print_r($result, true));
    return ;
  }

  public static function dependancy_info() {
    $return = array();
    $return['progress_file'] = jeedom::getTmpFolder('kroomba') . '/dependance';
    $return['state'] = 'ok';
    return $return;
  }

  public static function dependancy_install() {
    log::remove(__CLASS__ . '_update');
    return array('script' => dirname(__FILE__) . '/../../resources/install_#stype#.sh ' . jeedom::getTmpFolder('kroomba') . '/dependance', 'log' => log::getPathToLog(__CLASS__ . '_update'));
  }

  public function toHtml($_version = 'dashboard') {
    if (version_compare(jeedom::version(), '4.0.0') >= 0) {
       return parent::toHtml($_version);
    }

    $parameters = $this->getDisplay('parameters');
    if (is_array($parameters)) {
        foreach ($parameters as $key => $value) {
            $replace['#' . $key . '#'] = $value;
        }
    }

    $replace = $this->preToHtml($_version);
    if (!is_array($replace)) {
      return $replace;
    }
    $version = jeedom::versionAlias($_version);
    if ($this->getDisplay('hideOn' . $version) == 1) {
      return '';
    }
    $img_path = "plugins/kroomba/core/img/kroomba_";

    $statusCmd = kroombaCmd::byEqLogicIdAndLogicalId($this->getId(),'status');
    $status = $statusCmd->execCmd();
    log::add('kroomba', 'debug', 'toHtml status : ' . $status);
    $replace['#kroomba_ip#'] = $this->getConfiguration('roomba_ip','');
    $replace['#phase#'] = $status;
    switch($status)
    {
      case 'charge':
        $replace['#str_phase#'] = __('En charge', __FILE__);
        break;

      case 'home':
      case 'hmUsrDock':
        $replace['#str_phase#'] = __('Retour à la base', __FILE__);
        break;

      case 'run':
        $replace['#str_phase#'] = __('Nettoyage', __FILE__);
        break;

      case 'stop':
        $replace['#str_phase#'] = __('Arrété', __FILE__);
        break;

      case 'stuck':
        $replace['#str_phase#'] = __('Bloqué', __FILE__);
        break;

      case 'unknown':
      default:
        $replace['#str_phase#'] = __('Inconnu', __FILE__).$status;
        $status = 'unknown';
        break;
    }
    $replace['#img_phase#'] = $img_path . $status . '.png';

    $cmdlogic = kroombaCmd::byEqLogicIdAndLogicalId($this->getId(),'refresh');
    $replace['#refresh_id#'] = $cmdlogic->getId();
    $replace['#str_refresh#'] = __('Refresh', __FILE__);

    $cmdlogic = kroombaCmd::byEqLogicIdAndLogicalId($this->getId(),'start');
    $replace['#start_id#'] = $cmdlogic->getId();
    $replace['#str_start#'] = __('Start cleaning', __FILE__);

    $cmdlogic = kroombaCmd::byEqLogicIdAndLogicalId($this->getId(),'stop');
    $replace['#stop_id#'] = $cmdlogic->getId();
    $replace['#str_stop#'] = __('Stop cleaning', __FILE__);

    $cmdlogic = kroombaCmd::byEqLogicIdAndLogicalId($this->getId(),'dock');
    $replace['#dock_id#'] = $cmdlogic->getId();
    $replace['#str_dock#'] = __('Back to dock', __FILE__);

    $cmdlogic = kroombaCmd::byEqLogicIdAndLogicalId($this->getId(),'resume');
    $replace['#resume_id#'] = $cmdlogic->getId();
    $replace['#str_resume#'] = __('Resume cleaning', __FILE__);

    $cmdlogic = kroombaCmd::byEqLogicIdAndLogicalId($this->getId(),'pause');
    $replace['#pause_id#'] = $cmdlogic->getId();
    $replace['#str_pause#'] = __('Pause cleaning', __FILE__);

    $vcolor = ($_version == 'mobile') ? 'mcmdColor' : 'cmdColor';
		if ($this->getPrimaryCategory() == '') {
			$replace['#cmdColor#'] = jeedom::getConfiguration('eqLogic:category:default:' . $vcolor);
		} else {
			$replace['#cmdColor#'] = jeedom::getConfiguration('eqLogic:category:' . $this->getPrimaryCategory() . ':' . $vcolor);
		}

    $html = $this->postToHtml($_version, template_replace($replace, getTemplate('core', $_version, 'kroomba', 'kroomba')));
    return $html;
  }
}

class kroombaCmd extends cmd {
  public static $_widgetPossibility = array('custom' => true);
    public function execute($_options = null) {
      $eqLogic = $this->getEqLogic();
      switch ($this->getLogicalId()) {
        case 'start':
          $eqLogic->send_command("start");
          sleep(4);
        case 'pause':
          $eqLogic->send_command("pause");
          sleep(4);
        case 'resume':
          $eqLogic->send_command("resume");
          sleep(4);
        case 'stop':
          $eqLogic->send_command("stop");
          sleep(4);
        case 'dock':
          $eqLogic->send_command("dock");
          sleep(8);
      }
      $eqLogic->mission();
  }
}

?>
