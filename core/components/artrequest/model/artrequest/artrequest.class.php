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

class ArtRequest
{
	private $modx;
	private $pdoTools;

	private $corePath;
	private $assetsPath;
	private $assetsUrl;

	private $newDirMode;
	private $newFileMode;

	function __construct(modX $modx)
	{
		$this->modx = $modx;

		$this->pdoTools = $this->modx->getService('pdoTools');

		$this->corePath = $this->_getPathOption('core_path');
		$this->assetsPath = $this->_getPathOption('assets_path');
		$this->assetsUrl = $this->_getPathOption('assets_url');

		$this->modx->addPackage('artrequest', $this->corePath . 'model/');

		$this->newDirMode = $this->modx->getOption('new_folder_permissions', null, '0755');
		$this->newFileMode = $this->modx->getOption('new_file_permissions', null, '0644');
	}

    function _log($data) {
        file_put_contents($this->corePath . '/orders.log', print_r([
    		'data' => $data,
    		'time' => date('Y-m-d H:i:s'),
    	], true), FILE_APPEND);
    }
	function createRequest(array $data)
	{
		$ip = $this->modx->request->getClientIp();
		$ip = $ip['ip'];

		$email = $data['clientemail'];
		if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$email = '';
		}

		$request = $this->modx->newObject('ArtRequestRequest', array(
			'createdon' => strftime('%Y-%m-%d %H:%M:%S'),
			'clientemail' => $email,
			'clientip' => $ip,
			'hidden' => false,
			'contacts' => $data['contacts'],
		));
		
		$this->_log($data);

		$applicantsCount = 0;

		foreach ($data['persons'] as $subdata) {
			if ($subdata['name'] == '') {
				continue;
			}

			$request->addMany($this->modx->newObject('ArtRequestPerson', array(
				'name' => $subdata['name'],
				'ismale' => ($subdata['ismale'] == true),
				'birthdate' => $subdata['birthdate'],
				'federalsubject' => $subdata['federalsubject'],
				'city' => $subdata['city'],
				'address' => $subdata['address'],
				'postal' => $subdata['postal'],
				'reason' => $subdata['reason'],
				'awards' => $subdata['awards'],
				'contacts' => $subdata['contacts'],
				'photo' => $subdata['photo'],
				'work1' => $subdata['work1'],
				'work1name' => $subdata['work1name'],
				'work1about' => $subdata['work1about'],
				'work1framing' => $subdata['work1framing'],
				'work2' => $subdata['work2'],
				'work2name' => $subdata['work2name'],
				'work2about' => $subdata['work2about'],
				'work2framing' => $subdata['work2framing'],
				'work3' => $subdata['work3'],
				'work3name' => $subdata['work3name'],
				'work3about' => $subdata['work3about'],
				'work3framing' => $subdata['work3framing'],
			)));

			++$applicantsCount;
		}

		foreach ($data['orgpersons'] as $subdata) {
			if ($subdata['name'] == '') {
				continue;
			}

			$request->addMany($this->modx->newObject('ArtRequestOrgPerson', array(
				'name' => $subdata['name'],
				'ismale' => ($subdata['ismale'] == true),
				'birthdate' => $subdata['birthdate'],
				'federalsubject' => $subdata['federalsubject'],
				'city' => $subdata['city'],
				'address' => $subdata['address'],
				'postal' => $subdata['postal'],
				'reason' => $subdata['reason'],
				'awards' => $subdata['awards'],
				'contacts' => $subdata['contacts'],
				'photo' => $subdata['photo'],
				'work1' => $subdata['work1'],
				'work1name' => $subdata['work1name'],
				'work1about' => $subdata['work1about'],
				'work1framing' => $subdata['work1framing'],
				'work2' => $subdata['work2'],
				'work2name' => $subdata['work2name'],
				'work2about' => $subdata['work2about'],
				'work2framing' => $subdata['work2framing'],
				'work3' => $subdata['work3'],
				'work3name' => $subdata['work3name'],
				'work3about' => $subdata['work3about'],
				'work3framing' => $subdata['work3framing'],
			)));

			++$applicantsCount;
		}

