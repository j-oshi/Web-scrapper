<?php

class scrapper
{
    private $url = '';
    private $htmlContent = '';
    private $pageLinks = [];
    private $queryString = [];
    private $markUpData = [];

    public function __construct($url)
    {
        $this->url = $url;

        $this->nodeContent();
    }

    // Get url page content
    private function pageContent($url)
    {
        return file_get_contents("$url");
    }

    /**
     * Checks if url exist when request is made and return status.
     */
    public function page_exist($url, $response_code = 200)
    {
        $headers = get_headers($url);
        if (substr($headers[0], 9, 3) == $response_code) {
            return true;
        } else {
            return false;
        }
    }

    /*
     * Search dom content for pagination markup
     * return array of pagination url
     */
    private function paginationQueryStrings($url)
    {
        $pageContent = $this->pageContent($url);
        $this->htmlContent = $pageContent;
        if (isset($pageContent)) {
            $dom = new DOMDocument();
            @$dom->loadHTML($pageContent);
    
            $xpath = new DOMXPath($dom);
            $pagination = $xpath->query('//ul[@class="pagination"]//li/a/@href');
            $queryStringList = '';
            if (isset($pagination)) {
                $remove_duplicate = [];
                foreach ($pagination as $node) {
                    $remove_duplicate[] = $node->nodeValue;
                }
                $queryStringList = array_unique($remove_duplicate);
            }
    
            if (null == $queryStringList) {
                return;
            }
    
            return $queryStringList;
        }
    }

    /*
     * Get result header title
     * return array
     */
    public function nodeContent()
    {
        $siteUrl = $this->url;
        if (!isset($siteUrl)) {
            return;
        }

        $queryStringList = $this->paginationQueryStrings($siteUrl);
        if (!empty($queryStringList)) {
            set_time_limit(0);
            foreach ($queryStringList as $query) {
                $page_url = $siteUrl . $query;
                if ($this->page_exist($page_url)) {
                    $pageContent = $this->pageContent($page_url);
                    $this->getFullPageLink($pageContent);
                }
            }
            sleep(2);
        } else {
            if (isset($this->htmlContent)) {
                print '<pre>';
                print_r('thisis ia bb');
                print '</pre>';
                $this->getFullPageLink($this->htmlContent);
            }
        }
    }

    /*
     * Get all link to coresponding full description page
     * return array of markup data
     */
    private function getFullPageLink($page_content)
    {
        if (isset($page_content)) {
            $dom = new DOMDocument();

            @$dom->loadHTML($page_content);

            // Get link to each full description page
            $xpath = new DOMXPath($dom);
            $anchorLinks = $xpath->query('//tbody/tr/td/span/a/@href');

            foreach ($anchorLinks as $nodeLink) {
                array_push($this->pageLinks, $nodeLink->nodeValue);
            }
        }
    }

    /*
     * Get all full description page data
     * return array of markup data
     */
    public function getFullPageData()
    {
        if (isset($this->pageLinks)) {
            $baseUrl = 'https://jobs.sanctuary-group.co.uk';
            $links = $this->pageLinks;
            foreach ($links as $siteLink) {
                $link = $baseUrl . $siteLink;
                if ($this->page_exist($link)) {
                    $this->extractFullPageData($link);
                }
            }
            $this->saveToCSV();
        }
    }

    /*
     * Get all full description page data
     * return array of markup data
     */
    public function extractFullPageData($url)
    {
        if (isset($url)) {
            $page_content = $this->pageContent($url);
            $dom = new DOMDocument();

            @$dom->loadHTML($page_content);

            $data = [];

            $xpath = new DOMXPath($dom);

            $titleValue = $xpath->evaluate('//span[contains(., "Title:")]');
            $data['Job Title'] = $this->cleanWord($titleValue[0]->parentNode->textContent);

            $nameValue = $xpath->evaluate('//span[contains(., "Title:")]');
            $data['Name of Care Home'] = $this->cleanWord($nameValue[0]->parentNode->textContent);

            $locationValue = $xpath->evaluate('//span[contains(., "Location:")]');
            $data['Location'] = is_object($locationValue[0]) ? $this->cleanWord($locationValue[0]->parentNode->textContent) : '';

            $departmentValue = $xpath->evaluate('//span[contains(., "Department:")]');
            $data['Department'] = is_object($departmentValue[0]) ? $this->cleanWord($departmentValue[0]->parentNode->textContent) : '';

            $operationValue = $xpath->evaluate('//span[contains(., "Operation:")]');
            $data['Operation'] = is_object($operationValue[0]) ? $this->cleanWord($operationValue[0]->parentNode->textContent) : '';

            $requisitionValue = $xpath->evaluate('//span[contains(., "Requisition Number:")]');
            $data['Requisition Number'] = is_object($requisitionValue[0]) ? $this->cleanWord($requisitionValue[0]->parentNode->textContent) : '';

            $salaryValue = $xpath->evaluate('//p/span[contains(., "£")]');
            $data['Salary or Hourly Rate'] = is_object($salaryValue[0]) ? $salaryValue[0]->parentNode->textContent : '';

            $dateValue = $xpath->evaluate('//p/span[contains(., "Closing Date:")]');
            $data['Closing Date'] = is_object($dateValue[0]) ? $this->cleanWord($dateValue[0]->textContent) : '';

            $descriptionValue = $xpath->query('//span[@class="jobdescription"]/p');
            $filterDescription = '';
            foreach ($descriptionValue as $nodename) {
                if (preg_match('(£|Closing Date:)', $nodename->textContent) !== 1) {
                    $filterDescription .= $nodename->textContent;
                }
            }
            $data['Description'] = $filterDescription;

            array_push($this->markUpData, $data);
        }
    }

    private function cleanWord($String)
    {
        $splitString = explode(':', $String);
        return trim(preg_replace('/\s+/', ' ', $splitString[1]));
    }

    private function saveToCSV()
    {
        $data = $this->markUpData;
        if (isset($data)) {
            $fp = fopen($_SERVER["DOCUMENT_ROOT"]."/web-scrapper/result.csv", "w+");
            fputcsv($fp, array_keys($data[0]));
            foreach($data AS $values){
                fputcsv($fp, $values);
            }
            fclose($fp);
        }
    }

    // Return node array
    public function Result()
    {
        if (isset($this->markUpData)) {
            return $this->markUpData;
        }
    }
}