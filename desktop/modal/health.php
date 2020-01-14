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

if (!isConnect('admin')) {
	throw new Exception('401 Unauthorized');
}
$eqLogics = kroomba::byType('kroomba');
$cmd = 'sudo lsb_release -a';
exec($cmd . ' 2>&1',$result);
echo "<br><br>Version de Linux : " . implode(' ', $result);
$result = '';
$cmd = 'sudo python3 --version';
exec($cmd . ' 2>&1',$result);
echo "<br><br>Version de Python 3 : " . implode(' ', $result);
echo "<br><br>Version de Jeedom : " . jeedom::version();
echo "<br><br>";
?>

<table class="table table-condensed tablesorter" id="table_healthkroomba">
	<thead>
		<tr>
			<th>{{Nom}}</th>
			<th>{{IP}}</th>
			<th>{{Identifiant}}</th>
			<th>{{Mot de passe}}</th>
			<th>{{Status}}</th>
			<th>{{Bac plein}}</th>
			<th>{{Batterie}}</th>
		</tr>
	</thead>
	<tbody>
	 <?php
foreach ($eqLogics as $eqLogic) {
	echo '<tr><td><a href="' . $eqLogic->getLinkToConfiguration() . '" style="text-decoration: none;">' . $eqLogic->getHumanName(true) . '</a></td>';
    echo '<td><span class="label label-info" style="font-size : 1em;">' . $eqLogic->getConfiguration('roomba_ip') . '</span></td>';
    echo '<td><span class="label label-info" style="font-size : 1em;">' . $eqLogic->getConfiguration('username') . '</span></td>';
    echo '<td><span class="label label-info" style="font-size : 1em;">' . $eqLogic->getConfiguration('password') . '</span></td>';
    $roombacmd = $eqLogic->getCmd('info', 'status');
    $value = '';
    if (is_object($roombacmd)) {
         $value = $roombacmd->execCmd();
    }
    echo '<td><span class="label label-info" style="font-size : 1em;">' . $value . '</span></td>';
    $roombacmd = $eqLogic->getCmd('info', 'binfull');
    $value = '';
    if (is_object($roombacmd)) {
         $value = $roombacmd->execCmd() ? 'Oui' : 'Non';
    }
    echo '<td><span class="label label-info" style="font-size : 1em;">' . $value . '</span></td>';
    $roombacmd = $eqLogic->getCmd('info', 'battery');
    $value = '';
    if (is_object($roombacmd)) {
         $value = $roombacmd->execCmd();
    }
    echo '<td><span class="label label-info" style="font-size : 1em;">' . $value . '%</span></td>';
echo '</tr>';
}
?>
	</tbody>
</table>