		if (!$applicantsCount) {
			return false;
		}

		return $request->save() ? $request->get('id') : false;
	}

	function mailRequest($ids, $foldername, $to = null)
	{

		$assetsUrl = $this->modx->getOption('site_url') . 'uploads/works_up/' . $foldername . '/';
		$federaldistricts = $this->getFederalDistrictsSubjects();
		$requestreasons = $this->getRequestReasons();

		$body = '';

		foreach (is_array($ids) ? $ids : array($ids) as $id) {
			$request = $this->modx->getObjectGraph('ArtRequestRequest', '{"Persons": {}, "OrgPersons": {}}', $id);
			if (!$request) {
				continue;
			}

			$body .= "###############################################################################\n"
				. "ID заявки: " . $id . "\n"
				. "###############################################################################\n\n";

			foreach ($request->OrgPersons as $orgperson) {
				$federaldistrict = '';
				foreach ($federaldistricts as $district => $subjects) {
					if (in_array($orgperson->federalsubject, $subjects)) {
						$federaldistrict = $district;
						break;
					}
				}

				$body .= ($orgperson->name ?: '—') . " (" . ($orgperson->birthdate ?: '—') . ")\n";
				$body .= $federaldistrict . ", " . ($orgperson->federalsubject ?: '—') . "\n";
				$body .= ($orgperson->postal ?: '—') . ", " . ($orgperson->city ?: '—') . ", " . ($orgperson->address ?: '—') . "\n\n";
				$body .= "Категория заявки: " . $requestreasons[$orgperson->reason] . "\n\n";
				$body .= "Контактные данные участника\n" . ($orgperson->contacts ?: '—') . "\n\n";
				$body .= "Наличие наград у участника\n" . ($orgperson->awards ?: '—') . "\n\n";
				$body .= "Портретное фото участника\n" . ($orgperson->photo ? ($assetsUrl . $orgperson->photo) : '—') . "\n\n";
				
				if ($orgperson->work1) {
					$body .= "Работа \"" . $orgperson->work1name
						. "\", " . $orgperson->work1about
						. ", " . $orgperson->work1framing
						. "\n" . $assetsUrl . $orgperson->work1 . "\n\n";
				}
				if ($orgperson->work2) {
					$body .= "Работа \"" . $orgperson->work2name
						. "\", " . $orgperson->work2about
						. ", " . $orgperson->work2framing
						. "\n" . $assetsUrl . $orgperson->work2 . "\n\n";
				}
				if ($orgperson->work3) {
					$body .= "Работа \"" . $orgperson->work3name
						. "\", " . $orgperson->work3about
						. ", " . $orgperson->work3framing
						. "\n" . $assetsUrl . $orgperson->work3 . "\n\n";
				}

				$body .= "-------------------------------------------------------------------------------\n\n";
			}

			foreach ($request->Persons as $person) {
				$federaldistrict = '';
				foreach ($federaldistricts as $district => $subjects) {
					if (in_array($person->federalsubject, $subjects)) {
						$federaldistrict = $district;
						break;
					}
				}

				$body .= ($person->name ?: '—') . " (" . ($person->birthdate ?: '—') . ")\n";
				$body .= $federaldistrict . ", " . ($person->federalsubject ?: '—') . "\n";
				$body .= ($person->postal ?: '—') . ", " . ($person->city ?: '—') . ", " . ($person->address ?: '—') . "\n\n";
				$body .= "Категория заявки: " . $requestreasons[$person->reason] . "\n\n";
				$body .= "Контактные данные участника\n" . ($person->contacts ?: '—') . "\n\n";
				$body .= "Наличие наград у участника\n" . ($person->awards ?: '—') . "\n\n";
				$body .= "Портретное фото участника\n" . ($person->photo ? ($assetsUrl . $person->photo) : '—') . "\n\n";
				
				if ($person->work1) {
					$body .= "Работа \"" . $person->work1name
						. "\", " . $person->work1about
						. ", " . $person->work1framing
						. "\n" . $assetsUrl . $person->work1 . "\n\n";
				}
				if ($person->work2) {
					$body .= "Работа \"" . $person->work2name
						. "\", " . $person->work2about
						. ", " . $person->work2framing
						. "\n" . $assetsUrl . $person->work2 . "\n\n";
				}
				if ($person->work3) {
					$body .= "Работа \"" . $person->work3name
						. "\", " . $person->work3about
						. ", " . $person->work3framing
						. "\n" . $assetsUrl . $person->work3 . "\n\n";
				}

				$body .= "-------------------------------------------------------------------------------\n\n";
			}

			$body .= "===============================================================================\n\n";
			$body .= "Контактные данные представляющего лица и организации\n" . ($request->contacts ?: '—') . "\n\n";
			$body .= "===============================================================================\n\n";

			$body .= "Адрес электронной почты для обратной связи\n" . ($request->clientemail ?: '—') . "\n\n";
		}

		if ($body) {
			$mail = $this->modx->getService('mail', 'mail.modPHPMailer');
			$mail->set(modMail::MAIL_FROM, 'noreply@art.fondsci.localhost');
			$mail->set(modMail::MAIL_FROM_NAME, 'Семья - душа России');

			if ($to) {
				$mail->address('to', $to);
			} else {
                $mail->address('to', 'paint@fondsci.ru');
				$mail->address('to', 'geg.info@yandex.ru');
				}

			if (is_array($ids) && count($ids) > 1) {
				$mail->set(modMail::MAIL_SUBJECT, 'Перечень заявок');
			} elseif ((!is_array($ids) || count($ids) == 1) && count($request->OrgPersons) > 1) {
				$mail->set(modMail::MAIL_SUBJECT, 'Заявка на несколько человек');
			} else {
				$mail->set(modMail::MAIL_SUBJECT, 'Заявка');
			}

			$mail->set(modMail::MAIL_BODY, rtrim($body));

			$mail->send();
			$mail->reset();

			if ((!is_array($ids) || count($ids) == 1) && !$to) {
				$id = is_array($ids) ? reset($ids) : $ids;
				$request = $this->modx->getObject('ArtRequestRequest', $id);
				if ($request && $request->clientemail) {
					$mail->set(modMail::MAIL_FROM, 'noreply@art.fondsci.ru');
					$mail->set(modMail::MAIL_FROM_NAME, 'Семья - душа России');
					$mail->address('to', $request->clientemail);
					$mail->set(modMail::MAIL_SUBJECT, 'Уведомление о получении заявки');
					$mail->set(modMail::MAIL_BODY, 'Ваша заявка под номером ' . $id . ' получена и принята к рассмотрению.' . "\r\n"
						. 'Спасибо за участие во Всероссийском конкурсе художественных работ «Семья – душа России»!');
					$mail->send();
					$mail->reset();
				}
			}
		}
	}

	function mailRequestsAsTable(array $ids, $to)
	{
		$federaldistricts = $this->getFederalDistrictsSubjects();
		
		$body = '';
		
		foreach ($ids as $id) {
			$request = $this->modx->getObjectGraph('ArtRequestRequest', '{"Persons": {}, "OrgPersons": {}}', $id);
			if (!$request) {
				continue;
			}
			
			$body .= '<tr><td colspan="5"><b>Заявка № ' . $id . '</b></td></tr>';
			
			foreach ($request->OrgPersons as $orgperson) {
				if (!$orgperson->name) {
					continue;
				}
				
				$federaldistrict = '';
				foreach ($federaldistricts as $district => $subjects) {
					if (in_array($orgperson->federalsubject, $subjects)) {
						$federaldistrict = $district;
						break;
					}
				}
				
				$body .= '<tr>';
				$body .= '<td>' . ($orgperson->name ?: '—') . '</td>';
				$body .= '<td>' . $federaldistrict . ', ' . ($orgperson->federalsubject ?: '—') . '</td>';
				$body .= '<td>' . ($orgperson->postal ?: '—') . ', ' . ($orgperson->city ?: '—') . ', ' . ($orgperson->address ?: '—'). '</td>';
				$body .= '<td>' . nl2br($orgperson->contacts ?: '—') . '</td>';
				$body .= '<td>' . nl2br($request->contacts ?: '—') . '</td>';
				$body .= '</tr>';
			}
			
			foreach ($request->Persons as $person) {
				if (!$person->name) {
					continue;
				}
				
				$federaldistrict = '';
				foreach ($federaldistricts as $district => $subjects) {
					if (in_array($person->federalsubject, $subjects)) {
						$federaldistrict = $district;
						break;
					}
				}
				
				$body .= '<tr>';
				$body .= '<td>' . ($person->name ?: '—') . '</td>';
				$body .= '<td>' . $federaldistrict . ', ' . ($person->federalsubject ?: '—') . '</td>';
				$body .= '<td>' . ($person->postal ?: '—') . ', ' . ($person->city ?: '—') . ', ' . ($person->address ?: '—'). '</td>';
				$body .= '<td>' . nl2br($person->contacts ?: '—') . '</td>';
				$body .= '<td>' . nl2br($request->contacts ?: '—') . '</td>';
				$body .= '</tr>';
			}
		}
		
		if ($body) {
			$body = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">'
				. '<html xmlns="http://www.w3.org/1999/xhtml"><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" />'
				. '<meta name="viewport" content="width=device-width, initial-scale=1.0" /><style type="text/css">#outlook a{padding:0}'
				. '.ExternalClass{width:100%}.ExternalClass,.ExternalClass p,.ExternalClass span,.ExternalClass font,.ExternalClass td,'
				. '.ExternalClass div{line-height:100%}body{margin:0;padding:0;-ms-text-size-adjust:100%;-webkit-text-size-adjust:100%;width:100%!important}'
				. '#wrapper{line-height:100%!important;margin:0;padding:0;width:100%!important}img{-ms-interpolation-mode:bicubic;outline:0;text-decoration:none}'
				. 'a img{border:0}p{margin:1em 0}h1,h2,h3,h4,h5,h6{color:#000!important;margin:.67em 0}table td{border-collapse:collapse}'
				. 'table{border-collapse:collapse;mso-table-lspace:0;mso-table-rspace:0}</style><title>Список заявок в виде таблицы</title></head><body>'
				. '<table cellpadding="0" cellspacing="0" border="1" style="line-height:100% !important;margin:0;padding:0;width:100% !important"><tr>'
				. '<td><b>ФИО или название</b></td>'
				. '<td><b>Округ и субъект</b></td>'
				. '<td><b>Адрес</b></td>'
				. '<td><b>Контакты</b></td>'
				. '<td><b>Представитель</b></td>'
				. '</tr>' . $body . '</table>'
				. '</body></html>';
			
			$mail = $this->modx->getService('mail', 'mail.modPHPMailer');
			$mail->set(modMail::MAIL_FROM, 'noreply@art.fondsci.localhost');
			$mail->set(modMail::MAIL_FROM_NAME, 'Семья - душа России');
			$mail->set(modMail::MAIL_SUBJECT, 'Список заявок в виде таблицы');
			$mail->set(modMail::MAIL_BODY, $body);
			$mail->setHTML(true);
			
			$mail->address('to', $to);
			
			$mail->send();
			$mail->reset();
		}
	}

	function removeRequest($id)
	{
		$request = $this->modx->getObjectGraph('ArtRequestRequest', '{"Persons": {}, "OrgPersons": {}}', $id);
		if (!$request) {
			return;
		}

		foreach ($request->Persons as $person) {
			if ($person->photo) {
				$this->removeFileUpload($person->photo,$foldername);
			}
			if ($person->work1) {
				$this->removeFileUpload($person->work1,$foldername);
			}
			if ($person->work2) {
				$this->removeFileUpload($person->work2,$foldername);
			}
			if ($person->work3) {
				$this->removeFileUpload($person->work3,$foldername);
			}
		}

		foreach ($request->OrgPersons as $orgperson) {
			if ($orgperson->photo) {
				$this->removeFileUpload($orgperson->photo);
			}
			if ($orgperson->work1) {
				$this->removeFileUpload($orgperson->work1);
			}
			if ($orgperson->work2) {
				$this->removeFileUpload($orgperson->work2);
			}
			if ($orgperson->work3) {
				$this->removeFileUpload($orgperson->work3);
			}
		}

		return $request->remove();
	}
	
	function prepareRequestsQuery(xPDOQuery $c, array $params = array())
	{
		$hidden = $params['hidden'] ?: null;
		$federalsubject = $params['federalsubject'] ?: null;
		$federaldistrict = $params['federaldistrict'] ?: null;
		$requestreason = $params['requestreason'];
		$createdonFrom = $params['createdonFrom'] ?: null;
		$createdonTo = $params['createdonTo'] ?: null;
		$applicants = $params['applicants'] ?: null;
		$applicantName = $params['applicantName'] ?: null;
		$personIsMale = $params['personIsMale'];
		$requestId = $params['requestId'] ?: null;
		
		if ($federalsubject || $federaldistrict || $applicants || $applicantName != '' || isset($requestreason) || isset($personIsMale)) {
			$c->distinct()->leftJoin('ArtRequestPerson', 'Persons')->leftJoin('ArtRequestOrgPerson', 'OrgPersons');
		}
		
		if ($applicants) {
			$c->groupby('ArtRequestRequest.id');
		}
		
		if (isset($hidden)) {
			$c->where(array('hidden' => $hidden));
		}
		
		if ($federalsubject) {
			$c->where(array(
				array('Persons.federalsubject' => $federalsubject),
				array('OrgPersons.federalsubject' => $federalsubject),
			), xPDOQuery::SQL_OR);
		} elseif ($federaldistrict) {
			$federalsubjects = $this->modx->getService('artRequest')->getFederalDistrictsSubjects();
			$federalsubjects = !empty($federalsubjects[$federaldistrict]) ? $federalsubjects[$federaldistrict] : array();
			
			$c->where(array(
				array('Persons.federalsubject:IN' => $federalsubjects),
				array('OrgPersons.federalsubject:IN' => $federalsubjects),
			), xPDOQuery::SQL_OR);
		}
		
		if (isset($requestreason)) {
			$c->where(array(
				array('Persons.reason' => $requestreason),
				array('OrgPersons.reason' => $requestreason),
			), xPDOQuery::SQL_OR);
		}
		
		if ($createdonFrom) {
			$c->where(array('createdon:>=' => date('Y-m-d', strtotime($createdonFrom))));
		}
		
		if ($createdonTo) {
			$c->where(array('createdon:<' => date('Y-m-d', strtotime($createdonTo) + 86400)));
		}
		
		switch ($applicants) {
			case 'person':
				$c->having('COUNT(Persons.id) = 1 AND COUNT(OrgPersons.id) = 0');
				break;
				
			case 'orgperson':
				$c->having('COUNT(Persons.id) = 0 AND COUNT(OrgPersons.id) = 1');
				break;
			
			case 'orgpersons':
				$c->having('COUNT(Persons.id) = 0 AND COUNT(OrgPersons.id) > 1');
				break;
		}
		
		if ($applicantName != '') {
			$applicantName = mb_strtolower($applicantName);
			
			$applicantNameMasks = array($applicantName);
			for ($i = mb_strlen($applicantName) - 1; $i >= 0; --$i) {
				$char = mb_substr($applicantName, $i, 1);
				switch ($char) {
					case 'е':
					case 'ё':
						$masks = array();
						foreach ($applicantNameMasks as $mask) {
							$masks[] = mb_substr($mask, 0, $i) . 'е' . mb_substr($mask, $i + 1);
							$masks[] = mb_substr($mask, 0, $i) . 'ё' . mb_substr($mask, $i + 1);
						}
						$applicantNameMasks = $masks;
						break;
						
					case 'и':
					case 'й':
						$masks = array();
						foreach ($applicantNameMasks as $mask) {
							$masks[] = mb_substr($mask, 0, $i) . 'и' . mb_substr($mask, $i + 1);
							$masks[] = mb_substr($mask, 0, $i) . 'й' . mb_substr($mask, $i + 1);
						}
						$applicantNameMasks = $masks;
						break;
				}
			}
			
			$applicantNameConditions = array();
			foreach ($applicantNameMasks as $mask) {
				$applicantNameConditions[] = array('Persons.name:LIKE' => '%' . $mask . '%');
				$applicantNameConditions[] = array('OrgPersons.name:LIKE' => '%' . $mask . '%');
			}
			
			$c->where($applicantNameConditions, xPDOQuery::SQL_OR);
		}
		
		if (isset($personIsMale)) {
			$c->where(array(
				array('Persons.ismale' => ($personIsMale == true)),
				array('OrgPersons.ismale' => ($personIsMale == true)),
			), xPDOQuery::SQL_OR);
		}
		
		if ($requestId) {
			$c->where(array('id' => $requestId));
		}
		
		return $c;
	}

	function getRequestReasons()
	{
		return array(
			'Профессиональный художник',
			'Студент художественного вуза',
			'Непрофессиональный художник',
			'Юный художник (до 18 лет)',
		);
	}

	function getFederalDistrictsSubjects()
	{
		return array(
			'Центральный федеральный округ' => array(
				'Белгородская область',
				'Брянская область',
				'Владимирская область',
				'Воронежская область',
				'Ивановская область',
				'Калужская область',
				'Костромская область',
				'Курская область',
				'Липецкая область',
				'Москва',
				'Московская область',
				'Орловская область',
				'Рязанская область',
				'Смоленская область',
				'Тамбовская область',
				'Тверская область',
				'Тульская область',
				'Ярославская область',
			),
			'Южный федеральный округ' => array(
				'Республика Адыгея',
				'Астраханская область',
				'Волгоградская область',
				'Республика Калмыкия',
				'Краснодарский край',
				'Республика Крым',
				'Ростовская область',
				'Севастополь',
			),
			'Северо-Западный федеральный округ' => array(
				'Архангельская область',
				'Вологодская область',
				'Калининградская область',
				'Республика Карелия',
				'Республика Коми',
				'Ленинградская область',
				'Мурманская область',
				'Ненецкий автономный округ',
				'Новгородская область',
				'Псковская область',
				'Санкт-Петербург',
			),
			'Дальневосточный федеральный округ' => array(
				'Амурская область',
				'Еврейская автономная область',
				'Камчатский край',
				'Магаданская область',
				'Приморский край',
				'Республика Саха (Якутия)',
				'Сахалинская область',
				'Хабаровский край',
				'Чукотский автономный округ',
				'Донецкая  народная республика',
				'Луганская народная республика',
				'Херсонская область',
				'Запорожская область',
			),
			'Сибирский федеральный округ' => array(
				'Республика Алтай',
				'Алтайский край',
				'Республика Бурятия',
				'Забайкальский край',
				'Иркутская область',
				'Кемеровская область',
				'Красноярский край',
				'Новосибирская область',
				'Омская область',
				'Томская область',
				'Республика Тыва',
				'Республика Хакасия',
			),
			'Уральский федеральный округ' => array(
				'Курганская область',
				'Свердловская область',
				'Тюменская область',
				'Ханты-Мансийский автономный округ',
				'Челябинская область',
				'Ямало-Ненецкий автономный округ',
			),
			'Приволжский федеральный округ' => array(
				'Республика Башкортостан',
				'Кировская область',
				'Республика Марий Эл',
				'Республика Мордовия',
				'Нижегородская область',
				'Оренбургская область',
				'Пензенская область',
				'Пермский край',
				'Самарская область',
				'Саратовская область',
				'Республика Татарстан',
				'Удмуртская Республика',
				'Ульяновская область',
				'Чувашская Республика',
			),
			'Северо-Кавказский федеральный округ' => array(
				'Республика Дагестан',
				'Республика Ингушетия',
				'Кабардино-Балкарская Республика',
				'Карачаево-Черкесская Республика',
				'Республика Северная Осетия',
				'Ставропольский край',
				'Чеченская Республика',
			),
		);
	}

	function getFederalCities()
	{
		return array('Москва', 'Санкт-Петербург', 'Севастополь');
	}

	function getAssetsPath()
	{
		return $this->assetsPath;
	}

	function getAssetsUrl()
	{
		return $this->assetsUrl;
	}

	function jsonEncode($object)
{
    return preg_replace(
        '/(.{500}.*?)","/',
        "$1\",\n\"",
        preg_replace_callback(
            '/\\\\u([0-9a-fA-F]{4})/i',
            function ($matches) {
                return mb_convert_encoding(
                    pack("H*", $matches[1]),
                    "UTF-8",
                    "UTF-16"
                );
            },
            json_encode($object)
        )
    );
}

	function hasErroneousFileUploads()
	{
		foreach ($_FILES as $file) {
			foreach (is_array($file['error']) ? $file['error'] : array($file['error']) as $error) {
				if ($error != UPLOAD_ERR_OK && $error != UPLOAD_ERR_NO_FILE) {
					return true;
				}
			}
		}

		return false;
	}

	function getPostField($name, $index = null, $default = '')
	{
		if (isset($index) && !isset($_POST[$name][$index]) || !isset($_POST[$name])) {
			return $default;
		}

		$result = isset($index) ? $_POST[$name][$index] : $_POST[$name];
		return ($result != '') ? $result : $default;
	}


