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

require_once __DIR__ . '/../../vendor/autoload.php';

class kroomba extends eqLogic {
    use MipsEqLogicTrait;

    private static $_MQTT2 = 'mqtt2';

    protected static function getSocketPort() {
        return config::byKey('socketport', __CLASS__, 55072);;
    }

    public static function templateWidget() {
        $return = array('info' => array('string' => array()));
        $return['info']['string']['state'] = array(
            'template' => 'tmplmultistate',
            'replace' => array("#_desktop_width_#" => "80", "#_mobile_width_#" => "50"),
            'test' => array(
                array(
                    'operation' => "#value# == 'Charging'", 'state_light' => "<img src='plugins/kroomba/core/img/kroomba_charge.png' title ='" . __('En charge', __FILE__) . "'>",
                    'state_dark' => "<img src='plugins/kroomba/core/img/kroomba_charge.png' title ='" . __('En charge', __FILE__) . "'>"
                ),
                array(
                    'operation' => "#value# == 'home' || #value# == 'User Docking'", 'state_light' => "<img src='plugins/kroomba/core/img/kroomba_home.png' title ='" . __('Retour à la base', __FILE__) . "'>",
                    'state_dark' => "<img src='plugins/kroomba/core/img/kroomba_home.png' title ='" . __('Retour à la base', __FILE__) . "'>"
                ),
                array(
                    'operation' => "#value# == 'Running'", 'state_light' => "<img src='plugins/kroomba/core/img/kroomba_run.png' title ='" . __('Nettoyage', __FILE__) . "'>",
                    'state_dark' => "<img src='plugins/kroomba/core/img/kroomba_run.png' title ='" . __('Nettoyage', __FILE__) . "'>"
                ),
                array(
                    'operation' => "#value# == 'Stopped'", 'state_light' => "<img src='plugins/kroomba/core/img/kroomba_stop.png' title ='" . __('Arrété', __FILE__) . "'>",
                    'state_dark' => "<img src='plugins/kroomba/core/img/kroomba_stop.png' title ='" . __('Arrété', __FILE__) . "'>"
                ),
                array(
                    'operation' => "#value# == 'stuck'", 'state_light' => "<img src='plugins/kroomba/core/img/kroomba_stuck.png' title ='" . __('Bloqué', __FILE__) . "'>",
                    'state_dark' => "<img src='plugins/kroomba/core/img/kroomba_stuck.png' title ='" . __('Bloqué', __FILE__) . "'>"
                ),
                array(
                    'operation' => "#value# == 'hmPostMsn'", 'state_light' => "<img src='plugins/kroomba/core/img/kroomba_hmPostMsn.png' title ='" . __('Tâche achevée', __FILE__) . "'>",
                    'state_dark' => "<img src='plugins/kroomba/core/img/kroomba_hmPostMsn.png' title ='" . __('Tâche achevée', __FILE__) . "'>"
                ),
                array(
                    'operation' => "#value# == 'hmMidMsn'", 'state_light' => "<img src='plugins/kroomba/core/img/kroomba_hmMidMsn.png' title ='" . __('Recharge nécessaire', __FILE__) . "'>",
                    'state_dark' => "<img src='plugins/kroomba/core/img/kroomba_hmMidMsn.png' title ='" . __('Recharge nécessaire', __FILE__) . "'>"
                ),
                array(
                    'operation' => "#value# == 'unknown'|| #value# == ''", 'state_light' => "<img src='plugins/kroomba/core/img/kroomba_unknown.png' title ='" . __('Inconnu', __FILE__) . "'>",
                    'state_dark' => "<img src='plugins/kroomba/core/img/kroomba_unknown.png' title ='" . __('Inconnu', __FILE__) . "'>"
                )
            )
        );
        $return['info']['numeric']['battery'] = array(
            'template' => 'tmplmultistate',
            'test' => array(
                array('operation' => "#value# >75", 'state_light' => '<i class="icon icon_green jeedom-batterie3"></i>', 'state_dark' => '<i class="icon icon_green jeedom-batterie3"></i>'),
                array('operation' => "#value# <= 75 && #value# > 50", 'state_light' => '<i class="icon icon_green jeedom-batterie2"></i>', 'state_dark' => '<i class="icon icon_green jeedom-batterie2"></i>'),
                array('operation' => "#value# <= 50 && #value# > 25", 'state_light' => '<i class="icon icon_yellow jeedom-batterie1"></i>', 'state_dark' => '<i class="icon icon_yellow jeedom-batterie1"></i>'),
                array('operation' => "#value# <= 25", 'state_light' => '<i class="icon icon_red jeedom-batterie0"></i>', 'state_dark' => '<i class="icon icon_red jeedom-batterie0"></i>')
            )
        );
        $return['info']['binary']['binfull'] = array(
            'template' => 'tmplicon',
            'replace' => array('#_icon_on_#' => '<i class=\'icon icon_red maison-poubelle\'></i>', '#_icon_off_#' => '<i class=\'icon icon_green fas fa-check\'></i>')
        );
        return $return;
    }

