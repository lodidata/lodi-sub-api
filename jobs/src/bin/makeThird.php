<?php
global $app;
$index = new Logic\Admin\AdminIndex($app->getContainer());
$index->makeThird();