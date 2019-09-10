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
			'replace' => array("#_width_#" => "80","#_height_#" => "80"),
			'test' => array(
				array('operation' => "#value# == 'charge'",'state' => "<img src='plugins/kroomba/core/img/kroomba_charge.png' title ='" . __('En charge', __FILE__) . "'>"),
				array('operation' => "#value# == 'home' || #value# == 'hmUsrDock'",'state' => "<img src='plugins/kroomba/core/img/kroomba_home.png' title ='" . __('Retour à la base', __FILE__) . "'>"),
				array('operation' => "#value# == 'run'",'state' => "<img src='plugins/kroomba/core/img/kroomba_run.png' title ='" . __('Nettoyage', __FILE__) . "'>"),
				array('operation' => "#value# == 'stop'",'state' => "<img src='plugins/kroomba/core/img/kroomba_stop.png' title ='" . __('Arrété', __FILE__) . "'>"),
				array('operation' => "#value# == 'stuck'",'state' => "<img src='plugins/kroomba/core/img/kroomba_stuck.png' title ='" . __('Bloqué', __FILE__) . "'>"),
				array('operation' => "#value# == 'unknown'|| #value# == ''",'state' => "<img src='plugins/kroomba/core/img/kroomba_unknown.png' title ='" . __('Inconnu', __FILE__) . "'>")
			)
		);
		$return['info']['numeric']['battery'] = array(
			'template' => 'tmplmultistate',
			'test' => array(
				array('operation' => "#value# >75",'state' => '<i class="icon icon_green jeedom-batterie3"></i>'),
				array('operation' => "#value# <= 75 && #value# > 50",'state' => '<i class="icon icon_green jeedom-batterie2"></i>'),
				array('operation' => "#value# <= 50 && #value# > 25",'state' => '<i class="icon icon_yellow jeedom-batterie1"></i>'),
				array('operation' => "#value# <= 25",'state' => '<i class="icon icon_red jeedom-batterie0"></i>')
			)
		);
		$return['info']['binary']['binfull'] = array(
			'template' => 'tmplicon',
            'replace' => array('#_icon_on_#' => '<i class=\'icon icon_red maison-poubelle\'></i>','#_icon_off_#' => '<i class=\'icon icon_green fas fa-check\'></i>')
        );
		return $return;
	}

  public static function cron() {
    foreach (self::byType('kroomba') as $kroomba) {
      $cron_isEnable = $kroomba->getConfiguration('cron_isEnable',0);
      $autorefresh = $kroomba->getConfiguration('autorefresh','');
      $password = $kroomba->getConfiguration('password','');
      if ($kroomba->getIsEnable() == 1 && $cron_isEnable == 1 && $password != '' && $autorefresh != '') {
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
  public function postSave() {
    $cmdlogic = kroombaCmd::byEqLogicIdAndLogicalId($this->getId(),'battery');
    if (!is_object($cmdlogic)) {
      $cmdlogic = new kroombaCmd();
      $cmdlogic->setName(__('Batterie', __FILE__));
      $cmdlogic->setEqLogic_id($this->getId());
      $cmdlogic->setLogicalId('battery');
      $cmdlogic->setDisplay('generic_type', 'BATTERY');
      $cmdlogic->setIsVisible(0);
    }
    $cmdlogic->setType('info');
    $cmdlogic->setSubType('numeric');
    $cmdlogic->setTemplate('dashboard','kroomba::battery');
    $cmdlogic->setTemplate('mobile','kroomba::battery');
    $cmdlogic->save();

    $cmdlogic = kroombaCmd::byEqLogicIdAndLogicalId($this->getId(),'status');
    if (!is_object($cmdlogic)) {
      $cmdlogic = new kroombaCmd();
      $cmdlogic->setName(__('Statut', __FILE__));
      $cmdlogic->setEqLogic_id($this->getId());
      $cmdlogic->setLogicalId('status');
      $cmdlogic->setIsVisible(1);
      $cmdlogic->setDisplay('generic_type', 'MODE_STATE');
    }
    $cmdlogic->setType('info');
    $cmdlogic->setSubType('string');
    $cmdlogic->setTemplate('dashboard','kroomba::state');
    $cmdlogic->setTemplate('mobile','kroomba::state');
    $cmdlogic->save();

    $cmdlogic = kroombaCmd::byEqLogicIdAndLogicalId($this->getId(),'binFull');
    if (!is_object($cmdlogic)) {
      $cmdlogic = new kroombaCmd();
      $cmdlogic->setName(__('Bac plein', __FILE__));
      $cmdlogic->setEqLogic_id($this->getId());
      $cmdlogic->setLogicalId('binFull');
      $cmdlogic->setIsVisible(1);
      $cmdlogic->setDisplay('generic_type', 'GENERIC_INFO');
    }
    $cmdlogic->setType('info');
    $cmdlogic->setSubType('binary');
    $cmdlogic->setTemplate('dashboard','kroomba::binfull');
    $cmdlogic->setTemplate('mobile','kroomba::binfull');
    $cmdlogic->save();

    $cmdlogic = kroombaCmd::byEqLogicIdAndLogicalId($this->getId(),'mission');
    if (!is_object($cmdlogic)) {
      $cmdlogic = new kroombaCmd();
      $cmdlogic->setName(__('Rafraichir', __FILE__));
      $cmdlogic->setLogicalId('mission');
      $cmdlogic->setIsVisible(0);
      $cmdlogic->setDisplay('generic_type', 'GENERIC_ACTION');
    }
    $cmdlogic->setType('action');
    $cmdlogic->setEqLogic_id($this->getId());
    $cmdlogic->setSubType('other');
    $cmdlogic->save();

    $cmdlogic = kroombaCmd::byEqLogicIdAndLogicalId($this->getId(),'start');
    if (!is_object($cmdlogic)) {
      $cmdlogic = new kroombaCmd();
      $cmdlogic->setName(__('Démarrer', __FILE__));
      $cmdlogic->setLogicalId('start');
      $cmdlogic->setIsVisible(1);
      $cmdlogic->setDisplay('generic_type', 'GENERIC_ACTION');
      $cmdlogic->setDisplay('icon', '<i class="fas fa-play"></i>');
    }
    $cmdlogic->setType('action');
    $cmdlogic->setEqLogic_id($this->getId());
    $cmdlogic->setSubType('other');
    $cmdlogic->save();

    $cmdlogic = kroombaCmd::byEqLogicIdAndLogicalId($this->getId(),'pause');
    if (!is_object($cmdlogic)) {
      $cmdlogic = new kroombaCmd();
      $cmdlogic->setName(__('Pause', __FILE__));
      $cmdlogic->setLogicalId('pause');
      $cmdlogic->setIsVisible(1);
      $cmdlogic->setDisplay('generic_type', 'GENERIC_ACTION');
      $cmdlogic->setDisplay('icon', '<i class="fas fa-pause"></i>');
    }
    $cmdlogic->setType('action');
    $cmdlogic->setEqLogic_id($this->getId());
    $cmdlogic->setSubType('other');
    $cmdlogic->save();

    $cmdlogic = kroombaCmd::byEqLogicIdAndLogicalId($this->getId(),'resume');
    if (!is_object($cmdlogic)) {
      $cmdlogic = new kroombaCmd();
      $cmdlogic->setName(__('Resume', __FILE__));
      $cmdlogic->setLogicalId('resume');
      $cmdlogic->setIsVisible(1);
      $cmdlogic->setDisplay('generic_type', 'GENERIC_ACTION');
      $cmdlogic->setDisplay('icon', '<i class="fas fa-step-forward"></i>');
    }
    $cmdlogic->setType('action');
    $cmdlogic->setEqLogic_id($this->getId());
    $cmdlogic->setSubType('other');
    $cmdlogic->save();

    $cmdlogic = kroombaCmd::byEqLogicIdAndLogicalId($this->getId(),'stop');
    if (!is_object($cmdlogic)) {
      $cmdlogic = new kroombaCmd();
      $cmdlogic->setName(__('Stop', __FILE__));
      $cmdlogic->setLogicalId('stop');
      $cmdlogic->setIsVisible(1);
      $cmdlogic->setDisplay('generic_type', 'GENERIC_ACTION');
      $cmdlogic->setDisplay('icon', '<i class="fas fa-stop"></i>');
    }
    $cmdlogic->setType('action');
    $cmdlogic->setEqLogic_id($this->getId());
    $cmdlogic->setSubType('other');
    $cmdlogic->save();

    $cmdlogic = kroombaCmd::byEqLogicIdAndLogicalId($this->getId(),'dock');
    if (!is_object($cmdlogic)) {
      $cmdlogic = new kroombaCmd();
      $cmdlogic->setName(__('Base', __FILE__));
      $cmdlogic->setLogicalId('dock');
      $cmdlogic->setIsVisible(1);
      $cmdlogic->setDisplay('generic_type', 'GENERIC_ACTION');
      $cmdlogic->setDisplay('icon', '<i class="fas fa-home"></i>');
    }
    $cmdlogic->setType('action');
    $cmdlogic->setEqLogic_id($this->getId());
    $cmdlogic->setSubType('other');
    $cmdlogic->save();

    $cmdlogic = kroombaCmd::byEqLogicIdAndLogicalId($this->getId(),'sys');
    if (is_object($cmdlogic)) {
      $cmdlogic->remove();
    }

    $this->mission();
  }

  public function mission() {
    $resource_path = realpath(dirname(__FILE__) . '/../../resources');
    $cmd = 'cd ' . $resource_path . ' && python roombaStatus.py "'
      . $this->getConfiguration('roomba_ip','') . '" "'
      . $this->getConfiguration('username','') . '" "'
      . $this->getConfiguration('password','') . '"';
    log::add('kroomba', 'debug', 'Mission command : ' . str_replace($this->getConfiguration('password',''),'****',$cmd));
    exec($cmd . ' 2>&1',$result1);
    // log::add('kroomba', 'debug', 'Mission result : ' . print_r($result1, true));

    $result = "{}";
    foreach ($result1 as $res) {
        json_decode($res);
        if (json_last_error() == JSON_ERROR_NONE)
          $result = $res;
    }

    log::add('kroomba', 'debug', 'Mission result : ' . $result);
    $tempo = json_decode($result, true);
    if (!isset($tempo['state'])) {
        log::add('kroomba', 'debug', 'Wrong answer : ' . print_r($tempo,true));
        return;
    }
    $changed = false;
    if (isset($tempo['state']['reported']['cleanMissionStatus']['phase'])) {
        $phase = $tempo['state']['reported']['cleanMissionStatus']['phase'];
        log::add('kroomba', 'debug', 'phase : ' . $phase);
        $changed = $this->checkAndUpdateCmd('status', $phase) || $changed;
    }
    if (isset($tempo['state']['reported']['batPct'])) {
        $battery = $tempo['state']['reported']['batPct'];
        log::add('kroomba', 'debug', 'battery : ' . $battery);
        $changed = $this->checkAndUpdateCmd('battery', $battery) || $changed;
        $this->batteryStatus($battery);
        $this->setStatus('battery', $battery);
    }
    if (isset($tempo['state']['reported']['bin']['full'])) {
        $binFull = $tempo['state']['reported']['bin']['full'];
        log::add('kroomba', 'debug', 'binfull : ' . $binfull);
        $changed = $this->checkAndUpdateCmd('binfull', $binfull) || $changed;
    }
    if ($changed == true) {
        $this->refreshWidget();
    }
    // log::add('kroomba', 'debug', 'getStatus Battery: ' . $this->getStatus('battery', -2));
  }

  public function send_command($cmd) {
    $resource_path = realpath(dirname(__FILE__) . '/../../resources');
    $cmd = 'cd ' . $resource_path . ' && python roombaCmd.py ' . $cmd . ' "'
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
    $return['log'] = 'kroomba_dep';
    $return['progress_file'] = '/tmp/kroomba_dep';

    if (self::dep_test_python_module('roomba') and self::dep_test_python_module('paho.mqtt')) {
      $return['state'] = 'ok';
    } else {
      $return['state'] = 'nok';
    }
    log::add('kroomba_dep','debug',"Dependencies status: " . $return['state']);
    return $return;
  }

  public static function dep_test_python_module($module) {
    $resource_path = realpath(dirname(__FILE__) . '/../../resources');
    exec('cd ' . $resource_path . ' && python -c "import ""' . $module . '""" > /dev/null 2>&1 ; echo $?',$return);
    if (count($return)>0)
    {
      $check = ( intval($return[0]) == 0 );
      return $check;
    } else {
      log::add('kroomba_dep','error',"Unable to check installation of python module $module");
      return false;
    }
  }

  public static function delTree($dir) {
   $files = array_diff(scandir($dir), array('.','..'));
    foreach ($files as $file) {
      (is_dir("$dir/$file")) ? self::delTree("$dir/$file") : unlink("$dir/$file");
    }
    return rmdir($dir);
  }

  public static function dependancy_install() {
    log::clear('kroomba_dep');
    $resource_path = realpath(dirname(__FILE__) . '/../../resources');
    log::add('kroomba_dep','debug','Installation des dépendances python');
    $roomba_module_path = $resource_path . '/roomba';
    //$roomba_module_path = realpath(dirname(__FILE__) . '/../../resources/roomba');
    if(file_exists($roomba_module_path) and !self::delTree($roomba_module_path))
    {
      log::add('kroomba_dep','error',"Deletion of $roomba_module_path failed");
    }
    $roomba_module_path = $resource_path . '/Roomba980-Python';
    //$roomba_module_path = realpath(dirname(__FILE__) . '/../../resources/roomba');
    if(file_exists($roomba_module_path) and !self::delTree($roomba_module_path))
    {
      log::add('kroomba_dep','error',"Deletion of $roomba_module_path failed");
    }
    passthru("sudo mkdir -p ${HOME}/.local  >> " . log::getPathToLog('kroomba_dep') . " 2>&1"  );
    passthru("sudo mkdir -p ${HOME}/.pip  >> " . log::getPathToLog('kroomba_dep') . " 2>&1"  );
    passthru("sudo chown ${USER}:`groups |cut -d\" \" -f1` ${HOME}/.local  >> " . log::getPathToLog('kroomba_dep') . " 2>&1"  );
    passthru("sudo chown ${USER}:`groups |cut -d\" \" -f1` ${HOME}/.pip  >> " . log::getPathToLog('kroomba_dep') . " 2>&1"  );
    passthru('cd /tmp');
    passthru(' ( pip uninstall -y six ; pip install --user six )  >> ' . log::getPathToLog('kroomba_dep') . ' 2>&1');
    passthru(' ( pip uninstall -y paho-mqtt ; pip install --user paho-mqtt )  >> ' . log::getPathToLog('kroomba_dep') . ' 2>&1');
    //passthru(' ( pip uninstall -y numpy ; pip install --user numpy )  >> ' . log::getPathToLog('kroomba_dep') . ' 2>&1');
    passthru(' cd ' . $resource_path . ' && git clone https://github.com/NickWaterton/Roomba980-Python.git >> ' . log::getPathToLog('kroomba_dep') . ' 2>&1');
    passthru(' mv "' . $resource_path . '/Roomba980-Python/roomba" "' . $resource_path . '/" >> ' . log::getPathToLog('kroomba_dep') . ' 2>&1');
    //passthru(' cd ' . $resource_path . ' && pip install --user roomba >> ' . log::getPathToLog('kroomba_dep') . ' 2>&1 &');
    //passthru("touch $roomba_module_path/__init__.py");
    self::delTree('/tmp/kroomba_dep');
  }
}

class kroombaCmd extends cmd {
	public static $_widgetPossibility = array('custom' => true);
    public function execute($_options = null) {
		if ($this->getType() == 'info') {
			return;
		}
		$eqLogic = $this->getEqLogic();
		if ($this->getLogicalId() == 'mission') {
			$eqLogic->mission();
		}
		$eqLogic = $this->getEqLogic();
		if ($this->getLogicalId() == 'start') {
			$eqLogic->send_command("start");
            sleep(4);
			$eqLogic->mission();
		}
		if ($this->getLogicalId() == 'pause') {
			$eqLogic->send_command("pause");
            sleep(4);
			$eqLogic->mission();
		}
		if ($this->getLogicalId() == 'resume') {
			$eqLogic->send_command("resume");
            sleep(4);
			$eqLogic->mission();
		}
		if ($this->getLogicalId() == 'stop') {
			$eqLogic->send_command("stop");
            sleep(4);
			$eqLogic->mission();
		}
		if ($this->getLogicalId() == 'dock') {
			$eqLogic->send_command("dock");
            sleep(8);
			$eqLogic->mission();
		}
  }
}

?>
