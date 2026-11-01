<?php
/**
 * Art Request System for MODX Revolution
 * Copyright © 2014, 2018 Aleksandr Romanov <x0rhamster@ya.ru>
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation; either version 2 of the License,
 * or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

$configPath = './';
while (!file_exists($configPath . 'config.core.php') && realpath($configPath) != realpath('/')) {
	$configPath = '../' . $configPath;
}

require_once $configPath . 'config.core.php';
require_once MODX_CORE_PATH . 'config/' . MODX_CONFIG_KEY . '.inc.php';
require_once MODX_CONNECTORS_PATH . 'index.php';

$artRequestCorePath = $modx->getOption(
	'artrequest.core_path', null,
	$modx->getOption('core_path') . 'components/artrequest/'
);

// preload service for later shortcut access
$modx->getService('artRequest', 'ArtRequest', $artRequestCorePath . 'model/artrequest/');

$modx->request->handleRequest(array('processors_path' => $artRequestCorePath . 'processors/'));
