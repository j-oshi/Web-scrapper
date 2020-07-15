<?php

include './scrapper.php';

$scrapper = new scrapper('https://jobs.sanctuary-group.co.uk/search');
$scrapper->getChildNodeOfResult();
unset($scrapper);