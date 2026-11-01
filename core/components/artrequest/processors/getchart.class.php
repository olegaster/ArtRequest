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

class ArtRequestGetChartProcessor extends modProcessor
{
	public function process()
	{
		$service = $this->modx->getService('artRequest');

		$this->modx->lexicon->load('artrequest:default');

		$source = $this->getProperty('source');
		$federaldistrict = $this->getProperty('federaldistrict');
		$federalsubject = $this->getProperty('federalsubject');
		$createdonFrom = $this->getProperty('createdon_from');
		$createdonTo = $this->getProperty('createdon_to');

		$federaldistrictssubjects = $service->getFederalDistrictsSubjects();
		$requestreasons = $service->getRequestReasons();

		$federalsubjectsdistricts = array();
		foreach ($federaldistrictssubjects as $district => $subjects) {
			$federalsubjectsdistricts = array_merge($federalsubjectsdistricts,
				array_combine($subjects, array_fill(0, count($subjects), $district)));
		}

		$count = array();

		switch ($source) {
			case 'federalentities':
				$c = $this->modx->newQuery('ArtRequestRequest');
				$c->query['from']['joins'][] = array(
					'type' => 'JOIN',
					'table' => '(SELECT request, federalsubject FROM artrequest_persons UNION SELECT request, federalsubject FROM artrequest_orgpersons)',
					'alias' => 'Applicants',
					'conditions' => new xPDOQueryCondition(array('sql' => 'id = request')),
				);
				$c
					->select('id, federalsubject')
				;

				if ($federaldistrict) {
					$c->where(array('Applicants.federalsubject:IN' => $federaldistrictssubjects[$federaldistrict]));
				}

				if ($createdonFrom) {
					$c->where(array('createdon:>=' => date('Y-m-d', strtotime($createdonFrom))));
				}
				if ($createdonTo) {
					$c->where(array('createdon:<' => date('Y-m-d', strtotime($createdonTo) + 86400)));
				}

				$c->prepare()->execute();
				foreach ($c->stmt->fetchAll() as $row) {
					$category = $federaldistrict ? $row['federalsubject'] : $federalsubjectsdistricts[$row['federalsubject']];
					$count[$category][$row['id']] = true;
				}
				foreach ($count as $category => $data) {
					$count[$category] = count($data);
				}

				uksort($count, 'strnatcmp');
				break;

			case 'requestreason':
				$c = $this->modx->newQuery('ArtRequestPerson')
					->join('ArtRequestRequest', 'Request')
					->select('COUNT(DISTINCT request) AS data, reason')
					->groupby('reason')
				;

				if ($federalsubject) {
					$c->where(array('federalsubject' => $federalsubject));
				} elseif ($federaldistrict) {
					$c->where(array('federalsubject:IN' => $federaldistrictssubjects[$federaldistrict]));
				}

				if ($createdonFrom) {
					$c->where(array('Request.createdon:>=' => date('Y-m-d', strtotime($createdonFrom))));
				}
				if ($createdonTo) {
					$c->where(array('Request.createdon:<' => date('Y-m-d', strtotime($createdonTo) + 86400)));
				}

				$c->prepare()->execute();
				foreach ($c->stmt->fetchAll() as $row) {
					$category = $requestreasons[$row['reason']];
					$category2 = preg_replace('/(.{0,80}[^\s]*).*/', '$1', $category);
					$category = $category2 . ($category2 != $category ? '…' : '');

					$count[$category] = ($count[$category] ?: 0) + $row['data'];
				}
				
				$c = $this->modx->newQuery('ArtRequestOrgPerson')
					->join('ArtRequestRequest', 'Request')
					->select('COUNT(DISTINCT request) AS data, reason')
					->groupby('reason')
				;

				if ($federalsubject) {
					$c->where(array('federalsubject' => $federalsubject));
				} elseif ($federaldistrict) {
					$c->where(array('federalsubject:IN' => $federaldistrictssubjects[$federaldistrict]));
				}

				if ($createdonFrom) {
					$c->where(array('Request.createdon:>=' => date('Y-m-d', strtotime($createdonFrom))));
				}
				if ($createdonTo) {
					$c->where(array('Request.createdon:<' => date('Y-m-d', strtotime($createdonTo) + 86400)));
				}

				$c->prepare()->execute();
				foreach ($c->stmt->fetchAll() as $row) {
					$category = $requestreasons[$row['reason']];
					$category2 = preg_replace('/(.{0,80}[^\s]*).*/', '$1', $category);
					$category = $category2 . ($category2 != $category ? '…' : '');

					$count[$category] = ($count[$category] ?: 0) + $row['data'];
				}
				break;

			case 'applicants':
				$c = $this->modx->newQuery('ArtRequestRequest')
					->leftJoin('ArtRequestPerson', 'Persons')
					->leftJoin('ArtRequestOrgPerson', 'OrgPersons')
					->select('ArtRequestRequest.id')
				;

				if ($federalsubject) {
					$c->where(array(
						array('Persons.federalsubject' => $federalsubject),
						array('OrgPersons.federalsubject' => $federalsubject),
					), xPDOQuery::SQL_OR);
				} elseif ($federaldistrict) {
					$c->where(array(
						array('Persons.federalsubject:IN' => $federaldistrictssubjects[$federaldistrict]),
						array('OrgPersons.federalsubject:IN' => $federaldistrictssubjects[$federaldistrict]),
					), xPDOQuery::SQL_OR);
				}

				if ($createdonFrom) {
					$c->where(array('createdon:>=' => date('Y-m-d', strtotime($createdonFrom))));
				}
				if ($createdonTo) {
					$c->where(array('createdon:<' => date('Y-m-d', strtotime($createdonTo) + 86400)));
				}

				$c->prepare();
				$c = $this->modx->newQuery('ArtRequestRequest')
					->leftJoin('ArtRequestPerson', 'Persons')
					->leftJoin('ArtRequestOrgPerson', 'OrgPersons')
					->select('ArtRequestRequest.id, COUNT(Persons.id) AS persons, COUNT(OrgPersons.id) AS orgpersons')
					->groupby('ArtRequestRequest.id')
					->where('ArtRequestRequest.id IN (' . $c->toSQL() . ')')
				;

				$count = array(
					$this->modx->lexicon('artrequest.applicants_person') => 0,
					$this->modx->lexicon('artrequest.applicants_orgperson') => 0,
					$this->modx->lexicon('artrequest.applicants_orgpersons') => 0,
				);

				$c->prepare()->execute();
				foreach ($c->stmt->fetchAll() as $row) {
					if ($row['persons'] == 1 && !$row['orgpersons']) {
						$category = $this->modx->lexicon('artrequest.applicants_person');
					} elseif (!$row['persons'] && $row['orgpersons'] == 1) {
						$category = $this->modx->lexicon('artrequest.applicants_orgperson');
					} elseif (!$row['persons'] && $row['orgpersons'] > 1) {
						$category = $this->modx->lexicon('artrequest.applicants_orgpersons');
					} else {
						continue;
					}

					++$count[$category];
				}
				break;

			case 'applicant_ages':
				$c = $this->modx->newQuery('ArtRequestRequest')
					->join('ArtRequestPerson', 'Persons')
					->select('COUNT(1) AS data, YEAR(createdon) - YEAR(Persons.birthdate) - (RIGHT(createdon, 5) < RIGHT(Persons.birthdate, 5)) AS age')
					->groupby('age')
				;

				if ($federalsubject) {
					$c->where(array('Persons.federalsubject' => $federalsubject));
				} elseif ($federaldistrict) {
					$c->where(array('Persons.federalsubject:IN' => $federaldistrictssubjects[$federaldistrict]));
				}

				if ($createdonFrom) {
					$c->where(array('createdon:>=' => date('Y-m-d', strtotime($createdonFrom))));
				}
				if ($createdonTo) {
					$c->where(array('createdon:<' => date('Y-m-d', strtotime($createdonTo) + 86400)));
				}

				$c->prepare()->execute();
				foreach ($c->stmt->fetchAll() as $row) {
					$category = $row['age'];
					$count[$category] = ($count[$category] ?: 0) + $row['data'];
				}
				
				$c = $this->modx->newQuery('ArtRequestRequest')
					->join('ArtRequestOrgPerson', 'OrgPersons')
					->select('COUNT(1) AS data, YEAR(createdon) - YEAR(OrgPersons.birthdate) - (RIGHT(createdon, 5) < RIGHT(OrgPersons.birthdate, 5)) AS age')
					->groupby('age')
				;

				if ($federalsubject) {
					$c->where(array('OrgPersons.federalsubject' => $federalsubject));
				} elseif ($federaldistrict) {
					$c->where(array('OrgPersons.federalsubject:IN' => $federaldistrictssubjects[$federaldistrict]));
				}

				if ($createdonFrom) {
					$c->where(array('createdon:>=' => date('Y-m-d', strtotime($createdonFrom))));
				}
				if ($createdonTo) {
					$c->where(array('createdon:<' => date('Y-m-d', strtotime($createdonTo) + 86400)));
				}

				$c->prepare()->execute();
				foreach ($c->stmt->fetchAll() as $row) {
					$category = $row['age'];
					$count[$category] = ($count[$category] ?: 0) + $row['data'];
				}

				$countLow = 0;
				$countHigh = 0;
				$countAges = array();
				foreach ($count as $category => $data) {
					if ($category < 5) {
						$countLow += $data;
					} elseif ($category > 23) {
						$countHigh += $data;
					}
				}
				for ($i = 5; $i <= 23; ++$i) {
					if (!empty($count[$i])) {
						$x = array('год', 'года', 'лет');
						$x = array($x[2], $x[0], $x[1], $x[1], $x[1], $x[2], $x[2], $x[2], $x[2], $x[2]);
						$x = ($i < 11 || $i > 14) ? $x[$i % 10] : $x[0];

						$countAges[$i . ' ' . $x] = $count[$i];
					}
				}

				$count = array();
				if ($countLow) {
					$count['меньше 5 лет'] = $countLow;
				}
				$count = array_merge($count, $countAges);
				if ($countHigh) {
					$count['больше 23 лет'] = $countHigh;
				}
				break;

			case 'person_genders':
				$c = $this->modx->newQuery('ArtRequestRequest')
					->join('ArtRequestPerson', 'Persons')
					->select('COUNT(1) AS data, ismale')
					->groupby('ismale')
				;

				if ($federalsubject) {
					$c->where(array('Persons.federalsubject' => $federalsubject));
				} elseif ($federaldistrict) {
					$c->where(array('Persons.federalsubject:IN' => $federaldistrictssubjects[$federaldistrict]));
				}

				if ($createdonFrom) {
					$c->where(array('createdon:>=' => date('Y-m-d', strtotime($createdonFrom))));
				}
				if ($createdonTo) {
					$c->where(array('createdon:<' => date('Y-m-d', strtotime($createdonTo) + 86400)));
				}

				$c->prepare()->execute();
				foreach ($c->stmt->fetchAll() as $row) {
					$category = $row['ismale'] ? 'Мужской' : 'Женский';
					$count[$category] = ($count[$category] ?: 0) + $row['data'];
				}
				
				$c = $this->modx->newQuery('ArtRequestRequest')
					->join('ArtRequestOrgPerson', 'OrgPersons')
					->select('COUNT(1) AS data, ismale')
					->groupby('ismale')
				;

				if ($federalsubject) {
					$c->where(array('OrgPersons.federalsubject' => $federalsubject));
				} elseif ($federaldistrict) {
					$c->where(array('OrgPersons.federalsubject:IN' => $federaldistrictssubjects[$federaldistrict]));
				}

				if ($createdonFrom) {
					$c->where(array('createdon:>=' => date('Y-m-d', strtotime($createdonFrom))));
				}
				if ($createdonTo) {
					$c->where(array('createdon:<' => date('Y-m-d', strtotime($createdonTo) + 86400)));
				}

				$c->prepare()->execute();
				foreach ($c->stmt->fetchAll() as $row) {
					$category = $row['ismale'] ? 'Мужской' : 'Женский';
					$count[$category] = ($count[$category] ?: 0) + $row['data'];
				}
				break;
		}

		$result = array();
		foreach ($count as $category => $data) {
			$result[] = array('category' => $category, 'data' => $data);
		}

		return $this->outputArray($result, array_sum($count));
	}
}

return 'ArtRequestGetChartProcessor';
