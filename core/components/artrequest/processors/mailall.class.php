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

class ArtRequestMailAllProcessor extends modProcessor
{
	public $classKey = 'ArtRequestRequest';

	public function checkPermissions()
	{
		return $this->modx->hasPermission('view');
	}

	public function process()
	{
		$service = $this->modx->getService('artRequest');
		
		$to = $this->getProperty('to');
		
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
		);
		
		$c = $this->modx->newQuery($this->classKey);
		$c = $service->prepareRequestsQuery($c, $params);
		
		$ids = array();
		foreach ($this->modx->getIterator($this->classKey, $c) as $object) {
			$ids[] = $object->id;
		}
		
		$service->mailRequestsAsTable($ids, $to);
		
		return $this->success('', array());
	}
}

return 'ArtRequestMailAllProcessor';