    private static function getTopicPrefix() {
        return config::byKey('topic_prefix', __CLASS__, 'kroomba');
    }

    public static function deamon_info() {
        $return = array();
        $return['log'] = __CLASS__;
        $return['launchable'] = 'ok';
        $return['state'] = 'nok';
        $pid_file = jeedom::getTmpFolder(__CLASS__) . '/daemon.pid';
        if (file_exists($pid_file)) {
            if (@posix_getsid(trim(file_get_contents($pid_file)))) {
                $return['state'] = 'ok';
            } else {
                shell_exec(system::getCmdSudo() . 'rm -rf ' . $pid_file . ' 2>&1 > /dev/null');
            }
        }
        if (!class_exists(self::$_MQTT2)) {
            $return['launchable'] = 'nok';
            $return['launchable_message'] = __('Le plugin mqtt2 n\'est pas installé', __FILE__);
        } else {
            if (self::$_MQTT2::deamon_info()['state'] != 'ok') {
                $return['launchable'] = 'nok';
                $return['launchable_message'] = __('Le démon mqtt2 n\'est pas demarré', __FILE__);
            }
        }
        return $return;
    }

    public static function deamon_start() {
        self::$_MQTT2::addPluginTopic(__CLASS__, self::getTopicPrefix());
        self::deamon_stop();
        $deamon_info = self::deamon_info();
        if ($deamon_info['launchable'] != 'ok') {
            throw new Exception(__('Veuillez vérifier la configuration', __FILE__));
        }
        message::removeAll(__CLASS__, 'kroomba_no_robot');

        $mqttInfos = self::$_MQTT2::getFormatedInfos();

        $path = realpath(dirname(__FILE__) . '/../../resources/kroomba');
        $cmd = "/usr/bin/python3 {$path}/kroombad.py";
        $cmd .= ' --loglevel ' . log::convertLogLevel(log::getLogLevel(__CLASS__));
        $cmd .= ' --host ' . $mqttInfos['ip'];
        $cmd .= ' --port ' . $mqttInfos['port'];
        $cmd .= ' --user "' . trim(str_replace('"', '\"', $mqttInfos['user'])) . '"';
        $cmd .= ' --password "' . trim(str_replace('"', '\"', $mqttInfos['password'])) . '"';
        $cmd .= ' --topic_prefix "' . trim(str_replace('"', '\"', self::getTopicPrefix())) . '"';
        $cmd .= ' --socketport ' . self::getSocketPort();
        $cmd .= ' --callback ' . network::getNetworkAccess('internal', 'proto:127.0.0.1:port:comp') . '/plugins/kroomba/core/php/jeekroomba.php';
        $cmd .= ' --apikey ' . jeedom::getApiKey(__CLASS__);
        $cmd .= ' --pid ' . jeedom::getTmpFolder(__CLASS__) . '/daemon.pid';
        log::add(__CLASS__, 'info', 'Lancement démon');
        $result = exec($cmd . ' >> ' . log::getPathToLog(__CLASS__ . '_daemon') . ' 2>&1 &');
        $i = 0;
        while ($i < 10) {
            $deamon_info = self::deamon_info();
            if ($deamon_info['state'] == 'ok') {
                break;
            }
            sleep(1);
            $i++;
        }
        if ($i >= 10) {
            log::add(__CLASS__, 'error', __('Impossible de lancer le démon', __FILE__), 'unableStartDeamon');
            return false;
        }
        message::removeAll(__CLASS__, 'unableStartDeamon');
    }

