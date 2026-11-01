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

abstract class ArtRequestManagerController extends modManagerController
{
	protected $service;
	
	public function initialize()
	{
		$this->service = $this->modx->getService(
			'artRequest',
			'ArtRequest',
			$this->modx->getOption(
				'artrequest.core_path', null,
				$this->modx->getOption('core_path') . 'components/artrequest/'
			) . 'model/artrequest/'
		);
	}
	
	public function getLanguageTopics() {
		return array('artrequest:default');
	}
	
	public function checkPermissions()
	{
		return true;
	}
	
	public function loadCustomCssJs()
	{
		$this->addCss($this->service->getAssetsUrl() . 'mgr.css');
		$this->addJavascript($this->service->getAssetsUrl() . 'mgr.js');
		$this->addHtml('<script>ArtRequest.assets_url="' . $this->service->getAssetsUrl() . '";'
			. 'ArtRequest.uploads_url="' . $this->service->getAssetsUrl() . 'uploads/";'
			. 'ArtRequest.connector_url="' . $this->service->getAssetsUrl() . 'connector.php"</script>');
	}
	
	public function process(array $scriptProperties = array())
	{
	}
}
