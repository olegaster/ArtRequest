<!DOCTYPE html>
<html>
<head>

<meta charset="UTF-8">
<title>{$_lang['artrequest.requests_print']}</title>

<style>
@media screen { section + section { border-top: 3px dashed #000; padding-top: 40px; } }
section { page-break-after: always; }
section:last-child { page-break-after: auto; }
section, article { margin-bottom: 40px; }
section:after, article:after { clear: both; content: ''; display: table; }
img { display: block; float: right; margin: 0 0 20px 20px; max-height: 200px; }
dl { margin: 1px 0 0; }
dt { float: left; font-weight: 700; }
dt.newline { float: none; }
dd { margin: 0 0 10px; }
dd:last-child { margin-bottom: 0; }
dd:after { content: '\a0'; }
dt.newline + dd:after { content: ''; }
</style>

</head>
<body>

{foreach from=$requests item=request}
	<section>
		<article>
			<dl>
				<dt>ID представления:&nbsp;</dt><dd>{$request.id|escape}</dd>
			</dl>
		</article>
		
		{foreach from=$request.persons item=person}
			<article>
				{if $person.photo}<img src="{$person.photo}" alt="Портретное фото кандидата">{/if}
				
				<dl>
					<dt>ФИО кандидата:&nbsp;</dt><dd>{$person.name|escape}</dd>
					<dt>Пол:&nbsp;</dt><dd>{$person.gender|escape}</dd>
					<dt>Дата рождения:&nbsp;</dt><dd>{$person.birthdate|escape}</dd>
					<dt>Федеральный округ:&nbsp;</dt><dd>{$person.federaldistrict|escape}</dd>
					<dt>Субъект федерации:&nbsp;</dt><dd>{$person.federalsubject|escape}</dd>
					<dt>Город (населенный пункт):&nbsp;</dt><dd>{$person.city|escape}</dd>
					<dt>Адрес кандидата:&nbsp;</dt><dd>{$person.address|escape}</dd>
					<dt>Почтовый индекс:&nbsp;</dt><dd>{$person.postal|escape}</dd>
					<dt>Представление кандидата связано:&nbsp;</dt><dd>{$person.reason|escape}</dd>
					<dt class="newline">Наличие наград у кандидата:&nbsp;</dt><dd>{$person.awards|escape|nl2br}</dd>
					<dt class="newline">Контактные данные кандидата или членов семьи:&nbsp;</dt><dd>{$person.contacts|escape|nl2br}</dd>
				</dl>
			</article>
		{/foreach}
		
		{foreach from=$request.orgpersons item=orgperson}
			<article>
				{if $orgperson.photo}<img src="{$orgperson.photo}" alt="Портретное фото кандидата">{/if}
				
				<dl>
					<dt>ФИО кандидата:&nbsp;</dt><dd>{$orgperson.name|escape}</dd>
					<dt>Пол:&nbsp;</dt><dd>{$orgperson.gender|escape}</dd>
					<dt>Дата рождения:&nbsp;</dt><dd>{$orgperson.birthdate|escape}</dd>
					<dt>Федеральный округ:&nbsp;</dt><dd>{$orgperson.federaldistrict|escape}</dd>
					<dt>Субъект федерации:&nbsp;</dt><dd>{$orgperson.federalsubject|escape}</dd>
					<dt>Город (населенный пункт):&nbsp;</dt><dd>{$orgperson.city|escape}</dd>
					<dt>Адрес кандидата:&nbsp;</dt><dd>{$orgperson.address|escape}</dd>
					<dt>Почтовый индекс:&nbsp;</dt><dd>{$orgperson.postal|escape}</dd>
					<dt>Представление кандидата связано:&nbsp;</dt><dd>{$orgperson.reason|escape}</dd>
					<dt class="newline">Наличие наград у кандидата:&nbsp;</dt><dd>{$orgperson.awards|escape|nl2br}</dd>
					<dt class="newline">Контактные данные кандидата или членов семьи:&nbsp;</dt><dd>{$orgperson.contacts|escape|nl2br}</dd>
				</dl>
			</article>
		{/foreach}
		
		<dl>
			<dt class="newline">Контактные данные представляющего лица и организации:&nbsp;</dt><dd>{$request.contacts|escape|nl2br}</dd>
			<dt>Представление создано:&nbsp;</dt><dd>{$request.createdon|escape}</dd>
			{if $request.clientemail}<dt>Адрес электронной почты:&nbsp;</dt><dd>{$request.clientemail|escape}</dd>{/if}
		</dl>
	</section>
{/foreach}

</body>
</html>