function handleFileUpload($name, $index = null, $foldername = '')
{
    if (empty($_FILES[$name])) {
        return '';
    }

    $file = $_FILES[$name];

    if (isset($index)) {
        if (!isset($file['error'][$index])) {
            return '';
        }

        $file = array_map(
            function ($x) use ($index) {
                return $x[$index];
            },
            $file
        );
    }

    if ($file['error'] != UPLOAD_ERR_OK) {
        return '';
    }

    $uploadsPath = 'uploads/works_up/' . $foldername;

    if (!is_dir($uploadsPath)) {
        mkdir($uploadsPath, 0755, true);
    }

    $last = 1;

    foreach (glob($uploadsPath . '/*') as $filename) {
        if (!is_file($filename)) {
            continue;
        }

        $filename = pathinfo($filename, PATHINFO_FILENAME);

        if (!ctype_digit($filename)) {
            continue;
        }

        if ($filename >= $last) {
            $last = $filename + 1;
        }
    }

    $filename = strtolower(
        rtrim($last . '.' . pathinfo($file['name'], PATHINFO_EXTENSION), '.')
    );

    return move_uploaded_file(
        $file['tmp_name'],
        $uploadsPath . '/' . $filename
    ) ? $filename : '';
}

	function removeFileUpload($filename, $foldername)
	{
		if (!$filename) {
			return;
		}
		
		@unlink('uploads/works_up/' . $foldername . '/' . str_replace(array('\\', '/'), '', $filename));
	}



	function getChunk($name, array $properties = array())
	{
		return $this->pdoTools->getChunk($name, $properties);
	}

	private function _getPathOption($key)
	{
		return $this->modx->getOption(
			'artrequest.' . $key, null,
			$this->modx->getOption($key) . 'components/artrequest/'
		);
	}
}
