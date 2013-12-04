<?php

error_reporting(E_ALL | E_STRICT);
chdir('..');
require_once('app/Mage.php');
Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
umask(0);

printf("Running manual PClass import. This may take a while...\n");
Mage::getModel('klarna/import')->run();
printf("Done.\n");
