<?php

include './scrapper.php';

print '<pre>';
print_r('Please wait, it might take a while....');
print '</pre>';

$scrapper = new scrapper('https://jobs.sanctuary-group.co.uk/search');
$scrapper->getFullPageData();
print '<pre>';
print_r($scrapper->Result());
print '</pre>';
unset($scrapper);