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

require_once dirname(__FILE__) . '/../../../model/artrequest/artrequestmanagercontroller.class.php';

class ArtRequestRequestPrintManagerController extends ArtRequestManagerController
{
	public $loadHeader = false;
	public $loadFooter = false;
	
	public function process(array $scriptProperties = array())
	{
		$ids = $this->modx->getOption('id', $scriptProperties);
		if ($ids && !is_array($ids)) {
			$ids = array($ids);
		}
		
		$modAuth = $this->modx->user->getUserToken($this->modx->context->get('key'));
		$siteUrl = $this->modx->getOption('site_url', MODX_SITE_URL);
		$formatDate = $this->modx->getOption('manager_date_format');
		$formatDateTime = $formatDate . ' ' . $this->modx->getOption('manager_time_format');
		
		$federaldistricts = $this->service->getFederalDistrictsSubjects();
		$requestreasons = $this->service->getRequestReasons();
		
		$result = $this->modx->getCollectionGraph(
			'ArtRequestRequest',
			'{"Persons":{},"OrgPersons":{}}',
			$ids ? array('id:IN' => $ids) : null
		);
		
		$requests = array();
		foreach ($result as $record) {
			$request = array('persons' => array(), 'orgpersons' => array());
			
			foreach ($record->Persons as $subrecord) {
				$person = $subrecord->toArray();
				$person['gender'] = $person['ismale'] ? 'Мужской' : 'Женский';
				$person['birthdate'] = date($formatDate, strtotime($person['birthdate']));
				$person['reason'] = $requestreasons[$person['reason']];
				
				if ($person['photo']) {
					$person['photo'] = $siteUrl . ltrim($this->service->getAssetsUrl(), '/') . 'uploads/' . $person['photo'];
				}
				
				foreach ($federaldistricts as $district => $subjects) {
					if (in_array($person['federalsubject'], $subjects)) {
						$person['federaldistrict'] = $district;
						break;
					}
				}
				
				$request['persons'][] = $person;
			}
			
			foreach ($record->OrgPersons as $subrecord) {
				$orgperson = $subrecord->toArray();
				$orgperson['gender'] = $orgperson['ismale'] ? 'Мужской' : 'Женский';
				$orgperson['birthdate'] = date($formatDate, strtotime($orgperson['birthdate']));
				$orgperson['reason'] = $requestreasons[$orgperson['reason']];
				
				if ($orgperson['photo']) {
					$orgperson['photo'] = $siteUrl . ltrim($this->service->getAssetsUrl(), '/') . 'uploads/' . $orgperson['photo'];
				}
				
				foreach ($federaldistricts as $district => $subjects) {
					if (in_array($orgperson['federalsubject'], $subjects)) {
						$orgperson['federaldistrict'] = $district;
						break;
					}
				}
				
				$request['orgpersons'][] = $orgperson;
			}
			
			$request = array_merge($request, $record->toArray());
			$request['createdon'] = date($formatDateTime, strtotime($request['createdon']));
			
			$requests[] = $request;
		}
		
		return array('requests' => $requests);
	}
	
	public function getTemplateFile()
	{
		return 'request/print.tpl';
	}
	
	public function getPageTitle()
	{
		return $this->modx->lexicon('artrequest');
	}
}
