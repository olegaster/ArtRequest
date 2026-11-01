<?php
$xpdo_meta_map['ArtRequestOrgPerson']= array (
  'package' => 'artrequest',
  'version' => '1.1',
  'extends' => 'xPDOSimpleObject',
  'table' => 'artrequest_orgpersons',
  'fields' => 
  array (
    'request' => NULL,
    'name' => NULL,
    'ismale' => NULL,
    'birthdate' => NULL,
    'federalsubject' => NULL,
    'city' => NULL,
    'address' => NULL,
    'postal' => NULL,
    'reason' => NULL,
    'awards' => NULL,
    'contacts' => NULL,
    'photo' => NULL,
    'work1' => NULL,
    'work1name' => NULL,
    'work1about' => NULL,
    'work1framing' => NULL,
    'work2' => NULL,
    'work2name' => NULL,
    'work2about' => NULL,
    'work2framing' => NULL,
    'work3' => NULL,
    'work3name' => NULL,
    'work3about' => NULL,
    'work3framing' => NULL,
  ),
  'fieldMeta' => 
  array (
    'request' => 
    array (
      'phptype' => 'integer',
      'dbtype' => 'int',
      'attributes' => 'unsigned',
      'null' => false,
    ),
    'name' => 
    array (
      'phptype' => 'string',
      'dbtype' => 'varchar',
      'precision' => '255',
      'null' => false,
    ),
    'ismale' => 
    array (
      'phptype' => 'boolean',
      'dbtype' => 'bool',
      'null' => false,
    ),
    'birthdate' => 
    array (
      'phptype' => 'date',
      'dbtype' => 'date',
      'null' => false,
    ),
    'federalsubject' => 
    array (
      'phptype' => 'string',
      'dbtype' => 'varchar',
      'precision' => '255',
      'null' => false,
    ),
    'city' => 
    array (
      'phptype' => 'string',
      'dbtype' => 'varchar',
      'precision' => '255',
      'null' => false,
    ),
    'address' => 
    array (
      'phptype' => 'string',
      'dbtype' => 'varchar',
      'precision' => '255',
      'null' => false,
    ),
    'postal' => 
    array (
      'phptype' => 'string',
      'dbtype' => 'varchar',
      'precision' => '20',
      'null' => false,
    ),
    'reason' => 
    array (
      'phptype' => 'integer',
      'dbtype' => 'tinyint',
      'attributes' => 'unsigned',
      'null' => false,
    ),
    'awards' => 
    array (
      'phptype' => 'string',
      'dbtype' => 'text',
      'null' => false,
    ),
    'contacts' => 
    array (
      'phptype' => 'string',
      'dbtype' => 'text',
      'null' => false,
    ),
    'photo' => 
    array (
      'phptype' => 'string',
      'dbtype' => 'varchar',
      'precision' => '255',
      'null' => false,
    ),
    'work1' => 
    array (
      'phptype' => 'string',
      'dbtype' => 'varchar',
      'precision' => '255',
      'null' => false,
    ),
    'work1name' => 
    array (
      'phptype' => 'string',
      'dbtype' => 'varchar',
      'precision' => '255',
      'null' => false,
    ),
    'work1about' => 
    array (
      'phptype' => 'string',
      'dbtype' => 'varchar',
      'precision' => '255',
      'null' => false,
    ),
    'work1framing' => 
    array (
      'phptype' => 'string',
      'dbtype' => 'varchar',
      'precision' => '255',
      'null' => false,
    ),
    'work2' => 
    array (
      'phptype' => 'string',
      'dbtype' => 'varchar',
      'precision' => '255',
      'null' => false,
    ),
    'work2name' => 
    array (
      'phptype' => 'string',
      'dbtype' => 'varchar',
      'precision' => '255',
      'null' => false,
    ),
    'work2about' => 
    array (
      'phptype' => 'string',
      'dbtype' => 'varchar',
      'precision' => '255',
      'null' => false,
    ),
    'work2framing' => 
    array (
      'phptype' => 'string',
      'dbtype' => 'varchar',
      'precision' => '255',
      'null' => false,
    ),
    'work3' => 
    array (
      'phptype' => 'string',
      'dbtype' => 'varchar',
      'precision' => '255',
      'null' => false,
    ),
    'work3name' => 
    array (
      'phptype' => 'string',
      'dbtype' => 'varchar',
      'precision' => '255',
      'null' => false,
    ),
    'work3about' => 
    array (
      'phptype' => 'string',
      'dbtype' => 'varchar',
      'precision' => '255',
      'null' => false,
    ),
    'work3framing' => 
    array (
      'phptype' => 'string',
      'dbtype' => 'varchar',
      'precision' => '255',
      'null' => false,
    ),
  ),
  'aggregates' => 
  array (
    'Request' => 
    array (
      'class' => 'ArtRequestRequest',
      'local' => 'request',
      'foreign' => 'id',
      'cardinality' => 'one',
      'owner' => 'foreign',
    ),
  ),
);