    public static function deamon_stop() {
        $pid_file = jeedom::getTmpFolder(__CLASS__) . '/daemon.pid';
        if (file_exists($pid_file)) {
            $pid = intval(trim(file_get_contents($pid_file)));
            system::kill($pid);
        }
        sleep(1);
        system::kill('roombad.py');
        // system::fuserk(config::byKey('socketport', __CLASS__));
        sleep(1);
    }

    public static function discoverRobots($login, $password) {
        if (empty($login) || empty($password)) {
            throw new Exception(__('Vous devez entrer votre adresse email et votre mot de passe', __FILE__));
        }
        self::sendToDaemon(array(
            'action' => 'discover',
            'login' => $login,
            'password' => $password
        ));
    }

    private static function getRoomba($name) {
        $eqLogic = eqLogic::byLogicalId($name, __CLASS__);
        if (!is_object($eqLogic)) {
            log::add(__CLASS__, 'info', "Creating new roomba with logicalId={$name}");
            $eqLogic = new self();
            $eqLogic->setLogicalId($name);
            $eqLogic->setEqType_name(__CLASS__);
            $eqLogic->setIsEnable(1);
            $eqLogic->setIsVisible(1);
            $eqLogic->setName($name);

            $eqLogic->save();
        }
        return $eqLogic;
    }

    public static function handleMqttMessage($_message) {
        log::add(__CLASS__, 'debug', 'handle Mqtt Message:' . json_encode($_message));
        if (isset($_message[self::getTopicPrefix()]) && isset($_message[self::getTopicPrefix()]['feedback'])) {
            $message = $_message[self::getTopicPrefix()]['feedback'];
            foreach ($message as $key => $value) {
                log::add(__CLASS__, 'debug', "Message for roomba: {$key}");
                $roomba = self::getRoomba($key);
                $changed = false;
                foreach ($value as $key => $value) {

                    switch ($key) {
                            // case 'mac':
                            //     $roomba->setConfiguration($key, $value);
                            //     $roomba->save(true);
                            //     break;
                        case 'state':
                            log::add(__CLASS__, 'debug', "{$key}={$value}");
                            $changed = $roomba->checkAndUpdateCmd('status', $value) || $changed;
                            break;
                        case 'batInfo_mName':
                            if ($roomba->getConfiguration('battery_type', 'undefined') == 'undefined') {
                                $roomba->setConfiguration('battery_type', $value);
                                $roomba->save(true);
                            }
                            break;
                        case 'batPct':
                            $changed = $roomba->checkAndUpdateCmd('battery', $value) || $changed;
                            $roomba->batteryStatus($value);
                            break;
                        case 'bin_full':
                            $changed = $roomba->checkAndUpdateCmd('binfull', $value == 'False' ? 0 : 1) || $changed;
                            break;
                        default:
                            log::add(__CLASS__, 'debug', "Message sub-topic: {$key}={$value}");
                            break;
                    }
                }
            }
        } else {
            log::add(__CLASS__, 'debug', 'Message is not for kroomba');
            return;
        }
        foreach ($message as $key => $value) {
        }
    }

    public function postInsert() {
        $this->createCommandsFromConfigFile(__DIR__ . '/../config/commands.json', 'commands');
    }

    public function send_command($cmd) {
        self::$_MQTT2::publish(kroomba::getTopicPrefix() . '/command/' . $this->getLogicalId(), $cmd);
    }
}

class kroombaCmd extends cmd {
    public function execute($_options = null) {
        $eqLogic = $this->getEqLogic();
        $eqLogic->send_command($this->getLogicalId());
    }
}
