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

class ArtRequestGetListProcessor extends modObjectGetListProcessor
{
	public $classKey = 'ArtRequestRequest';
	public $defaultSortField = 'createdon';
	public $defaultSortDirection = 'ASC';
	
	public $languageTopics = array('artrequest:default');
	public $objectType = 'artrequest.request';
	
	public function prepareQueryBeforeCount(xPDOQuery $c)
	{
		$service = $this->modx->getService('artRequest');
		
		$params = array(
			'hidden' => $this->getProperty('hidden'),
			'federalsubject' => $this->getProperty('federalsubject'),
			'federaldistrict' => $this->getProperty('federaldistrict'),
			'requestreason' => $this->getProperty('requestreason'),
			'createdonFrom' => $this->getProperty('createdon_from'),
			'createdonTo' => $this->getProperty('createdon_to'),
			'applicants' => $this->getProperty('applicants'),
			'applicantName' => $this->getProperty('applicant_name'),
			'personIsMale' => $this->getProperty('person_ismale'),
			'requestId' => $this->getProperty('id'),
		);
		
		return $service->prepareRequestsQuery($c, $params);
	}
	
	public function prepareRow(xPDOObject $object)
	{
		$result = $object->toArray();
		
		$result['createdon'] = str_replace(' ', 'T', $result['createdon']) . date('O');
		
		$result['person_names'] = array();
		foreach ($object->getMany('Persons') as $person) {
			$result['person_names'][] = $person->name;
		}
		
		$result['orgperson_names'] = array();
		foreach ($object->getMany('OrgPersons') as $orgperson) {
			$result['orgperson_names'][] = $orgperson->name;
		}
		
		usort($result['person_names'], 'strnatcmp');
		usort($result['orgperson_names'], 'strnatcmp');
		
		return $result;
	}
}

return 'ArtRequestGetListProcessor';
