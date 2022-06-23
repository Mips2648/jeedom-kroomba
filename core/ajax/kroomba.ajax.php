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

function myDiscover() {
  log::add('kroomba', 'debug', 'myDiscover');
  $result = [];
  $resource_path = realpath(dirname(__FILE__) . '/../../resources');
  $cmd = 'cd ' . $resource_path . ' && python3 discover.py';
  log::add('kroomba', 'debug', 'Discover');
  exec($cmd . ' 2>&1', $roombas);
  log::add('kroomba', 'debug', 'Discover Results :' . implode($roombas));

  foreach ($roombas as $roomba) {
    log::add('kroomba', 'debug', 'Discover Result :' . $roomba);
    preg_match('/IP:(\d+\.\d+\.\d+\.\d+),blid:(\w+)/', $roomba, $matches);
    log::add('kroomba', 'debug', 'Discover ip :' . $matches[1]);
    log::add('kroomba', 'debug', 'Discover blid :' . $matches[2]);
    $result[] = array(
      "ip"   =>  $matches[1],
      "blid" =>  $matches[2]
    );
  }
  return $result;
}

function getPassword($ip, $blid) {
  log::add('kroomba', 'debug', 'getPassword ' . $ip . ' ' . $blid);
  $resource_path = realpath(dirname(__FILE__) . '/../../resources');
  $cmd = 'cd ' . $resource_path . ' && python3 getPassword.py ' . $ip;

  log::add('kroomba', 'debug', 'getPassword command : ' . $cmd);
  exec($cmd . ' 2>&1', $result);
  $password = '';
  foreach ($result as $line) {
    log::add('kroomba', 'debug', 'getPassword result :' . $line);
    if (preg_match('/Password (.+)/', $line, $matches) == 1) {
      $password = $matches[1];
      log::add('kroomba', 'debug', 'getPassword found :' . $password);
    }
  }
  if ($password == '') {
    log::add('kroomba', 'error', 'getPassword password not found.');
    return false;
  }
  return $password;
}

try {
  require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
  include_file('core', 'authentification', 'php');

  if (!isConnect('admin')) {
    throw new Exception(__('401 - Accès non autorisé', __FILE__));
  }

  log::add('kroomba', 'debug', 'Action : ' . init('action'));

  if (init('action') == 'getPassword') {
    $kroomba = eqLogic::byLogicalId('kroomba_' . init('blid'), 'kroomba', $_multiple = false);
    if (!is_object($kroomba)) {
      ajax::error('Unknown kroomba: kroomba_' . init('blid'), 400);
    } else {
      $password = getPassword(
        $kroomba->getConfiguration("roomba_ip", ""),
        $kroomba->getConfiguration("username", "")
      );
      if ($password) {
        ajax::success($password);
      } else {
        ajax::error(__('Aucun signal du Roomba. Vérifiez les instructions et adresse IP.', __FILE__), 401);
      }
    }
  }

  if (init('action') == 'discover') {
    $roombas = myDiscover();
    foreach ($roombas as $roomba) {
      $kroomba = eqLogic::byLogicalId('kroomba_' . $roomba['blid'], 'kroomba', $_multiple = false);
      if (!is_object($kroomba)) {
        $kroomba = new kroomba();
        $kroomba->setName('Kroomba_' . $roomba['blid']);
        $kroomba->setLogicalId('kroomba_' . $roomba['blid']);
        $kroomba->setEqType_name('kroomba');
      }
      $kroomba->setConfiguration("roomba_ip", $roomba['ip']);
      $kroomba->setConfiguration("username", $roomba['blid']);
      $kroomba->setConfiguration('battery_type', 'undefined');
      $kroomba->save();
    }
    ajax::success();
  }

  throw new Exception(__('Aucune methode correspondante à : ', __FILE__) . init('action'));
  /*     * *********Catch exeption*************** */
} catch (Exception $e) {
  ajax::error(displayException($e), $e->getCode());
}
