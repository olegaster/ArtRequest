<noscript>
	<div class="alert alert-danger">Для работы формы создания онлайн-заявки требуется JavaScript.</div>
</noscript>
[[+message:ne=``:then=`<div class="alert alert-[[+messageType:default=`danger`]]">[[+message]]</div>`]]

<form id="requestForm" class="hidden" method="POST" [[+handler:ne=``:then=`action="[[+handler]]"`]] enctype="multipart/form-data">
<p>Убедительная просьба заполнять анкету полностью и правдиво. Указанная информация будет использована для связи с победителем.</p>
	<div class="row">
		<div id="requestFormApplicants" class="col-md-7">
		<div class="form-group" style="display:none;">
    		<button id="requestFormApplicantsPerson" class="btn btn-default" type="button">Индивидуальная заявка</button>
		</div>
		

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

				<!--<div class="form-group">-->
				<!--	<label class="required" for="requestFormPersonPhoto">Портретное фото участника (JPEG или PNG, не менее 600 &times; 800)</label>-->
				<!--	<input id="requestFormPersonPhoto" type="file" name="person_photo" required accept="image/*">-->
				<!--</div>-->
				
				<hr>
				
				<fieldset class="work">
					<div class="row">
						<div class="col-sm-6">
							<div class="form-group">
								<label class="required" for="requestFormPersonWorkName">Название проекта</label>
								<input id="requestFormPersonWorkName" class="form-control" type="text" name="person_workname" required>
							</div>
						</div>
						
						<!--<div class="col-sm-6">-->
						<!--	<div class="form-group">-->
						<!--		<label class="required" for="requestFormPersonWorkAbout">Формат, техника (пример: 50×70 см, холст, масло)</label>-->
						<!--		<input id="requestFormPersonWorkAbout" class="form-control" type="text" name="person_workabout" required>-->
						<!--	</div>-->
						<!--</div>-->
					</div>
					
					<div class="row">
						<div class="col-sm-12">
							<div class="form-group">
								<label class="required" for="requestFormPersonWorkPicture">Изображения для предварительного просмотра (от 3 до 8, размер до 10 мБ)</label>
								<input id="requestFormPersonWorkPicture" type="file" name="person_work" required multiple accept="image/*">
							</div>
						</div>
					</div>
					
					<hr>
				</fieldset>
				
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
				<button class="btn btn-lg btn-primary submit" type="submit">Отправить заявку</button>
			</div>
		</div>
	</div>
</form>
