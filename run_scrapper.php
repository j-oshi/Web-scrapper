<?php

include './scrapper.php';

$scrapper = new scrapper('https://jobs.sanctuary-group.co.uk/search');
$scrapper->getFullPageData();
print '<pre>';
print_r($scrapper->Result());
print '</pre>';
unset($scrapper);