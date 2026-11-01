<noscript>
	<div class="alert alert-danger">Для работы формы создания онлайн-заявки требуется JavaScript.</div>
</noscript>
[[+message:ne=``:then=`<div class="alert alert-[[+messageType:default=`danger`]]">[[+message]]</div>`]]

<form id="requestForm" class="_hidden" method="POST" [[+handler:ne=``:then=`action="[[+handler]]"`]] enctype="multipart/form-data">
<p>Убедительная просьба заполнять анкету полностью и правдиво. Указанная информация будет использована для связи с победителем.</p>
	<div class="row">
		<div id="requestFormApplicants" class="col-md-7">
		<!-- 09.02.2019 Нужно сделать так, чтобы подавалась только заявка на одного человека и скрыть кнопки -->
		<div class="form-group" style="display:none;">
    		<button id="requestFormApplicantsPerson" class="btn btn-default" type="button">Индивидуальная заявка</button>
		</div>
		
			<!-- <div class="form-group">
			    <div class="btn-group">
			        <button id="requestFormApplicantsOrgPersons" class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown"><span>Заявка от организации</span> <i class="caret"></i></button>
			        <ul class="dropdown-menu">
			            <li><a href="javascript:void(0)">1 человек от организации</a></li>
			            <li><a href="javascript:void(0)">2 человека от организации</a></li>
			            <li><a href="javascript:void(0)">3 человека от организации</a></li>
			            <li><a href="javascript:void(0)">4 человека от организации</a></li>
			            <li><a href="javascript:void(0)">5 человек от организации</a></li>
			            <li><a href="javascript:void(0)">6 человек от организации</a></li>
			            <li><a href="javascript:void(0)">7 человек от организации</a></li>
			            <li><a href="javascript:void(0)">8 человек от организации</a></li>
			            <li><a href="javascript:void(0)">9 человек от организации</a></li>
			            <li><a href="javascript:void(0)">10 человек от организации</a></li>
			        </ul>
			    </div>
			    
			    <button id="requestFormApplicantsPerson" class="btn btn-default" type="button">Индивидуальная заявка</button>
			</div> -->

			<fieldset id="requestFormPerson" class="well">
				<div class="row">
					<div class="col-sm-5 col-lg-6">
						<div class="form-group">
							<label class="required" for="requestFormPersonName">ФИО участника</label>
							<input id="requestFormPersonName" class="form-control" type="text" name="person_name" required>
						</div>
					</div>

					<div class="col-sm-3">
						<div class="form-group">
							<label class="required" for="requestFormPersonIsMale">Пол</label>
							<select id="requestFormPersonIsMale" class="form-control" name="person_ismale" required>
								<option value=""></option>
								<option value="1">Мужской</option>
								<option value="0">Женский</option>
							</select>
						</div>
					</div>

					<div class="col-sm-4 col-lg-3">
						<div class="form-group">
							<label class="required" for="requestFormPersonBirthDate">Дата рождения</label>
							<input id="requestFormPersonBirthDate" class="form-control with-datetimepicker" type="text" name="person_birthdate" required>
						</div>
					</div>
				</div>

				<div class="row">
					<div class="col-sm-6">
						<div class="form-group">
							<label class="required" for="requestFormPersonFederalSubject">Субъект РФ (необходимо выбрать из списка)</label>
							<select id="requestFormPersonFederalSubject" class="form-control with-federal-subjects" name="person_federalsubject" required></select>
							<p class="help-block small with-federal-district"></p>
						</div>
					</div>

					<div class="col-sm-6">
						<div class="form-group">
							<label class="required" for="requestFormPersonCity">Город (населенный пункт)</label>
							<input id="requestFormPersonCity" class="form-control" type="text" name="person_city" required>
						</div>
					</div>
				</div>

				<div class="row">
					<div class="col-sm-9">
						<div class="form-group">
							<label class="required" for="requestFormPersonAddress">Адрес участника</label>
							<input id="requestFormPersonAddress" class="form-control" type="text" name="person_address" required>
						</div>
					</div>

					<div class="col-sm-3">
						<div class="form-group">
							<label class="required" for="requestFormPersonPostal">Индекс</label>
							<input id="requestFormPersonPostal" class="form-control" type="text" name="person_postal" required>
						</div>
					</div>
				</div>

				<div class="row">
					<div class="col-sm-6">
						<div class="form-group">
							<label class="required" for="requestFormPersonContactsPhone">Контактный телефон</label>
							<input id="requestFormPersonContactsPhone" class="form-control" type="tel" name="person_contacts_phone" required>
						</div>
					</div>

					<div class="col-sm-6">
						<div class="form-group">
							<label for="requestFormPersonContactsEmail">Адрес электронной почты</label>
							<input id="requestFormPersonContactsEmail" class="form-control" type="email" name="person_contacts_email">
						</div>
					</div>
				</div>

				<div class="form-group">
					<fieldset class="with-person-reasons required">
						<legend>Категория заявки</legend>
					</fieldset>
				</div>

				<!--<div class="form-group">-->
				<!--	<label for="requestFormPersonAwards">Наличие наград у участника (включая регистрационные данные)</label>-->
				<!--	<textarea id="requestFormPersonAwards" class="form-control" name="person_awards"></textarea>-->
				<!--</div>-->

				<!--<div class="form-group">-->
				<!--	<label class="required" for="requestFormPersonPhoto">Портретное фото участника (JPEG или PNG, не менее 600 &times; 800)</label>-->
				<!--	<input id="requestFormPersonPhoto" type="file" name="person_photo" required accept="image/*">-->
				<!--</div>-->
				
				<hr>
				
				<fieldset id="requestFormPersonWork" class="work">
					<div class="row">
						<div class="col-sm-6">
							<div class="form-group">
								<label class="required" for="requestFormPersonWorkName">Название работы</label>
								<input id="requestFormPersonWorkName" class="form-control" type="text" name="person_workname" required>
							</div>
						</div>
						
						<div class="col-sm-6">
							<div class="form-group">
								<label class="required" for="requestFormPersonWorkAbout">Формат, техника (пример: 50×70 см, холст, масло)</label>
								<input id="requestFormPersonWorkAbout" class="form-control" type="text" name="person_workabout" required>
							</div>
						</div>
					</div>
					
					<div class="row">
						<div class="col-sm-6">
							<div class="form-group">
								<label class="required" for="requestFormPersonWorkPicture">Изображение работы в хорошем качестве</label>
								<input id="requestFormPersonWorkPicture" type="file" name="person_work" required accept="image/*">
							</div>
						</div>
						
						<div class="col-sm-6">
							<div class="form-group">
								<label class="required" for="requestFormPersonWorkFraming">Оформление</label>
								<select id="requestFormPersonWorkFraming" class="form-control" name="person_workframing" required>
									<option value=""></option>
									<option>Нет оформления</option>
									<option>Оформлена в багет</option>
									<option>Оформлена в паспарту</option>
								</select>
							</div>
						</div>
					</div>
					
					<hr>
				</fieldset>
				
				<div class="form-group clearfix">
					<div class="pull-left">
						<button type="button" class="btn btn-default js-works-add"><i class="fas fa-plus"></i> Добавить еще одну работу</button>
					</div>
					
					<div class="pull-right">
						<button type="button" class="btn btn-default js-works-remove"><i class="fas fa-times"></i> Убрать последнюю работу</button>
					</div>
				</div>
			</fieldset>
			
			<fieldset id="requestFormOrgPerson" class="well">
				<div class="row">
					<div class="col-sm-5 col-lg-6">
						<div class="form-group">
							<label class="required" for="requestFormOrgPersonName">ФИО участника</label>
							<input id="requestFormOrgPersonName" class="form-control" type="text" name="orgperson_name" required>
						</div>
					</div>

					<div class="col-sm-3">
						<div class="form-group">
							<label class="required" for="requestFormOrgPersonIsMale">Пол</label>
							<select id="requestFormOrgPersonIsMale" class="form-control" name="orgperson_ismale" required>
								<option value=""></option>
								<option value="1">Мужской</option>
								<option value="0">Женский</option>
							</select>
						</div>
					</div>

					<div class="col-sm-4 col-lg-3">
						<div class="form-group">
							<label class="required" for="requestFormOrgPersonBirthDate">Дата рождения</label>
							<input id="requestFormOrgPersonBirthDate" class="form-control with-datetimepicker" type="text" name="orgperson_birthdate" required>
						</div>
					</div>
				</div>

				<div class="row">
					<div class="col-sm-6">
						<div class="form-group">
							<label class="required" for="requestFormOrgPersonFederalSubject">Субъект РФ (необходимо выбрать из списка)</label>
							<select id="requestFormOrgPersonFederalSubject" class="form-control with-federal-subjects" name="orgperson_federalsubject" required></select>
							<p class="help-block small with-federal-district"></p>
						</div>
					</div>

					<div class="col-sm-6">
						<div class="form-group">
							<label class="required" for="requestFormOrgPersonCity">Город (населенный пункт)</label>
							<input id="requestFormOrgPersonCity" class="form-control" type="text" name="orgperson_city" required>
						</div>
					</div>
				</div>

				<div class="row">
					<div class="col-sm-9">
						<div class="form-group">
							<label class="required" for="requestFormOrgPersonAddress">Адрес участника</label>
							<input id="requestFormOrgPersonAddress" class="form-control" type="text" name="orgperson_address" required>
						</div>
					</div>

					<div class="col-sm-3">
						<div class="form-group">
							<label class="required" for="requestFormOrgPersonPostal">Индекс</label>
							<input id="requestFormOrgPersonPostal" class="form-control" type="text" name="orgperson_postal" required>
						</div>
					</div>
				</div>

				<div class="row">
					<div class="col-sm-6">
						<div class="form-group">
							<label class="required" for="requestFormOrgPersonContactsPhone">Контактный телефон</label>
							<input id="requestFormOrgPersonContactsPhone" class="form-control" type="tel" name="orgperson_contacts_phone" required>
						</div>
					</div>

					<div class="col-sm-6">
						<div class="form-group">
							<label for="requestFormPersonOrgContactsEmail">Адрес электронной почты</label>
							<input id="requestFormPersonOrgContactsEmail" class="form-control" type="email" name="orgperson_contacts_email">
						</div>
					</div>
				</div>

				<div class="form-group">
					<fieldset class="with-orgperson-reasons required">
						<legend>Категория заявки</legend>
					</fieldset>
				</div>

				<!--<div class="form-group">-->
				<!--	<label for="requestFormOrgPersonAwards">Наличие наград у участника (включая регистрационные данные)</label>-->
				<!--	<textarea id="requestFormOrgPersonAwards" class="form-control" name="orgperson_awards"></textarea>-->
				<!--</div>-->

				<!--<div class="form-group">-->
				<!--	<label class="required" for="requestFormOrgPersonPhoto">Портретное фото участника (JPEG или PNG, не менее 600 &times; 800)</label>-->
				<!--	<input id="requestFormOrgPersonPhoto" type="file" name="orgperson_photo" required accept="image/*">-->
				<!--</div>-->
				
				<hr>
				
				<fieldset id="requestFormOrgPersonWork" class="work">
					<div class="row">
						<div class="col-sm-6">
							<div class="form-group">
								<label class="required" for="requestFormOrgPersonWorkName">Название работы</label>
								<input id="requestFormPersonOrgWorkName" class="form-control" type="text" name="orgperson_workname" required>
							</div>
						</div>
						
						<div class="col-sm-6">
							<div class="form-group">
								<label class="required" for="requestFormOrgPersonWorkAbout">Формат, техника (пример: 50×70 см, холст, масло)</label>
								<input id="requestFormOrgPersonWorkAbout" class="form-control" type="text" name="orgperson_workabout" required>
							</div>
						</div>
					</div>
					
					<div class="row">
						<div class="col-sm-6">
							<div class="form-group">
								<label class="required" for="requestFormOrgPersonWorkPicture">Изображение работы в хорошем качестве</label>
								<input id="requestFormOrgPersonWorkPicture" type="file" name="orgperson_work" required accept="image/*">
							</div>
						</div>
						
						<div class="col-sm-6">
							<div class="form-group">
								<label class="required" for="requestFormOrgPersonWorkFraming">Оформление</label>
								<select id="requestFormOrgPersonWorkFraming" class="form-control" name="orgperson_workframing" required>
									<option value=""></option>
									<option>Нет оформления</option>
									<option>Оформлена в багет</option>
									<option>Оформлена в паспарту</option>
								</select>
							</div>
						</div>
					</div>
					
					<hr>
				</fieldset>
				
				<div class="form-group clearfix">
					<div class="pull-left">
						<button type="button" class="btn btn-default js-works-add"><i class="fas fa-plus"></i> Добавить еще одну работу</button>
					</div>
					
					<div class="pull-right">
						<button type="button" class="btn btn-default js-works-remove"><i class="fas fa-times"></i> Убрать последнюю работу</button>
					</div>
				</div>
			</fieldset>
		</div>

		<div class="col-md-5">
			<div id="requestFormContacts" class="well">
				<h3>Контактная информация заполняющего заявку:</h3>
				
				<div class="form-group">
					<label class="required" for="requestFormContactsOrganization">Полное наименование организации (учебного заведения, творческой&nbsp;школы)</label>
					<input id="requestFormContactsOrganization" class="form-control" type="text" name="contacts_organization" required>
				</div>
				
				<div class="form-group">
					<label class="required" for="requestFormContactsName">ФИО <span class="for-orgpersons">представителя организации</span></label>
					<input id="requestFormContactsName" class="form-control" type="text" name="contacts_name" required>
				</div>

				<div class="form-group">
					<label class="required" for="requestFormContactsPosition">Должность представителя организации</label>
					<input id="requestFormContactsPosition" class="form-control" type="text" name="contacts_position" required>
				</div>

				<div class="form-group">
					<label class="required" for="requestFormContactsPhone">Контактный телефон</label>
					<input id="requestFormContactsPhone" class="form-control" type="tel" name="contacts_phone" required>
				</div>

				<div class="form-group">
					<label class="required" for="requestFormClientEmail">Адрес электронной почты</label>
					<input id="requestFormClientEmail" class="form-control" type="email" name="clientemail" required>
				</div>
			</div>
			
			<div class="checkbox">
				<label class="required">
					<input type="checkbox" required>
					<b class="small">заполнение настоящей формы заявки означает согласие кандидата и (в необходимых случаях) его законных представителей c <a href="https://art.fondsci.localhost/uploads/okonkurse/terms2026.pdf" target="_blank" rel="noopener noreferrer">положением</a> о конкурсе</b>
				</label>
			</div>

			<p class="small">
				<br>
				В соответствии с требованиями ст.ст. 6 и 9 Федерального закона «О&nbsp;персональных данных» от 27.07.2006 № 152-ФЗ подтверждаю, что:
			</p>
			
			<div class="checkbox">
				<label class="required">
					<input type="checkbox" required>
					<b class="small">заполнение настоящей формы заявки означает согласие кандидата и (в необходимых случаях) его законных представителей на обработку организаторами конкурса и членами Жюри персональных данных, содержащихся в заявке, а также на дальнейшее предоставление к этим данным доступа неопределенному кругу лиц, включая публикацию работ и сведений об авторах.</b>
				</label>
			</div>


			<div class="form-group">
				<br>
				<button class="btn btn-lg btn-primary" type="submit">Отправить заявку</button>
			</div>
		</div>
	</div>
</form>
