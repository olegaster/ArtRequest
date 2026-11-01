<?php
$xpdo_meta_map['ArtRequestRequest']= array (
  'package' => 'artrequest',
  'version' => '1.1',
  'extends' => 'xPDOSimpleObject',
  'table' => 'artrequest_requests',
  'fields' => 
  array (
    'createdon' => NULL,
    'clientemail' => NULL,
    'clientip' => NULL,
    'hidden' => NULL,
    'contacts' => NULL,
    'letter' => NULL,
  ),
  'fieldMeta' => 
  array (
    'createdon' => 
    array (
      'phptype' => 'datetime',
      'dbtype' => 'datetime',
      'null' => false,
    ),
    'clientemail' => 
    array (
      'phptype' => 'string',
      'dbtype' => 'varchar',
      'precision' => '255',
      'null' => false,
    ),
    'clientip' => 
    array (
      'phptype' => 'string',
      'dbtype' => 'varchar',
      'precision' => '20',
      'null' => false,
    ),
    'hidden' => 
    array (
      'phptype' => 'boolean',
      'dbtype' => 'bool',
      'null' => false,
    ),
    'contacts' => 
    array (
      'phptype' => 'string',
      'dbtype' => 'text',
      'null' => false,
    ),
    'letter' => 
    array (
      'phptype' => 'string',
      'dbtype' => 'varchar',
      'precision' => '255',
      'null' => false,
    ),
  ),
  'composites' => 
  array (
    'Persons' => 
    array (
      'class' => 'ArtRequestPerson',
      'local' => 'id',
      'foreign' => 'request',
      'cardinality' => 'many',
      'owner' => 'local',
    ),
    'OrgPersons' => 
    array (
      'class' => 'ArtRequestOrgPerson',
      'local' => 'id',
      'foreign' => 'request',
      'cardinality' => 'many',
      'owner' => 'local',
    ),
  ),
);
