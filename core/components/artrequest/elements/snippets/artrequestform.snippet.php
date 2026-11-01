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

$service = $modx->getService(
	'artRequest',
	'ArtRequest',
	$modx->getOption(
		'artrequest.core_path', null,
		$modx->getOption('core_path') . 'components/artrequest/'
	) . 'model/artrequest/'
);

$handler = $modx->resource->uri;
$message = '';
$messageType = '';

if (!empty($_POST)) {
	if ($service->hasErroneousFileUploads()) {
		$message = 'При загрузке прикрепленных файлов возникла ошибка. Возможно, вы попытались загрузить слишком большие файлы, либо из-за нестабильного соединения с интернетом файлы были загружены не полностью. Пожалуйста, повторите попытку позже.';
	}

	if (!$message) {
		$uploads = array();

		if ($service->getPostField('person_name')) {
			$contacts = 'ФИО: ' . $service->getPostField('contacts_name') . "\r\n"
				. 'Контактный телефон: ' . $service->getPostField('contacts_phone');
		} else {
			$contacts = 'Организация: ' . $service->getPostField('contacts_organization') . "\r\n"
				. 'ФИО: ' . $service->getPostField('contacts_name') . "\r\n"
				. 'Должность: ' . $service->getPostField('contacts_position') . "\r\n"
				. 'Контактный телефон: ' . $service->getPostField('contacts_phone');
		}

		$request = array(
			'clientemail' => $service->getPostField('clientemail'),
			'contacts' => $contacts,
			'persons' => array(),
			'orgpersons' => array(),
		);

		foreach (array_keys($service->getPostField('person_name', null, array())) as $i) {
			$contacts = 'Контактный телефон: ' . $service->getPostField('person_contacts_phone', $i) . "\r\n"
				. 'Адрес электронной почты: ' . $service->getPostField('person_contacts_email', $i);

		$foldername = $service->getPostField('person_reason', $i, '0');
		
		if ($foldername == 0) {
			$folder = 'professionals';
		} elseif ($foldername == 1) {
			$folder = 'students';
		} elseif ($foldername == 2) {
			$folder = 'amateurs'; 
		} elseif ($foldername == 3) {
			$folder = 'young';
		} 


			$request['persons'][] = array(
				'name' => $service->getPostField('person_name', $i),
				'ismale' => $service->getPostField('person_ismale', $i, true),
				'birthdate' => $service->getPostField('person_birthdate', $i, '01.01.1970'),
				'federalsubject' => $service->getPostField('person_federalsubject', $i),
				'city' => $service->getPostField('person_city', $i),
				'address' => $service->getPostField('person_address', $i),
				'postal' => $service->getPostField('person_postal', $i),
				'reason' => $service->getPostField('person_reason', $i, '0'),
				'awards' => $service->getPostField('person_awards', $i),
				'contacts' => $contacts,

				'photo' => ($uploads[] = $service->handleFileUpload('person_photo', $i, $folder)),
				
				'work1' => ($uploads[] = $service->handleFileUpload('person_work1', $i, $folder)),
				'work1name' => $service->getPostField('person_workname1', $i),
				'work1about' => $service->getPostField('person_workabout1', $i),
				'work1framing' => $service->getPostField('person_workframing1', $i),
				
				'work2' => ($uploads[] = $service->handleFileUpload('person_work2', $i, $folder)),
				'work2name' => $service->getPostField('person_workname2', $i),
				'work2about' => $service->getPostField('person_workabout2', $i),
				'work2framing' => $service->getPostField('person_workframing2', $i),
				
				'work3' => ($uploads[] = $service->handleFileUpload('person_work3', $i, $folder)),
				'work3name' => $service->getPostField('person_workname3', $i),
				'work3about' => $service->getPostField('person_workabout3', $i),
				'work3framing' => $service->getPostField('person_workframing3', $i),
			);
			
			
		}

		foreach (array_keys($service->getPostField('orgperson_name', null, array())) as $i) {
			$contacts = 'Контактный телефон: ' . $service->getPostField('orgperson_contacts_phone', $i) . "\r\n"
				. 'Адрес электронной почты: ' . $service->getPostField('orgperson_contacts_email', $i);

			$request['orgpersons'][] = array(
				'name' => $service->getPostField('orgperson_name', $i),
				'ismale' => $service->getPostField('orgperson_ismale', $i, true),
				'birthdate' => $service->getPostField('orgperson_birthdate', $i, '01.01.1970'),
				'federalsubject' => $service->getPostField('orgperson_federalsubject', $i),
				'city' => $service->getPostField('orgperson_city', $i),
				'address' => $service->getPostField('orgperson_address', $i),
				'postal' => $service->getPostField('orgperson_postal', $i),
				'reason' => $service->getPostField('orgperson_reason', $i, '0'),
				'awards' => $service->getPostField('orgperson_awards', $i),
				'contacts' => $contacts,

				'photo' => ($uploads[] = $service->handleFileUpload('orgperson_photo', $i, $folder)),
				
				'work1' => ($uploads[] = $service->handleFileUpload('orgperson_work1', $i, $folder)),
				'work1name' => $service->getPostField('orgperson_workname1', $i),
				'work1about' => $service->getPostField('orgperson_workabout1', $i),
				'work1framing' => $service->getPostField('orgperson_workframing1', $i),
				
				'work2' => ($uploads[] = $service->handleFileUpload('orgperson_work2', $i, $folder)),
				'work2name' => $service->getPostField('orgperson_workname2', $i),
				'work2about' => $service->getPostField('orgperson_workabout2', $i),
				'work2framing' => $service->getPostField('orgperson_workframing2', $i),
				
				'work3' => ($uploads[] = $service->handleFileUpload('orgperson_work3', $i, $folder)),
				'work3name' => $service->getPostField('orgperson_workname3', $i),
				'work3about' => $service->getPostField('orgperson_workabout3', $i),
				'work3framing' => $service->getPostField('orgperson_workframing3', $i),
			);

		}

		if ($id = $service->createRequest($request)) {
		
			$service->mailRequest($id, $folder);

			$handler = null;
			$message = '<b>Ваша заявка под номером ' . $id . ' получено и принято к рассмотрению.</b><br>Спасибо за участие во Всероссийском конкурсе художественных работ «Семья – душа России»!'
				. '<br><br><a class="alert-link" href="' . $modx->resource->uri . '">Перейти к заполнению новой заявки</a>';
			$messageType = 'success';	
			
		} else {
		
			foreach (array_filter($uploads) as $filename) {
				$service->removeFileUpload($filename, $folder);
				
			}
			$handler = null;
			$message = 'Во время обработки онлайн-заявки возникла непредвиденная ошибка. Пожалуйста, повторите попытку позже.';
			
		}
	}
}

$tpl = $modx->getOption('tpl', $scriptProperties);

$modx->regClientCSS($service->getAssetsUrl() . 'vendors/bootstrap-datetimepicker.min.css');
$modx->regClientHTMLBlock('<script defer src="' . $service->getAssetsUrl() . 'vendors/moment-with-locales.min.js"></script>');
$modx->regClientHTMLBlock('<script defer src="' . $service->getAssetsUrl() . 'vendors/bootstrap-datetimepicker.min.js"></script>');

$modx->regClientCSS($service->getAssetsUrl() . 'web.css');
$modx->regClientHTMLBlock('<script defer src="' . $service->getAssetsUrl() . 'web.js"></script>');

$modx->regClientHTMLBlock('<script>var artRequest = ' . $service->jsonEncode(array(
	'requestReasons' => $service->getRequestReasons(),
	'federalDistrictsSubjects' => $service->getFederalDistrictsSubjects(),
	'federalCities' => $service->getFederalCities(),
)) . '</script>');

return $service->getChunk($tpl, array(
	'handler' => $handler,
	'message' => $message,
	'messageType' => $messageType,
));