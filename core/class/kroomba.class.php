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

  public static function cron() {
		foreach (self::byType('kroomba') as $kroomba) {
      $cron_isEnable = $kroomba->getConfiguration('cron_isEnable',0);
			$autorefresh = $kroomba->getConfiguration('autorefresh','');
			$password = $kroomba->getConfiguration('password','');
			if ($kroomba->getIsEnable() == 1 && $cron_isEnable == 1 && $password != '' && $autorefresh != '') {
				try {
					$c = new Cron\CronExpression($autorefresh, new Cron\FieldFactory);
					if ($c->isDue()) {
						try {
							$kroomba->mission();
              $kroomba->refreshWidget();
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
    log::add('kroomba', 'debug', 'postSaveBegin:getStatus Battery: ' . $this->getStatus('battery', -2));
    $cmdlogic = kroombaCmd::byEqLogicIdAndLogicalId($this->getId(),'battery');
    if (!is_object($cmdlogic)) {
      $cmdlogic = new kroombaCmd();
      $cmdlogic->setName(__('Battery', __FILE__));
      $cmdlogic->setEqLogic_id($this->getId());
      $cmdlogic->setLogicalId('battery');
		  $cmdlogic->setDisplay('generic_type', 'BATTERY');
    }
    $cmdlogic->setType('info');
    $cmdlogic->setSubType('numeric');
    $cmdlogic->save();

    $cmdlogic = kroombaCmd::byEqLogicIdAndLogicalId($this->getId(),'status');
    if (!is_object($cmdlogic)) {
      $cmdlogic = new kroombaCmd();
      $cmdlogic->setName(__('Status', __FILE__));
      $cmdlogic->setEqLogic_id($this->getId());
      $cmdlogic->setLogicalId('status');
		  $cmdlogic->setDisplay('generic_type', 'MODE_STATE');
    }
    $cmdlogic->setType('info');
    $cmdlogic->setSubType('string');
    $cmdlogic->save();

    $cmdlogic = kroombaCmd::byEqLogicIdAndLogicalId($this->getId(),'binFull');
    if (!is_object($cmdlogic)) {
      $cmdlogic = new kroombaCmd();
      $cmdlogic->setName(__('binFull', __FILE__));
      $cmdlogic->setEqLogic_id($this->getId());
      $cmdlogic->setLogicalId('binFull');
		  $cmdlogic->setDisplay('generic_type', 'GENERIC_INFO');
    }
    $cmdlogic->setType('info');
    $cmdlogic->setSubType('binary');
    $cmdlogic->save();

    $cmdlogic = kroombaCmd::byEqLogicIdAndLogicalId($this->getId(),'mission');
    if (!is_object($cmdlogic)) {
      $cmdlogic = new kroombaCmd();
      $cmdlogic->setName(__('Mission', __FILE__));
      $cmdlogic->setLogicalId('mission');
      $cmdlogic->setIsVisible(1);
		  $cmdlogic->setDisplay('generic_type', 'GENERIC_ACTION');
    }
    $cmdlogic->setType('action');
    $cmdlogic->setEqLogic_id($this->getId());
    $cmdlogic->setSubType('other');
    $cmdlogic->save();

    $cmdlogic = kroombaCmd::byEqLogicIdAndLogicalId($this->getId(),'start');
    if (!is_object($cmdlogic)) {
      $cmdlogic = new kroombaCmd();
      $cmdlogic->setName(__('Start', __FILE__));
      $cmdlogic->setLogicalId('start');
      $cmdlogic->setIsVisible(1);
		  $cmdlogic->setDisplay('generic_type', 'GENERIC_ACTION');
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
    }
    $cmdlogic->setType('action');
    $cmdlogic->setEqLogic_id($this->getId());
    $cmdlogic->setSubType('other');
    $cmdlogic->save();

    $cmdlogic = kroombaCmd::byEqLogicIdAndLogicalId($this->getId(),'dock');
    if (!is_object($cmdlogic)) {
      $cmdlogic = new kroombaCmd();
      $cmdlogic->setName(__('Dock', __FILE__));
      $cmdlogic->setLogicalId('dock');
      $cmdlogic->setIsVisible(1);
		  $cmdlogic->setDisplay('generic_type', 'GENERIC_ACTION');
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
    log::add('kroomba', 'debug', 'postSaveEnd:getStatus Battery: ' . $this->getStatus('battery', -2));
  }

  public function mission() {
    $resource_path = realpath(dirname(__FILE__) . '/../../resources');
    $cmd = 'cd ' . $resource_path . ' && python roombaStatus.py "'
      . $this->getConfiguration('roomba_ip','') . '" "'
      . $this->getConfiguration('username','') . '" "'
      . $this->getConfiguration('password','') . '"';
    log::add('kroomba', 'debug', $cmd . ' : ' . str_replace($this->getConfiguration('password',''),'****',$cmd));
    exec($cmd . ' 2>&1',$result1);
    log::add('kroomba', 'debug', 'Result : ' . implode($result1));

    $result = "{}";
    foreach ($result1 as $res)
    {
        json_decode($res);
        if (json_last_error() == JSON_ERROR_NONE)
          $result = $res;
    }

    $cmdlogic = kroombaCmd::byEqLogicIdAndLogicalId($this->getId(),'mission');
    if (array_key_exists ('state',json_decode($result,true))) {
      if (is_array ($result)) { $cmdlogic->setConfiguration('value', implode($result));}
      else {$cmdlogic->setConfiguration('value', $result);}
      $cmdlogic->save();
      if (is_array ($result)) {$cmdlogic->event(implode($result));}
      else {$cmdlogic->event($result);}
      
      $phase = json_decode($result,true)['state']['reported']['cleanMissionStatus']['phase'];
      $cmdlogic = kroombaCmd::byEqLogicIdAndLogicalId($this->getId(),'status');
      $cmdlogic->setConfiguration('value', $phase);
      $cmdlogic->save();
      $cmdlogic->event($phase);

      $battery = json_decode($result,true)['state']['reported']['batPct'];
      $this->batteryStatus($battery);
      $this->setStatus('battery', $battery);
      $cmdlogic = kroombaCmd::byEqLogicIdAndLogicalId($this->getId(),'battery');
      $cmdlogic->setConfiguration('value', $battery);
      $cmdlogic->save();
      $cmdlogic->event($battery);

      $cmdlogic = kroombaCmd::byEqLogicIdAndLogicalId($this->getId(),'binFull');
      if (is_object($cmdlogic)) {
        $binFull = json_decode($result,true)['state']['reported']['bin']['full'];
        $cmdlogic->setConfiguration('value', $binFull);
        $cmdlogic->save();
        $cmdlogic->event($binFull);
      }

      log::add('kroomba', 'debug', 'getStatus Battery: ' . $this->getStatus('battery', -2));
    } else {
      log::add('kroomba', 'debug', 'Wrong answer: ' . print_r(json_decode($result,true),true));
    }
    $this->toHtml('mobile');
    $this->toHtml('dashboard');
    $this->refreshWidget();
    return ;
  }

  public function send_command($cmd) {
    $resource_path = realpath(dirname(__FILE__) . '/../../resources');
    $cmd = 'cd ' . $resource_path . ' && python roombaCmd.py ' . $cmd . ' "'
      . $this->getConfiguration('roomba_ip','') . '" "'
      . $this->getConfiguration('username','') . '" "'
      . $this->getConfiguration('password','') . '"';
    log::add('kroomba', 'debug', $cmd . ' : ' . str_replace($this->getConfiguration('password',''),'****',$cmd));
    exec($cmd . ' 2>&1',$result);
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

  public function toHtml($_version = 'dashboard') {
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
    $img_path = "plugins/kroomba/doc/images/kroomba_";

    $statusCmd = kroombaCmd::byEqLogicIdAndLogicalId($this->getId(),'status');
    $status = $statusCmd->getConfiguration("value","unknown");
    $replace['#kroomba_ip#'] = $this->getConfiguration('roomba_ip','');
    $replace['#phase#'] = $status;
    switch($status)
    {
      case 'charge':
        $replace['#str_phase#'] = __('Charging', __FILE__);
        break;

      case 'home':
      case 'hmUsrDock':
        $replace['#str_phase#'] = __('Returning to dock', __FILE__);
        break;

      case 'run':
        $replace['#str_phase#'] = __('Cleaning', __FILE__);
        break;

      case 'stop':
        $replace['#str_phase#'] = __('Stopped', __FILE__);
        break;

      case 'stuck':
        $replace['#str_phase#'] = __('Stucked !?!', __FILE__);
        break;

      case 'unknown':
      default:
        $replace['#str_phase#'] = __('Unknown: ', __FILE__).$status;
        $status = 'unknown';
        break;
    }
    $replace['#img_phase#'] = $img_path . $status . '.png';

    $cmdlogic = kroombaCmd::byEqLogicIdAndLogicalId($this->getId(),'mission');
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
  		$eqLogic->mission();
		}
		if ($this->getLogicalId() == 'pause') {
			$eqLogic->send_command("pause");
  		$eqLogic->mission();
		}
		if ($this->getLogicalId() == 'resume') {
			$eqLogic->send_command("resume");
  		$eqLogic->mission();
		}
		if ($this->getLogicalId() == 'stop') {
			$eqLogic->send_command("stop");
  		$eqLogic->mission();
		}
		if ($this->getLogicalId() == 'dock') {
			$eqLogic->send_command("dock");
  		$eqLogic->mission();
		}
  }
}

?>
