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

class ArtRequestFederalSubjectGetListProcessor extends modProcessor
{
	public function process()
	{
		$service = $this->modx->getService('artRequest');
		
		$federaldistrict = $this->getProperty('federaldistrict');
		
		$federalsubjects = array();
		foreach (array('ArtRequestPerson', 'ArtRequestOrganization') as $classKey) {
			$query = $this->modx->newQuery($classKey)->select(array($classKey . '.federalsubject'));
			$query->prepare()->execute();
			while (($value = $query->stmt->fetchColumn()) !== false) {
				$federalsubjects[] = $value;
			}
		}
		
		$result = array();
		foreach ($service->getFederalDistrictsSubjects() as $district => $subjects) {
			if (!$federaldistrict || $district == $federaldistrict) {
				foreach (array_intersect($subjects, $federalsubjects) as $subject) {
					$result[] = array('name' => $subject, 'district' => $district);
				}
			}
		}
		
		usort($result, create_function('$a,$b', 'return strcmp($a["name"],$b["name"]);'));
		
		return $this->outputArray($result);
	}
}

return 'ArtRequestFederalSubjectGetListProcessor';
